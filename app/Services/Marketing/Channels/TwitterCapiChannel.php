<?php

namespace App\Services\Marketing\Channels;

class TwitterCapiChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'twitter'; }
    public function name(): string { return 'X (Twitter) Conversions API'; }

    /**
     * X event names are arbitrary strings tied to a specific event_id created
     * in Ads Manager. We use the standard event names directly and let the
     * advertiser map them in the X dashboard.
     */
    public function isEnabled(): bool
    {
        return (int) get_setting('twitter_capi_enabled') === 1
            && !empty(env('TWITTER_PIXEL_ID'))
            && !empty(env('TWITTER_BEARER_TOKEN'));
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $u = $this->hashedUserData();
        $pixelId = env('TWITTER_PIXEL_ID');

        $body = [
            'conversions' => [[
                'event_id'         => $eventId,
                'event_name'       => $eventName,
                'conversion_time'  => gmdate('c'),
                'identifiers' => array_values(array_filter([
                    isset($u['email_sha256']) ? ['hashed_email' => $u['email_sha256']] : null,
                    isset($u['phone_sha256']) ? ['hashed_phone_number' => $u['phone_sha256']] : null,
                    ['twclid' => request()->cookie('twclid')],
                ])),
                'conversion_value' => isset($payload['value']) ? [
                    'currency' => $payload['currency'] ?? 'USD',
                    'amount'   => (float) $payload['value'],
                ] : null,
            ]],
        ];

        return $this->post(
            "https://ads-api.twitter.com/12/measurement/conversions/{$pixelId}",
            $body,
            ['Authorization' => 'Bearer ' . env('TWITTER_BEARER_TOKEN')]
        );
    }
}
