<?php

namespace App\Services\Seo\Board;

use App\Jobs\Seo\AiAutoFixSeoJob;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoFixBatch;
use App\Models\SeoMeta;
use App\Models\SeoProject;
use App\Models\SeoScoreHistory;
use App\Services\Seo\OnPage\Features\TruSeoAnalysisService;
use App\Services\Seo\Providers\SeoProviderManager;
use App\Services\Seo\Providers\SeoProviderReliability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Powers the AI SEO Board: scans every supported entity (Product, Category,
 * Page, Blog), computes a TruSEO score from cached seo_meta + inline columns,
 * and produces row data the admin table renders.
 *
 * The board is read-mostly — the {@see scanAndPaginate()} entry point is
 * designed to stream through tens of thousands of rows without loading them
 * all into memory. Individual fixes are applied via {@see applyAiFix()}.
 */
class AiSeoBoardService
{
    public const SUPPORTED_TYPES = ['product', 'category', 'page', 'blog'];
    public const SEO_DONE_SCORE = 80;
    public const AUTOPILOT_TYPES = ['page', 'category', 'product'];
    public const MAX_AUTOPILOT_ATTEMPTS = 3;

    /** @var array<string,array{class:string,label:string,url:string,name:string,description:?string,image:?string}> */
    protected array $typeMap;

    public function __construct(protected ?TruSeoAnalysisService $analyzer = null)
    {
        $this->analyzer = $this->analyzer ?: new TruSeoAnalysisService();

        $this->typeMap = [
            'product'  => ['class' => Product::class,  'label' => 'Product'],
            'category' => ['class' => Category::class, 'label' => 'Category'],
            'page'     => ['class' => Page::class,     'label' => 'Page'],
            'blog'     => ['class' => Blog::class,     'label' => 'Blog'],
        ];
    }

    /** Site-wide summary stats used by the dashboard header. */
    public function siteSummary(): array
    {
        $stats = [
            'total'       => 0,
            'with_meta'   => 0,
            'with_og'     => 0,
            'with_schema' => 0,
            'avg_score'   => 0,
            'critical'    => 0,
            'warning'     => 0,
            'good'        => 0,
        ];

        if (!Schema::hasTable('seo_meta')) {
            return $stats;
        }

        foreach (array_keys($this->typeMap) as $type) {
            $stats['total'] += $this->baseQuery($type)->count();
        }

        $metaQuery = SeoMeta::query()
            ->whereIn('model_type', array_column($this->typeMap, 'class'));

        $stats['with_meta']   = (clone $metaQuery)->whereNotNull('meta_title')->whereNotNull('meta_description')->count();
        $stats['with_og']     = (clone $metaQuery)->whereNotNull('og_image')->count();
        $stats['with_schema'] = (clone $metaQuery)->whereNotNull('schema_json')->count();
        $stats['avg_score']   = (int) round((clone $metaQuery)->whereNotNull('seo_score')->avg('seo_score') ?? 0);
        $stats['critical']    = (clone $metaQuery)->where('seo_score', '<', 50)->count();
        $stats['warning']     = (clone $metaQuery)->whereBetween('seo_score', [50, 79])->count();
        $stats['good']        = (clone $metaQuery)->where('seo_score', '>=', 80)->count();

        return $stats;
    }

    public function dashboardUrlInventory(int $doneLimit = 10, int $pendingLimit = 12): array
    {
        $done = collect();
        $pending = collect();

        if (!Schema::hasTable('seo_meta')) {
            return [
                'done' => collect(),
                'pending' => collect(),
                'done_count' => 0,
                'pending_count' => 0,
                'total_count' => 0,
            ];
        }

        foreach (array_keys($this->typeMap) as $type) {
            $query = $this->baseQuery($type)->latest('updated_at')->limit(max($doneLimit, $pendingLimit) * 2);
            foreach ($query->get() as $entity) {
                $row = $this->buildRow($entity, $type);
                if ($this->isSeoDoneRow($row)) {
                    $done->push($row);
                } else {
                    $pending->push($row);
                }
            }
        }

        return [
            'done' => $done->sortByDesc('score')->take($doneLimit)->values(),
            'pending' => $pending->sortBy('score')->take($pendingLimit)->values(),
            'done_count' => $this->countSeoDoneUrls(),
            'pending_count' => $this->countSeoPendingUrls(),
            'total_count' => collect(array_keys($this->typeMap))->sum(fn($type) => $this->baseQuery($type)->count()),
        ];
    }

    public function collectPendingTargetsAcrossTypes(int $limit = 10, array $types = self::AUTOPILOT_TYPES): array
    {
        $limit = max(1, min(100, $limit));

        return $this->nextAutopilotTargetPreview($limit, $types)
            ->map(fn(array $row) => ['type' => $row['type'], 'id' => (int) $row['id']])
            ->values()
            ->all();
    }

    protected function isSeoDoneRow(array $row): bool
    {
        return !empty($row['has_meta'])
            && !empty($row['has_focus_kw'])
            && !empty($row['has_schema'])
            && (int) ($row['score'] ?? 0) >= self::SEO_DONE_SCORE;
    }

    public function pendingBreakdownByType(array $types = self::AUTOPILOT_TYPES): array
    {
        $rows = [];

        foreach ($types as $type) {
            if (!isset($this->typeMap[$type])) {
                continue;
            }

            $class = $this->typeMap[$type]['class'];
            $total = $this->baseQuery($type)->count();
            $done = Schema::hasTable('seo_meta')
                ? SeoMeta::query()
                    ->where('model_type', $class)
                    ->whereNotNull('meta_title')
                    ->whereNotNull('meta_description')
                    ->whereNotNull('focus_keyword')
                    ->whereNotNull('schema_json')
                    ->where('seo_score', '>=', self::SEO_DONE_SCORE)
                    ->count()
                : 0;

            $missingMetaQuery = $this->baseQuery($type);
            if (Schema::hasTable('seo_meta')) {
                $this->applyMissingFilter($missingMetaQuery, $class, 'meta');
            }

            $rows[$type] = [
                'label' => $this->typeMap[$type]['label'],
                'total' => $total,
                'done' => $done,
                'pending' => max(0, $total - $done),
                'missing_meta' => Schema::hasTable('seo_meta') ? $missingMetaQuery->count() : $total,
                'completion' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ];
        }

        return $rows;
    }

    public function nextAutopilotTargetPreview(int $limit = 10, array $types = self::AUTOPILOT_TYPES): Collection
    {
        $limit = max(1, min(100, $limit));
        $attempts = $this->recentBatchTargetAttempts();
        $retryRows = $this->pendingRowsFromPreviousBatches($limit, $types, $attempts);
        $retryKeys = $retryRows
            ->mapWithKeys(fn(array $row) => [$this->targetKey($row) => true])
            ->all();

        return $retryRows
            ->concat($this->pendingAutopilotRows($limit, $types)
                ->reject(function (array $row) use ($attempts, $retryKeys): bool {
                    $key = $this->targetKey($row);

                    return isset($retryKeys[$key])
                        || (int) ($attempts[$key] ?? 0) >= self::MAX_AUTOPILOT_ATTEMPTS;
                }))
            ->take($limit)
            ->values();
    }

    protected function pendingRowsFromPreviousBatches(int $limit, array $types, array $attempts): Collection
    {
        if (!Schema::hasTable('seo_fix_batches')) {
            return collect();
        }

        $allowedTypes = array_flip($this->orderedAutopilotTypes($types));
        $rows = collect();

        $batches = SeoFixBatch::query()
            ->whereIn('status', [
                SeoFixBatch::STATUS_QUEUED,
                SeoFixBatch::STATUS_RUNNING,
                SeoFixBatch::STATUS_COMPLETED,
                SeoFixBatch::STATUS_FAILED,
            ])
            ->latest()
            ->limit(30)
            ->get()
            ->sortBy(function (SeoFixBatch $batch): string {
                $active = in_array($batch->status, [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING], true);

                return $active
                    ? '0-' . str_pad((string) $batch->id, 9, '0', STR_PAD_LEFT)
                    : '1-' . str_pad((string) (999999999 - (int) $batch->id), 9, '0', STR_PAD_LEFT);
            });

        foreach ($batches as $batch) {
            $active = in_array($batch->status, [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING], true);
            $targets = $batch->target_ids ?? [];
            $offset = $active ? (int) $batch->processed : 0;

            foreach (array_slice($targets, $offset) as $target) {
                $type = (string) ($target['type'] ?? '');
                $id = (int) ($target['id'] ?? 0);
                $key = $type . ':' . $id;

                if (!$type || !$id || !isset($allowedTypes[$type]) || $rows->has($key)) {
                    continue;
                }
                if (!$active && (int) ($attempts[$key] ?? 0) >= self::MAX_AUTOPILOT_ATTEMPTS) {
                    continue;
                }

                $class = $this->typeMap[$type]['class'];
                $entity = $class::find($id);
                if (!$entity) {
                    continue;
                }

                $row = $this->buildRow($entity, $type);
                if ($this->isSeoDoneRow($row)) {
                    continue;
                }

                $row = $this->buildAutopilotPreviewRow($row);
                $row['queue_source'] = $active ? 'active_resume' : 'previous_retry';
                $row['retry_from_batch'] = (int) $batch->id;
                $row['attempt'] = (int) ($attempts[$key] ?? 0) + ($active ? 0 : 1);
                $rows->put($key, $row);

                if ($rows->count() >= $limit) {
                    return $rows->values();
                }
            }
        }

        return $rows->values();
    }

    protected function recentBatchTargetAttempts(): array
    {
        if (!Schema::hasTable('seo_fix_batches')) {
            return [];
        }

        $attempts = [];
        $batches = SeoFixBatch::query()
            ->whereIn('status', [
                SeoFixBatch::STATUS_QUEUED,
                SeoFixBatch::STATUS_RUNNING,
                SeoFixBatch::STATUS_COMPLETED,
                SeoFixBatch::STATUS_FAILED,
            ])
            ->latest()
            ->limit(100)
            ->get();

        foreach ($batches as $batch) {
            foreach ($this->attemptedTargetsForBatch($batch) as $target) {
                $key = $this->targetKey($target);
                if ($key === ':0') {
                    continue;
                }
                $attempts[$key] = (int) ($attempts[$key] ?? 0) + 1;
            }
        }

        return $attempts;
    }

    protected function attemptedTargetsForBatch(SeoFixBatch $batch): array
    {
        $targets = array_values($batch->target_ids ?? []);
        $processed = min(max(0, (int) $batch->processed), count($targets));

        return array_slice($targets, 0, $processed);
    }

    protected function targetKey(array $target): string
    {
        return (string) ($target['type'] ?? '') . ':' . (int) ($target['id'] ?? 0);
    }

    protected function pendingAutopilotRows(int $limit, array $types): Collection
    {
        $preview = collect();
        $candidateLimit = max(40, $limit * 8);

        foreach ($this->orderedAutopilotTypes($types) as $type) {
            if (!isset($this->typeMap[$type])) {
                continue;
            }

            $class = $this->typeMap[$type]['class'];
            $entities = collect();

            if (Schema::hasTable('seo_meta')) {
                foreach (['meta', 'focus', 'schema'] as $missing) {
                    $query = $this->baseQuery($type);
                    $this->applyMissingFilter($query, $class, $missing);
                    $entities = $entities->merge($query->latest('updated_at')->limit($candidateLimit)->get());
                }
            }

            $entities = $entities->merge(
                $this->baseQuery($type)->latest('updated_at')->limit($candidateLimit)->get()
            );

            foreach ($entities->unique(fn($entity) => $type . ':' . $entity->getKey()) as $entity) {
                $row = $this->buildRow($entity, $type);
                if ($this->isSeoDoneRow($row)) {
                    continue;
                }

                $preview->push($this->buildAutopilotPreviewRow($row));
            }
        }

        return $preview
            ->sort(function (array $left, array $right): int {
                $typePriority = array_flip(self::AUTOPILOT_TYPES);
                $leftType = $typePriority[$left['type'] ?? ''] ?? PHP_INT_MAX;
                $rightType = $typePriority[$right['type'] ?? ''] ?? PHP_INT_MAX;

                return $leftType <=> $rightType
                    ?: ((int) ($right['priority_score'] ?? 0) <=> (int) ($left['priority_score'] ?? 0));
            })
            ->values();
    }

