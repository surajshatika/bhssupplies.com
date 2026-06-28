<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Http;

class OpenAIProvider implements SeoAiProviderInterface
{
    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $apiKey = $this->getApiKey();
        try {
            $response = Http::timeout(config('seo.provider_failover.request_timeout', 10))
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->withToken($apiKey)
                ->post(config('seo.providers.openai.endpoint'), [
                    'model' => config('seo.providers.openai.model'),
                    'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.4,
                    'messages' => array_values(array_filter([
                        $systemPrompt ? ['role' => 'system', 'content' => $systemPrompt] : null,
                        ['role' => 'user', 'content' => $prompt],
                    ])),
                ]);

            if (!$response->successful()) {
                $errMsg = data_get($response->json(), 'error.message', substr($response->body(), 0, 200));
                \Illuminate\Support\Facades\Log::warning('[SEO] OpenAIProvider HTTP error', ['status' => $response->status(), 'error' => $errMsg]);
                \Illuminate\Support\Facades\Cache::put('seo:provider-last-error:openai', ['status' => $response->status(), 'error' => $errMsg], now()->addHours(12));
                return null;
            }

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('[SEO] OpenAIProvider exception', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    public function getName()
    {
        return 'openai';
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.openai.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting('seo_openai_api_key');
        }

        return null;
    }
}
