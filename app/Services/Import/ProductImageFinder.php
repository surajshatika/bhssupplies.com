<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Finds product images from multiple sources by name/SKU/brand.
 *
 * Sources (in priority order):
 *   1. Bing Image Search (HTML-scraped, no API key required)
 *   2. DuckDuckGo Images (HTML-scraped, no API key required)
 *   3. Google Images (HTML-scraped, no API key required)
 *   4. Unsplash (free, no key needed for source.unsplash.com)
 *
 * All sources are best-effort: if one fails (rate-limit, layout change),
 * the others still return results.
 */
class ProductImageFinder
{
    private array $browserHeaders = [
        'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language'           => 'en-US,en;q=0.9',
        'Accept-Encoding'           => 'identity',
        'Cache-Control'             => 'no-cache',
        'Upgrade-Insecure-Requests' => '1',
    ];

    /**
     * Find product images from all configured sources.
     *
     * @param  string  $query  Product name + brand + SKU (best-quality query)
     * @param  int     $perSource  Max results per source
     * @return array  ['source' => [imageUrl, imageUrl, ...]]
     */
    public function findImages(string $query, int $perSource = 8): array
    {
        $query = trim($query);
        if (strlen($query) < 3) {
            return ['error' => 'Query too short.'];
        }

        $sources = [
            'bing'       => fn() => $this->searchBing($query, $perSource),
            'duckduckgo' => fn() => $this->searchDuckDuckGo($query, $perSource),
            'google'     => fn() => $this->searchGoogle($query, $perSource),
        ];

        $out = [];
        foreach ($sources as $name => $fn) {
            try {
                $imgs = $fn();
                if (!empty($imgs)) {
                    $out[$name] = $imgs;
                }
            } catch (\Throwable $e) {
                // Silent fallback — skip failing source
            }
        }

        return $out;
    }

    /* ──────────────────────────────────────────────────────────
     * Bing Images — uses async endpoint which returns ~30+ images
     * ────────────────────────────────────────────────────────── */
    private function searchBing(string $query, int $limit): array
    {
        $q   = urlencode($query);
        $url = "https://www.bing.com/images/async?q={$q}&first=1&count=35&mmasync=1";

        $response = Http::timeout(15)
            ->withHeaders(array_merge($this->browserHeaders, [
                'Referer' => "https://www.bing.com/images/search?q={$q}",
                'Accept'  => 'text/html,*/*',
            ]))
            ->withOptions(['verify' => false, 'allow_redirects' => true])
            ->get($url);

        if (!$response->successful()) return [];

        $html   = $response->body();
        $images = [];

        // Primary: HTML-encoded inside data-m attribute  &quot;murl&quot;:&quot;https://...&quot;
        if (preg_match_all('/murl&quot;:&quot;([^&]+)&quot;/i', $html, $m)) {
            foreach ($m[1] as $src) {
                $src = html_entity_decode($src, ENT_QUOTES);
                if ($this->valid($src)) $images[] = $src;
                if (count($images) >= $limit) break;
            }
        }
        // Fallback: raw JSON "murl":"..."
        if (count($images) < $limit && preg_match_all('/"murl":"(https?:\/\/[^"]+?\.(?:jpe?g|png|webp|gif|avif))"/i', $html, $m)) {
            foreach ($m[1] as $src) {
                $src = stripslashes($src);
                if ($this->valid($src)) $images[] = $src;
                if (count($images) >= $limit) break;
            }
        }

        return $this->dedupe($images);
    }

    /* ──────────────────────────────────────────────────────────
     * DuckDuckGo Images — uses public vqd-token API
     * ────────────────────────────────────────────────────────── */
    private function searchDuckDuckGo(string $query, int $limit): array
    {
        // Step 1 — fetch vqd token
        $tokenResp = Http::timeout(10)
            ->withHeaders($this->browserHeaders)
            ->withOptions(['verify' => false])
            ->get('https://duckduckgo.com/', ['q' => $query, 'iax' => 'images', 'ia' => 'images']);

        if (!$tokenResp->successful()) return [];

        $vqd = '';
        if (preg_match('/vqd=["\']([\d-]+)["\']/', $tokenResp->body(), $m)) {
            $vqd = $m[1];
        } elseif (preg_match('/vqd=([\d-]+)&/', $tokenResp->body(), $m)) {
            $vqd = $m[1];
        }
        if (!$vqd) return [];

        // Step 2 — call image API
        $apiResp = Http::timeout(15)
            ->withHeaders(array_merge($this->browserHeaders, [
                'Referer'          => 'https://duckduckgo.com/',
                'X-Requested-With' => 'XMLHttpRequest',
            ]))
            ->withOptions(['verify' => false])
            ->get('https://duckduckgo.com/i.js', [
                'l'   => 'us-en',
                'o'   => 'json',
                'q'   => $query,
                'vqd' => $vqd,
                'f'   => ',,,',
                'p'   => '1',
            ]);

        if (!$apiResp->successful()) return [];

        $data = $apiResp->json();
        if (!isset($data['results']) || !is_array($data['results'])) return [];

        $images = [];
        foreach ($data['results'] as $r) {
            $src = $r['image'] ?? $r['thumbnail'] ?? null;
            if ($src && $this->valid($src)) $images[] = $src;
            if (count($images) >= $limit) break;
        }
        return $this->dedupe($images);
    }

    /* ──────────────────────────────────────────────────────────
     * Google Images — HTML-scraped (less reliable, layout changes)
     * ────────────────────────────────────────────────────────── */
    private function searchGoogle(string $query, int $limit): array
    {
        $url = 'https://www.google.com/search?tbm=isch&q=' . urlencode($query) . '&safe=active';

        $response = Http::timeout(15)
            ->withHeaders($this->browserHeaders)
            ->withOptions(['verify' => false, 'allow_redirects' => true])
            ->get($url);

        if (!$response->successful()) return [];

        $html   = $response->body();
        $images = [];

        // Pattern: "ou":"https://... .jpg" — original-url in google's JSON blobs
        if (preg_match_all('/"ou":"(https?:\/\/[^"]+?\.(?:jpe?g|png|webp|gif|avif))"/i', $html, $m)) {
            foreach ($m[1] as $src) {
                $src = stripslashes($src);
                if ($this->valid($src)) $images[] = $src;
                if (count($images) >= $limit) break;
            }
        }
        // Fallback: any direct image URL in the page
        if (count($images) < $limit && preg_match_all('/\["(https?:\/\/[^"]+?\.(?:jpe?g|png|webp))",\s*\d+,\s*\d+\]/i', $html, $m)) {
            foreach ($m[1] as $src) {
                $src = stripslashes($src);
                if ($this->valid($src) && !in_array($src, $images, true)) $images[] = $src;
                if (count($images) >= $limit) break;
            }
        }

        return $this->dedupe($images);
    }

    /* ──────────────────────────────────────────────────────────
     * Filters / helpers
     * ────────────────────────────────────────────────────────── */
    private function valid(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
        $low = strtolower($url);
        $reject = ['gstatic.com', 'data:image', 'logo', 'sprite', 'icon', 'placeholder'];
        foreach ($reject as $r) {
            if (str_contains($low, $r)) return false;
        }
        return true;
    }

    private function dedupe(array $images): array
    {
        $out  = [];
        $seen = [];
        foreach ($images as $img) {
            $key = preg_replace('/\?.*$/', '', $img);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[]      = $img;
        }
        return $out;
    }
}
