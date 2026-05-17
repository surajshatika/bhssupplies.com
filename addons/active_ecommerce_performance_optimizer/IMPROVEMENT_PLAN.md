# Performance Optimizer — Implementation Plan
**Target**: Upgrade v1.0 → v2.0 with 4 advanced feature sets
**Audience**: New Claude Code / developer session picking this up cold
**Working dir**: `d:/wamp/www/bhssupplies1/`
**Addon root**: `addons/active_ecommerce_performance_optimizer/`
**Live install paths**: Files already copied to `app/`, `routes/`, `resources/views/backend/performance_optimizer/`, `public/assets/performance_optimizer/`

---

## TABLE OF CONTENTS

1. [Context — what's already built (v1.0)](#1-context--whats-already-built-v10)
2. [Goal of this session — v2.0 deliverables](#2-goal-of-this-session--v20-deliverables)
3. [Pre-flight checklist](#3-pre-flight-checklist)
4. [Implementation order (6 milestones)](#4-implementation-order-6-milestones)
5. [Milestone 1 — Schema + routes + menu setup](#5-milestone-1--schema--routes--menu-setup)
6. [Milestone 2 — Script Manager](#6-milestone-2--script-manager-module-4)
7. [Milestone 3 — Cache Rules](#7-milestone-3--cache-rules-module-1)
8. [Milestone 4 — Cloudflare + Bunny.net integration](#8-milestone-4--cloudflare--bunnynet-integration-module-10)
9. [Milestone 5 — Slow Query Analyzer + Bot Protection + Hotlink](#9-milestone-5--slow-query-analyzer--bot-protection--hotlink-module-9--14)
10. [Milestone 6 — AI Recommendations engine (rule-based)](#10-milestone-6--ai-recommendations-engine-rule-based-module-7--12--16)
11. [Cross-cutting concerns](#11-cross-cutting-concerns)
12. [Testing checklist](#12-testing-checklist)
13. [Definition of Done](#13-definition-of-done)
14. [Rollback plan](#14-rollback-plan)
15. [Appendix A — Module deep dives (for reference)](#appendix-a--module-deep-dives-for-reference)
16. [Appendix B — Future phases (out of this session's scope)](#appendix-b--future-phases-out-of-this-sessions-scope)

---

## 1. Context — what's already built (v1.0)

**Project**: BHS Supplies (Active eCommerce CMS, Laravel 10, PHP 8.2+). Already running locally at `http://127.0.0.1:8000/admin/performance-optimizer`.

### v1.0 inventory (DO NOT recreate these)

**Backend** — already shipped:
- 10 controllers in [app/Http/Controllers/](app/Http/Controllers/): `PerformanceOptimizerController`, `PerformanceImageController`, `PerformanceCssJsController`, `PerformanceCacheController`, `PerformanceDatabaseController`, `PerformanceFontController`, `PerformanceVitalsController`, `PerformanceSecurityController`, `PerformanceLogController`, `PerformanceMonitorController`
- 7 services in [app/Services/PerformanceOptimizer/](app/Services/PerformanceOptimizer/): `ImageOptimizerService`, `CssJsMinifierService`, `PageCacheService`, `DatabaseCleanerService`, `FontOptimizerService`, `WebVitalsService`, `SecurityAuditService`
- 5 middlewares in [app/Http/Middleware/PerformanceOptimizer/](app/Http/Middleware/PerformanceOptimizer/): `PageCacheMiddleware`, `PerformanceOutputMiddleware`, `SecurityHardeningMiddleware`, `AddonActiveMiddleware`, `VitalsCollectMiddleware`
- 2 models in [app/Models/PerformanceOptimizer/](app/Models/PerformanceOptimizer/): `OptimizationLog`, `WebVital`
- 1 job: `app/Jobs/PerformanceOptimizer/ConvertImagesJob.php`
- 1 cron command: `app/Console/Commands/PerformanceOptimizer/PerfAutoCleanCommand.php`
- Routes file: [routes/performance_optimizer.php](routes/performance_optimizer.php) — 36 routes, registered via `RouteServiceProvider::mapPerformanceOptimizerRoutes()`

**Frontend** — already shipped:
- Master view: [resources/views/backend/performance_optimizer/index.blade.php](resources/views/backend/performance_optimizer/index.blade.php) with always-visible top section (Core Web Vitals gauges + 4 stat cards + pill tab bar with icons/badges + Emergency Disable All button + master switch footer)
- 10 tab views in [resources/views/backend/performance_optimizer/tabs/](resources/views/backend/performance_optimizer/tabs/)
- Sidebar partial: [resources/views/backend/performance_optimizer/partials/sidebar_menu.blade.php](resources/views/backend/performance_optimizer/partials/sidebar_menu.blade.php) — already pasted into `admin_sidenav.blade.php`
- CSS: [public/assets/performance_optimizer/css/performance_optimizer.css](public/assets/performance_optimizer/css/performance_optimizer.css) (~450 lines, Sevenopal-style)
- JS: `performance_optimizer.js` + `web_vitals_tracker.js`

**Database** — already exists:
- Tables: `performance_optimization_logs`, `performance_web_vitals`
- 34 `perf_*` rows in `business_settings`
- `manage_performance_optimizer` permission seeded + granted to roles 1 (Super Admin) + 3 (Admin)
- Addon row in `addons` table with `unique_identifier = 'performance_optimizer'`

### v1.0 features already wired
Image lazy-load, defer JS, delay JS (interaction-triggered), critical CSS inline, font preload, font-display swap, force HTTPS, hide PHP version, block XML-RPC, WebP auto-serve via HTML rewrite, page-cache with HIT/MISS/BYPASS headers, security audit (14 points + grade A-F), multi-source log viewer, environment health checklist, OPcache monitor, sitemap warmer.

### v1.0 manual hookups already done in `app/`
- `RouteServiceProvider.php` — `mapPerformanceOptimizerRoutes()` method added + called in `map()`
- `admin_sidenav.blade.php` — sidebar `<li>` block pasted near "AI Chat Support"

**NOT yet hooked up (this session is OK to leave these manual):**
- `app/Http/Kernel.php` global middleware (`SecurityHardeningMiddleware`) — optional
- `app/Http/Kernel.php` web group (`PageCacheMiddleware`, `PerformanceOutputMiddleware`) — optional
- `app/Http/Middleware/VerifyCsrfToken.php` `$except` for `performance-optimizer/collect-vital` — optional
- `app/Console/Kernel.php` schedule for `perf:auto-clean` — optional

---

## 2. Goal of this session — v2.0 deliverables

Implement the **4 advanced feature sets** the user selected, in order:

| # | Feature set | Module ref | Files added | DB tables added |
|---|---|---|---|---|
| 2.1 | **Script Manager** | M4 | 1 controller + 1 service + 1 model + 1 middleware + 1 view tab | `perf_script_rules` |
| 2.2 | **Cache Rules** | M1 | 1 controller + 1 model + 1 view tab + PageCacheService update | `perf_cache_rules` |
| 2.3 | **Cloudflare + Bunny.net integration** | M10 | 1 controller + 2 services (Cloudflare, Bunny) + 1 view tab + event listener | (no new tables — uses business_settings) |
| 2.4 | **Slow Query + Bot Protection + Hotlink** | M9 + M14 | 1 controller + 2 services + 2 middlewares + 1 view tab + 1 cron command | `perf_slow_queries` |
| 2.5 | **AI Recommendations (rule-based)** | M7 + M12 + M16 | 1 controller + 1 engine + 2 models + 2 view tabs + 1 cron command + 1 job | `perf_ai_recommendations`, `perf_auto_fix_history` |

**Total**: 5 new tabs (Script Manager, Cache Rules, Edge CDN, Bot/Security+, AI Recommendations), ~30 new files, 5 new DB tables.

After this session:
- Master view tab bar grows from 10 → 15 tabs
- Sidebar menu grows from 10 → 15 sub-items
- 25-30 new settings rows in `business_settings`
- Routes count: 36 → ~80
- v2.0 ready for ZIP install

---

## 3. Pre-flight checklist

Before writing code, verify:

```bash
# Working directory
cd "d:/wamp/www/bhssupplies1"

# 1. Confirm v1.0 install is healthy
php artisan route:list | grep performance_optimizer | wc -l    # should be 36
ls app/Http/Controllers/Performance*.php | wc -l               # should be 10
ls app/Services/PerformanceOptimizer/ | wc -l                  # should be 7

# 2. Confirm DB state
php -r '
require "vendor/autoload.php"; $app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "Settings rows: " . DB::table("business_settings")->where("type", "LIKE", "perf_%")->count() . "\n";
echo "Tables: "; foreach (DB::select("SHOW TABLES LIKE \"performance_%\"") as $t) echo array_values((array)$t)[0]." ";
echo "\n";
'

# 3. Confirm Laravel caches clean
php artisan cache:clear && php artisan view:clear && php artisan config:clear && php artisan route:clear

# 4. Open the dashboard — should load without errors
# http://127.0.0.1:8000/admin/performance-optimizer
```

If any of these fail, STOP and fix v1.0 before continuing.

---

## 4. Implementation order (6 milestones)

**Hard dependencies** (do not reorder):

```
M1 (schema + routes + menu)
    ↓
    ├── M2 (Script Manager)         ─┐
    ├── M3 (Cache Rules)             ├── Independent, can be parallel
    └── M5 (Slow Q + Bot + Hotlink) ─┘
            ↓
            M4 (Cloudflare + Bunny)    ← uses Cache Rules for purge triggers
                    ↓
                    M6 (AI Recommendations)   ← consumes data from all above
```

Each milestone has a clear **commit point** — verify dashboard still loads + run smoke test before moving on.

---

## 5. Milestone 1 — Schema + routes + menu setup

**Goal**: Land all 5 new DB tables, register placeholder routes, add 5 new tab nav entries so subsequent milestones plug in cleanly.

### 5.1 Add new SQL migration

Create `addons/active_ecommerce_performance_optimizer/sql/1.1.sql`:

```sql
-- =====================================================================
-- Performance Optimizer v2.0 — Phase 3 schema additions
-- =====================================================================

-- M2 — Script Manager
CREATE TABLE IF NOT EXISTS `perf_script_rules` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `script_pattern` VARCHAR(255) NOT NULL,
    `route_pattern` VARCHAR(255) NOT NULL DEFAULT '*',
    `device_filter` ENUM('any','mobile','desktop','tablet') NOT NULL DEFAULT 'any',
    `action`        ENUM('allow','deny','defer','async','delay') NOT NULL DEFAULT 'allow',
    `priority`      INT NOT NULL DEFAULT 50,
    `enabled`       TINYINT(1) NOT NULL DEFAULT 1,
    `note`          VARCHAR(255) NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `perf_script_rules_route_idx` (`route_pattern`),
    KEY `perf_script_rules_enabled_idx` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- M3 — Cache Rules
CREATE TABLE IF NOT EXISTS `perf_cache_rules` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pattern`       VARCHAR(500) NOT NULL,
    `action`        ENUM('cache','bypass','vary_device','vary_locale') NOT NULL DEFAULT 'cache',
    `ttl_minutes`   INT NULL,
    `priority`      INT NOT NULL DEFAULT 50,
    `enabled`       TINYINT(1) NOT NULL DEFAULT 1,
    `note`          VARCHAR(255) NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `perf_cache_rules_enabled_idx` (`enabled`),
    KEY `perf_cache_rules_priority_idx` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- M5 — Slow Query log
CREATE TABLE IF NOT EXISTS `perf_slow_queries` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `query_hash`    CHAR(32) NOT NULL,
    `query_text`    MEDIUMTEXT NOT NULL,
    `avg_time_ms`   INT NOT NULL,
    `max_time_ms`   INT NOT NULL,
    `occurrences`   INT NOT NULL DEFAULT 1,
    `explain_result` JSON NULL,
    `suggested_index` TEXT NULL,
    `last_seen`     TIMESTAMP NOT NULL,
    `first_seen`    TIMESTAMP NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `perf_slow_q_hash_uq` (`query_hash`),
    KEY `perf_slow_q_avg_idx` (`avg_time_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- M6 — AI Recommendations + auto-fix history
CREATE TABLE IF NOT EXISTS `perf_ai_recommendations` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category`      VARCHAR(50) NOT NULL,
    `severity`      ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium',
    `title`         VARCHAR(255) NOT NULL,
    `body`          TEXT NOT NULL,
    `evidence`      JSON NULL,
    `confidence`    TINYINT UNSIGNED NOT NULL DEFAULT 100,
    `auto_fixable`  TINYINT(1) NOT NULL DEFAULT 0,
    `auto_fix_action` VARCHAR(120) NULL,
    `auto_fix_payload` JSON NULL,
    `source`        VARCHAR(50) NOT NULL DEFAULT 'rule_engine',
    `applied_at`    TIMESTAMP NULL,
    `dismissed_at`  TIMESTAMP NULL,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `perf_ai_recs_cat_idx` (`category`),
    KEY `perf_ai_recs_sev_idx` (`severity`),
    KEY `perf_ai_recs_status_idx` (`applied_at`, `dismissed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `perf_auto_fix_history` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recommendation_id` BIGINT UNSIGNED NULL,
    `action`        VARCHAR(120) NOT NULL,
    `before_state`  JSON NULL,
    `after_state`   JSON NULL,
    `applied_at`    TIMESTAMP NOT NULL,
    `rolled_back_at` TIMESTAMP NULL,
    `user_id`       BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    KEY `perf_autofix_rec_idx` (`recommendation_id`),
    KEY `perf_autofix_applied_idx` (`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New business_settings entries
INSERT IGNORE INTO `business_settings` (`type`, `value`) VALUES
    -- Cloudflare (M4)
    ('perf_cloudflare_status',        '0'),
    ('perf_cloudflare_zone_id',       ''),
    ('perf_cloudflare_api_token',     ''),
    ('perf_cloudflare_auto_purge',    '1'),

    -- Bunny.net (M4)
    ('perf_bunny_status',             '0'),
    ('perf_bunny_pull_zone',          ''),
    ('perf_bunny_api_key',            ''),
    ('perf_bunny_cdn_hostname',       ''),

    -- AWS CloudFront (M4)
    ('perf_cloudfront_status',        '0'),
    ('perf_cloudfront_distribution',  ''),
    ('perf_cloudfront_access_key',    ''),
    ('perf_cloudfront_secret',        ''),

    -- Image CDN (M4)
    ('perf_image_cdn_status',         '0'),
    ('perf_image_cdn_url',            ''),

    -- Bot Protection (M5)
    ('perf_bot_protect_status',       '0'),
    ('perf_bot_rate_limit_per_min',   '60'),
    ('perf_bot_block_list',           "ahrefsbot\nmj12bot\nsemrushbot\ndotbot\nmegaindex"),

    -- Hotlink (M5)
    ('perf_hotlink_protect_status',   '0'),
    ('perf_hotlink_allowed_domains',  ''),

    -- Slow Query (M5)
    ('perf_slow_query_status',        '0'),
    ('perf_slow_query_threshold_ms',  '500'),

    -- AI Recommendations (M6)
    ('perf_ai_recs_status',           '1'),
    ('perf_ai_recs_auto_apply',       '0'),
    ('perf_ai_recs_auto_apply_threshold', '85'),
    ('perf_ai_recs_categories',       'lcp,cls,inp,css,js,image,cache,db,security'),

    -- Script Manager (M2)
    ('perf_script_manager_status',    '0'),

    -- Cache Rules (M3)
    ('perf_cache_rules_status',       '1');

-- Permissions for new tabs
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
    ('manage_performance_script_manager', 'web', NOW(), NOW()),
    ('manage_performance_cache_rules',    'web', NOW(), NOW()),
    ('manage_performance_edge_cdn',       'web', NOW(), NOW()),
    ('manage_performance_security_plus',  'web', NOW(), NOW()),
    ('manage_performance_ai_recs',        'web', NOW(), NOW());
```

**Run it** (one-time, manually):
```bash
php -r '
require "vendor/autoload.php"; $app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sql = file_get_contents("addons/active_ecommerce_performance_optimizer/sql/1.1.sql");
DB::unprepared($sql);
echo "1.1.sql executed\n";
\Illuminate\Support\Facades\Cache::forget("business_settings");
'
```

Also update `sql/update.sql` to be the **concatenated 1.0 + 1.1** so future installs work. (Active eCommerce installer reads `sql/update.sql` for first install; for updates it reads `sql/{newVersion}.sql` per the installer logic.)

Bump `config.json` `version` to `"1.1"`.

### 5.2 Register new routes

Edit `addons/active_ecommerce_performance_optimizer/routes/performance_optimizer.php` — add inside the existing admin group:

```php
// M2 — Script Manager
Route::controller(\App\Http\Controllers\PerformanceScriptManagerController::class)
    ->prefix('/performance-optimizer/script-manager')->name('performance_optimizer.scripts.')
    ->group(function () {
        Route::get('/',              'index')->name('index');
        Route::post('/',             'store')->name('store');
        Route::put('/{id}',          'update')->name('update');
        Route::delete('/{id}',       'destroy')->name('destroy');
        Route::post('/{id}/toggle',  'toggle')->name('toggle');
    });

// M3 — Cache Rules
Route::controller(\App\Http\Controllers\PerformanceCacheRulesController::class)
    ->prefix('/performance-optimizer/cache-rules')->name('performance_optimizer.cache_rules.')
    ->group(function () {
        Route::get('/',              'index')->name('index');
        Route::post('/',             'store')->name('store');
        Route::put('/{id}',          'update')->name('update');
        Route::delete('/{id}',       'destroy')->name('destroy');
        Route::post('/{id}/toggle',  'toggle')->name('toggle');
    });

// M4 — Edge CDN
Route::controller(\App\Http\Controllers\PerformanceEdgeCdnController::class)
    ->prefix('/performance-optimizer/edge-cdn')->name('performance_optimizer.edge.')
    ->group(function () {
        Route::get('/',                       'index')->name('index');
        Route::post('/cloudflare/save',       'saveCloudflare')->name('cloudflare.save');
        Route::post('/cloudflare/purge',      'purgeCloudflare')->name('cloudflare.purge');
        Route::post('/cloudflare/test',       'testCloudflare')->name('cloudflare.test');
        Route::post('/bunny/save',            'saveBunny')->name('bunny.save');
        Route::post('/bunny/purge',           'purgeBunny')->name('bunny.purge');
        Route::post('/cloudfront/save',       'saveCloudFront')->name('cloudfront.save');
        Route::post('/cloudfront/invalidate', 'invalidateCloudFront')->name('cloudfront.invalidate');
        Route::post('/image-cdn/save',        'saveImageCdn')->name('image_cdn.save');
    });

// M5 — Security+ (bot, hotlink, slow query)
Route::controller(\App\Http\Controllers\PerformanceSecurityPlusController::class)
    ->prefix('/performance-optimizer/security-plus')->name('performance_optimizer.secplus.')
    ->group(function () {
        Route::get('/',                  'index')->name('index');
        Route::post('/save',             'save')->name('save');
        Route::post('/slow-q/scan',      'scanSlowQueries')->name('slowq.scan');
        Route::post('/slow-q/{id}/dismiss', 'dismissSlowQuery')->name('slowq.dismiss');
    });

// M6 — AI Recommendations
Route::controller(\App\Http\Controllers\PerformanceAiController::class)
    ->prefix('/performance-optimizer/ai')->name('performance_optimizer.ai.')
    ->group(function () {
        Route::get('/',                         'index')->name('index');
        Route::post('/run',                     'run')->name('run');
        Route::post('/{id}/apply',              'apply')->name('apply');
        Route::post('/{id}/dismiss',            'dismiss')->name('dismiss');
        Route::post('/history/{id}/rollback',   'rollback')->name('rollback');
        Route::post('/save',                    'save')->name('save');
    });
```

### 5.3 Update master view tab nav

Edit `addons/active_ecommerce_performance_optimizer/views/backend/performance_optimizer/index.blade.php`. In the `$perfTabs` array, add 5 new entries (place them between the existing ones logically):

```php
$perfTabs = [
    'dashboard'  => ['Dashboard',         'las la-tachometer-alt', 'performance_optimizer.dashboard'],
    'images'     => ['Image Optimization','las la-image',          'performance_optimizer.images'],
    'cssjs'      => ['CSS / JS',          'las la-code',           'performance_optimizer.cssjs'],
    'scripts'    => ['Script Manager',    'las la-tasks',          'performance_optimizer.scripts.index'],   // NEW
    'caching'    => ['Caching',           'las la-bolt',           'performance_optimizer.cache'],
    'cache_rules'=> ['Cache Rules',       'las la-filter',         'performance_optimizer.cache_rules.index'], // NEW
    'edge'       => ['Edge / CDN',        'las la-cloud',          'performance_optimizer.edge.index'],        // NEW
    'database'   => ['Database',          'las la-database',       'performance_optimizer.database'],
    'fonts'      => ['Fonts',             'las la-font',           'performance_optimizer.fonts'],
    'monitor'    => ['System Monitor',    'las la-heartbeat',      'performance_optimizer.monitor'],
    'logs'       => ['Error Logs',        'las la-bug',            'performance_optimizer.logs'],
    'secplus'    => ['Security+',         'las la-user-shield',    'performance_optimizer.secplus.index'],     // NEW
    'security'   => ['Security Audit',    'las la-shield-alt',     'performance_optimizer.security'],
    'ai'         => ['AI Recommendations','las la-robot',          'performance_optimizer.ai.index'],          // NEW (badge with unread count)
    'vitals'     => ['Web Vitals',        'las la-chart-line',     'performance_optimizer.vitals'],
];
```

Also add an unread-count badge for the AI tab — append in the badge block:

```blade
@if($k === 'ai')
    @php $aiCount = \App\Models\PerformanceOptimizer\AiRecommendation::whereNull('applied_at')->whereNull('dismissed_at')->count(); @endphp
    @if($aiCount > 0)
        <span class="perf-tab-badge perf-tab-badge-warning">{{ $aiCount }}</span>
    @endif
@endif
```

### 5.4 Update sidebar partial

Edit `addons/active_ecommerce_performance_optimizer/views/backend/performance_optimizer/partials/sidebar_menu.blade.php` — add the 5 new `<li>` items inside the existing `<ul class="aiz-side-nav-list level-2">`. Sample for one:

```blade
<li class="aiz-side-nav-item">
    <a href="{{ route('performance_optimizer.ai.index') }}"
       class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.ai.index']) }}">
        <span class="aiz-side-nav-text">{{ translate('AI Recommendations') }}</span>
    </a>
</li>
```

Repeat for `scripts.index`, `cache_rules.index`, `edge.index`, `secplus.index`.

Then **re-paste the updated partial into the live** `resources/views/backend/inc/admin_sidenav.blade.php` (look for the existing block — replace the inner `<ul>` items).

### 5.5 Stub controllers (so routes resolve)

Create 5 stub files in `addons/active_ecommerce_performance_optimizer/controllers/` AND copy each to `app/Http/Controllers/` after edit. Stub content:

```php
<?php

namespace App\Http\Controllers;

class PerformanceScriptManagerController extends Controller
{
    public function __construct() { $this->middleware(['auth', 'admin']); }
    public function index()  { return view('backend.performance_optimizer.index', ['tab' => 'scripts']); }
    public function store()  { abort(501, 'Not implemented yet'); }
    public function update() { abort(501); }
    public function destroy(){ abort(501); }
    public function toggle() { abort(501); }
}
```

Repeat for `PerformanceCacheRulesController`, `PerformanceEdgeCdnController`, `PerformanceSecurityPlusController`, `PerformanceAiController` — `index()` returns its tab, every other method aborts 501 for now. Add `update_directory` entries for all 5 in `config.json`.

### 5.6 Stub tab views

Create 5 empty placeholder views in `views/backend/performance_optimizer/tabs/`:
- `scripts.blade.php`
- `cache_rules.blade.php`
- `edge.blade.php`
- `secplus.blade.php`
- `ai.blade.php`

Each contains:
```blade
<div class="alert alert-info">{{ translate('This feature is being built — wire up next milestone.') }}</div>
```

### 5.7 M1 acceptance criteria
- [ ] `php artisan migrate` (or manual SQL run) succeeds; 5 new tables exist
- [ ] `php artisan route:list` shows 80+ `performance_optimizer.*` routes
- [ ] Browser: `/admin/performance-optimizer` loads; all 15 tabs visible in tab bar; clicking each loads the placeholder without 500
- [ ] Sidebar shows 15 sub-items under Performance Optimizer
- [ ] No PHP errors in `storage/logs/laravel.log`

**Commit checkpoint**: tag as `perf-optimizer-v2.0-m1-scaffold`.

---

## 6. Milestone 2 — Script Manager (Module 4)

**Goal**: Per-page allow/deny/defer matrix for script tags. Output middleware applies rules.

### 6.1 Model — `app/Models/PerformanceOptimizer/ScriptRule.php`

```php
namespace App\Models\PerformanceOptimizer;
use Illuminate\Database\Eloquent\Model;

class ScriptRule extends Model
{
    protected $table = 'perf_script_rules';
    protected $fillable = ['script_pattern','route_pattern','device_filter','action','priority','enabled','note'];
    protected $casts = ['enabled' => 'boolean'];
}
```

### 6.2 Service — `app/Services/PerformanceOptimizer/ScriptManagerService.php`

```php
namespace App\Services\PerformanceOptimizer;
use App\Models\PerformanceOptimizer\ScriptRule;

class ScriptManagerService
{
    /**
     * For a given request context, return rules to apply to <script> tags.
     * Returns array of ['script_pattern' => '...', 'action' => 'deny|defer|async|delay']
     */
    public function rulesFor(string $routePath, string $device): array
    {
        if ((int) get_setting('perf_script_manager_status', 0) !== 1) return [];

        return ScriptRule::where('enabled', true)
            ->orderBy('priority')
            ->get()
            ->filter(fn ($r) => $this->routeMatches($r->route_pattern, $routePath)
                            && $this->deviceMatches($r->device_filter, $device))
            ->map(fn ($r) => [
                'script_pattern' => $r->script_pattern,
                'action'         => $r->action,
            ])
            ->values()
            ->all();
    }

    public function applyToHtml(string $html, array $rules): string
    {
        if (empty($rules)) return $html;
        return preg_replace_callback('/<script\b([^>]*)>([\s\S]*?)<\/script>/i', function ($m) use ($rules) {
            $attrs   = $m[1];
            $content = $m[2];
            $src     = '';
            if (preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) $src = $sm[2];

            foreach ($rules as $rule) {
                $needle = $rule['script_pattern'];
                if ($needle === '') continue;
                $hit = $src && stripos($src, $needle) !== false;
                if (!$hit) $hit = stripos($content, $needle) !== false;
                if (!$hit) continue;

                switch ($rule['action']) {
                    case 'deny':  return '<!-- script blocked by perf:' . htmlspecialchars($needle) . ' -->';
                    case 'defer': if (!stripos($attrs, 'defer')) return '<script defer' . $attrs . '>' . $content . '</script>'; break;
                    case 'async': if (!stripos($attrs, 'async')) return '<script async' . $attrs . '>' . $content . '</script>'; break;
                    case 'delay': $attrs .= ' data-perf-delay="1"'; return '<script type="text/perf-delay"' . $attrs . '>' . $content . '</script>';
                }
            }
            return $m[0];
        }, $html);
    }

    protected function routeMatches(string $pattern, string $path): bool
    {
        if ($pattern === '' || $pattern === '*') return true;
        return fnmatch($pattern, $path) || str_starts_with($path, $pattern);
    }

    protected function deviceMatches(string $filter, string $device): bool
    {
        return $filter === 'any' || $filter === $device;
    }
}
```

### 6.3 Hook into `PerformanceOutputMiddleware`

Add to existing `app/Http/Middleware/PerformanceOptimizer/PerformanceOutputMiddleware.php` — inside `handle()`, after `processScripts()`, add:

```php
$html = $this->processScriptManager($html, $request);
```

And the method:
```php
protected function processScriptManager(string $html, $request): string
{
    $sm = app(\App\Services\PerformanceOptimizer\ScriptManagerService::class);
    $routePath = ltrim($request->path(), '/');
    $ua = (string) $request->userAgent();
    $device = preg_match('/Mobi|Android/i', $ua) ? 'mobile' : 'desktop';
    $rules = $sm->rulesFor($routePath, $device);
    return $sm->applyToHtml($html, $rules);
}
```

### 6.4 Controller — `PerformanceScriptManagerController.php`

Implement all 5 methods (replace M1 stub). Standard Laravel CRUD: list rules in `index()`, validate + save in `store()`, etc. Pass `$rules` collection to the view.

### 6.5 View — `tabs/scripts.blade.php`

Build using the existing `perf-section` + `perf-table` styles. Layout:
- Header section: master toggle + "Add Rule" button
- Existing rules table: Script Pattern | Route | Device | Action | Priority | Enabled | Edit | Delete
- Inline create/edit form (collapsible)
- Default rules helper: "Apply 10 sensible defaults for Active eCommerce" button (jquery on checkout, slick on product pages, etc.)

### 6.6 Seed defaults

In M2's controller `index()`, if `ScriptRule::count() === 0`, optionally seed defaults via a separate "Apply Defaults" button (not auto, user-triggered):

```php
$defaults = [
    ['script_pattern' => 'firebase',     'route_pattern' => '*',         'action' => 'delay', 'note' => 'Push notifications — load on interaction'],
    ['script_pattern' => 'recaptcha',    'route_pattern' => 'checkout*', 'action' => 'allow', 'note' => 'Required for checkout'],
    ['script_pattern' => 'recaptcha',    'route_pattern' => '*',         'action' => 'deny',  'note' => 'Block recaptcha outside checkout'],
    ['script_pattern' => 'sweetalert',   'route_pattern' => '*',         'action' => 'defer'],
    ['script_pattern' => 'slick',        'route_pattern' => 'cart*',     'action' => 'deny',  'note' => 'No sliders on cart page'],
    // ... etc
];
```

### 6.7 M2 acceptance criteria
- [ ] Create, edit, delete, toggle rules via UI
- [ ] On frontend (logged-out), with master switch on + a `deny` rule for `firebase`, view source has zero `firebase` script tags
- [ ] `defer` rule adds `defer` to matching script tags
- [ ] `delay` rule converts type to `text/perf-delay` and gets executed on user interaction (extend delay-JS bootstrap)
- [ ] Default-seed button populates 10 rules

---

## 7. Milestone 3 — Cache Rules (Module 1)

**Goal**: Replace hardcoded path/cookie exclude lists with database-driven cache rules.

### 7.1 Model — `CacheRule.php`

```php
namespace App\Models\PerformanceOptimizer;
use Illuminate\Database\Eloquent\Model;

class CacheRule extends Model
{
    protected $table = 'perf_cache_rules';
    protected $fillable = ['pattern','action','ttl_minutes','priority','enabled','note'];
    protected $casts = ['enabled' => 'boolean'];
}
```

### 7.2 Update `PageCacheService::shouldCache()`

In `app/Services/PerformanceOptimizer/PageCacheService.php`, after the existing `excludedPaths()` check, add database rule check:

```php
// Database-driven cache rules
if ((int) get_setting('perf_cache_rules_status', 1) === 1) {
    $rules = CacheRule::where('enabled', true)->orderBy('priority')->get();
    foreach ($rules as $rule) {
        if (!$this->patternMatches($rule->pattern, $path)) continue;
        if ($rule->action === 'bypass') return false;
        // 'cache' → fall through (allowed)
        // Other variants handled in cacheVariant()
        break;
    }
}
```

Add helper `patternMatches()` that supports `*`, `?`, prefix match.

Also use the rule's `ttl_minutes` when storing (override the global setting if rule has one).

### 7.3 Controller + View

Same CRUD pattern as Script Manager. Table columns: Pattern | Action | TTL | Priority | Enabled | Note | Edit | Delete.

### 7.4 Seed defaults helper

When user clicks "Apply Defaults":
```php
$defaults = [
    ['pattern' => 'admin/*',       'action' => 'bypass', 'priority' => 1],
    ['pattern' => 'cart',          'action' => 'bypass', 'priority' => 5],
    ['pattern' => 'checkout/*',    'action' => 'bypass', 'priority' => 5],
    ['pattern' => 'my-account/*',  'action' => 'bypass', 'priority' => 5],
    ['pattern' => 'user/*',        'action' => 'bypass', 'priority' => 5],
    ['pattern' => 'seller/*',      'action' => 'bypass', 'priority' => 5],
    ['pattern' => '/',             'action' => 'cache',  'ttl_minutes' => 1440, 'priority' => 100],
    ['pattern' => 'product/*',     'action' => 'cache',  'ttl_minutes' => 720,  'priority' => 100],
    ['pattern' => 'category/*',    'action' => 'cache',  'ttl_minutes' => 720,  'priority' => 100],
];
```

### 7.5 M3 acceptance criteria
- [ ] CRUD works
- [ ] Adding a `bypass` rule for `/test-path/*` causes that path to return `X-Performance-Cache: BYPASS`
- [ ] Removing the rule restores caching
- [ ] Default-seed button populates 9 sensible rules

---

## 8. Milestone 4 — Cloudflare + Bunny.net integration (Module 10)

**Goal**: Working API integrations with purge buttons, auto-purge on product/category save.

### 8.1 Service — `CloudflareService.php`

```php
namespace App\Services\PerformanceOptimizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected string $apiBase = 'https://api.cloudflare.com/client/v4';

    public function isConfigured(): bool
    {
        return (int) get_setting('perf_cloudflare_status', 0) === 1
            && get_setting('perf_cloudflare_zone_id')
            && get_setting('perf_cloudflare_api_token');
    }

    protected function client()
    {
        return Http::baseUrl($this->apiBase)
            ->withToken((string) get_setting('perf_cloudflare_api_token'))
            ->acceptJson()
            ->timeout(15);
    }

    public function test(): array
    {
        if (!$this->isConfigured()) return ['ok' => false, 'error' => 'Not configured'];
        $zone = get_setting('perf_cloudflare_zone_id');
        $r = $this->client()->get("/zones/{$zone}");
        return $r->successful()
            ? ['ok' => true, 'name' => $r->json('result.name'), 'status' => $r->json('result.status')]
            : ['ok' => false, 'error' => $r->json('errors.0.message', 'Unknown')];
    }

    public function purgeAll(): array
    {
        if (!$this->isConfigured()) return ['ok' => false, 'error' => 'Not configured'];
        $zone = get_setting('perf_cloudflare_zone_id');
        $r = $this->client()->post("/zones/{$zone}/purge_cache", ['purge_everything' => true]);
        $this->log('purge_all', $r);
        return ['ok' => $r->successful(), 'response' => $r->json()];
    }

    public function purgeUrls(array $urls): array
    {
        if (!$this->isConfigured() || empty($urls)) return ['ok' => false];
        $zone = get_setting('perf_cloudflare_zone_id');
        $r = $this->client()->post("/zones/{$zone}/purge_cache", ['files' => array_values($urls)]);
        $this->log('purge_urls', $r, ['urls' => $urls]);
        return ['ok' => $r->successful()];
    }

    protected function log(string $action, $response, array $meta = []): void
    {
        \App\Models\PerformanceOptimizer\OptimizationLog::create([
            'type'   => 'cloudflare',
            'action' => $action,
            'status' => $response->successful() ? 'success' : 'failed',
            'meta'   => array_merge($meta, ['status_code' => $response->status()]),
            'error_message' => $response->successful() ? null : (string) $response->body(),
        ]);
    }
}
```

### 8.2 Service — `BunnyCdnService.php`

Similar pattern. Uses `https://api.bunny.net/pullzone/{id}/purgeCache` with header `AccessKey: {api_key}`.

### 8.3 Service — `CloudFrontService.php`

Uses AWS SDK (`composer require aws/aws-sdk-php` — pre-existing in vendor for Active eCommerce S3). Create invalidation:

```php
$client->createInvalidation([
    'DistributionId' => get_setting('perf_cloudfront_distribution'),
    'InvalidationBatch' => [
        'Paths' => ['Quantity' => 1, 'Items' => ['/*']],
        'CallerReference' => (string) time(),
    ],
]);
```

### 8.4 Auto-purge on model save

Create `app/Providers/PerformanceOptimizerEventServiceProvider.php`:

```php
namespace App\Providers;
use App\Models\Product;
use App\Models\Category;
use App\Services\PerformanceOptimizer\CloudflareService;
use App\Services\PerformanceOptimizer\BunnyCdnService;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Support\ServiceProvider;

class PerformanceOptimizerEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!function_exists('addon_is_activated') || !addon_is_activated('performance_optimizer')) return;

        $purge = function ($model) {
            // Local page cache
            try { app(PageCacheService::class)->clearAll(); } catch (\Throwable $e) {}

            // Edge purge
            if ((int) get_setting('perf_cloudflare_auto_purge', 1) === 1) {
                try { app(CloudflareService::class)->purgeAll(); } catch (\Throwable $e) {}
            }
            try { app(BunnyCdnService::class)->purgeAll(); } catch (\Throwable $e) {}
        };

        Product::saved($purge);
        Product::deleted($purge);
        Category::saved($purge);
        Category::deleted($purge);
    }
}
```

Register in `config/app.php` providers array (or merge into existing `AppServiceProvider` if cleaner).

### 8.5 Controller — `PerformanceEdgeCdnController.php`

8 methods: `index`, `saveCloudflare`, `purgeCloudflare`, `testCloudflare`, `saveBunny`, `purgeBunny`, `saveCloudFront`, `invalidateCloudFront`, `saveImageCdn`.

### 8.6 View — `tabs/edge.blade.php`

3-card layout (Cloudflare / Bunny / CloudFront) + Image CDN section at bottom. Each provider card has:
- Toggle (enable/disable)
- Settings form (zone/token/API key)
- "Test connection" button (returns zone name on success)
- "Purge all" button
- "Auto-purge on product/category save" toggle

### 8.7 M4 acceptance criteria
- [ ] Cloudflare test button returns real zone name when correct credentials entered
- [ ] Purge button returns success and logs to `optimization_logs`
- [ ] Saving a product in admin triggers auto-purge (verify via log entry)
- [ ] Bunny.net purge works against a real test zone
- [ ] Image CDN URL prefix rewrite works in output middleware (verify in browser View Source)

---

## 9. Milestone 5 — Slow Query Analyzer + Bot Protection + Hotlink (Module 9 + 14)

### 9.1 Service — `SlowQueryAnalyzerService.php`

```php
namespace App\Services\PerformanceOptimizer;
use App\Models\PerformanceOptimizer\SlowQuery;
use Illuminate\Support\Facades\DB;

class SlowQueryAnalyzerService
{
    public function enableQueryLog(): void
    {
        DB::listen(function ($query) {
            if ($query->time < (int) get_setting('perf_slow_query_threshold_ms', 500)) return;
            $this->record($query->sql, $query->bindings, $query->time);
        });
    }

    public function record(string $sql, array $bindings, float $timeMs): void
    {
        $normalized = $this->normalize($sql);
        $hash = md5($normalized);
        $existing = SlowQuery::where('query_hash', $hash)->first();

        if ($existing) {
            $existing->update([
                'avg_time_ms'  => (int) (($existing->avg_time_ms * $existing->occurrences + $timeMs) / ($existing->occurrences + 1)),
                'max_time_ms'  => max($existing->max_time_ms, (int) $timeMs),
                'occurrences'  => $existing->occurrences + 1,
                'last_seen'    => now(),
            ]);
        } else {
            try { $explain = DB::select("EXPLAIN " . $sql, $bindings); }
            catch (\Throwable $e) { $explain = []; }

            $suggested = $this->suggestIndex($explain);

            SlowQuery::create([
                'query_hash'      => $hash,
                'query_text'      => $sql,
                'avg_time_ms'     => (int) $timeMs,
                'max_time_ms'     => (int) $timeMs,
                'occurrences'     => 1,
                'explain_result'  => $explain,
                'suggested_index' => $suggested,
                'last_seen'       => now(),
                'first_seen'      => now(),
            ]);
        }
    }

    protected function normalize(string $sql): string
    {
        return preg_replace(['/\d+/', '/\'[^\']*\'/'], ['?', '?'], $sql);
    }

    protected function suggestIndex(array $explain): ?string
    {
        foreach ($explain as $row) {
            $r = (array) $row;
            if (($r['type'] ?? '') === 'ALL' && !empty($r['table'])) {
                return "Consider adding an index to `{$r['table']}` covering the WHERE columns (rows scanned: " . ($r['rows'] ?? '?') . ")";
            }
        }
        return null;
    }
}
```

Wire `enableQueryLog()` into `PerformanceOptimizerEventServiceProvider::boot()` if `perf_slow_query_status === 1`.

### 9.2 Middleware — `BotProtectionMiddleware.php`

```php
namespace App\Http\Middleware\PerformanceOptimizer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class BotProtectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ((int) get_setting('perf_bot_protect_status', 0) !== 1) return $next($request);

        $ua = strtolower((string) $request->userAgent());
        $blockList = array_filter(array_map('trim', explode("\n", (string) get_setting('perf_bot_block_list', ''))));
        foreach ($blockList as $needle) {
            if ($needle !== '' && str_contains($ua, strtolower($needle))) {
                abort(403, 'Blocked');
            }
        }

        $perMin = (int) get_setting('perf_bot_rate_limit_per_min', 60);
        if ($perMin > 0) {
            $key = 'perf_bot_' . md5($request->ip() . $ua);
            if (RateLimiter::tooManyAttempts($key, $perMin)) {
                abort(429, 'Too Many Requests');
            }
            RateLimiter::hit($key, 60);
        }

        return $next($request);
    }
}
```

### 9.3 Middleware — `HotlinkProtectionMiddleware.php`

Only acts on requests to `/uploads/*` / `/images/*`. If `Referer` header is not empty AND not from one of the allowed domains, return 403 (or 1x1 transparent gif).

```php
public function handle(Request $request, Closure $next)
{
    if ((int) get_setting('perf_hotlink_protect_status', 0) !== 1) return $next($request);
    $path = $request->path();
    if (!str_starts_with($path, 'uploads/') && !str_starts_with($path, 'images/')) return $next($request);

    $referer = $request->header('Referer', '');
    if (!$referer) return $next($request);  // Direct access OK

    $allowed = array_filter(array_map('trim', explode("\n", (string) get_setting('perf_hotlink_allowed_domains', ''))));
    $appHost = parse_url(config('app.url'), PHP_URL_HOST);
    $allowed[] = $appHost;
    $refererHost = parse_url($referer, PHP_URL_HOST);

    foreach ($allowed as $a) {
        if ($a !== '' && (str_contains($refererHost, $a) || str_contains($a, $refererHost))) {
            return $next($request);
        }
    }
    abort(403);
}
```

### 9.4 Controller + View

Single tab `secplus.blade.php` with 3 sections (Bot / Hotlink / Slow Queries). Slow queries section shows table with sort by `avg_time_ms DESC`, columns: Query (truncated, expandable) | Avg ms | Max ms | Occurrences | Last seen | Suggested index | Dismiss.

### 9.5 Cron command

`PerfSlowQueryScanCommand.php` — runs daily, parses any external `mysql-slow.log` if configured, deduplicates with DB rows.

### 9.6 M5 acceptance criteria
- [ ] Enabling bot protection and adding `curl` to block list returns 403 to `curl -A curl localhost`
- [ ] Rate limit returns 429 after exceeding `perf_bot_rate_limit_per_min`
- [ ] Slow query analyzer captures real slow queries from the app
- [ ] Hotlink protection blocks image requests with foreign referer

---

## 10. Milestone 6 — AI Recommendations engine (rule-based) (Module 7 + 12 + 16)

**Goal**: Real rule-based recommendation engine (no OpenAI yet) that analyzes RUM data, image stats, DB stats and produces concrete recommendations with optional auto-fix.

### 10.1 Models

```php
// app/Models/PerformanceOptimizer/AiRecommendation.php
class AiRecommendation extends Model
{
    protected $table = 'perf_ai_recommendations';
    protected $fillable = ['category','severity','title','body','evidence','confidence','auto_fixable','auto_fix_action','auto_fix_payload','source','applied_at','dismissed_at'];
    protected $casts = ['evidence' => 'array', 'auto_fix_payload' => 'array', 'auto_fixable' => 'boolean'];
}

// app/Models/PerformanceOptimizer/AutoFixHistory.php
class AutoFixHistory extends Model { /* similar */ }
```

### 10.2 Engine — `app/AddonAi/PerformanceOptimizer/Engine/RecommendationEngine.php`

```php
namespace App\AddonAi\PerformanceOptimizer\Engine;

use App\Models\PerformanceOptimizer\AiRecommendation;
use App\Models\PerformanceOptimizer\WebVital;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use App\Services\PerformanceOptimizer\PageCacheService;

class RecommendationEngine
{
    public function run(): int
    {
        $generated = 0;
        $generated += $this->analyzeVitals();
        $generated += $this->analyzeImages();
        $generated += $this->analyzeDatabase();
        $generated += $this->analyzeCache();
        $generated += $this->analyzeSecurity();
        $generated += $this->analyzeCssJs();
        return $generated;
    }

    protected function analyzeVitals(): int
    {
        $count = 0;
        $thresholds = [
            'LCP' => ['poor' => 4000, 'severity' => 'high'],
            'INP' => ['poor' => 500,  'severity' => 'high'],
            'CLS' => ['poor' => 0.25, 'severity' => 'medium'],
        ];

        foreach ($thresholds as $metric => $t) {
            $rows = WebVital::where('metric', $metric)
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('value')->pluck('value')->all();
            if (count($rows) < 10) continue;
            $p75 = $rows[(int) floor((count($rows) - 1) * 0.75)];
            if ($p75 < $t['poor']) continue;

            $title = "P75 {$metric} is {$p75}" . ($metric === 'CLS' ? '' : 'ms') . " — above the 'poor' threshold";
            if ($this->exists('vitals', $title)) continue;

            $body = match ($metric) {
                'LCP' => "Largest Contentful Paint at the 75th percentile exceeds " . $t['poor'] . "ms. Common causes: large hero image (>200KB), render-blocking CSS, slow TTFB. Suggested fixes: convert hero image to WebP/AVIF, preload it with fetchpriority=\"high\", inline critical CSS.",
                'INP' => "Interaction to Next Paint at P75 is above 500ms — clicks feel laggy. Likely cause: heavy JavaScript on main thread. Suggested fixes: defer/delay non-critical JS, use the Script Manager to disable analytics on product pages.",
                'CLS' => "Cumulative Layout Shift at P75 > 0.25 — content jumps as page loads. Cause: images without width/height. Suggested fix: run CSS/JS → Auto-Fix Dimensions.",
            };

            AiRecommendation::create([
                'category'       => 'vitals',
                'severity'       => $t['severity'],
                'title'          => $title,
                'body'           => $body,
                'evidence'       => ['p75' => $p75, 'sample_count' => count($rows)],
                'confidence'     => 95,
                'auto_fixable'   => $metric === 'CLS',
                'auto_fix_action'=> $metric === 'CLS' ? 'autoFixImageDimensions' : null,
                'source'         => 'rule_engine',
            ]);
            $count++;
        }
        return $count;
    }

    protected function analyzeImages(): int
    {
        $count = 0;
        $stats = app(ImageOptimizerService::class)->getStats();
        if ($stats['not_converted'] > 100 && !$this->exists('image', 'Convert ' . $stats['not_converted'] . ' more images to WebP')) {
            AiRecommendation::create([
                'category'        => 'image',
                'severity'        => 'medium',
                'title'           => "Convert {$stats['not_converted']} more images to WebP",
                'body'            => "{$stats['not_converted']} images are still in JPG/PNG. Converting saves ~30% bandwidth and improves LCP. Estimated savings based on current ratio: " . $this->estimateSavings($stats),
                'evidence'        => $stats,
                'confidence'      => 100,
                'auto_fixable'    => true,
                'auto_fix_action' => 'convertImagesWebp',
                'auto_fix_payload'=> ['limit' => min(500, $stats['not_converted'])],
                'source'          => 'rule_engine',
            ]);
            $count++;
        }
        return $count;
    }

    protected function analyzeDatabase(): int { /* old sessions > 10k, abandoned carts > 5k → suggest cleanup */ }
    protected function analyzeCache(): int    { /* page cache pages=0 + status=1 → suggest warm */ }
    protected function analyzeSecurity(): int { /* APP_DEBUG=true → critical recommendation */ }
    protected function analyzeCssJs(): int    { /* minify status=0 → suggest enable */ }

    protected function exists(string $category, string $title): bool
    {
        return AiRecommendation::where('category', $category)
            ->where('title', $title)
            ->whereNull('applied_at')
            ->whereNull('dismissed_at')
            ->exists();
    }

    protected function estimateSavings(array $stats): string
    {
        // ... compute estimated MB savings ...
        return 'approx 250 MB';
    }
}
```

### 10.3 Engine — `AutoFixEngine.php`

```php
namespace App\AddonAi\PerformanceOptimizer\Engine;

class AutoFixEngine
{
    public function apply(AiRecommendation $rec, ?int $userId = null): array
    {
        if (!$rec->auto_fixable) return ['ok' => false, 'error' => 'Not auto-fixable'];

        $before = $this->snapshotBefore($rec);
        try {
            $result = $this->executeAction($rec->auto_fix_action, $rec->auto_fix_payload ?? []);
            $after  = $this->snapshotAfter($rec, $result);

            AutoFixHistory::create([
                'recommendation_id' => $rec->id,
                'action'            => $rec->auto_fix_action,
                'before_state'      => $before,
                'after_state'       => $after,
                'applied_at'        => now(),
                'user_id'           => $userId,
            ]);

            $rec->update(['applied_at' => now()]);
            return ['ok' => true, 'result' => $result];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function rollback(AutoFixHistory $h): array
    {
        // For each action type, reverse the operation
        // For now, only image-conversion has a true rollback (restore from backup)
        // For setting changes, restore the before_state values
        $action = $h->action;
        switch ($action) {
            case 'convertImagesWebp':
                // Hard to rollback bulk — recommend "Restore All Originals" button
                return ['ok' => false, 'error' => 'Use Images → Restore All Originals'];
            case 'setSettings':
                foreach (($h->before_state['settings'] ?? []) as $k => $v) {
                    \App\Models\BusinessSetting::updateOrCreate(['type' => $k], ['value' => $v]);
                }
                $h->update(['rolled_back_at' => now()]);
                return ['ok' => true];
            default:
                return ['ok' => false, 'error' => 'No rollback for action: ' . $action];
        }
    }

    protected function executeAction(string $action, array $payload): array
    {
        return match ($action) {
            'convertImagesWebp'      => app(\App\Services\PerformanceOptimizer\ImageOptimizerService::class)->convertAllToWebp(null, $payload['limit'] ?? 100),
            'autoFixImageDimensions' => app(\App\Services\PerformanceOptimizer\CssJsMinifierService::class)->autoFixDimensions(),
            'warmCache'              => app(\App\Services\PerformanceOptimizer\PageCacheService::class)->warmFromSitemap(),
            'setSettings'            => $this->setSettings($payload),
            default => throw new \RuntimeException('Unknown auto-fix action: ' . $action),
        };
    }
}
```

### 10.4 Cron command — `PerfRunAiAnalysisCommand.php`

```php
class PerfRunAiAnalysisCommand extends Command
{
    protected $signature   = 'perf:ai-analysis {--auto-apply}';
    protected $description = 'Generate AI recommendations from RUM data and optionally auto-apply';

    public function handle(\App\AddonAi\PerformanceOptimizer\Engine\RecommendationEngine $engine,
                          \App\AddonAi\PerformanceOptimizer\Engine\AutoFixEngine $fixer): int
    {
        if ((int) get_setting('perf_ai_recs_status', 1) !== 1) return self::SUCCESS;
        $count = $engine->run();
        $this->info("Generated {$count} new recommendations.");

        if ($this->option('auto-apply') && (int) get_setting('perf_ai_recs_auto_apply', 0) === 1) {
            $threshold = (int) get_setting('perf_ai_recs_auto_apply_threshold', 85);
            $applied = 0;
            \App\Models\PerformanceOptimizer\AiRecommendation::where('auto_fixable', true)
                ->where('confidence', '>=', $threshold)
                ->whereNull('applied_at')->whereNull('dismissed_at')
                ->get()
                ->each(function ($r) use ($fixer, &$applied) {
                    if (($fixer->apply($r)['ok'] ?? false)) $applied++;
                });
            $this->info("Auto-applied {$applied} fixes.");
        }
        return self::SUCCESS;
    }
}
```

### 10.5 Controller + View

`PerformanceAiController.php`:
- `index()` — list recommendations grouped by status (pending / applied / dismissed)
- `run()` — invoke `RecommendationEngine::run()`, redirect back with flash
- `apply($id)` — invoke `AutoFixEngine::apply()`
- `dismiss($id)` — set `dismissed_at`
- `rollback($historyId)` — invoke `AutoFixEngine::rollback()`

`tabs/ai.blade.php`:
- Top: 4 stat cards (Pending / Applied This Month / Auto-Fixed / Dismissed)
- Filters: severity + category dropdown
- Table of pending recommendations with Apply / Dismiss buttons
- "Run Analysis Now" button (calls `run()`)
- Auto-Fix History accordion (collapsed by default)
- Settings panel (right sidebar): auto-apply toggle + threshold slider + run frequency

### 10.6 OpenAI provider stub (for future)

Create `app/AddonAi/PerformanceOptimizer/Provider/AiProviderInterface.php`:
```php
interface AiProviderInterface
{
    public function complete(string $systemPrompt, string $userPrompt, array $context = []): array;
    public function isConfigured(): bool;
}
```

Create stub `OpenAiProvider.php` that throws `RuntimeException('Not configured — add perf_ai_api_key in Settings')` so future integration is a drop-in.

### 10.7 M6 acceptance criteria
- [ ] Running `php artisan perf:ai-analysis` generates real recommendations from current site state
- [ ] Apply button on a "Convert images to WebP" recommendation actually converts and logs to `perf_auto_fix_history`
- [ ] Dismiss button hides the recommendation
- [ ] AI tab shows badge count of pending recommendations
- [ ] Tab bar AI badge updates after applying/dismissing

---

## 11. Cross-cutting concerns

### 11.1 Config.json updates

After all milestones, `config.json` must include the new file mappings (controllers, services, models, middleware, jobs, commands) and bump `version` to `"1.1"`.

### 11.2 ZIP rebuild

After implementation, rebuild the ZIP:
```bash
php -r '
$srcDir = "d:/wamp/www/bhssupplies1/addons/active_ecommerce_performance_optimizer";
$zipPath = "d:/wamp/www/bhssupplies1/addons/active_ecommerce_performance_optimizer.zip";
$prefix = "active_ecommerce_performance_optimizer/";
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addEmptyDir(rtrim($prefix, "/"));
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $f) {
    $rel = str_replace("\\", "/", substr($f->getRealPath(), strlen($srcDir) + 1));
    $f->isDir() ? $zip->addEmptyDir($prefix . $rel) : $zip->addFile($f->getRealPath(), $prefix . $rel);
}
$zip->close();
'
```

### 11.3 Live file sync

After editing addon source, sync to live install paths:
```bash
SRC="d:/wamp/www/bhssupplies1/addons/active_ecommerce_performance_optimizer"
DST="d:/wamp/www/bhssupplies1"
cp -r "$SRC/views/backend/performance_optimizer/"* "$DST/resources/views/backend/performance_optimizer/"
cp -r "$SRC/public/assets/performance_optimizer/"* "$DST/public/assets/performance_optimizer/"
cp $SRC/controllers/*.php $DST/app/Http/Controllers/
cp $SRC/Services/*.php $DST/app/Services/PerformanceOptimizer/
cp $SRC/Models/*.php $DST/app/Models/PerformanceOptimizer/
cp $SRC/Middleware/*.php $DST/app/Http/Middleware/PerformanceOptimizer/
cp $SRC/Jobs/*.php $DST/app/Jobs/PerformanceOptimizer/
cp $SRC/Commands/*.php $DST/app/Console/Commands/PerformanceOptimizer/
mkdir -p $DST/app/AddonAi/PerformanceOptimizer/Engine $DST/app/AddonAi/PerformanceOptimizer/Provider $DST/app/AddonAi/PerformanceOptimizer/PromptLibrary
cp -r $SRC/AddonAi/* $DST/app/AddonAi/PerformanceOptimizer/
cp $SRC/routes/performance_optimizer.php $DST/routes/performance_optimizer.php
php artisan view:clear && php artisan cache:clear && php artisan route:clear
```

### 11.4 Permissions grant

After M1 SQL run, grant new permissions to admin roles:
```php
foreach (['manage_performance_script_manager','manage_performance_cache_rules','manage_performance_edge_cdn','manage_performance_security_plus','manage_performance_ai_recs'] as $perm) {
    $p = DB::table('permissions')->where('name', $perm)->first();
    foreach ([1, 3] as $roleId) {  // Super Admin, Admin
        DB::table('role_has_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $p->id], []);
    }
}
```

### 11.5 RouteServiceProvider

Already set up in v1.0 — `mapPerformanceOptimizerRoutes()` covers the new routes since they're appended to the same routes file. No additional work needed.

### 11.6 Middleware registration

The 2 new middlewares from M5 (Bot, Hotlink) need to be in the global `Kernel.php` `$middleware` array:
```php
\App\Http\Middleware\PerformanceOptimizer\BotProtectionMiddleware::class,
\App\Http\Middleware\PerformanceOptimizer\HotlinkProtectionMiddleware::class,
```

Document this in `INSTALL_HOOKS.md`.

---

## 12. Testing checklist

After all milestones complete, run this end-to-end smoke test:

| Test | Steps | Expected |
|---|---|---|
| **T1**: Master switch | Click "Emergency Disable All" → check frontend response headers | `X-Performance-Cache` absent, no `loading="lazy"` injection |
| **T2**: Script Manager deny | Add rule `script_pattern=jquery, route=*, action=deny` → frontend view source | No `<script src="...jquery...">` tags |
| **T3**: Cache Rules bypass | Add rule `pattern=/test-bypass/*, action=bypass` → curl that path | Response has `X-Performance-Cache: BYPASS` |
| **T4**: Cloudflare test | Edge → Cloudflare → enter real Zone ID + token → Test | Returns real zone name |
| **T5**: Cloudflare purge | Click "Purge All" → check `optimization_logs` | Row with `type=cloudflare, action=purge_all, status=success` |
| **T6**: Auto-purge | Edit a product in admin and save → check logs | New `cloudflare purge_all` log entry |
| **T7**: Bot protection | `curl -A "ahrefsbot" http://...` | 403 Forbidden |
| **T8**: Hotlink | `curl -H "Referer: https://evil.com" http://.../uploads/test.jpg` | 403 Forbidden |
| **T9**: Slow query | Run a known slow query in tinker (`User::all()->each(fn($u) => $u->orders)`) → check Security+ tab | Slow query row visible |
| **T10**: AI Recommendations | Run `php artisan perf:ai-analysis` → AI tab | At least 3 recommendations visible |
| **T11**: Auto-apply | Click "Apply" on a "Convert images" recommendation → check Images tab | Stats reflect new conversion |
| **T12**: Rollback | Click rollback on a setting-change history entry | Setting reverts |

---

## 13. Definition of Done

v2.0 is shipped when:

- [ ] All 5 new DB tables exist
- [ ] All 5 new tabs render without 500
- [ ] All 5 new tabs in sidebar partial + live `admin_sidenav.blade.php`
- [ ] Script Manager: CRUD works, defaults seed works, rule application visible in frontend HTML
- [ ] Cache Rules: CRUD works, defaults seed works, `X-Performance-Cache` reflects rule
- [ ] Cloudflare: test/purge/auto-purge all green
- [ ] Bot Protection: 403 returned to UA in block list
- [ ] Hotlink: 403 returned to foreign referer for `/uploads/*`
- [ ] Slow Query: at least 1 captured + EXPLAIN result stored
- [ ] AI Engine: at least 6 different rule generators implemented (vitals, images, db, cache, security, css/js)
- [ ] Auto-Fix: at least 3 action types working (convertImagesWebp, autoFixImageDimensions, warmCache)
- [ ] Rollback: works for `setSettings` action
- [ ] `INSTALL_HOOKS.md` updated with the 2 new middlewares
- [ ] `README.md` updated with new feature list
- [ ] `config.json` bumped to v1.1, all new files listed
- [ ] `sql/update.sql` updated (now contains both 1.0 + 1.1 statements)
- [ ] ZIP rebuilt and tested with fresh install on a copy DB
- [ ] All 12 tests in section 12 pass
- [ ] No PHP errors in `storage/logs/laravel.log` during smoke test

---

## 14. Rollback plan

If v2.0 breaks production:

### 14.1 Immediate (no DB loss)
1. Click "Emergency Disable All" in admin header (sets `perf_status=0` — addon goes dormant)
2. Deactivate addon at `/admin/addons` (sets `addons.activated=0` — middleware returns 404 on admin routes)

### 14.2 Full revert (preserves v1.0 data)
1. Restore v1.0 ZIP from backup: `addons/active_ecommerce_performance_optimizer-v1.0.zip`
2. Re-upload via admin (overwrites files)
3. The 5 new v2.0 tables are harmless to leave in place — they just won't be read
4. Optionally drop v2.0 tables:
   ```sql
   DROP TABLE IF EXISTS perf_script_rules, perf_cache_rules, perf_slow_queries, perf_ai_recommendations, perf_auto_fix_history;
   DELETE FROM business_settings WHERE type LIKE 'perf_cloudflare%' OR type LIKE 'perf_bunny%' OR type LIKE 'perf_cloudfront%' OR type LIKE 'perf_bot_%' OR type LIKE 'perf_hotlink_%' OR type LIKE 'perf_slow_query%' OR type LIKE 'perf_ai_%' OR type LIKE 'perf_script_manager%' OR type LIKE 'perf_cache_rules%' OR type LIKE 'perf_image_cdn%';
   ```

### 14.3 Per-feature toggle
Each new feature can be turned off independently via its master switch:
- `perf_script_manager_status = 0`
- `perf_cache_rules_status = 0`
- `perf_cloudflare_status = 0`, `perf_bunny_status = 0`
- `perf_bot_protect_status = 0`
- `perf_hotlink_protect_status = 0`
- `perf_slow_query_status = 0`
- `perf_ai_recs_status = 0`

---

## Appendix A — Module deep dives (for reference)

Originally these modules were planned for separate phases. They are referenced throughout the doc as M1-M16. Quick reference:

| # | Name | Phase | v2.0 coverage |
|---|---|---|---|
| M1 | Smart Caching Engine | 1+3 | ✅ v1.0 base + v2.0 Cache Rules |
| M2 | HTML Optimization | 3+4 | future |
| M3 | Advanced CSS | 1+3 | ✅ v1.0 minify; Critical CSS auto-extract = future |
| M4 | Advanced JS | 1+3 | ✅ v1.0 minify + v2.0 Script Manager |
| M5 | AI Image Optimization | 2+4 | ✅ v1.0 batch WebP; AI quality tune = future |
| M6 | Smart Lazy Loading | 1+3 | ✅ v1.0 images; video/iframe lazy = future |
| M7 | Core Web Vitals AI | 2+4 | ✅ v1.0 RUM + v2.0 rule engine recommendations |
| M8 | AI Preload & Prefetch | 3+4 | future |
| M9 | Database Optimization | 2+3 | ✅ v1.0 cleanup + v2.0 slow query analyzer |
| M10 | CDN & Edge | 3 | ✅ v2.0 Cloudflare/Bunny/CloudFront |
| M11 | AI Ecommerce Optimizer | 4 | future |
| M12 | AI Auto-Fix Engine | 4 | ✅ v2.0 rule-based + rollback |
| M13 | AI Analytics Dashboard | 5 | future |
| M14 | Security | 1+3 | ✅ v1.0 audit + v2.0 Bot/Hotlink |
| M15 | Server Optimization | 1+3 | ✅ v1.0 monitor; config wizard = future |
| M16 | AI Learning System | 4+5 | partial — rule engine in v2.0, OpenAI provider stub ready |

---

## Appendix B — Future phases (out of this session's scope)

After v2.0 ships, the next session can pick from:

### Phase 4 — Add OpenAI to the AI engine
- Implement `OpenAiProvider::complete()` against existing `AiProviderInterface`
- Add prompt templates in `app/AddonAi/PerformanceOptimizer/PromptLibrary/`
- Add settings: `perf_ai_provider`, `perf_ai_api_key`, `perf_ai_model`
- Cost guardrails: per-category token budget, response cache

### Phase 4 — Critical CSS auto-extraction
- Server-side Node.js + `critical` npm package
- Queue job `ExtractCriticalCssJob`
- Per-route critical CSS storage in `public/assets/performance_optimizer/critical/`

### Phase 4 — Responsive image generator
- Generate 480/768/1200/1920 widths on upload
- Inject `srcset` in HTML output

### Phase 4 — Per-route Web Vitals breakdown
- Pivot table: route × metric × P75
- Drill-down per route

### Phase 5 — Octane / Reverb / Horizon
- Performance: 10× throughput
- Realtime stats updates

### Phase 5 — GA / GSC / PSI integrations
- OAuth flows
- Daily snapshot cron

### Phase 5 — Multi-tenant
- Per-store config in marketplace seller dashboard

---

## Final notes for the implementer

1. **Match existing conventions** — check how `support_board` and `backup_restore` addons are structured. Use the same `controllers/` (lowercase) folder pattern, same `BusinessSetting::firstOrNew(['type' => ...])` pattern for settings, same `flash(translate(...))->success()` pattern for flash messages.

2. **Use `addon_is_activated('performance_optimizer')`** everywhere — the AddonActiveMiddleware already gates admin routes, but auto-purge listeners and middleware in global Kernel need to check it themselves.

3. **DEMO_MODE check** — every destructive controller action must respect `env('DEMO_MODE') == 'On'` (returns flash error). Existing v1.0 controllers already do this; copy the pattern.

4. **Don't break the page cache** — when adding new middlewares to the `web` group, place them AFTER `PageCacheMiddleware` so HIT responses skip the rest of the stack.

5. **Test with cache cleared** — after every change, run `php artisan view:clear && php artisan cache:clear` before testing in browser.

6. **Branch + commit per milestone** — `git checkout -b perf-v2.0-m2-script-manager`, commit when M2 acceptance criteria all green, merge back, branch for M3, etc.

7. **Update this doc** — when actually implemented, change "Goal" to "Status: shipped" and check off items.

---

**End of implementation plan.**

Last updated: 2026-05-16 · v2 of plan (implementation-ready) · written against v1.0 of the addon, targets v2.0.

For the original vision (16 modules, 5 phases, full AI roadmap), see [Appendix A](#appendix-a--module-deep-dives-for-reference) and the user's `SEO_SPEED_PHASE3_PLAN.md` reference document.
