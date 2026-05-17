<?php

namespace App\Services\Marketing\Channels;

class PinterestConversionChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'pinterest'; }
    public function name(): string { return 'Pinterest Conversions API'; }

    /**
     * Pinterest event names
     * https://developers.pinterest.com/docs/conversions/conversion-management/
     */
    protected array $map = [
        'Purchase'             => 'checkout',
        'AddToCart'            => 'add_to_cart',
        'ViewContent'          => 'page_visit',
        'InitiateCheckout'     => 'checkout',
        'Search'               => 'search',
        'Lead'                 => 'lead',
        'CompleteRegistration' => 'signup',
        'Subscribe'            => 'lead',
    ];

    public function isEnabled(): bool
    {
        return (int) get_setting('pinterest_capi_enabled') === 1
            && !empty(env('PINTEREST_AD_ACCOUNT_ID'))
            && !empty(env('PINTEREST_ACCESS_TOKEN'));
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $pinEvent = $this->map[$eventName] ?? null;
        if (!$pinEvent) return false;

        $u = $this->hashedUserData();

        $body = [
            'data' => [[
                'event_name'    => $pinEvent,
                'action_source' => 'web',
                'event_time'    => time(),
                'event_id'      => $eventId,
                'event_source_url' => $u['url'] ?? null,
                'user_data' => array_filter([
                    'em'          => isset($u['email_sha256']) ? [$u['email_sha256']] : null,
                    'ph'          => isset($u['phone_sha256']) ? [$u['phone_sha256']] : null,
                    'external_id' => isset($u['external_id_sha256']) ? [$u['external_id_sha256']] : null,
                    'client_ip_address' => $u['ip'] ?? null,
                    'client_user_agent' => $u['user_agent'] ?? null,
                ]),
                'custom_data' => array_filter([
                    'currency' => $payload['currency'] ?? null,
                    'value'    => isset($payload['value']) ? (string) $payload['value'] : null,
                    'order_id' => $payload['order_id'] ?? null,
                    'search_string' => $payload['query'] ?? null,
                    'content_ids' => $payload['content_ids'] ?? null,
                ]),
            ]],
        ];

        $accountId = env('PINTEREST_AD_ACCOUNT_ID');
        return $this->post(
            "https://api.pinterest.com/v5/ad_accounts/{$accountId}/events",
            $body,
            ['Authorization' => 'Bearer ' . env('PINTEREST_ACCESS_TOKEN')]
        );
    }
}
