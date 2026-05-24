<?php

/*
|--------------------------------------------------------------------------
| Performance Optimizer Addon — Routes
|--------------------------------------------------------------------------
| Loaded via app/Providers/RouteServiceProvider.php → mapPerformanceOptimizerRoutes()
*/

use App\Http\Controllers\PerformanceOptimizerController;
use App\Http\Controllers\PerformanceImageController;
use App\Http\Controllers\PerformanceCssJsController;
use App\Http\Controllers\PerformanceCacheController;
use App\Http\Controllers\PerformanceDatabaseController;
use App\Http\Controllers\PerformanceFontController;
use App\Http\Controllers\PerformanceVitalsController;
use App\Http\Controllers\PerformanceSecurityController;
use App\Http\Controllers\PerformanceLogController;
use App\Http\Controllers\PerformanceMonitorController;
use App\Http\Middleware\PerformanceOptimizer\AddonActiveMiddleware;
use App\Http\Middleware\PerformanceOptimizer\VitalsCollectMiddleware;

// ── Admin ─────────────────────────────────────────────────────────────────
Route::group([
    'prefix'     => 'admin',
    'middleware' => [
        'auth',
        'admin',
        'permission:manage_performance_optimizer',
        AddonActiveMiddleware::class,
    ],
], function () {

    // Dashboard + global toggle + settings
    Route::controller(PerformanceOptimizerController::class)->group(function () {
        Route::get('/performance-optimizer',                 'index')->name('performance_optimizer.index');
        Route::get('/performance-optimizer/dashboard',       'dashboard')->name('performance_optimizer.dashboard');
        Route::post('/performance-optimizer/toggle',         'toggle')->name('performance_optimizer.toggle');
        Route::post('/performance-optimizer/settings',       'updateSettings')->name('performance_optimizer.settings.update');
    });

    // Images
    Route::controller(PerformanceImageController::class)->group(function () {
        Route::get('/performance-optimizer/images',          'index')->name('performance_optimizer.images');
        Route::post('/performance-optimizer/images/scan',    'scan')->name('performance_optimizer.images.scan');
        Route::post('/performance-optimizer/images/webp',    'convertAllWebp')->name('performance_optimizer.images.webp');
        Route::post('/performance-optimizer/images/avif',    'convertAllAvif')->name('performance_optimizer.images.avif');
        Route::post('/performance-optimizer/images/single',  'convertSingle')->name('performance_optimizer.images.single');
        Route::post('/performance-optimizer/images/restore', 'restoreAll')->name('performance_optimizer.images.restore');
        Route::post('/performance-optimizer/images/clean-backups', 'cleanBackups')->name('performance_optimizer.images.clean_backups');
    });

    // CSS / JS
    Route::controller(PerformanceCssJsController::class)->group(function () {
        Route::get('/performance-optimizer/css-js',          'index')->name('performance_optimizer.cssjs');
        Route::post('/performance-optimizer/css-js/minify-css', 'minifyCss')->name('performance_optimizer.cssjs.minify_css');
        Route::post('/performance-optimizer/css-js/minify-js',  'minifyJs')->name('performance_optimizer.cssjs.minify_js');
        Route::post('/performance-optimizer/css-js/scan-dims',  'scanDimensions')->name('performance_optimizer.cssjs.scan_dims');
        Route::post('/performance-optimizer/css-js/fix-dims',   'fixDimensions')->name('performance_optimizer.cssjs.fix_dims');
    });

    // Page Cache
    Route::controller(PerformanceCacheController::class)->group(function () {
        Route::get('/performance-optimizer/caching',                     'index')->name('performance_optimizer.cache');
        Route::post('/performance-optimizer/caching/clear',              'clear')->name('performance_optimizer.cache.clear');
        Route::post('/performance-optimizer/caching/warm',               'warm')->name('performance_optimizer.cache.warm');
        Route::get('/performance-optimizer/caching/warm',                fn() => redirect()->route('performance_optimizer.cache'));
        Route::post('/performance-optimizer/caching/laravel-clear',      'clearLaravel')->name('performance_optimizer.cache.laravel_clear');
        Route::post('/performance-optimizer/caching/laravel-optimize',   'optimize')->name('performance_optimizer.cache.optimize');
        // Combined purge (Page Cache + LiteSpeed + Cloudflare in one click)
        Route::post('/performance-optimizer/caching/purge-everything',   'purgeEverything')->name('performance_optimizer.cache.purge_everything');
        // LiteSpeed-specific purge
        Route::post('/performance-optimizer/caching/purge-litespeed',    'purgeLiteSpeed')->name('performance_optimizer.cache.purge_litespeed');
        Route::post('/performance-optimizer/caching/purge-litespeed-tag','purgeLiteSpeedTag')->name('performance_optimizer.cache.purge_litespeed_tag');
        // OPcache
        Route::post('/performance-optimizer/caching/flush-opcache',      'flushOpcache')->name('performance_optimizer.cache.flush_opcache');
        Route::get('/performance-optimizer/caching/opcache-stats',       'opcacheStats')->name('performance_optimizer.cache.opcache_stats');
    });

    // Database
    Route::controller(PerformanceDatabaseController::class)->group(function () {
        Route::get('/performance-optimizer/database',        'index')->name('performance_optimizer.database');
        Route::post('/performance-optimizer/database/clean', 'clean')->name('performance_optimizer.database.clean');
        Route::post('/performance-optimizer/database/optimize-tables', 'optimizeTables')->name('performance_optimizer.database.optimize');
    });

    // Fonts
    Route::controller(PerformanceFontController::class)->group(function () {
        Route::get('/performance-optimizer/fonts',           'index')->name('performance_optimizer.fonts');
        Route::post('/performance-optimizer/fonts/save',     'save')->name('performance_optimizer.fonts.save');
    });

    // Web Vitals
    Route::controller(PerformanceVitalsController::class)->group(function () {
        Route::get('/performance-optimizer/vitals',          'index')->name('performance_optimizer.vitals');
        Route::post('/performance-optimizer/vitals/clear',   'clear')->name('performance_optimizer.vitals.clear');
    });

    // Security audit
    Route::controller(PerformanceSecurityController::class)->group(function () {
        Route::get('/performance-optimizer/security',        'index')->name('performance_optimizer.security');
        Route::post('/performance-optimizer/security/run',   'run')->name('performance_optimizer.security.run');
    });

    // Logs
    Route::controller(PerformanceLogController::class)->group(function () {
        Route::get('/performance-optimizer/logs',            'index')->name('performance_optimizer.logs');
        Route::post('/performance-optimizer/logs/clear-error', 'clearError')->name('performance_optimizer.logs.clear_error');
        Route::post('/performance-optimizer/logs/clear-optimization', 'clearOptimization')->name('performance_optimizer.logs.clear_optimization');
    });

    // Monitor
    Route::controller(PerformanceMonitorController::class)->group(function () {
        Route::get('/performance-optimizer/monitor',         'index')->name('performance_optimizer.monitor');
        Route::get('/performance-optimizer/monitor/refresh', 'refresh')->name('performance_optimizer.monitor.refresh');
    });

    // ── v2.0 modules ────────────────────────────────────────────────────

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
            Route::get('/',                     'index')->name('index');
            Route::post('/save',                'save')->name('save');
            Route::post('/slow-q/scan',         'scanSlowQueries')->name('slowq.scan');
            Route::post('/slow-q/{id}/dismiss', 'dismissSlowQuery')->name('slowq.dismiss');
        });

    // M6 — AI Recommendations
    Route::controller(\App\Http\Controllers\PerformanceAiController::class)
        ->prefix('/performance-optimizer/ai')->name('performance_optimizer.ai.')
        ->group(function () {
            Route::get('/',                       'index')->name('index');
            Route::post('/run',                   'run')->name('run');
            Route::post('/{id}/apply',            'apply')->name('apply');
            Route::post('/{id}/dismiss',          'dismiss')->name('dismiss');
            Route::post('/history/{id}/rollback','rollback')->name('rollback');
            Route::post('/save',                  'save')->name('save');
        });
});

// ── Public collector (Web Vitals) ─────────────────────────────────────────
// NOTE: Add `performance-optimizer/collect-vital` to App\Http\Middleware\VerifyCsrfToken::$except
//       so navigator.sendBeacon calls from cached pages aren't rejected.
Route::middleware(['web', 'throttle:60,1', VitalsCollectMiddleware::class])->group(function () {
    Route::post('/performance-optimizer/collect-vital', [PerformanceVitalsController::class, 'collect'])
        ->name('performance_optimizer.collect_vital');
});
