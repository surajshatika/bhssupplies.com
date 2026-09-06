<?php

namespace App\Services\Seo\Providers;

/**
 * Grok (xAI). Real-time-leaning model with X/Twitter data access.
 */
class GrokProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'grok';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.x.ai/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'grok-3-mini';
    }
}
