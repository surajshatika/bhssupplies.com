<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\OptimizationLog;
use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        $stats = Cache::remember('perf_dashboard_stats', 60, function () {
            $image = app(ImageOptimizerService::class);
            $cache = app(PageCacheService::class);
            $db    = app(DatabaseCleanerService::class);

            return [
                'images'   => $image->getStats(),
                'cache'    => $cache->getStats(),
                'database' => $db->getStats(),
                'logs'     => [
                    'today'  => OptimizationLog::whereDate('created_at', today())->count(),
                    'failed' => OptimizationLog::where('status', 'failed')->count(),
                    'total'  => OptimizationLog::count(),
                ],
            ];
        });
        $recent_logs = Cache::remember('perf_dashboard_recent_logs', 30, function () {
            return OptimizationLog::orderByDesc('id')->limit(10)->get();
        });

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
            'perf_status', 'perf_cms_fast_mode', 'perf_image_webp_quality', 'perf_image_avif_quality',
            'perf_image_lazyload', 'perf_image_compress_on_upload', 'perf_image_serve_webp_auto',
            'perf_css_minify_status', 'perf_js_minify_status', 'perf_css_combine_status',
            'perf_js_combine_status', 'perf_js_defer_status', 'perf_js_delay_status',
            'perf_critical_css', 'perf_css_minify_exclude', 'perf_js_minify_exclude', 'perf_js_defer_exclude',
            'perf_lcp_preload_status', 'perf_html_minify_status', 'perf_script_manager_status',
            'perf_page_cache_status', 'perf_page_cache_driver', 'perf_page_cache_ttl_minutes',
            'perf_page_cache_exclude_paths', 'perf_page_cache_exclude_cookies',
            'perf_db_auto_clean_status', 'perf_db_auto_clean_keep_days',
            'perf_fonts_preload_status', 'perf_fonts_preload_list', 'perf_fonts_swap_status',
            'perf_vitals_collect_status', 'perf_vitals_sample_rate',
            'perf_security_block_xmlrpc', 'perf_security_hide_php_version',
            'perf_security_force_https', 'perf_security_strong_pwd_required',
            'perf_image_webp_status', 'perf_image_avif_status',
            'perf_cloudflare_status', 'perf_cloudflare_zone_id', 'perf_cloudflare_api_token',
            'perf_cloudflare_auto_purge', 'perf_bunny_status', 'perf_bunny_pull_zone',
            'perf_bunny_api_key', 'perf_bunny_cdn_hostname', 'perf_cloudfront_status',
            'perf_cloudfront_distribution', 'perf_cloudfront_access_key', 'perf_cloudfront_secret',
            'perf_image_cdn_status', 'perf_image_cdn_url',
            'perf_bot_protect_status', 'perf_bot_rate_limit_per_min', 'perf_bot_block_list',
            'perf_hotlink_protect_status', 'perf_hotlink_allowed_domains',
            'perf_slow_query_status', 'perf_slow_query_threshold_ms',
            'perf_ai_recs_status', 'perf_ai_recs_auto_apply', 'perf_ai_recs_auto_apply_threshold',
        ];

        $failed = [];

        foreach ($fields as $f) {
            if (!$request->has($f)) {
                continue;
            }

            try {
                $value = $this->normalizeSettingValue($f, $request->input($f));
                $setting = BusinessSetting::firstOrNew(['type' => $f]);
                $setting->value = $value;
                $setting->save();
            } catch (Throwable $e) {
                $failed[] = $f;
                Log::error('[PerfOptimizer] Setting save failed', [
                    'field' => $f,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try { Cache::forget('business_settings'); } catch (Throwable $e) {}

        if (!empty($failed)) {
            flash(translate('Some settings could not be saved') . ': ' . implode(', ', $failed))->warning();
            return back();
        }

        flash(translate('Performance Optimizer settings updated.'))->success();
        return back();
    }

    protected function normalizeSettingValue(string $field, $value): string
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($field === 'perf_page_cache_driver') {
            $allowed = ['file', 'redis', 'litespeed', 'memcached'];
            $value = in_array((string) $value, $allowed, true) ? (string) $value : 'file';
            if ($value === 'memcached' && !class_exists(\Memcached::class)) {
                $value = 'file';
            }
        }

        if ($field === 'perf_page_cache_ttl_minutes') {
            $value = max(1, (int) $value);
        }

        return (string) ($value ?? '');
    }
}
