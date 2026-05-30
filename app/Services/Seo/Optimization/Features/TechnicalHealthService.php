<?php

namespace App\Services\Seo\Optimization\Features;

use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Real, measurement-based technical SEO checks — the deterministic counterpart
 * to the AI-only TechnicalAuditService.
 *
 * Every check here is computed from an actual HTTP fetch of the page (timing
 * from cURL handler stats) and a structural parse of the returned HTML, so the
 * results map one-to-one to what external auditors (Seobility, SEO Site
 * Checkup, SEOptimer) report:
 *
 *   - Time To First Byte (TTFB)            cURL starttransfer_time
 *   - First / Largest Contentful Paint     PageSpeed Insights field data (if key set)
 *   - Render-Blocking Resources            <head> CSS/JS without async/defer
 *   - Unsafe Cross-Origin Links            target=_blank without rel=noopener
 *   - Plaintext Emails                     mailto:/raw addresses exposed to scrapers
 *   - Ads.txt Validation                   /ads.txt reachable
 *   - Mixed Content                        http:// assets on an https page
 *   - Image weight / lazy-loading / dims   <img> audit
 *   - DOM size & request count             node/asset counts
 */
class TechnicalHealthService
{
    public function run(array $payload): array
    {
        $url = $this->normalizeUrl($payload['url'] ?? url('/'));
        $isHttps = str_starts_with($url, 'https://');
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        $fetch = $this->fetchPage($url);
        $html  = $fetch['html'];
        $checks = [];

        // ── Performance / Core Web Vitals ──────────────────────────────────
        $checks[] = $this->ttfbCheck($fetch['ttfb']);
        $checks[] = $this->loadTimeCheck($fetch['total_time']);

        $psi = $this->fetchPageSpeed($url);
        $checks[] = $this->cwvCheck('fcp', 'First Contentful Paint', $psi['fcp'] ?? null, 1.8, 3.0);
        $checks[] = $this->cwvCheck('lcp', 'Largest Contentful Paint', $psi['lcp'] ?? null, 2.5, 4.0);
        $checks[] = $this->cwvCheck('cls', 'Cumulative Layout Shift', $psi['cls'] ?? null, 0.1, 0.25, false, '');

        // ── HTML structure / requests ──────────────────────────────────────
        if ($html !== '') {
            $head = $this->headSection($html);
            $checks[] = $this->renderBlockingCheck($head);
            $checks[] = $this->unsafeLinksCheck($html);
            $checks[] = $this->plaintextEmailCheck($html);
            $checks[] = $this->mixedContentCheck($html, $isHttps);
            $checks[] = $this->imageCheck($html);
            $checks[] = $this->domSizeCheck($html);
            $checks[] = $this->requestCountCheck($html);
            $checks[] = $this->viewportCheck($head);
        }

        // ── Crawlable files ────────────────────────────────────────────────
        $base = rtrim(($isHttps ? 'https://' : 'http://') . $host, '/');
        $checks[] = $this->fileCheck('ads_txt', 'Ads.txt Validation', $base . '/ads.txt');
        $checks[] = $this->fileCheck('robots_txt', 'Robots.txt', $base . '/robots.txt');
        $checks[] = $this->fileCheck('sitemap', 'XML Sitemap', $base . '/sitemap.xml');

        $checks = array_values(array_filter($checks));

        return [
            'url'            => $url,
            'fetched'        => $fetch['ok'],
            'score'          => $this->score($checks),
            'grade'          => $this->grade($this->score($checks)),
            'ttfb_seconds'   => $fetch['ttfb'],
            'load_seconds'   => $fetch['total_time'],
            'page_bytes'     => $fetch['size'],
            'checks'         => $checks,
            'summary'        => $this->summary($checks),
            'measured_at'    => now()->toDateTimeString(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Fetching
    // ──────────────────────────────────────────────────────────────────────

    protected function fetchPage(string $url): array
    {
        $out = ['ok' => false, 'html' => '', 'ttfb' => null, 'total_time' => null, 'size' => 0];

        try {
            $stats = null;
            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'BHS-SEO-HealthBot/1.0 (+technical-audit)'])
                ->withOptions([
                    'verify'   => (bool) config('seo.ssl_verify', false),
                    'on_stats' => function (TransferStats $s) use (&$stats) {
                        $stats = $s;
                    },
                ])
                ->get($url);

            $handler = $stats ? $stats->getHandlerStats() : [];
            $out['ok']         = $response->successful();
            $out['html']       = $response->body();
            $out['ttfb']       = isset($handler['starttransfer_time']) ? round((float) $handler['starttransfer_time'], 3) : null;
            $out['total_time'] = isset($handler['total_time'])
                ? round((float) $handler['total_time'], 3)
                : ($stats ? round((float) $stats->getTransferTime(), 3) : null);
            $out['size']       = isset($handler['size_download']) ? (int) $handler['size_download'] : strlen($out['html']);
        } catch (Throwable $e) {
            logger()->info('TechnicalHealthService fetch failed', ['url' => $url, 'err' => $e->getMessage()]);
        }

        return $out;
    }

    /** Pull real FCP/LCP/CLS field data from PageSpeed Insights when a key is set. */
    protected function fetchPageSpeed(string $url): array
    {
        $key = config('services.google.pagespeed_key');
        if (!$key) {
            return [];
        }
        try {
            $res = Http::timeout(25)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url' => $url, 'key' => $key, 'strategy' => 'mobile',
            ]);
            if (!$res->successful()) {
                return [];
            }
            $metrics = $res->json('loadingExperience.metrics') ?? [];
            return [
                'fcp' => isset($metrics['FIRST_CONTENTFUL_PAINT_MS']['percentile']) ? $metrics['FIRST_CONTENTFUL_PAINT_MS']['percentile'] / 1000 : null,
                'lcp' => isset($metrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile']) ? $metrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile'] / 1000 : null,
                'cls' => isset($metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile']) ? $metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'] / 100 : null,
            ];
        } catch (Throwable $e) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Individual checks
    // ──────────────────────────────────────────────────────────────────────

