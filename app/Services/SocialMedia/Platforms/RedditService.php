<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class RedditService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('reddit_access_token'))
            && !empty(SocialAutomationSetting::get('reddit_subreddit'));
    }

    public function getSlug(): string { return 'reddit'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token     = SocialAutomationSetting::get('reddit_access_token');
        $subreddit = ltrim(SocialAutomationSetting::get('reddit_subreddit'), 'r/');
        $title     = $options['title'] ?? mb_substr($content, 0, 300);
        $kind      = $options['kind'] ?? 'self'; // self | link

        try {
            $payload = [
                'sr'      => $subreddit,
                'kind'    => $kind,
                'title'   => $title,
                'nsfw'    => false,
                'spoiler' => false,
                'resubmit'=> true,
            ];

            if ($kind === 'self') {
                $payload['text'] = $content;
            } elseif ($kind === 'link') {
                $payload['url'] = $options['link'] ?? url('/');
            }

            $response = Http::withToken($token)
                ->withUserAgent('BHSSupplies Social Agent/1.0')
                ->post('https://oauth.reddit.com/api/submit', $payload);

            $data = $response->json();
            $postUrl = data_get($data, 'jquery.10.3.0') ?? null;
            $postId  = data_get($data, 'jquery.10.3.0') ?? null;

            if ($response->successful() && !isset($data['json']['errors'][0])) {
                return ['success' => true, 'post_id' => $postId, 'post_url' => $postUrl, 'response' => json_encode($data)];
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
