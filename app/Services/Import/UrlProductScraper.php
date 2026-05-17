<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UrlProductScraper
{
    private array $defaultSelectors = [
        'name'        => ['h1', '.product-title', '.product-name', '[itemprop="name"]'],
        'price'       => ['.price', '.product-price', '[itemprop="price"]', '.woocommerce-Price-amount'],
        'description' => ['.product-description', '.description', '[itemprop="description"]', '#product-description', '.product-short-description'],
        'sku'         => ['.sku', '[itemprop="sku"]', '.product-sku'],
        'image'       => ['img.product-image', '.product-gallery img', '[itemprop="image"]', '.wp-post-image'],
    ];

    /** Browser-like headers to avoid basic bot detection */
    private array $browserHeaders = [
        'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language'           => 'en-US,en;q=0.9',
        'Accept-Encoding'           => 'identity',
        'Connection'                => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
        'Sec-Fetch-Dest'            => 'document',
        'Sec-Fetch-Mode'            => 'navigate',
        'Sec-Fetch-Site'            => 'none',
        'Cache-Control'             => 'max-age=0',
    ];

    /**
     * Scrape a single product URL.
     * Falls back to URL-path extraction when the site blocks scraping (403/JS-rendered).
     */
    public function scrapeUrl(string $url, array $selectorOverrides = []): array
    {
        $url = filter_var(trim($url), FILTER_VALIDATE_URL) ? trim($url) : null;
        if (!$url) {
            return ['error' => 'Invalid URL provided.', 'url' => $url];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders($this->browserHeaders)
                ->withOptions(['verify' => false, 'allow_redirects' => true])
                ->get($url);

            if ($response->successful()) {
                $html    = $response->body();
                $product = $this->parseHtml($html, $url, $selectorOverrides);

                if (!empty($product['name'])) {
                    return $product;
                }
            }

            // Blocked (403/503/JS-only SPA) — fall back to URL-path extraction
            return $this->extractFromUrlPath($url);

        } catch (\Throwable $e) {
            return $this->extractFromUrlPath($url);
        }
    }

    /**
     * Extract product data from the URL path segments when HTML scraping fails.
     */
    private function extractFromUrlPath(string $url): array
    {
        $path     = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $segments = array_values(array_filter(explode('/', $path)));

        $generic  = ['products', 'product', 'shop', 'store', 'catalog', 'item', 'items', 'p'];
        $segments = array_values(array_filter($segments, fn($s) => !in_array(strtolower($s), $generic)));

        if (empty($segments)) {
            return ['error' => 'Could not extract product info from URL.', 'url' => $url];
        }

        $lastSeg  = end($segments);
        $isSku    = preg_match('/^[A-Z0-9\-]{3,20}$/i', $lastSeg) && !preg_match('/^[a-z\-]+$/', $lastSeg);
        $sku      = $isSku ? strtoupper($lastSeg) : null;

        $nameSlug = $isSku && count($segments) >= 2
            ? $segments[count($segments) - 2]
            : $lastSeg;

        $catSlug  = count($segments) >= 2 ? $segments[0] : null;

        $name     = ucwords(str_replace(['-', '_'], ' ', $nameSlug));
        $category = $catSlug ? ucwords(str_replace(['-', '_'], ' ', $catSlug)) : '';

        return array_filter([
            'url'           => $url,
            'name'          => $name,
            'sku'           => $sku,
            'category'      => $category,
            'import_source' => 'url-path',
        ]);
    }

    /**
     * Scrape multiple URLs with polite crawl delay.
     */
    public function scrapeUrls(array $urls, array $selectorOverrides = []): array
    {
        $results = [];
        foreach ($urls as $url) {
            $results[] = $this->scrapeUrl($url, $selectorOverrides);
            usleep(500000); // 0.5s polite delay
        }
        return [
            'products'      => array_filter($results, fn($r) => !isset($r['error'])),
            'errors'        => array_filter($results, fn($r) => isset($r['error'])),
            'total_scraped' => count(array_filter($results, fn($r) => !isset($r['error']))),
            'total_errors'  => count(array_filter($results, fn($r) => isset($r['error']))),
        ];
    }

    private function parseHtml(string $html, string $url, array $overrides): array
    {
        $product = [
            'url'         => $url,
            'name'        => $this->extractByPatterns($html, 'name'),
            'price'       => $this->extractPrice($html),
            'description' => $this->extractByPatterns($html, 'description'),
            'sku'         => $this->extractByPatterns($html, 'sku'),
            'image'       => $this->extractImage($html, $url),
        ];

        // Meta tag fallbacks
        if (!$product['name']) {
            $product['name'] = $this->extractMeta($html, 'og:title')
                ?: $this->extractMeta($html, 'twitter:title')
                ?: $this->extractTitle($html);
        }
        if (!$product['description']) {
            $product['description'] = $this->extractMeta($html, 'og:description')
                ?: $this->extractMeta($html, 'description');
        }
        if (!$product['image']) {
            $product['image'] = $this->extractMeta($html, 'og:image')
                ?: $this->extractMeta($html, 'twitter:image');
        }

        // JSON-LD Product schema — most reliable source
        $jsonLd = $this->extractJsonLd($html, $url);
        if ($jsonLd) {
            // Merge but don't overwrite good values already found
            foreach ($jsonLd as $key => $val) {
                if (empty($product[$key]) && !empty($val)) {
                    $product[$key] = $val;
                }
            }
            // Always prefer JSON-LD price and SKU (more structured)
            if (!empty($jsonLd['price']))  $product['price']  = $jsonLd['price'];
            if (!empty($jsonLd['sku']))    $product['sku']    = $jsonLd['sku'];
            if (!empty($jsonLd['brand']))  $product['brand']  = $jsonLd['brand'];
            if (!empty($jsonLd['gtin']))   $product['gtin']   = $jsonLd['gtin'];
            if (!empty($jsonLd['images'])) $product['images'] = $jsonLd['images'];
        }

        // Clean up name (remove site name suffix like " | Brand")
        if (!empty($product['name'])) {
            $product['name'] = $this->cleanProductName($product['name']);
        }

        // Resolve relative image URL
        if (!empty($product['image'])) {
            $product['image'] = $this->absoluteUrl($product['image'], $url);
        }

        // Collect ALL candidate images from the page (gallery, thumbnails, og, twitter, schema)
        $allImages = $this->extractAllImages($html, $url);
        if (!empty($product['image'])) {
            array_unshift($allImages, $product['image']);
        }
        if (!empty($product['images']) && is_array($product['images'])) {
            $allImages = array_merge($product['images'], $allImages);
        }
        $product['images'] = $this->dedupeImages($allImages);

        return array_filter($product, fn($v) => $v !== null && $v !== '' && $v !== []);
    }

    /**
     * Extract every image candidate from a product page — gallery, thumbnails, og:image,
     * twitter:image, JSON-LD, plus inline <img> tags that look like product imagery.
     */
    private function extractAllImages(string $html, string $pageUrl): array
    {
        $images = [];

        // og:image / twitter:image (sometimes multiple og:image meta tags)
        if (preg_match_all('/<meta[^>]+(?:property|name)=["\'](?:og:image(?::secure_url)?|twitter:image)["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            foreach ($m[1] as $src) $images[] = $src;
        }

        // <link rel="image_src">
        if (preg_match_all('/<link[^>]+rel=["\']image_src["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
            foreach ($m[1] as $src) $images[] = $src;
        }

        // data-zoom-image, data-large-image, data-large_image, data-src
        if (preg_match_all('/data-(?:zoom-image|large(?:[-_]image)?|original|src)=["\']([^"\']{15,})["\']/i', $html, $m)) {
            foreach ($m[1] as $src) $images[] = $src;
        }

        // <a href="...jpg|png|webp"> — common gallery anchor wrap
        if (preg_match_all('/<a[^>]+href=["\']([^"\']+\.(?:jpe?g|png|webp|avif))(?:\?[^"\']*)?["\']/i', $html, $m)) {
            foreach ($m[1] as $src) $images[] = $src;
        }

        // All <img src=...> — filtered to plausible product imagery
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            foreach ($m[1] as $src) $images[] = $src;
        }

        // srcset largest candidate
        if (preg_match_all('/srcset=["\']([^"\']+)["\']/i', $html, $m)) {
            foreach ($m[1] as $set) {
                $parts = preg_split('/\s*,\s*/', $set);
                $last  = end($parts);
                if ($last) {
                    $url = trim(preg_split('/\s+/', trim($last))[0] ?? '');
                    if ($url) $images[] = $url;
                }
            }
        }

        // Resolve to absolute URLs and filter out junk (icons, sprites, tracking pixels, base64)
        $absolute = [];
        foreach ($images as $src) {
            $src = trim($src);
            if ($src === '' || str_starts_with($src, 'data:')) continue;
            $abs = $this->absoluteUrl($src, $pageUrl);
            if ($this->isLikelyProductImage($abs)) {
                $absolute[] = $abs;
            }
        }

        return $this->dedupeImages($absolute);
    }

    private function isLikelyProductImage(string $url): bool
    {
        $low = strtolower($url);
        // Reject obvious non-product assets
        $reject = ['logo', 'sprite', 'icon', 'favicon', 'pixel', 'spacer', 'blank', 'placeholder',
                   'avatar', 'badge', 'button', 'arrow', 'banner', 'paypal', 'visa', 'mastercard',
                   'instagram', 'facebook', 'twitter-icon', '/wp-includes/', '/wp-content/themes/',
                   '/assets/icons/', 'tracking', 'analytics', 'pinterest', 'youtube'];
        foreach ($reject as $r) {
            if (str_contains($low, $r)) return false;
        }
        // Must be plausible image extension OR a CDN URL with no extension
        if (preg_match('#\.(jpe?g|png|webp|avif|gif)(\?|$)#i', $low)) return true;
        if (preg_match('#/(images?|media|cdn|assets|uploads?|catalog|product)/#', $low)) return true;
        return false;
    }

    private function dedupeImages(array $images): array
    {
        $seen = [];
        $out  = [];
        foreach ($images as $img) {
            // Strip query string for dedup but keep original
            $key = preg_replace('/\?.*$/', '', $img);
            // Normalize trailing -size suffixes (e.g. -300x300, _thumb, _small)
            $key = preg_replace('/[-_](?:\d+x\d+|thumb|thumbnail|small|medium|large|xl)(?=\.[a-z]+$)/i', '', $key);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[]      = $img;
            if (count($out) >= 20) break;
        }
        return $out;
    }

    private function extractByPatterns(string $html, string $field): ?string
    {
        $patterns = [
            'name'        => [
                '/<h1[^>]*class="[^"]*product[^"]*"[^>]*>([\s\S]{3,200}?)<\/h1>/i',
                '/<h1[^>]*itemprop="name"[^>]*>([\s\S]{3,200}?)<\/h1>/i',
                '/<h1[^>]*>([\s\S]{3,200}?)<\/h1>/i',
                '/itemprop="name"[^>]*>([^<]{3,200})</i',
            ],
            'description' => [
                '/<div[^>]*itemprop="description"[^>]*>([\s\S]{10,2000}?)<\/div>/i',
                '/<p[^>]*itemprop="description"[^>]*>([\s\S]{10,1000}?)<\/p>/i',
                '/<div[^>]*class="[^"]*product-description[^"]*"[^>]*>([\s\S]{10,2000}?)<\/div>/i',
                '/<div[^>]*id="product-description"[^>]*>([\s\S]{10,2000}?)<\/div>/i',
            ],
            'sku'         => [
                '/itemprop="sku"[^>]*>([^<]{2,50})</i',
                '/class="[^"]*sku[^"]*"[^>]*>([^<]{2,50})</i',
                '/<span[^>]*class="[^"]*model[^"]*"[^>]*>([^<]{2,50})<\/span>/i',
            ],
        ][$field] ?? [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $value = trim(strip_tags($m[1]));
                if (strlen($value) >= 2) {
                    return $value;
                }
            }
        }
        return null;
    }

    private function extractPrice(string $html): ?float
    {
        // Schema.org price content attribute (most reliable)
        if (preg_match('/itemprop="price"[^>]*content="([\d.]+)"/i', $html, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/content="([\d.]+)"[^>]*itemprop="price"/i', $html, $m)) {
            return (float) $m[1];
        }
        // WooCommerce / common price patterns
        if (preg_match('/class="[^"]*price[^"]*"[^>]*>[\s\S]{0,50}?[\$£€]\s*([\d,]+\.?\d{0,2})/i', $html, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }
        // Generic currency pattern
        if (preg_match('/[\$£€]\s*([\d,]+\.\d{2})\b/', $html, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }
        return null;
    }

    private function extractImage(string $html, string $pageUrl): ?string
    {
        // itemprop="image"
        if (preg_match('/itemprop="image"[^>]*(?:src|content)="([^"]{10,}?)"/i', $html, $m)) {
            return $this->absoluteUrl($m[1], $pageUrl);
        }
        // data-zoom-image or data-large (common in product galleries)
        if (preg_match('/data-(?:zoom-image|large(?:-image)?)="([^"]{10,}?)"/i', $html, $m)) {
            return $this->absoluteUrl($m[1], $pageUrl);
        }
        // WooCommerce gallery image
        if (preg_match('/<img[^>]*class="[^"]*wp-post-image[^"]*"[^>]*src="([^"]+)"/i', $html, $m)) {
            return $this->absoluteUrl($m[1], $pageUrl);
        }
        // Product image class
        if (preg_match('/<img[^>]*class="[^"]*product[^"]*"[^>]*src="([^"]{10,}?)"/i', $html, $m)) {
            return $this->absoluteUrl($m[1], $pageUrl);
        }
        return null;
    }

    private function extractMeta(string $html, string $name): ?string
    {
        if (preg_match('/<meta[^>]+(?:name|property)="'.preg_quote($name, '/').'"[^>]+content="([^"]+)"/i', $html, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/<meta[^>]+content="([^"]+)"[^>]+(?:name|property)="'.preg_quote($name, '/').'"[^>]*/i', $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
            return $this->cleanProductName(trim($m[1]));
        }
        return null;
    }

    private function extractJsonLd(string $html, string $pageUrl): ?array
    {
        if (!preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>([\s\S]+?)<\/script>/i', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            try {
                $data = json_decode(trim($json), true);
                if (!is_array($data)) continue;

                // Handle @graph arrays (common in Yoast SEO)
                if (isset($data['@graph']) && is_array($data['@graph'])) {
                    foreach ($data['@graph'] as $node) {
                        if (isset($node['@type']) && strtolower($node['@type']) === 'product') {
                            $data = $node;
                            break;
                        }
                    }
                }

                if (!isset($data['@type']) || strtolower($data['@type']) !== 'product') {
                    continue;
                }

                // Extract all images
                $images = [];
                if (!empty($data['image'])) {
                    $imgData = $data['image'];
                    if (is_string($imgData)) {
                        $images[] = $this->absoluteUrl($imgData, $pageUrl);
                    } elseif (is_array($imgData)) {
                        foreach ($imgData as $img) {
                            $src = is_array($img) ? ($img['url'] ?? $img['contentUrl'] ?? '') : $img;
                            if ($src) $images[] = $this->absoluteUrl($src, $pageUrl);
                        }
                    }
                }

                // Price from offers
                $price = null;
                if (!empty($data['offers'])) {
                    $offers = isset($data['offers']['@type']) ? [$data['offers']] : $data['offers'];
                    foreach ((array) $offers as $offer) {
                        if (!empty($offer['price'])) {
                            $price = (float) $offer['price'];
                            break;
                        }
                    }
                }

                return array_filter([
                    'name'        => $data['name'] ?? null,
                    'description' => is_string($data['description'] ?? null)
                                        ? Str::limit(strip_tags($data['description']), 1000)
                                        : null,
                    'sku'         => $data['sku'] ?? $data['mpn'] ?? null,
                    'price'       => $price,
                    'image'       => $images[0] ?? null,
                    'images'      => $images ?: null,
                    'brand'       => $data['brand']['name'] ?? (is_string($data['brand'] ?? null) ? $data['brand'] : null),
                    'gtin'        => $data['gtin'] ?? $data['gtin13'] ?? $data['gtin12'] ?? $data['gtin8'] ?? null,
                ]);

            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Remove common site-name suffixes: "Product Name | Site Name" or "Product Name - Site"
     */
    private function cleanProductName(string $name): string
    {
        // Remove trailing " | Something" or " - Something" if the name has content before it
        $cleaned = preg_replace('/\s*[\|–—]\s*.{3,60}$/', '', $name);
        $cleaned = trim($cleaned ?: $name);
        // Decode HTML entities
        return html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function absoluteUrl(string $url, string $pageUrl): string
    {
        $url = trim($url);
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }
        if (Str::startsWith($url, '//')) {
            $scheme = parse_url($pageUrl, PHP_URL_SCHEME) ?? 'https';
            return $scheme . ':' . $url;
        }
        $base   = parse_url($pageUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host   = $base['host']   ?? '';
        return Str::startsWith($url, '/') ? "{$scheme}://{$host}{$url}" : "{$scheme}://{$host}/{$url}";
    }
}