    protected function ttfbCheck(?float $ttfb): array
    {
        if ($ttfb === null) {
            return $this->result('ttfb', 'Time To First Byte', 'warning', 'medium', 'TTFB could not be measured.', 'Ensure the page is reachable from the server.');
        }
        $status = $ttfb <= 0.8 ? 'pass' : ($ttfb <= 1.8 ? 'warning' : 'fail');
        return $this->result('ttfb', 'Time To First Byte', $status, $ttfb > 1.8 ? 'high' : 'medium',
            sprintf('TTFB is %.2fs (target ≤ 0.8s).', $ttfb),
            'Add server caching (full-page/OPcache), tune queries, and use a CDN to cut TTFB.', $ttfb);
    }

    protected function loadTimeCheck(?float $total): array
    {
        if ($total === null) {
            return $this->result('load_time', 'Page Load Time', 'warning', 'medium', 'Load time could not be measured.', 'Verify the page responds without errors.');
        }
        $status = $total <= 2.5 ? 'pass' : ($total <= 5.0 ? 'warning' : 'fail');
        return $this->result('load_time', 'Page Load Time', $status, $total > 5 ? 'high' : 'medium',
            sprintf('Full document fetched in %.2fs.', $total),
            'Reduce payload, compress images, and defer non-critical JS/CSS.', $total);
    }

    protected function cwvCheck(string $id, string $label, ?float $value, float $good, float $poor, bool $seconds = true, string $unit = 's'): array
    {
        if ($value === null) {
            return $this->result($id, $label, 'warning', 'low',
                'Not measured — set services.google.pagespeed_key to pull real field data.',
                'Add a Google PageSpeed Insights API key to capture CrUX field metrics.');
        }
        $status = $value <= $good ? 'pass' : ($value <= $poor ? 'warning' : 'fail');
        return $this->result($id, $label, $status, $value > $poor ? 'high' : 'medium',
            sprintf('%s field value: %s%s (good ≤ %s%s).', $label, $this->fmt($value), $unit, $this->fmt($good), $unit),
            'Optimise the largest paint element, preconnect to origins, and reserve image dimensions.', $value);
    }

