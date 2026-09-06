<?php

namespace App\Services\Seo\Providers;

/**
 * Fireworks AI. Fast hosted open-weight models.
 */
class FireworksProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'fireworks';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.fireworks.ai/inference/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'accounts/fireworks/models/llama-v3p3-70b-instruct';
    }
}
