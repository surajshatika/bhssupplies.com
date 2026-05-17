<?php

namespace App\Services\Seo\Providers;

class SeoProviderManager
{
    public static function make($name = null)
    {
        $name = strtolower($name ?: config('seo.default_provider', 'openai'));

        switch ($name) {
            case 'claude':
            case 'anthropic':
                return new ClaudeProvider();
            case 'gemini':
            case 'google':
                return new GeminiProvider();
            case 'openai':
            case 'chatgpt':
                return new OpenAIProvider();
            case 'grok':
            case 'xai':
                return new GrokProvider();
            default:
                return new NullProvider();
        }
    }
}
