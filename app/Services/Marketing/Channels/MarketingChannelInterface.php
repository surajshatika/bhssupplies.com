<?php

namespace App\Services\Marketing\Channels;

interface MarketingChannelInterface
{
    /** Lowercase slug used in settings keys (e.g. "tiktok"). */
    public function slug(): string;

    /** Display name shown in admin UI. */
    public function name(): string;

    /** Is this channel configured + toggled ON in admin settings? */
    public function isEnabled(): bool;

    /**
     * Send one standardised marketing event. Implementations map the standard
     * event name (Purchase, AddToCart, ViewContent, InitiateCheckout, Search,
     * Lead, CompleteRegistration, Subscribe) to channel-specific names.
     *
     * @return bool true if sent successfully, false otherwise (never throws).
     */
    public function send(string $eventName, array $payload, string $eventId): bool;
}
