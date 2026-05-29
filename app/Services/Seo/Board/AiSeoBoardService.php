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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function collectPendingTargetsAcrossTypes(int $limit = 10, array $types = ['product', 'category', 'page']): array
    {
        $limit = max(1, min(10, $limit));

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
            && (int) ($row['score'] ?? 0) >= 70;
    }

    public function pendingBreakdownByType(array $types = ['product', 'category', 'page']): array
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
                    ->where('seo_score', '>=', 70)
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

    public function nextAutopilotTargetPreview(int $limit = 10, array $types = ['product', 'category', 'page']): Collection
    {
        $limit = max(1, min(20, $limit));

        return $this->pendingAutopilotRows($limit, $types)
            ->take($limit)
            ->values();
    }

    protected function pendingAutopilotRows(int $limit, array $types): Collection
    {
        $preview = collect();
        $candidateLimit = max(40, $limit * 8);

        foreach ($types as $type) {
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
            ->sortByDesc('priority_score')
            ->values();
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

    public function offPageCampaignTargetPreview(int $limit = 10, array $types = ['product', 'category', 'page']): Collection
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

        $typeBoost = ['product' => 8, 'category' => 6, 'page' => 4, 'blog' => 2];
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
            $this->urlFor($entity, $type)
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
     * Apply an AI-generated fix to a single entity. Writes ONLY missing
     * fields (meta_title, meta_description, og_image, focus_keyword,
     * schema_json) so existing curated SEO content is never overwritten.
     *
     * @return array{applied: array<string,string>, score_before:int, score_after:int, source: 'ai'|'template'}
     */
    public function applyAiFix(string $type, int $id, ?string $providerName = null): array
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

        $applied = [];
        $source  = 'template';

        $ai = SeoProviderManager::make($providerName ?: get_setting('seo_suite_default_provider', config('seo.default_provider')));

        $aiData = null;
        if ($ai && method_exists($ai, 'isConfigured') && $ai->isConfigured()) {
            $aiData = $this->askAiForSeoBundle($ai, $name, $desc, $type);
            if (!empty($aiData)) {
                $source = 'ai';
            }
        }

        $patch = [];

        if (empty($meta['meta_title'])) {
            $patch['meta_title'] = $aiData['title']
                ?? $this->templateTitle($name, $type);
            $applied['meta_title'] = $patch['meta_title'];
        }

        if (empty($meta['meta_description'])) {
            $patch['meta_description'] = $aiData['description']
                ?? $this->templateDescription($name, $type);
            $applied['meta_description'] = $patch['meta_description'];
        }

        if (empty($meta['focus_keyword'])) {
            $patch['focus_keyword'] = $aiData['focus_keyword'] ?? $this->primaryCanadaKeyword($name, $type);
            $applied['focus_keyword'] = $patch['focus_keyword'];
        }

        if (empty($meta['secondary_keywords'])) {
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

        if (empty($meta['schema_json'])) {
            $schema = $this->generateSchema($entity, $type, $patch + $meta);
            if ($schema) {
                $patch['schema_json'] = $schema;
                $applied['schema_json'] = json_encode($schema, JSON_UNESCAPED_SLASHES);
            }
        }

        if (!empty($patch)) {
            $patch['last_analyzed_at'] = now();
            $this->persistMeta($entity, $type, $patch);
        }

        $contentPatch = $this->contentPatch($entity, $type, $aiData, $patch + $meta);
        if (!empty($contentPatch)) {
            $entity->forceFill($contentPatch)->save();
            foreach ($contentPatch as $field => $value) {
                $applied[$field] = Str::limit(strip_tags((string) $value), 120);
            }
        }

        $entity->refresh();
        $afterScore = $this->scoreEntity($entity, $type);
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

    protected function countSeoDoneUrls(): int
    {
        return SeoMeta::query()
            ->whereIn('model_type', array_column($this->typeMap, 'class'))
            ->whereNotNull('meta_title')
            ->whereNotNull('meta_description')
            ->whereNotNull('focus_keyword')
            ->whereNotNull('schema_json')
            ->where('seo_score', '>=', 70)
            ->count();
    }

    protected function countSeoPendingUrls(): int
    {
        $total = collect(array_keys($this->typeMap))->sum(fn($type) => $this->baseQuery($type)->count());
        return max(0, $total - $this->countSeoDoneUrls());
    }

    protected function plainContent(Model $entity, string $type): string
    {
        $candidates = match ($type) {
            'product'  => [$entity->description ?? null, $entity->short_description ?? null],
            'category' => [$entity->top_description ?? null, $entity->bottom_description ?? null],
            'page'     => [$entity->content ?? null],
            'blog'     => [$entity->description ?? null, $entity->short_description ?? null],
            default    => [],
        };

        $text = trim(implode("\n\n", array_filter($candidates)));
        return trim(preg_replace('/\s+/', ' ', strip_tags($text)));
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

    protected function generateSchema(Model $entity, string $type, array $meta): ?array
    {
        $name = $this->displayName($entity, $type);
        $url  = $this->urlFor($entity, $type);

        return match ($type) {
            'product' => [
                '@context'    => 'https://schema.org',
                '@type'       => 'Product',
                'name'        => $name,
                'description' => $meta['meta_description'] ?? Str::limit($this->plainContent($entity, $type), 200, ''),
                'image'       => $meta['og_image'] ?? null,
                'url'         => $url,
                'offers'      => [
                    '@type'         => 'Offer',
                    'price'         => $entity->unit_price ?? null,
                    'priceCurrency' => $this->currencyCode(),
                    'availability'  => 'https://schema.org/InStock',
                    'url'           => $url,
                ],
            ],
            'category' => [
                '@context'    => 'https://schema.org',
                '@type'       => 'CollectionPage',
                'name'        => $name,
                'description' => $meta['meta_description'] ?? null,
                'url'         => $url,
            ],
            'page' => [
                '@context'    => 'https://schema.org',
                '@type'       => 'WebPage',
                'name'        => $name,
                'description' => $meta['meta_description'] ?? null,
                'url'         => $url,
            ],
            'blog' => [
                '@context'    => 'https://schema.org',
                '@type'       => 'Article',
                'headline'    => $name,
                'description' => $meta['meta_description'] ?? null,
                'image'       => $meta['og_image'] ?? null,
                'url'         => $url,
            ],
            default => null,
        };
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
            . $competitorContext
            . "Return ONLY this JSON shape with no other text:\n"
            . '{"title":"SEO title 30-60 chars","description":"meta description 120-160 chars","focus_keyword":"primary keyword phrase","secondary_keywords":["keyword 1","keyword 2","keyword 3","keyword 4","keyword 5"],"'.$contentField.'":"clean HTML with H2/H3 sections, Canada intent, benefits, and FAQ"}';

        try {
            $raw = $ai->generate($prompt, $systemPrompt, ['max_tokens' => 1000]);
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
            $html = $this->templateContentHtml($this->displayName($entity, $type), $type, $meta);
        }

        $html = $this->cleanGeneratedHtml($html);
        if ($html === '') {
            return [];
        }

        $table = $entity->getTable();
        $patch = [];

        if ($type === 'product') {
            if (Schema::hasColumn($table, 'description') && $this->isThinContent($entity->description ?? null, 180)) {
                $patch['description'] = $html;
            }
            if (Schema::hasColumn($table, 'short_description') && $this->isThinContent($entity->short_description ?? null, 70)) {
                $patch['short_description'] = Str::limit(strip_tags($html), 240, '');
            }
        } elseif ($type === 'category') {
            if (Schema::hasColumn($table, 'top_description') && $this->isThinContent($entity->top_description ?? null, 80)) {
                $patch['top_description'] = $this->categoryIntroHtml($this->displayName($entity, $type), $meta);
            }
            if (Schema::hasColumn($table, 'bottom_description') && $this->isThinContent($entity->bottom_description ?? null, 180)) {
                $patch['bottom_description'] = $html;
            }
        } elseif ($type === 'page' && Schema::hasColumn($table, 'content') && $this->isThinContent($entity->content ?? null, 180)) {
            $patch['content'] = $html;
        }

        return $patch;
    }

    protected function isThinContent($value, int $minWords): bool
    {
        return str_word_count(strip_tags((string) $value)) < $minWords;
    }

    protected function cleanGeneratedHtml(string $html): string
    {
        $html = preg_replace('/```(?:html)?|```/', '', $html);
        $html = trim($html);
        return strip_tags($html, '<h2><h3><p><ul><ol><li><strong><em><br>');
    }

    protected function templateTitle(string $name, string $type): string
    {
        return match ($type) {
            'product' => Str::limit($name . ' in Canada | ' . get_setting('website_name', config('app.name')), 60, ''),
            'category' => Str::limit($name . ' Supplier in Canada', 60, ''),
            'page' => Str::limit($name . ' | Canada', 60, ''),
            default => Str::limit($name . ' Canada SEO Guide', 60, ''),
        };
    }

    protected function templateDescription(string $name, string $type): string
    {
        return match ($type) {
            'product' => Str::limit('Shop ' . $name . ' in Canada with reliable supply, clear specs, competitive pricing, and expert support for Canadian buyers.', 160, ''),
            'category' => Str::limit('Explore ' . $name . ' in Canada. Compare options, request pricing, and source dependable products for business and industrial needs.', 160, ''),
            'page' => Str::limit('Learn about ' . $name . ' for Canadian customers with helpful details, trusted support, and clear next steps.', 160, ''),
            default => Str::limit('Find practical Canada-focused information about ' . $name . ' with expert guidance and useful next steps.', 160, ''),
        };
    }

    protected function primaryCanadaKeyword(string $name, string $type): string
    {
        $suffix = match ($type) {
            'product' => 'Canada',
            'category' => 'supplier Canada',
            'page' => 'Canada',
            default => 'Canada',
        };
        return Str::limit(Str::lower(trim($name . ' ' . $suffix)), 80, '');
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

    protected function templateContentHtml(string $name, string $type, array $meta): string
    {
        $keyword = $meta['focus_keyword'] ?? $this->primaryCanadaKeyword($name, $type);

        if ($type === 'product') {
            return '<h2>' . e($name) . ' for Canadian Buyers</h2>'
                . '<p>' . e($name . ' is prepared for customers across Canada who need dependable supply, clear product information, and practical buying support.') . '</p>'
                . '<h3>Key Benefits</h3><ul><li>Suitable for business, industrial, and trade purchasing needs.</li><li>Local sourcing support for Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, and Burlington.</li><li>Clear product details to help compare quality, fit, and value.</li></ul>'
                . '<h3>Related Keywords</h3><p>' . e($keyword . ', ' . implode(', ', $this->canadaKeywordSet($name, $type))) . '</p>';
        }

        if ($type === 'category') {
            return '<h2>' . e($name) . ' in Canada</h2>'
                . '<p>' . e('Browse ' . $name . ' for Canadian business and industrial requirements. This category is structured to help buyers compare products, applications, and purchasing options quickly.') . '</p>'
                . '<h3>How to Choose</h3><p>Review specifications, use case, availability, compliance needs, and long-term value before selecting the right option.</p>'
                . '<h3>Local Supply Coverage</h3><p>Useful for buyers searching in Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, Burlington, and across the GTA.</p>';
        }

        return '<h2>' . e($name) . ' in Canada</h2>'
            . '<p>' . e('This page explains ' . $name . ' for Canadian customers, including helpful details, trust signals, and practical next steps.') . '</p>'
            . '<h3>What You Should Know</h3><p>Use this information to compare options, understand requirements, and choose the right solution for your needs.</p>'
            . '<h3>Frequently Asked Questions</h3><p><strong>Is this available in Canada?</strong> Yes, this content is optimized for Canadian search intent and buyer expectations.</p>';
    }

    protected function categoryIntroHtml(string $name, array $meta): string
    {
        return '<p>' . e('Shop ' . $name . ' in Canada with a focused selection, helpful product details, and support for Canadian buyers comparing quality, availability, and value.') . '</p>';
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

        // Per-entity token estimate: ~600 input + 250 output (title+desc+focus_kw)
        $inPerEntity  = 600;
        $outPerEntity = 250;

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
    public function createBatch(array $targets, ?string $providerName = null, ?int $userId = null, ?string $label = null): SeoFixBatch
    {
        $estimate = $this->estimateBatchCost($targets, $providerName);

        $batch = SeoFixBatch::create([
            'project_id'         => null,
            'label'              => $label ?: 'AI SEO Board batch — ' . now()->format('Y-m-d H:i'),
            'status'             => SeoFixBatch::STATUS_QUEUED,
            'provider'           => $estimate['provider'],
            'total'              => count($targets),
            'processed'          => 0,
            'succeeded'          => 0,
            'failed'             => 0,
            'skipped'            => 0,
            'estimated_cost_usd' => $estimate['usd'],
            'actual_cost_usd'    => 0,
            'target_ids'         => $targets,
            'options'            => [
                'ai_call' => $estimate['ai_call'],
                'rates'   => $this->providerRates($estimate['provider']),
            ],
            'created_by'         => $userId,
        ]);

        AiAutoFixSeoJob::dispatch($batch->id);

        return $batch->fresh();
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
        return match (strtolower($provider)) {
            'openai', 'chatgpt' => ['in_usd_per_1m' => 0.15,  'out_usd_per_1m' => 0.60,  'note' => 'gpt-4o-mini'],
            'claude', 'anthropic' => ['in_usd_per_1m' => 1.00,  'out_usd_per_1m' => 5.00, 'note' => 'claude-haiku 4.5'],
            'gemini', 'google'  => ['in_usd_per_1m' => 0.075, 'out_usd_per_1m' => 0.30,  'note' => 'gemini-1.5-flash'],
            'grok', 'xai'       => ['in_usd_per_1m' => 0.50,  'out_usd_per_1m' => 1.50,  'note' => 'grok-3-mini'],
            default             => ['in_usd_per_1m' => 0.20,  'out_usd_per_1m' => 0.80,  'note' => 'fallback estimate'],
        };
    }
}
