<?php

namespace App\Services\Seo\Speed;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google PageSpeed Insights v5 API connector.
 *
 * Free to use without an API key (anonymous quota is limited), or with an
 * API key paste from the Google Cloud Console (Maps/Cloud APIs > PageSpeed
 * Insights). Returns Lighthouse Performance / SEO / Accessibility / Best
 * Practices scores plus Core Web Vitals (LCP, CLS, INP, TTFB, FCP).
 *
 * Settings consulted:
 *   - seo_pagespeed_api_key (optional, raises the quota ceiling)
 *   - seo_google_search_api_key (fallback — same key works for many APIs)
 */
class PageSpeedInsightsService
{
    public const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public function isConfigured(): bool
    {
        // Anonymous calls are allowed; we treat the API key as optional.
        return true;
    }

    public function apiKey(): ?string
    {
        // ONLY use a key explicitly scoped for PageSpeed Insights.
        // The Custom Search key (seo_google_search_api_key) is a different
        // service and Google will reject it for PSI.
        return get_setting('seo_pagespeed_api_key') ?: env('SEO_PAGESPEED_API_KEY');
    }

    /**
     * @param  string $url
     * @param  string $strategy  desktop | mobile
     * @param  array  $categories any subset of: performance, seo, accessibility, best-practices, pwa
     * @return array{success:bool, url:string, strategy:string, scores:array, vitals:array, fetched_at:string, error:?string}
     */
    public function audit(string $url, string $strategy = 'mobile', array $categories = ['performance','seo','accessibility','best-practices']): array
    {
        $params = [
            'url'      => $url,
            'strategy' => $strategy === 'desktop' ? 'desktop' : 'mobile',
        ];
        foreach ($categories as $c) {
            $params['category'][] = strtoupper(str_replace('-', '_', $c));
        }
        if ($key = $this->apiKey()) {
            $params['key'] = $key;
        }

        try {
            $resp = Http::timeout(120)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->get(self::ENDPOINT, $params);

            if (!$resp->successful()) {
                return $this->fail($url, $strategy, 'HTTP ' . $resp->status() . ': ' . $resp->json('error.message', $resp->body()));
            }

            $lh = $resp->json('lighthouseResult') ?? [];
            $catScores = $lh['categories'] ?? [];
            $audits    = $lh['audits']     ?? [];

            $scores = [];
            foreach ($catScores as $key => $cat) {
                $scores[$key] = isset($cat['score']) ? (int) round((float) $cat['score'] * 100) : null;
            }

            $vitals = [
                'lcp_ms'  => (int) ($audits['largest-contentful-paint']['numericValue'] ?? 0),
                'fcp_ms'  => (int) ($audits['first-contentful-paint']['numericValue']  ?? 0),
                'cls'     => round((float) ($audits['cumulative-layout-shift']['numericValue'] ?? 0), 3),
                'inp_ms'  => (int) ($audits['interaction-to-next-paint']['numericValue']  ?? 0),
                'tbt_ms'  => (int) ($audits['total-blocking-time']['numericValue']        ?? 0),
                'ttfb_ms' => (int) ($audits['server-response-time']['numericValue']       ?? 0),
                'si_ms'   => (int) ($audits['speed-index']['numericValue']                 ?? 0),
            ];

            return [
                'success'    => true,
                'url'        => $url,
                'strategy'   => $strategy,
                'scores'     => $scores,
                'vitals'     => $vitals,
                'fetched_at' => now()->toDateTimeString(),
                'error'      => null,
            ];
        } catch (Throwable $e) {
            return $this->fail($url, $strategy, $e->getMessage());
        }
    }

    protected function fail(string $url, string $strategy, string $error): array
    {
        return [
            'success'    => false,
            'url'        => $url,
            'strategy'   => $strategy,
            'scores'     => [],
            'vitals'     => [],
            'fetched_at' => now()->toDateTimeString(),
            'error'      => $error,
        ];
    }
}
