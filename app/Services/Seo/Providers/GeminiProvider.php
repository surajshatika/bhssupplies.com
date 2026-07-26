<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements SeoAiProviderInterface
{
    use ResilientProviderHttp;

    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $apiKey = $this->getApiKey();
            $model = config('seo.providers.gemini.model');
            // Key goes in a header, not the URL — URLs leak into logs and
            // exception traces, query-string keys leak with them.
            $endpoint = rtrim(config('seo.providers.gemini.endpoint'), '/').'/'.$model.':generateContent';

            $response = $this->providerHttp()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => trim($systemPrompt.' '.$prompt)],
                        ],
                    ],
                ],
            ]);

            if (!$response->successful()) {
                $errMsg = data_get($response->json(), 'error.message', substr($response->body(), 0, 200));
                \Illuminate\Support\Facades\Log::warning('[SEO] GeminiProvider HTTP error', ['status' => $response->status(), 'error' => $errMsg]);
                \Illuminate\Support\Facades\Cache::put('seo:provider-last-error:gemini', ['status' => $response->status(), 'error' => $errMsg], now()->addHours(12));
                return null;
            }

            return data_get($response->json(), 'candidates.0.content.parts.0.text');
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('[SEO] GeminiProvider exception', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    public function isConfigured()
    {
        return (bool) $this->getApiKey();
    }

    public function getName()
    {
        return 'gemini';
    }

    protected function getApiKey()
    {
        $key = config('seo.providers.gemini.api_key');
        if ($key) {
            return $key;
        }

        if (function_exists('get_setting')) {
            return get_setting('seo_gemini_api_key');
        }

        return null;
    }
}
