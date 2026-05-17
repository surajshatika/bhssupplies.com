# AI SEO Suite — Production-Level Improvement Plan
**Project:** BHS Supplies (Active eCommerce Laravel platform)
**Date:** 2026-05-16
**Author:** Senior SEO Architect Review
**Goal:** Take the existing `app/Services/Seo/*` foundation to AIOSEO / Yoast / Rank Math / WP Rocket / NitroPack / Semrush feature parity, with a flagship **AI SEO Board** that auto-detects and one-click-fixes every page on the site.

---

## 0. EXECUTIVE SUMMARY

A substantial SEO Suite already exists, but it is **fragile, partial, and not production-grade**. Key findings:

| Layer | Status | Critical Issue |
|---|---|---|
| **Migrations** | ❌ **MISSING** | 7 `seo_*` tables live in prod SQL dump but **zero Laravel migrations** in `database/migrations/`. New environments break. |
| **Services** | ✅ 35+ feature classes | Mostly thin AI wrappers; many return rule-based stubs, not real analysis. |
| **Score model** | ⚠️ URL-keyed | `seo_score_histories` keys by `url`, not by entity (`product_id`/`category_id`). Can't bulk-render "score per product". |
| **Frontend output** | ❌ Not wired | No middleware that auto-injects `<meta>`, OG, JSON-LD on public pages from saved SEO data. Existing `Helpers.php` writes meta_title manually. |
| **Redirect engine** | ⚠️ Table only | `seo_redirects` exists; **no middleware actually consumes it.** 301s never fire. |
| **Scheduler** | ❌ None | No `App\Console\Kernel::schedule()` entries for sitemap refresh, score snapshots, broken-link sweep, IndexNow ping. |
| **AI Board** | ❌ Not built | Per user request — needs to be the **flagship feature** of this plan. |
| **Speed layer** | ⚠️ Manual `.htaccess` | No critical-CSS, no defer-JS bundler, no edge-cache integration. WP Rocket / NitroPack functionality is absent. |
| **Polymorphic SEO** | ❌ Inline columns | `meta_title` lives on `products`, `categories`, `pages`, `blogs` separately. No `seo_meta` polymorphic table → can't store OG, Twitter, focus_keyword, score per entity uniformly. |
| **Observers** | ❌ None | Slug changes don't auto-create 301s. Save events don't bust sitemap cache. |
| **Caching** | ⚠️ Cache::flush() | `saveSettings` does `Cache::flush()` — nuclear; will wipe product cache too. |

---

## 1. WHAT EXISTS TODAY (Audit Snapshot)

### 1.1 Routes (`routes/admin.php` line 342–426)
- `/admin/seo-suite` — main dashboard (`SeoSuiteController@index`, view `backend.seo_suite.index`)
- `/admin/seo-suite/{sitemap,robots,redirects,indexnow,llms-txt,ai-*,keyword-tracker,search-stats,index-status,webmaster,revisions,link-assistant}`
- `/admin/seo/on-page`, `/admin/seo/off-page`, `/admin/seo/optimization` — alternate module-split controllers (`Seo\OnPageSeoController`, `OffPageSeoController`, `OptimizationController`)
- `/admin/seo/optimization/generate-meta`, `/generate-product-content`, `/generate-category-content` — AJAX endpoints already wired into product/category/page edit screens

### 1.2 Services (`app/Services/Seo/`)
- **OnPage Features (13):** MetaTagService, KeywordDensityService, ContentWriterService, HeadingStructureService, AltTextService, InternalLinkService, SchemaMarkupService, ReadabilityService, SeoAuditService, OpenGraphService, TruSeoAnalysisService, BreadcrumbService, ImageSeoService
- **OffPage Features (5):** BacklinkOutreachService, GuestPostService, SocialSignalService, PressReleaseService, AnchorTextService
- **Optimization Features (27):** SmartSitemapService, VideoSitemapService, GoogleNewsSitemapService, RssContentService, RobotsTxtService, RedirectService, BrokenLinkService, CanonicalService, FaqSchemaService, LocalSeoService, EcommerceSeoService, SmallBusinessSeoService, ScoreDashboardService, IndexNowService, LinkAssistantService, WebmasterToolsService, KeywordRankTrackerService, SearchStatisticsService, PostIndexStatusService, SeoRevisionsService, LlmsTxtService, AiWritingAssistantService, AiImageGeneratorService, AiAssistantService, PageSpeedService, TechnicalAuditService, CompetitorGapService
- **Providers (4):** OpenAIProvider, ClaudeProvider, GeminiProvider, GrokProvider + NullProvider fallback (via SeoProviderManager)

### 1.3 Database (production, no migrations)
- `seo_projects(id, name, slug, base_url, default_provider, settings_json, created_by, ts)`
- `seo_runs(id, project_id, module, feature, provider, status, target_type, target_id, url, input_payload, result_payload, error_message, started_at, completed_at, ts)`
- `seo_redirects(id, source_url UNIQUE, target_url, status_code, is_active, notes, ts)`
- `seo_score_histories(id, project_id, seo_run_id, url, score, grade, metrics, recorded_at, ts)`
- `seo_campaigns(id, project_id, name, type, status, settings_json, started_at, completed_at, created_by, ts)`
- `on_page_seo_pages(id, project_id, url, title, focus_keyword, meta_description, h1, word_count, images_count, internal_links_count, seo_score, seo_grade, status, provider, input_payload, result_payload, last_audited_at, ts)`
- `on_page_seo_tasks(id, page_id, seo_run_id, feature, status, result_payload, completed_at, ts)`
- Inline meta on existing tables: `products.meta_title/meta_description/meta_keywords/meta_img`, `categories.meta_title/meta_description`, `pages.meta_title/meta_description`, `blogs.meta_title/...`

