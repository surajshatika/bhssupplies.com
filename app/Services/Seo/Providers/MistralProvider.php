<?php

namespace App\Services\Seo\Providers;

/**
 * Mistral AI. European-hosted; also provides embeddings for keyword clustering.
 */
class MistralProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'mistral';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.mistral.ai/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'mistral-small-latest';
    }
}
