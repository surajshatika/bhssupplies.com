<?php

namespace App\Services\SocialMedia\AI;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class GrokProvider implements SocialAiProviderInterface
{
    public function generate(string $prompt, ?string $systemPrompt = null, array $options = []): ?string
    {
        if (!$this->isConfigured()) return null;

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            // xAI Grok uses OpenAI-compatible API format
            $response = Http::timeout(60)
                ->withOptions(['verify' => config('social_media.ssl_verify', true)])
                ->withToken($this->getApiKey())
                ->post(config('social_media.ai_providers.grok.endpoint'), [
                    'model'       => $options['model'] ?? $this->getModel(),
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens'  => $options['max_tokens'] ?? 1500,
                    'messages'    => $messages,
                ]);

            if (!$response->successful()) return null;

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable) {
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    public function getName(): string { return 'grok'; }
    public function getLabel(): string { return 'Grok (xAI)'; }

    protected function getApiKey(): ?string
    {
        $key = config('social_media.ai_providers.grok.api_key');
        if ($key) return $key;
        // Use ?: so empty-string falls through to the SEO suite key
        return SocialAutomationSetting::get('social_ai_grok_key')
            ?: get_setting('seo_grok_api_key');
    }

    protected function getModel(): string
    {
        return SocialAutomationSetting::get('social_ai_grok_model')
            ?? config('social_media.ai_providers.grok.model', 'grok-beta');
    }
}