### 1.4 Admin UI
- `seo_suite/index.blade.php` (813 lines) — feature-rich dashboard with setup wizard, score ring, charts
- `seo/on_page/index.blade.php` (173) — feature-card grid + modal trigger
- `seo/off_page/index.blade.php` (157) — same pattern
- `seo/optimization/index.blade.php` (416) — best of the three: tool groups, score cards, redirects, trend list
- `seo/settings.blade.php` (253) — provider keys, verification codes, IndexNow
- 9 sub-views under `seo_suite/`: ai_assistant, ai_image_generator, keyword_tracker, link_assistant, post_index_status, search_statistics, seo_revisions, webmaster_tools, index

### 1.5 Product/Category Admin Integration ✅ (already wired)
- `product/products/edit.blade.php:962` exposes `window.SEO_META_URL` and `window.AI_PRODUCT_CONTENT_URL`
- `product/categories/edit.blade.php` uses `generateMetaTags` + `generateCategoryContent`
- `website_settings/pages/edit.blade.php` uses `generateMetaTags`

---

## 2. GAP ANALYSIS vs INDUSTRY LEADERS

| Capability | Yoast / AIOSEO / Rank Math | WP Rocket / NitroPack | Semrush | **BHS Today** | **Gap** |
|---|---|---|---|---|---|
| Per-entity meta editor with live preview | ✅ | – | – | ❌ (no SERP preview) | **Build** |
| TruSEO / Real-time content score | ✅ Rank Math TruSEO | – | – | ⚠️ Backend only, not live in editor | **Wire live JS** |
| Auto 301 on slug change | ✅ Rank Math, AIOSEO | – | – | ❌ | **Build (Observer)** |
| Bulk schema (Product/FAQ/Breadcrumb/LocalBusiness) | ✅ all 3 | – | – | ⚠️ Services exist; not auto-injected | **Wire frontend** |
| XML sitemap (image + video + news) | ✅ all 3 | – | – | ⚠️ Services exist; no scheduler | **Schedule + cache** |
| Robots.txt editor + URL tester | ✅ AIOSEO, RM | – | – | ⚠️ Generator only | **Add tester** |
| Redirect manager with chain detection | ✅ Rank Math, AIOSEO | – | – | ⚠️ Table only, no middleware | **Add middleware** |
| Broken link detector with auto-replace | ✅ Rank Math | – | – | ⚠️ Stub HTTP-checks 20 links | **Background scan + persist** |
| Internal linking suggestions | ✅ Link Whisper, AIOSEO | – | – | ⚠️ Service exists | **Wire to editor** |
| Local SEO / GMB / Knowledge graph | ✅ AIOSEO Pro, RM Pro | – | – | ⚠️ Schema only | **Add GMB connector** |
| Search Console integration (GSC API) | ✅ AIOSEO, RM | – | ✅ | ❌ (stub only) | **Build OAuth flow** |
| Bing IndexNow | ✅ Rank Math | – | – | ✅ Done | – |
| Keyword tracking with SERP API | ✅ RM Pro, AIOSEO Pro | – | ✅ flagship | ⚠️ Service uses Google CSE | **Add SerpAPI/DataForSEO** |
| Competitor analysis | ⚠️ AIOSEO Topical Map | – | ✅ flagship | ⚠️ Simple set-diff | **Real KW research** |
| Critical CSS / inline above-fold | – | ✅ flagship | – | ❌ | **Build (WP Rocket parity)** |
| Lazy load images + iframes | – | ✅ all | – | ⚠️ Manual `loading="lazy"` only | **Auto-process content** |
| Defer / async JS | – | ✅ all | – | ❌ | **Build** |
| Database cleanup / object cache | – | ✅ WP Rocket | – | ❌ | **Build** |
| Edge cache integration | – | ✅ NitroPack CDN | – | ❌ | **Cloudflare connector** |
| LLMs.txt | ✅ Rank Math | – | – | ✅ Done | – |
| Content AI / AI Writer | ✅ RM Content AI, AIOSEO Writing Asst | – | ✅ Semrush AI | ✅ AiWritingAssistantService | – |
| **One-click bulk auto-fix all SEO issues** | ⚠️ Partial in AIOSEO | – | – | ❌ | **🚩 AI SEO BOARD — flagship** |

---

## 3. THE AI SEO BOARD — Flagship Feature (User's Primary Ask)

### 3.1 Vision
A **single command center** at `/admin/seo-suite/ai-board` that:
1. **Scans** every Product, Category, Page, Blog post on the site
2. **Scores** each entity (0–100) using TruSEO checks
3. **Lists** entities missing meta title / description / OG image / focus keyword / schema / alt text
4. **One-click fix** any single entity, a filtered batch, or **the entire site** — using the configured AI provider
5. **Tracks** before/after score deltas and persists revisions for rollback

