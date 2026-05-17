<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class AltTextService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $images  = $payload['images']  ?? [];
        $keyword = $payload['keyword'] ?? '';
        $url     = $payload['url']     ?? '';

        if (empty($images)) {
            return [
                'generated' => [],
                'message'   => 'No images provided.',
            ];
        }

        $imageList = implode("\n", array_map(fn($img, $i) => ($i + 1).'. '.$img, $images, array_keys($images)));

        $prompt = <<<PROMPT
Generate SEO-optimized alt text for the following images on a web page.

Page URL: {$url}
Focus Keyword: {$keyword}
Images:
{$imageList}

Rules:
- Alt text must be descriptive and concise (under 125 characters)
- Naturally incorporate focus keyword in 1-2 alts where relevant
- Do NOT start with "image of" or "photo of"
- Be contextually relevant to the page topic

Return JSON:
{
  "generated": [
    {"image_url": "", "alt_text": "", "char_count": 0, "keyword_included": false}
  ],
  "keyword_coverage_percent": 0
}
PROMPT;

        return $this->askForJson($prompt, 'You are an accessibility and SEO image specialist.', [
            'generated'                => [],
            'keyword_coverage_percent' => 0,
        ]);
    }
}
