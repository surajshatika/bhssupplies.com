<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-user throttle for AI-spending SEO endpoints. Defaults to 30 calls per
 * minute per authenticated admin, configurable via `seo_ai_rate_per_min`
 * business setting. Returns JSON 429 for AJAX requests to keep the AI
 * Board UI clean.
 */
class SeoRateLimitMiddleware
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $perMin = (int) (get_setting('seo_ai_rate_per_min', 30));
        $perMin = max(1, min($perMin, 300));

        $key = 'seo-ai:' . (optional($request->user())->id ?: $request->ip());

        if ($this->limiter->tooManyAttempts($key, $perMin)) {
            $retry = $this->limiter->availableIn($key);
            $msg = 'Too many AI requests. Please wait ' . $retry . ' seconds.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $msg, 'retry_after' => $retry], 429);
            }
            return response($msg, 429)->header('Retry-After', $retry);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}