### 3.2 Page Layout (single route, tabbed)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  AI SEO BOARD                              [Refresh Scan] [Fix All AI] │
├─────────────────────────────────────────────────────────────────────────┤
│  Site Score: 73/100  ▲+8 this week                                      │
│  ┌────────┬────────┬────────┬────────┬────────┐                         │
│  │ Issues │ Critical│ Warning │  Good  │  Fixed │                       │
│  │  142   │   23    │   119   │  287   │   18   │                       │
│  └────────┴────────┴────────┴────────┴────────┘                         │
├─ Tabs ──────────────────────────────────────────────────────────────────┤
│  [All]  [Products 412]  [Categories 86]  [Pages 12]  [Blogs 24]         │
├─────────────────────────────────────────────────────────────────────────┤
│  Filter: ▼ Missing Meta  ▼ Score <50  ▼ No Schema  ▼ No OG  [Search]   │
├─────────────────────────────────────────────────────────────────────────┤
│  ☐  Title              Type      Score  Issues          Actions         │
│  ☐  Knipex Pliers      Product   42 ▼   No meta, no FK  [Edit][Fix AI]  │
│  ☐  HVAC Filters       Category  68     Thin content    [Edit][Fix AI]  │
│  ☐  About Us           Page      31 ▼   No description  [Edit][Fix AI]  │
│  …                                                                       │
│              [Bulk Fix Selected]  [Export CSV]  [Schedule Auto-Fix]     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.3 Scan & Score Engine

**New service:** `app/Services/Seo/Board/AiSeoBoardService.php`

```php
public function scanAll(array $filters = []): Collection
{
    // For each entity type, build a row:
    return collect()
        ->merge($this->scanProducts())
        ->merge($this->scanCategories())
        ->merge($this->scanPages())
        ->merge($this->scanBlogs());
}

protected function scanProducts(): Collection
{
    return Product::query()
        ->select('id','name','slug','meta_title','meta_description',
                 'meta_img','description','tags','updated_at')
        ->cursor()
        ->map(fn($p) => $this->scoreEntity($p, 'Product'));
}

protected function scoreEntity($entity, string $type): array
{
    $checks = (new TruSeoAnalysisService)->runChecks(
        keyword:     $entity->seo_focus_keyword ?? '',
        title:       $entity->meta_title,
        description: $entity->meta_description,
        content:     $entity->description ?? '',
        url:         $this->urlFor($entity, $type)
    );
    return [
        'id'          => $entity->id,
        'type'        => $type,
        'title'       => $entity->name ?? $entity->title,
        'url'         => $this->urlFor($entity, $type),
        'score'       => $this->calcScore($checks),
        'grade'       => $this->gradeFromScore(...),
        'issues'      => collect($checks)->where('pass', false)->pluck('label')->all(),
        'has_meta'    => !empty($entity->meta_title) && !empty($entity->meta_description),
        'has_og'      => !empty($entity->meta_img),
        'has_schema'  => $this->detectSchema($entity, $type),
        'last_scan'   => now(),
    ];
}
```

### 3.4 One-Click Fix-All Engine

**New job:** `app/Jobs/Seo/AiAutoFixSeoJob.php`

```php
public function handle(): void
{
    $entities = $this->payload['entity_ids']; // [['type'=>'Product','id'=>5], ...]
    foreach ($entities as $ent) {
        try {
            $this->fixEntity($ent);
            event(new SeoEntityFixed($ent));   // pushes WebSocket/Pusher progress
        } catch (\Throwable $e) {
            \Log::warning("AI auto-fix failed for {$ent['type']}#{$ent['id']}", ['e' => $e->getMessage()]);
        }
    }
}

protected function fixEntity(array $ent): void
{
    $model = $this->resolveModel($ent);
    $ai = SeoProviderManager::make($this->payload['provider']);

    // 1. Meta title + description if missing
    if (empty($model->meta_title) || empty($model->meta_description)) {
        $generated = $ai->generate(...);
        $model->meta_title       = $this->trim($generated['title'], 60);
        $model->meta_description = $this->trim($generated['description'], 160);
    }

    // 2. Focus keyword if missing (extract from name + AI suggest)
    if (empty($model->seo_focus_keyword ?? null)) {
        $model->seo_focus_keyword = $ai->generateKeyword($model);
    }

    // 3. OG image — fall back to primary product image if no meta_img
    if (empty($model->meta_img) && $model->thumbnail_img) {
        $model->meta_img = $model->thumbnail_img;
    }

    // 4. Schema — generate JSON-LD and store in seo_meta polymorphic table
    SeoMeta::updateOrCreate(
        ['model_type' => $ent['type'], 'model_id' => $ent['id']],
        ['schema_json' => app(SchemaMarkupService::class)->forEntity($model, $ent['type'])]
    );

    // 5. Re-score and persist revision
    $newScore = (new AiSeoBoardService)->scoreEntity($model, $ent['type']);
    SeoScoreHistory::create([
        'project_id'  => $this->projectId,
        'target_type' => $ent['type'],
        'target_id'   => $ent['id'],
        'score'       => $newScore['score'],
        'grade'       => $newScore['grade'],
        'metrics'     => $newScore,
        'recorded_at' => now(),
    ]);

    $model->save();
}
```

