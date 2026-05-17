<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use App\Models\AmazonProduct;
use App\Models\AmazonSyncLog;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class AmazonListingService
{
    private const SP_API_BASE = 'https://sellingpartnerapi-na.amazon.com';
    private const SANDBOX_BASE = 'https://sandbox.sellingpartnerapi-na.amazon.com';

    public function __construct(
        private AmazonAuthService $authService,
        private AmazonProductMapper $mapper
    ) {}

    public function uploadProduct(Product $product, AmazonAccount $account): AmazonProduct
    {
        $sku     = $this->mapper->buildSku($product);
        $payload = $this->mapper->toListingPayload($product);

        $log = AmazonSyncLog::create([
            'product_id'      => $product->id,
            'action'          => 'upload',
            'status'          => 'pending',
            'request_payload' => $payload,
        ]);

        try {
            $response = $this->putListing($account, $sku, $payload);

            $amazonProduct = AmazonProduct::updateOrCreate(
                ['product_id' => $product->id, 'account_id' => $account->id],
                [
                    'amazon_sku'     => $sku,
                    'status'         => 'pending',
                    'last_synced_at' => now(),
                    'error_message'  => null,
                ]
            );

            $log->update([
                'amazon_product_id' => $amazonProduct->id,
                'status'            => 'success',
                'response_payload'  => $response,
            ]);

            return $amazonProduct;
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateListing(AmazonProduct $amazonProduct, array $patchPayload): bool
    {
        $log = AmazonSyncLog::create([
            'product_id'        => $amazonProduct->product_id,
            'amazon_product_id' => $amazonProduct->id,
            'action'            => 'upload',
            'status'            => 'pending',
            'request_payload'   => $patchPayload,
        ]);

        try {
            $response = $this->patchListing(
                $amazonProduct->account,
                $amazonProduct->amazon_sku,
                $patchPayload
            );

            $amazonProduct->update(['last_synced_at' => now(), 'error_message' => null]);

            $log->update(['status' => 'success', 'response_payload' => $response]);
            return true;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deactivateListing(AmazonProduct $amazonProduct): bool
    {
        $log = AmazonSyncLog::create([
            'product_id'        => $amazonProduct->product_id,
            'amazon_product_id' => $amazonProduct->id,
            'action'            => 'deactivate',
            'status'            => 'pending',
        ]);

        try {
            $this->deleteListing($amazonProduct->account, $amazonProduct->amazon_sku);

            $amazonProduct->update(['status' => 'inactive', 'last_synced_at' => now()]);

            $log->update(['status' => 'success']);
            return true;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function putListing(AmazonAccount $account, string $sku, array $payload): array
    {
        $url     = $this->baseUrl() . "/listings/2021-08-01/items/{$account->seller_id}/{$sku}";
        $headers = $this->buildHeaders($account);

        $response = Http::withHeaders($headers)
            ->put($url . '?marketplaceIds=' . $account->marketplace_id, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Amazon PutListingItem failed: ' . $response->body());
        }

        return $response->json();
    }

    private function patchListing(AmazonAccount $account, string $sku, array $payload): array
    {
        $url     = $this->baseUrl() . "/listings/2021-08-01/items/{$account->seller_id}/{$sku}";
        $headers = $this->buildHeaders($account);

        $response = Http::withHeaders($headers)
            ->patch($url . '?marketplaceIds=' . $account->marketplace_id, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Amazon PatchListingItem failed: ' . $response->body());
        }

        return $response->json();
    }

    private function deleteListing(AmazonAccount $account, string $sku): void
    {
        $url     = $this->baseUrl() . "/listings/2021-08-01/items/{$account->seller_id}/{$sku}";
        $headers = $this->buildHeaders($account);

        $response = Http::withHeaders($headers)
            ->delete($url . '?marketplaceIds=' . $account->marketplace_id);

        if ($response->failed()) {
            throw new \RuntimeException('Amazon DeleteListingItem failed: ' . $response->body());
        }
    }

    private function buildHeaders(AmazonAccount $account): array
    {
        return [
            'x-amz-access-token' => $this->authService->getAccessToken($account),
            'Content-Type'       => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return config('amazon.sandbox') ? self::SANDBOX_BASE : self::SP_API_BASE;
    }
}
