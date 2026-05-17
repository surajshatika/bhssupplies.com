<?php

namespace App\Services\Seo\Ranking;

/**
 * Picks the configured SERP ranker. Reads `seo_rank_provider` business
 * setting, falls back to the first ranker that has a valid API key.
 */
class RankerManager
{
    public static function make(?string $provider = null): SerpRankerInterface
    {
        $provider = strtolower($provider ?: (string) get_setting('seo_rank_provider', 'serpapi'));

        $candidates = match ($provider) {
            'serpapi'      => [SerpApiRanker::class],
            default        => [SerpApiRanker::class],
        };

        foreach ($candidates as $class) {
            $r = new $class();
            if ($r->isConfigured()) {
                return $r;
            }
        }

        // Always return something callable — caller checks isConfigured()/error.
        return new SerpApiRanker();
    }
}