### 3.5 Progress UI (real-time)

- Use **Pusher / Laravel Echo / Server-Sent Events** (or simple `setInterval` poll on `/admin/seo-suite/ai-board/progress?batch_id=…`)
- Show: `[██████░░░░░░] 47/200 fixed (23.5%) — currently fixing: Knipex 4-in-1 Multitool`
- Cancel button → marks batch as `cancelled`; job checks `$batch->status` between entities

### 3.6 Smart Cost Controls

- Settings: `max_ai_calls_per_run` (default 50), `max_tokens_per_entity` (default 500), `daily_budget_usd` (default $5)
- Per-provider cost estimation (OpenAI ~$0.0003/1k input + $0.0006/1k output for gpt-4o-mini)
- Pre-flight estimate before bulk fix: "This will run 412 AI calls (~$1.84 in OpenAI fees). Continue?"

### 3.7 Auto-Schedule Mode

- Toggle in board: **"Auto-fix new content as it's saved"**
- Wires `ProductObserver::created`, `CategoryObserver::created`, `PageObserver::created` → queue `AiAutoFixSeoJob` for single entity

---

## 4. PRODUCTION-LEVEL IMPROVEMENT PLAN (Phased)

### PHASE 1 — Foundation (Critical, Week 1)

#### 1.1 Write the missing migrations
Create proper `database/migrations/` files for every `seo_*` table currently only present in the SQL dump. Each migration must be idempotent (`Schema::hasTable` guard) so it runs cleanly on existing prod **and** fresh installs:

```
2026_05_16_000001_create_seo_projects_table.php
2026_05_16_000002_create_seo_runs_table.php
2026_05_16_000003_create_seo_redirects_table.php
2026_05_16_000004_create_seo_score_histories_table.php
2026_05_16_000005_create_seo_campaigns_table.php
2026_05_16_000006_create_on_page_seo_pages_table.php
2026_05_16_000007_create_on_page_seo_tasks_table.php
2026_05_16_000008_create_seo_meta_table.php          # NEW polymorphic
2026_05_16_000009_create_seo_broken_links_table.php  # NEW
2026_05_16_000010_create_seo_keywords_table.php      # NEW
2026_05_16_000011_create_seo_analytics_table.php     # NEW GSC daily snapshot
2026_05_16_000012_alter_seo_score_histories_add_target.php   # add target_type+target_id
2026_05_16_000013_alter_products_add_seo_focus_keyword.php   # add focus_keyword, og_title, og_description, twitter_title, twitter_description, schema_type, robots_meta, canonical_url
2026_05_16_000014_alter_categories_add_seo_fields.php
2026_05_16_000015_alter_pages_add_seo_fields.php
```

The new **`seo_meta`** polymorphic table is the linchpin — it lets us store per-entity SEO data **without** ballooning the entity tables further:

```php
Schema::create('seo_meta', function (Blueprint $t) {
    $t->id();
    $t->morphs('model');                       // model_type + model_id (Product, Category, Page, Blog)
    $t->string('lang', 8)->default('en');
    $t->string('focus_keyword', 191)->nullable();
    $t->json('secondary_keywords')->nullable();
    $t->string('canonical_url', 500)->nullable();
    $t->string('robots_meta', 100)->default('index, follow');
    $t->string('og_title', 191)->nullable();
    $t->text('og_description')->nullable();
    $t->string('og_image', 500)->nullable();
    $t->string('twitter_card', 30)->default('summary_large_image');
    $t->string('twitter_title', 191)->nullable();
    $t->text('twitter_description')->nullable();
    $t->string('twitter_image', 500)->nullable();
    $t->json('schema_json')->nullable();        // JSON-LD blob
    $t->json('breadcrumbs_json')->nullable();
    $t->decimal('seo_score', 5, 2)->nullable();
    $t->string('seo_grade', 2)->nullable();
    $t->json('analysis_checks')->nullable();    // TruSEO checks last run
    $t->timestamp('last_analyzed_at')->nullable();
    $t->timestamps();
    $t->unique(['model_type', 'model_id', 'lang'], 'seo_meta_morph_lang_unique');
    $t->index('seo_score');
});
```

#### 1.2 Trait & Models
- `app/Models/SeoMeta.php` (new) with morphTo relationship
- `app/Traits/HasSeoMeta.php` — attach to Product, Category, Page, Blog
- `app/Models/SeoBrokenLink.php`, `SeoKeyword.php`, `SeoAnalytics.php` (new)
- Backfill: `php artisan seo:migrate-inline-meta` artisan command — copies `products.meta_title/meta_description/meta_img` into `seo_meta` rows so the polymorphic table becomes source of truth without losing existing data

#### 1.3 SeoServiceProvider
Create `app/Providers/SeoServiceProvider.php`:
- Registers Blade directives `@seoMeta($entity)`, `@schema($json)`, `@breadcrumbs($entity)`
- Registers observers (`ProductObserver`, `CategoryObserver`, `PageObserver`, `BlogObserver`)
- Binds `SeoMetaResolver` singleton (used by frontend partial to look up SEO for the current entity in the view)
- Listed in `config/app.php` providers array

---

