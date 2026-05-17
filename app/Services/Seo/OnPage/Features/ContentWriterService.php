<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class ContentWriterService extends AbstractSeoService
{
    public function write(array $payload): array
    {
        $topic   = $payload['topic']   ?? $payload['title']   ?? '';
        $keyword = $payload['keyword'] ?? '';
        $url     = $payload['url']     ?? '';
        $length  = $payload['length']  ?? 1500;

        $prompt = <<<PROMPT
Write a high-quality, SEO-optimized article on the following topic.

Topic: {$topic}
Focus Keyword: {$keyword}
Target URL: {$url}
Target Word Count: ~{$length} words

Requirements:
- Include a compelling H1 title
- Use keyword naturally 2-3 times per 500 words
- Include H2 and H3 subheadings
- Write in an engaging, informative tone
- Add a meta title (≤60 chars) and meta description (≤160 chars)
- Include an FAQ section with 3-5 questions
- End with a clear call to action

Return valid JSON:
{
  "meta_title": "",
  "meta_description": "",
  "h1": "",
  "article_html": "",
  "word_count": 0,
  "headings": [],
  "faq": [{"question": "", "answer": ""}],
  "cta": ""
}
PROMPT;

        return $this->askForJson($prompt, 'You are an expert SEO content writer.', [
            'meta_title'       => $topic,
            'meta_description' => '',
            'h1'               => $topic,
            'article_html'     => '',
            'word_count'       => 0,
            'headings'         => [],
            'faq'              => [],
            'cta'              => '',
        ]);
    }
}
