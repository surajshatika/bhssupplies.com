<?php

namespace App\Services\Seo\Providers;

interface SeoAiProviderInterface
{
    public function generate($prompt, $systemPrompt = null, array $options = []);

    public function isConfigured();

    public function getName();
}
