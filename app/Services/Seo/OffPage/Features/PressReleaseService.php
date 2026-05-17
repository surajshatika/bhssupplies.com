<?php

namespace App\Services\Seo\OffPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class PressReleaseService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $url     = $payload['url']     ?? '';
        $title   = $payload['title']   ?? '';
        $keyword = $payload['keyword'] ?? '';
        $content = $payload['content'] ?? '';

        $prompt = <<<PROMPT
Write a professional SEO-optimized press release for the following.

URL: {$url}
Headline Topic: {$title}
Focus Keyword: {$keyword}
Background Details:
{$content}

The press release must:
- Follow AP style
- Include keyword naturally 2-3 times
- Have a compelling headline (includes keyword)
- Include a dateline, lead paragraph (who, what, where, when, why)
- Include a quote from a company representative
- End with a boilerplate "About" section
- Include 1-2 distribution-ready anchor text link suggestions

Return JSON:
{
  "headline": "",
  "subheadline": "",
  "dateline": "",
  "body": "",
  "quote": {"speaker": "", "title": "", "text": ""},
  "boilerplate": "",
  "cta": "",
  "anchor_links": [{"anchor_text": "", "url": ""}],
  "distribution_sites": [],
  "word_count": 0
}
PROMPT;

        return $this->askForJson($prompt, 'You are a PR copywriter and SEO link building expert.', [
            'headline'         => $title,
            'subheadline'      => '',
            'dateline'         => '',
            'body'             => '',
            'quote'            => [],
            'boilerplate'      => '',
            'cta'              => '',
            'anchor_links'     => [],
            'distribution_sites' => [],
            'word_count'       => 0,
        ]);
    }
}
