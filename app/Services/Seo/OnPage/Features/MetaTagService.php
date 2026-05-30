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
You are an SEO specialist. Generate optimized meta tags for the following page.

URL: {$url}
Page Title: {$title}
Focus Keyword: {$keyword}
Content Summary: {$content}

Rules:
- Meta title: 50–60 characters, focus keyword in the FIRST 3 words, add a power word (Best, Trusted, Wholesale) where natural.
- Meta description: 150–160 characters (never under 150), include the focus keyword once, a benefit, and a clear CTA.
- Suggest one H1 and two H2 subheadings that each contain the focus keyword or a close variant — this fixes "keywords not distributed across headings".
- Provide 3 alternatives for both title and description.
- Return valid JSON only.

JSON schema:
{
  "primary": { "title": "", "description": "" },
  "alternatives": [
    { "title": "", "description": "" },
    { "title": "", "description": "" },
    { "title": "", "description": "" }
  ],
  "suggested_h1": "",
  "suggested_h2": ["", ""],
  "keyword_placement_tips": ""
}
PROMPT;

        return $this->askForJson($prompt, 'You are an expert SEO content strategist.', [
            'primary'      => ['title' => $title, 'description' => ''],
            'alternatives' => [],
            'suggested_h1' => '',
            'suggested_h2' => [],
            'keyword_placement_tips' => '',
        ]);
    }
}
