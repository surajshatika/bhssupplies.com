<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use Closure;
use Illuminate\Http\Request;

/**
 * 404s the admin routes when the addon is deactivated in /admin/addons.
 * Without this, deactivating in the UI just toggles the row — routes stay reachable.
 */
class AddonActiveMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!function_exists('addon_is_activated') || !addon_is_activated('performance_optimizer')) {
            abort(404, 'Performance Optimizer addon is not activated');
        }
        return $next($request);
    }
}
