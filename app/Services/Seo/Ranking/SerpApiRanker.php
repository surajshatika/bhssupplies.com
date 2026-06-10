<?php

namespace App\Services\Seo\Ranking;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * SerpAPI implementation (https://serpapi.com).
 *
 * Reads the API key from `seo_serpapi_key` business setting or
 * SERPAPI_KEY env var. Returns the first organic position whose URL matches
 * the requested target domain.
 */
class SerpApiRanker implements SerpRankerInterface
{
    public function name(): string
    {
        return 'serpapi';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey());
    }

    public function rank(string $keyword, string $targetDomainOrUrl, string $country = 'us', string $device = 'desktop'): array
    {
        if (!$this->isConfigured()) {
            return ['rank' => null, 'found_url' => null, 'raw' => null, 'error' => 'SerpAPI key not configured'];
        }

        $target = $this->extractDomain($targetDomainOrUrl);
        if (!$target) {
            return ['rank' => null, 'found_url' => null, 'raw' => null, 'error' => 'Could not extract domain from target.'];
        }

        try {
            // SerpAPI is rate-limited; a burst of keyword checks often trips HTTP 429.
            // Retry a couple of times with backoff so a transient limit doesn't get
            // mis-recorded as "not found".
            $resp = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $resp = Http::timeout(30)
                    ->withOptions(['verify' => config('seo.ssl_verify', true)])
                    ->get('https://serpapi.com/search.json', [
                        'engine'  => 'google',
                        'q'       => $keyword,
                        'gl'      => $country,
                        'hl'      => 'en',
                        'device'  => $device,
                        'num'     => 100,
                        'api_key' => $this->apiKey(),
                    ]);

                if ($resp->status() !== 429 || $attempt === 3) {
                    break;
                }
                usleep(1_200_000 * $attempt); // 1.2s, 2.4s backoff
            }

            if (!$resp->successful()) {
                return ['rank' => null, 'found_url' => null, 'raw' => null, 'error' => 'HTTP ' . $resp->status()];
            }

            $results = (array) ($resp->json('organic_results') ?? []);
            foreach ($results as $row) {
                $link = (string) ($row['link'] ?? '');
                $position = (int) ($row['position'] ?? 0);
                if (!$link || !$position) {
                    continue;
                }
                if (stripos($link, $target) !== false) {
                    return ['rank' => $position, 'found_url' => $link, 'raw' => null, 'error' => null];
                }
            }

            return ['rank' => 0, 'found_url' => null, 'raw' => null, 'error' => null];
        } catch (Throwable $e) {
            return ['rank' => null, 'found_url' => null, 'raw' => null, 'error' => $e->getMessage()];
        }
    }

    protected function apiKey(): ?string
    {
        return get_setting('seo_serpapi_key') ?: env('SERPAPI_KEY');
    }

    protected function extractDomain(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $host = parse_url($value, PHP_URL_HOST) ?: $value;
        return preg_replace('/^www\./i', '', $host);
    }
}
