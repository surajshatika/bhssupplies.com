<?php

namespace App\Services\SocialMedia\Platforms;

interface SocialPlatformInterface
{
    /** @return array{success: bool, post_id: string|null, post_url: string|null, response: string} */
    public function post(string $content, array $options = []): array;
    public function isConfigured(): bool;
    public function getSlug(): string;
}
