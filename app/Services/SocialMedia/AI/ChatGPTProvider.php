<?php

namespace App\Services\SocialMedia\AI;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTProvider implements SocialAiProviderInterface
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
            $response = Http::timeout(60)
                ->withOptions(['verify' => config('social_media.ssl_verify', true)])
                ->withToken($this->getApiKey())
                ->post(config('social_media.ai_providers.openai.endpoint'), [
                    'model'       => $options['model'] ?? $this->getModel(),
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens'  => $options['max_tokens'] ?? 1500,
                    'messages'    => $messages,
                ]);

            if (!$response->successful()) {
                Log::warning('ChatGPT API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'model'  => $options['model'] ?? $this->getModel(),
                ]);
                return null;
            }

            return data_get($response->json(), 'choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning('ChatGPT provider exception: ' . $e->getMessage());
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    public function getName(): string { return 'openai'; }
    public function getLabel(): string { return 'ChatGPT (OpenAI)'; }

    protected function getApiKey(): ?string
    {
        $key = config('social_media.ai_providers.openai.api_key');
        if ($key) return $key;
        return SocialAutomationSetting::get('social_ai_openai_key')
            ?? get_setting('seo_openai_api_key'); // reuse SEO suite key if set
    }

    protected function getModel(): string
    {
        return SocialAutomationSetting::get('social_ai_openai_model')
            ?? config('social_media.ai_providers.openai.model', 'gpt-4o');
    }
}
