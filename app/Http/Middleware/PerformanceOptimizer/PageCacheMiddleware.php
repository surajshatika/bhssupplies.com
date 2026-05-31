<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use App\Services\PerformanceOptimizer\PageCacheService;
use App\Services\PerformanceOptimizer\LiteSpeedCacheService;
use Closure;
use Illuminate\Http\Request;

class PageCacheMiddleware
{
    protected PageCacheService $cache;
    protected LiteSpeedCacheService $lsc;

    public function __construct(PageCacheService $cache, LiteSpeedCacheService $lsc)
    {
        $this->cache = $cache;
        $this->lsc   = $lsc;
    }

    public function handle(Request $request, Closure $next)
    {
        if ((int) get_setting('perf_status', 1) !== 1) {
            $response = $next($request);
            if ($this->cache->isNeverCachePath(ltrim($request->path(), '/'))) {
                $this->markNoStore($response);
            }
            return $response;
        }

        $driver = (string) get_setting('perf_page_cache_driver', 'file');

        // ── LiteSpeed Cache driver ────────────────────────────────────────────
        // LiteSpeed handles storage itself via the server module.
        // PHP only needs to set the correct X-LiteSpeed-Cache-Control headers.
        if ($driver === 'litespeed'
            && $this->lsc->isLiteSpeedServer()
            && (int) get_setting('perf_page_cache_status', 0) === 1
        ) {
            $shouldCache = $this->cache->shouldCache($request);
            $url = $request->fullUrl();

            // Keep a local safety copy. Some shared hosts expose LiteSpeed headers
            // while server-level LSCache is disabled or repeatedly misses.
            if ($shouldCache && ($cached = $this->cache->get($url, $request)) !== null) {
                $ttlSeconds = $this->cache->ttlMinutesFor($request) * 60;
                return response($cached, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('X-LiteSpeed-Cache-Control', "public,max-age={$ttlSeconds}")
                    ->header('X-LiteSpeed-Vary', 'value=cookie[currency_code],value=cookie[locale_code]')
                    ->header('X-Performance-Cache', 'HIT')
                    ->header('Cache-Control', "public, max-age=0, s-maxage={$ttlSeconds}, stale-while-revalidate=60")
                    ->header('Vary', 'Accept-Encoding');
            }

            $response = $next($request);
            if ($shouldCache && $this->isLiteSpeedCacheable($response)) {
                $ttlMinutes = $this->cache->ttlMinutesFor($request);
                $ttlSeconds = $ttlMinutes * 60;
                $response->setContent($this->cache->prepareHtmlForSharedCache((string) $response->getContent()));
                $this->cache->store($url, (string) $response->getContent(), $request);
                $this->stripPublicCacheCookies($response);
                // LSCache header — tells LiteSpeed server to cache this page
                $response->headers->set('X-LiteSpeed-Cache-Control', "public,max-age={$ttlSeconds}");
                $response->headers->set('X-LiteSpeed-Vary', 'value=cookie[currency_code],value=cookie[locale_code]');
                // Standard Cache-Control — picked up by Cloudflare CDN (and any other reverse proxy)
                // s-maxage targets shared caches (CDN); max-age=0 forces browser revalidation
                $response->headers->set('Cache-Control', "public, max-age=0, s-maxage={$ttlSeconds}, stale-while-revalidate=60");
                $response->headers->set('Vary', 'Accept-Encoding');
                $response->headers->set('X-Performance-Cache', 'LSC-PASS');
            } else {
                $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
                $response->headers->set('X-Performance-Cache', 'LSC-BYPASS');
            }
            return $response;
        }

        // ── File / Redis driver, plus non-LiteSpeed fallback ──────────────────
        if (!$this->cache->shouldCache($request)) {
            $response = $next($request);
            if ($this->cache->isNeverCachePath(ltrim($request->path(), '/'))) {
                $this->markNoStore($response);
            }
            // Expose BYPASS header on GET HTML responses so admins can verify cache-rule wiring
            if ((int) get_setting('perf_page_cache_status', 0) === 1
                && $request->isMethod('GET')
                && isset($response->headers)
                && stripos((string) $response->headers->get('Content-Type', ''), 'text/html') !== false
                && !$response->headers->has('X-Performance-Cache')
            ) {
                $response->headers->set('X-Performance-Cache', 'BYPASS');
            }
            return $response;
        }

        $url = $request->fullUrl();

        $cached = $this->cache->get($url, $request);
        if ($cached !== null) {
            $sMageAge = $this->cache->ttlMinutesFor($request) * 60;
            return response($cached, 200)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Performance-Cache', 'HIT')
                ->header('Cache-Control', "public, max-age=0, s-maxage={$sMageAge}, stale-while-revalidate=60")
                ->header('Vary', 'Accept-Encoding');
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if ($this->isCacheable($response)) {
            $this->cache->store($url, (string) $response->getContent(), $request);
            $sMageAge = $this->cache->ttlMinutesFor($request) * 60;
            $response->headers->set('X-Performance-Cache', 'MISS');
            $response->headers->set('Cache-Control', "public, max-age=0, s-maxage={$sMageAge}, stale-while-revalidate=60");
            $response->headers->set('Vary', 'Accept-Encoding');
        } else {
            $response->headers->set('X-Performance-Cache', 'BYPASS');
        }

        return $response;
    }

    /**
     * Strict rules for storing a response. Skips:
     *  - non-200 responses
     *  - responses with non-session Set-Cookie (auth cookies, cart cookies, etc.)
     *  - Cache-Control: no-store / private
     *  - explicit X-Performance-Cache: BYPASS marker
     *  - non-HTML content
     *  - empty / tiny bodies
     *
     * NOTE: Laravel's StartSession always adds Set-Cookie for the session on new-visitor
     * requests. We allow that cookie through — only block non-session cookies which
     * indicate user-specific state (auth, remember_me, cart, etc.).
     */
    protected function isCacheable($response): bool
    {
        if (!method_exists($response, 'getStatusCode') || $response->getStatusCode() !== 200) {
            return false;
        }

        $headers = $response->headers;

        if ($headers->has('Set-Cookie')) {
            $sessionCookieName = config('session.cookie', 'laravel_session');
            $rememberCookieName = 'remember_web';
            foreach ($headers->all('Set-Cookie') as $cookieHeader) {
                // First-party analytics cookies are generated per visitor but page cache stores
                // only HTML content, not response headers, so they must not prevent cache fill.
                if (str_starts_with($cookieHeader, 'mm_') || str_starts_with($cookieHeader, 'XSRF-TOKEN=')) {
                    continue;
                }

                // Block if any Set-Cookie is NOT the plain session token (e.g. auth, cart, remember_me)
                if (!str_starts_with($cookieHeader, $sessionCookieName . '=')
                    && !str_starts_with($cookieHeader, $rememberCookieName . '_')
                ) {
                    return false;
                }
            }
        }

        if ($headers->get('X-Performance-Cache') === 'BYPASS') {
            return false;
        }

        $cc = strtolower((string) $headers->get('Cache-Control', ''));
        if (str_contains($cc, 'no-store')) {
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

    protected function markNoStore($response): void
    {
        if (!isset($response->headers)) {
            return;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Performance-Cache', 'BYPASS');
        $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
    }

    protected function stripPublicCacheCookies($response): void
    {
        if (!isset($response->headers)) {
            return;
        }

        // Cacheable public pages should not emit visitor-specific cookies.
        // Cached HTML uses a CSRF placeholder and refreshes the real token via /refresh-csrf.
        $response->headers->remove('Set-Cookie');
    }

    protected function isLiteSpeedCacheable($response): bool
    {
        if (!method_exists($response, 'getStatusCode') || $response->getStatusCode() !== 200) {
            return false;
        }

        $headers = $response->headers;
        if ($headers->get('X-Performance-Cache') === 'BYPASS') {
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
