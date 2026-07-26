<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Http;

class PerplexityProvider implements SeoAiProviderInterface
{
    use ResilientProviderHttp;

    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $apiKey = $this->getApiKey();
        try {
            $response = $this->providerHttp()
                ->withToken($apiKey)
                ->post(config('seo.providers.perplexity.endpoint', 'https://api.perplexity.ai/chat/completions'), [
                    'model' => config('seo.providers.perplexity.model', 'sonar-pro'),
                    'temperature' => $options['temperature'] ?? 0.4,
                    'messages' => array_values(array_filter([
                        $systemPrompt ? ['role' => 'system', 'content' => $systemPrompt] : null,
                        ['role' => 'user', 'content' => $prompt],
                    ])),
                ]);

            if (!$response->successful()) {
                $errMsg = data_get($response->json(), 'error.message', substr($response->body(), 0, 200));
                \Illuminate\Support\Facades\Log::warning('[SEO] PerplexityProvider HTTP error', ['status' => $response->status(), 'error' => $errMsg]);
                \Illuminate\Support\Facades\Cache::put('seo:provider-last-error:perplexity', ['status' => $response->status(), 'error' => $errMsg], now()->addHours(12));
                return null;
            }

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SEO] PerplexityProvider exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    public function getName()
    {
        return 'perplexity';
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.perplexity.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting('seo_perplexity_api_key');
        }

        return null;
    }
}
