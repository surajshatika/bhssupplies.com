<?php

namespace App\Services\Seo\Providers;

/**
 * OpenRouter — a single gateway that fronts 300+ models from most major labs
 * behind one API key and one OpenAI-compatible endpoint.
 *
 * Useful here as an escape hatch: a model can be swapped by changing the
 * `OPENROUTER_MODEL` string alone, with no new driver, key, or deploy. That
 * makes it the cheapest way to trial a newly released model against the SEO
 * prompts before committing to a first-class integration.
 */
class OpenRouterProvider extends OpenAiCompatibleProvider
{
    public function getName(): string
    {
        return 'openrouter';
    }

    protected function defaultEndpoint(): string
    {
        return 'https://openrouter.ai/api/v1/chat/completions';
    }

    protected function defaultModel(): string
    {
        return 'anthropic/claude-sonnet-4.5';
    }

    /**
     * OpenRouter uses these for app attribution on its public leaderboards.
     * They are optional but recommended, and harmless if the site URL is local.
     */
    protected function extraHeaders(): array
    {
        return [
            'HTTP-Referer' => (string) config('seo.site_url', config('app.url', '')),
            'X-Title'      => (string) (function_exists('get_setting')
                ? get_setting('website_name', 'AI SEO Suite')
                : 'AI SEO Suite'),
        ];
    }
}
