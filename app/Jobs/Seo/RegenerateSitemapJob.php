<?php

namespace App\Jobs\Seo;

use App\Services\Seo\Optimization\Features\SmartSitemapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Regenerates the on-disk XML sitemaps (index + per-type split files, now with
 * <image:image> entries and SEO-score-driven priorities).
 *
 * Dispatched two ways:
 *   - debounced from {@see \App\Observers\SeoEntitySlugObserver} when a published
 *     entity is saved (opt-in real-time updates), and
 *   - interval-gated from the master {@see \App\Console\Commands\Seo\AutomationRunCommand}.
 *
 * {@see ShouldBeUnique} collapses a burst of saves into a single regeneration.
 */
class RegenerateSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    /** Hold the unique lock for 5 minutes so rapid saves coalesce. */
    public function uniqueFor(): int
    {
        return 300;
    }

    public function uniqueId(): string
    {
        return 'seo-regenerate-sitemap';
    }

    public function handle(SmartSitemapService $sitemap): void
    {
        try {
            $result = $sitemap->handle([
                'persist'  => true,
                'split'    => true,
                'base_url' => url('/'),
            ]);

            logger()->info('SEO sitemap regenerated', [
                'urls'   => $result['url_count'] ?? null,
                'groups' => $result['groups'] ?? null,
            ]);
        } catch (Throwable $e) {
            logger()->warning('RegenerateSitemapJob failed', ['error' => $e->getMessage()]);
        } finally {
            Cache::forget('seo:sitemap:regen:debounce');
        }
    }
}
