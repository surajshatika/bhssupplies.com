<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\PerformanceOptimizer\FontOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceFontController extends Controller
{
    protected FontOptimizerService $service;

    public function __construct(FontOptimizerService $service)
    {
        $this->middleware(['auth', 'admin']);
        $this->service = $service;
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'  => 'fonts',
            'list' => $this->service->getPreloadList(),
        ]);
    }

    public function save(Request $request)
    {
        foreach (['perf_fonts_preload_status', 'perf_fonts_swap_status', 'perf_fonts_preload_list'] as $f) {
            $setting = BusinessSetting::firstOrNew(['type' => $f]);
            $setting->value = (string) $request->input($f, '');
            $setting->save();
        }
        Cache::forget('business_settings');
        flash(translate('Font settings saved.'))->success();
        return back();
    }
}
