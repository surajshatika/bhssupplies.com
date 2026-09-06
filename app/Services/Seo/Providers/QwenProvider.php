<?php

namespace App\Services\Seo\Providers;

/**
 * Qwen (Alibaba DashScope). Strong multilingual coverage — useful for non-English SEO.
 */
class QwenProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'qwen';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'qwen-plus';
    }
}
