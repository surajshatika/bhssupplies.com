<?php

namespace App\Services\Marketing\Channels;

class TikTokEventsChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'tiktok'; }
    public function name(): string { return 'TikTok Events API'; }

    /**
     * Standard event mapping → TikTok event names.
     * https://business-api.tiktok.com/portal/docs?id=1741601162187777
     */
    protected array $map = [
        'Purchase'             => 'CompletePayment',
        'AddToCart'            => 'AddToCart',
        'AddToWishlist'        => 'AddToWishlist',
        'ViewContent'          => 'ViewContent',
        'InitiateCheckout'     => 'InitiateCheckout',
        'Search'               => 'Search',
        'Lead'                 => 'SubmitForm',
        'CompleteRegistration' => 'CompleteRegistration',
        'Subscribe'            => 'Subscribe',
    ];

    public function isEnabled(): bool
    {
        return (int) get_setting('tiktok_capi_enabled') === 1
            && !empty(env('TIKTOK_PIXEL_ID'))
            && !empty(env('TIKTOK_ACCESS_TOKEN'));
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $tiktokEvent = $this->map[$eventName] ?? null;
        if (!$tiktokEvent) return false;

        $u    = $this->hashedUserData();
        $body = [
            'event_source'    => 'web',
            'event_source_id' => env('TIKTOK_PIXEL_ID'),
            'data' => [[
                'event'      => $tiktokEvent,
                'event_time' => time(),
                'event_id'   => $eventId,
                'user' => array_filter([
                    'email'       => $u['email_sha256'] ?? null,
                    'phone'       => $u['phone_sha256'] ?? null,
                    'external_id' => $u['external_id_sha256'] ?? null,
                    'ip'          => $u['ip'] ?? null,
                    'user_agent'  => $u['user_agent'] ?? null,
                    'ttp'         => request()->cookie('_ttp'),
                ]),
                'page' => ['url' => $u['url'] ?? null],
                'properties' => $this->buildProperties($payload),
            ]],
        ];

        return $this->post(
            'https://business-api.tiktok.com/open_api/v1.3/event/track/',
            $body,
            ['Access-Token' => env('TIKTOK_ACCESS_TOKEN')]
        );
    }

    protected function buildProperties(array $payload): array
    {
        $props = [
            'currency' => $payload['currency'] ?? null,
            'value'    => isset($payload['value']) ? (float) $payload['value'] : null,
        ];

        if (!empty($payload['content_ids'])) {
            $props['contents'] = array_map(fn ($id) => [
                'content_id'   => (string) $id,
                'content_type' => 'product',
            ], $payload['content_ids']);
        }
        if (!empty($payload['query']))         $props['query'] = $payload['query'];
        if (!empty($payload['order_id']))      $props['order_id'] = (string) $payload['order_id'];

        return array_filter($props, fn ($v) => !is_null($v) && $v !== '');
    }
}
