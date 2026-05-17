<?php

namespace App\Http\Controllers;

use App\Models\PerformanceOptimizer\WebVital;
use App\Services\PerformanceOptimizer\WebVitalsService;
use Illuminate\Http\Request;

class PerformanceVitalsController extends Controller
{
    protected WebVitalsService $service;

    public function __construct(WebVitalsService $service)
    {
        $this->service = $service;
        $this->middleware(['auth', 'admin'])->except('collect');
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'        => 'vitals',
            'summary'    => $this->service->summary(7),
            'samples'    => WebVital::orderByDesc('id')->limit(50)->get(),
            'trend'      => $this->service->dailyTrend(7),
        ]);
    }

    public function collect(Request $request)
    {
        if ((int) get_setting('perf_vitals_collect_status', 0) !== 1) {
            return response()->json(['success' => false, 'reason' => 'disabled'], 200);
        }
        $ok = $this->service->record($request->all() ?: (json_decode((string) $request->getContent(), true) ?? []));
        return response()->json(['success' => $ok]);
    }

    public function clear()
    {
        WebVital::query()->delete();
        flash(translate('Web Vitals samples cleared.'))->success();
        return back();
    }
}
