<?php

namespace App\Services\Seo\OffPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class SocialSignalService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $url     = $payload['url']     ?? '';
        $title   = $payload['title']   ?? '';
        $keyword = $payload['keyword'] ?? '';
        $cta     = $payload['cta']     ?? 'Check it out';

        $prompt = <<<PROMPT
Create social media posts to build social signals for the following page/content.

URL: {$url}
Title/Topic: {$title}
Focus Keyword: {$keyword}
CTA: {$cta}

Write optimized posts for: Twitter/X, Facebook, LinkedIn, Instagram, Pinterest.

Return JSON:
{
  "platforms": {
    "twitter": {
      "post": "",
      "hashtags": [],
      "char_count": 0,
      "best_time": ""
    },
    "facebook": {
      "post": "",
      "hashtags": [],
      "best_time": ""
    },
    "linkedin": {
      "post": "",
      "hashtags": [],
      "best_time": ""
    },
    "instagram": {
      "caption": "",
      "hashtags": [],
      "best_time": ""
    },
    "pinterest": {
      "description": "",
      "board_suggestion": "",
      "hashtags": []
    }
  },
  "posting_schedule": [],
  "engagement_tips": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a social media marketing and SEO specialist.', [
            'platforms'       => [],
            'posting_schedule' => [],
            'engagement_tips' => [],
        ]);
    }
}
