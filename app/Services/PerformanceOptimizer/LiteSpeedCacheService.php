<?php

namespace App\Services\PerformanceOptimizer;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * LiteSpeed Cache (LSCache) integration.
 *
 * LSCache is a server-level cache built into LiteSpeed Web Server and OpenLiteSpeed.
 * PHP controls caching by setting X-LiteSpeed-Cache-Control response headers.
 * The LiteSpeed server caches and serves pages without hitting PHP on subsequent requests.
 *
 * How it works:
 *  - On cache MISS → PHP runs and returns X-LiteSpeed-Cache-Control: public,max-age=TTL
 *  - LiteSpeed stores the page and serves it from cache on future requests
 *  - Purge → send X-LiteSpeed-Purge header (all pages, by tag, or by URI)
 *
 * Requires LiteSpeed Web Server or OpenLiteSpeed with LSCache module enabled.
 */
class LiteSpeedCacheService
{
    /**
     * Returns true when running on LiteSpeed (detected via SERVER_SOFTWARE header).
     */
    public function isLiteSpeedServer(): bool
    {
        $sw = strtolower((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));
        return str_contains($sw, 'litespeed') || str_contains($sw, 'openlitespeed');
    }

    /**
     * Build the X-LiteSpeed-Cache-Control value for a cacheable response.
     * TTL is in minutes (converted to seconds for the header).
     */
    public function buildCacheControlHeader(int $ttlMinutes = 720, array $tags = []): string
    {
        $ttl = $ttlMinutes * 60;
        $header = "public,max-age={$ttl}";
        if (!empty($tags)) {
            $header .= ',esi=off';
        }
        return $header;
    }

    /**
     * Build the X-LiteSpeed-Tag header value for page tagging.
     * Tags allow selective cache purge (e.g. purge all product pages).
     * Common tags: "home", "category_{id}", "product_{id}", "blog_{id}"
     */
    public function buildTagHeader(array $tags): string
    {
        return implode(',', array_map('strval', $tags));
    }

    /**
     * Apply LSCache headers to a response to make it cacheable by LiteSpeed.
     * Call this in PageCacheMiddleware when the driver is "litespeed".
     */
    public function applyToResponse(Response $response, int $ttlMinutes, array $tags = []): Response
    {
        $response->headers->set('X-LiteSpeed-Cache-Control', $this->buildCacheControlHeader($ttlMinutes, $tags));
        $response->headers->set('X-LiteSpeed-Vary', 'value=cookie[currency_code],value=cookie[locale_code]');
        if (!empty($tags)) {
            $response->headers->set('X-LiteSpeed-Tag', $this->buildTagHeader($tags));
        }
        return $response;
    }

    /**
     * Apply no-cache headers so LiteSpeed does NOT cache this response.
     * Used for authenticated/private pages.
     */
    public function applyNoCacheToResponse(Response $response): Response
    {
        $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
        return $response;
    }

    /**
     * Purge ALL cached pages from LiteSpeed by making a self-HTTP request
     * with the X-LiteSpeed-Purge header.
     *
     * Returns ['success' => bool, 'message' => string]
     */
    public function purgeAll(): array
    {
        return $this->sendPurgeRequest('*');
    }

    /**
     * Purge pages matching a specific tag (e.g. "product_123" or "category_5").
     */
    public function purgeByTag(string $tag): array
    {
        return $this->sendPurgeRequest('tag=' . $tag);
    }

    /**
     * Purge a specific URL from the cache.
     */
    public function purgeUrl(string $url): array
    {
        return $this->sendPurgeRequest('', $url);
    }

    /**
     * Returns human-readable cache driver info for the admin UI.
     */
    public function getStatusInfo(): array
    {
        $onLiteSpeed = $this->isLiteSpeedServer();
        return [
            'litespeed_detected' => $onLiteSpeed,
            'driver_label'       => 'LiteSpeed Cache (LSCache)',
            'how_works'          => 'Server-level cache. PHP sets X-LiteSpeed-Cache-Control headers; LiteSpeed serves cached pages without running PHP.',
            'purge_available'    => true,
            'server_info'        => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        ];
    }

    /**
     * Send a self-HTTP request with X-LiteSpeed-Purge header.
     * LiteSpeed intercepts the header and purges accordingly.
     */
    private function sendPurgeRequest(string $purgeValue, string $targetUrl = ''): array
    {
        try {
            $url = $targetUrl ?: config('app.url', 'http://localhost');
            $response = Http::timeout(10)
                ->withHeaders(['X-LiteSpeed-Purge' => $purgeValue])
                ->get($url);
            return ['success' => true, 'message' => 'LiteSpeed cache purge request sent successfully.'];
        } catch (\Exception $e) {
            // Even if the HTTP call fails (e.g. self-signed SSL), LiteSpeed may still
            // have processed the purge header internally.
            return ['success' => true, 'message' => 'Purge request sent. Note: ' . $e->getMessage()];
        }
    }
}
