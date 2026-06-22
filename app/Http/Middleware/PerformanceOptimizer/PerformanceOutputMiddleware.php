<?php

namespace App\Http\Middleware\PerformanceOptimizer;

use App\Services\PerformanceOptimizer\CssJsMinifierService;
use App\Services\PerformanceOptimizer\FontOptimizerService;
use App\Services\PerformanceOptimizer\ScriptManagerService;
use Closure;
use Illuminate\Http\Request;

/**
 * Post-processes the HTML response on the frontend, performing the
 * optimizations the user has enabled via the admin UI:
 *
 *  - perf_image_lazyload         → adds loading="lazy" decoding="async" to <img>
 *  - perf_critical_css           → injects <style> in <head>
 *  - perf_fonts_preload_status   → injects <link rel="preload" as="font"> in <head>
 *  - perf_fonts_swap_status      → forces font-display: swap
 *  - perf_js_defer_status        → adds defer to <script src> (excluding allowlist)
 *  - perf_js_delay_status        → injects user-interaction-triggered delay-JS bootstrap
 *  - perf_image_serve_webp_auto  → rewrites <img src> from .jpg/.png to .webp when sibling exists
 *  - perf_vitals_collect_status  → injects the Web Vitals tracker before </body>
 *  - perf_lcp_preload_status     → auto-detects LCP hero image and preloads it (fetchpriority="high")
 *  - perf_html_minify_status     → strips HTML comments and collapses inter-tag whitespace
 *
 * Runs only when:
 *  - perf_status == 1
 *  - perf_cms_fast_mode != 1
 *  - at least one runtime HTML rewrite feature is explicitly enabled
 *  - admin / API / non-HTML responses are skipped
 *  - response is 200 OK and Content-Type is text/html
 */
class PerformanceOutputMiddleware
{
    protected FontOptimizerService $fonts;
    protected CssJsMinifierService $cssjs;
    protected array $settings = [];
    protected array $localFileExists = [];

