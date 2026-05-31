<?php

namespace App\Services\Seo\Optimization\Features;

use App\Models\SeoKeyword;
use App\Models\SeoProject;
use App\Services\Seo\Ranking\RankerManager;
use App\Services\Seo\Ranking\SerpRankerInterface;
use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class KeywordRankTrackerService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $keywords = $this->normalizeKeywords($payload['keywords'] ?? []);
        $domain = $payload['domain'] ?? parse_url(url('/'), PHP_URL_HOST);
        $engine = $payload['engine'] ?? 'google';
        $country = strtolower($payload['country'] ?? 'ca');
        $device = strtolower($payload['device'] ?? 'desktop');

        if (empty($keywords)) {
            return ['error' => 'No keywords provided.'];
        }

        $ranker = RankerManager::make($payload['provider'] ?? null);
        $results = [];

        foreach ($keywords as $keyword) {
            $result = $this->checkRank($keyword, $domain, $engine, $country, $device, $ranker);
            $stored = $this->persistKeyword($keyword, $country, $device, $engine, $result);
            $rank = $stored ? (int) ($stored->rank_current ?? 0) : $result['rank'];
            $previous = $stored ? (int) ($stored->rank_previous ?? 0) : null;

            $results[] = [
                'keyword' => $keyword,
                'rank' => $rank,
                'previous_rank' => $previous ?: null,
                'movement' => $rank > 0 && $previous > 0 ? $previous - $rank : null,
                'google_page' => $rank > 0 ? (int) ceil($rank / 10) : null,
                'google_page_label' => $rank > 0 ? 'Google Page ' . (int) ceil($rank / 10) : 'Not found in top 100',
                'url' => $result['found_url'] ?? ($stored->target_url ?? null),
                'status' => $this->rankStatus($rank, $result['error'] ?? null),
                'source' => $result['source'],
                'error' => $result['error'] ?? null,
                'checked_at' => now()->toDateTimeString(),
            ];
        }

        $prompt = "You are an SEO rank analysis expert. Here is a Canadian Google keyword ranking report for domain {$domain}:\n"
            . json_encode(array_slice($results, 0, 20), JSON_PRETTY_PRINT) . "\n\n"
            . "Provide:\n"
            . "1. Overall ranking health assessment\n"
            . "2. Quick wins close to Google page 1\n"
            . "3. Keywords and landing pages to prioritize\n"
            . "4. Actionable Canada/GTA ranking recommendations";

        return [
            'domain' => $domain,
            'engine' => $engine,
            'provider' => $ranker->name(),
            'provider_configured' => $ranker->isConfigured(),
            'keyword_count' => count($keywords),
            'results' => $results,
            'ai_insights' => $this->ai()->generate($prompt, 'You are an expert Canadian SEO rank tracking analyst.'),
        ];
    }

    protected function normalizeKeywords(array|string $keywords): array
    {
        if (is_string($keywords)) {
            $keywords = preg_split('/[\r\n,]+/', $keywords);
        }

        return collect($keywords)
            ->map(fn($keyword) => trim((string) $keyword))
            ->filter()
            ->unique(fn($keyword) => mb_strtolower($keyword))
            ->take(100)
            ->values()
            ->all();
    }

    protected function checkRank(
        string $keyword,
        string $domain,
        string $engine,
        string $country,
        string $device,
        SerpRankerInterface $ranker
    ): array {
        if ($ranker->isConfigured()) {
            return array_merge(
                $ranker->rank($keyword, $domain, $country, $device),
                ['source' => $ranker->name()]
            );
        }

        $apiKey = get_setting('seo_google_search_api_key') ?? env('GOOGLE_SEARCH_API_KEY');
        $cx = get_setting('seo_google_search_cx') ?? env('GOOGLE_SEARCH_CX');

        if ($engine !== 'google' || !$apiKey || !$cx) {
            return [
                'rank' => null,
                'found_url' => null,
                'error' => 'Configure a SerpAPI key in SEO settings for accurate Google top-100 rankings.',
                'source' => 'not_configured',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->get('https://www.googleapis.com/customsearch/v1', [
                    'key' => $apiKey,
                    'cx' => $cx,
                    'q' => $keyword,
                    'num' => 10,
                ]);

            if (!$response->successful()) {
                return [
                    'rank' => null,
                    'found_url' => null,
                    'error' => 'Google Custom Search returned HTTP ' . $response->status(),
                    'source' => 'google_cse',
                ];
            }

            foreach ($response->json('items', []) as $position => $item) {
                $url = (string) ($item['link'] ?? '');
                if ($url && stripos($url, $domain) !== false) {
                    return [
                        'rank' => $position + 1,
                        'found_url' => $url,
                        'error' => null,
                        'source' => 'google_cse_top_10',
                    ];
                }
            }

            return ['rank' => 0, 'found_url' => null, 'error' => null, 'source' => 'google_cse_top_10'];
        } catch (Throwable $e) {
            return ['rank' => null, 'found_url' => null, 'error' => $e->getMessage(), 'source' => 'google_cse'];
        }
    }

    protected function persistKeyword(string $keyword, string $country, string $device, string $engine, array $result): ?SeoKeyword
    {
        if (!Schema::hasTable('seo_keywords')) {
            return null;
        }

        $projectId = Schema::hasTable('seo_projects')
            ? optional(SeoProject::query()->where('slug', 'default-seo-suite')->first() ?: SeoProject::query()->first())->id
            : null;

        $stored = SeoKeyword::query()->firstOrNew([
            'project_id' => $projectId,
            'keyword' => $keyword,
            'country' => $country,
            'device' => $device,
        ]);

        $stored->engine = $engine;
        $stored->is_active = true;
        if (!empty($result['found_url'])) {
            $stored->target_url = $result['found_url'];
        }
        $stored->save();

        if (empty($result['error']) && $result['rank'] !== null) {
            $stored->recordRank((int) $result['rank']);
        } else {
            $stored->last_checked_at = now();
            $stored->save();
        }

        return $stored->fresh();
    }

    protected function rankStatus(?int $rank, ?string $error): string
    {
        if ($error) return 'error';
        if (!$rank) return 'not_found';
        if ($rank <= 3) return 'top_3';
        if ($rank <= 10) return 'page_1';
        if ($rank <= 20) return 'page_2';

        return 'page_3_10';
    }
}
