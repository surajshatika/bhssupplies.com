<?php

namespace App\Services\Seo\Advanced;

use App\Services\Seo\Providers\ResilientProviderHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Chrome UX Report (CrUX) field data.
 *
 * The existing PageSpeed/Lighthouse integration produces LAB data — a
 * simulated load on synthetic hardware. Google does not rank on lab data.
 * It ranks on FIELD data: the 28-day rolling distribution of real Chrome
 * users' measurements. This service reads that actual field dataset.
 *
 * Important honesty property: CrUX only has data for origins/URLs with
 * enough real Chrome traffic. When a URL is below that threshold the API
 * returns 404. We surface that as an explicit "not enough real traffic"
 * state rather than falling back to lab numbers and presenting them as if
 * they were field data — a silent lab/field swap is exactly the kind of
 * thing that makes a performance dashboard untrustworthy.
 */
class CruxFieldDataService
{
    use ResilientProviderHttp;

    protected const ENDPOINT = 'https://chromeuxreport.googleapis.com/v1/records:queryRecord';
    protected const CACHE_TTL = 21600; // 6h — CrUX itself only updates daily.

    /**
     * Core Web Vitals thresholds (good / needs-improvement boundary),
     * per web.dev. p75 is the metric Google evaluates.
     */
    public const THRESHOLDS = [
        'largest_contentful_paint'         => ['good' => 2500, 'poor' => 4000, 'unit' => 'ms',  'label' => 'LCP', 'name' => 'Largest Contentful Paint'],
        'interaction_to_next_paint'        => ['good' => 200,  'poor' => 500,  'unit' => 'ms',  'label' => 'INP', 'name' => 'Interaction to Next Paint'],
        'cumulative_layout_shift'          => ['good' => 0.1,  'poor' => 0.25, 'unit' => '',    'label' => 'CLS', 'name' => 'Cumulative Layout Shift'],
        'first_contentful_paint'           => ['good' => 1800, 'poor' => 3000, 'unit' => 'ms',  'label' => 'FCP', 'name' => 'First Contentful Paint'],
        'experimental_time_to_first_byte'  => ['good' => 800,  'poor' => 1800, 'unit' => 'ms',  'label' => 'TTFB', 'name' => 'Time to First Byte'],
    ];

