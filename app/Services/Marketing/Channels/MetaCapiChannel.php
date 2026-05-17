<?php

namespace App\Services\Marketing\Channels;

use App\Services\FacebookConversionService;

class MetaCapiChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'meta'; }
    public function name(): string { return 'Meta (Facebook + Instagram) CAPI'; }

    public function isEnabled(): bool
    {
        return (int) get_setting('facebook_pixel_capi') === 1
            && !empty(get_setting('FACEBOOK_PIXEL_ID') ?: env('FACEBOOK_PIXEL_ID'))
            && !empty(get_setting('FACEBOOK_PIXEL_API') ?: env('FACEBOOK_PIXEL_API'));
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $svc = app(FacebookConversionService::class);

        // Map standard events to FacebookConversionService methods.
        switch ($eventName) {
            case 'Purchase':              return isset($payload['order']) ? $svc->sendPurchase($payload['order']) : false;
            case 'AddToCart':             return isset($payload['product']) ? $svc->sendAddToCart($payload['product'], $payload['value'] ?? 0, $eventId) : false;
            case 'AddToWishlist':         return isset($payload['product_id']) ? $svc->sendAddToWishlist($payload['product_id'], $eventId) : false;
            case 'ViewContent':           return isset($payload['product']) ? $svc->sendViewContent($payload['product'], $eventId) : false;
            case 'InitiateCheckout':      return isset($payload['cart']) ? $svc->sendInitiateCheckout($payload['cart'], $payload['value'] ?? null, $payload['num_items'] ?? null) : false;
            case 'Search':                return isset($payload['query']) ? $svc->sendSearch($payload['query']) : false;
            case 'Lead':                  return $svc->sendLead($payload['content_name'] ?? null);
            case 'CompleteRegistration':  return isset($payload['user']) ? $svc->sendCompleteRegistration($payload['user']) : false;
            case 'Subscribe':             return $svc->sendSubscribe($payload['email'] ?? null);
            default:                      return false;
        }
    }
}
