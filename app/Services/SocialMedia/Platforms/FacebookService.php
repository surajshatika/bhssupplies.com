<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class FacebookService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('facebook_page_access_token'))
            && !empty(SocialAutomationSetting::get('facebook_page_id'));
    }

    public function getSlug(): string { return 'facebook'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token  = SocialAutomationSetting::get('facebook_page_access_token');
        $pageId = SocialAutomationSetting::get('facebook_page_id');

        try {
            $payload = [
                'message'      => $content,
                'access_token' => $token,
            ];
            if (!empty($options['link'])) {
                $payload['link'] = $options['link'];
            }

            $response = Http::post("https://graph.facebook.com/v19.0/{$pageId}/feed", $payload);
            $data = $response->json();

            if ($response->successful() && isset($data['id'])) {
                $postUrl = "https://facebook.com/{$data['id']}";
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
