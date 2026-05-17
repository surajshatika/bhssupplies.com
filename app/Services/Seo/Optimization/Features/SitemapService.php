<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\File;

class SitemapService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $baseUrl = $this->normalizeUrl($payload['url'] ?? url('/'));
        $urls    = $payload['links'] ?? [];

        // Build sitemap XML
        $now    = now()->toAtomString();
        $urlEntries = '';

        foreach ($urls as $pageUrl) {
            $escaped = htmlspecialchars($pageUrl, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $urlEntries .= <<<XML

  <url>
    <loc>{$escaped}</loc>
    <lastmod>{$now}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
XML;
        }

        // If no URLs provided, ask AI to suggest a sitemap structure
        if (empty($urlEntries)) {
            $prompt = <<<PROMPT
Generate a sitemap.xml structure recommendation for an e-commerce website.

Base URL: {$baseUrl}

Suggest the most important URL patterns for an e-commerce store sitemap (homepage, categories, products, blog, contact, etc.).

Return JSON:
{
  "recommended_url_patterns": [],
  "sitemap_sections": [
    {"name": "", "priority": 0.0, "changefreq": "", "example_urls": []}
  ],
  "best_practices": []
}
PROMPT;
            $aiResult = $this->askForJson($prompt, 'You are an XML sitemap and technical SEO expert.', [
                'recommended_url_patterns' => [],
                'sitemap_sections'         => [],
                'best_practices'           => [],
            ]);
            return array_merge($aiResult, ['sitemap_xml' => '', 'url_count' => 0]);
        }

        $sitemapXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">{$urlEntries}
</urlset>
XML;

        // Save to public directory
        $savePath = public_path('sitemap.xml');
        try {
            File::put($savePath, $sitemapXml);
            $saved = true;
        } catch (\Throwable $e) {
            $saved = false;
        }

        return [
            'sitemap_xml' => $sitemapXml,
            'url_count'   => count($urls),
            'saved'       => $saved,
            'path'        => $saved ? url('sitemap.xml') : null,
        ];
    }
}
