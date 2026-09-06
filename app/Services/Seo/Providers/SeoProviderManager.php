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

    /**
     * The single source of truth for which providers exist.
     *
     * Previously a switch statement and a separate in_array() whitelist both
     * had to be edited in step; when they drifted a provider would construct
     * but fail name validation (or worse, validate and then fall through to
     * NullProvider). One registry makes that class of bug impossible.
     *
     * Note: 'grok' (xAI) and 'groq' (Groq LPU inference) are DIFFERENT
     * providers whose names differ by one letter. They are deliberately kept
     * distinct with no cross-aliasing — silently routing one to the other
     * would be far more confusing than an unknown-provider error.
     */
    public const PROVIDERS = [
        'openai'     => OpenAIProvider::class,
        'claude'     => ClaudeProvider::class,
        'gemini'     => GeminiProvider::class,
        'grok'       => GrokProvider::class,
        'perplexity' => PerplexityProvider::class,
        'mistral'    => MistralProvider::class,
        'deepseek'   => DeepSeekProvider::class,
        'groq'       => GroqProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'together'   => TogetherProvider::class,
        'fireworks'  => FireworksProvider::class,
        'qwen'       => QwenProvider::class,
        'moonshot'   => MoonshotProvider::class,
        'cohere'     => CohereProvider::class,
    ];

    /**
     * Presentation + wiring metadata for each provider, in one place.
     *
     * The settings form, the provider dropdowns, the settings save/load maps
     * and the health panel all read from here. Previously each of those kept
     * its own hand-maintained list, so adding a provider meant editing five
     * places and every miss produced a silently unsaved API key.
     *
     * `field` is the form input name and `setting` the business-setting key;
     * both follow `<provider>_api_key` / `seo_<provider>_api_key` except
     * Claude, which predates the convention and uses "anthropic".
     */
    public const META = [
        'openai'     => ['label' => 'OpenAI (ChatGPT)',        'badge' => 'success',   'placeholder' => 'sk-...',     'hint' => 'Content generation, image generation, TruSEO analysis'],
        'claude'     => ['label' => 'Claude (Anthropic)',      'badge' => 'primary',   'placeholder' => 'sk-ant-...', 'hint' => 'Advanced content, schema markup, SEO strategy', 'field' => 'anthropic_api_key', 'setting' => 'seo_anthropic_api_key'],
        'gemini'     => ['label' => 'Gemini (Google)',         'badge' => 'info',      'placeholder' => 'AIza...',    'hint' => 'Multimodal content, structured data, embeddings'],
        'grok'       => ['label' => 'Grok (xAI)',              'badge' => 'warning',   'placeholder' => 'xai-...',    'hint' => 'Real-time insights with X/Twitter data access'],
        'perplexity' => ['label' => 'Perplexity (Web Search)', 'badge' => 'secondary', 'placeholder' => 'pplx-...',   'hint' => 'Live-web-grounded answers — best for gap and competitor research'],
        'mistral'    => ['label' => 'Mistral AI',              'badge' => 'dark',      'placeholder' => '...',        'hint' => 'European-hosted; also provides embeddings for clustering'],
        'deepseek'   => ['label' => 'DeepSeek (Low Cost)',     'badge' => 'light',     'placeholder' => 'sk-...',     'hint' => 'Lowest cost per token — the value pick for bulk SEO passes'],
        'groq'       => ['label' => 'Groq (Fastest)',          'badge' => 'danger',    'placeholder' => 'gsk_...',    'hint' => 'LPU inference, highest tokens/sec — best for streaming. Not xAI\'s Grok.'],
        'openrouter' => ['label' => 'OpenRouter (300+ models)','badge' => 'primary',   'placeholder' => 'sk-or-...',  'hint' => 'One key fronting most major labs; switch model via OPENROUTER_MODEL'],
        'together'   => ['label' => 'Together AI',             'badge' => 'info',      'placeholder' => '...',        'hint' => 'Hosted open-weight models, cheap for bulk generation'],
        'fireworks'  => ['label' => 'Fireworks AI',            'badge' => 'warning',   'placeholder' => 'fw_...',     'hint' => 'Fast hosted open-weight models'],
        'qwen'       => ['label' => 'Qwen (Alibaba)',          'badge' => 'secondary', 'placeholder' => 'sk-...',     'hint' => 'Strong multilingual coverage for non-English SEO'],
        'moonshot'   => ['label' => 'Moonshot (Kimi)',         'badge' => 'dark',      'placeholder' => 'sk-...',     'hint' => 'Long-context model for whole-page and multi-document analysis'],
        'cohere'     => ['label' => 'Cohere',                  'badge' => 'success',   'placeholder' => '...',        'hint' => 'Also offers embeddings and reranking'],
    ];

    /** Provider key => display label, for dropdowns. */
    public static function labels(): array
    {
        return array_map(fn($meta) => $meta['label'], static::META);
    }

    /** Full metadata with `field` and `setting` defaults filled in. */
    public static function meta(): array
    {
        $resolved = [];
        foreach (static::META as $name => $meta) {
            $resolved[$name] = $meta + [
                'field'   => $name . '_api_key',
                'setting' => 'seo_' . $name . '_api_key',
            ];
        }

        return $resolved;
    }

    /** Accepted alternate spellings, mapped to the canonical provider key. */
    public const ALIASES = [
        'anthropic'  => 'claude',
        'google'     => 'gemini',
        'chatgpt'    => 'openai',
        'xai'        => 'grok',
        'x.ai'       => 'grok',
        'togetherai' => 'together',
        'kimi'       => 'moonshot',
        'dashscope'  => 'qwen',
        'alibaba'    => 'qwen',
        'open-router' => 'openrouter',
    ];

    public static function makeDirect($name = null): SeoAiProviderInterface
    {
        $name = static::normalizeName($name ?: config('seo.default_provider', 'openai'));

        $class = static::PROVIDERS[$name] ?? null;

        return $class ? new $class() : new NullProvider();
    }

    /** Provider keys, for UI dropdowns and validation. */
    public static function available(): array
    {
        return array_keys(static::PROVIDERS);
    }

    public static function fallbackOrder($preferredProvider = null): array
    {
        $preferred = static::normalizeName($preferredProvider ?: config('seo.default_provider', 'openai'));
        if (!static::failoverEnabled()) {
            return array_values(array_filter([$preferred]));
        }

        $configuredOrder = static::setting(
            'seo_ai_failover_order',
            config('seo.provider_failover.order', ['claude', 'openai', 'gemini', 'grok', 'perplexity', 'mistral', 'deepseek'])
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

        $name = static::ALIASES[$name] ?? $name;

        return isset(static::PROVIDERS[$name]) ? $name : null;
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
