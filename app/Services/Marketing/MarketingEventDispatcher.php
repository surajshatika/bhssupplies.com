<?php

namespace App\Services\Marketing;

use App\Jobs\Marketing\DispatchMarketingEventJob;
use App\Services\Marketing\EventStore;
use App\Services\Marketing\Channels\GoogleAdsConversionChannel;
use App\Services\Marketing\Channels\LinkedInConversionChannel;
use App\Services\Marketing\Channels\MarketingChannelInterface;
use App\Services\Marketing\Channels\MetaCapiChannel;
use App\Services\Marketing\Channels\PinterestConversionChannel;
use App\Services\Marketing\Channels\SnapchatCapiChannel;
use App\Services\Marketing\Channels\TikTokEventsChannel;
use App\Services\Marketing\Channels\TwitterCapiChannel;
use Illuminate\Support\Facades\Log;

class MarketingEventDispatcher
{
    /** @return array<class-string<MarketingChannelInterface>> */
    public function channelClasses(): array
    {
        return [
            MetaCapiChannel::class,
            TikTokEventsChannel::class,
            PinterestConversionChannel::class,
            SnapchatCapiChannel::class,
            LinkedInConversionChannel::class,
            TwitterCapiChannel::class,
            GoogleAdsConversionChannel::class,
        ];
    }

    /** @return MarketingChannelInterface[] */
    public function allChannels(): array
    {
        return array_map(fn ($cls) => app($cls), $this->channelClasses());
    }

    /** @return MarketingChannelInterface[] */
    public function enabledChannels(): array
    {
        return array_values(array_filter($this->allChannels(), fn ($c) => $c->isEnabled()));
    }

    /**
     * Generate a deterministic event_id used to deduplicate the browser-side
     * Pixel firing with all server-side CAPI firings.
     */
    public static function eventId(string $eventName, $primaryKey): string
    {
        return strtolower($eventName) . '_' . $primaryKey;
    }

    /**
     * Fan out a single standardised event to every enabled channel — queued so
     * the calling request never blocks on slow third-party APIs.
     *
     * Standardised event names: Purchase, AddToCart, AddToWishlist,
     * ViewContent, InitiateCheckout, Search, Lead, CompleteRegistration,
     * Subscribe.
     *
     * `$payload` keys depend on the event:
     *   - Purchase:             ['order' => CombinedOrder, 'value', 'currency', 'order_id', 'content_ids']
     *   - AddToCart:            ['product' => Product, 'value', 'currency', 'content_ids']
     *   - AddToWishlist:        ['product_id', 'content_ids']
     *   - ViewContent:          ['product' => Product, 'value', 'currency', 'content_ids']
     *   - InitiateCheckout:     ['cart' => Collection, 'value', 'num_items', 'content_ids', 'currency']
     *   - Search:               ['query']
     *   - Lead/Subscribe/etc.:  ['content_name'?, 'email'?]
     */
    public function dispatch(string $eventName, array $payload, ?string $eventId = null, bool $queued = true): void
    {
        $eventId = $eventId ?: self::eventId($eventName, uniqid('', true));

        // First-party append: synchronously write to the local event store so the
        // analytics warehouse stays accurate even if all CAPI channels are off.
        try {
            app(EventStore::class)->record($eventName, $payload, $eventId);
        } catch (\Throwable $e) {
            Log::debug('[MarketingEventDispatcher] EventStore record failed: ' . $e->getMessage());
        }

        // strip non-serialisable keys for queue safety; they're rebuilt where needed.
        $serialisable = $this->serialisablePayload($payload);

        if ($queued) {
            DispatchMarketingEventJob::dispatch($eventName, $serialisable, $eventId)->afterResponse();
            return;
        }

        $this->fireSync($eventName, $payload, $eventId);
    }

    /**
     * Called by the queue job. Resolves channels fresh in the worker.
     */
    public function fireSync(string $eventName, array $payload, string $eventId): void
    {
        foreach ($this->enabledChannels() as $channel) {
            try {
                $channel->send($eventName, $payload, $eventId);
            } catch (\Throwable $e) {
                Log::warning('[MarketingEventDispatcher] channel threw', [
                    'channel' => $channel->slug(),
                    'event'   => $eventName,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function serialisablePayload(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (is_object($v)) {
                if (method_exists($v, 'getKey')) {
                    $out[$k . '_id'] = $v->getKey();
                    $out[$k . '_class'] = get_class($v);
                }
                continue;
            }
            $out[$k] = $v;
        }
        return $out;
    }
}
