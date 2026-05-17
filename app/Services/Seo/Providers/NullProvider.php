<?php

namespace App\Services\Seo\Providers;

class NullProvider implements SeoAiProviderInterface
{
    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        return null;
    }

    public function isConfigured()
    {
        return false;
    }

    public function getName()
    {
        return 'fallback';
    }
}
