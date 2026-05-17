<?php

namespace App\Services\SocialMedia\AI;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAiProvider implements SocialAiProviderInterface
{
    public function generate(string $prompt, ?string $systemPrompt = null, array $options = []): ?string
    {
        if (!$this->isConfigured()) return null;

        try {
            $payload = [
                'model'      => $options['model'] ?? $this->getModel(),
                'max_tokens' => $options['max_tokens'] ?? 1500,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ];
            if ($systemPrompt) {
                $payload['system'] = $systemPrompt;
            }

            $response = Http::timeout(60)
                ->withOptions(['verify' => config('social_media.ssl_verify', true)])
                ->withHeaders([
                    'x-api-key'         => $this->getApiKey(),
                    'anthropic-version' => '2023-06-01',
                ])
                ->post(config('social_media.ai_providers.claude.endpoint'), $payload);

            if (!$response->successful()) {
                Log::warning('Claude API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'model'  => $options['model'] ?? $this->getModel(),
                ]);
                return null;
            }

            return data_get($response->json(), 'content.0.text');
        } catch (\Throwable $e) {
            Log::warning('Claude provider exception: ' . $e->getMessage());
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    public function getName(): string { return 'claude'; }
    public function getLabel(): string { return 'Claude (Anthropic)'; }

    protected function getApiKey(): ?string
    {
        $key = config('social_media.ai_providers.claude.api_key');
        if ($key) return $key;
        // Use ?: not ?? so empty-string values fall through to the next option
        return SocialAutomationSetting::get('social_ai_claude_key')
            ?: get_setting('seo_anthropic_api_key'); // reuse SEO suite key
    }

    protected function getModel(): string
    {
        return SocialAutomationSetting::get('social_ai_claude_model')
            ?? config('social_media.ai_providers.claude.model', 'claude-sonnet-4-6');
    }
}
