<?php

namespace App\Services\Seo\Providers;

/**
 * Groq. LPU inference at very high tokens/sec — best latency for interactive and streaming use. NOTE: distinct from xAI's Grok despite the near-identical name.
 */
class GroqProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'groq';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://api.groq.com/openai/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'llama-3.3-70b-versatile';
    }
}
