<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class LinkedInService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('linkedin_access_token'))
            && !empty(SocialAutomationSetting::get('linkedin_organization_urn'));
    }

    public function getSlug(): string { return 'linkedin'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token = SocialAutomationSetting::get('linkedin_access_token');
        $urn   = SocialAutomationSetting::get('linkedin_organization_urn');

        $body = [
            'author'          => $urn,
            'lifecycleState'  => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary'    => ['text' => mb_substr($content, 0, 3000)],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post('https://api.linkedin.com/v2/ugcPosts', $body);

            $data   = $response->json();
            $postId = $response->header('x-restli-id') ?? ($data['id'] ?? null);

            if ($response->successful()) {
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
