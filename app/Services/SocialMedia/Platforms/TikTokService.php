<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class TikTokService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('tiktok_access_token'))
            && !empty(SocialAutomationSetting::get('tiktok_open_id'));
    }

    public function getSlug(): string { return 'tiktok'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token  = SocialAutomationSetting::get('tiktok_access_token');
        $openId = SocialAutomationSetting::get('tiktok_open_id');

        // TikTok Business API v2 — direct post with video required
        // For text/caption posts, use Content Posting API
        $videoUrl = $options['video_url'] ?? null;
        if (!$videoUrl) {
            return $this->fail('TikTok requires a video_url in options');
        }

        try {
            $response = Http::withToken($token)
                ->post('https://open.tiktokapis.com/v2/post/publish/video/init/', [
                    'post_info' => [
                        'title'            => mb_substr($content, 0, 2200),
                        'privacy_level'    => $options['privacy_level'] ?? 'PUBLIC_TO_EVERYONE',
                        'disable_duet'     => false,
                        'disable_comment'  => false,
                        'disable_stitch'   => false,
                    ],
                    'source_info' => [
                        'source'    => 'PULL_FROM_URL',
                        'video_url' => $videoUrl,
                    ],
                ]);

            $data = $response->json();
            if ($response->successful() && isset($data['data']['publish_id'])) {
                return ['success' => true, 'post_id' => $data['data']['publish_id'], 'post_url' => null, 'response' => json_encode($data)];
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
