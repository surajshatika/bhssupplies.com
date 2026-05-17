<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Blocks aggressive crawlers by User-Agent substring and rate-limits per (ip, ua).
 *
 * Skipped automatically:
 *   - When perf_status (master switch) is OFF
 *   - When perf_bot_protect_status is OFF
 *   - On admin paths (would block real ops)
 */
class BotProtectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ((int) get_setting('perf_status', 1) !== 1) return $next($request);
        if ((int) get_setting('perf_bot_protect_status', 0) !== 1) return $next($request);

        // Never gate admin or our own endpoints
        $path = ltrim($request->path(), '/');
        if (str_starts_with($path, 'admin') || str_starts_with($path, 'performance-optimizer')) {
            return $next($request);
        }

        $ua = strtolower((string) $request->userAgent());

        // 1) Hard block list (newline-separated UA substrings)
        $blockList = array_filter(array_map('trim', preg_split('/\R/', (string) get_setting('perf_bot_block_list', '')) ?: []));
        foreach ($blockList as $needle) {
            if ($needle === '') continue;
            if (str_contains($ua, strtolower($needle))) {
                abort(403, 'Forbidden');
            }
        }

        // 2) Rate limit per (ip, ua) — sliding 60-second window
        $perMin = (int) get_setting('perf_bot_rate_limit_per_min', 60);
        if ($perMin > 0) {
            $key = 'perf_bot_rl_' . md5($request->ip() . '|' . $ua);
            $count = (int) Cache::get($key, 0);
            if ($count >= $perMin) {
                abort(429, 'Too Many Requests');
            }
            Cache::put($key, $count + 1, 60);
        }

        return $next($request);
    }
}
