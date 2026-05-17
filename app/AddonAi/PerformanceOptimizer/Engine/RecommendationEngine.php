<?php

namespace App\AddonAi\PerformanceOptimizer\Engine;

use App\Models\PerformanceOptimizer\AiRecommendation;
use App\Models\PerformanceOptimizer\WebVital;
use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use Throwable;

/**
 * Rule-based recommendation engine. Each `analyze*()` method inspects a slice
 * of the site state and produces zero or more `AiRecommendation` rows.
 *
 * All generators are idempotent: an unresolved pending recommendation with the
 * same title is not duplicated.
 */
class RecommendationEngine
{
    public function run(): int
    {
        $count  = 0;
        $count += $this->analyzeVitals();
        $count += $this->analyzeImages();
        $count += $this->analyzeDatabase();
        $count += $this->analyzeCache();
        $count += $this->analyzeSecurity();
        $count += $this->analyzeCssJs();
        return $count;
    }

    // ── Vitals ───────────────────────────────────────────────────────

    protected function analyzeVitals(): int
    {
        $count = 0;
        $thresholds = [
            'LCP' => ['poor' => 4000, 'severity' => 'high'],
            'INP' => ['poor' => 500,  'severity' => 'high'],
            'CLS' => ['poor' => 0.25, 'severity' => 'medium'],
        ];

        foreach ($thresholds as $metric => $t) {
            try {
                $rows = WebVital::where('metric', $metric)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->orderBy('value')->pluck('value')->all();
            } catch (Throwable $e) { $rows = []; }
            if (count($rows) < 10) continue;

            $p75 = $rows[(int) floor((count($rows) - 1) * 0.75)];
            if ($p75 < $t['poor']) continue;

            $title = "P75 {$metric} is " . ($metric === 'CLS' ? number_format($p75, 3) : number_format($p75) . 'ms')
                . ' — above the "poor" threshold';
            if ($this->exists('vitals', $title)) continue;

            $body = match ($metric) {
                'LCP' => 'Largest Contentful Paint at the 75th percentile exceeds ' . $t['poor'] . 'ms. Common causes: large hero image, render-blocking CSS, slow TTFB. Suggested fixes: convert hero to WebP/AVIF, preload it with fetchpriority="high", enable critical CSS.',
                'INP' => 'Interaction to Next Paint at P75 exceeds 500ms — clicks feel laggy. Likely cause: heavy JavaScript on main thread. Suggested fixes: defer/delay non-critical JS via Script Manager, disable analytics on product pages.',
                'CLS' => 'Cumulative Layout Shift at P75 above 0.25 — content jumps as the page loads. Most common cause: images without width/height. The Auto-Fix can scan and inject dimensions automatically.',
                default => 'Investigate this metric.',
            };

            AiRecommendation::create([
                'category'         => 'vitals',
                'severity'         => $t['severity'],
                'title'            => $title,
                'body'             => $body,
                'evidence'         => ['p75' => $p75, 'sample_count' => count($rows), 'metric' => $metric],
                'confidence'       => 95,
                'auto_fixable'     => $metric === 'CLS',
                'auto_fix_action'  => $metric === 'CLS' ? 'autoFixImageDimensions' : null,
                'auto_fix_payload' => null,
                'source'           => 'rule_engine',
            ]);
            $count++;
        }
        return $count;
    }

    // ── Images ───────────────────────────────────────────────────────

