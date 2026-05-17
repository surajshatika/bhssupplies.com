<?php

namespace App\Services\Seo\OffPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class GuestPostService extends AbstractSeoService
{
    public function generateTopics(array $payload): array
    {
        $niche   = $payload['topic']   ?? $payload['keyword'] ?? '';
        $url     = $payload['url']     ?? '';
        $keyword = $payload['keyword'] ?? '';

        $prompt = <<<PROMPT
Generate guest post topic ideas for the following niche/website.

Niche/Industry: {$niche}
Website: {$url}
Focus Keyword: {$keyword}

Generate 10 compelling guest post topics that:
- Will be accepted by authoritative blogs
- Include the focus keyword or related terms
- Provide genuine value to readers

Return JSON:
{
  "topics": [
    {
      "title": "",
      "target_blog_type": "",
      "keyword_angle": "",
      "estimated_traffic_potential": "low|medium|high",
      "difficulty": "easy|medium|hard"
    }
  ],
  "niche_analysis": "",
  "top_target_sites": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a guest posting and link building strategist.', [
            'topics'           => [],
            'niche_analysis'   => '',
            'top_target_sites' => [],
        ]);
    }

    public function writeArticle(array $payload): array
    {
        $topic   = $payload['topic']   ?? $payload['title'] ?? '';
        $keyword = $payload['keyword'] ?? '';
        $url     = $payload['url']     ?? '';

        $prompt = <<<PROMPT
Write a complete guest post article on the following topic.

Topic: {$topic}
Focus Keyword: {$keyword}
Author Website: {$url}
Target Word Count: 1200-1500 words

Include a natural author bio at the end with a link back to {$url}.

Return JSON:
{
  "title": "",
  "meta_description": "",
  "article_html": "",
  "word_count": 0,
  "author_bio": "",
  "cta": "",
  "target_publications": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are an expert guest post writer and link builder.', [
            'title'               => $topic,
            'meta_description'    => '',
            'article_html'        => '',
            'word_count'          => 0,
            'author_bio'          => '',
            'cta'                 => '',
            'target_publications' => [],
        ]);
    }
}
