<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Http;

class GrokProvider implements SeoAiProviderInterface
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
                ->withToken($apiKey)
                ->post(config('seo.providers.grok.endpoint', 'https://api.x.ai/v1/chat/completions'), [
                    'model' => config('seo.providers.grok.model', 'grok-3-mini'),
                    'temperature' => $options['temperature'] ?? 0.4,
                    'messages' => array_values(array_filter([
                        $systemPrompt ? ['role' => 'system', 'content' => $systemPrompt] : null,
                        ['role' => 'user', 'content' => $prompt],
                    ])),
                ]);

            if (!$response->successful()) {
                return null;
            }

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    public function getName()
    {
        return 'grok';
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.grok.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting('seo_grok_api_key');
        }

        return null;
    }
}
