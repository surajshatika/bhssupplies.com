<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use App\Services\PerformanceOptimizer\PageCacheService;
use Closure;
use Illuminate\Http\Request;

class PageCacheMiddleware
{
    protected PageCacheService $cache;

    public function __construct(PageCacheService $cache)
    {
        $this->cache = $cache;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!$this->cache->shouldCache($request)) {
            $response = $next($request);
            // Expose BYPASS header on GET HTML responses so admins can verify cache-rule wiring
            if ((int) get_setting('perf_page_cache_status', 0) === 1
                && $request->isMethod('GET')
                && method_exists($response, 'headers')
                && stripos((string) $response->headers->get('Content-Type', ''), 'text/html') !== false
                && !$response->headers->has('X-Performance-Cache')
            ) {
                $response->headers->set('X-Performance-Cache', 'BYPASS');
            }
            return $response;
        }

        $url = $request->fullUrl();

        if ($this->cache->has($url, $request)) {
            $cached = $this->cache->get($url, $request);
            if ($cached !== null) {
                return response($cached, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('X-Performance-Cache', 'HIT');
            }
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if ($this->isCacheable($response)) {
            $this->cache->store($url, (string) $response->getContent(), $request);
            $response->headers->set('X-Performance-Cache', 'MISS');
        } else {
            $response->headers->set('X-Performance-Cache', 'BYPASS');
        }

        return $response;
    }

    /**
     * Strict rules for storing a response. Skips:
     *  - non-200 responses
     *  - responses with Set-Cookie (login/session bootstrap)
     *  - Cache-Control: no-store / private
     *  - explicit X-Performance-Cache: BYPASS marker
     *  - non-HTML content
     *  - empty / tiny bodies
     */
    protected function isCacheable($response): bool
    {
        if (!method_exists($response, 'getStatusCode') || $response->getStatusCode() !== 200) {
            return false;
        }

        $headers = $response->headers;

        if ($headers->has('Set-Cookie')) {
            return false;
        }
        if ($headers->get('X-Performance-Cache') === 'BYPASS') {
            return false;
        }

        $cc = strtolower((string) $headers->get('Cache-Control', ''));
        if (str_contains($cc, 'no-store') || str_contains($cc, 'private') || str_contains($cc, 'no-cache')) {
            return false;
        }

        $contentType = (string) $headers->get('Content-Type', '');
        if (stripos($contentType, 'text/html') === false) {
            return false;
        }

        $body = (string) $response->getContent();
        if (strlen($body) < 200) {
            return false;
        }

        return true;
    }
}
