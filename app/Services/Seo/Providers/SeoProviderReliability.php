<?php

namespace App\Services\Seo\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SeoProviderReliability
{
    public const CACHE_PREFIX = 'seo:ai-provider-health:';

    public const PROVIDERS = ['openai', 'claude', 'gemini', 'grok'];

    public function shouldSkip(string $provider): bool
    {
        if (!$this->cooldownEnabled()) {
            return false;
        }

        $provider = $this->normalize($provider);
        $health = $this->health($provider);
        $until = $this->parseTime($health['cooldown_until'] ?? null);

        if (!$until) {
            return false;
        }

        if ($until->isFuture()) {
            return true;
        }

        $health['cooldown_until'] = null;
        $health['consecutive_failures'] = 0;
        $this->store($provider, $health);

        return false;
    }

    public function recordAttempt(string $provider, string $status, ?string $error = null, ?int $durationMs = null): array
    {
        $provider = $this->normalize($provider);
        $health = $this->health($provider);
        $success = $status === 'success';

        $health['attempts']++;
        $health['estimated_cost_usd'] = round(
            (float) $health['estimated_cost_usd'] + $this->estimateAttemptCost($provider),
            6
        );
        $health['last_status'] = $status;
        $health['last_error'] = $error;
        $health['last_duration_ms'] = $durationMs;
        $health['last_attempt_at'] = CarbonImmutable::now()->toDateTimeString();

        if ($success) {
            $health['successes']++;
            // Decay instead of hard reset so a flapping provider
            // (fail, fail, success, fail, fail, ...) still accumulates toward
            // its cooldown threshold instead of never reaching it.
            $health['consecutive_failures'] = max(0, (int) $health['consecutive_failures'] - 2);
            $health['cooldown_until'] = null;
            $health['cooldown_streak'] = max(0, (int) ($health['cooldown_streak'] ?? 0) - 1);
            $health['last_success_at'] = CarbonImmutable::now()->toDateTimeString();
        } else {
            $health['failures']++;
            $health['consecutive_failures']++;

            // Bad credentials never fix themselves — hard-disable for 12h
            // instead of burning an attempt on every entity of every batch.
            if ($this->looksLikeAuthError($error)) {
                $health['cooldown_until'] = CarbonImmutable::now()->addHours(12)->toDateTimeString();
                $health['last_error'] = ($error ? $error . ' ' : '') . '(auth error: provider disabled for 12h — fix the API key in SEO settings)';
            } elseif ($this->cooldownEnabled() && $health['consecutive_failures'] >= $this->failureThreshold()) {
                // Exponential backoff: each consecutive cooldown doubles the
                // duration (15m, 30m, 60m, ...) capped at 24h.
                $streak = (int) ($health['cooldown_streak'] ?? 0) + 1;
                $health['cooldown_streak'] = $streak;
                $minutes = min(1440, $this->cooldownMinutes() * (2 ** ($streak - 1)));
                $health['cooldown_until'] = CarbonImmutable::now()
                    ->addMinutes($minutes)
                    ->toDateTimeString();
            }
        }

        $this->store($provider, $health);

        return $health;
    }

    public function recordFallbackSelection(string $provider): void
    {
        $provider = $this->normalize($provider);
        $health = $this->health($provider);
        $health['fallback_selections']++;
        $this->store($provider, $health);
    }

    public function dashboard(): array
    {
        return collect(self::PROVIDERS)
            ->mapWithKeys(function (string $provider): array {
                $coolingDown = $this->shouldSkip($provider);
                $health = $this->health($provider);

                try {
                    $configured = SeoProviderManager::makeDirect($provider)->isConfigured();
                } catch (Throwable $e) {
                    $configured = false;
                }

                return [$provider => array_merge($health, [
                    'configured' => (bool) $configured,
                    'cooling_down' => $coolingDown,
                    'success_rate' => $health['attempts'] > 0
                        ? round(($health['successes'] / $health['attempts']) * 100)
                        : null,
                ])];
            })
            ->all();
    }

    public function estimateAttemptCost(string $provider): float
    {
        $provider = $this->normalize($provider);

        return max(0.0, (float) config("seo.provider_failover.attempt_cost_usd.{$provider}", 0.001));
    }

    public function reset(?string $provider = null): void
    {
        $providers = $provider ? [$this->normalize($provider)] : self::PROVIDERS;
        foreach ($providers as $name) {
            Cache::forget(self::CACHE_PREFIX . $name);
            Cache::forget('seo:provider-last-error:' . $name);
        }
    }

    protected function health(string $provider): array
    {
        return array_merge($this->emptyHealth(), Cache::get(self::CACHE_PREFIX . $provider, []));
    }

    protected function store(string $provider, array $health): void
    {
        Cache::forever(self::CACHE_PREFIX . $provider, $health);
    }

    protected function cooldownEnabled(): bool
    {
        return (int) SeoProviderManager::setting(
            'seo_ai_provider_cooldown_enabled',
            config('seo.provider_failover.cooldown_enabled', true) ? 1 : 0
        ) === 1;
    }

    protected function failureThreshold(): int
    {
        return min(10, max(1, (int) SeoProviderManager::setting(
            'seo_ai_provider_failure_threshold',
            config('seo.provider_failover.failure_threshold', 3)
        )));
    }

    protected function cooldownMinutes(): int
    {
        return min(1440, max(1, (int) SeoProviderManager::setting(
            'seo_ai_provider_cooldown_minutes',
            config('seo.provider_failover.cooldown_minutes', 15)
        )));
    }

    protected function looksLikeAuthError(?string $error): bool
    {
        if (!$error) {
            return false;
        }

        $needle = mb_strtolower($error);

        foreach (['401', '403', 'unauthorized', 'invalid api key', 'invalid x-api-key', 'authentication', 'permission denied', 'api key not valid'] as $marker) {
            if (str_contains($needle, $marker)) {
                return true;
            }
        }

        return false;
    }

    protected function normalize(string $provider): string
    {
        return SeoProviderManager::normalizeName($provider) ?: 'unknown';
    }

    protected function parseTime(?string $value): ?CarbonImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function emptyHealth(): array
    {
        return [
            'attempts' => 0,
            'successes' => 0,
            'failures' => 0,
            'consecutive_failures' => 0,
            'fallback_selections' => 0,
            'estimated_cost_usd' => 0.0,
            'last_status' => null,
            'last_error' => null,
            'last_duration_ms' => null,
            'last_attempt_at' => null,
            'last_success_at' => null,
            'cooldown_until' => null,
            'cooldown_streak' => 0,
        ];
    }
}
