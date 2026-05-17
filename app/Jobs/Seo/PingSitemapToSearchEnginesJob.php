<?php

namespace App\Jobs\Seo;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pings Google + Bing with the canonical sitemap URL so they re-crawl it.
 * Free, no auth required. Google deprecated the /ping endpoint in mid-2023,
 * but Bing still accepts it; we keep both to maximise coverage.
 *
 * Dispatched automatically by the daily sitemap-regen scheduler task.
 */
class PingSitemapToSearchEnginesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(public ?string $sitemapUrl = null)
    {
        $this->sitemapUrl = $sitemapUrl ?: rtrim(url('/'), '/') . '/sitemap.xml';
        $this->onQueue(config('seo.queue.optimization', 'default'));
    }

    public function handle(): void
    {
        $url = urlencode($this->sitemapUrl);

        // Bing — still supported.
        $this->silentPing("https://www.bing.com/ping?sitemap={$url}");

        // Google — endpoint deprecated June 2023 but kept here for legacy.
        // (Google now recommends registering the sitemap in Search Console.)
        $this->silentPing("https://www.google.com/ping?sitemap={$url}");
    }

    protected function silentPing(string $endpoint): void
    {
        try {
            Http::timeout(15)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->withHeaders(['User-Agent' => 'BHS-SeoBot/1.0 (+sitemap-ping)'])
                ->get($endpoint);
        } catch (Throwable $e) {
            logger()->info('sitemap-ping skipped', ['endpoint' => $endpoint, 'err' => $e->getMessage()]);
        }
    }
}
