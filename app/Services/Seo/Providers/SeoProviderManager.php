<?php

namespace App\Services\Seo\Providers;

use Throwable;

class SeoProviderManager
{
    public static function make($name = null)
    {
        $name = static::normalizeName($name ?: config('seo.default_provider', 'openai'));

        if (static::failoverEnabled()) {
            return new FailoverSeoProvider($name);
        }

        return static::makeDirect($name);
    }

    public static function makeDirect($name = null): SeoAiProviderInterface
    {
        $name = static::normalizeName($name ?: config('seo.default_provider', 'openai'));

        switch ($name) {
            case 'claude':
                return new ClaudeProvider();
            case 'gemini':
                return new GeminiProvider();
            case 'openai':
                return new OpenAIProvider();
            case 'grok':
                return new GrokProvider();
            default:
                return new NullProvider();
        }
    }

    public static function fallbackOrder($preferredProvider = null): array
    {
        $preferred = static::normalizeName($preferredProvider ?: config('seo.default_provider', 'openai'));
        if (!static::failoverEnabled()) {
            return array_values(array_filter([$preferred]));
        }

        $configuredOrder = static::setting(
            'seo_ai_failover_order',
            config('seo.provider_failover.order', ['claude', 'openai', 'gemini', 'grok'])
        );
        $order = is_array($configuredOrder)
            ? $configuredOrder
            : preg_split('/[\s,]+/', (string) $configuredOrder);
        $order = array_map(fn($name) => static::normalizeName($name), $order ?: []);
        $order = array_values(array_filter(array_unique(array_merge([$preferred], $order))));
        $maxAttempts = min(4, max(1, (int) static::setting(
            'seo_ai_failover_max_attempts',
            config('seo.provider_failover.max_attempts', 4)
        )));

        return array_slice($order, 0, $maxAttempts);
    }

    public static function failoverEnabled(): bool
    {
        return (int) static::setting(
            'seo_ai_failover_enabled',
            config('seo.provider_failover.enabled', true) ? 1 : 0
        ) === 1;
    }

    public static function normalizeName($name): ?string
    {
        $name = strtolower(trim((string) $name));

        return match ($name) {
            'anthropic' => 'claude',
            'google' => 'gemini',
            'chatgpt' => 'openai',
            'xai' => 'grok',
            default => in_array($name, ['claude', 'gemini', 'openai', 'grok'], true) ? $name : null,
        };
    }

    public static function setting(string $key, $default)
    {
        if (!config('seo.provider_failover.database_settings', true) || !function_exists('get_setting')) {
            return $default;
        }

        try {
            return get_setting($key, $default);
        } catch (Throwable $e) {
            return $default;
        }
    }
}