### PHASE 2 — Redirect Middleware (Critical, Week 1)

Currently `seo_redirects` table exists but **no middleware reads it.** 301s never fire. Build:

`app/Http/Middleware/SeoRedirectMiddleware.php`
```php
public function handle($request, Closure $next)
{
    $path = '/' . ltrim($request->path(), '/');
    $map  = Cache::remember('seo_redirects_map', 3600, fn() =>
        SeoRedirect::where('is_active', true)->pluck('target_url', 'source_url')->toArray()
    );
    if (isset($map[$path])) {
        return redirect($map[$path], 301);   // or pull status_code from row
    }
    return $next($request);
}
```

- Register in `app/Http/Kernel.php` `$middlewareGroups['web']` **before** route resolution
- Cache busted by `SeoRedirect` model `saved` / `deleted` events
- Detect chains (A→B→C → collapse to A→C) on save → emit warning in UI
- CSV import + export (.htaccess + nginx flavor) — already partially built in OptimizationService

---

### PHASE 3 — Frontend Auto-Injection (Critical, Week 1–2)

#### 3.1 Universal meta partial
`resources/views/seo/partials/meta-tags.blade.php`:
```blade
@php
    $meta = app('seo.resolver')->forCurrentRoute();
@endphp
<title>{{ $meta['title'] }}</title>
<meta name="description" content="{{ $meta['description'] }}">
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $meta['og_type'] }}">
<meta property="og:title" content="{{ $meta['og_title'] }}">
<meta property="og:description" content="{{ $meta['og_description'] }}">
<meta property="og:image" content="{{ $meta['og_image'] }}">
<meta property="og:url" content="{{ $meta['canonical'] }}">

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $meta['twitter_card'] }}">
<meta name="twitter:title" content="{{ $meta['twitter_title'] }}">
<meta name="twitter:description" content="{{ $meta['twitter_description'] }}">
<meta name="twitter:image" content="{{ $meta['twitter_image'] }}">

{{-- JSON-LD --}}
@foreach($meta['schema'] as $schema)
    <script type="application/ld+json">{!! $schema !!}</script>
@endforeach

{{-- Hreflang (multi-lang) --}}
@foreach($meta['hreflang'] as $lang => $url)
    <link rel="alternate" hreflang="{{ $lang }}" href="{{ $url }}">
@endforeach
```

#### 3.2 SeoMetaResolver
`app/Services/Seo/SeoMetaResolver.php` — singleton that:
- Detects current route name → maps to entity type (`product` route → Product model from `$request->route('slug')`)
- Reads `seo_meta` row, falls back to inline columns, falls back to AI-generated, falls back to template (site name + category)
- Adds default schemas: Organization (site-wide), WebSite (with SearchAction)
- Caches result per URL for 5 minutes

#### 3.3 Replace existing `<title>` blocks
- `resources/views/frontend/layouts/app.blade.php` — replace the manual `<title>` and `<meta description>` blocks with `@include('seo.partials.meta-tags')`
- Remove duplicate title-writing logic in `Helpers.php` (keep helpers as fallbacks)

---

### PHASE 4 — AI SEO Board (Flagship, Week 2)

Build the page described in §3. Concrete deliverables:

| File | Purpose |
|---|---|
| `app/Http/Controllers/Seo/AiSeoBoardController.php` | index, scan, fix, fixAll, progress, exportCsv |
| `app/Services/Seo/Board/AiSeoBoardService.php` | scanAll, scoreEntity, queueBatch, applyFix |
| `app/Jobs/Seo/AiAutoFixSeoJob.php` | Per-entity fix, retries=2, timeout=120s |
| `app/Jobs/Seo/AiSeoBatchJob.php` | Orchestrates N entity jobs, persists batch status |
| `app/Models/SeoFixBatch.php` | id, project_id, status, total, processed, failed, started_at, completed_at, ai_provider, estimated_cost, actual_cost |
| `resources/views/backend/seo/ai_board/index.blade.php` | Main board (tabs, filters, table, bulk action bar) |
| `resources/views/backend/seo/ai_board/components/score_card.blade.php` | Reusable score chip |
| `resources/views/backend/seo/ai_board/components/issues_modal.blade.php` | Per-entity issue list + preview of AI-suggested fix |
| `routes/admin.php` new routes | `/admin/seo-suite/ai-board[/scan|/fix|/progress/{batch}|/export]` |
| `database/migrations/..._create_seo_fix_batches_table.php` | Batch tracking |

**Key UX details:**
- Server-side pagination with `cursor` (LazyCollection) — site might have 50k products
- Filters persisted in URL query for shareable links
- Each row has 4 quick badges: 🏷️ Meta, 📷 OG, 📐 Schema, 🔑 Focus KW (green/red)
- "Fix AI" button on a row opens an inline preview drawer with the AI-generated diff BEFORE applying — user clicks **Apply** to write
- Bulk Fix shows **dry-run estimate** (calls + tokens + $ cost) before executing
- Sticky "Site Score" header card updates every 10s during a batch run via SSE

---

### PHASE 5 — Observers & Auto-Workflows (Week 2)

