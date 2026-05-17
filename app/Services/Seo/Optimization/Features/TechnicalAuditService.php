<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class TechnicalAuditService extends AbstractSeoService
{
    public function audit(array $payload): array
    {
        $url = $this->normalizeUrl($payload['url'] ?? url('/'));

        // Fetch page HTML for analysis
        $html = '';
        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                $html = substr($response->body(), 0, 5000);
            }
        } catch (\Throwable $e) {
            // continue with AI-only analysis
        }

        $htmlContext = $html ? "Page HTML (first 5000 chars):\n{$html}" : "HTML could not be fetched.";

        $prompt = <<<PROMPT
Perform a technical SEO audit for the following website/page.

URL: {$url}
{$htmlContext}

Audit technical factors: HTTPS, canonical tags, robots.txt, sitemap.xml, structured data,
hreflang, pagination, mobile viewport, noindex tags, page speed, 404 errors, redirect chains,
duplicate content signals, XML sitemap presence, Core Web Vitals readiness.

Return JSON:
{
  "url": "",
  "score": 0,
  "grade": "A",
  "checks": {
    "https": {"pass": false, "message": ""},
    "canonical": {"pass": false, "message": ""},
    "robots_txt": {"pass": false, "message": ""},
    "sitemap_xml": {"pass": false, "message": ""},
    "structured_data": {"pass": false, "message": ""},
    "mobile_viewport": {"pass": false, "message": ""},
    "noindex_check": {"pass": false, "message": ""},
    "redirect_chains": {"pass": false, "message": ""},
    "page_speed": {"pass": false, "message": ""},
    "hreflang": {"pass": false, "message": ""}
  },
  "critical_issues": [],
  "warnings": [],
  "passed_checks": [],
  "recommendations": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a technical SEO specialist.', [
            'url'             => $url,
            'score'           => 0,
            'grade'           => 'F',
            'checks'          => [],
            'critical_issues' => [],
            'warnings'        => [],
            'passed_checks'   => [],
            'recommendations' => [],
        ]);
    }
}
