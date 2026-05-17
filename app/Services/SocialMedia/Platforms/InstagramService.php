<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class InstagramService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('instagram_graph_api_token'))
            && !empty(SocialAutomationSetting::get('instagram_business_account_id'));
    }

    public function getSlug(): string { return 'instagram'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token     = SocialAutomationSetting::get('instagram_graph_api_token');
        $accountId = SocialAutomationSetting::get('instagram_business_account_id');
        $imageUrl  = $options['image_url'] ?? null;

        if (!$imageUrl) {
            return $this->fail('Instagram requires an image_url in options');
        }

        try {
            // Step 1: Create media container
            $container = Http::post(
                "https://graph.facebook.com/v19.0/{$accountId}/media",
                ['image_url' => $imageUrl, 'caption' => $content, 'access_token' => $token]
            )->json();

            if (!isset($container['id'])) {
                return $this->fail(json_encode($container));
            }

            // Step 2: Publish
            $publish = Http::post(
                "https://graph.facebook.com/v19.0/{$accountId}/media_publish",
                ['creation_id' => $container['id'], 'access_token' => $token]
            )->json();

            if (isset($publish['id'])) {
                return ['success' => true, 'post_id' => $publish['id'], 'post_url' => null, 'response' => json_encode($publish)];
            }
            return $this->fail(json_encode($publish));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'post_id' => null, 'post_url' => null, 'response' => $msg];
    }
}
