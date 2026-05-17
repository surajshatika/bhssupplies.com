<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class PinterestService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('pinterest_access_token'))
            && !empty(SocialAutomationSetting::get('pinterest_board_id'));
    }

    public function getSlug(): string { return 'pinterest'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token   = SocialAutomationSetting::get('pinterest_access_token');
        $boardId = SocialAutomationSetting::get('pinterest_board_id');
        $imageUrl = $options['image_url'] ?? null;

        if (!$imageUrl) {
            return $this->fail('Pinterest requires an image_url in options');
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('https://api.pinterest.com/v5/pins', [
                    'board_id'    => $boardId,
                    'description' => mb_substr($content, 0, 500),
                    'link'        => $options['link'] ?? null,
                    'media_source' => [
                        'source_type' => 'image_url',
                        'url'         => $imageUrl,
                    ],
                    'title' => $options['title'] ?? mb_substr($content, 0, 100),
                ]);

            $data = $response->json();
            if ($response->successful() && isset($data['id'])) {
                $postUrl = "https://pinterest.com/pin/{$data['id']}";
                return ['success' => true, 'post_id' => $data['id'], 'post_url' => $postUrl, 'response' => json_encode($data)];
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
