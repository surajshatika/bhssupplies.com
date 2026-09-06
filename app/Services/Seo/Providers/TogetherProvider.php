<?php

namespace App\Services\Seo\Providers;

/**
 * Together AI. Hosted open-weight models; cheap for bulk generation.
 */
class TogetherProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'together';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.together.xyz/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'meta-llama/Llama-3.3-70B-Instruct-Turbo';
    }
}
