<?php

namespace App\Services\Seo\OffPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class AnchorTextService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $url         = $payload['url']         ?? '';
        $keyword     = $payload['keyword']     ?? '';
        $anchorText  = $payload['anchor_text'] ?? '';

        $prompt = <<<PROMPT
Build and analyze an SEO anchor text profile for the following website/page.

Target URL: {$url}
Primary Keyword: {$keyword}
Current Anchor Texts (comma-separated):
{$anchorText}

Analyze the anchor text profile and generate a healthy distribution strategy.

Rules for healthy anchor text profile:
- Exact match: 5-10%
- Partial match: 20-30%
- Branded: 30-40%
- Naked URL: 10-15%
- Generic (click here, etc.): 5-10%
- LSI / Related: 10-20%

Return JSON:
{
  "current_profile": {
    "exact_match_percent": 0,
    "partial_match_percent": 0,
    "branded_percent": 0,
    "naked_url_percent": 0,
    "generic_percent": 0,
    "lsi_percent": 0
  },
  "health_status": "healthy|over_optimized|under_optimized",
  "issues": [],
  "recommended_anchors": [
    {"type": "exact_match", "text": "", "usage_percent": 0},
    {"type": "partial_match", "text": "", "usage_percent": 0},
    {"type": "branded", "text": "", "usage_percent": 0},
    {"type": "lsi", "text": "", "usage_percent": 0}
  ],
  "action_plan": [],
  "risk_level": "low|medium|high"
}
PROMPT;

        return $this->askForJson($prompt, 'You are a link building and anchor text profile specialist.', [
            'current_profile'     => [],
            'health_status'       => 'unknown',
            'issues'              => [],
            'recommended_anchors' => [],
            'action_plan'         => [],
            'risk_level'          => 'unknown',
        ]);
    }
}
