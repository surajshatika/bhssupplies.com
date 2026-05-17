<?php

namespace App\Jobs\Seo;

use App\Services\Seo\Speed\CloudflareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PurgeCloudflareUrlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 45;
    public int $tries   = 2;

    public function __construct(public array $urls)
    {
        $this->urls = array_values(array_filter($this->urls));
        $this->onQueue(config('seo.queue.optimization', 'default'));
    }

    public function handle(CloudflareService $cf): void
    {
        if (empty($this->urls) || !$cf->isConfigured()) {
            return;
        }

        try {
            $res = $cf->purgeUrls($this->urls);
            if (!$res['success']) {
                logger()->info('Cloudflare purge incomplete', ['error' => $res['error'] ?? null]);
            }
        } catch (Throwable $e) {
            logger()->warning('Cloudflare purge job failed', ['error' => $e->getMessage()]);
        }
    }
}
