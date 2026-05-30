<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use App\Services\PerformanceOptimizer\PageCacheService;
use Closure;
use Illuminate\Http\Request;

class PublicCacheCookieStripperMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldStrip($request, $response)) {
            $response->headers->remove('Set-Cookie');
            $response->headers->set('X-Performance-Cookies', 'stripped');
        }

        return $response;
    }

    protected function shouldStrip(Request $request, $response): bool
    {
        if ((int) get_setting('perf_public_cache_cookie_strip', 1) !== 1) {
            return false;
        }

        if ((int) get_setting('perf_status', 1) !== 1
            || (int) get_setting('perf_page_cache_status', 0) !== 1) {
            return false;
        }

        if (!$request->isMethod('GET') || $request->ajax() || $request->wantsJson() || auth()->check()) {
            return false;
        }

        if (!isset($response->headers)) {
            return false;
        }

        $cacheState = (string) $response->headers->get('X-Performance-Cache', '');
        if (!in_array($cacheState, ['HIT', 'MISS', 'LSC-PASS'], true)) {
            return false;
        }

        if (stripos((string) $response->headers->get('Content-Type', ''), 'text/html') === false) {
            return false;
        }

        if (app(PageCacheService::class)->isNeverCachePath(ltrim($request->path(), '/'))) {
            return false;
        }

        return true;
    }
}