    public function __construct(FontOptimizerService $fonts, CssJsMinifierService $cssjs)
    {
        $this->fonts = $fonts;
        $this->cssjs = $cssjs;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$this->shouldProcess($request, $response)) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '') return $response;

        // Detect LCP candidate BEFORE processImages() adds loading="lazy" to everything
        $lcpSrc = $this->detectLcpCandidateSrc($html);

        $html = $this->injectHead($html, $request, $lcpSrc);
        $html = $this->processImages($html, $lcpSrc);
        $html = $this->processLocalizedScripts($html);
        $html = $this->processScripts($html);
        $html = $this->processScriptManager($html, $request);
        $html = $this->injectBodyEnd($html);
        $html = $this->minifyHtml($html);

        $response->setContent($html);
        return $response;
    }

    protected function shouldProcess(Request $request, $response): bool
    {
        if (!method_exists($response, 'getStatusCode') || $response->getStatusCode() !== 200) return false;
        if ((string) $response->headers->get('X-Performance-Cache', '') === 'HIT') return false;

        // Skip AJAX / XHR / JSON requests — they return HTML fragments, not full pages.
        // Injecting scripts/preloads into partials causes duplicate loading (e.g. tracker 3×).
        if ($request->ajax() || $request->expectsJson()) return false;
        if ($request->headers->has('X-Requested-With')) return false;

        $ct = (string) $response->headers->get('Content-Type', '');
        if (stripos($ct, 'text/html') === false) return false;

        // Skip admin / API / debug paths
        $path = ltrim($request->path(), '/');
        $skipPrefixes = ['admin', 'api', 'install', 'update', '_debugbar', 'performance-optimizer'];
        foreach ($skipPrefixes as $p) {
            if (str_starts_with($path, $p)) return false;
        }

        if ((int) $this->setting('perf_status', 1) !== 1) return false;
        if ((int) $this->setting('perf_cms_fast_mode', 1) === 1) return false;
        if (!$this->hasRuntimeFeaturesEnabled()) return false;

        // Skip HTML fragments — only process full documents
        $body = (string) $response->getContent();
        if (stripos($body, '<html') === false) return false;

        return true;
    }

    // ── <head> injections ────────────────────────────────────────────

    protected function injectHead(string $html, Request $request, ?string $lcpSrc = null): string
    {
        $injects = [];

        // LCP hero image preload — inject before anything else so browser discovers it first
        if ($lcpSrc !== null && !$this->hasImagePreload($html)) {
            $injects[] = '<link rel="preload" as="image" href="' . htmlspecialchars($lcpSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" fetchpriority="high">';
        }

        // Critical CSS (inlined)
        if ($cc = trim($this->setting('perf_critical_css', ''))) {
            $injects[] = '<style data-perfopt="critical-css">' . $cc . '</style>';
        }

        // Font preload tags
        if ((int) $this->setting('perf_fonts_preload_status', 0) === 1) {
            $tags = $this->fonts->renderPreloadTags();
            if ($tags !== '') $injects[] = $tags;
        }

        // Force font-display: swap
        if ((int) $this->setting('perf_fonts_swap_status', 0) === 1) {
            $injects[] = $this->fonts->renderFontDisplaySwap();
        }

        // Preconnect and DNS-Prefetch
        if ($domains = trim($this->setting('perf_preconnect_domains', ''))) {
            $domainList = array_filter(array_map('trim', explode("\n", $domains)));
            foreach ($domainList as $domain) {
                if ($domain !== '') {
                    $injects[] = '<link rel="preconnect" href="' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '" crossorigin>';
                    $injects[] = '<link rel="dns-prefetch" href="' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '">';
                }
            }
        }

        // Speculation Rules (Prerendering)
        if ((int) $this->setting('perf_speculation_rules_status', 0) === 1) {
            $injects[] = '<script type="speculationrules">
{"prerender":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/admin/*","/login","/logout","/cart","/checkout","/api/*"]}}]},"eagerness":"moderate"}]}
</script>';
        }

        if (empty($injects)) return $html;

        $block = "\n" . implode("\n", $injects) . "\n";
        // Inject just before </head>, fallback to start of <body>
        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $block . '</head>', $html, 1);
        }
        return preg_replace('/<body\b/i', $block . '<body', $html, 1);
    }

    // ── <img> processing ─────────────────────────────────────────────

    protected function processImages(string $html, ?string $lcpSrc = null): string
    {
        $lazy   = (int) $this->setting('perf_image_lazyload', 0) === 1;
        $serveWebp = (int) $this->setting('perf_image_serve_webp_auto', 0) === 1;
        $imageCdn  = (int) $this->setting('perf_image_cdn_status', 0) === 1;
        $cdnUrl    = rtrim($this->setting('perf_image_cdn_url', ''), '/');
        if (!$lazy && !$serveWebp && !($imageCdn && $cdnUrl !== '')) return $html;

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return preg_replace_callback('/<img\b([^>]*)>/i', function ($m) use ($lazy, $serveWebp, $imageCdn, $cdnUrl, $appHost, $lcpSrc) {
            $attrs = $m[1];

            // Skip if user explicitly opted out via data-no-perf
            if (preg_match('/\bdata-no-perf\b/i', $attrs)) return $m[0];

            // Detect whether this is the LCP hero image
            $isLcp = $lcpSrc !== null
                && preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $lcpCheck)
                && $lcpCheck[2] === $lcpSrc;

            // Lazyload — LCP image must be eager + high priority, never lazy
            if (!preg_match('/\bloading\s*=/i', $attrs)) {
                $attrs .= $isLcp ? ' loading="eager"' : ($lazy ? ' loading="lazy"' : '');
            }
            if ($isLcp && !preg_match('/\bfetchpriority\s*=/i', $attrs)) {
                $attrs .= ' fetchpriority="high"';
            }
            if (!$isLcp && $lazy && !preg_match('/\bdecoding\s*=/i', $attrs)) {
                $attrs .= ' decoding="async"';
            }

            // WebP auto-rewrite (only if sibling .webp exists)
            if ($serveWebp && preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) {
                $src = $sm[2];
                if (preg_match('/\.(jpe?g|png)$/i', $src)) {
                    $webpUrl = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
                    $localPath = $this->urlToLocal($webpUrl);
                    if ($localPath && $this->cachedFileExists($localPath)) {
                        $attrs = preg_replace(
                            '/\bsrc\s*=\s*(["\'])[^"\']+\1/i',
                            'src="' . $webpUrl . '"',
                            $attrs,
                            1
                        );
                    }
                }
            }

            // Image CDN — rewrite /uploads/* (relative or same-host absolute) to {cdnUrl}/uploads/*
            if ($imageCdn && $cdnUrl !== '' && preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) {
                $src = $sm[2];
                $rewritten = $this->rewriteToImageCdn($src, $cdnUrl, $appHost);
                if ($rewritten !== null && $rewritten !== $src) {
                    $attrs = preg_replace(
                        '/\bsrc\s*=\s*(["\'])[^"\']+\1/i',
                        'src="' . $rewritten . '"',
                        $attrs,
                        1
                    );
                }
            }

            return '<img' . $attrs . '>';
        }, $html);
    }

    /**
     * Rewrite an image URL to use the configured Image CDN if it points to a
     * local /uploads/* path. Returns null if no rewrite is appropriate.
     */
    protected function rewriteToImageCdn(string $src, string $cdnUrl, ?string $appHost): ?string
    {
        if ($src === '' || str_starts_with($src, 'data:')) return null;
        if (str_starts_with($src, $cdnUrl . '/')) return null;

        // Same-host absolute → strip origin to relative
        if (preg_match('#^https?://([^/]+)(/.*)$#i', $src, $um)) {
            if ($appHost && stripos($um[1], (string) $appHost) === false) return null;
            $rel = $um[2];
        } else {
            $rel = $src;
        }
        $rel = '/' . ltrim($rel, '/');

        if (!preg_match('#^/(?:public/)?uploads/#i', $rel)) return null;
        return $cdnUrl . $rel;
    }

    protected function urlToLocal(string $url): ?string
    {
        $parsed = parse_url($url);
        $path   = ltrim((string) ($parsed['path'] ?? ''), '/');
        if ($path === '') return null;
        return public_path($path);
    }

    // ── Localize 3rd-Party Scripts ───────────────────────────────────

    protected function processLocalizedScripts(string $html): string
    {
        if ((int) $this->setting('perf_localize_scripts_status', 0) !== 1) return $html;

        $map = [
            'https://www.google-analytics.com/analytics.js' => asset('perf/scripts/analytics.js'),
            'https://connect.facebook.net/en_US/fbevents.js' => asset('perf/scripts/fbevents.js'),
            'https://www.googletagmanager.com/gtm.js' => asset('perf/scripts/gtm.js'),
        ];

        return preg_replace_callback('/<script\b([^>]*)>/i', function ($m) use ($map) {
            $attrs = $m[1];
            if (!preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) return $m[0];
            
            $src = $sm[2];
            foreach ($map as $remote => $local) {
                if (str_starts_with($src, $remote)) {
                    $path = public_path('perf/scripts/' . basename(parse_url($remote, PHP_URL_PATH)));
                    if ($this->cachedFileExists($path)) {
                        $attrs = str_replace($src, $local, $attrs);
                        return '<script' . $attrs . '>';
                    }
                }
            }
            return $m[0];
        }, $html);
    }

    // ── <script> processing (defer) ──────────────────────────────────

    protected function processScripts(string $html): string
    {
        if ((int) $this->setting('perf_js_defer_status', 0) !== 1) return $html;

        // Safety baseline: never defer scripts that ship jQuery or the AIZ core,
        // because the layout has inline <script> blocks that call $() / AIZ.* at parse time.
        // Deferring these breaks home-section AJAX loaders, infinite scroll, modals, etc.
        $defaultExcludes = ['jquery', 'vendors.js', 'aiz-core', 'bootstrap', 'slick', 'checkout', 'stripe', 'recaptcha', 'firebase'];
        $userExcludes    = array_filter(array_map('trim', explode("\n", $this->setting('perf_js_defer_exclude', ''))));
        $excludes        = array_values(array_unique(array_merge($defaultExcludes, $userExcludes)));

        return preg_replace_callback('/<script\b([^>]*)>/i', function ($m) use ($excludes) {
            $attrs = $m[1];

            // Already has defer / async / module
            if (preg_match('/\b(defer|async|type\s*=\s*["\']?module)\b/i', $attrs)) return $m[0];
            // Skip inline (no src) — too risky to defer
            if (!preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) return $m[0];

            $src = $sm[2];
            foreach ($excludes as $ex) {
                if ($ex !== '' && stripos($src, $ex) !== false) return $m[0];
            }
            return '<script defer' . $attrs . '>';
        }, $html);
    }

    // ── Script Manager (per-route allow/deny/defer/async/delay rules) ─

    protected function processScriptManager(string $html, Request $request): string
    {
        if ((int) $this->setting('perf_script_manager_status', 0) !== 1) return $html;

        $sm        = app(ScriptManagerService::class);
        $routePath = ltrim($request->path(), '/');
        $ua        = (string) $request->userAgent();
        $device    = preg_match('/Mobi|Android/i', $ua) ? 'mobile' : 'desktop';

        $rules = $sm->rulesFor($routePath, $device);
        if (empty($rules)) return $html;
        return $sm->applyToHtml($html, $rules);
    }

    // ── Body-end injections (delay-JS + vitals tracker) ──────────────

    protected function injectBodyEnd(string $html): string
    {
        $injects = [];

        if ((int) $this->setting('perf_js_delay_status', 0) === 1) {
            $injects[] = $this->cssjs->getDelayScript();
        }

        if ((int) $this->setting('perf_vitals_collect_status', 0) === 1) {
            $rate     = max(1, min(100, (int) $this->setting('perf_vitals_sample_rate', 10)));
            $endpoint = route('performance_optimizer.collect_vital');
            $injects[] = "<script data-perfopt=\"vitals-bootstrap\">\nwindow.PERF_VITALS_ENDPOINT='{$endpoint}';\nwindow.PERF_VITALS_RATE={$rate};\n</script>";
            $injects[] = '<script defer src="' . asset('assets/performance_optimizer/js/web_vitals_tracker.js') . '"></script>';
        }

        if (empty($injects)) return $html;

        $block = "\n" . implode("\n", $injects) . "\n";
        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $block . '</body>', $html, 1);
        }
        return $html . $block;
    }

    // ── LCP Auto-Preload ─────────────────────────────────────────────

    /**
     * Scans the HTML body for the first meaningful <img> and returns its src.
     * Used to inject a <link rel="preload" fetchpriority="high"> before any
     * other render-blocking resources, directly improving LCP score.
     *
     * Returns null when the feature is disabled or no candidate is found.
     */
    protected function detectLcpCandidateSrc(string $html): ?string
    {
        if ((int) $this->setting('perf_lcp_preload_status', 0) !== 1) return null;

        // Only scan the <body> to avoid picking up hidden/icon images in <head>
        $bodyOffset = stripos($html, '<body');
        $bodyHtml   = $bodyOffset !== false ? substr($html, $bodyOffset) : $html;

        if (!preg_match_all('/<img\b([^>]*)>/i', $bodyHtml, $matches)) return null;

        foreach ($matches[1] as $attrs) {
            // Must have a plain src (not srcset-only)
            if (!preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) continue;
            $src = $sm[2];

            // Skip data URIs, external tracking pixels, empty src
            if ($src === '' || str_starts_with($src, 'data:')) continue;
            if (preg_match('/\b(pixel|1x1|track|beacon|spacer)\b/i', $src)) continue;

            // Skip images with explicit width/height of 1 (tracking pixels in HTML)
            if (preg_match('/\b(?:width|height)\s*=\s*["\']?1["\']?/i', $attrs)) continue;

            // Skip images the developer already marked as lazy
            if (preg_match('/\bloading\s*=\s*["\']?lazy["\']?/i', $attrs)) continue;

            // Skip opt-out marker
            if (preg_match('/\bdata-no-perf\b/i', $attrs)) continue;

            return $src;
        }

        return null;
    }

    // ── HTML Minification ────────────────────────────────────────────

    /**
     * Strips HTML comments and collapses inter-tag whitespace.
     * <pre>, <script>, <style>, and <textarea> content is preserved verbatim.
     * Controlled by perf_html_minify_status (default 0 = disabled).
     */
    protected function minifyHtml(string $html): string
    {
        if ((int) $this->setting('perf_html_minify_status', 0) !== 1) return $html;

        // Pull out blocks whose content must never be touched
        $placeholders = [];
        $idx          = 0;

        $html = preg_replace_callback(
            '/<(pre|script|style|textarea)\b[^>]*>.*?<\/\1>/is',
            function (array $m) use (&$placeholders, &$idx): string {
                $token               = "\x02PERFOPT{$idx}\x03";
                $placeholders[$token] = $m[0];
                $idx++;
                return $token;
            },
            $html
        ) ?? $html;

        // Remove HTML comments — keep IE conditionals (<!--[if) and noindex markers
        $html = preg_replace('/<!--(?!\[if|noindex\b).*?-->/s', '', $html) ?? $html;

        // Collapse whitespace runs between tags
        $html = preg_replace('/>\s{2,}</s', '> <', $html) ?? $html;

        // Trim leading/trailing whitespace on lines (safe because block content is placeholdered)
        $html = preg_replace('/^[ \t]+/m', '', $html) ?? $html;
        $html = preg_replace('/[ \t]+$/m', '', $html) ?? $html;

        // Collapse multiple blank lines into one
        $html = preg_replace('/\n{3,}/', "\n\n", $html) ?? $html;

        // Restore preserved blocks
        foreach ($placeholders as $token => $block) {
            $html = str_replace($token, $block, $html);
        }

        return $html;
    }

    protected function hasRuntimeFeaturesEnabled(): bool
    {
        if ((int) $this->setting('perf_image_lazyload', 0) === 1) return true;
        if ((int) $this->setting('perf_image_serve_webp_auto', 0) === 1) return true;
        if ((int) $this->setting('perf_image_cdn_status', 0) === 1 && trim($this->setting('perf_image_cdn_url', '')) !== '') return true;
        if ((int) $this->setting('perf_fonts_preload_status', 0) === 1) return true;
        if ((int) $this->setting('perf_fonts_swap_status', 0) === 1) return true;
        if ((int) $this->setting('perf_js_defer_status', 0) === 1) return true;
        if ((int) $this->setting('perf_js_delay_status', 0) === 1) return true;
        if ((int) $this->setting('perf_script_manager_status', 0) === 1) return true;
        if ((int) $this->setting('perf_vitals_collect_status', 0) === 1) return true;
        if ((int) $this->setting('perf_lcp_preload_status', 0) === 1) return true;
        if ((int) $this->setting('perf_html_minify_status', 0) === 1) return true;
        if (trim($this->setting('perf_critical_css', '')) !== '') return true;
        if ((int) $this->setting('perf_speculation_rules_status', 0) === 1) return true;
        if (trim($this->setting('perf_preconnect_domains', '')) !== '') return true;
        if ((int) $this->setting('perf_localize_scripts_status', 0) === 1) return true;
        return false;
    }

    protected function setting(string $key, $default = null): string
    {
        if (!array_key_exists($key, $this->settings)) {
            $this->settings[$key] = (string) get_setting($key, $default);
        }
        return $this->settings[$key];
    }

    protected function cachedFileExists(string $path): bool
    {
        if (!array_key_exists($path, $this->localFileExists)) {
            $this->localFileExists[$path] = file_exists($path);
        }
        return $this->localFileExists[$path];
    }

    protected function hasImagePreload(string $html): bool
    {
        return (bool) preg_match(
            '/<link\b(?=[^>]*\brel\s*=\s*["\']preload["\'])(?=[^>]*\bas\s*=\s*["\']image["\'])[^>]*>/i',
            $html
        );
    }
}
