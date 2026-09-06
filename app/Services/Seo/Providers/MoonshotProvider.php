<?php

namespace App\Services\Seo\Providers;

/**
 * Moonshot (Kimi). Long-context model, suited to whole-page and multi-document analysis.
 */
class MoonshotProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'moonshot';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.moonshot.ai/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'kimi-k2-0905-preview';
    }
}
