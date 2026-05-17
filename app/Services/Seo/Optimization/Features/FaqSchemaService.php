<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class FaqSchemaService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $faq     = $payload['faq']     ?? [];
        $url     = $payload['url']     ?? '';
        $keyword = $payload['keyword'] ?? '';
        $content = $payload['content'] ?? '';

        // If no FAQ provided, generate from content
        if (empty($faq)) {
            $prompt = <<<PROMPT
Generate 5-8 frequently asked questions (FAQ) with answers for the following page, then create FAQ Schema markup.

URL: {$url}
Focus Keyword: {$keyword}
Page Content:
{$content}

Return JSON:
{
  "faqs": [{"question": "", "answer": ""}],
  "schema_json_ld": {},
  "html_snippet": "",
  "seo_tips": []
}
PROMPT;
        } else {
            $faqJson = json_encode($faq);
            $prompt = <<<PROMPT
Create FAQ Schema (JSON-LD) markup for the following FAQ items.

URL: {$url}
FAQ Items: {$faqJson}

Return JSON:
{
  "faqs": [],
  "schema_json_ld": {},
  "html_snippet": "",
  "seo_tips": []
}
PROMPT;
        }

        return $this->askForJson($prompt, 'You are a structured data and FAQ schema specialist.', [
            'faqs'          => $faq,
            'schema_json_ld' => [],
            'html_snippet'  => '',
            'seo_tips'      => [],
        ]);
    }
}
