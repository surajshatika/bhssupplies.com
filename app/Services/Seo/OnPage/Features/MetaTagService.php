<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class MetaTagService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $url     = $payload['url']     ?? '';
        $title   = $payload['title']   ?? '';
        $keyword = $payload['keyword'] ?? '';
        $content = $payload['content'] ?? '';

        $prompt = <<<PROMPT
You are an SEO specialist. Generate optimized meta title and meta description for the following page.

URL: {$url}
Page Title: {$title}
Focus Keyword: {$keyword}
Content Summary: {$content}

Rules:
- Meta title: 50–60 characters, include focus keyword near the start
- Meta description: 150–160 characters, include focus keyword, compelling CTA
- Provide 3 alternatives for both title and description
- Return valid JSON only

JSON schema:
{
  "primary": { "title": "", "description": "" },
  "alternatives": [
    { "title": "", "description": "" },
    { "title": "", "description": "" },
    { "title": "", "description": "" }
  ],
  "keyword_placement_tips": ""
}
PROMPT;

        return $this->askForJson($prompt, 'You are an expert SEO content strategist.', [
            'primary'      => ['title' => $title, 'description' => ''],
            'alternatives' => [],
            'keyword_placement_tips' => '',
        ]);
    }
}
