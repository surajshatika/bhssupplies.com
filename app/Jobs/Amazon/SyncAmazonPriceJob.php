<?php

namespace App\Jobs\Amazon;

use App\Models\AmazonProduct;
use App\Services\Amazon\AmazonPriceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAmazonPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public array $backoff = [60, 120, 300];

    public function __construct(public AmazonProduct $amazonProduct) {}

    public function handle(AmazonPriceService $service): void
    {
        $service->syncPrice($this->amazonProduct);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Amazon price sync job failed', [
            'amazon_product_id' => $this->amazonProduct->id,
            'error'             => $exception->getMessage(),
        ]);
    }
}
