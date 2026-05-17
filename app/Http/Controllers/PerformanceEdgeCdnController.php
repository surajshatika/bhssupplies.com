<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\PerformanceOptimizer\BunnyCdnService;
use App\Services\PerformanceOptimizer\CloudflareService;
use App\Services\PerformanceOptimizer\CloudFrontService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceEdgeCdnController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $cf = app(CloudflareService::class);
        $bn = app(BunnyCdnService::class);
        $cfront = app(CloudFrontService::class);

        return view('backend.performance_optimizer.index', [
            'tab' => 'edge',
            'cf_configured'     => $cf->isConfigured(),
            'bn_configured'     => $bn->isConfigured(),
            'cfront_configured' => $cfront->isConfigured(),
            'aws_sdk_present'   => class_exists(\Aws\CloudFront\CloudFrontClient::class),
        ]);
    }

    // ── Cloudflare ────────────────────────────────────────────────────

    public function saveCloudflare(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $this->saveSettings($request, [
            'perf_cloudflare_status',
            'perf_cloudflare_zone_id',
            'perf_cloudflare_api_token',
            'perf_cloudflare_auto_purge',
        ]);
        flash(translate('Cloudflare settings saved.'))->success();
        return back();
    }

    public function testCloudflare()
    {
        if ($r = $this->demoBlock()) return $r;
        $r = app(CloudflareService::class)->test();
        if (!empty($r['ok'])) {
            flash(translate('Cloudflare OK — Zone:') . ' ' . ($r['name'] ?? '?') . ' (' . ($r['status'] ?? '?') . ')')->success();
        } else {
            flash(translate('Cloudflare test failed') . ': ' . ($r['error'] ?? 'unknown'))->error();
        }
        return back();
    }

    public function purgeCloudflare()
    {
        if ($r = $this->demoBlock()) return $r;
        $r = app(CloudflareService::class)->purgeAll();
        if (!empty($r['ok'])) {
            flash(translate('Cloudflare cache purged.'))->success();
        } else {
            flash(translate('Cloudflare purge failed') . ': ' . ($r['error'] ?? 'unknown'))->error();
        }
        return back();
    }

    // ── Bunny.net ─────────────────────────────────────────────────────

    public function saveBunny(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $this->saveSettings($request, [
            'perf_bunny_status',
            'perf_bunny_pull_zone',
            'perf_bunny_api_key',
            'perf_bunny_cdn_hostname',
        ]);
        flash(translate('Bunny.net settings saved.'))->success();
        return back();
    }

    public function purgeBunny()
    {
        if ($r = $this->demoBlock()) return $r;
        $r = app(BunnyCdnService::class)->purgeAll();
        if (!empty($r['ok'])) {
            flash(translate('Bunny.net cache purged.'))->success();
        } else {
            flash(translate('Bunny.net purge failed') . ': ' . ($r['error'] ?? 'unknown'))->error();
        }
        return back();
    }

    // ── AWS CloudFront ────────────────────────────────────────────────

    public function saveCloudFront(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $this->saveSettings($request, [
            'perf_cloudfront_status',
            'perf_cloudfront_distribution',
            'perf_cloudfront_access_key',
            'perf_cloudfront_secret',
        ]);
        flash(translate('CloudFront settings saved.'))->success();
        return back();
    }

    public function invalidateCloudFront()
    {
        if ($r = $this->demoBlock()) return $r;
        $r = app(CloudFrontService::class)->invalidate(['/*']);
        if (!empty($r['ok'])) {
            flash(translate('CloudFront invalidation started') . ' (ID: ' . ($r['invalidation_id'] ?? '?') . ')')->success();
        } else {
            flash(translate('CloudFront invalidation failed') . ': ' . ($r['error'] ?? 'unknown'))->error();
        }
        return back();
    }

    // ── Image CDN ─────────────────────────────────────────────────────

    public function saveImageCdn(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $this->saveSettings($request, [
            'perf_image_cdn_status',
            'perf_image_cdn_url',
        ]);
        flash(translate('Image CDN settings saved.'))->success();
        return back();
    }

    // ── helpers ──────────────────────────────────────────────────────

    protected function saveSettings(Request $request, array $keys): void
    {
        foreach ($keys as $k) {
            $setting = BusinessSetting::firstOrNew(['type' => $k]);
            $setting->value = (string) $request->input($k, '');
            $setting->save();
        }
        Cache::forget('business_settings');
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
