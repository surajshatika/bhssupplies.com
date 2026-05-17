<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookConversionService
{
    protected string $apiVersion = 'v19.0';
    protected int $timeout = 10;
    protected int $retries = 2;

    /**
     * Public: build a deterministic event_id used for Pixel<->CAPI deduplication.
     * Frontend Pixel must use the SAME id (same event name + same primary key).
     */
    public static function eventId(string $name, $key): string
    {
        return strtolower($name) . '_' . $key;
    }

    protected function pixelId(): ?string
    {
        // Prefer DB-backed setting (config:cache-safe); fall back to env() for legacy installs.
        return get_setting('FACEBOOK_PIXEL_ID') ?: (env('FACEBOOK_PIXEL_ID') ?: null);
    }

    protected function accessToken(): ?string
    {
        return get_setting('FACEBOOK_PIXEL_API') ?: (env('FACEBOOK_PIXEL_API') ?: null);
    }

    protected function isCapiEnabled(): bool
    {
        return (int) get_setting('facebook_pixel_capi') === 1
            && !empty($this->pixelId())
            && !empty($this->accessToken());
    }

    protected function currencyCode(): string
    {
        try {
            return Currency::findOrFail(get_setting('system_default_currency'))->code;
        } catch (\Throwable $e) {
            return 'USD';
        }
    }

    protected function buildUserData(): array
    {
        $user = auth()->check() ? auth()->user() : null;

        $data = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
            'fbp'               => request()->cookie('_fbp'),
            'fbc'               => request()->cookie('_fbc'),
        ];

        if ($user) {
            if (!empty($user->email)) $data['em'] = hash('sha256', strtolower(trim($user->email)));
            if (!empty($user->phone)) $data['ph'] = hash('sha256', preg_replace('/\D/', '', $user->phone));
            if (!empty($user->name)) {
                $parts = explode(' ', trim($user->name), 2);
                $data['fn'] = hash('sha256', strtolower($parts[0] ?? ''));
                if (!empty($parts[1])) $data['ln'] = hash('sha256', strtolower($parts[1]));
            }
            $data['external_id'] = hash('sha256', (string) $user->id);
        }

        return array_filter($data, fn($v) => !is_null($v) && $v !== '');
    }

    /**
     * Core dispatcher — retries, logs, never throws to caller.
     */
    protected function sendToFacebook(string $eventName, array $customData = [], ?string $eventId = null, ?string $eventSourceUrl = null): bool
    {
        if (!$this->isCapiEnabled()) {
            return false;
        }

        $pixelId     = $this->pixelId();
        $accessToken = $this->accessToken();

        $eventId        = $eventId ?: ($eventName . '_' . uniqid('', true));
        $eventSourceUrl = $eventSourceUrl ?: (request()->fullUrl() ?: url('/'));

        $payload = [
            'data' => [[
                'event_name'       => $eventName,
                'event_time'       => time(),
                'event_id'         => $eventId,
                'event_source_url' => $eventSourceUrl,
                'action_source'    => 'website',
                'user_data'        => $this->buildUserData(),
                'custom_data'      => array_filter($customData, fn($v) => !is_null($v) && $v !== ''),
            ]],
        ];

        $url = sprintf('https://graph.facebook.com/%s/%s/events', $this->apiVersion, $pixelId);

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 400, throw: false)
                ->post($url . '?access_token=' . urlencode($accessToken), $payload);

            if (!$response->successful()) {
                Log::warning('[FacebookConversionService] Non-2xx from Meta CAPI', [
                    'event'  => $eventName,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('[FacebookConversionService] CAPI request failed', [
                'event'   => $eventName,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /* =========================================================
     * Public event helpers (existing names retained for back-compat)
     * ========================================================= */

    public function sendPurchase($combinedOrder): bool
    {
        $contentIds = [];
        $count = 0;
        foreach ($combinedOrder->orders as $order) {
            foreach ($order->orderDetails as $detail) {
                $contentIds[] = (string) $detail->product_id;
                $count++;
            }
        }

        return $this->sendToFacebook('Purchase', [
            'currency'     => $this->currencyCode(),
            'value'        => (float) $combinedOrder->grand_total,
            'content_ids'  => $contentIds,
            'content_type' => 'product',
            'num_items'    => $count,
            'order_id'     => (string) $combinedOrder->id,
        ], self::eventId('Purchase', $combinedOrder->id));
    }

    public function sendAddToCart($product, $price, $eventId = null): bool
    {
        return $this->sendToFacebook('AddToCart', [
            'currency'     => $this->currencyCode(),
            'value'        => (float) $price,
            'content_ids'  => [(string) $product->id],
            'content_name' => $product->getTranslation('name'),
            'content_type' => 'product',
        ], $eventId ?: self::eventId('AddToCart', $product->id . '_' . request()->session()->getId()));
    }

    public function sendAddToWishlist($productId, $eventId = null): bool
    {
        return $this->sendToFacebook('AddToWishlist', [
            'content_ids'  => [(string) $productId],
            'content_type' => 'product',
        ], $eventId ?: self::eventId('AddToWishlist', $productId . '_' . (auth()->id() ?? request()->session()->getId())));
    }

    public function sendViewContent($product, $eventId = null): bool
    {
        return $this->sendToFacebook('ViewContent', [
            'content_ids'  => [(string) $product->id],
            'content_name' => $product->getTranslation('name'),
            'content_type' => 'product',
            'value'        => (float) home_discounted_price($product),
            'currency'     => $this->currencyCode(),
        ], $eventId ?: self::eventId('ViewContent', $product->id . '_' . request()->session()->getId()));
    }

    /* ---------- New events (advanced coverage) ---------- */

    public function sendInitiateCheckout($cart, ?float $value = null, ?int $numItems = null): bool
    {
        $contentIds = [];
        $sum        = 0.0;
        $count      = 0;
        foreach ($cart as $row) {
            if (isset($row->product_id)) {
                $contentIds[] = (string) $row->product_id;
                $sum         += (float) ($row->price ?? 0) * (int) ($row->quantity ?? 1);
                $count       += (int) ($row->quantity ?? 1);
            }
        }
        return $this->sendToFacebook('InitiateCheckout', [
            'currency'     => $this->currencyCode(),
            'value'        => $value ?? $sum,
            'content_ids'  => $contentIds,
            'content_type' => 'product',
            'num_items'    => $numItems ?? $count,
        ], self::eventId('InitiateCheckout', (auth()->id() ?? request()->session()->getId())));
    }

    public function sendSearch(string $query): bool
    {
        return $this->sendToFacebook('Search', [
            'search_string' => $query,
        ], self::eventId('Search', md5($query)));
    }

    public function sendLead(?string $contentName = null): bool
    {
        return $this->sendToFacebook('Lead', [
            'content_name' => $contentName,
        ], self::eventId('Lead', auth()->id() ?? request()->session()->getId()));
    }

    public function sendCompleteRegistration($user): bool
    {
        return $this->sendToFacebook('CompleteRegistration', [
            'content_name' => 'User Registration',
            'status'       => 'completed',
        ], self::eventId('CompleteRegistration', $user->id ?? request()->session()->getId()));
    }

    public function sendSubscribe(?string $email = null): bool
    {
        return $this->sendToFacebook('Subscribe', [
            'content_name' => 'Newsletter Subscription',
        ], self::eventId('Subscribe', md5($email ?? request()->ip())));
    }
}
