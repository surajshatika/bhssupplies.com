<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class PageSpeedService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $url = $this->normalizeUrl($payload['url'] ?? '');
        if (!$url) {
            return ['error' => 'URL is required.'];
        }

        // Attempt Google PageSpeed Insights API if key is configured
        $apiKey = config('services.google.pagespeed_key');
        $apiData = null;

        if ($apiKey) {
            try {
                $response = Http::timeout(15)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                    'url'      => $url,
                    'key'      => $apiKey,
                    'strategy' => 'mobile',
                ]);
                if ($response->successful()) {
                    $apiData = $response->json();
                }
            } catch (\Throwable $e) {
                // fall through to AI analysis
            }
        }

        $contextJson = $apiData ? json_encode(array_slice((array) $apiData, 0, 20)) : 'No API data available';

        $prompt = <<<PROMPT
Analyze page speed and Core Web Vitals for the following URL and provide an actionable fix plan.

URL: {$url}
PageSpeed API Data (partial): {$contextJson}

Return JSON:
{
  "url": "",
  "mobile_score": 0,
  "desktop_score": 0,
  "core_web_vitals": {
    "lcp": {"value": "", "status": "good|needs_improvement|poor"},
    "fid": {"value": "", "status": "good|needs_improvement|poor"},
    "cls": {"value": "", "status": "good|needs_improvement|poor"},
    "ttfb": {"value": "", "status": "good|needs_improvement|poor"}
  },
  "opportunities": [
    {"title": "", "savings_ms": 0, "fix": ""}
  ],
  "diagnostics": [],
  "action_plan": [],
  "estimated_improvement": ""
}
PROMPT;

        return $this->askForJson($prompt, 'You are a web performance and Core Web Vitals expert.', [
            'url'             => $url,
            'mobile_score'    => 0,
            'desktop_score'   => 0,
            'core_web_vitals' => [],
            'opportunities'   => [],
            'diagnostics'     => [],
            'action_plan'     => [],
        ]);
    }
}
