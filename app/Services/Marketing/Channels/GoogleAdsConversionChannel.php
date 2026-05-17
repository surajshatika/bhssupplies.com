<?php

namespace App\Services\Marketing\Channels;

/**
 * Google Ads Enhanced Conversions via the Google Ads API.
 *
 * Note: Google Ads API requires OAuth2 + a developer token + customer ID. For most
 * shop installations the simpler path is the gtag-based Enhanced Conversions
 * (frontend) plus a server-to-server upload via the Google Ads API for offline
 * conversions. This channel implements the offline-conversion upload via the
 * "Click Conversion" endpoint when both `gclid` cookie + conversion action ID
 * are present. Otherwise it returns false (handled by frontend pixel only).
 */
class GoogleAdsConversionChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'google_ads'; }
    public function name(): string { return 'Google Ads Enhanced Conversions'; }

    /** Map standard events → conversion-action env keys (one per event). */
    protected array $envKey = [
        'Purchase'             => 'GOOGLE_ADS_CONV_PURCHASE',
        'AddToCart'            => 'GOOGLE_ADS_CONV_ADD_TO_CART',
        'InitiateCheckout'     => 'GOOGLE_ADS_CONV_BEGIN_CHECKOUT',
        'Lead'                 => 'GOOGLE_ADS_CONV_LEAD',
        'CompleteRegistration' => 'GOOGLE_ADS_CONV_SIGN_UP',
        'Subscribe'            => 'GOOGLE_ADS_CONV_SUBSCRIBE',
    ];

    public function isEnabled(): bool
    {
        return (int) get_setting('google_ads_capi_enabled') === 1
            && !empty(env('GOOGLE_ADS_CUSTOMER_ID'))
            && !empty(env('GOOGLE_ADS_DEVELOPER_TOKEN'))
            && !empty(env('GOOGLE_ADS_OAUTH_TOKEN'));
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $envKey = $this->envKey[$eventName] ?? null;
        $conversionAction = $envKey ? env($envKey) : null;
        $gclid = request()->cookie('_gcl_aw') ?: request()->cookie('gclid');

        if (!$conversionAction || !$gclid) {
            return false; // can't send offline conversion without gclid + action
        }

        $customerId = preg_replace('/\D/', '', env('GOOGLE_ADS_CUSTOMER_ID'));

        $body = [
            'conversions' => [[
                'gclid'                  => $gclid,
                'conversionAction'       => "customers/{$customerId}/conversionActions/{$conversionAction}",
                'conversionDateTime'     => gmdate('Y-m-d H:i:s O'),
                'conversionValue'        => isset($payload['value']) ? (float) $payload['value'] : 0,
                'currencyCode'           => $payload['currency'] ?? 'USD',
                'orderId'                => $payload['order_id'] ?? $eventId,
            ]],
            'partialFailure' => true,
        ];

        return $this->post(
            "https://googleads.googleapis.com/v17/customers/{$customerId}:uploadClickConversions",
            $body,
            [
                'Authorization'    => 'Bearer ' . env('GOOGLE_ADS_OAUTH_TOKEN'),
                'developer-token'  => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
                'login-customer-id'=> $customerId,
                'Content-Type'     => 'application/json',
            ]
        );
    }
}
