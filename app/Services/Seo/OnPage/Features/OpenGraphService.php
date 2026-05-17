<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class OpenGraphService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $url         = $payload['url']         ?? '';
        $title       = $payload['title']       ?? '';
        $description = $payload['description'] ?? '';
        $keyword     = $payload['keyword']     ?? '';

        $prompt = <<<PROMPT
Generate Open Graph and Twitter Card meta tags for the following page.

URL: {$url}
Page Title: {$title}
Description: {$description}
Focus Keyword: {$keyword}

Generate complete Open Graph (og:) and Twitter Card (twitter:) meta tags.

Return JSON:
{
  "open_graph": {
    "og:title": "",
    "og:description": "",
    "og:url": "",
    "og:type": "website",
    "og:image": "",
    "og:site_name": "",
    "og:locale": "en_US"
  },
  "twitter_card": {
    "twitter:card": "summary_large_image",
    "twitter:title": "",
    "twitter:description": "",
    "twitter:image": "",
    "twitter:site": ""
  },
  "html_snippet": "",
  "recommendations": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a social media SEO and Open Graph specialist.', [
            'open_graph'      => [],
            'twitter_card'    => [],
            'html_snippet'    => '',
            'recommendations' => [],
        ]);
    }
}
