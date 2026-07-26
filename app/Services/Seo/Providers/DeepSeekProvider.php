<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Http;

class DeepSeekProvider implements SeoAiProviderInterface
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
                ->post(config('seo.providers.deepseek.endpoint', 'https://api.deepseek.com/chat/completions'), [
                    'model' => config('seo.providers.deepseek.model', 'deepseek-chat'),
                    'temperature' => $options['temperature'] ?? 0.4,
                    'messages' => array_values(array_filter([
                        $systemPrompt ? ['role' => 'system', 'content' => $systemPrompt] : null,
                        ['role' => 'user', 'content' => $prompt],
                    ])),
                ]);

            if (!$response->successful()) {
                $errMsg = data_get($response->json(), 'error.message', substr($response->body(), 0, 200));
                \Illuminate\Support\Facades\Log::warning('[SEO] DeepSeekProvider HTTP error', ['status' => $response->status(), 'error' => $errMsg]);
                \Illuminate\Support\Facades\Cache::put('seo:provider-last-error:deepseek', ['status' => $response->status(), 'error' => $errMsg], now()->addHours(12));
                return null;
            }

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SEO] DeepSeekProvider exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    public function getName()
    {
        return 'deepseek';
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.deepseek.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting('seo_deepseek_api_key');
        }

        return null;
    }
}
