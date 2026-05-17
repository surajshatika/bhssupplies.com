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

        $opportunities = $this->findLinkOpportunities($keyword, $content, $siteUrl);

        $prompt = "You are a link building and internal linking expert. Analyze this page:\n"
            . "URL: {$url}\n"
            . "Target Keyword: {$keyword}\n"
            . "Content preview: " . substr($content, 0, 500) . "\n\n"
            . "Found internal linking opportunities: " . json_encode(array_slice($opportunities['internal'], 0, 10)) . "\n\n"
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

        // Suggested external authority links
        if ($keyword) {
            $keywordLower = strtolower($keyword);
            if (str_contains($keywordLower, 'safety') || str_contains($keywordLower, 'industrial')) {
                $external = [
                    ['url' => 'https://osha.gov', 'domain_authority' => 90, 'type' => 'authority'],
                    ['url' => 'https://nsc.org', 'domain_authority' => 72, 'type' => 'industry'],
                    ['url' => 'https://asse.org', 'domain_authority' => 60, 'type' => 'industry'],
                ];
            }
        }

        return ['internal' => $internal, 'external' => $external];
    }
}
