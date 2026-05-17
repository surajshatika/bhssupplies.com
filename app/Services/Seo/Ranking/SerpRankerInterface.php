<?php

namespace App\Services\Seo\Ranking;

/**
 * Returns the SERP position of a target_url for a given keyword.
 * Implementations: SerpApiRanker, DataForSeoRanker, ScrapingBeeRanker, …
 *
 * Position semantics:
 *   - 1..100 means "found at this rank in top 100 results"
 *   - 0 means "not found in the inspected results"
 *   - null on a hard failure (network, quota exceeded, mis-configured key)
 */
interface SerpRankerInterface
{
    public function isConfigured(): bool;

    public function name(): string;

    /**
     * @return array{rank: ?int, found_url: ?string, raw: mixed, error: ?string}
     */
    public function rank(string $keyword, string $targetDomainOrUrl, string $country = 'us', string $device = 'desktop'): array;
}
