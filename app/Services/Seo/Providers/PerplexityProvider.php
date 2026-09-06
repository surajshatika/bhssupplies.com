<?php

namespace App\Services\Seo\Providers;

/**
 * Perplexity. Answers are grounded in a live web search, which makes it the strongest pick for competitor and gap research.
 */
class PerplexityProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'perplexity';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.perplexity.ai/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'sonar-pro';
    }
}
