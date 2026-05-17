<?php

namespace App\Services\SocialMedia\AI;

interface SocialAiProviderInterface
{
    public function generate(string $prompt, ?string $systemPrompt = null, array $options = []): ?string;
    public function isConfigured(): bool;
    public function getName(): string;
    public function getLabel(): string;
}
