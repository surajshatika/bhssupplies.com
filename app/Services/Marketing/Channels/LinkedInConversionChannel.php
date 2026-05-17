<?php

namespace App\Services\Marketing\Channels;

class LinkedInConversionChannel extends AbstractCapiChannel
{
    public function slug(): string { return 'linkedin'; }
    public function name(): string { return 'LinkedIn Conversions API'; }

    public function isEnabled(): bool
    {
        return (int) get_setting('linkedin_capi_enabled') === 1
            && !empty(env('LINKEDIN_CONVERSION_RULE_URN'))
            && !empty(env('LINKEDIN_ACCESS_TOKEN'));
    }

    /**
     * LinkedIn Conversions API needs a per-event Conversion Rule URN that
     * the user creates in Campaign Manager. We map standard events → distinct
     * URNs configured in env (one URN per event). If a URN is missing the event
     * is silently skipped.
     */
    protected function urnFor(string $eventName): ?string
    {
        $key = 'LINKEDIN_URN_' . strtoupper($eventName);
        return env($key) ?: env('LINKEDIN_CONVERSION_RULE_URN');
    }

    public function send(string $eventName, array $payload, string $eventId): bool
    {
        $urn = $this->urnFor($eventName);
        if (!$urn) return false;

        $u = $this->hashedUserData();
        $body = [
            'conversion'         => $urn,
            'conversionHappenedAt' => round(microtime(true) * 1000),
            'conversionValue'    => isset($payload['value']) ? [
                'currencyCode' => $payload['currency'] ?? 'USD',
                'amount'       => (string) $payload['value'],
            ] : null,
            'eventId' => $eventId,
            'user' => [
                'userIds' => array_values(array_filter([
                    isset($u['email_sha256']) ? ['idType' => 'SHA256_EMAIL', 'idValue' => $u['email_sha256']] : null,
                ])),
                'userInfo' => array_filter([
                    'firstName' => null,
                    'lastName'  => null,
                ]),
            ],
        ];
        $body = array_filter($body, fn ($v) => !is_null($v));

        return $this->post(
            'https://api.linkedin.com/rest/conversionEvents',
            $body,
            [
                'Authorization'        => 'Bearer ' . env('LINKEDIN_ACCESS_TOKEN'),
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version'     => '202404',
            ]
        );
    }
}
