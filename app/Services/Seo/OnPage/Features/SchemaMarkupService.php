<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class SchemaMarkupService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $url     = $payload['url']     ?? '';
        $title   = $payload['title']   ?? '';
        $content = $payload['content'] ?? '';
        $keyword = $payload['keyword'] ?? '';

        $prompt = <<<PROMPT
Generate structured data (Schema.org JSON-LD) markup for the following page.

Page URL: {$url}
Page Title: {$title}
Focus Keyword: {$keyword}
Page Content Summary:
{$content}

Generate appropriate schema types (Article, Product, FAQPage, BreadcrumbList, WebPage, etc.).

Return JSON:
{
  "schemas": [
    {
      "type": "Article",
      "json_ld": {},
      "placement": "head|body",
      "priority": "high|medium|low"
    }
  ],
  "recommended_types": [],
  "rich_result_eligible": [],
  "implementation_notes": ""
}
PROMPT;

        return $this->askForJson($prompt, 'You are a structured data and schema markup expert.', [
            'schemas'              => [],
            'recommended_types'    => [],
            'rich_result_eligible' => [],
            'implementation_notes' => '',
        ]);
    }
}
