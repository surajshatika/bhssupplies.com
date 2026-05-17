<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class InternalLinkService extends AbstractSeoService
{
    public function suggest(array $payload): array
    {
        $url     = $payload['url']     ?? '';
        $content = $payload['content'] ?? '';
        $keyword = $payload['keyword'] ?? '';
        $links   = $payload['links']   ?? [];

        $linkList = !empty($links) ? implode("\n", $links) : 'Not provided';

        $prompt = <<<PROMPT
Suggest internal linking opportunities for the following page content.

Current Page URL: {$url}
Focus Keyword: {$keyword}
Existing Site Pages (for linking):
{$linkList}

Content:
{$content}

Analyze the content and return JSON:
{
  "suggestions": [
    {
      "anchor_text": "",
      "target_url": "",
      "context_sentence": "",
      "reason": ""
    }
  ],
  "total_links_found": 0,
  "link_equity_score": 0,
  "orphan_risk": "low|medium|high",
  "recommendations": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are an internal linking SEO strategist.', [
            'suggestions'       => [],
            'total_links_found' => 0,
            'link_equity_score' => 0,
            'orphan_risk'       => 'unknown',
            'recommendations'   => [],
        ]);
    }
}
