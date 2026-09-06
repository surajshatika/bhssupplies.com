<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Shared base for every provider that speaks the OpenAI chat-completions wire
 * format — which is most of them: Grok, Perplexity, Mistral, DeepSeek, Groq,
 * OpenRouter, Together, Fireworks, Qwen, Moonshot and others all accept the
 * same `{model, messages[], temperature}` payload and return the same
 * `choices[0].message.content` shape.
 *
 * Before this existed each provider was a byte-identical 68-line copy differing
 * only in its endpoint and model string. Subclasses now declare just those
 * few values, so adding a provider is a handful of lines and a behaviour fix
 * lands everywhere at once instead of needing the same edit in eleven files.
 */
abstract class OpenAiCompatibleProvider implements SeoAiProviderInterface
{
    use ResilientProviderHttp;

    /** Provider key used in config, settings, cache, and the failover chain. */
    abstract public function getName(): string;

    abstract protected function defaultEndpoint(): string;

    abstract protected function defaultModel(): string;

    /** Business-setting key holding the API key when no env/config value is set. */
    protected function settingKey(): string
    {
        return 'seo_' . $this->getName() . '_api_key';
    }

    /** Extra headers a specific provider requires (OpenRouter wants attribution). */
    protected function extraHeaders(): array
    {
        return [];
    }

    /** Hook for providers needing extra body fields. */
    protected function payloadExtras(array $options): array
    {
        return [];
    }

    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $name = $this->getName();

        try {
            $payload = array_merge([
                'model'       => $this->model(),
                'temperature' => $options['temperature'] ?? 0.4,
                'messages'    => array_values(array_filter([
                    $systemPrompt ? ['role' => 'system', 'content' => $systemPrompt] : null,
                    ['role' => 'user', 'content' => $prompt],
                ])),
            ], $this->payloadExtras($options));

            $response = $this->providerHttp()
                ->withToken($this->getApiKey())
                ->withHeaders($this->extraHeaders())
                ->post($this->endpoint(), $payload);

            if (!$response->successful()) {
                $errMsg = data_get($response->json(), 'error.message', substr($response->body(), 0, 200));

                Log::warning("[SEO] {$name} HTTP error", ['status' => $response->status(), 'error' => $errMsg]);
                // Surfaced by the provider-health panel and Monitoring dashboard.
                Cache::put("seo:provider-last-error:{$name}", [
                    'status' => $response->status(),
                    'error'  => $errMsg,
                ], now()->addHours(12));

                return null;
            }

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning("[SEO] {$name} exception", ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    protected function endpoint(): string
    {
        return config('seo.providers.' . $this->getName() . '.endpoint', $this->defaultEndpoint());
    }

    protected function model(): string
    {
        return config('seo.providers.' . $this->getName() . '.model', $this->defaultModel());
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.' . $this->getName() . '.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting($this->settingKey());
        }

        return null;
    }
}
