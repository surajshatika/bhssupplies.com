<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\MarketingEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchMarketingEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(
        public string $eventName,
        public array  $payload,
        public string $eventId
    ) {}

    public function handle(MarketingEventDispatcher $dispatcher): void
    {
        // Re-hydrate Eloquent models referenced by *_id / *_class hints.
        $payload = $this->payload;
        foreach ($payload as $k => $v) {
            if (str_ends_with($k, '_class') && class_exists($v)) {
                $modelKey = substr($k, 0, -6);
                $idKey    = $modelKey . '_id';
                if (isset($payload[$idKey])) {
                    try {
                        $payload[$modelKey] = $v::find($payload[$idKey]);
                    } catch (\Throwable $e) {
                        Log::debug('[MarketingEventJob] could not rehydrate', ['model' => $v, 'id' => $payload[$idKey]]);
                    }
                }
            }
        }

        $dispatcher->fireSync($this->eventName, $payload, $this->eventId);
    }
}
