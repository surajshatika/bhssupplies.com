<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\SlowQuery;
use App\Services\PerformanceOptimizer\SlowQueryAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceSecurityPlusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'         => 'secplus',
            'slow_queries'=> SlowQuery::orderByDesc('avg_time_ms')->limit(50)->get(),
            'slow_count'  => SlowQuery::count(),
        ]);
    }

    public function save(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $keys = [
            'perf_bot_protect_status',
            'perf_bot_rate_limit_per_min',
            'perf_bot_block_list',
            'perf_hotlink_protect_status',
            'perf_hotlink_allowed_domains',
            'perf_slow_query_status',
            'perf_slow_query_threshold_ms',
        ];
        foreach ($keys as $k) {
            $setting = BusinessSetting::firstOrNew(['type' => $k]);
            $setting->value = (string) $request->input($k, '');
            $setting->save();
        }
        Cache::forget('business_settings');
        flash(translate('Security+ settings saved.'))->success();
        return back();
    }

    public function scanSlowQueries()
    {
        if ($r = $this->demoBlock()) return $r;
        $r = app(SlowQueryAnalyzerService::class)->scanOnce();
        if (($r['status'] ?? '') === 'disabled') {
            flash(translate('Enable "Slow Query Analyzer" before running a scan.'))->warning();
        } else {
            flash(translate('Scan complete') . ": {$r['samples']} " . translate('queries sampled,') . " {$r['found']} " . translate('new slow queries captured.'))->success();
        }
        return back();
    }

    public function dismissSlowQuery($id)
    {
        if ($r = $this->demoBlock()) return $r;
        app(SlowQueryAnalyzerService::class)->dismiss((int) $id);
        flash(translate('Slow query dismissed.'))->success();
        return back();
    }

    protected function demoBlock()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        return null;
    }
}
