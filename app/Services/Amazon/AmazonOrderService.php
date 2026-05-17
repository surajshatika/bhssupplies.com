<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use App\Models\AmazonOrder;
use App\Models\AmazonSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AmazonOrderService
{
    private const SP_API_BASE = 'https://sellingpartnerapi-na.amazon.com';
    private const SANDBOX_BASE = 'https://sandbox.sellingpartnerapi-na.amazon.com';

    public function __construct(private AmazonAuthService $authService) {}

    public function fetchNewOrders(AmazonAccount $account, Carbon $since): array
    {
        $url     = $this->baseUrl() . '/orders/v0/orders';
        $headers = $this->buildHeaders($account);

        $response = Http::withHeaders($headers)->get($url, [
            'MarketplaceIds'    => $account->marketplace_id,
            'CreatedAfter'      => $since->toIso8601String(),
            'OrderStatuses'     => 'Unshipped,PartiallyShipped,Shipped,Canceled',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Amazon GetOrders failed: ' . $response->body());
        }

        return $response->json('payload.Orders', []);
    }

    public function importOrder(array $rawOrder, AmazonAccount $account): AmazonOrder
    {
        $log = AmazonSyncLog::create([
            'action'          => 'order_import',
            'status'          => 'pending',
            'request_payload' => ['amazon_order_id' => $rawOrder['AmazonOrderId']],
        ]);

        try {
            $order = AmazonOrder::updateOrCreate(
                ['amazon_order_id' => $rawOrder['AmazonOrderId']],
                [
                    'account_id'   => $account->id,
                    'status'       => $rawOrder['OrderStatus'] ?? 'Pending',
                    'buyer_email'  => $rawOrder['BuyerInfo']['BuyerEmail'] ?? null,
                    'buyer_name'   => $rawOrder['BuyerInfo']['BuyerName'] ?? null,
                    'total_amount' => $rawOrder['OrderTotal']['Amount'] ?? 0,
                    'currency'     => $rawOrder['OrderTotal']['CurrencyCode'] ?? 'CAD',
                    'order_items'  => $rawOrder['OrderItems'] ?? [],
                    'raw_data'     => $rawOrder,
                ]
            );

            $log->update(['status' => 'success', 'response_payload' => ['id' => $order->id]]);

            return $order;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function importAllNew(AmazonAccount $account): int
    {
        $since  = now()->subHours(24);
        $orders = $this->fetchNewOrders($account, $since);
        $count  = 0;

        foreach ($orders as $rawOrder) {
            $this->importOrder($rawOrder, $account);
            $count++;
        }

        return $count;
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
