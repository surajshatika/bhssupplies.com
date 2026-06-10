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
        $serpExhausted = false;

        foreach ($keywords as $keyword) {
            $result = $this->checkRank($keyword, $domain, $engine, $country, $device, $ranker, $serpExhausted);

            // Circuit breaker: the ranker already retried with backoff inside rank().
            // Once it still comes back 429, the quota is gone for now — checking the
            // remaining keywords against it would just serialize ~30s timeouts and
            // block this (synchronous) request until the worker is killed.
            if (!$serpExhausted && stripos((string) ($result['error'] ?? ''), '429') !== false) {
                $serpExhausted = true;
            }
            $stored = $this->persistKeyword($keyword, $country, $device, $engine, $result);
            $rank = $stored ? (int) ($stored->rank_current ?? 0) : $result['rank'];
            $previous = $stored ? (int) ($stored->rank_previous ?? 0) : null;

            $results[] = [
                'keyword' => $keyword,
                'rank' => $rank,
                'previous_rank' => $previous ?: null,
                'movement' => $rank > 0 && $previous > 0 ? $previous - $rank : null,
                'google_page' => $rank > 0 ? (int) ceil($rank / 10) : null,
                'google_page_label' => $this->pageLabel($rank, $result['error'] ?? null, (string) ($result['source'] ?? '')),
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
        SerpRankerInterface $ranker,
        bool $skipPrimary = false
    ): array {
        if ($ranker->isConfigured() && $skipPrimary) {
            // Rate-limit circuit breaker tripped earlier in this run. Try the free
            // CSE top-10 check; otherwise report the skip honestly (treated like a
            // 429 so the stored rank is preserved and the label says "rate limited").
            $fallback = $this->googleCseRank($keyword, $domain, $engine);
            if ($fallback !== null && empty($fallback['error']) && (int) ($fallback['rank'] ?? 0) > 0) {
                return $fallback;
            }

            return [
                'rank' => null,
                'found_url' => null,
                'error' => 'HTTP 429 (skipped — provider rate limited earlier in this run)',
                'source' => $ranker->name(),
            ];
        }

        if ($ranker->isConfigured()) {
            $result = array_merge(
                $ranker->rank($keyword, $domain, $country, $device),
                ['source' => $ranker->name()]
            );

            // Primary ranker failed/rate-limited (e.g. SerpAPI 429) — fall back to
            // Google Custom Search (top 10) so we still get real data instead of
            // recording the keyword as "not found". Only accept a POSITIVE rank:
            // CSE sees just 10 results, so its rank=0 is inconclusive (the keyword
            // may rank 11-100) and must not clobber a previously known rank.
            if (!empty($result['error'])) {
                $fallback = $this->googleCseRank($keyword, $domain, $engine);
                if ($fallback !== null && empty($fallback['error']) && (int) ($fallback['rank'] ?? 0) > 0) {
                    return $fallback;
                }
            }

            return $result;
        }

        $fallback = $this->googleCseRank($keyword, $domain, $engine);

        return $fallback ?? [
            'rank' => null,
            'found_url' => null,
            'error' => 'Configure a SerpAPI key in SEO settings for accurate Google top-100 rankings.',
            'source' => 'not_configured',
        ];
    }

    /**
     * Google Programmable Search (CSE) lookup of the top 10 results. Returns null
     * when CSE is not configured (so the caller can keep the primary error).
     */
    protected function googleCseRank(string $keyword, string $domain, string $engine): ?array
    {
        $apiKey = get_setting('seo_google_search_api_key') ?? env('GOOGLE_SEARCH_API_KEY');
        $cx = get_setting('seo_google_search_cx') ?? env('GOOGLE_SEARCH_CX');

        if ($engine !== 'google' || !$apiKey || !$cx) {
            return null;
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

        // A CSE top-10 check that didn't find the site is inconclusive about
        // positions 11-100 — never let it zero out a previously known rank.
        $cseInconclusive = (int) ($result['rank'] ?? -1) === 0
            && ($result['source'] ?? '') === 'google_cse_top_10'
            && (int) ($stored->rank_current ?? 0) > 0;

        if (empty($result['error']) && $result['rank'] !== null && !$cseInconclusive) {
            $stored->recordRank((int) $result['rank']);
            if (Schema::hasColumn('seo_keywords', 'last_status')) {
                $stored->last_status = 'ok';
                $stored->save();
            }
        } elseif ($cseInconclusive) {
            $stored->last_checked_at = now();
            if (Schema::hasColumn('seo_keywords', 'last_status')) {
                $stored->last_status = 'inconclusive:cse_top10';
            }
            $stored->save();
        } else {
            // Check failed (e.g. SerpAPI HTTP 429). Do NOT overwrite a known rank —
            // just record that this check could not be completed so the UI can show
            // an honest "rate limited / not checked" instead of "not found".
            $stored->last_checked_at = now();
            if (Schema::hasColumn('seo_keywords', 'last_status')) {
                $stored->last_status = 'error:' . ($result['error'] ?? 'unknown');
            }
            $stored->save();
        }

        return $stored->fresh();
    }

    /** Honest page label that never reports "not found" when the check actually errored. */
    protected function pageLabel(int $rank, ?string $error, string $source = ''): string
    {
        if ($rank > 0) {
            return 'Google Page ' . (int) ceil($rank / 10);
        }
        if (!empty($error)) {
            return stripos($error, '429') !== false
                ? 'Rate limited — not checked'
                : 'Check failed — not recorded';
        }
        // A CSE check only inspects 10 results — claiming "not in top 100" from it would lie.
        if ($source === 'google_cse_top_10') {
            return 'Not in top 10 (top-100 needs SerpAPI)';
        }

        return 'Not found in top 100';
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