    protected function renderBlockingCheck(string $head): array
    {
        $blockingCss = preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $head, $cssM);
        $cssBlocking = 0;
        foreach ($cssM[0] ?? [] as $tag) {
            if (!preg_match('/\bmedia=["\']print["\']/i', $tag) && !str_contains($tag, 'preload')) {
                $cssBlocking++;
            }
        }
        $jsBlocking = 0;
        if (preg_match_all('/<script\b[^>]*\bsrc=["\'][^"\']+["\'][^>]*>/i', $head, $jsM)) {
            foreach ($jsM[0] as $tag) {
                if (!preg_match('/\b(async|defer|type=["\']module["\'])\b/i', $tag)) {
                    $jsBlocking++;
                }
            }
        }
        $total = $cssBlocking + $jsBlocking;
        $status = $total === 0 ? 'pass' : ($total <= 3 ? 'warning' : 'fail');
        return $this->result('render_blocking', 'Render-Blocking Resources', $status, $total > 3 ? 'high' : 'medium',
            sprintf('%d render-blocking resource(s) in <head> (%d CSS, %d JS).', $total, $cssBlocking, $jsBlocking),
            'Inline critical CSS, async/defer scripts, and preload async stylesheets (see app/Http/SeoHelpers.php).', $total);
    }

    protected function unsafeLinksCheck(string $html): array
    {
        $unsafe = 0;
        if (preg_match_all('/<a\b[^>]*target=["\']_blank["\'][^>]*>/i', $html, $m)) {
            foreach ($m[0] as $tag) {
                if (!preg_match('/\brel=["\'][^"\']*noopener[^"\']*["\']/i', $tag)) {
                    $unsafe++;
                }
            }
        }
        $status = $unsafe === 0 ? 'pass' : ($unsafe <= 3 ? 'warning' : 'fail');
        return $this->result('unsafe_links', 'Unsafe Cross-Origin Links', $status, 'medium',
            $unsafe === 0
                ? 'All target="_blank" links use rel="noopener".'
                : sprintf('%d link(s) open a new tab without rel="noopener" (security/perf risk).', $unsafe),
            'Add rel="noopener noreferrer" to every target="_blank" anchor.', $unsafe);
    }

    protected function plaintextEmailCheck(string $html): array
    {
        $mailto = preg_match_all('/mailto:[^"\'>\s]+/i', $html, $mm) ? count($mm[0]) : 0;
        // Raw addresses sitting in text (not already inside a mailto:).
        $stripped = preg_replace('/mailto:[^"\'>\s]+/i', '', $html);
        $raw = preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', strip_tags($stripped));
        $exposed = $mailto + $raw;
        $status = $exposed === 0 ? 'pass' : 'warning';
        return $this->result('plaintext_emails', 'Plaintext Emails', $status, 'low',
            $exposed === 0
                ? 'No scraper-harvestable email addresses found in the markup.'
                : sprintf('%d plaintext email reference(s) exposed to scrapers.', $exposed),
            'Obfuscate addresses (entity-encode, JS-render, or use a contact form) to cut spam harvesting.', $exposed);
    }

    protected function mixedContentCheck(string $html, bool $isHttps): array
    {
        if (!$isHttps) {
            return $this->result('mixed_content', 'HTTPS / Mixed Content', 'fail', 'high',
                'Page is served over HTTP.', 'Serve the entire site over HTTPS and redirect HTTP → HTTPS.');
        }
        $count = 0;
        if (preg_match_all('/(?:src|href)=["\'](http:\/\/[^"\']+)["\']/i', $html, $m)) {
            foreach ($m[1] as $u) {
                // Ignore schema.org / xmlns context URLs.
                if (!preg_match('#http://(www\.)?(schema\.org|www\.w3\.org)#i', $u)) {
                    $count++;
                }
            }
        }
        $status = $count === 0 ? 'pass' : 'fail';
        return $this->result('mixed_content', 'HTTPS / Mixed Content', $status, $count > 0 ? 'high' : 'low',
            $count === 0 ? 'No insecure http:// assets on the page.' : sprintf('%d insecure http:// asset(s) on an HTTPS page.', $count),
            'Load every script, style, image, and iframe over https://.', $count);
    }

    protected function imageCheck(string $html): array
    {
        $imgs = preg_match_all('/<img\b[^>]*>/i', $html, $m) ? $m[0] : [];
        $total = count($imgs);
        if ($total === 0) {
            return $this->result('images', 'Image Optimization', 'pass', 'low', 'No <img> tags to audit.', '');
        }
        $missingAlt = $missingDims = $noLazy = 0;
        foreach ($imgs as $i => $tag) {
            if (!preg_match('/\balt=/i', $tag)) {
                $missingAlt++;
            }
            if (!preg_match('/\bwidth=/i', $tag) || !preg_match('/\bheight=/i', $tag)) {
                $missingDims++;
            }
            // First image is usually the LCP hero — lazy-loading it hurts LCP.
            if ($i > 0 && !preg_match('/\bloading=["\']lazy["\']/i', $tag)) {
                $noLazy++;
            }
        }
        $issues = $missingAlt + $missingDims;
        $status = $issues === 0 ? 'pass' : ($issues <= $total * 0.3 ? 'warning' : 'fail');
        return $this->result('images', 'Image Optimization', $status, 'medium',
            sprintf('%d image(s): %d missing alt, %d missing width/height, %d not lazy-loaded.', $total, $missingAlt, $missingDims, $noLazy),
            'Add alt text + explicit width/height (prevents CLS) and lazy-load below-the-fold images. Serve WebP/AVIF.', $issues);
    }

    protected function domSizeCheck(string $html): array
    {
        $nodes = preg_match_all('/<[a-z][a-z0-9]*\b/i', $html);
        $status = $nodes <= 1500 ? 'pass' : ($nodes <= 3000 ? 'warning' : 'fail');
        return $this->result('dom_size', 'DOM Size', $status, $nodes > 3000 ? 'medium' : 'low',
            sprintf('Approximately %d DOM elements (recommended ≤ 1,500).', $nodes),
            'Simplify markup, paginate long lists, and remove wrapper-div nesting.', $nodes);
    }

    protected function requestCountCheck(string $html): array
    {
        $css = preg_match_all('/<link[^>]+rel=["\']stylesheet["\']/i', $html);
        $js  = preg_match_all('/<script\b[^>]*\bsrc=/i', $html);
        $img = preg_match_all('/<img\b[^>]*\bsrc=/i', $html);
        $total = $css + $js + $img;
        $status = $total <= 20 ? 'pass' : ($total <= 50 ? 'warning' : 'fail');
        return $this->result('requests', 'Asset Requests', $status, 'low',
            sprintf('~%d asset requests in markup (%d CSS, %d JS, %d images).', $total, $css, $js, $img),
            'Bundle CSS/JS, use sprites/icon-fonts, and lazy-load images to cut requests.', $total);
    }

    protected function viewportCheck(string $head): array
    {
        $ok = (bool) preg_match('/<meta[^>]+name=["\']viewport["\']/i', $head);
        return $this->result('viewport', 'Mobile Viewport', $ok ? 'pass' : 'fail', $ok ? 'low' : 'high',
            $ok ? 'Responsive viewport meta tag present.' : 'Missing <meta name="viewport">.',
            'Add <meta name="viewport" content="width=device-width, initial-scale=1">.');
    }

    protected function fileCheck(string $id, string $label, string $url): array
    {
        try {
            $res = Http::timeout(10)->withOptions(['verify' => (bool) config('seo.ssl_verify', false)])->get($url);
            $ok = $res->successful() && trim($res->body()) !== '';
            return $this->result($id, $label, $ok ? 'pass' : 'fail', $id === 'ads_txt' ? 'low' : 'medium',
                $ok ? "{$label} is reachable at {$url}." : "{$label} is missing or empty ({$url}).",
                $ok ? '' : "Publish a valid {$label} at the site root.");
        } catch (Throwable $e) {
            return $this->result($id, $label, 'warning', 'low', "Could not verify {$label}.", "Confirm {$url} is reachable.");
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    protected function headSection(string $html): string
    {
        if (preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $m)) {
            return $m[1];
        }
        return substr($html, 0, 8000);
    }

    protected function result(string $id, string $label, string $status, string $severity, string $message, string $fix, $value = null): array
    {
        return compact('id', 'label', 'status', 'severity', 'message', 'fix', 'value');
    }

    protected function score(array $checks): int
    {
        if (empty($checks)) {
            return 0;
        }
        $earned = 0;
        $total  = 0;
        foreach ($checks as $c) {
            $weight = match ($c['severity']) { 'high' => 3, 'medium' => 2, default => 1 };
            $total += $weight;
            $earned += match ($c['status']) { 'pass' => $weight, 'warning' => $weight * 0.5, default => 0 };
        }
        return $total > 0 ? (int) round(($earned / $total) * 100) : 0;
    }

    protected function summary(array $checks): array
    {
        $s = ['pass' => 0, 'warning' => 0, 'fail' => 0];
        foreach ($checks as $c) {
            $s[$c['status']] = ($s[$c['status']] ?? 0) + 1;
        }
        return $s;
    }

    protected function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default      => 'F',
        };
    }

    protected function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return rtrim(url('/'), '/');
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = rtrim(url('/'), '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }
}
