<?php

namespace App\Http\Middleware;

use Closure;

class HttpsProtocol {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $host = strtolower((string) $request->getHost());
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');

        if (!$isLocalHost && env('FORCE_HTTPS') == "On" && !$request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }
        return $next($request);
    }
}