    protected function orderedAutopilotTypes(array $types): array
    {
        $typePriority = array_flip(self::AUTOPILOT_TYPES);

        return collect($types)
            ->unique()
            ->sortBy(fn(string $type) => $typePriority[$type] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    protected function buildAutopilotPreviewRow(array $row): array
    {
        $priorityScore = $this->autopilotPriorityScore($row);
        $row['priority_score'] = $priorityScore;
        $row['priority_label'] = $priorityScore >= 90 ? 'Critical' : ($priorityScore >= 70 ? 'High' : ($priorityScore >= 45 ? 'Medium' : 'Low'));
        $row['priority_reasons'] = $this->autopilotPriorityReasons($row);

        return $row;
    }

    protected function autopilotPriorityReasons(array $row): array
    {
        $reasons = [];

        if (!$row['has_meta']) {
            $reasons[] = 'Missing meta';
        }
        if (!$row['has_focus_kw']) {
            $reasons[] = 'Missing focus keyword';
        }
        if (!$row['has_schema']) {
            $reasons[] = 'Missing schema';
        }

        $score = (int) ($row['score'] ?? 0);
        if ($score < 50) {
            $reasons[] = 'Critical score';
        } elseif ($score < 70) {
            $reasons[] = 'Weak score';
        }

        $type = $row['type'] ?? '';
        if ($type === 'product') {
            $reasons[] = 'Revenue page';
        } elseif ($type === 'category') {
            $reasons[] = 'Category expansion';
        } elseif ($type === 'page') {
            $reasons[] = 'Trust page';
        }

        $haystack = Str::lower(($row['title'] ?? '') . ' ' . ($row['url'] ?? '') . ' ' . ($row['focus_keyword'] ?? ''));
        if (Str::contains($haystack, ['mississauga', 'brampton', 'toronto'])) {
            $reasons[] = 'Primary city intent';
        } elseif (Str::contains($haystack, ['etobicoke', 'vaughan', 'oakville', 'scarborough', 'markham', 'north york', 'burlington'])) {
            $reasons[] = 'GTA city intent';
        } else {
            $reasons[] = 'Needs Canada/GTA terms';
        }

        return array_slice(array_values(array_unique($reasons ?: ['Needs review'])), 0, 5);
    }

    public function offPageCampaignTargetPreview(int $limit = 10, array $types = self::AUTOPILOT_TYPES): Collection
    {
        $preview = collect();
        $limit = max(1, min(20, $limit));

        if (!Schema::hasTable('seo_meta')) {
            return $preview;
        }

        foreach ($types as $type) {
            if (!isset($this->typeMap[$type])) {
                continue;
            }

            $entities = $this->baseQuery($type)->latest('updated_at')->limit($limit * 6)->get();
            foreach ($entities as $entity) {
                $row = $this->buildRow($entity, $type);
                if (!$this->isSeoDoneRow($row)) {
                    continue;
                }

                $offPageScore = $this->offPagePriorityScore($row);
                $row['offpage_score'] = $offPageScore;
                $row['offpage_label'] = $offPageScore >= 90
                    ? 'Tier 1'
                    : ($offPageScore >= 75 ? 'Strong' : ($offPageScore >= 60 ? 'Ready' : 'Reserve'));
                $row['offpage_reasons'] = $this->offPagePriorityReasons($row);
                $preview->push($row);
            }
        }

        return $preview
            ->sortByDesc('offpage_score')
            ->take($limit)
            ->values();
    }

    protected function autopilotPriorityScore(array $row): int
    {
        $score = 0;
        if (!$row['has_meta']) {
            $score += 35;
        }
        if (!$row['has_focus_kw']) {
            $score += 20;
        }
        if (!$row['has_schema']) {
            $score += 20;
        }
        $seoScore = (int) ($row['score'] ?? 0);
        if ($seoScore < 40) {
            $score += 30;
        } elseif ($seoScore < 70) {
            $score += 18;
        } elseif ($seoScore < 80) {
            $score += 8;
        }

        $typeBoost = ['page' => 12, 'category' => 8, 'product' => 4, 'blog' => 2];
        $score += $typeBoost[$row['type'] ?? ''] ?? 0;

        $haystack = Str::lower(($row['title'] ?? '') . ' ' . ($row['url'] ?? '') . ' ' . ($row['focus_keyword'] ?? ''));
        if (!Str::contains($haystack, ['mississauga', 'brampton', 'toronto'])) {
            $score += 10;
        }
        if (!Str::contains($haystack, ['canada', 'gta', 'ontario'])) {
            $score += 6;
        }
        if (($row['type'] ?? '') === 'category' && !Str::contains($haystack, ['supplier', 'supplies', 'wholesale', 'trade'])) {
            $score += 6;
        }
        if (($row['type'] ?? '') === 'product' && !Str::contains($haystack, ['buy', 'shop', 'canada'])) {
            $score += 4;
        }

        return min(100, $score);
    }

    protected function offPagePriorityScore(array $row): int
    {
        $score = (int) floor(min(100, max(0, (int) ($row['score'] ?? 0))) / 2);

        $typeBoost = ['category' => 12, 'page' => 10, 'product' => 8, 'blog' => 4];
        $score += $typeBoost[$row['type'] ?? ''] ?? 0;

        if (!empty($row['has_schema'])) {
            $score += 10;
        }
        if (!empty($row['has_focus_kw'])) {
            $score += 8;
        }
        if (!empty($row['has_meta'])) {
            $score += 6;
        }

        $haystack = Str::lower(($row['title'] ?? '') . ' ' . ($row['url'] ?? '') . ' ' . ($row['focus_keyword'] ?? ''));
        if (Str::contains($haystack, ['mississauga', 'brampton', 'toronto'])) {
            $score += 10;
        }
        if (Str::contains($haystack, ['etobicoke', 'vaughan', 'oakville', 'scarborough', 'markham', 'north york', 'burlington'])) {
            $score += 6;
        }
        if (Str::contains($haystack, ['trade account', 'leave a review', 'review'])) {
            $score += 8;
        }

        $seoScore = (int) ($row['score'] ?? 0);
        if ($seoScore >= 85) {
            $score += 8;
        } elseif ($seoScore >= 75) {
            $score += 5;
        }

        return min(100, $score);
    }

    protected function offPagePriorityReasons(array $row): array
    {
        $reasons = ['SEO protected'];
        $type = $row['type'] ?? '';

        if ($type === 'category') {
            $reasons[] = 'Category authority';
        } elseif ($type === 'product') {
            $reasons[] = 'Money page';
        } elseif ($type === 'page') {
            $reasons[] = 'Trust asset';
        }

        if (!empty($row['has_schema'])) {
            $reasons[] = 'Schema ready';
        }
        if (!empty($row['has_focus_kw'])) {
            $reasons[] = 'Keyword ready';
        }

        $haystack = Str::lower(($row['title'] ?? '') . ' ' . ($row['url'] ?? '') . ' ' . ($row['focus_keyword'] ?? ''));
        if (Str::contains($haystack, ['mississauga', 'brampton', 'toronto'])) {
            $reasons[] = 'Primary city intent';
        } elseif (Str::contains($haystack, ['etobicoke', 'vaughan', 'oakville', 'scarborough', 'markham', 'north york', 'burlington'])) {
            $reasons[] = 'GTA city intent';
        }

        if (trim((string) get_setting('seo_competitor_urls', get_setting('ai_blog_competitor_urls', ''))) !== '') {
            $reasons[] = 'Competitor gap campaign';
        }

        return array_slice(array_values(array_unique($reasons)), 0, 5);
    }

    /**
     * Page through entities of one type, returning Board rows.
     *
     * @param array $filters supported keys:
     *   - search (string)   match on name/title
     *   - missing (string)  one of: meta, og, schema, focus, none
     *   - min_score (int)   include only rows >= this score
     *   - max_score (int)   include only rows <= this score
     *   - sort (string)     score_asc | score_desc | recent (default recent)
     */
    public function scanAndPaginate(string $type, array $filters = [], int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $this->assertType($type);
        $class = $this->typeMap[$type]['class'];

        $query = $this->baseQuery($type);
        $this->applySearch($query, $type, $filters['search'] ?? null);

        if (!empty($filters['missing'])) {
            $this->applyMissingFilter($query, $class, $filters['missing']);
        }

        if (isset($filters['min_score']) || isset($filters['max_score'])) {
            $this->applyScoreRange($query, $class, $filters['min_score'] ?? null, $filters['max_score'] ?? null);
        }

        $this->applySort($query, $class, $filters['sort'] ?? 'recent');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $rows = collect($paginator->items())->map(fn($entity) => $this->buildRow($entity, $type));

        return new LengthAwarePaginator(
            $rows,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->path()]
        );
    }

    /** Build a single Board row from any entity. Public so the fix flow can re-emit it. */
    public function buildRow(Model $entity, string $type): array
    {
        $meta  = $this->loadOrSynthesizeMeta($entity, $type);
        $score = $this->scoreEntity($entity, $type, $meta);

        return [
            'type'          => $type,
            'type_label'    => $this->typeMap[$type]['label'],
            'id'            => $entity->getKey(),
            'title'         => $this->displayName($entity, $type),
            'url'           => $this->urlFor($entity, $type),
            'slug'          => $entity->slug ?? null,
            'updated_at'    => optional($entity->updated_at)->format('Y-m-d H:i') ?? '—',

            'meta_title'        => $meta['meta_title'] ?? null,
            'meta_description'  => $meta['meta_description'] ?? null,
            'focus_keyword'     => $meta['focus_keyword'] ?? null,
            'og_image'          => $meta['og_image'] ?? null,
            'schema_present'    => !empty($meta['schema_json']),

            'score'         => $score['score'],
            'grade'         => $score['grade'],
            'issues'        => $score['issues'],

            'has_meta'      => !empty($meta['meta_title']) && !empty($meta['meta_description']),
            'has_og'        => !empty($meta['og_image']),
            'has_schema'    => !empty($meta['schema_json']),
            'has_focus_kw'  => !empty($meta['focus_keyword']),
        ];
    }

    /** Score a single entity via the existing TruSEO checks, persisting to seo_meta if applicable. */
    public function scoreEntity(Model $entity, string $type, array $meta = []): array
    {
        $meta = $meta ?: $this->loadOrSynthesizeMeta($entity, $type);

        $checks = $this->analyzer->runChecks(
            $meta['focus_keyword'] ?? '',
            $meta['meta_title']    ?? '',
            $meta['meta_description'] ?? '',
            $this->plainContent($entity, $type),
            $this->urlFor($entity, $type),
            $this->buildScoreContext($entity, $type, $meta)
        );

        $totalWeight = array_sum(array_column($checks, 'weight'));
        $earnedWeight = array_sum(array_map(fn($c) => $c['pass'] ? $c['weight'] : 0, $checks));
        $score = $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;

        $issues = [];
        foreach ($checks as $key => $c) {
            if (!$c['pass']) {
                $issues[] = $c['label'];
            }
        }

        return [
            'score'  => $score,
            'grade'  => $this->grade($score),
            'checks' => $checks,
            'issues' => $issues,
        ];
    }

    /**
     * Apply an AI-generated fix to a single pending entity. SEO-done rows are
     * protected, while weak pending rows can have bad meta/content repaired.
     *
     * @return array{applied: array<string,string>, score_before:int, score_after:int, source:string}
     */
    public function applyAiFix(string $type, int $id, ?string $providerName = null): array
    {
        $this->assertType($type);
        $class  = $this->typeMap[$type]['class'];
        $entity = $class::find($id);
        if (!$entity) {
            throw new \RuntimeException("Entity {$type}#{$id} not found.");
        }

        $currentRow = $this->buildRow($entity, $type);
        if ($this->isSeoDoneRow($currentRow)) {
            return [
                'applied'      => [],
                'score_before' => (int) ($currentRow['score'] ?? 0),
                'score_after'  => (int) ($currentRow['score'] ?? 0),
                'source'       => 'protected',
                'row'          => $currentRow,
            ];
        }

        $before = $this->scoreEntity($entity, $type);
        $snapshot = $this->mutationSnapshot($entity, $type);
        $meta   = $this->loadOrSynthesizeMeta($entity, $type);
        $name   = $this->displayName($entity, $type);
        $desc   = $this->plainContent($entity, $type);

        $applied = [];
        $source  = 'template';
        $actualProvider = null;

        $bundle = $this->askBestAiForSeoBundle(
            $providerName ?: get_setting('seo_suite_default_provider', config('seo.default_provider')),
            $name,
            $desc,
            $type
        );
        $aiData = $bundle['data'];
        $aiAttempts = $bundle['tried'] ?? [];
        $aiAttemptDetails = $bundle['attempt_details'] ?? [];
        if (!empty($aiData)) {
            $source = 'ai';
            $actualProvider = $bundle['provider'];
        }

        $patch = [];

        $candidateFocus = $this->normalizeFocusKeyword(
            $aiData['focus_keyword'] ?? ($meta['focus_keyword'] ?? null),
            $name,
            $type
        );

        if ($this->needsFocusKeywordRefresh($meta['focus_keyword'] ?? null)) {
            $patch['focus_keyword'] = $candidateFocus;
            $applied['focus_keyword'] = $patch['focus_keyword'];
        }

        $focusForCopy = $patch['focus_keyword'] ?? ($meta['focus_keyword'] ?? $candidateFocus);

        if ($this->needsMetaTitleRefresh($meta['meta_title'] ?? null, $focusForCopy)) {
            $patch['meta_title'] = $this->bestTitleForFocus($aiData['title'] ?? null, $focusForCopy, $name, $type);
            $applied['meta_title'] = $patch['meta_title'];
        }

        if ($this->needsMetaDescriptionRefresh($meta['meta_description'] ?? null, $focusForCopy)) {
            $patch['meta_description'] = $this->bestDescriptionForFocus($aiData['description'] ?? null, $focusForCopy, $name, $type);
            $applied['meta_description'] = $patch['meta_description'];
        }

        if ($this->needsSecondaryKeywordsRefresh($meta['secondary_keywords'] ?? null)) {
            $patch['secondary_keywords'] = $aiData['secondary_keywords'] ?? $this->canadaKeywordSet($name, $type);
            $applied['secondary_keywords'] = implode(', ', $patch['secondary_keywords']);
        }

        if (empty($meta['og_image'])) {
            $fallbackImage = $this->fallbackImage($entity, $type);
            if ($fallbackImage) {
                $patch['og_image']      = $fallbackImage;
                $patch['twitter_image'] = $fallbackImage;
                $applied['og_image']    = $fallbackImage;
            }
        }

        foreach ($this->advancedMetaPatch($entity, $type, $patch + $meta) as $field => $value) {
            $patch[$field]   = $value;
            $applied[$field] = $this->summarizeAppliedValue($value);
        }

        // Write content BEFORE schema so FAQ visibility is known: FAQPage schema
        // is only emitted when the matching Q&A is actually on the page.
        $contentPatch = $this->contentPatch($entity, $type, $aiData, $patch + $meta);
        if (!empty($contentPatch)) {
            $entity->forceFill($contentPatch)->save();
            foreach ($contentPatch as $field => $value) {
                $applied[$field] = Str::limit(strip_tags((string) $value), 120);
            }
        }

        $faqVisible = $this->injectFaqContent($entity, $type, $aiData['faqs'] ?? null);
        if ($faqVisible && empty($applied['faqs'])) {
            $applied['faqs'] = count($this->normalizeFaqs($aiData['faqs'] ?? null) ?? []) . ' FAQ(s) added';
        }

        if (empty($meta['schema_json'])) {
            $schemaMeta = $patch + $meta;
            $schemaMeta['faqs']        = $faqVisible ? ($aiData['faqs'] ?? null) : null;
            $schemaMeta['howto_steps'] = $aiData['howto_steps'] ?? null;
            $schema = $this->generateSchema($entity, $type, $schemaMeta);
            if ($schema) {
                $validation = (new \App\Services\Seo\Optimization\Features\SchemaValidatorService())->validate($schema);
                if ($validation['valid']) {
                    $patch['schema_json'] = $schema;
                    $applied['schema_json'] = json_encode($schema, JSON_UNESCAPED_SLASHES);
                } else {
                    // Never persist markup Google would reject — log and skip.
                    logger()->warning('AI SEO Board generated invalid schema; skipped', [
                        'type'   => $type,
                        'id'     => $entity->getKey(),
                        'errors' => $validation['errors'],
                    ]);
                }
            }
        }

        if (!empty($patch)) {
            $patch['last_analyzed_at'] = now();
            $this->persistMeta($entity, $type, $patch);
        }

        if (!empty($applied) && $actualProvider) {
            $applied['ai_provider'] = $actualProvider;
        }

        $entity->refresh();
        $afterScore = $this->scoreEntity($entity, $type);
        $qualityGateRolledBack = false;
        if ($this->shouldRollbackSeoMutation($before, $afterScore)) {
            $generatedScore = $afterScore['score'];
            $this->restoreMutationSnapshot($entity, $type, $snapshot);
            $entity->refresh();
            $afterScore = $this->scoreEntity($entity, $type);
            $qualityGateRolledBack = true;
            $source = 'quality_gate_rollback';
            $applied = [
                'quality_gate' => 'Rolled back because the generated SEO score regressed.',
            ];

            logger()->warning('AI SEO Board quality gate rolled back a regression', [
                'type' => $type,
                'id' => $entity->getKey(),
                'score_before' => $before['score'],
                'score_after_generated' => $generatedScore,
                'score_after_restore' => $afterScore['score'],
            ]);
        }
        $this->persistMeta($entity, $type, [
            'seo_score' => $afterScore['score'],
            'seo_grade' => $afterScore['grade'],
            'analysis_checks' => $afterScore['checks'],
            'last_analyzed_at' => now(),
        ]);
        $this->recordScoreHistory($entity, $type, $afterScore);

        $afterRow = $this->buildRow($entity->refresh(), $type);

        return [
            'applied'      => $applied,
            'score_before' => $before['score'],
            'score_after'  => $afterRow['score'],
            'source'       => $source,
            'ai_attempts'  => $aiAttempts,
            'ai_attempt_details' => $aiAttemptDetails,
            'quality_gate_rolled_back' => $qualityGateRolledBack,
            'row'          => $afterRow,
        ];
    }

    /**
     * Generate AI/template SEO suggestions for an entity WITHOUT persisting —
     * powers the "preview & edit before apply" workflow. Only proposes values
     * for fields that are currently empty (never overwrites curated content).
     *
     * @return array{type:string,id:int,title:string,url:string,source:string,score_before:int,current:array,suggestions:array}
     */
    public function previewAiFix(string $type, int $id, ?string $providerName = null): array
    {
        $this->assertType($type);
        $class  = $this->typeMap[$type]['class'];
        $entity = $class::find($id);
        if (!$entity) {
            throw new \RuntimeException("Entity {$type}#{$id} not found.");
        }

        $before = $this->scoreEntity($entity, $type);
        $meta   = $this->loadOrSynthesizeMeta($entity, $type);
        $name   = $this->displayName($entity, $type);
        $desc   = $this->plainContent($entity, $type);

        $source = 'template';
        $aiData = null;
        $bundle = $this->askBestAiForSeoBundle(
            $providerName ?: get_setting('seo_suite_default_provider', config('seo.default_provider')),
            $name,
            $desc,
            $type
        );
        if (!empty($bundle['data'])) {
            $aiData = $bundle['data'];
            $source = 'ai';
        }

        $suggestions = [];
        if (empty($meta['meta_title'])) {
            $suggestions['meta_title'] = $aiData['title'] ?? $this->templateTitle($name, $type);
        }
        if (empty($meta['meta_description'])) {
            $suggestions['meta_description'] = !empty($aiData['description'])
                ? $this->fitDescription($aiData['description'])
                : $this->templateDescription($name, $type);
        }
        if (empty($meta['focus_keyword'])) {
            $suggestions['focus_keyword'] = $aiData['focus_keyword'] ?? $this->primaryCanadaKeyword($name, $type);
        }
        if (empty($meta['secondary_keywords'])) {
            $kw = $aiData['secondary_keywords'] ?? $this->canadaKeywordSet($name, $type);
            $suggestions['secondary_keywords'] = implode(', ', $kw);
        }
        $suggestions['schema'] = empty($meta['schema_json']); // checkbox default for "generate schema"

        return [
            'type'         => $type,
            'id'           => $id,
            'title'        => $name,
            'url'          => $this->urlFor($entity, $type),
            'source'       => $source,
            'provider'     => $bundle['provider'] ?? null,
            'ai_attempts'  => $bundle['tried'] ?? [],
            'ai_attempt_details' => $bundle['attempt_details'] ?? [],
            'score_before' => $before['score'],
            'current'      => [
                'meta_title'       => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'focus_keyword'    => $meta['focus_keyword'] ?? null,
            ],
            'suggestions'  => $suggestions,
        ];
    }

    /**
     * Persist admin-approved (and possibly edited) suggestion values. Writes
     * only fields that are still empty, so a concurrent curated edit always wins.
     *
     * @param array $approved keys: meta_title, meta_description, focus_keyword,
     *                        secondary_keywords (string|array), schema (bool)
     */
    public function applyApprovedFix(string $type, int $id, array $approved): array
    {
        $this->assertType($type);
        $class  = $this->typeMap[$type]['class'];
        $entity = $class::find($id);
        if (!$entity) {
            throw new \RuntimeException("Entity {$type}#{$id} not found.");
        }

        $before = $this->scoreEntity($entity, $type);
        $meta   = $this->loadOrSynthesizeMeta($entity, $type);

        $patch = [];
        $applied = [];

        foreach (['meta_title', 'meta_description', 'focus_keyword'] as $field) {
            if (empty($meta[$field]) && !empty($approved[$field])) {
                $value = trim((string) $approved[$field]);
                if ($field === 'meta_description') {
                    $value = $this->fitDescription($value);
                }
                $patch[$field]   = $value;
                $applied[$field] = $value;
            }
        }

        if (empty($meta['secondary_keywords']) && !empty($approved['secondary_keywords'])) {
            $list = is_array($approved['secondary_keywords'])
                ? $approved['secondary_keywords']
                : array_values(array_filter(array_map('trim', explode(',', (string) $approved['secondary_keywords']))));
            if (!empty($list)) {
                $patch['secondary_keywords']   = array_values($list);
                $applied['secondary_keywords'] = implode(', ', $list);
            }
        }

        if (empty($meta['og_image'])) {
            $img = $this->fallbackImage($entity, $type);
            if ($img) {
                $patch['og_image']      = $img;
                $patch['twitter_image'] = $img;
                $applied['og_image']    = $img;
            }
        }

        foreach ($this->advancedMetaPatch($entity, $type, $patch + $meta) as $field => $value) {
            $patch[$field]   = $value;
            $applied[$field] = $this->summarizeAppliedValue($value);
        }

        if (empty($meta['schema_json']) && !empty($approved['schema'])) {
            $schema = $this->generateSchema($entity, $type, $patch + $meta);
            if ($schema) {
                $validation = (new \App\Services\Seo\Optimization\Features\SchemaValidatorService())->validate($schema);
                if ($validation['valid']) {
                    $patch['schema_json']   = $schema;
                    $applied['schema_json'] = json_encode($schema, JSON_UNESCAPED_SLASHES);
                }
            }
        }

        if (!empty($patch)) {
            $patch['last_analyzed_at'] = now();
            $this->persistMeta($entity, $type, $patch);
        }

        $entity->refresh();
        $afterScore = $this->scoreEntity($entity, $type);
        $this->persistMeta($entity, $type, [
            'seo_score'        => $afterScore['score'],
            'seo_grade'        => $afterScore['grade'],
            'analysis_checks'  => $afterScore['checks'],
            'last_analyzed_at' => now(),
        ]);
        $this->recordScoreHistory($entity, $type, $afterScore);

        $afterRow = $this->buildRow($entity->refresh(), $type);

        return [
            'applied'      => $applied,
            'score_before' => $before['score'],
            'score_after'  => $afterRow['score'],
            'source'       => 'approved',
            'row'          => $afterRow,
        ];
    }

    protected function recordScoreHistory(Model $entity, string $type, array $score): void
    {
        if (!Schema::hasTable('seo_score_histories')) {
            return;
        }

        try {
            SeoScoreHistory::create([
                'project_id' => optional(SeoProject::query()->first())->id,
                'seo_run_id' => null,
                'target_type' => $this->typeMap[$type]['class'],
                'target_id' => $entity->getKey(),
                'url' => $this->urlFor($entity, $type),
                'score' => $score['score'],
                'grade' => $score['grade'],
                'metrics' => $score,
                'recorded_at' => now(),
            ]);
        } catch (Throwable $e) {
            logger()->warning('AI SEO Board score history write failed', [
                'type' => $type,
                'id' => $entity->getKey(),
                'err' => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────────────

    protected function assertType(string $type): void
    {
        if (!isset($this->typeMap[$type])) {
            throw new \InvalidArgumentException("Unsupported SEO Board entity type: {$type}");
        }
    }

    protected function baseQuery(string $type): Builder
    {
        $class = $this->typeMap[$type]['class'];
        return $class::query();
    }

    protected function applySearch(Builder $query, string $type, ?string $search): void
    {
        if (!$search) {
            return;
        }
        $col = $type === 'page' || $type === 'blog' ? 'title' : 'name';
        if (Schema::hasColumn($query->getModel()->getTable(), $col)) {
            $query->where($col, 'like', '%' . $search . '%');
        }
    }

    protected function applyMissingFilter(Builder $query, string $class, string $missing): void
    {
        $sub = function ($column) use ($class) {
            return SeoMeta::query()
                ->select('model_id')
                ->where('model_type', $class)
                ->whereNotNull($column);
        };

        switch ($missing) {
            case 'meta':
                $query->whereNotIn('id', $sub('meta_title')->whereNotNull('meta_description'));
                break;
            case 'og':
                $query->whereNotIn('id', $sub('og_image'));
                break;
            case 'schema':
                $query->whereNotIn('id', $sub('schema_json'));
                break;
            case 'focus':
                $query->whereNotIn('id', $sub('focus_keyword'));
                break;
        }
    }

    protected function applyScoreRange(Builder $query, string $class, ?int $min, ?int $max): void
    {
        $sub = SeoMeta::query()->select('model_id')->where('model_type', $class);
        if ($min !== null) {
            $sub->where('seo_score', '>=', $min);
        }
        if ($max !== null) {
            $sub->where('seo_score', '<=', $max);
        }
        $query->whereIn('id', $sub);
    }

    protected function applySort(Builder $query, string $class, string $sort): void
    {
        switch ($sort) {
            case 'score_asc':
            case 'score_desc':
                // Join seo_meta for sort. Use a sub-select to keep null scores last/first.
                $direction = $sort === 'score_asc' ? 'asc' : 'desc';
                $query->leftJoinSub(
                    SeoMeta::query()->selectRaw('model_id, seo_score')->where('model_type', $class),
                    'sm',
                    fn($j) => $j->on('sm.model_id', '=', $query->getModel()->getTable() . '.id')
                )->orderBy('sm.seo_score', $direction);
                break;
            case 'recent':
            default:
                $query->latest('updated_at');
        }
    }

    protected function loadOrSynthesizeMeta(Model $entity, string $type): array
    {
        $class = $this->typeMap[$type]['class'];

        $inline = [
            'meta_title'       => $entity->meta_title ?? null,
            'meta_description' => $entity->meta_description ?? null,
            'focus_keyword'    => null,
            'og_image'         => $this->resolveImageColumn($entity),
            'twitter_image'    => $this->resolveImageColumn($entity),
            'schema_json'      => null,
        ];

        $meta = SeoMeta::query()
            ->where('model_type', $class)
            ->where('model_id', $entity->getKey())
            ->where('lang', config('app.locale', 'en'))
            ->first();

        if (!$meta) {
            return $inline;
        }

        // Merge: prefer seo_meta values, fall back to inline for any null seo_meta field.
        $arr = $meta->toArray();
        foreach ($inline as $key => $value) {
            if (empty($arr[$key]) && !empty($value)) {
                $arr[$key] = $value;
            }
        }
        return $arr;
    }

    protected function persistMeta(Model $entity, string $type, array $patch): void
    {
        $class = $this->typeMap[$type]['class'];

        $patch['seo_score'] = $patch['seo_score'] ?? null;

        SeoMeta::updateOrCreate(
            [
                'model_type' => $class,
                'model_id'   => $entity->getKey(),
                'lang'       => config('app.locale', 'en'),
            ],
            $patch
        );
    }

    /**
     * Fill page-level signals that are deterministic and safe to write without
     * replacing curated values. Schema is handled separately after content so
     * its FAQ markup stays aligned with the visible page.
     */
    protected function advancedMetaPatch(Model $entity, string $type, array $meta): array
    {
        $patch = [];
        $title = trim((string) ($meta['meta_title'] ?? ''));
        $description = trim((string) ($meta['meta_description'] ?? ''));
        $image = $meta['og_image'] ?? $meta['twitter_image'] ?? $this->fallbackImage($entity, $type);
        $desiredOgType = match ($type) {
            'product' => 'product',
            'blog' => 'article',
            default => 'website',
        };

        if (empty($meta['canonical_url'])) {
            $patch['canonical_url'] = $this->urlFor($entity, $type);
        }
        if (empty($meta['robots_meta'])) {
            $patch['robots_meta'] = 'index, follow, max-image-preview:large, max-snippet:-1';
        }
        if (empty($meta['og_title']) && $title !== '') {
            $patch['og_title'] = Str::limit($title, 80, '');
        }
        if (empty($meta['og_description']) && $description !== '') {
            $patch['og_description'] = Str::limit($description, 200, '');
        }
        if (empty($meta['og_type']) || ($meta['og_type'] === 'website' && $desiredOgType !== 'website')) {
            $patch['og_type'] = $desiredOgType;
        }
        if (empty($meta['twitter_card'])) {
            $patch['twitter_card'] = 'summary_large_image';
        }
        if (empty($meta['twitter_title']) && $title !== '') {
            $patch['twitter_title'] = Str::limit($title, 80, '');
        }
        if (empty($meta['twitter_description']) && $description !== '') {
            $patch['twitter_description'] = Str::limit($description, 200, '');
        }
        if (empty($meta['twitter_image']) && $image) {
            $patch['twitter_image'] = $image;
        }
        if (empty($meta['breadcrumbs_json']) && $breadcrumbs = $this->breadcrumbSchema($entity, $type)) {
            $patch['breadcrumbs_json'] = $breadcrumbs;
        }

        return $patch;
    }

    protected function summarizeAppliedValue($value): string
    {
        if (is_array($value)) {
            return Str::limit((string) json_encode($value, JSON_UNESCAPED_SLASHES), 120);
        }

        return Str::limit((string) $value, 120);
    }

    protected function shouldRollbackSeoMutation(array $before, array $after): bool
    {
        return (int) ($after['score'] ?? 0) < (int) ($before['score'] ?? 0);
    }

    protected function mutationSnapshot(Model $entity, string $type): array
    {
        $contentFields = match ($type) {
            'product' => ['description', 'short_description'],
            'category' => ['top_description', 'bottom_description'],
            'page' => ['content'],
            'blog' => ['description', 'short_description'],
            default => [],
        };
        $entityValues = [];
        foreach ($contentFields as $field) {
            if (Schema::hasColumn($entity->getTable(), $field)) {
                $entityValues[$field] = $entity->getAttribute($field);
            }
        }

        $meta = $this->metaQuery($entity, $type)->first();

        return [
            'entity_values' => $entityValues,
            'meta_exists' => (bool) $meta,
            'meta_values' => $meta?->getAttributes() ?? [],
        ];
    }

    protected function restoreMutationSnapshot(Model $entity, string $type, array $snapshot): void
    {
        if (!empty($snapshot['entity_values'])) {
            $entity->forceFill($snapshot['entity_values'])->save();
        }

        $query = $this->metaQuery($entity, $type);
        if (empty($snapshot['meta_exists'])) {
            $query->delete();
            return;
        }

        $row = $query->first() ?: new SeoMeta([
            'model_type' => $this->typeMap[$type]['class'],
            'model_id' => $entity->getKey(),
            'lang' => config('app.locale', 'en'),
        ]);
        $row->forceFill(Arr::except($snapshot['meta_values'] ?? [], ['id', 'created_at', 'updated_at']))->save();
    }

    protected function metaQuery(Model $entity, string $type): Builder
    {
        return SeoMeta::query()
            ->where('model_type', $this->typeMap[$type]['class'])
            ->where('model_id', $entity->getKey())
            ->where('lang', config('app.locale', 'en'));
    }

    protected function countSeoDoneUrls(): int
    {
        return SeoMeta::query()
            ->whereIn('model_type', array_column($this->typeMap, 'class'))
            ->whereNotNull('meta_title')
            ->whereNotNull('meta_description')
            ->whereNotNull('focus_keyword')
            ->whereNotNull('schema_json')
            ->where('seo_score', '>=', self::SEO_DONE_SCORE)
            ->count();
    }

    protected function countSeoPendingUrls(): int
    {
        $total = collect(array_keys($this->typeMap))->sum(fn($type) => $this->baseQuery($type)->count());
        return max(0, $total - $this->countSeoDoneUrls());
    }

    protected function plainContent(Model $entity, string $type): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($this->rawContent($entity, $type))));
    }

    /** Raw (un-stripped) HTML so structural TruSEO checks see headings/links/images. */
    protected function rawContent(Model $entity, string $type): string
    {
        $candidates = match ($type) {
            'product'  => [$entity->description ?? null, $entity->short_description ?? null],
            'category' => [$entity->top_description ?? null, $entity->bottom_description ?? null],
            'page'     => [$entity->content ?? null],
            'blog'     => [$entity->description ?? null, $entity->short_description ?? null],
            default    => [],
        };

        return trim(implode("\n\n", array_filter($candidates)));
    }

    /**
     * Build the structural context the expanded TruSEO engine consumes:
     * raw HTML, secondary keywords, image alts, and schema/OG/canonical flags.
     */
    protected function buildScoreContext(Model $entity, string $type, array $meta): array
    {
        $rawHtml = $this->rawContent($entity, $type);

        $alts = [];
        if ($rawHtml !== '' && preg_match_all('/<img\s[^>]*alt=["\']([^"\']*)["\']/i', $rawHtml, $m)) {
            $alts = array_values(array_filter($m[1]));
        }
        if (empty($alts) && $this->fallbackImage($entity, $type)) {
            $alts[] = (string) ($meta['focus_keyword'] ?? $this->displayName($entity, $type));
        }

        // Entity pages in this app always render a canonical via the layout,
        // so a canonical is resolvable whenever we have a real slug/URL.
        $hasCanonical = !empty($meta['canonical_url']) || !empty($entity->slug);

        return [
            'raw_html'           => $rawHtml,
            'secondary_keywords' => $meta['secondary_keywords'] ?? [],
            'image_alts'         => $alts,
            'has_schema'         => !empty($meta['schema_json']),
            'has_og'             => !empty($meta['og_image']) || !empty($meta['og_title']),
            'has_canonical'      => $hasCanonical,
        ];
    }

    protected function displayName(Model $entity, string $type): string
    {
        $col = in_array($type, ['page', 'blog'], true) ? 'title' : 'name';
        return (string) ($entity->{$col} ?? '#' . $entity->getKey());
    }

    protected function urlFor(Model $entity, string $type): string
    {
        $slug = $entity->slug ?? '';
        $base = rtrim(url('/'), '/');

        return match ($type) {
            'product'  => $base . '/product/' . $slug,
            'category' => $base . '/category/' . $slug,
            'page'     => $base . '/' . $slug,
            'blog'     => $base . '/blog/' . $slug,
        };
    }

    protected function resolveImageColumn(Model $entity): ?string
    {
        foreach (['meta_img', 'meta_image', 'thumbnail_img', 'banner'] as $col) {
            $val = $entity->{$col} ?? null;
            if (!$val) {
                continue;
            }
            if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL)) {
                return $val;
            }
            // Numeric upload IDs are resolved at fallbackImage() time.
            return null;
        }
        return null;
    }