#### 5.1 Slug-change → auto 301
```php
// app/Observers/ProductObserver.php
public function updating(Product $p): void {
    if ($p->isDirty('slug') && $p->getOriginal('slug')) {
        SeoRedirect::updateOrCreate(
            ['source_url' => '/product/' . $p->getOriginal('slug')],
            ['target_url' => '/product/' . $p->slug, 'status_code' => 301, 'is_active' => true, 'notes' => 'Auto-created on slug change']
        );
        Cache::forget('seo_redirects_map');
    }
}
```

#### 5.2 Save → invalidate sitemap cache
```php
public function saved(Product $p): void {
    if (get_setting('seo_auto_sitemap', 1)) {
        Cache::forget('sitemap.xml');
        if ($p->wasRecentlyCreated || $p->wasChanged(['name','slug','published'])) {
            dispatch(new RegenerateSitemapJob())->afterCommit();
        }
    }
}
```

#### 5.3 Save → re-score in background
```php
public function saved(Product $p): void {
    dispatch(new RescoreEntityJob('Product', $p->id))->onQueue('seo')->afterCommit();
}
```

#### 5.4 Save → ping IndexNow
```php
public function saved(Product $p): void {
    if (get_setting('seo_auto_indexnow', 0) && $p->published) {
        IndexNowService::ping(route('product', $p->slug))->onQueue('seo')->afterCommit();
    }
}
```

---

