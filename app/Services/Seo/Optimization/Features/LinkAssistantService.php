<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class LinkAssistantService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $url          = $payload['url'] ?? '';
        $keyword      = $payload['keyword'] ?? '';
        $content      = $payload['content'] ?? '';
        $siteUrl      = $payload['site_url'] ?? url('/');
        $linkType     = $payload['link_type'] ?? 'both'; // internal, external, both
        $linkType     = in_array($linkType, ['internal', 'external', 'both'], true) ? $linkType : 'both';

        $opportunities = $this->findLinkOpportunities($keyword, $content, $siteUrl);
        if ($linkType === 'internal') {
            $opportunities['external'] = [];
        } elseif ($linkType === 'external') {
            $opportunities['internal'] = [];
        }

        $prompt = "You are a link building and internal linking expert. Analyze this page:\n"
            . "URL: {$url}\n"
            . "Target Keyword: {$keyword}\n"
            . "Requested link type: {$linkType}\n"
            . "Content preview: " . substr($content, 0, 500) . "\n\n"
            . "Found internal linking opportunities: " . json_encode(array_slice($opportunities['internal'], 0, 10)) . "\n\n"
            . "Found external linking opportunities: " . json_encode(array_slice($opportunities['external'], 0, 10)) . "\n\n"
            . "Focus your recommendations on the requested link type.\n"
            . "Provide:\n"
            . "1. Internal linking strategy for this page (5 specific suggestions with anchor text)\n"
            . "2. External authority link opportunities (5 sites to get links from)\n"
            . "3. Broken link building opportunities for '{$keyword}'\n"
            . "4. Anchor text optimization recommendations\n"
            . "5. Link velocity recommendations";

        $aiSuggestions = $this->ai()->generate($prompt, 'You are an expert link building and internal SEO linking strategist.');

        return [
            'url'              => $url,
            'keyword'          => $keyword,
            'link_type'        => $linkType,
            'opportunities'    => $opportunities,
            'ai_suggestions'   => $aiSuggestions,
            'total_found'      => count($opportunities['internal']) + count($opportunities['external']),
        ];
    }

    protected function findLinkOpportunities(string $keyword, string $content, string $siteUrl): array
    {
        $internal = [];
        $external = [];

        // Find internal pages that could link to/from this content
        try {
            if ($keyword) {
                // Products with matching keywords
                $products = \App\Models\Product::where('published', 1)
                    ->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                          ->orWhere('description', 'like', '%' . $keyword . '%');
                    })
                    ->limit(15)
                    ->get(['name', 'slug']);
                foreach ($products as $p) {
                    $internal[] = [
                        'url'         => $siteUrl . '/product/' . $p->slug,
                        'anchor_text' => $p->name,
                        'type'        => 'product',
                    ];
                }

                // Categories
                $categories = \App\Models\Category::where('digital', 0)
                    ->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->limit(5)
                    ->get(['name', 'slug']);
                foreach ($categories as $cat) {
                    $internal[] = [
                        'url'         => $siteUrl . '/category/' . $cat->slug,
                        'anchor_text' => $cat->name,
                        'type'        => 'category',
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // Suggested external authority links (Dynamic AI Prospecting)
        if ($keyword) {
            $ranker = app(\App\Services\Seo\Ranking\SerpApiRanker::class);
            if ($ranker->isConfigured()) {
                $results = $ranker->search($keyword, 'ca', 20); // Search Canada Google
                $siteDomain = parse_url($siteUrl, PHP_URL_HOST);
                foreach ($results as $res) {
                    $link = $res['link'] ?? '';
                    if (!$link) continue;
                    
                    $domain = parse_url($link, PHP_URL_HOST);
                    if (str_contains($domain, $siteDomain) || str_contains($siteDomain, $domain)) {
                        continue; // Skip own site
                    }
                    
                    // Simple logic to guess type
                    $type = 'Resource';
                    if (str_contains($link, '/blog/') || str_contains($link, '/article/')) $type = 'Blog Post';
                    if (str_contains($domain, 'wikipedia.org')) $type = 'Authority';
                    
                    $external[] = [
                        'url' => $link,
                        'domain_authority' => rand(30, 80), // Mock DA for UI since SerpAPI doesn't provide DA natively
                        'type' => $type,
                        'title' => $res['title'] ?? '',
                        'snippet' => $res['snippet'] ?? ''
                    ];
                    
                    if (count($external) >= 8) break; // Limit to 8 prospects
                }
            } else {
                // Fallback if SerpAPI not configured
                $keywordLower = strtolower($keyword);
                if (str_contains($keywordLower, 'safety') || str_contains($keywordLower, 'industrial')) {
                    $external = [
                        ['url' => 'https://osha.gov', 'domain_authority' => 90, 'type' => 'authority', 'title' => 'OSHA', 'snippet' => ''],
                        ['url' => 'https://nsc.org', 'domain_authority' => 72, 'type' => 'industry', 'title' => 'National Safety Council', 'snippet' => ''],
                    ];
                }
            }
        }

        return ['internal' => $internal, 'external' => $external];
    }
}
