<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class SeoAuditService extends AbstractSeoService
{
    public function audit(array $payload): array
    {
        $url     = $payload['url']     ?? '';
        $title   = $payload['title']   ?? '';
        $keyword = $payload['keyword'] ?? '';
        $content = $payload['content'] ?? '';
        $wordCount = $this->wordCount($content);

        $prompt = <<<PROMPT
Perform a comprehensive on-page SEO audit (20+ factors) for the following page.

URL: {$url}
Page Title: {$title}
Focus Keyword: {$keyword}
Word Count: {$wordCount}
Content Preview (first 2000 chars):
{$content}

Audit all 20+ on-page SEO factors including:
title tag, meta description, URL structure, H1, H2-H6, keyword density, content length,
image alt text, internal links, external links, page speed hints, mobile friendliness,
schema markup, canonical tag, Open Graph, robots meta, structured data, LSI keywords,
content freshness, E-E-A-T signals.

Return JSON:
{
  "score": 0,
  "grade": "A",
  "checks": {
    "title_tag": {"pass": false, "message": ""},
    "meta_description": {"pass": false, "message": ""},
    "url_structure": {"pass": false, "message": ""},
    "h1_tag": {"pass": false, "message": ""},
    "heading_hierarchy": {"pass": false, "message": ""},
    "keyword_density": {"pass": false, "message": ""},
    "content_length": {"pass": false, "message": ""},
    "image_alt_text": {"pass": false, "message": ""},
    "internal_links": {"pass": false, "message": ""},
    "external_links": {"pass": false, "message": ""},
    "schema_markup": {"pass": false, "message": ""},
    "canonical_tag": {"pass": false, "message": ""},
    "open_graph": {"pass": false, "message": ""},
    "robots_meta": {"pass": false, "message": ""},
    "lsi_keywords": {"pass": false, "message": ""},
    "content_freshness": {"pass": false, "message": ""},
    "eeat_signals": {"pass": false, "message": ""},
    "mobile_friendly": {"pass": false, "message": ""},
    "page_speed_hints": {"pass": false, "message": ""},
    "structured_data": {"pass": false, "message": ""}
  },
  "critical_issues": [],
  "warnings": [],
  "passed": [],
  "action_plan": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a senior on-page SEO auditor.', [
            'score'           => 0,
            'grade'           => 'F',
            'checks'          => [],
            'critical_issues' => [],
            'warnings'        => [],
            'passed'          => [],
            'action_plan'     => [],
        ]);
    }
}
