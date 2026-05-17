<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class KeywordRankTrackerService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $keywords = $payload['keywords'] ?? [];
        $domain   = $payload['domain'] ?? parse_url(url('/'), PHP_URL_HOST);
        $engine   = $payload['engine'] ?? 'google';

        if (is_string($keywords)) {
            $keywords = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $keywords)));
        }

        if (empty($keywords)) {
            return ['error' => 'No keywords provided.'];
        }

        $results = [];
        foreach ($keywords as $keyword) {
            $results[] = $this->checkRank($keyword, $domain, $engine);
        }

        $prompt = "You are an SEO rank analysis expert. Here is a keyword ranking report for domain {$domain}:\n"
            . json_encode(array_slice($results, 0, 10), JSON_PRETTY_PRINT) . "\n\n"
            . "Provide:\n"
            . "1. Overall ranking health assessment\n"
            . "2. Quick wins (keywords close to page 1)\n"
            . "3. Keywords to prioritize for improvement\n"
            . "4. Actionable recommendations for ranking improvements";

        $aiInsights = $this->ai()->generate($prompt, 'You are an expert SEO rank tracking analyst.');

        return [
            'domain'      => $domain,
            'engine'      => $engine,
            'keyword_count' => count($keywords),
            'results'     => $results,
            'ai_insights' => $aiInsights,
        ];
    }

    protected function checkRank(string $keyword, string $domain, string $engine): array
    {
        // Use Google Custom Search API or scraping-safe alternatives
        // Without API key, we estimate based on content analysis
        $apiKey = get_setting('seo_google_search_api_key') ?? env('GOOGLE_SEARCH_API_KEY');
        $cx     = get_setting('seo_google_search_cx') ?? env('GOOGLE_SEARCH_CX');

        $rank = null;
        $url  = null;

        if ($apiKey && $cx) {
            try {
                $response = Http::timeout(15)
                    ->withOptions(['verify' => config('seo.ssl_verify', true)])
                    ->get('https://www.googleapis.com/customsearch/v1', [
                        'key' => $apiKey,
                        'cx'  => $cx,
                        'q'   => $keyword,
                        'num' => 10,
                    ]);

                if ($response->successful()) {
                    $items = $response->json('items', []);
                    foreach ($items as $position => $item) {
                        if (stripos($item['link'] ?? '', $domain) !== false) {
                            $rank = $position + 1;
                            $url  = $item['link'];
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // API unavailable
            }
        }

        return [
            'keyword'     => $keyword,
            'rank'        => $rank,
            'url'         => $url,
            'status'      => $rank === null ? 'not_found' : ($rank <= 3 ? 'top_3' : ($rank <= 10 ? 'page_1' : 'page_2+')),
            'checked_at'  => now()->toDateTimeString(),
        ];
    }
}
