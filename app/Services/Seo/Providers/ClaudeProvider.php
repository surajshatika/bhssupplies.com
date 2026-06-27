<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Http;

class ClaudeProvider implements SeoAiProviderInterface
{
    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $apiKey = $this->getApiKey();
        try {
            $response = Http::timeout(60)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post(config('seo.providers.claude.endpoint'), [
                    'model' => config('seo.providers.claude.model'),
                    'max_tokens' => isset($options['max_tokens']) ? $options['max_tokens'] : 1200,
                    'system' => $systemPrompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                $errMsg = data_get($response->json(), 'error.message', substr($response->body(), 0, 200));
                \Illuminate\Support\Facades\Log::warning('[SEO] ClaudeProvider HTTP error', ['status' => $response->status(), 'error' => $errMsg]);
                \Illuminate\Support\Facades\Cache::put('seo:provider-last-error:claude', ['status' => $response->status(), 'error' => $errMsg], now()->addHours(12));
                return null;
            }

            return data_get($response->json(), 'content.0.text');
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('[SEO] ClaudeProvider exception', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    public function getName()
    {
        return 'claude';
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.claude.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting('seo_anthropic_api_key');
        }

        return null;
    }
}
