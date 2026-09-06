<?php

namespace App\Services\Seo\Providers;

/**
 * Cohere. Uses the OpenAI-compatibility endpoint; also offers embeddings and reranking.
 */
class CohereProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'cohere';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.cohere.ai/compatibility/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'command-a-03-2025';
    }
}