    protected function fallbackImage(Model $entity, string $type): ?string
    {
        foreach (['meta_img', 'meta_image', 'thumbnail_img', 'banner'] as $col) {
            $val = $entity->{$col} ?? null;
            if (!$val) {
                continue;
            }
            if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL)) {
                return $val;
            }
            if (is_numeric($val)) {
                try {
                    $upload = \App\Models\Upload::find($val);
                    if ($upload && $upload->file_name) {
                        return asset('public/' . $upload->file_name);
                    }
                } catch (Throwable $e) {
                    // continue
                }
            }
        }
        return null;
    }

    /**
     * Build a STACKED list of schema nodes for the entity — the AIOSEO "stack
     * multiple schema types on one page" model. Always includes the primary
     * type + a BreadcrumbList, and adds an FAQPage / HowTo when the AI returned
     * structured Q&A or steps. Returns a list the resolver renders as separate
     * <script type="ld+json"> blocks.
     */
    protected function generateSchema(Model $entity, string $type, array $meta): ?array
    {
        $primary = match ($type) {
            'product'  => $this->productSchema($entity, $meta),
            'category' => $this->collectionSchema($entity, $type, $meta),
            'page'     => $this->pageSchema($entity, $type, $meta),
            'blog'     => $this->articleSchema($entity, $type, $meta),
            default    => null,
        };

        if (!$primary) {
            return null;
        }

        $stack = [$this->pruneNulls($primary)];

        if ($breadcrumb = $this->breadcrumbSchema($entity, $type)) {
            $stack[] = $breadcrumb;
        }
        if ($faq = $this->faqSchema($meta['faqs'] ?? null)) {
            $stack[] = $faq;
        }
        if ($howto = $this->howToSchema($entity, $type, $meta)) {
            $stack[] = $howto;
        }

        return $stack;
    }

    /** Deterministic BreadcrumbList: Home › Section › Entity. */
    protected function breadcrumbSchema(Model $entity, string $type): ?array
    {
        $base = rtrim(url('/'), '/');
        $name = $this->displayName($entity, $type);
        $url  = $this->urlFor($entity, $type);

        $trail = [['name' => 'Home', 'url' => $base . '/']];

        $section = match ($type) {
            'product'  => ['name' => 'Shop', 'url' => $base . '/shop'],
            'category' => ['name' => 'Categories', 'url' => $base . '/categories'],
            'blog'     => ['name' => 'Blog', 'url' => $base . '/blog'],
            default    => null,
        };
        if ($section) {
            $trail[] = $section;
        }
        $trail[] = ['name' => Str::limit($name, 80, ''), 'url' => $url];

        $items = [];
        foreach ($trail as $i => $crumb) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /** FAQPage from AI-provided [{question, answer}, …]. */
    protected function faqSchema($faqs): ?array
    {
        if (!is_array($faqs) || empty($faqs)) {
            return null;
        }

        $questions = [];
        foreach ($faqs as $faq) {
            $q = trim((string) ($faq['question'] ?? $faq['q'] ?? ''));
            $a = trim(strip_tags((string) ($faq['answer'] ?? $faq['a'] ?? '')));
            if ($q === '' || $a === '') {
                continue;
            }
            $questions[] = [
                '@type'          => 'Question',
                'name'           => Str::limit($q, 200, ''),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => Str::limit($a, 600, '')],
            ];
        }

        if (count($questions) < 2) {
            return null; // Google wants a genuine FAQ set, not a single Q.
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Append a visible FAQ block to the entity's main content so the FAQPage
     * schema reflects on-page content (a Google rich-results requirement).
     * Idempotent — skips if an FAQ section already exists. Additive only;
     * never removes curated content.
     */
    protected function injectFaqContent(Model $entity, string $type, $faqs): bool
    {
        $clean = $this->normalizeFaqs($faqs);
        if (!$clean || count($clean) < 2) {
            return false;
        }

        $field = match ($type) {
            'product'  => 'description',
            'category' => 'bottom_description',
            'page'     => 'content',
            'blog'     => 'description',
            default    => null,
        };
        if (!$field || !Schema::hasColumn($entity->getTable(), $field)) {
            return false;
        }

        $existing = (string) ($entity->{$field} ?? '');
        if (stripos($existing, 'frequently asked') !== false) {
            return true; // already visible — don't duplicate
        }

        $html = '<h2>Frequently Asked Questions</h2>';
        foreach ($clean as $faq) {
            $html .= '<h3>' . e($faq['question']) . '</h3><p>' . e($faq['answer']) . '</p>';
        }

        $entity->forceFill([$field => trim($existing . "\n" . $html)])->save();
        return true;
    }

    /** Normalize AI faqs into a clean [{question, answer}] list. */
    protected function normalizeFaqs($faqs): ?array
    {
        if (!is_array($faqs)) {
            return null;
        }
        $clean = [];
        foreach ($faqs as $faq) {
            if (!is_array($faq)) {
                continue;
            }
            $q = trim((string) ($faq['question'] ?? $faq['q'] ?? ''));
            $a = trim((string) ($faq['answer'] ?? $faq['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $clean[] = ['question' => $q, 'answer' => $a];
            }
        }
        return $clean ?: null;
    }

    /** HowTo only when the AI returned real, ordered steps — never fabricated. */
    protected function howToSchema(Model $entity, string $type, array $meta): ?array
    {
        $steps = $meta['howto_steps'] ?? null;
        if (!is_array($steps) || count($steps) < 2) {
            return null;
        }

        $stepNodes = [];
        foreach ($steps as $i => $step) {
            $text = trim(strip_tags((string) (is_array($step) ? ($step['text'] ?? '') : $step)));
            if ($text === '') {
                continue;
            }
            $stepNodes[] = [
                '@type'    => 'HowToStep',
                'position' => $i + 1,
                'name'     => Str::limit($text, 60, ''),
                'text'     => Str::limit($text, 300, ''),
            ];
        }

        if (count($stepNodes) < 2) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type'    => 'HowTo',
            'name'     => Str::limit('How to use ' . $this->displayName($entity, $type), 100, ''),
            'step'     => $stepNodes,
        ];
    }

    /** Rich Product schema: brand, aggregateRating, stock-aware Offer, seller. */
    protected function productSchema(Model $entity, array $meta): array
    {
        $name = $this->displayName($entity, 'product');
        $url  = $this->urlFor($entity, 'product');

        $inStock = ($entity->current_stock ?? 0) > 0
            || ($entity->stock_visibility_state ?? null) !== 'hide';

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $name,
            'description' => $meta['meta_description'] ?? Str::limit($this->plainContent($entity, 'product'), 200, ''),
            'image'       => $meta['og_image'] ?? $this->fallbackImage($entity, 'product'),
            'url'         => $url,
            'sku'         => (string) $entity->getKey(),
            'brand'       => $this->brandNode($entity),
            'offers'      => [
                '@type'           => 'Offer',
                'price'           => $entity->unit_price !== null ? number_format((float) $entity->unit_price, 2, '.', '') : null,
                'priceCurrency'   => $this->currencyCode(),
                'availability'    => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition'   => 'https://schema.org/NewCondition',
                'url'             => $url,
                'priceValidUntil' => now()->addYear()->toDateString(),
                'seller'          => $this->organizationNode(),
            ],
        ];

        $rating = $this->aggregateRatingNode($entity);
        if ($rating) {
            $schema['aggregateRating'] = $rating;
        }

        return $schema;
    }

    protected function collectionSchema(Model $entity, string $type, array $meta): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => $this->displayName($entity, $type),
            'description' => $meta['meta_description'] ?? null,
            'url'         => $this->urlFor($entity, $type),
        ];
    }

    protected function pageSchema(Model $entity, string $type, array $meta): array
    {
        $name = $this->displayName($entity, $type);
        $url  = $this->urlFor($entity, $type);
        $slug = Str::lower((string) ($entity->slug ?? ''));

        // About / Contact pages carry the business identity — emit LocalBusiness.
        if (Str::contains($slug, ['about', 'contact', 'location', 'store'])) {
            return $this->localBusinessNode($url, $meta['meta_description'] ?? null);
        }

        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $name,
            'description' => $meta['meta_description'] ?? null,
            'url'         => $url,
            'publisher'   => $this->organizationNode(),
        ];
    }

    protected function articleSchema(Model $entity, string $type, array $meta): array
    {
        return [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => Str::limit($this->displayName($entity, $type), 110, ''),
            'description'   => $meta['meta_description'] ?? null,
            'image'         => $meta['og_image'] ?? null,
            'url'           => $this->urlFor($entity, $type),
            'datePublished' => optional($entity->created_at)->toAtomString(),
            'dateModified'  => optional($entity->updated_at)->toAtomString(),
            'publisher'     => $this->organizationNode(),
        ];
    }

    protected function brandNode(Model $entity): ?array
    {
        try {
            $brand = method_exists($entity, 'brand') ? $entity->brand : null;
            if ($brand && !empty($brand->name)) {
                return ['@type' => 'Brand', 'name' => $brand->name];
            }
        } catch (Throwable $e) {
            // ignore — brand is optional
        }
        return null;
    }

    protected function aggregateRatingNode(Model $entity): ?array
    {
        $rating = (float) ($entity->rating ?? 0);
        if ($rating <= 0) {
            return null;
        }

        $count = 0;
        try {
            if (method_exists($entity, 'reviews')) {
                $count = (int) $entity->reviews()->where('status', 1)->count();
            }
        } catch (Throwable $e) {
            $count = 0;
        }

        if ($count < 1) {
            return null;
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => round(min(5, max(1, $rating)), 1),
            'reviewCount' => $count,
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }

    protected function organizationNode(): array
    {
        return [
            '@type' => 'Organization',
            'name'  => get_setting('website_name', config('app.name')),
            'url'   => rtrim(url('/'), '/'),
        ];
    }

    protected function localBusinessNode(string $url, ?string $description): array
    {
        $phone = get_setting('seo_local_phone', get_setting('contact_phone', config('seo.local_business.phone')));

        return $this->pruneNulls([
            '@context'    => 'https://schema.org',
            '@type'       => config('seo.local_business.type', 'Store'),
            'name'        => get_setting('website_name', config('app.name')),
            'description' => $description,
            'url'         => $url,
            'telephone'   => $phone ?: null,
            'image'       => get_setting('header_logo') ? uploaded_asset(get_setting('header_logo')) : null,
            'address'     => [
                '@type'           => 'PostalAddress',
                'addressLocality' => config('seo.local_business.city'),
                'addressRegion'   => config('seo.local_business.region'),
                'addressCountry'  => config('seo.local_business.country'),
            ],
            'areaServed'  => config('seo.local_business.region', 'Ontario') . ', ' . config('seo.local_business.country', 'Canada'),
        ]);
    }

    /** Recursively drop null / empty-string values so schema validates cleanly. */
    protected function pruneNulls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->pruneNulls($value);
                if ($data[$key] === []) {
                    unset($data[$key]);
                }
            } elseif ($value === null || $value === '') {
                unset($data[$key]);
            }
        }
        return $data;
    }

    protected function currencyCode(): string
    {
        try {
            $currency = \App\Models\Currency::find(get_setting('system_default_currency'));
            return $currency ? $currency->code : 'USD';
        } catch (Throwable $e) {
            return 'USD';
        }
    }

    /**
     * Try the selected AI first, then fall back through the strongest configured
     * SEO writers. Weak/partial JSON is repaired once, but empty or unusable
     * output moves to the next provider automatically.
     */
    protected function askBestAiForSeoBundle(?string $preferredProvider, string $name, string $description, string $type): array
    {
        $tried = [];
        $attemptDetails = [];
        $reliability = app(SeoProviderReliability::class);

        foreach ($this->providerFallbackOrder($preferredProvider) as $providerName) {
            if ($reliability->shouldSkip($providerName)) {
                $attemptDetails[] = $this->providerAttemptDetail($providerName, 'cooldown', 0, 0.0);
                continue;
            }

            $ai = SeoProviderManager::makeDirect($providerName);
            if (!$ai || !method_exists($ai, 'isConfigured') || !$ai->isConfigured()) {
                continue;
            }

            $actualName = method_exists($ai, 'getName') ? $ai->getName() : $providerName;
            $startedAt = microtime(true);
            $data = $this->askAiForSeoBundle($ai, $name, $description, $type);
            $tried[] = $actualName;

            if (empty($data)) {
                $durationMs = $this->providerDurationMs($startedAt);
                $reliability->recordAttempt($actualName, 'empty', null, $durationMs);
                $attemptDetails[] = $this->providerAttemptDetail(
                    $actualName,
                    'empty',
                    $durationMs,
                    $reliability->estimateAttemptCost($actualName)
                );
                logger()->info('AI SEO provider returned empty bundle; trying fallback', [
                    'provider' => $actualName,
                    'type' => $type,
                    'name' => Str::limit($name, 80),
                ]);
                continue;
            }

            $data = $this->repairSeoBundle($data, $name, $type);
            if ($this->seoBundleHasMinimumQuality($data)) {
                $durationMs = $this->providerDurationMs($startedAt);
                $reliability->recordAttempt($actualName, 'success', null, $durationMs);
                $attemptDetails[] = $this->providerAttemptDetail(
                    $actualName,
                    'success',
                    $durationMs,
                    $reliability->estimateAttemptCost($actualName)
                );
                if (SeoProviderManager::normalizeName($actualName) !== SeoProviderManager::normalizeName($preferredProvider)) {
                    $reliability->recordFallbackSelection($actualName);
                }

                return [
                    'data' => $data,
                    'provider' => $actualName,
                    'tried' => $tried,
                    'attempt_details' => $attemptDetails,
                ];
            }

            $durationMs = $this->providerDurationMs($startedAt);
            $reliability->recordAttempt($actualName, 'weak_bundle', null, $durationMs);
            $attemptDetails[] = $this->providerAttemptDetail(
                $actualName,
                'weak_bundle',
                $durationMs,
                $reliability->estimateAttemptCost($actualName)
            );
            logger()->info('AI SEO provider bundle was too weak; trying fallback', [
                'provider' => $actualName,
                'type' => $type,
                'name' => Str::limit($name, 80),
            ]);
        }

        return ['data' => null, 'provider' => null, 'tried' => $tried, 'attempt_details' => $attemptDetails];
    }

    protected function providerAttemptDetail(string $provider, string $status, int $durationMs, float $estimatedCostUsd): array
    {
        return [
            'provider' => $provider,
            'status' => $status,
            'duration_ms' => $durationMs,
            'estimated_cost_usd' => round($estimatedCostUsd, 6),
        ];
    }

    protected function providerDurationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    protected function providerFallbackOrder(?string $preferredProvider): array
    {
        return SeoProviderManager::fallbackOrder($preferredProvider);
    }

    protected function seoBundleHasMinimumQuality(array $data): bool
    {
        return !empty($data['title'])
            && !empty($data['description'])
            && !empty($data['focus_keyword'])
            && str_word_count(strip_tags((string) ($data['content_html'] ?? ''))) >= 220;
    }

    protected function repairSeoBundle(array $data, string $name, string $type): array
    {
        $focus = trim((string) ($data['focus_keyword'] ?? ''));
        if ($focus === '') {
            $focus = $this->primaryCanadaKeyword($name, $type);
            $data['focus_keyword'] = $focus;
        }

        if (empty($data['title']) || mb_stripos((string) $data['title'], $focus) === false) {
            $data['title'] = $this->titleWithFocus($focus, $name, $type);
        }

        if (empty($data['description']) || mb_stripos((string) $data['description'], $focus) === false) {
            $data['description'] = $this->descriptionWithFocus($focus, $name, $type);
        } else {
            $data['description'] = $this->fitDescription((string) $data['description']);
        }

        if (empty($data['secondary_keywords']) || !is_array($data['secondary_keywords'])) {
            $data['secondary_keywords'] = $this->canadaKeywordSet($name, $type);
        }

        $html = trim((string) ($data['content_html'] ?? ''));
        if ($html === '') {
            $html = $this->templateContentHtml($name, $type, $data);
        }
        $data['content_html'] = $this->ensureSeoContentSignals($html, $name, $type, $data);

        return $data;
    }

    protected function askAiForSeoBundle($ai, string $name, string $description, string $type): ?array
    {
        $siteName = get_setting('website_name', config('app.name'));
        $entityLabel = $this->typeMap[$type]['label'];
        $contentField = match ($type) {
            'product' => 'product_description_html',
            'category' => 'category_description_html',
            'page' => 'page_content_html',
            default => 'content_html',
        };
        $competitorContext = $this->competitorContext();
        $strategy = match ($type) {
            'product' => 'Use buyer intent, specs, applications, Canadian availability, B2B supply language, and conversion-focused benefits.',
            'category' => 'Use collection intent, product range coverage, Canada supplier wording, selection guidance, and internal-link friendly copy.',
            'page' => 'Use service/page intent, trust signals, Canada-focused value proposition, clear sections, and helpful explanatory content.',
            'blog' => 'Use informational intent, practical Canadian buyer guidance, FAQ-style support, and topical authority.',
            default => 'Use search intent and Canada-focused commercial relevance.',
        };

        $systemPrompt = 'You are an expert SEO copywriter. Output ONLY valid JSON, no markdown, no code fences, no extra commentary.';

        $prompt = "Generate advanced Canada-focused SEO for an ecommerce {$entityLabel} on {$siteName}.\n"
            . "Name: \"{$name}\"\n"
            . ($description ? "Details: \"" . Str::limit($description, 400) . "\"\n" : '')
            . "Algorithm: {$strategy}\n"
            . "Local priority: primary targets are Mississauga, Brampton, Toronto. Secondary targets are Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, Burlington. Include Trade Account and Leave a Review intent only where natural.\n"
            . "Keyword distribution is critical: the focus keyword MUST appear in the title (first 3 words), in the meta description, and in at least one H2 of the content HTML.\n"
            . $competitorContext
            . "Also return 3-5 genuine FAQs (question + answer) that match real buyer questions; these power FAQ rich results.\n"
            . "Return ONLY this JSON shape with no other text:\n"
            . '{"title":"SEO title 50-60 chars with focus keyword first","description":"meta description 150-160 chars, never under 150, focus keyword + benefit + CTA","focus_keyword":"primary keyword phrase","secondary_keywords":["keyword 1","keyword 2","keyword 3","keyword 4","keyword 5"],"'.$contentField.'":"clean HTML; focus keyword in at least one <h2>; H2/H3 sections; Canada intent; benefits; FAQ","faqs":[{"question":"...","answer":"..."}]}';

        try {
            $raw = $ai->generate($prompt, $systemPrompt, ['max_tokens' => 1800]);
            if (!$raw) {
                return null;
            }

            $raw = preg_replace('/```(?:json)?|```/', '', $raw);
            if (!preg_match('/\{.*\}/s', $raw, $m)) {
                return null;
            }

            $decoded = json_decode($m[0], true);
            if (!is_array($decoded)) {
                return null;
            }

            return [
                'title'         => isset($decoded['title']) ? Str::limit(trim((string) $decoded['title']), 60, '') : null,
                'description'   => isset($decoded['description']) ? Str::limit(trim((string) $decoded['description']), 160, '') : null,
                'focus_keyword' => isset($decoded['focus_keyword']) ? Str::limit(trim((string) $decoded['focus_keyword']), 80, '') : null,
                'secondary_keywords' => isset($decoded['secondary_keywords']) && is_array($decoded['secondary_keywords'])
                    ? array_values(array_filter(array_map(fn($k) => Str::limit(trim((string) $k), 80, ''), $decoded['secondary_keywords'])))
                    : null,
                'content_html' => $decoded[$contentField] ?? $decoded['content_html'] ?? null,
                'faqs'         => $this->normalizeFaqs($decoded['faqs'] ?? null),
                'howto_steps'  => isset($decoded['howto_steps']) && is_array($decoded['howto_steps'])
                    ? array_values(array_filter(array_map(fn($s) => trim(strip_tags((string) (is_array($s) ? ($s['text'] ?? '') : $s))), $decoded['howto_steps'])))
                    : null,
            ];
        } catch (Throwable $e) {
            logger()->warning('AiSeoBoard ai bundle failed', ['e' => $e->getMessage()]);
            return null;
        }
    }

    protected function contentPatch(Model $entity, string $type, ?array $aiData, array $meta): array
    {
        $html = trim((string) ($aiData['content_html'] ?? ''));
        if ($html === '') {
            $html = $this->templateContentHtml($this->displayName($entity, $type), $type, $meta, $entity);
        }

        $name = $this->displayName($entity, $type);
        $html = $this->ensureSeoContentSignals($html, $name, $type, $meta, $entity);
        $html = $this->cleanGeneratedHtml($html);
        if ($html === '') {
            return [];
        }

        $table = $entity->getTable();
        $patch = [];

        if ($type === 'product') {
            if (Schema::hasColumn($table, 'description') && $this->needsSeoContentRefresh($entity->description ?? null, $meta, 300)) {
                $patch['description'] = $this->mergeSeoHtml($entity->description ?? null, $html, $meta, $entity, $type);
            }
            if (Schema::hasColumn($table, 'short_description') && $this->needsSeoContentRefresh($entity->short_description ?? null, $meta, 45, false)) {
                $patch['short_description'] = $this->shortSeoText($name, $type, $meta);
            }
        } elseif ($type === 'category') {
            if (Schema::hasColumn($table, 'top_description') && $this->needsSeoContentRefresh($entity->top_description ?? null, $meta, 60, false)) {
                $patch['top_description'] = $this->categoryIntroHtml($name, $meta);
            }
            if (Schema::hasColumn($table, 'bottom_description') && $this->needsSeoContentRefresh($entity->bottom_description ?? null, $meta, 300)) {
                $patch['bottom_description'] = $this->mergeSeoHtml($entity->bottom_description ?? null, $html, $meta, $entity, $type);
            }
        } elseif ($type === 'page' && Schema::hasColumn($table, 'content') && $this->needsSeoContentRefresh($entity->content ?? null, $meta, 300)) {
            $patch['content'] = $this->mergeSeoHtml($entity->content ?? null, $html, $meta, $entity, $type);
        }

        return $patch;
    }

    protected function isThinContent($value, int $minWords): bool
    {
        return str_word_count(strip_tags((string) $value)) < $minWords;
    }

    protected function needsSeoContentRefresh($value, array $meta, int $minWords, bool $needHeading = true): bool
    {
        $html = trim((string) $value);
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
        $focus = trim((string) ($meta['focus_keyword'] ?? ''));

        if (str_word_count($plain) < $minWords) {
            return true;
        }
        if ($focus !== '' && mb_stripos($plain, $focus) === false) {
            return true;
        }
        if ($needHeading && $focus !== '' && !$this->htmlHeadingContains($html, $focus)) {
            return true;
        }
        if ($needHeading && !str_contains($html, 'data-seo-context-links')) {
            return true;
        }

        return false;
    }

    protected function mergeSeoHtml($current, string $generated, array $meta, ?Model $entity = null, ?string $type = null): string
    {
        $current = trim((string) $current);
        if ($current === '') {
            return $generated;
        }

        $focus = trim((string) ($meta['focus_keyword'] ?? ''));
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($current)));
        if ($focus !== '' && mb_stripos($plain, $focus) !== false && str_word_count($plain) >= 300 && $this->htmlHeadingContains($current, $focus)) {
            if (!str_contains($current, 'data-seo-context-links')) {
                return $current . "\n\n" . $this->seoLinkParagraph($meta, $entity, $type);
            }
            return $current;
        }

        return $generated . "\n\n" . $current;
    }

    protected function htmlHeadingContains(string $html, string $needle): bool
    {
        if ($html === '' || $needle === '') {
            return false;
        }

        if (!preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $html, $matches)) {
            return false;
        }

        foreach ($matches[1] as $heading) {
            if (mb_stripos(strip_tags($heading), $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function cleanGeneratedHtml(string $html): string
    {
        $html = preg_replace('/```(?:html)?|```/', '', $html);
        $html = trim($html);
        $html = preg_replace('/\s(on\w+|style)=["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/href=["\']\s*javascript:[^"\']*["\']/i', 'href="#"', $html);
        return strip_tags($html, '<h2><h3><p><ul><ol><li><strong><em><br><a>');
    }

    protected function ensureSeoContentSignals(string $html, string $name, string $type, array $meta, ?Model $entity = null): string
    {
        $focus = trim((string) ($meta['focus_keyword'] ?? ''));
        if ($focus === '') {
            $focus = $this->primaryCanadaKeyword($name, $type);
            $meta['focus_keyword'] = $focus;
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
        $needsCoreBlock = str_word_count($plain) < 300
            || mb_stripos($plain, $focus) === false
            || !$this->htmlHeadingContains($html, $focus);

        if ($needsCoreBlock) {
            return $this->seoSupportHtml($name, $type, $meta, $entity) . ($html !== '' ? "\n\n" . $html : '');
        }

        if (!str_contains($html, 'data-seo-context-links')) {
            $html .= "\n\n" . $this->seoLinkParagraph($meta, $entity, $type);
        }

        return $html;
    }

    protected function seoSupportHtml(string $name, string $type, array $meta, ?Model $entity = null): string
    {
        $focus = trim((string) ($meta['focus_keyword'] ?? $this->primaryCanadaKeyword($name, $type)));
        $keywordList = implode(', ', array_slice($meta['secondary_keywords'] ?? $this->canadaKeywordSet($name, $type), 0, 10));
        $areaText = 'Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, Burlington and the wider GTA';

        $intro = match ($type) {
            'product' => "{$focus} is prepared for Canadian buyers who need reliable supply, practical product details, and clear purchasing support. BHS Supplies helps contractors, maintenance teams, facility buyers, and trade customers compare fit, availability, and value across {$areaText}.",
            'category' => "{$focus} options help Canadian buyers compare product families, applications, availability, and trade purchasing needs in one place. This category supports sourcing for contractors, maintenance teams, businesses, and local buyers across {$areaText}.",
            'page' => "{$focus} information is organized for Canadian customers who need clear next steps, local trust signals, and practical support from BHS Supplies across {$areaText}.",
            default => "{$focus} is covered with Canadian search intent, buyer questions, and practical guidance for customers across {$areaText}.",
        };

        return '<h2>' . e(Str::title($focus)) . ' for Canada and GTA Buyers</h2>'
            . '<p>' . e($intro) . '</p>'
            . '<h3>Why Buyers Choose BHS Supplies</h3>'
            . '<p>' . e('Customers often need fast access to product information, dependable stock, and a supplier that understands commercial, residential, and trade requirements. Because buying decisions depend on compatibility, quality, price, and delivery, this page keeps the most important selection points easy to review before ordering.') . '</p>'
            . '<ul>'
            . '<li>Canada-focused sourcing for local and regional buyers.</li>'
            . '<li>Helpful support for bulk orders, trade account needs, and repeat purchasing.</li>'
            . '<li>Useful coverage for HVAC, plumbing, electrical, hardware, and related supply searches.</li>'
            . '<li>Clear product or category context so buyers can compare specifications and use cases.</li>'
            . '</ul>'
            . '<h3>Applications and Selection Notes</h3>'
            . '<p>' . e('The right choice depends on the job site, required size, installation conditions, durability expectations, and how quickly the item is needed. Buyers should compare product details with the intended application, then confirm whether accessories, replacement parts, or compatible items are required. Additionally, repeat buyers can use trade account support to simplify future orders and keep purchasing more consistent.') . '</p>'
            . '<h3>Local Search Coverage</h3>'
            . '<p>' . e("This page is optimized for buyers searching in {$areaText}. It also supports trade account, pickup, quote request, and leave a review intent where those actions are natural for the customer journey.") . '</p>'
            . '<h3>Related Canada Keywords</h3>'
            . '<p>' . e($keywordList) . '</p>'
            . $this->seoLinkParagraph($meta, $entity, $type)
            . '<h3>Buying Guidance</h3>'
            . '<p>' . e('First, confirm the product size, application, material, and compatibility. Next, compare delivery or pickup needs with order quantity and pricing. Finally, contact BHS Supplies when you need help matching an item, opening a trade account, or planning a repeat order for your business.') . '</p>';
    }

    protected function seoLinkParagraph(array $meta, ?Model $entity = null, ?string $type = null): string
    {
        $focus = trim((string) ($meta['focus_keyword'] ?? 'products'));
        $links = $this->contextualInternalLinks($entity, $type);
        $anchors = array_map(
            fn(array $link) => '<a href="' . e($link['url']) . '">' . e($link['label']) . '</a>',
            $links
        );

        return '<p data-seo-context-links="1">For related ' . e($focus) . ' options and next steps, review '
            . implode(', ', $anchors)
            . '. Canadian business buyers can also review general small business guidance from <a href="https://www.canada.ca/en/services/business.html" rel="nofollow noopener" target="_blank">Canada.ca</a> while comparing purchasing requirements.</p>';
    }

    protected function contextualInternalLinks(?Model $entity, ?string $type): array
    {
        $links = [];

        try {
            if ($entity && $type === 'product' && $category = $entity->main_category) {
                if (!empty($category->slug)) {
                    $links[] = [
                        'url' => url('/category/' . ltrim((string) $category->slug, '/')),
                        'label' => Str::limit((string) ($category->name ?: 'related category'), 70, ''),
                    ];
                }
            } elseif ($entity && $type === 'category' && $parent = $entity->parentCategory) {
                if (!empty($parent->slug)) {
                    $links[] = [
                        'url' => url('/category/' . ltrim((string) $parent->slug, '/')),
                        'label' => Str::limit((string) ($parent->name ?: 'parent category'), 70, ''),
                    ];
                }
            }
        } catch (Throwable $e) {
            // The core internal-link set below is still useful if a relation is unavailable.
        }

        $links[] = ['url' => url('/shop'), 'label' => 'BHS Supplies products'];
        $links[] = ['url' => url('/contractor-trade-account'), 'label' => 'contractor trade account'];
        $links[] = ['url' => url('/review'), 'label' => 'leave a review'];
        $links[] = $this->localLandingLink($entity, $type);

        return collect($links)->unique('url')->values()->all();
    }

    protected function localLandingLink(?Model $entity, ?string $type): array
    {
        $locations = [
            ['slug' => 'mississauga', 'label' => 'HVAC supplies Mississauga'],
            ['slug' => 'brampton', 'label' => 'HVAC supplies Brampton'],
            ['slug' => 'toronto', 'label' => 'HVAC supplies Toronto'],
            ['slug' => 'etobicoke', 'label' => 'HVAC supplies Etobicoke'],
            ['slug' => 'vaughan', 'label' => 'HVAC supplies Vaughan'],
            ['slug' => 'oakville', 'label' => 'HVAC supplies Oakville'],
            ['slug' => 'scarborough', 'label' => 'HVAC supplies Scarborough'],
            ['slug' => 'markham', 'label' => 'HVAC supplies Markham'],
            ['slug' => 'north-york', 'label' => 'HVAC supplies North York'],
            ['slug' => 'burlington', 'label' => 'HVAC supplies Burlington'],
        ];
        $seed = ($type ?: 'page') . ':' . ($entity?->getKey() ?: ($entity?->slug ?? 'default'));
        $location = $locations[abs(crc32($seed)) % count($locations)];

        return [
            'url' => url('/hvac-supplies-' . $location['slug']),
            'label' => $location['label'],
        ];
    }

    protected function hasAnyLink(string $html): bool
    {
        return (bool) preg_match('/<a\s[^>]*href=["\'][^"\']+["\']/i', $html);
    }

    protected function titleWithFocus(string $focus, string $name, string $type): string
    {
        $focusTitle = Str::title(trim($focus));
        $tail = match ($type) {
            'product' => 'Trusted Canada Supplier 2026',
            'category' => 'Wholesale Canada 2026',
            'page' => 'Canada Guide 2026',
            default => 'Canada SEO Guide 2026',
        };

        $title = trim($focusTitle . ' | ' . $tail);
        if (mb_strlen($title) > 60) {
            $title = trim($focusTitle . ' Canada');
        }
        if (mb_strlen($title) < 30) {
            $title .= ' | BHS Supplies';
        }

        return Str::limit($title, 60, '');
    }

    protected function descriptionWithFocus(string $focus, string $name, string $type): string
    {
        $focusTitle = Str::title(trim($focus));
        $text = "{$focusTitle} for Mississauga, Brampton, Toronto and GTA buyers. Compare specs, trade pricing, stock, pickup options, and order from BHS Supplies.";

        return $this->fitDescription($text);
    }

    protected function shortSeoText(string $name, string $type, array $meta): string
    {
        $focus = trim((string) ($meta['focus_keyword'] ?? $this->primaryCanadaKeyword($name, $type)));
        return Str::limit(strip_tags($this->descriptionWithFocus($focus, $name, $type)), 240, '');
    }

    protected function templateTitle(string $name, string $type): string
    {
        return $this->titleWithFocus($this->primaryCanadaKeyword($name, $type), $name, $type);
    }

    protected function templateDescription(string $name, string $type): string
    {
        $site = get_setting('website_name', config('app.name'));
        $text = match ($type) {
            'product'  => 'Shop ' . $name . ' in Canada with reliable supply, clear specs, competitive trade pricing, and expert support for buyers in Mississauga, Brampton, Toronto and the GTA.',
            'category' => 'Explore ' . $name . ' in Canada. Compare options, request trade pricing, and source dependable products for business and industrial needs across the GTA.',
            'page'     => 'Learn about ' . $name . ' for Canadian customers, including helpful details, trusted support, trade account options, and clear next steps from ' . $site . '.',
            default    => 'Find practical Canada-focused information about ' . $name . ' with expert guidance, trade support, and useful next steps from ' . $site . '.',
        };

        return $this->fitDescription($text);
    }

    /**
     * Keep a meta description inside Google's 150–160 char sweet spot: pad short
     * copy with a natural suffix, hard-trim anything over 160 on a word boundary.
     */
    protected function fitDescription(string $text, int $min = 150, int $max = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if (mb_strlen($text) < $min) {
            foreach ([' Fast Canada-wide shipping and trade accounts available.', ' Trusted Canadian supplier with bulk and trade pricing.', ' Order online or request a quote today.'] as $suffix) {
                if (mb_strlen($text) >= $min) {
                    break;
                }
                $text = rtrim($text, '.') . '.' . $suffix;
            }
        }

        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
            $lastSpace = mb_strrpos($text, ' ');
            if ($lastSpace !== false && $lastSpace > $min - 15) {
                $text = mb_substr($text, 0, $lastSpace);
            }
            $text = rtrim($text, " ,.;:") . '.';
        }

        return $text;
    }

    protected function normalizeFocusKeyword($value, string $name, string $type): string
    {
        $keyword = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
        if ($keyword === '') {
            $keyword = $this->primaryCanadaKeyword($name, $type);
        }

        $keyword = preg_replace('/\s+\|\s+.*$/', '', $keyword);
        $keyword = trim($keyword, " \t\n\r\0\x0B-_,.;:");

        if (mb_strlen($keyword) > 58 || str_word_count($keyword) > 8) {
            $words = preg_split('/\s+/', $keyword);
            $keyword = implode(' ', array_slice($words, 0, 6));
        }

        if (mb_strlen($keyword) > 58) {
            $keyword = mb_substr($keyword, 0, 58);
            $lastSpace = mb_strrpos($keyword, ' ');
            if ($lastSpace !== false && $lastSpace > 20) {
                $keyword = mb_substr($keyword, 0, $lastSpace);
            }
        }

        $keyword = trim($keyword, " \t\n\r\0\x0B-_,.;:");
        if ($keyword === '') {
            $keyword = $this->primaryCanadaKeyword($name, $type);
        }

        return Str::lower($keyword);
    }

    protected function needsFocusKeywordRefresh($value): bool
    {
        $value = trim((string) $value);
        return $value === '' || mb_strlen($value) > 65 || str_word_count($value) > 9;
    }

    protected function needsMetaTitleRefresh($value, string $focus): bool
    {
        $value = trim((string) $value);
        $len = mb_strlen($value);

        return $value === ''
            || $len < 30
            || $len > 60
            || ($focus !== '' && mb_stripos($value, $focus) === false);
    }

    protected function needsMetaDescriptionRefresh($value, string $focus): bool
    {
        $value = trim((string) $value);
        $len = mb_strlen($value);

        return $value === ''
            || $len < 120
            || $len > 160
            || ($focus !== '' && mb_stripos($value, $focus) === false);
    }

    protected function needsSecondaryKeywordsRefresh($value): bool
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value);
        }

        return !is_array($value) || count(array_filter($value)) < 5;
    }

    protected function bestTitleForFocus($aiTitle, string $focus, string $name, string $type): string
    {
        $aiTitle = trim((string) $aiTitle);
        $len = mb_strlen($aiTitle);
        if ($aiTitle !== '' && $len >= 30 && $len <= 60 && mb_stripos($aiTitle, $focus) !== false) {
            return $aiTitle;
        }

        return $this->titleWithFocus($focus, $name, $type);
    }

    protected function bestDescriptionForFocus($aiDescription, string $focus, string $name, string $type): string
    {
        $aiDescription = trim((string) $aiDescription);
        if ($aiDescription !== '' && mb_stripos($aiDescription, $focus) !== false) {
            return $this->fitDescription($aiDescription);
        }

        return $this->descriptionWithFocus($focus, $name, $type);
    }

    protected function primaryCanadaKeyword(string $name, string $type): string
    {
        $keyword = match ($type) {
            'category' => trim($name . ' supplier'),
            default => trim($name),
        };

        return Str::limit(Str::lower($keyword), 80, '');
    }

    protected function canadaKeywordSet(string $name, string $type): array
    {
        $base = Str::lower($name);
        $intent = $type === 'category' ? 'supplier' : ($type === 'product' ? 'buy' : 'services');
        return array_values(array_unique([
            "{$base} Mississauga",
            "{$base} Brampton",
            "{$base} Toronto",
            "{$base} GTA",
            "{$base} Etobicoke",
            "{$base} Vaughan",
            "{$base} Oakville",
            "{$base} Scarborough",
            "{$base} Markham",
            "{$base} North York",
            "{$base} Burlington",
            "{$base} trade account",
            "{$base} leave a review",
            "{$base} Canadian supplier",
            "{$intent} {$base} Mississauga",
        ]));
    }

    protected function competitorContext(): string
    {
        $raw = (string) get_setting('seo_competitor_urls', get_setting('ai_blog_competitor_urls', ''));
        $urls = $this->parseCompetitorUrls($raw);

        if (empty($urls)) {
            return '';
        }

        return "Competitor websites to outrank/reference for keyword gaps and content angles: "
            . implode(', ', array_slice($urls, 0, 8))
            . ". Do not copy their wording. Do not mention competitor brand names.\n";
    }

    protected function parseCompetitorUrls(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $normalized = preg_replace('/(https?:\/\/)/', '|||$1', $raw);
        $parts = str_contains($normalized, '|||')
            ? explode('|||', $normalized)
            : preg_split('/[\r\n,]+/', $raw);

        $urls = [];
        foreach ($parts as $part) {
            $url = trim($part);
            if ($url === '') {
                continue;
            }
            if (!preg_match('#^https?://#i', $url)) {
                $url = 'https://' . ltrim($url, '/');
            }
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $urls[] = rtrim($url, '/');
            }
        }

        return array_values(array_unique($urls));
    }

    protected function templateContentHtml(string $name, string $type, array $meta, ?Model $entity = null): string
    {
        return $this->seoSupportHtml($name, $type, $meta, $entity);
    }

    protected function categoryIntroHtml(string $name, array $meta): string
    {
        $focus = trim((string) ($meta['focus_keyword'] ?? $this->primaryCanadaKeyword($name, 'category')));
        return '<p>' . e(Str::title($focus) . ' in Canada with focused selection, helpful product details, trade account support, and local buying coverage for Mississauga, Brampton, Toronto and the GTA.') . '</p>';
    }

    protected function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default      => 'F',
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Bulk fix API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Approximate token + USD cost of running a bulk fix.
     *
     * Tokens-per-entity is a small fixed estimate (input prompt + output JSON).
     * Real cost varies — this is a conservative pre-flight figure shown in the
     * confirmation modal so admins know what they're triggering.
     *
     * @return array{count:int, input_tokens:int, output_tokens:int, usd:float, per_entity_usd:float, provider:string, ai_call:bool}
     */
    public function estimateBatchCost(array $targets, ?string $providerName = null): array
    {
        $count    = count($targets);
        $provider = $providerName ?: get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));

        // Per-entity estimate covers title, description, keywords, content HTML,
        // FAQs, and possible provider fallback.
        $inPerEntity  = 900;
        $outPerEntity = 1200;

        $rates = $this->providerRates($provider);

        $input  = $count * $inPerEntity;
        $output = $count * $outPerEntity;
        $usd    = round(($input / 1000000 * $rates['in_usd_per_1m']) + ($output / 1000000 * $rates['out_usd_per_1m']), 4);

        // If no AI provider key is configured, fix runs in template-only mode (free).
        $aiCall = false;
        try {
            $ai = SeoProviderManager::make($provider);
            if ($ai && method_exists($ai, 'isConfigured') && $ai->isConfigured()) {
                $aiCall = true;
            }
        } catch (Throwable $e) {
            $aiCall = false;
        }

        if (!$aiCall) {
            $usd = 0.0;
        }

        return [
            'count'           => $count,
            'input_tokens'    => $input,
            'output_tokens'   => $output,
            'usd'             => $usd,
            'per_entity_usd'  => $count > 0 ? round($usd / $count, 4) : 0.0,
            'provider'        => $provider,
            'ai_call'         => $aiCall,
        ];
    }

    /**
     * Compute a target list given filter params: either explicit IDs from the
     * UI, or "all matching the current filters" (which streams through pages).
     *
     * @param array $payload supports either:
     *   - targets: [['type'=>..., 'id'=>...], ...]
     *   - type + filters[]: pull all IDs that match the current filter set
     *   - limit (optional) cap for "all matching"
     */
    public function collectTargets(array $payload): array
    {
        if (!empty($payload['targets']) && is_array($payload['targets'])) {
            // Sanitize and de-dupe explicit target list.
            $clean = [];
            foreach ($payload['targets'] as $t) {
                $type = $t['type'] ?? null;
                $id   = (int) ($t['id'] ?? 0);
                if (!in_array($type, self::SUPPORTED_TYPES, true) || $id <= 0) {
                    continue;
                }
                $clean[$type . ':' . $id] = ['type' => $type, 'id' => $id];
            }
            return array_values($clean);
        }

        $type = $payload['type'] ?? 'product';
        $this->assertType($type);

        $filters = $payload['filters'] ?? [];
        $limit   = min((int) ($payload['limit'] ?? 500), 5000);

        $query = $this->baseQuery($type);
        $this->applySearch($query, $type, $filters['search'] ?? null);

        $class = $this->typeMap[$type]['class'];

        if (!empty($filters['missing'])) {
            $this->applyMissingFilter($query, $class, $filters['missing']);
        }
        if (isset($filters['min_score']) || isset($filters['max_score'])) {
            $this->applyScoreRange($query, $class, $filters['min_score'] ?? null, $filters['max_score'] ?? null);
        }
        $this->applySort($query, $class, $filters['sort'] ?? 'recent');

        return $query->limit($limit)
            ->pluck('id')
            ->map(fn($id) => ['type' => $type, 'id' => (int) $id])
            ->values()
            ->all();
    }

    /**
     * Create a SeoFixBatch row and dispatch the worker job. Returns the batch.
     */
    public function createBatch(array $targets, ?string $providerName = null, ?int $userId = null, ?string $label = null, bool $dispatch = true): SeoFixBatch
    {
        $targets = $this->normalizeNewBatchTargets($targets);
        if (empty($targets)) {
            throw new \InvalidArgumentException('All selected URLs are already queued or invalid. Let the active cron batches finish first.');
        }

        $estimate = $this->estimateBatchCost($targets, $providerName);
        $cronChunked = config('queue.default') === 'sync';

        $batch = SeoFixBatch::create([
            'project_id'         => null,
            'label'              => $label ?: 'AI SEO Board batch - ' . now()->format('Y-m-d H:i'),
            'status'             => SeoFixBatch::STATUS_QUEUED,
            'provider'           => $estimate['provider'],
            'total'              => count($targets),
            'processed'          => 0,
            'succeeded'          => 0,
            'failed'             => 0,
            'skipped'            => 0,
            'current_label'      => $cronChunked ? 'Queued for cron chunk processor' : null,
            'estimated_cost_usd' => $estimate['usd'],
            'actual_cost_usd'    => 0,
            'target_ids'         => $targets,
            'options'            => [
                'ai_call' => $estimate['ai_call'],
                'rates'   => $this->providerRates($estimate['provider']),
                'cron_chunked' => $cronChunked,
            ],
            'created_by'         => $userId,
        ]);

        if ($dispatch && !$cronChunked) {
            AiAutoFixSeoJob::dispatch($batch->id);
        }

        return $batch->fresh();
    }

    protected function normalizeNewBatchTargets(array $targets): array
    {
        $activeTargetKeys = [];
        if (Schema::hasTable('seo_fix_batches')) {
            $activeTargetKeys = SeoFixBatch::query()
                ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
                ->get()
                ->flatMap(fn(SeoFixBatch $batch) => $batch->target_ids ?? [])
                ->mapWithKeys(fn(array $target) => [$this->targetKey($target) => true])
                ->all();
        }

        $seen = [];

        return collect($targets)
            ->filter(function ($target) use (&$seen, $activeTargetKeys): bool {
                if (!is_array($target)) {
                    return false;
                }

                $type = (string) ($target['type'] ?? '');
                $id = (int) ($target['id'] ?? 0);
                $key = $type . ':' . $id;

                if (!$id || !isset($this->typeMap[$type]) || isset($seen[$key]) || isset($activeTargetKeys[$key])) {
                    return false;
                }

                $seen[$key] = true;

                return true;
            })
            ->map(fn(array $target) => ['type' => (string) $target['type'], 'id' => (int) $target['id']])
            ->values()
            ->all();
    }

    public function cancelBatch(SeoFixBatch $batch): SeoFixBatch
    {
        if (!$batch->isTerminal()) {
            $batch->update([
                'status'       => SeoFixBatch::STATUS_CANCELLED,
                'completed_at' => now(),
            ]);
        }
        return $batch->fresh();
    }

    /**
     * Rough provider rate card. Prices are public list prices for the cheapest
     * widely-used model on each provider as of 2026 — adjust if config changes.
     */
    protected function providerRates(string $provider): array
    {
        // Rates priced against the model each provider is actually configured to
        // call in config/seo.php — keep these aligned when the model env changes.
        return match (strtolower($provider)) {
            'openai', 'chatgpt' => ['in_usd_per_1m' => 0.15,  'out_usd_per_1m' => 0.60,  'note' => 'gpt-4o-mini'],
            // config/seo.php defaults claude → claude-sonnet-4-6 ($3 in / $15 out).
            'claude', 'anthropic' => ['in_usd_per_1m' => 3.00,  'out_usd_per_1m' => 15.00, 'note' => 'claude-sonnet-4-6'],
            'gemini', 'google'  => ['in_usd_per_1m' => 0.075, 'out_usd_per_1m' => 0.30,  'note' => 'gemini-1.5-flash'],
            'grok', 'xai'       => ['in_usd_per_1m' => 0.50,  'out_usd_per_1m' => 1.50,  'note' => 'grok-3-mini'],
            default             => ['in_usd_per_1m' => 0.20,  'out_usd_per_1m' => 0.80,  'note' => 'fallback estimate'],
        };
    }
}
