<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class SnapchatService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('snapchat_access_token'))
            && !empty(SocialAutomationSetting::get('snapchat_ad_account_id'));
    }

    public function getSlug(): string { return 'snapchat'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token     = SocialAutomationSetting::get('snapchat_access_token');
        $accountId = SocialAutomationSetting::get('snapchat_ad_account_id');
        $mediaUrl  = $options['media_url'] ?? null;

        if (!$mediaUrl) {
            return $this->fail('Snapchat Business requires a media_url in options');
        }

        try {
            // Snapchat Marketing API — story ad creation
            $response = Http::withToken($token)
                ->post("https://adsapi.snapchat.com/v1/adaccounts/{$accountId}/creatives", [
                    'creatives' => [[
                        'ad_account_id' => $accountId,
                        'name'          => mb_substr($content, 0, 250),
                        'type'          => 'SNAP_AD',
                        'top_snap_media_id' => $mediaUrl,
                        'brand_name'    => $options['brand_name'] ?? get_setting('website_name', 'Brand'),
                        'headline'      => mb_substr($content, 0, 34),
                    ]],
                ]);

            $data = $response->json();
            $postId = data_get($data, 'creatives.0.creative.id');
            if ($response->successful() && $postId) {
                return ['success' => true, 'post_id' => $postId, 'post_url' => null, 'response' => json_encode($data)];
            }
            return $this->fail(json_encode($data));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'post_id' => null, 'post_url' => null, 'response' => $msg];
    }
}
