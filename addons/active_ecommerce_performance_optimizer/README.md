# Performance Optimizer Addon — Active eCommerce CMS

Advanced site-speed, caching, security and AI-assisted operations addon for **Active eCommerce
CMS** (Laravel 10+, PHP 8.2+). Inspired by the Sevenopal performance-optimizer plugin for Botble;
rebuilt to fit Active eCommerce conventions (BusinessSetting, `addon_is_activated`, AIZ admin UI).

Current version: **1.1** (`config.json`). 15 admin tabs, 7 background services beyond the core
optimizer, 6 request middlewares, 2 scheduled artisan commands, and a rule-based AI recommendation
engine — all gated behind their own on/off toggle, so nothing activates just by installing the addon.

## Features

### Core optimization (v1.0)

| Tab            | What it does                                                                                  |
|----------------|------------------------------------------------------------------------------------------------|
| **Dashboard**  | Live stats (images, page cache, DB, recent activity) + quick-action buttons.                  |
| **Images**     | Batch WebP / AVIF conversion (sync or queued), originals auto-backed up, single-file restore, lazy-load toggle. |
| **CSS / JS**   | Bulk minify (`*.min.css` / `*.min.js`), defer / delay JS, critical-CSS, auto-fix `<img>` width/height (CLS scan & fix). |
| **Page Cache** | Full-page HTML cache (file or Redis driver), TTL, path & cookie excludes, sitemap warmer, combined purge (Page Cache + LiteSpeed + Cloudflare in one click), LiteSpeed cache purge/tag-purge, OPcache stats + flush. |
| **Database**   | Targeted cleanup of sessions / failed jobs / expired tokens / abandoned carts / OTPs + `OPTIMIZE TABLE`, nightly auto-clean via `perf:auto-clean`. |
| **Fonts**      | Preload critical fonts, force `font-display: swap`.                                           |
| **Web Vitals** | LCP / FID / CLS / INP / FCP / TTFB collector with P50/P75/P95 rollups (PerformanceObserver, CSRF-exempt beacon endpoint). |
| **Security**   | 14-point audit (APP_DEBUG, HTTPS, .env exposure, weak admin pw, etc.) + score.                |
| **Logs**       | Optimization activity log + tailed Laravel error log (`storage/logs/laravel.log`).            |
| **Monitor**    | PHP/Laravel/MySQL/OPcache/disk/memory/extension snapshot.                                     |

### Advanced modules (v1.1)

