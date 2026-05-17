<?php

namespace App\Services\Seo\Board;

use App\Jobs\Seo\AiAutoFixSeoJob;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoFixBatch;
use App\Models\SeoMeta;
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
                ?? Str::limit($name . ' | ' . get_setting('website_name', config('app.name')), 60, '');
            $applied['meta_title'] = $patch['meta_title'];
        }

        if (empty($meta['meta_description'])) {
            $patch['meta_description'] = $aiData['description']
                ?? Str::limit('Shop ' . $name . '. Quality products, fast delivery, and competitive prices.', 160, '');
            $applied['meta_description'] = $patch['meta_description'];
        }

        if (empty($meta['focus_keyword'])) {
            $patch['focus_keyword'] = $aiData['focus_keyword'] ?? Str::limit(Str::lower($name), 60, '');
            $applied['focus_keyword'] = $patch['focus_keyword'];
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

        $afterRow = $this->buildRow($entity->refresh(), $type);

        return [
            'applied'      => $applied,
            'score_before' => $before['score'],
            'score_after'  => $afterRow['score'],
            'source'       => $source,
            'row'          => $afterRow,
        ];
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

        $systemPrompt = 'You are an expert SEO copywriter. Output ONLY valid JSON, no markdown, no code fences, no extra commentary.';

        $prompt = "Generate SEO metadata for an ecommerce {$entityLabel} on {$siteName}.\n"
            . "Name: \"{$name}\"\n"
            . ($description ? "Details: \"" . Str::limit($description, 400) . "\"\n" : '')
            . "Return ONLY this JSON shape with no other text:\n"
            . '{"title":"SEO title 30-60 chars","description":"meta description 120-160 chars","focus_keyword":"primary keyword phrase"}';

        try {
            $raw = $ai->generate($prompt, $systemPrompt, ['max_tokens' => 400]);
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
            ];
        } catch (Throwable $e) {
            logger()->warning('AiSeoBoard ai bundle failed', ['e' => $e->getMessage()]);
            return null;
        }
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
