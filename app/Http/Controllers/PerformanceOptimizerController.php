<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\OptimizationLog;
use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceOptimizerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        return $this->dashboard();
    }

    public function dashboard()
    {
        $image = app(ImageOptimizerService::class);
        $cache = app(PageCacheService::class);
        $db    = app(DatabaseCleanerService::class);

        $stats = [
            'images'   => $image->getStats(),
            'cache'    => $cache->getStats(),
            'database' => $db->getStats(),
            'logs'     => [
                'today'  => OptimizationLog::whereDate('created_at', today())->count(),
                'failed' => OptimizationLog::where('status', 'failed')->count(),
                'total'  => OptimizationLog::count(),
            ],
        ];
        $recent_logs = OptimizationLog::orderByDesc('id')->limit(10)->get();

        return view('backend.performance_optimizer.index', [
            'tab'         => 'dashboard',
            'stats'       => $stats,
            'recent_logs' => $recent_logs,
        ]);
    }

    public function toggle(Request $request)
    {
        $setting = BusinessSetting::firstOrNew(['type' => $request->input('type')]);
        $setting->value = $request->input('value');
        $setting->save();
        Cache::forget('business_settings');
        return response()->json(['success' => true]);
    }

    public function updateSettings(Request $request)
    {
        $fields = [
            'perf_status', 'perf_image_webp_quality', 'perf_image_avif_quality',
            'perf_image_lazyload', 'perf_image_compress_on_upload', 'perf_image_serve_webp_auto',
            'perf_css_minify_status', 'perf_js_minify_status', 'perf_css_combine_status',
            'perf_js_combine_status', 'perf_js_defer_status', 'perf_js_delay_status',
            'perf_critical_css', 'perf_css_minify_exclude', 'perf_js_minify_exclude', 'perf_js_defer_exclude',
            'perf_page_cache_status', 'perf_page_cache_driver', 'perf_page_cache_ttl_minutes',
            'perf_page_cache_exclude_paths', 'perf_page_cache_exclude_cookies',
            'perf_db_auto_clean_status', 'perf_db_auto_clean_keep_days',
            'perf_fonts_preload_status', 'perf_fonts_preload_list', 'perf_fonts_swap_status',
            'perf_vitals_collect_status', 'perf_vitals_sample_rate',
            'perf_security_block_xmlrpc', 'perf_security_hide_php_version',
            'perf_security_force_https', 'perf_security_strong_pwd_required',
            'perf_image_webp_status', 'perf_image_avif_status',
        ];

        foreach ($fields as $f) {
            if ($request->has($f)) {
                $setting = BusinessSetting::firstOrNew(['type' => $f]);
                $setting->value = (string) $request->input($f);
                $setting->save();
            }
        }
        Cache::forget('business_settings');
        flash(translate('Performance Optimizer settings updated.'))->success();
        return back();
    }
}
