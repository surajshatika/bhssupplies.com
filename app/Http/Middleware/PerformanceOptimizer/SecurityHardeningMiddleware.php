<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use Closure;
use Illuminate\Http\Request;

/**
 * Wires up the three security toggles on Performance Optimizer → Security:
 *   - perf_security_block_xmlrpc      → 403 any request to /xmlrpc.php
 *   - perf_security_force_https       → 301 redirect HTTP → HTTPS
 *   - perf_security_hide_php_version  → strip X-Powered-By header from responses
 *
 * Place this middleware in app/Http/Kernel.php → $middleware (global) so it
 * intercepts even non-routed paths like /xmlrpc.php.
 */
class SecurityHardeningMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Block XML-RPC requests outright
        if ((int) get_setting('perf_security_block_xmlrpc', 0) === 1) {
            $path = ltrim($request->path(), '/');
            if (strcasecmp($path, 'xmlrpc.php') === 0) {
                abort(403, 'XML-RPC disabled');
            }
        }

        // Force HTTPS — skip on localhost / 127.0.0.1
        if ((int) get_setting('perf_security_force_https', 0) === 1) {
            $host = $request->getHost();
            $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
            if (!$request->isSecure() && !$isLocal && !app()->runningInConsole()) {
                $url = 'https://' . $host . $request->getRequestUri();
                return redirect()->away($url, 301);
            }
        }

        $response = $next($request);

        // Strip PHP version exposure header
        if ((int) get_setting('perf_security_hide_php_version', 0) === 1) {
            if (method_exists($response, 'headers')) {
                $response->headers->remove('X-Powered-By');
            }
            @header_remove('X-Powered-By');
        }

        return $response;
    }
}
