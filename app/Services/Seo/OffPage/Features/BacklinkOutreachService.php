<?php

namespace App\Services\Seo\OffPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class BacklinkOutreachService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $url         = $payload['url']          ?? '';
        $topic       = $payload['topic']        ?? $payload['title'] ?? '';
        $keyword     = $payload['keyword']      ?? '';
        $contactName = $payload['contact_name'] ?? 'Website Owner';
        $targetSite  = $payload['target_site']  ?? '';

        $prompt = <<<PROMPT
Write a professional backlink outreach email for the following context.

Our Website: {$url}
Topic / Page: {$topic}
Focus Keyword: {$keyword}
Contact Name: {$contactName}
Target Website: {$targetSite}

Write 3 different outreach email variants (friendly, professional, value-first).

Return JSON:
{
  "emails": [
    {
      "variant": "friendly",
      "subject": "",
      "body": "",
      "tone": ""
    },
    {
      "variant": "professional",
      "subject": "",
      "body": "",
      "tone": ""
    },
    {
      "variant": "value_first",
      "subject": "",
      "body": "",
      "tone": ""
    }
  ],
  "follow_up_template": "",
  "outreach_tips": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a link building and digital PR specialist.', [
            'emails'            => [],
            'follow_up_template' => '',
            'outreach_tips'     => [],
        ]);
    }
}
