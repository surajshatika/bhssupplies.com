<?php

namespace App\Services\Seo\Providers;

/**
 * DeepSeek. Very low cost per token — the value pick for high-volume bulk SEO passes.
 */
class DeepSeekProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'deepseek';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.deepseek.com/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'deepseek-chat';
    }
}
