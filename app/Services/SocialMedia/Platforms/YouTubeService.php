<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class YouTubeService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('youtube_oauth_token'))
            && !empty(SocialAutomationSetting::get('youtube_channel_id'));
    }

    public function getSlug(): string { return 'youtube'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token = SocialAutomationSetting::get('youtube_oauth_token');

        try {
            $response = Http::withToken($token)
                ->post('https://www.googleapis.com/youtube/v3/activities?part=snippet,contentDetails', [
                    'snippet'        => ['description' => $content],
                    'contentDetails' => ['bulletin' => ['resourceId' => []]],
                ]);

            $data = $response->json();
            if ($response->successful() && isset($data['id'])) {
                return ['success' => true, 'post_id' => $data['id'], 'post_url' => null, 'response' => json_encode($data)];
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
