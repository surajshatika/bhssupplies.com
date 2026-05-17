<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class TwitterService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('twitter_api_key'))
            && !empty(SocialAutomationSetting::get('twitter_access_token'));
    }

    public function getSlug(): string { return 'twitter'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return $this->fail('Not configured');
        }

        $apiKey            = SocialAutomationSetting::get('twitter_api_key');
        $apiSecret         = SocialAutomationSetting::get('twitter_api_secret');
        $accessToken       = SocialAutomationSetting::get('twitter_access_token');
        $accessTokenSecret = SocialAutomationSetting::get('twitter_access_token_secret');

        $url  = 'https://api.twitter.com/2/tweets';
        $text = mb_substr($content, 0, 280);

        try {
            $oauth    = $this->buildOAuth('POST', $url, $apiKey, $apiSecret, $accessToken, $accessTokenSecret);
            $response = Http::withHeaders([
                'Authorization' => $oauth,
                'Content-Type'  => 'application/json',
            ])->post($url, ['text' => $text]);

            $data = $response->json();
            if ($response->successful() && isset($data['data']['id'])) {
                $postId  = $data['data']['id'];
                $postUrl = "https://twitter.com/i/web/status/{$postId}";
                return ['success' => true, 'post_id' => $postId, 'post_url' => $postUrl, 'response' => json_encode($data)];
            }
            return $this->fail(json_encode($data));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function buildOAuth(string $method, string $url, string $key, string $secret, string $token, string $tokenSecret): string
    {
        $nonce     = bin2hex(random_bytes(16));
        $timestamp = time();
        $params    = [
            'oauth_consumer_key'     => $key,
            'oauth_nonce'            => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => $timestamp,
            'oauth_token'            => $token,
            'oauth_version'          => '1.0',
        ];
        ksort($params);
        $base = strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode(http_build_query($params));
        $key  = rawurlencode($secret).'&'.rawurlencode($tokenSecret);
        $sig  = base64_encode(hash_hmac('sha1', $base, $key, true));
        $params['oauth_signature'] = $sig;
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = rawurlencode($k).'="'.rawurlencode($v).'"';
        }
        return 'OAuth '.implode(', ', $parts);
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'post_id' => null, 'post_url' => null, 'response' => $msg];
    }
}
