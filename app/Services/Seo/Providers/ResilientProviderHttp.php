<?php

namespace App\Services\Seo\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Shared HTTP client setup for AI providers: split connect/read timeouts and
 * automatic retry with backoff on transient failures (429 rate limits, 5xx,
 * connection errors). Auth errors (401/403) never retry — a bad key stays bad.
 */
trait ResilientProviderHttp
{
    protected function providerHttp(): PendingRequest
    {
        return Http::timeout((int) config('seo.provider_failover.request_timeout', 45))
            ->connectTimeout((int) config('seo.provider_failover.connect_timeout', 5))
            ->withOptions(['verify' => config('seo.ssl_verify', true)])
            ->retry(
                (int) config('seo.provider_failover.http_retries', 2),
                1000,
                function (Throwable $exception): bool {
                    if ($exception instanceof RequestException) {
                        $status = $exception->response->status();

                        return $status === 429 || $status >= 500;
                    }

                    // Connection-level failures (DNS, timeout, reset).
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                },
                throw: false
            );
    }
}
