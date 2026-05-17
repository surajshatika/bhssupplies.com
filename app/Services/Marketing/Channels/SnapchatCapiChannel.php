<?php

namespace App\Services\Marketing\Channels;

class SnapchatCapiChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'snapchat'; }
    public function name(): string { return 'Snapchat Conversions API'; }

    protected array $map = [
        'Purchase'             => 'PURCHASE',
        'AddToCart'            => 'ADD_CART',
        'AddToWishlist'        => 'SAVE',
        'ViewContent'          => 'VIEW_CONTENT',
        'InitiateCheckout'     => 'START_CHECKOUT',
        'Search'               => 'SEARCH',
        'Lead'                 => 'SIGN_UP',
        'CompleteRegistration' => 'SIGN_UP',
        'Subscribe'            => 'SUBSCRIBE',
    ];

    public function isEnabled(): bool
    {
        return (int) get_setting('snapchat_capi_enabled') === 1
            && !empty(env('SNAPCHAT_PIXEL_ID'))
            && !empty(env('SNAPCHAT_ACCESS_TOKEN'));
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $snapEvent = $this->map[$eventName] ?? null;
        if (!$snapEvent) return false;

        $u    = $this->hashedUserData();
        $pixelId = env('SNAPCHAT_PIXEL_ID');

        $body = [
            'data' => [[
                'event_name'    => $snapEvent,
                'event_time'    => time(),
                'event_source_url' => $u['url'] ?? null,
                'action_source' => 'WEB',
                'event_id'      => $eventId,
                'user_data' => array_filter([
                    'em'                 => isset($u['email_sha256']) ? [$u['email_sha256']] : null,
                    'ph'                 => isset($u['phone_sha256']) ? [$u['phone_sha256']] : null,
                    'external_id'        => isset($u['external_id_sha256']) ? [$u['external_id_sha256']] : null,
                    'client_ip_address'  => $u['ip'] ?? null,
                    'client_user_agent'  => $u['user_agent'] ?? null,
                    'sc_click_id'        => request()->cookie('_scid'),
                ]),
                'custom_data' => array_filter([
                    'currency'    => $payload['currency'] ?? null,
                    'value'       => isset($payload['value']) ? (float) $payload['value'] : null,
                    'order_id'    => $payload['order_id'] ?? null,
                    'content_ids' => $payload['content_ids'] ?? null,
                ]),
            ]],
        ];

        return $this->post(
            "https://tr.snapchat.com/v3/{$pixelId}/events?access_token=" . urlencode(env('SNAPCHAT_ACCESS_TOKEN')),
            $body
        );
    }
}
