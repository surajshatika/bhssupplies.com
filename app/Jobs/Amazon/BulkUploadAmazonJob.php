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

class BulkUploadAmazonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 2;
    public array $backoff = [120, 300];
    public int $timeout  = 600;

    public function __construct(
        public array $productIds,
        public AmazonAccount $account
    ) {}

    public function handle(AmazonListingService $service): void
    {
        $results = ['success' => 0, 'failed' => 0];

        foreach ($this->productIds as $productId) {
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            try {
                $service->uploadProduct($product, $this->account);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                \Log::warning("Bulk Amazon upload failed for product {$productId}: " . $e->getMessage());
            }
        }

        \Log::info('Amazon bulk upload completed', $results);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Amazon bulk upload job failed', [
            'product_count' => count($this->productIds),
            'error'         => $exception->getMessage(),
        ]);
    }
}
