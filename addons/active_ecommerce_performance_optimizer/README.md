# Performance Optimizer Addon — Active eCommerce CMS

Advanced site-speed and operations addon for **Active eCommerce CMS** (Laravel 10+, PHP 8.2+).
Inspired by the Sevenopal performance-optimizer plugin for Botble; rebuilt to fit Active eCommerce
conventions (BusinessSetting, `addon_is_activated`, AIZ admin UI).

## Features

| Tab            | What it does                                                                                  |
|----------------|------------------------------------------------------------------------------------------------|
| **Dashboard**  | Live stats (images, page cache, DB, recent activity) + quick-action buttons.                  |
| **Images**     | Batch WebP / AVIF conversion, originals auto-backed up, single-file restore, lazy-load toggle.|
| **CSS / JS**   | Bulk minify (`*.min.css` / `*.min.js`), defer / delay JS, critical-CSS, auto-fix `<img>` size.|
| **Page Cache** | Full-page HTML cache (file or Redis driver), TTL, path & cookie excludes, sitemap warmer.     |
| **Database**   | Targeted cleanup of sessions / failed jobs / expired tokens / abandoned carts / OTPs + `OPTIMIZE TABLE`. |
| **Fonts**      | Preload critical fonts, force `font-display: swap`.                                           |
| **Web Vitals** | LCP / FID / CLS / INP / FCP / TTFB collector with P50/P75/P95 rollups (PerformanceObserver).  |
| **Security**   | 14-point audit (APP_DEBUG, HTTPS, .env exposure, weak admin pw, etc.) + score.                |
| **Logs**       | Optimization activity log + tailed Laravel error log (`storage/logs/laravel.log`).            |
| **Monitor**    | PHP/Laravel/MySQL/OPcache/disk/memory/extension snapshot.                                     |

## Install (Zip method — recommended)

1. Zip the **`active_ecommerce_performance_optimizer`** folder (preserve folder name as root inside the ZIP).
2. **Admin → Addons → +Add New**, paste any purchase code (e.g. `BHSpayment`), submit.
3. The installer (`AddonController@store`) will:
   - Copy files per `config.json`.
   - Run `1.0.sql` (creates settings + tables + permission).
   - Insert a row into the `addons` table so the addon shows on `/admin/addons`.
4. Complete the 5 manual hookups in [INSTALL_HOOKS.md](INSTALL_HOOKS.md) (route service provider, kernel middleware, CSRF exempt, scheduler, sidebar).

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

### c) (Optional) Enable the page-cache middleware

Edit `app/Http/Kernel.php` → `$middlewareGroups['web']`, append:

```php
\App\Http\Middleware\PerformanceOptimizer\PageCacheMiddleware::class,
```

The middleware short-circuits HTML responses for guest visitors on GET requests whose path/cookies
are not in the exclude lists. It honours the `Enable full-page cache` toggle in **Page Cache → Settings**.

### d) (Optional) Embed the Web-Vitals tracker

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

## Permission

The SQL seeds a `manage_performance_optimizer` permission. Grant it to admin roles via
**Setup & Configurations → Staff Permissions** if you want non-superadmin staff to access this addon.

## Banner

Replace `assets/performance_optimizer_banner.png` with a real 600×300 banner image before zipping.

## Uninstall

1. Deactivate from **Admin → Addons**.
2. Revert the route map + sidebar edits.
3. Drop the two tables if you want a clean removal:

```sql
DROP TABLE IF EXISTS `performance_optimization_logs`;
DROP TABLE IF EXISTS `performance_web_vitals`;
DELETE FROM `business_settings` WHERE `type` LIKE 'perf_%';
DELETE FROM `permissions`       WHERE `name`  = 'manage_performance_optimizer';
```

## File map

```
addons/active_ecommerce_performance_optimizer/
├── config.json
├── 1.0.sql
├── README.md
├── assets/
│   └── performance_optimizer_banner.png   ← replace with real banner
├── controllers/                            → app/Http/Controllers/
├── Services/                               → app/Services/PerformanceOptimizer/
├── Models/                                 → app/Models/PerformanceOptimizer/
├── Middleware/                             → app/Http/Middleware/PerformanceOptimizer/
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