### PHASE 6 — Scheduler (Week 2)

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule) {
    // Daily 2am — rebuild full sitemap
    $schedule->command('seo:generate-sitemap')->dailyAt('02:00');

    // Weekly Mon 3am — broken-link sweep
    $schedule->command('seo:check-broken-links')->weeklyOn(1, '03:00');

    // Daily 4am — fetch GSC clicks/impressions for last 7d
    $schedule->command('seo:sync-search-console')->dailyAt('04:00');

    // Every 6 hours — keyword rank check (if SerpAPI key set)
    $schedule->command('seo:check-keyword-ranks')->everySixHours();

    // Daily 1am — snapshot site-wide score for trend chart
    $schedule->command('seo:snapshot-scores')->dailyAt('01:00');

    // Hourly — process pending AI fix batches (in case worker died mid-batch)
    $schedule->command('seo:resume-batches')->hourly();
}
```

New commands (`app/Console/Commands/Seo/`):
- `GenerateSitemapCommand.php`
- `CheckBrokenLinksCommand.php`
- `SyncSearchConsoleCommand.php` — Google OAuth2, store tokens in `business_settings`
- `CheckKeywordRanksCommand.php`
- `SnapshotScoresCommand.php`
- `ResumeBatchesCommand.php`

---

### PHASE 7 — Speed Optimization Layer (WP Rocket / NitroPack parity, Week 3)

A new sibling module at `app/Services/Seo/Speed/`:

#### 7.1 Critical CSS extraction
- Command: `php artisan seo:extract-critical-css --route=/`
- Uses headless Chrome (puppeteer via `node_modules/puppeteer` or `spatie/browsershot`) to fetch the page and emit only above-fold CSS
- Writes to `public/assets/css/critical/{route-slug}.css`
- Frontend layout injects via `<style>{!! file_get_contents(...) !!}</style>` and async-loads the full CSS

#### 7.2 Image lazy-load + WebP
- Already partially done (`loading="lazy"` added in Phase 3 of speed plan)
- Add: rewrite filter that processes `{!! $product->description !!}` HTML — adds `loading="lazy"` and `decoding="async"` to every `<img>` not in first 600px
- Already have `images:convert-webp` artisan command — wire it to scheduler to run nightly on new uploads

#### 7.3 Defer non-critical JS
- New helper `defer_script($src)` — emits `<script defer src="...">`
- Replace direct `<script>` tags in `app.blade.php` (lines 539–1450 inline JS) with deferred external file `aiz-inline.js`
- Auto-defer 3rd-party tags (GA, GTM, Clarity) via GTM loader

#### 7.4 Browser cache headers (already in .htaccess) + edge cache integration
- `app/Services/Seo/Speed/CloudflareService.php` — read Cloudflare API token from settings, expose:
  - `purgeCache(array $urls)`
  - `purgeEverything()`
  - `setDevelopmentMode(bool)`
- Wire to ProductObserver: on save, purge `route('product', $slug)` at the edge

#### 7.5 Database cleanup
- `app/Console/Commands/Seo/CleanupDatabaseCommand.php`
- Runs weekly: deletes `failed_jobs` >30d, `seo_runs` `completed` >90d (keep last 100 per project), `cache` orphaned rows
- Reports rows deleted in admin notification

---

### PHASE 8 — Real Search Console & Keyword Tracking (Week 3)

The existing `SearchStatisticsService` and `KeywordRankTrackerService` are stubs. Make them real:

#### 8.1 Google Search Console OAuth
- New page: `/admin/seo-suite/integrations/search-console`
- OAuth2 flow → stores `refresh_token` encrypted in `business_settings`
- `SyncSearchConsoleCommand` calls `https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query` daily, persists into `seo_analytics`:
  ```
  date, query, page, country, device, clicks, impressions, ctr, position
  ```
- Dashboard widget: Top queries, top pages, CTR trend, impression-to-clicks funnel

#### 8.2 Keyword rank tracking
- Add provider abstraction: `SerpRankerInterface` with `SerpApiRanker`, `DataForSeoRanker`, `ZenSerpRanker`, `ScraperApiRanker`
- Settings UI: pick provider + API key
- `seo_keywords` table tracks: keyword, target_url, rank_current, rank_previous, search_volume, kw_difficulty, last_checked_at
- Daily job runs 50 KWs/day (free tier safe), persists rank delta, alerts on >10 position drops

#### 8.3 Real competitor analysis
- Replace `CompetitorGapService` set-diff stub with:
  - Crawl competitor URL, extract title/H1/H2/meta
  - Compare keyword density vs ours
  - Use AI to suggest 5 content gaps + 5 quick-win pages

---

### PHASE 9 — Caching Hardening (Week 3)

Replace nuclear `Cache::flush()` in `SeoSuiteController::saveSettings` with tagged invalidation:

```php
// On settings save:
Cache::tags(['seo:settings', 'seo:meta-resolver'])->flush();
// NOT Cache::flush() — that nukes product/cart cache too
```

Tag every SEO-related cache write:
- `Cache::tags(['seo:sitemap'])->put('sitemap.xml', ...)`
- `Cache::tags(['seo:redirects'])->put('seo_redirects_map', ...)`
- `Cache::tags(['seo:meta', "seo:meta:{$type}:{$id}"])->put(...)`

Note: requires Redis/Memcached driver; if file driver, fall back to explicit `Cache::forget()` lists per setting type. Detect driver in service provider.

---

### PHASE 10 — Security & Rate Limiting (Week 3)

- **All AJAX endpoints** in `OptimizationController` (`generateMetaTags`, `generateProductContent`, `generateCategoryContent`) currently have **no rate limit**. A malicious admin (or stolen session) could run up huge OpenAI bills.
  - Apply `throttle:30,1` middleware (30 requests/min)
  - Add per-day spend cap from `seo_settings.daily_budget_usd`; track in `seo_runs` and refuse new runs over budget
- **CSRF** — confirm every form has `@csrf` (looks good in current views)
- **Input sanitization** — `generateMetaTags` strips tags ✅; but `extra_payload` in `SeoSuiteController::buildPayload` does `json_decode` of user input — make sure size-limited (`max:10000`) to prevent DoS
- **Encryption of API keys** — `business_settings.value` stores OpenAI / Claude keys as plaintext. Add accessor/mutator on `BusinessSetting` model: if `type LIKE '%api_key'`, encrypt at write, decrypt at read

---

### PHASE 11 — Unified UI Restructure (Week 3–4)

Currently there are **two** competing nav paths:
- `/admin/seo-suite/*` (main)
- `/admin/seo/{on-page,off-page,optimization}/*` (alternate split)

This duplicates 18 routes and confuses admins. **Recommendation:** keep `/admin/seo-suite` as the canonical prefix, redirect `/admin/seo/*` permanently. The split-controllers (`OnPageSeoController`, `OffPageSeoController`, `OptimizationController`) become thin delegators that just `return redirect()->route('admin.seo-suite.xyz')`.

New sidebar structure:
```
AI SEO Suite
├── Dashboard
├── 🚩 AI SEO Board                ← FLAGSHIP
├── On-Page SEO
│   ├── Meta Editor (all entities)
│   ├── Schema Manager
│   ├── Open Graph
│   ├── Content Analyzer
│   └── Readability
├── Off-Page SEO
│   ├── Backlink Outreach
│   ├── Guest Post Generator
│   ├── Social Signals
│   └── Press Releases
├── Technical
│   ├── Sitemap Manager
│   ├── Robots.txt
│   ├── Redirects (301/302/410)
│   ├── Broken Links
│   ├── Canonical Manager
│   └── LLMs.txt
├── Local SEO
│   ├── Google Business Profile
│   ├── NAP Citations
│   └── Local Schema
├── Performance ← NEW
│   ├── Critical CSS
│   ├── Image Optimization
│   ├── JS/CSS Minification
│   ├── Lazy Loading
│   ├── Cloudflare CDN
│   └── Database Cleanup
├── Search Console
│   ├── Performance (clicks, impressions)
│   ├── Index Coverage
│   ├── Keyword Rankings
│   └── Sitemap Submission
├── AI Tools
│   ├── SEO Chat Assistant
│   ├── Content Writer
│   ├── Image Generator
│   ├── Link Assistant
│   └── Competitor Gap
└── Settings
    ├── AI Providers & API Keys
    ├── Webmaster Verification
    ├── Budget & Rate Limits
    ├── Auto-Fix Rules
    └── Integrations (GSC, GA4, Cloudflare)
```

---

### PHASE 12 — Testing, Monitoring, Docs (Week 4)

#### 12.1 Tests
- **Feature tests** for: redirect middleware, SeoMetaResolver, AiSeoBoardService::scanAll, AiAutoFixSeoJob (with fake AI provider)
- **Unit tests** for TruSeoAnalysisService score math
- **Browser tests** (Laravel Dusk) for: editor live-score, Bulk Fix flow, settings save without 500

#### 12.2 Monitoring
- Log every AI call: provider, tokens-in, tokens-out, latency, cost, success
- Expose `/admin/seo-suite/monitoring` — daily/weekly cost chart, error rate, slowest features
- Sentry integration: tag SEO errors with `module: seo`

#### 12.3 Documentation
- `README.md` per service folder (terse — 10 lines max)
- Admin help video links on each tool card (YouTube unlisted)
- `php artisan seo:doctor` — diagnostic command that checks: migrations run, queue worker alive, AI key valid, sitemap fresh, robots.txt readable, GSC reachable

---

## 5. PRIORITIZED ROADMAP

| # | Phase | Why First | Effort | Risk |
|---|---|---|---|---|
| 1 | **Migrations + SeoMeta polymorphic table** | Everything else builds on this | 2d | Low |
| 2 | **Redirect middleware** | 301s currently broken | 0.5d | Low |
| 3 | **SeoMetaResolver + frontend partial** | Public pages get correct meta | 1.5d | Med (touches every view) |
| 4 | **🚩 AI SEO Board** | User's primary ask | 4d | Med |
| 5 | **Observers (slug→301, save→sitemap)** | Prevents 404 regressions | 1d | Low |
| 6 | **Scheduler + commands** | Background hygiene | 1.5d | Low |
| 7 | **Speed layer (critical CSS, defer JS, Cloudflare)** | Score-variability fix | 4d | Med |
| 8 | **GSC OAuth + real keyword tracking** | Actual SEO insights | 3d | Med (external APIs) |
| 9 | **Cache tagging + security/rate-limit** | Cost & stability | 1d | Low |
| 10 | **Unified UI restructure** | UX clarity | 2d | Low |
| 11 | **Tests + monitoring + docs** | Production-ready | 2d | Low |

**Total:** ~22.5 dev days (5 weeks single dev, 3 weeks two devs).

---

## 6. ROLLBACK & MIGRATION SAFETY

- **All new migrations** use `Schema::hasTable` / `Schema::hasColumn` guards → safe to run on prod where tables already exist
- **`seo_meta` backfill command** is idempotent (`updateOrCreate`) — can run multiple times safely
- **Inline columns** (`products.meta_title` etc.) kept as fallback for 1 release cycle, then dropped in v2
- **Feature flag** `SEO_AI_BOARD_ENABLED=true` in .env — lets you deploy code dark and flip on per environment
- **Per-fix dry run preview** in AI Board UI — operator sees AI's proposal before any DB write

---

## 7. WHAT THIS PLAN DELIBERATELY DOES NOT DO

To stay shippable:
- **No frontend rebuild.** Keep current Active eCommerce Blade structure; only inject SEO partial into existing layout files.
- **No new build system.** Don't introduce Vite/Webpack rewrite for critical-CSS — use Browsershot from PHP-land instead.
- **No new permissions matrix.** Use existing admin guards; permissions polish in Phase 12 only if time permits.
- **No multi-tenancy / multi-site.** Single project (`default-seo-suite`). Add later if needed.
- **No replacing existing rule-based services with AI-only.** Keep both; AI augments rules, doesn't replace them (cost + reliability).

---

## 8. SUCCESS METRICS (after 5 weeks)

| Metric | Today | Target |
|---|---|---|
| Products with meta_title set | ~?% (audit needed via AI Board scan) | 100% |
| Products with seo_score ≥ 70 | unknown | 80% |
| Avg PageSpeed Desktop | 45 | 80 |
| Lighthouse SEO score | 68 | 95 |
| Broken internal links | unknown | <5 |
| 404s in GSC last 30d | unknown | 0 critical |
| AI cost per month | unbounded | <$30 (with caps) |
| Admin time per new product to publish-ready | ~5min manual SEO | <30s (AI Board auto-fix) |

---

## 9. FIRST 3 COMMITS (Concrete Starting Point)

If approved, here's exactly what I would commit first, in order:

**Commit 1 — Make the project deployable to a fresh environment**
- 7 migrations for existing `seo_*` tables (with `Schema::hasTable` guards)
- `SeoMeta` model + `seo_meta` migration + `HasSeoMeta` trait
- `php artisan seo:migrate-inline-meta` command (backfill from products/categories/pages)

**Commit 2 — Make redirects actually fire and slug changes safe**
- `SeoRedirectMiddleware` registered in web group
- `ProductObserver`, `CategoryObserver`, `PageObserver` (slug-change auto-301)
- Tests for both

**Commit 3 — Build the AI SEO Board MVP**
- Route, controller, service, view, single-entity fix only (no bulk yet)
- Score column on AI Board lists comes from existing `TruSeoAnalysisService::runChecks()`
- "Fix AI" button → opens drawer → shows AI suggestion → Apply writes `seo_meta` row
- Ships visible value to user, gives feedback loop before tackling bulk-fix complexity

After these 3 commits the foundation is solid; phases 4–12 are additive.

---

## END OF PLAN

**Open questions for decision before Commit 1:**
1. Is **Redis/Memcached** available on the prod GreenGeek server? (Affects cache tagging strategy.)
2. Is **`php artisan queue:work`** running as a supervisor process on prod? (AI Board batches require it.)
3. Confirm OpenAI is the **primary** AI provider for production (vs Claude/Gemini), so cost model and prompts are tuned to it.
4. Is there a Google Cloud project + OAuth client we can use for the **Search Console integration**, or do we create new credentials?
5. Cloudflare account ownership — do we have admin access to add the site, or only client does it?