    /**
     * Fetch field data for a URL (or origin).
     *
     * @param string $target    URL or origin to query
     * @param string $formFactor PHONE|DESKTOP|TABLET|ALL
     * @param bool   $originLevel Query the whole origin instead of the exact URL
     */
    public function fetch(string $target, string $formFactor = 'PHONE', bool $originLevel = false): array
    {
        $apiKey = $this->apiKey();
        if (!$apiKey) {
            return $this->unavailable($target, $formFactor, 'no_api_key', 'No PageSpeed/CrUX API key configured. Add one in SEO Settings to enable real field data.');
        }

        $cacheKey = 'seo:crux:' . md5($target . '|' . $formFactor . '|' . ($originLevel ? 'origin' : 'url'));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($target, $formFactor, $originLevel, $apiKey) {
                return $this->request($target, $formFactor, $originLevel, $apiKey);
            });
        } catch (Throwable $e) {
            Log::warning('[SEO][CrUX] lookup failed', ['target' => $target, 'error' => $e->getMessage()]);

            return $this->unavailable($target, $formFactor, 'error', 'CrUX lookup failed: ' . $e->getMessage());
        }
    }

    /**
     * Fetch both URL-level and origin-level data. When a specific URL has too
     * little traffic, origin-level data is still a legitimate — and clearly
     * labelled — answer about the site as a whole.
     */
    public function fetchWithOriginFallback(string $url, string $formFactor = 'PHONE'): array
    {
        $urlLevel = $this->fetch($url, $formFactor, false);
        if ($urlLevel['has_data']) {
            return $urlLevel;
        }

        $origin = $this->originOf($url);
        if ($origin === null) {
            return $urlLevel;
        }

        $originData = $this->fetch($origin, $formFactor, true);
        if ($originData['has_data']) {
            $originData['fell_back_to_origin'] = true;
            $originData['requested_url'] = $url;
            $originData['note'] = 'This specific URL has too little Chrome traffic for its own dataset. Showing origin-wide field data instead.';
        }

        return $originData;
    }

    protected function request(string $target, string $formFactor, bool $originLevel, string $apiKey): array
    {
        $payload = [$originLevel ? 'origin' : 'url' => $target];
        if ($formFactor !== 'ALL') {
            $payload['formFactor'] = $formFactor;
        }

        $response = $this->providerHttp()
            ->post(self::ENDPOINT . '?key=' . urlencode($apiKey), $payload);

        // 404 is the documented "not enough real-user data" response. It is a
        // normal outcome for low-traffic pages, not an error condition.
        if ($response->status() === 404) {
            return $this->unavailable(
                $target,
                $formFactor,
                'insufficient_traffic',
                'Not enough real Chrome traffic for this ' . ($originLevel ? 'origin' : 'URL') . ' yet. CrUX needs a minimum sample size before it will report field data.'
            );
        }

        if (!$response->successful()) {
            $message = data_get($response->json(), 'error.message', 'HTTP ' . $response->status());

            return $this->unavailable($target, $formFactor, 'error', 'CrUX API error: ' . $message);
        }

        $record = $response->json('record', []);
        $metrics = [];

        foreach (self::THRESHOLDS as $key => $config) {
            $metric = data_get($record, "metrics.{$key}");
            if (!$metric) {
                continue;
            }

            $p75 = data_get($metric, 'percentiles.p75');
            if ($p75 === null) {
                continue;
            }
            $p75 = is_numeric($p75) ? (float) $p75 : null;
            if ($p75 === null) {
                continue;
            }

            // CrUX returns the good/needs-improvement/poor split directly —
            // this is the real distribution across users, not a single number.
            $bins = data_get($metric, 'histogram', []);

            $metrics[$key] = [
                'label'      => $config['label'],
                'name'       => $config['name'],
                'unit'       => $config['unit'],
                'p75'        => $p75,
                'display'    => $this->formatValue($p75, $config),
                'rating'     => $this->rate($p75, $config),
                'good_pct'   => (int) round((float) data_get($bins, '0.density', 0) * 100),
                'medium_pct' => (int) round((float) data_get($bins, '1.density', 0) * 100),
                'poor_pct'   => (int) round((float) data_get($bins, '2.density', 0) * 100),
                'threshold_good' => $config['good'],
                'threshold_poor' => $config['poor'],
            ];
        }

        if (empty($metrics)) {
            return $this->unavailable($target, $formFactor, 'insufficient_traffic', 'CrUX returned a record with no usable metrics for this target.');
        }

        return [
            'target'        => $target,
            'form_factor'   => $formFactor,
            'origin_level'  => $originLevel,
            'has_data'      => true,
            'data_source'   => 'CrUX field data (real Chrome users, 28-day rolling window)',
            'collection'    => data_get($record, 'collectionPeriod'),
            'metrics'       => $metrics,
            'cwv_pass'      => $this->passesCoreWebVitals($metrics),
        ];
    }

    /**
     * Google's Core Web Vitals assessment: LCP, INP, and CLS must all be
     * "good" at p75. FCP/TTFB are diagnostic and do not count toward the pass.
     */
    protected function passesCoreWebVitals(array $metrics): ?bool
    {
        $required = ['largest_contentful_paint', 'interaction_to_next_paint', 'cumulative_layout_shift'];

        foreach ($required as $key) {
            if (!isset($metrics[$key])) {
                return null; // Incomplete data — do not claim a pass or a fail.
            }
            if ($metrics[$key]['rating'] !== 'good') {
                return false;
            }
        }

        return true;
    }

    protected function rate(float $value, array $config): string
    {
        if ($value <= $config['good']) {
            return 'good';
        }
        if ($value <= $config['poor']) {
            return 'needs-improvement';
        }

        return 'poor';
    }

    protected function formatValue(float $value, array $config): string
    {
        if ($config['unit'] === 'ms') {
            return $value >= 1000
                ? round($value / 1000, 2) . ' s'
                : round($value) . ' ms';
        }

        return (string) round($value, 3);
    }

    protected function unavailable(string $target, string $formFactor, string $reason, string $message): array
    {
        return [
            'target'      => $target,
            'form_factor' => $formFactor,
            'has_data'    => false,
            'reason'      => $reason,
            'message'     => $message,
            'metrics'     => [],
            'cwv_pass'    => null,
        ];
    }

    protected function originOf(string $url): ?string
    {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'];
    }

    /** CrUX accepts the same API key as PageSpeed Insights. */
    protected function apiKey(): ?string
    {
        if (function_exists('get_setting')) {
            $key = get_setting('seo_pagespeed_api_key');
            if ($key) {
                return $key;
            }
        }

        return env('PAGESPEED_API_KEY') ?: env('GOOGLE_SEARCH_API_KEY') ?: null;
    }
}
