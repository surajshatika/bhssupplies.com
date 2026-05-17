<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class CanonicalService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $url   = $this->normalizeUrl($payload['url'] ?? '');
        $links = $payload['links'] ?? [];

        $urlList = !empty($links) ? implode("\n", array_slice($links, 0, 50)) : 'No URLs provided';

        $prompt = <<<PROMPT
Analyze canonical URL issues and generate canonical tag recommendations.

Base URL: {$url}
Page URLs to analyze:
{$urlList}

Identify canonical issues: duplicate content, www vs non-www, http vs https, trailing slashes,
pagination, faceted navigation, URL parameters.

Return JSON:
{
  "canonical_issues": [
    {"url": "", "issue": "", "recommended_canonical": "", "severity": "critical|warning|info"}
  ],
  "canonical_strategy": "",
  "self_referencing_canonical": true,
  "pagination_strategy": "",
  "parameter_handling": [],
  "html_examples": [
    {"url": "", "canonical_tag": ""}
  ],
  "recommendations": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a technical SEO canonical URL specialist.', [
            'canonical_issues'           => [],
            'canonical_strategy'         => '',
            'self_referencing_canonical' => true,
            'pagination_strategy'        => '',
            'parameter_handling'         => [],
            'html_examples'              => [],
            'recommendations'            => [],
        ]);
    }
}
