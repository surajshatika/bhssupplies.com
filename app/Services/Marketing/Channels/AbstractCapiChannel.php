<?php

namespace App\Services\Marketing\Channels;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractCapiChannel implements MarketingChannelInterface
{
    protected int $timeout = 10;
    protected int $retries = 2;
    protected int $retryDelayMs = 400;

    abstract public function slug(): string;
    abstract public function name(): string;
    abstract public function send(string $eventName, array $payload, string $eventId): bool;

    public function isEnabled(): bool
    {
        return (int) get_setting($this->slug() . '_capi_enabled') === 1;
    }

    /** Hashed user identifiers — most CAPIs accept SHA-256 hashed PII. */
    protected function hashedUserData(): array
    {
        $user = auth()->check() ? auth()->user() : null;

        $data = [];
        if ($user) {
            if (!empty($user->email)) $data['email_sha256'] = hash('sha256', strtolower(trim($user->email)));
            if (!empty($user->phone)) $data['phone_sha256'] = hash('sha256', preg_replace('/\D/', '', $user->phone));
            $data['external_id_sha256'] = hash('sha256', (string) $user->id);
        }

        $data['ip']         = request()->ip();
        $data['user_agent'] = request()->userAgent();
        $data['url']        = request()->fullUrl() ?: url('/');

        return array_filter($data, fn ($v) => !is_null($v) && $v !== '');
    }

    protected function post(string $url, array $body, array $headers = []): bool
    {
        try {
            /** @var Response $response */
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, $this->retryDelayMs, throw: false)
                ->withHeaders($headers + ['Accept' => 'application/json'])
                ->post($url, $body);

            if (!$response->successful()) {
                Log::warning('[Marketing][' . $this->slug() . '] non-2xx', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 500),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('[Marketing][' . $this->slug() . '] request failed', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function setting(string $key, $default = null)
    {
        return get_setting($this->slug() . '_' . $key, $default);
    }
}
