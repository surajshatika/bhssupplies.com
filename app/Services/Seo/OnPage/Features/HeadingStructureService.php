<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class HeadingStructureService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $content = $payload['content'] ?? '';
        $keyword = $payload['keyword'] ?? '';
        $url     = $payload['url']     ?? '';

        $prompt = <<<PROMPT
Analyze and improve the heading structure (H1–H6) of the following page content.

URL: {$url}
Focus Keyword: {$keyword}
Content:
{$content}

Evaluate:
1. Whether H1 exists and contains focus keyword
2. Heading hierarchy (no skipping levels)
3. Keyword usage in H2/H3
4. Number of headings vs content length

Return JSON:
{
  "h1_present": false,
  "h1_contains_keyword": false,
  "h1_text": "",
  "hierarchy_valid": false,
  "headings_found": [{"level": "H1", "text": ""}],
  "issues": [],
  "improved_structure": [{"level": "H1", "text": "", "notes": ""}],
  "score": 0,
  "grade": "F"
}
PROMPT;

        return $this->askForJson($prompt, 'You are an on-page SEO specialist.', [
            'h1_present'          => false,
            'h1_contains_keyword' => false,
            'h1_text'             => '',
            'hierarchy_valid'     => false,
            'headings_found'      => [],
            'issues'              => [],
            'improved_structure'  => [],
            'score'               => 0,
            'grade'               => 'F',
        ]);
    }
}
