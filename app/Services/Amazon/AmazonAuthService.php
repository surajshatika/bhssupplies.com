<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use App\Models\AmazonToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AmazonAuthService
{
    private const LWA_TOKEN_URL = 'https://api.amazon.com/auth/o2/token';
    private const TOKEN_CACHE_TTL = 3500; // seconds (just under 1 hour)

    public function getAccessToken(AmazonAccount $account): string
    {
        $cacheKey = "amazon_access_token_{$account->id}";

        return Cache::remember($cacheKey, self::TOKEN_CACHE_TTL, function () use ($account) {
            return $this->refreshAccessToken($account);
        });
    }

    private function refreshAccessToken(AmazonAccount $account): string
    {
        $token = AmazonToken::where('account_id', $account->id)->firstOrFail();

        $response = Http::asForm()->post(self::LWA_TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_id'     => $account->lwa_client_id,
            'client_secret' => $account->lwa_client_secret,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Amazon LWA token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $token->update([
            'access_token' => $data['access_token'],
            'expires_at'   => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data['access_token'];
    }

    public function getDefaultAccount(): ?AmazonAccount
    {
        return AmazonAccount::where('is_active', 1)->first();
    }
}
