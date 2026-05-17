<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class DiscordService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('discord_webhook_url'));
    }

    public function getSlug(): string { return 'discord'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $webhookUrl = SocialAutomationSetting::get('discord_webhook_url');

        try {
            $payload = [
                'content'  => mb_substr($content, 0, 2000),
                'username' => $options['username'] ?? get_setting('website_name', 'Bot'),
            ];

            if (!empty($options['embeds'])) {
                $payload['embeds'] = $options['embeds'];
            } elseif (!empty($options['title']) || !empty($options['image_url'])) {
                $embed = [];
                if (!empty($options['title'])) $embed['title'] = $options['title'];
                if (!empty($options['description'])) $embed['description'] = $options['description'];
                if (!empty($options['image_url'])) $embed['image'] = ['url' => $options['image_url']];
                if (!empty($options['color'])) $embed['color'] = $options['color'];
                $embed['timestamp'] = now()->toIso8601String();
                $payload['embeds'] = [$embed];
            }

            $response = Http::post($webhookUrl, $payload);

            if ($response->status() === 204 || $response->successful()) {
                $data = $response->body() ? $response->json() : [];
                $postId = $data['id'] ?? null;
                return ['success' => true, 'post_id' => $postId, 'post_url' => null, 'response' => json_encode($data)];
            }
            return $this->fail($response->body());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'post_id' => null, 'post_url' => null, 'response' => $msg];
    }
}
