<?php

namespace App\Jobs\Seo;

use App\Services\Seo\Optimization\Features\IndexNowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Submits one or more URLs to Bing IndexNow. Wraps the existing service so
 * observers can fire-and-forget on save without coupling model code to HTTP
 * calls or external API latency.
 */
class PingIndexNowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string[] */
    public array $urls;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(array $urls)
    {
        $this->urls = array_values(array_filter(array_unique($urls)));
        $this->onQueue(config('seo.queue.optimization', 'default'));
    }

    public function handle(IndexNowService $service): void
    {
        if (empty($this->urls)) {
            return;
        }

        try {
            $result = $service->handle(['urls' => $this->urls]);
            if (!empty($result['error'])) {
                logger()->info('IndexNow ping skipped', ['reason' => $result['error']]);
            }
        } catch (Throwable $e) {
            logger()->warning('IndexNow ping failed', [
                'urls'  => $this->urls,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