| Tab                | What it does |
|--------------------|--------------|
| **AI Recommendations** | Rule-based (no external LLM call) analysis engine that scans Web Vitals, images, database bloat, cache state, security posture and CSS/JS settings, then emits scored, deduplicated recommendations (severity + confidence). One-click **Apply** runs an auto-fix action (convert images to WebP, fix image dimensions, warm cache, clean database, or change settings); fixes are logged to an **Auto-Fix History** with **Rollback** where reversible (settings changes and cache warms — image conversions/dimension fixes/DB cleanup cannot be rolled back). Can auto-apply fixes above a confidence threshold on a schedule. |
| **Cache Rules**    | Database-driven rule table for the page cache: per-path glob pattern → `cache` / `bypass` / `vary_device` / `vary_locale`, with per-rule TTL and priority (first match wins). "Apply Defaults" seeds 11 sensible rules (bypass admin/cart/checkout/account; cache homepage/product/category/shop). Replaces/extends the static Page Cache excludes for finer control. |
| **Script Manager** | Per-route, per-device rule matrix for `<script>` tags — match by pattern (src or inline body) and route glob, filter by device (any/mobile/desktop/tablet), and `allow` / `deny` / `defer` / `async` / `delay`. Core scripts (jQuery, vendors.js, aiz-core.js) are hard-protected and can never be blocked. "Apply Defaults" seeds 7 rules (delay Firebase, gate reCAPTCHA/Slick to relevant routes, defer SweetAlert). |
| **Edge / CDN**     | **Cloudflare** — zone purge-all / test connection, optional auto-purge on product & category save. **Bunny.net** — pull-zone purge-all. **AWS CloudFront** — path invalidation (requires `aws/aws-sdk-php`, feature auto-disables in the UI if the SDK isn't installed). **Image CDN** — rewrite `/uploads/*` image URLs to a CDN origin at output time. All actions logged to the activity log. |
| **Security Plus**  | **Bot Protection** — user-agent block list (case-insensitive substring match → 403) plus a sliding per-IP+UA rate limit (→ 429). **Hotlink Protection** — blocks cross-site `Referer` requests to `/uploads/*` and `/images/*` (allowed-domain list, subdomains match automatically, no-referer requests always pass). **Slow Query Analyzer** — hooks `DB::listen`, normalizes/dedupes queries over a configurable threshold, runs `EXPLAIN` and flags full table scans with a suggested index. |

## Install (Zip method — recommended)

1. Zip the **`active_ecommerce_performance_optimizer`** folder (preserve folder name as root inside the ZIP).
2. **Admin → Addons → +Add New**, paste any purchase code (e.g. `BHSpayment`), submit.
3. The installer (`AddonController@store`) will:
   - Copy files per `config.json`.
   - Run `1.0.sql` (creates settings + core tables + permission). Advanced-module tables come from
     `sql/1.1.sql` / `sql/2.0.sql` — run these manually if upgrading an existing v1.0 install (see
     [Upgrading](#upgrading-from-v10) below).
   - Insert a row into the `addons` table so the addon shows on `/admin/addons`.
4. Complete the manual hookups in [INSTALL_HOOKS.md](INSTALL_HOOKS.md). Steps 1–5 are required for
   the core v1.0 features; steps 6–10 are needed for the v1.1 advanced modules (JShrink, queue
   worker, CDN auto-purge event provider, Bot/Hotlink middleware, AI analysis schedule).

### a) Register the route group

Edit `app/Providers/RouteServiceProvider.php`:

```php
public function map()
{
    // ...existing maps...
    $this->mapPerformanceOptimizerRoutes();
    $this->mapWebRoutes();
}

protected function mapPerformanceOptimizerRoutes()
{
    Route::middleware('web')
        ->namespace($this->namespace)
        ->group(base_path('routes/performance_optimizer.php'));
}
```

### b) Add the sidebar menu item

Open `resources/views/backend/inc/admin_sidenav.blade.php` and paste the `<li>` block from
`resources/views/backend/performance_optimizer/partials/sidebar_menu.blade.php` (this file is
copied by the installer) wherever you want it in the existing `aiz-side-nav-list`.

The block is already wrapped in `@if(addon_is_activated('performance_optimizer'))` so it
disappears automatically if the addon is deactivated.

### c) Enable the page-cache and output-processing middleware

Edit `app/Http/Kernel.php` → `$middlewareGroups['web']`, append (order matters — page cache
must run before output processing so cached HTML is already optimized):

```php
\App\Http\Middleware\PerformanceOptimizer\PageCacheMiddleware::class,
\App\Http\Middleware\PerformanceOptimizer\PerformanceOutputMiddleware::class,
```

The page-cache middleware short-circuits HTML responses for guest visitors on GET requests whose
path/cookies are not excluded (or matched by a **Cache Rules** bypass entry). It honours the
`Enable full-page cache` toggle in **Page Cache → Settings**.

### d) Embed the Web-Vitals tracker

Add to your frontend layout's `<head>` (e.g. `resources/views/frontend/partials/header.blade.php`):

```blade
@if(addon_is_activated('performance_optimizer') && (int) get_setting('perf_vitals_collect_status') === 1)
    <script>
        window.PERF_VITALS_ENDPOINT = "{{ route('performance_optimizer.collect_vital') }}";
        window.PERF_VITALS_RATE     = {{ (int) get_setting('perf_vitals_sample_rate', 10) }};
    </script>
    <script src="{{ asset('assets/performance_optimizer/js/web_vitals_tracker.js') }}" defer></script>
@endif
```

Also add `performance-optimizer/collect-vital` to the `$except` array in
`app/Http/Middleware/VerifyCsrfToken.php` (the route validates itself via
`VitalsCollectMiddleware`, so exempting it here is safe).

### e) (Optional) Bot & Hotlink protection

Append to `$middlewareGroups['web']`, **before** `PageCacheMiddleware`, so blocked requests are
never cached:

```php
\App\Http\Middleware\PerformanceOptimizer\BotProtectionMiddleware::class,
\App\Http\Middleware\PerformanceOptimizer\HotlinkProtectionMiddleware::class,
```

Both are inert until turned on via `perf_bot_protect_status` / `perf_hotlink_protect_status` in
**Security Plus**.

### f) (Optional) CDN auto-purge + slow-query listener

Register the addon's event provider in `app/Providers/AppServiceProvider.php::boot()`:

```php
if (class_exists(\App\Providers\PerformanceOptimizerEventServiceProvider::class)) {
    $this->app->register(\App\Providers\PerformanceOptimizerEventServiceProvider::class);
}
```

This wires: auto-purge of local page cache + Cloudflare + Bunny on Product/Category save & delete,
and the Slow Query Analyzer's `DB::listen` hook.

### g) (Optional) Scheduled commands

In `app/Console/Kernel.php::schedule()`:

```php
$schedule->command('perf:auto-clean')
    ->dailyAt('03:30')->withoutOverlapping()->runInBackground();

$schedule->command('perf:ai-analysis --auto-apply')
    ->dailyAt('04:00')->withoutOverlapping()->runInBackground();
```

`perf:auto-clean` is gated by `perf_db_auto_clean_status`; the AI command's `--auto-apply` flag is
gated by `perf_ai_recs_auto_apply` — both stay inert until enabled in the UI.

### h) (Optional) Extra Composer packages

| Package | Needed for | Behaviour if missing |
|---|---|---|
| `tedivm/jshrink` | JS minification (CSS/JS tab) | "Minify all JS" returns an explicit error instead of corrupting JS with a naive regex fallback. |
| `aws/aws-sdk-php` | AWS CloudFront invalidation (Edge/CDN tab) | The CloudFront toggle is auto-disabled in the UI with a warning badge. |

### i) (Optional) Queue worker for async image conversion

Ticking **"Run async"** on the bulk WebP/AVIF button dispatches `ConvertImagesJob` to the
`perf-optimizer` queue:

```bash
php artisan queue:work --queue=perf-optimizer
```

With the default `QUEUE_CONNECTION=sync`, conversion runs synchronously instead.

## Permission

The SQL seeds a `manage_performance_optimizer` permission. Grant it to admin roles via
**Setup & Configurations → Staff Permissions** if you want non-superadmin staff to access this addon.

## Upgrading from v1.0

If you already have the core v1.0 tables installed, run the incremental SQL files to add the
advanced-module tables and settings, then complete install steps (e)–(g) above:

```bash
mysql -u <user> -p <database> < addons/active_ecommerce_performance_optimizer/sql/1.1.sql
mysql -u <user> -p <database> < addons/active_ecommerce_performance_optimizer/sql/2.0.sql
```

`sql/update.sql` contains any idempotent column/index patches for existing installs — safe to
re-run.

## Banner

Replace `assets/performance_optimizer_banner.png` with a real 600×300 banner image before zipping.

## Uninstall

1. Deactivate from **Admin → Addons**.
2. Revert the route map, sidebar, Kernel middleware, `AppServiceProvider`, and scheduler edits.
3. Drop all tables if you want a clean removal:

```sql
DROP TABLE IF EXISTS `performance_optimization_logs`;
DROP TABLE IF EXISTS `performance_web_vitals`;
DROP TABLE IF EXISTS `perf_cache_rules`;
DROP TABLE IF EXISTS `perf_script_rules`;
DROP TABLE IF EXISTS `perf_slow_queries`;
DROP TABLE IF EXISTS `perf_ai_recommendations`;
DROP TABLE IF EXISTS `perf_auto_fix_history`;
DELETE FROM `business_settings` WHERE `type` LIKE 'perf_%';
DELETE FROM `permissions`       WHERE `name`  = 'manage_performance_optimizer';
```

## File map

```
addons/active_ecommerce_performance_optimizer/
├── config.json
├── 1.0.sql
├── sql/1.1.sql, sql/2.0.sql, sql/update.sql   ← incremental upgrades for advanced modules
├── README.md
├── INSTALL_HOOKS.md                        ← full manual-integration checklist
├── IMPROVEMENT_PLAN.md                     ← module roadmap / milestones
├── assets/
│   └── performance_optimizer_banner.png   ← replace with real banner
├── controllers/                            → app/Http/Controllers/
├── Services/                               → app/Services/PerformanceOptimizer/
├── Models/                                 → app/Models/PerformanceOptimizer/
├── Middleware/                             → app/Http/Middleware/PerformanceOptimizer/
├── Jobs/                                   → app/Jobs/PerformanceOptimizer/
├── Commands/                               → app/Console/Commands/PerformanceOptimizer/
├── AddonAi/Engine/                         → app/AddonAi/PerformanceOptimizer/Engine/
├── Providers/                              → app/Providers/PerformanceOptimizerEventServiceProvider.php
├── routes/performance_optimizer.php        → routes/performance_optimizer.php
├── views/backend/performance_optimizer/    → resources/views/backend/performance_optimizer/
└── public/assets/performance_optimizer/    → public/assets/performance_optimizer/
```

## Compatibility

- Active eCommerce CMS 8.x+
- PHP 8.2+
- Laravel 10
- Intervention/Image 2.5+ (already in vendor)
- AVIF features require PHP 8.1+ built with GD AVIF support
- Redis driver requires the `predis/predis` package (already in vendor) and a configured Redis connection
- `tedivm/jshrink` required for JS minification (optional Composer package)
- `aws/aws-sdk-php` required for CloudFront invalidation (optional Composer package)
- Cloudflare / Bunny.net / CloudFront integrations require valid API credentials for the respective service
