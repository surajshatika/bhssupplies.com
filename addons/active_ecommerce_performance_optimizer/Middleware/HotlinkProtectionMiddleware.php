<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use Closure;
use Illuminate\Http\Request;

/**
 * Returns 403 on requests for /uploads/* (or /public/uploads/*) when the
 * Referer header is set AND its host is neither the app host nor in the
 * allowlist.
 *
 * No referer (direct hits, RSS readers, link checkers) → allowed.
 */
class HotlinkProtectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ((int) get_setting('perf_status', 1) !== 1) return $next($request);
        if ((int) get_setting('perf_hotlink_protect_status', 0) !== 1) return $next($request);

        $path = ltrim($request->path(), '/');
        if (!preg_match('#^(?:public/)?(?:uploads|images)/#i', $path)) return $next($request);

        $referer = (string) $request->header('Referer', '');
        if ($referer === '') return $next($request);  // direct hit OK

        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (!$refererHost) return $next($request);

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $allowed = array_filter(array_map('trim', preg_split('/\R/', (string) get_setting('perf_hotlink_allowed_domains', '')) ?: []));
        if ($appHost) $allowed[] = (string) $appHost;
        $allowed = array_filter(array_map('strtolower', $allowed));

        $refererHost = strtolower($refererHost);
        foreach ($allowed as $a) {
            if ($a === '') continue;
            if ($refererHost === $a || str_ends_with($refererHost, '.' . $a)) {
                return $next($request);
            }
        }

        abort(403, 'Hotlinking blocked');
    }
}