    protected function analyzeImages(): int
    {
        $count = 0;
        try {
            $stats = app(ImageOptimizerService::class)->getStats();
        } catch (Throwable $e) { return 0; }

        $total       = (int) ($stats['total'] ?? 0);
        $converted   = (int) ($stats['converted'] ?? 0);
        $unconverted = max(0, $total - $converted);

        if ($unconverted > 100) {
            $title = "Convert {$unconverted} more images to WebP";
            if (!$this->exists('image', $title)) {
                AiRecommendation::create([
                    'category'         => 'image',
                    'severity'         => $unconverted > 1000 ? 'high' : 'medium',
                    'title'            => $title,
                    'body'             => "{$unconverted} images remain as JPG/PNG. Converting to WebP typically saves ~30% bandwidth and improves LCP for image-heavy product pages.",
                    'evidence'         => $stats,
                    'confidence'       => 100,
                    'auto_fixable'     => true,
                    'auto_fix_action'  => 'convertImagesWebp',
                    'auto_fix_payload' => ['limit' => min(500, $unconverted)],
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
        }

        if ((int) get_setting('perf_image_lazyload', 1) !== 1) {
            $title = 'Enable image lazy-load';
            if (!$this->exists('image', $title)) {
                AiRecommendation::create([
                    'category'         => 'image', 'severity' => 'medium',
                    'title'            => $title,
                    'body'             => 'Image lazy-loading is OFF. Below-fold images currently download eagerly — wasting bandwidth on every page view. Auto-fix enables loading="lazy" injection.',
                    'evidence'         => ['perf_image_lazyload' => 0],
                    'confidence'       => 100,
                    'auto_fixable'     => true,
                    'auto_fix_action'  => 'setSettings',
                    'auto_fix_payload' => ['settings' => ['perf_image_lazyload' => '1']],
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
        }
        return $count;
    }

    // ── Database ─────────────────────────────────────────────────────

    protected function analyzeDatabase(): int
    {
        $count = 0;
        try {
            $stats = app(DatabaseCleanerService::class)->getStats();
        } catch (Throwable $e) { return 0; }

        $totalCruft = (int) ($stats['sessions'] ?? 0) + (int) ($stats['old_notifications'] ?? 0) + (int) ($stats['old_carts'] ?? 0);
        if ($totalCruft > 5000) {
            $title = "Clean up {$totalCruft} stale database rows";
            if (!$this->exists('db', $title)) {
                AiRecommendation::create([
                    'category'         => 'db',
                    'severity'         => $totalCruft > 50000 ? 'high' : 'medium',
                    'title'            => $title,
                    'body'             => 'Old sessions, notifications, and carts inflate table sizes and slow queries. Auto-fix runs a non-destructive cleanup of the conservative defaults (>30 days old).',
                    'evidence'         => $stats,
                    'confidence'       => 90,
                    'auto_fixable'     => true,
                    'auto_fix_action'  => 'cleanDatabase',
                    'auto_fix_payload' => ['items' => ['sessions', 'old_notifications', 'old_carts']],
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
        }
        return $count;
    }

    // ── Cache ────────────────────────────────────────────────────────

    protected function analyzeCache(): int
    {
        $count = 0;
        $enabled = (int) get_setting('perf_page_cache_status', 0) === 1;
        if (!$enabled) {
            $title = 'Page Cache is OFF — enable for instant TTFB wins';
            if (!$this->exists('cache', $title)) {
                AiRecommendation::create([
                    'category'         => 'cache', 'severity' => 'high',
                    'title'            => $title,
                    'body'             => 'Page Cache is disabled. Enabling caches the full HTML response for anonymous users — TTFB drops from PHP+DB time to filesystem read time (often 90% faster).',
                    'evidence'         => ['perf_page_cache_status' => 0],
                    'confidence'       => 95,
                    'auto_fixable'     => true,
                    'auto_fix_action'  => 'setSettings',
                    'auto_fix_payload' => ['settings' => ['perf_page_cache_status' => '1']],
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
            return $count;
        }

        // Cache enabled but no pages cached yet
        try {
            $stats = app(\App\Services\PerformanceOptimizer\PageCacheService::class)->getStats();
        } catch (Throwable $e) { $stats = ['pages' => 0]; }
        if (($stats['pages'] ?? 0) === 0) {
            $title = 'Page Cache is empty — warm it from sitemap';
            if (!$this->exists('cache', $title)) {
                AiRecommendation::create([
                    'category'         => 'cache', 'severity' => 'medium',
                    'title'            => $title,
                    'body'             => 'The page cache is empty. Warming it from the sitemap pre-renders the most important pages so the first visitor of each path also gets a cache HIT.',
                    'evidence'         => $stats,
                    'confidence'       => 90,
                    'auto_fixable'     => true,
                    'auto_fix_action'  => 'warmCache',
                    'auto_fix_payload' => ['max' => 100],
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
        }
        return $count;
    }

    // ── Security ─────────────────────────────────────────────────────

    protected function analyzeSecurity(): int
    {
        $count = 0;
        if (env('APP_DEBUG') === true || env('APP_DEBUG') === 'true' || env('APP_DEBUG') === '1') {
            $title = 'APP_DEBUG is ON in .env — disable in production';
            if (!$this->exists('security', $title)) {
                AiRecommendation::create([
                    'category'         => 'security', 'severity' => 'critical',
                    'title'            => $title,
                    'body'             => 'APP_DEBUG=true leaks stack traces, .env values, and SQL queries on errors. Must be set to false in production. This is the single highest-priority security setting.',
                    'evidence'         => ['APP_DEBUG' => env('APP_DEBUG')],
                    'confidence'       => 100,
                    'auto_fixable'     => false,  // Editing .env is too dangerous — manual only
                    'auto_fix_action'  => null,
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
        }

        if ((int) get_setting('perf_security_force_https', 0) !== 1) {
            $title = 'Force HTTPS redirect is OFF';
            if (!$this->exists('security', $title)) {
                AiRecommendation::create([
                    'category'         => 'security', 'severity' => 'medium',
                    'title'            => $title,
                    'body'             => 'HTTP requests are not redirected to HTTPS. Forcing HTTPS prevents man-in-the-middle attacks and avoids mixed-content warnings.',
                    'evidence'         => ['perf_security_force_https' => 0],
                    'confidence'       => 100,
                    'auto_fixable'     => true,
                    'auto_fix_action'  => 'setSettings',
                    'auto_fix_payload' => ['settings' => ['perf_security_force_https' => '1']],
                    'source'           => 'rule_engine',
                ]);
                $count++;
            }
        }
        return $count;
    }

    // ── CSS / JS ─────────────────────────────────────────────────────

    protected function analyzeCssJs(): int
    {
        $count = 0;
        $checks = [
            'perf_css_minify_status' => ['Enable CSS minification', 'CSS minification is OFF. Minifying typically shaves 20-30% off CSS payload.'],
            'perf_js_minify_status'  => ['Enable JS minification',  'JS minification is OFF. Minifying typically shaves 25-35% off JS payload.'],
            'perf_js_defer_status'   => ['Enable JS defer',         'JS defer is OFF. Adding defer to non-critical <script src=> tags eliminates render-blocking.'],
        ];
        foreach ($checks as $key => $info) {
            if ((int) get_setting($key, 0) === 1) continue;
            [$title, $body] = $info;
            if ($this->exists('css', $title)) continue;
            AiRecommendation::create([
                'category'         => str_contains($key, 'css') ? 'css' : 'js',
                'severity'         => 'medium',
                'title'            => $title,
                'body'             => $body,
                'evidence'         => [$key => 0],
                'confidence'       => 95,
                'auto_fixable'     => true,
                'auto_fix_action'  => 'setSettings',
                'auto_fix_payload' => ['settings' => [$key => '1']],
                'source'           => 'rule_engine',
            ]);
            $count++;
        }
        return $count;
    }

    // ── helpers ──────────────────────────────────────────────────────

    protected function exists(string $category, string $title): bool
    {
        return AiRecommendation::where('category', $category)
            ->where('title', $title)
            ->whereNull('applied_at')
            ->whereNull('dismissed_at')
            ->exists();
    }
}
