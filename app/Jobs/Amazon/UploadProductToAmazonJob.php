<?php

namespace App\Jobs\Amazon;

use App\Models\AmazonAccount;
use App\Models\Product;
use App\Services\Amazon\AmazonListingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadProductToAmazonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [60, 120, 300];

    public function __construct(
        public Product $product,
        public AmazonAccount $account
    ) {}

    public function handle(AmazonListingService $service): void
    {
        $service->uploadProduct($this->product, $this->account);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Amazon upload job failed', [
            'product_id' => $this->product->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
