<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class KeywordDensityService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $keyword = $payload['keyword'] ?? '';
        $content = $payload['content'] ?? '';
        $wordCount = $this->wordCount($content);

        $prompt = <<<PROMPT
Analyze keyword density for the following content.

Focus Keyword: {$keyword}
Total Word Count: {$wordCount}
Content:
{$content}

Analyze and return JSON:
{
  "focus_keyword": "",
  "occurrences": 0,
  "density_percent": 0.0,
  "ideal_range": "1-3%",
  "status": "optimal|too_low|too_high",
  "lsi_keywords_found": [],
  "lsi_keywords_missing": [],
  "keyword_placement": {
    "in_title": false,
    "in_h1": false,
    "in_first_paragraph": false,
    "in_last_paragraph": false,
    "in_meta_description": false
  },
  "recommendations": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are an SEO content analyst.', [
            'focus_keyword'      => $keyword,
            'occurrences'        => 0,
            'density_percent'    => 0.0,
            'status'             => 'unknown',
            'recommendations'    => [],
        ]);
    }
}
