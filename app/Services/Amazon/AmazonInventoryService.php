<?php

namespace App\Services\Amazon;

use App\Models\AmazonProduct;
use App\Models\AmazonSyncLog;

class AmazonInventoryService
{
    public function __construct(
        private AmazonListingService $listingService,
        private AmazonProductMapper $mapper
    ) {}

    public function syncStock(AmazonProduct $amazonProduct): bool
    {
        $product = $amazonProduct->product;
        $payload = $this->mapper->toInventoryPayload($product);

        $log = AmazonSyncLog::create([
            'product_id'        => $product->id,
            'amazon_product_id' => $amazonProduct->id,
            'action'            => 'inventory_sync',
            'status'            => 'pending',
            'request_payload'   => $payload,
        ]);

        try {
            $this->listingService->updateListing($amazonProduct, $payload);

            $amazonProduct->update(['last_synced_at' => now()]);
            $log->update(['status' => 'success']);

            return true;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return false;
        }
    }

    public function syncAllActive(): void
    {
        AmazonProduct::where('status', 'active')
            ->with('product', 'account')
            ->chunk(50, function ($amazonProducts) {
                foreach ($amazonProducts as $amazonProduct) {
                    $this->syncStock($amazonProduct);
                }
            });
    }
}
