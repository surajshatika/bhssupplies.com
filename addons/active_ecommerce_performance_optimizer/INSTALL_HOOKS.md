# Manual integration steps after ZIP install

The Active eCommerce addon installer **only copies files + runs SQL**. The integration
points below must be added by hand after the ZIP install (you can also pre-edit these in
your working copy before zipping). Steps 1–5 are required; 6–9 are recommended for v1.1
(v2.0 modules: Cache Rules, Edge CDN auto-purge, Bot/Hotlink protection, AI Recommendations).

---

## 1. Register the route group — `app/Providers/RouteServiceProvider.php`

Inside the `map()` method (before `$this->mapWebRoutes()`):

```php
$this->mapPerformanceOptimizerRoutes();
```

Add the method itself anywhere in the class:

```php
protected function mapPerformanceOptimizerRoutes()
{
    Route::middleware('web')
        ->namespace($this->namespace)
        ->group(base_path('routes/performance_optimizer.php'));
}
```

---

## 2. Register middleware — `app/Http/Kernel.php`

### a) Global middleware (XML-RPC block, force HTTPS, hide X-Powered-By):

Append to the `$middleware` array (top-level, before route middleware):

```php
\App\Http\Middleware\PerformanceOptimizer\SecurityHardeningMiddleware::class,
```

### b) Web group middleware (page cache + output processing):

Inside `$middlewareGroups['web']`, append at the **end** (so HTML is fully rendered before we touch it):

```php
'web' => [
    // ...existing entries...
    \App\Http\Middleware\PerformanceOptimizer\PageCacheMiddleware::class,
    \App\Http\Middleware\PerformanceOptimizer\PerformanceOutputMiddleware::class,
],
```

> Order matters: `PageCacheMiddleware` must come **before** `PerformanceOutputMiddleware`
> so cache writes contain the optimized HTML — not the raw HTML.

---

## 3. CSRF exemption for the Vitals collector — `app/Http/Middleware/VerifyCsrfToken.php`

`navigator.sendBeacon` sends data from possibly-cached pages whose embedded `_token` is stale.
Add the collector URI to `$except`:

```php
protected $except = [
    // ...existing entries...
    'performance-optimizer/collect-vital',
];
```

The route already has its own validation (origin check, body cap, throttle 60/min) via
`VitalsCollectMiddleware` so dropping CSRF here is safe.

---

## 4. Schedule the cleanup command — `app/Console/Kernel.php`

If you want auto-cleanup (DB rows + old image backups) every night, add to the `schedule()` method:

```php
protected function schedule(Schedule $schedule)
{
    // ...existing schedules...
    $schedule->command('perf:auto-clean')
        ->dailyAt('03:30')
        ->withoutOverlapping()
        ->runInBackground();
}
```

The command itself is gated by the `perf_db_auto_clean_status` toggle in **Performance Optimizer → Database**.

---

## 5. Sidebar menu — `resources/views/backend/inc/admin_sidenav.blade.php`

Paste the `<li>` block from
`resources/views/backend/performance_optimizer/partials/sidebar_menu.blade.php`
into the main sidebar `<ul class="aiz-side-nav-list">`. The block is gated with
`@if(addon_is_activated('performance_optimizer'))` so no further checks needed.

---

## 6. (Optional) Install JShrink for JS minification

The JS minifier requires `tedivm/jshrink`. Without it, "Minify all JS" returns an
error (this is intentional — the naive regex fallback corrupts URLs in JS strings).

```bash
composer require tedivm/jshrink
```

---

## 7. (Optional) Queue worker for bulk image conversion

If you tick **"Run async"** on the WebP/AVIF batch button, the work dispatches to a queue:

```bash
php artisan queue:work --queue=perf-optimizer
```

With `QUEUE_CONNECTION=sync` (default), it falls back to synchronous execution.

---

---

## 8. (v1.1 / M4) Register the Edge CDN auto-purge listener — `app/Providers/AppServiceProvider.php`

In `boot()`, register the addon's event provider (it gates itself on `addon_is_activated`):

```php
public function boot()
{
    // ...existing...
    if (class_exists(\App\Providers\PerformanceOptimizerEventServiceProvider::class)) {
        $this->app->register(\App\Providers\PerformanceOptimizerEventServiceProvider::class);
    }
}
```

This auto-purges the local page cache + Cloudflare + Bunny on Product / Category save+delete,
and attaches the Slow Query analyzer's `DB::listen` hook (M5).

---

## 9. (v1.1 / M5) Register Bot + Hotlink middlewares — `app/Http/Kernel.php`

For Bot Protection and Hotlink Protection to take effect on frontend requests, append to
the `$middlewareGroups['web']` array (place them **before** `PageCacheMiddleware` so blocked
requests don't get cached):

```php
'web' => [
    // ...existing entries...
    \App\Http\Middleware\PerformanceOptimizer\BotProtectionMiddleware::class,
    \App\Http\Middleware\PerformanceOptimizer\HotlinkProtectionMiddleware::class,
    // (existing PageCacheMiddleware + PerformanceOutputMiddleware go after these)
],
```

Both middlewares are gated by their own admin toggle (`perf_bot_protect_status`, `perf_hotlink_protect_status`)
so adding them to the stack is safe — they're a no-op until you enable them in the UI.

---

## 10. (v1.1 / M6) Schedule AI analysis — `app/Console/Kernel.php`

To keep recommendations fresh and optionally auto-apply high-confidence fixes:

```php
$schedule->command('perf:ai-analysis --auto-apply')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground();
```

The `--auto-apply` flag is gated by `perf_ai_recs_auto_apply` so it stays inert until you
turn it on in the UI.

---

## Quick verification

After installing + completing the 5 mandatory steps:

1. Visit `/admin/addons` — "Performance Optimizer (Advanced)" should appear with toggle ON.
2. Visit `/admin/performance-optimizer` — dashboard loads with the master switch + 10 tabs.
3. Deactivate from `/admin/addons`, re-visit `/admin/performance-optimizer` — should 404 (AddonActiveMiddleware works).
4. From a logged-out browser, view a non-admin page — response should have `X-Performance-Cache: MISS` (first visit) or `HIT` (subsequent), and `<img loading="lazy">` if image lazyload is on.
