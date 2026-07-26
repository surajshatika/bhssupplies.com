<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\CloudflareService;
use App\Services\PerformanceOptimizer\LiteSpeedCacheService;
use App\Services\PerformanceOptimizer\OPcacheService;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class PerformanceCacheController extends Controller
{
    protected PageCacheService $service;
    protected LiteSpeedCacheService $lsc;
    protected OPcacheService $opcache;

    public function __construct(PageCacheService $service, LiteSpeedCacheService $lsc, OPcacheService $opcache)
    {
        $this->middleware(['auth', 'admin']);
        $this->service  = $service;
        $this->lsc      = $lsc;
        $this->opcache  = $opcache;
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'          => 'caching',
            'stats'        => $this->service->getStats(),
            'cachedPages'  => $this->service->getPagesList(),
            'opcacheStats' => $this->opcache->getStats(),
            'lscInfo'      => $this->lsc->getStatusInfo(),
        ]);
    }

    protected function demoBlock()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        return null;
    }

    public function clear()
    {
        if ($r = $this->demoBlock()) return $r;
        $n = $this->service->clearAll();
        $this->forgetDashboardCaches();
        flash(translate('Page cache cleared.') . " {$n} " . translate('pages.'))->success();
        return back();
    }

    public function warm(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $max = (int) $request->input('max', 5);
        $max = max(1, min($max, 20));

        try {
            $result = $this->service->warmFromSitemap($max);
            $this->forgetDashboardCaches();
            $message = translate('Cache warm completed.') . " {$result['warmed']} / {$result['attempted']} "
                . translate('URLs warmed.') . " {$result['failed']} " . translate('failed.') . ' '
                . translate('Next batch starts at') . " {$result['next_cursor']} / {$result['total']}.";

            if (($result['warmed'] ?? 0) > 0) {
                flash($message)->success();
            } else {
                flash($message . ' ' . translate('Please check server connectivity and sitemap URLs.'))->warning();
            }
        } catch (Throwable $e) {
            Log::error('Performance cache warm failed', ['error' => $e->getMessage()]);
            flash(translate('Cache warm failed. Please check server logs.'))->error();
        }

        return back();
    }

    public function clearLaravel()
    {
        if ($r = $this->demoBlock()) return $r;

        $ok     = [];
        $failed = [];

        foreach ([
            'cache:clear'  => 'Application cache',
            'config:clear' => 'Config cache',
            'view:clear'   => 'View cache',
            'route:clear'  => 'Route cache',
        ] as $cmd => $label) {
            try {
                Artisan::call($cmd);
                $ok[] = $label;
            } catch (\Throwable $e) {
                $failed[$label] = $e->getMessage();
            }
        }

        // Direct file delete as fallback for permission-restricted hosting
        foreach ([
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/services.php'),
            base_path('bootstrap/cache/packages.php'),
        ] as $f) {
            if (is_file($f)) @unlink($f);
        }

        try { Cache::forget('business_settings'); } catch (\Throwable $e) {}
        $this->forgetDashboardCaches();

        if (empty($failed)) {
            flash(translate('Laravel cache cleared (cache + config + view + route).'))->success();
        } else {
            flash(translate('Partially cleared. Failed') . ': ' . implode(', ', array_keys($failed)))->warning();
        }
        return back();
    }

    public function optimize()
    {
        if ($r = $this->demoBlock()) return $r;

        $ok = [];
        $failed = [];

        foreach ([
            'config:cache' => translate('Config cache'),
            'view:cache' => translate('View cache'),
            'route:cache' => translate('Route cache'),
        ] as $cmd => $label) {
            try {
                Artisan::call($cmd);
                $ok[] = $label;
            } catch (Throwable $e) {
                $failed[$label] = $e->getMessage();
                Log::warning('Performance optimizer Laravel optimize step failed', [
                    'command' => $cmd,
                    'error' => $e->getMessage(),
                ]);

                if ($cmd === 'route:cache') {
                    try {
                        Artisan::call('route:clear');
                    } catch (Throwable $clearError) {
                        Log::warning('Route cache clear failed after route:cache failure', [
                            'error' => $clearError->getMessage(),
                        ]);
                    }
                }
            }
        }

        if (empty($failed)) {
            flash(translate('Laravel optimization cache built (config + route + view).'))->success();
        } elseif (!empty($ok)) {
            flash(translate('Laravel optimization partially completed.') . ' ' . translate('Failed') . ': ' . implode(', ', array_keys($failed)))->warning();
        } else {
            flash(translate('Optimization failed. Please check server logs.'))->error();
        }

        return back();
    }

    // ── Combined purge ────────────────────────────────────────────────────────

    /**
     * Purge everything in one click:
     *  1. File / Redis page cache
     *  2. LiteSpeed Cache (server-level)
     *  3. Cloudflare CDN (if configured)
     */
    public function purgeEverything()
    {
        if ($r = $this->demoBlock()) return $r;

        $lines   = [];
        $driver  = (string) get_setting('perf_page_cache_driver', 'file');

        // 1. File / Redis / Memcached page cache, including LiteSpeed safety copies
        $n = $this->service->clearAll();
        $lines[] = translate('Page cache cleared') . ": {$n} " . translate('pages');
        $this->forgetDashboardCaches();

        // 2. LiteSpeed Cache — always attempt when driver=litespeed, harmless otherwise
        if ($driver === 'litespeed') {
            $this->lsc->purgeAll();
            $lines[] = translate('LiteSpeed cache purge sent');
        }

        // 3. Cloudflare — only if zone + token are configured
        $cfZone  = trim((string) get_setting('perf_cloudflare_zone_id', ''));
        $cfToken = trim((string) get_setting('perf_cloudflare_api_token', ''));
        if ($cfZone !== '' && $cfToken !== '') {
            try {
                $result = app(CloudflareService::class)->purgeAll();
                $lines[] = $result['success'] ?? true
                    ? translate('Cloudflare cache purged')
                    : translate('Cloudflare purge failed');
            } catch (Exception $e) {
                $lines[] = translate('Cloudflare purge error') . ': ' . $e->getMessage();
            }
        }

        flash(implode(' · ', $lines) . '.')-> success();
        return back();
    }

    // ── LiteSpeed Cache ───────────────────────────────────────────────────────

    public function purgeLiteSpeed()
    {
        if ($r = $this->demoBlock()) return $r;
        $result = $this->lsc->purgeAll();
        if ($result['success']) {
            flash(translate('LiteSpeed cache purge request sent.'))->success();
        } else {
            flash($result['message'])->error();
        }
        return back();
    }

    public function purgeLiteSpeedTag(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;
        $tag = trim((string) $request->input('tag', ''));
        if ($tag === '') {
            flash(translate('Tag is required.'))->error();
            return back();
        }
        $this->lsc->purgeByTag($tag);
        flash(translate('LiteSpeed cache purge request sent for tag:') . ' ' . $tag)->success();
        return back();
    }

    // ── OPcache ───────────────────────────────────────────────────────────────

    public function flushOpcache()
    {
        if ($r = $this->demoBlock()) return $r;
        if (!$this->opcache->isAvailable()) {
            flash(translate('OPcache is not available on this server.'))->error();
            return back();
        }
        $ok = $this->opcache->flush();
        if ($ok) {
            flash(translate('OPcache flushed successfully.'))->success();
        } else {
            flash(translate('OPcache flush failed. OPcache may not be enabled.'))->error();
        }
        return back();
    }

    public function opcacheStats()
    {
        return response()->json($this->opcache->getStats());
    }

    public function testUrl(Request $request)
    {
        $input = trim((string) $request->input('url', url('/')));
        $normalized = $this->normalizeTestUrl($input);

        if (!$normalized['ok']) {
            flash($normalized['message'])->error();
            return back()->withInput();
        }

        $url = $normalized['url'];
        $diagnosis = $this->service->diagnoseUrl($url);

        $first = $this->probeUrl($url);
        usleep(250000);
        $second = $this->probeUrl($url);

        return redirect()
            ->route('performance_optimizer.cache')
            ->with('cache_test_result', [
                'url' => $url,
                'diagnosis' => $diagnosis,
                'first' => $first,
                'second' => $second,
                'tested_at' => now()->format('Y-m-d H:i:s'),
            ]);
    }

    public function actionRequiresPost(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => translate('This action requires a POST request.'),
            ], 405);
        }

        flash(translate('Please use the button inside Performance Optimizer. Direct action URLs are disabled for safety.'))->warning();
        return redirect()->route('performance_optimizer.cache');
    }

    protected function normalizeTestUrl(string $input): array
    {
        if ($input === '') {
            $input = url('/');
        }

        if (str_starts_with($input, '/')) {
            $input = rtrim(url('/'), '/') . '/' . ltrim($input, '/');
        } elseif (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . $input;
        }

        $host = strtolower((string) parse_url($input, PHP_URL_HOST));
        if ($host === '') {
            return ['ok' => false, 'message' => translate('Please enter a valid URL.')];
        }

        $allowedHosts = array_filter(array_unique([
            strtolower((string) parse_url(url('/'), PHP_URL_HOST)),
            strtolower((string) parse_url(config('app.url'), PHP_URL_HOST)),
        ]));

        $hostAllowed = false;
        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || $host === 'www.' . $allowedHost || 'www.' . $host === $allowedHost) {
                $hostAllowed = true;
                break;
            }
        }

        if (!$hostAllowed) {
            return ['ok' => false, 'message' => translate('Only this store domain can be tested from the cache tester.')];
        }

        return ['ok' => true, 'url' => $input];
    }

    protected function probeUrl(string $url): array
    {
        $started = microtime(true);
        $verifyTls = !str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1');

        try {
            $client = Http::connectTimeout(2)
                ->timeout(12)
                ->withOptions(['allow_redirects' => true])
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml',
                    'User-Agent' => 'BHS-Performance-Optimizer/1.1',
                ]);

            if (!$verifyTls) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get($url);
            $elapsed = (int) round((microtime(true) - $started) * 1000);

            return [
                'ok' => true,
                'status' => $response->status(),
                'time_ms' => $elapsed,
                'size_kb' => round(strlen((string) $response->body()) / 1024, 1),
                'headers' => $this->cacheProbeHeaders($response),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'status' => null,
                'time_ms' => (int) round((microtime(true) - $started) * 1000),
                'size_kb' => 0,
                'error' => $e->getMessage(),
                'headers' => [],
            ];
        }
    }

    protected function cacheProbeHeaders($response): array
    {
        $headers = [];
        foreach ([
            'x-performance-cache',
            'x-litespeed-cache',
            'x-litespeed-cache-control',
            'cf-cache-status',
            'cache-control',
            'age',
            'server',
            'content-type',
            'location',
        ] as $key) {
            $value = $response->header($key);
            if ($value !== null && $value !== '') {
                $headers[$key] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        $setCookie = $response->header('set-cookie');
        if ($setCookie) {
            $headers['set-cookie'] = is_array($setCookie) ? count($setCookie) . ' cookies' : 'present';
        }

        return $headers;
    }

    protected function forgetDashboardCaches(): void
    {
        foreach (['perf_dashboard_stats', 'perf_dashboard_recent_logs', 'perf_default_warm_urls'] as $key) {
            try {
                Cache::forget($key);
            } catch (Throwable $e) {
                Log::debug('Performance optimizer cache forget failed', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
