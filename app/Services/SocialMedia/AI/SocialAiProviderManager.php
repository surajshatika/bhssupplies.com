<?php

namespace App\Services\SocialMedia\AI;

use App\Models\SocialAutomationSetting;

class SocialAiProviderManager
{
    public static function make(?string $name = null): SocialAiProviderInterface
    {
        $name = strtolower($name ?: SocialAutomationSetting::get(
            'social_default_ai_provider',
            config('social_media.default_ai_provider', 'openai')
        ));

        return match ($name) {
            'claude', 'anthropic' => new ClaudeAiProvider(),
            'grok', 'xai'         => new GrokProvider(),
            default               => new ChatGPTProvider(),
        };
    }

    public static function providers(): array
    {
        return [
            'openai' => ['label' => 'ChatGPT (OpenAI)', 'provider' => new ChatGPTProvider()],
            'claude' => ['label' => 'Claude (Anthropic)', 'provider' => new ClaudeAiProvider()],
            'grok'   => ['label' => 'Grok (xAI)',        'provider' => new GrokProvider()],
        ];
    }

    public static function availableProviders(): array
    {
        return collect(static::providers())
            ->mapWithKeys(fn($p, $key) => [$key => $p['label']])
            ->toArray();
    }
}
