<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SmsRateLimiterService
{
    public function tooManyAttempts(string $phone, string $context, int $maxAttempts = 5, int $decaySeconds = 600): bool
    {
        return (int) Cache::get($this->attemptKey($phone, $context), 0) >= $maxAttempts
            || Cache::has($this->cooldownKey($phone, $context));
    }

    public function hit(string $phone, string $context, int $decaySeconds = 600, int $cooldownSeconds = 60): void
    {
        $key = $this->attemptKey($phone, $context);
        Cache::put($key, (int) Cache::get($key, 0) + 1, $decaySeconds);
        Cache::put($this->cooldownKey($phone, $context), 1, $cooldownSeconds);
        Cache::put($this->cooldownKey($phone, $context) . ':expires_at', time() + $cooldownSeconds, $cooldownSeconds);
    }

    public function clear(string $phone, string $context): void
    {
        Cache::forget($this->attemptKey($phone, $context));
        Cache::forget($this->cooldownKey($phone, $context));
        Cache::forget($this->cooldownKey($phone, $context) . ':expires_at');
    }

    public function secondsRemaining(string $phone, string $context): int
    {
        $key = $this->cooldownKey($phone, $context);
        $expiresAt = Cache::get($key . ':expires_at');
        return $expiresAt ? max(0, (int) $expiresAt - time()) : 60;
    }

    protected function attemptKey(string $phone, string $context): string
    {
        return 'sms_rate:' . $context . ':' . md5($phone);
    }

    protected function cooldownKey(string $phone, string $context): string
    {
        return 'sms_cooldown:' . $context . ':' . md5($phone);
    }
}
