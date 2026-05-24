<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\CloudflareService;
use App\Services\PerformanceOptimizer\LiteSpeedCacheService;
use App\Services\PerformanceOptimizer\OPcacheService;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Exception;

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
        flash(translate('Page cache cleared.') . " {$n} " . translate('pages.'))->success();
        return back();
    }

    public function warm()
    {
        if ($r = $this->demoBlock()) return $r;
        $r = $this->service->warmFromSitemap();
        flash(translate('Cache warmed.') . " {$r['warmed']} / {$r['total']} URLs. {$r['failed']} " . translate('failed.'))->success();
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
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            flash(translate('Laravel optimization cache built (config + route + view).'))->success();
        } catch (Exception $e) {
            flash(translate('Optimization failed') . ': ' . $e->getMessage())->error();
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

        // 1. File / Redis page cache
        if (in_array($driver, ['file', 'redis'], true)) {
            $n = $this->service->clearAll();
            $lines[] = translate('Page cache cleared') . ": {$n} " . translate('pages');
        }

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
}
