<?php

namespace App\Providers;

use App\Services\PerformanceOptimizer\BunnyCdnService;
use App\Services\PerformanceOptimizer\CloudflareService;
use App\Services\PerformanceOptimizer\CloudFrontService;
use App\Services\PerformanceOptimizer\PageCacheService;
use App\Services\PerformanceOptimizer\SlowQueryAnalyzerService;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Auto-purges local page cache + connected edge CDNs whenever a Product or
 * Category is saved/deleted. Safe to call before the addon is fully installed —
 * each step is guarded with try/catch and an `addon_is_activated` check.
 */
class PerformanceOptimizerEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Bail early if the addon isn't installed/active yet
        if (!function_exists('addon_is_activated')) return;
        try {
            if (!addon_is_activated('performance_optimizer')) return;
        } catch (Throwable $e) {
            return;
        }

        $purge = function ($model) {
            // 1) Local page cache
            try { app(PageCacheService::class)->clearAll(); } catch (Throwable $e) {}

            // 2) Cloudflare
            if ((int) get_setting('perf_cloudflare_auto_purge', 1) === 1) {
                try { app(CloudflareService::class)->purgeAll(); } catch (Throwable $e) {}
            }

            // 3) Bunny.net
            try { app(BunnyCdnService::class)->purgeAll(); } catch (Throwable $e) {}

            // 4) CloudFront — only invalidate when explicitly opted-in (cost-bearing)
            if ((int) get_setting('perf_cloudfront_status', 0) === 1
                && (int) get_setting('perf_cloudfront_auto_invalidate', 0) === 1) {
                try { app(CloudFrontService::class)->invalidate(['/*']); } catch (Throwable $e) {}
            }
        };

        // Listen to Product + Category lifecycle events. Class checks guard against
        // theme/marketplace variants that may have renamed models.
        if (class_exists(\App\Models\Product::class)) {
            \App\Models\Product::saved($purge);
            \App\Models\Product::deleted($purge);
        }
        if (class_exists(\App\Models\Category::class)) {
            \App\Models\Category::saved($purge);
            \App\Models\Category::deleted($purge);
        }

        // Slow Query analyzer — attach DB::listen on every request (gated inside)
        try { app(SlowQueryAnalyzerService::class)->attachListener(); } catch (Throwable $e) {}
    }
}
