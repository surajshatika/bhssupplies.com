<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Exception;

class PerformanceCacheController extends Controller
{
    protected PageCacheService $service;

    public function __construct(PageCacheService $service)
    {
        $this->middleware(['auth', 'admin']);
        $this->service = $service;
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'   => 'caching',
            'stats' => $this->service->getStats(),
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
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Cache::forget('business_settings');
            flash(translate('Laravel cache cleared (cache + config + view + route).'))->success();
        } catch (Exception $e) {
            flash(translate('Cache clear failed') . ': ' . $e->getMessage())->error();
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
}
