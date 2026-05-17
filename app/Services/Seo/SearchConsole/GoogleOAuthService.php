<?php

namespace App\Services\Seo\SearchConsole;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stateless OAuth2 web-flow helper for Google APIs.
 *
 * Flow:
 *   1. authUrl()                — admin clicks "Connect Google"
 *   2. exchangeCodeForTokens()  — callback receives ?code, we exchange it
 *   3. saveTokens()             — refresh_token + the user's email get stored
 *
 * Scopes are configurable. Defaults to webmasters.readonly so the same OAuth
 * flow can be extended to also cover PageSpeed Insights, Analytics, etc.
 */
class GoogleOAuthService
{
    public const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    public const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    /** Default scopes — admins can override via seo_gsc_scopes setting. */
    public const DEFAULT_SCOPES = [
        'https://www.googleapis.com/auth/webmasters.readonly',
        'openid',
        'email',
    ];

    public function isConfigured(): bool
    {
        return $this->clientId() && $this->clientSecret();
    }

    public function isConnected(): bool
    {
        return (bool) $this->refreshToken();
    }

    public function connectedEmail(): ?string
    {
        return get_setting('seo_gsc_connected_email') ?: null;
    }

    public function clientId(): ?string
    {
        return get_setting('seo_gsc_client_id') ?: env('SEO_GSC_CLIENT_ID');
    }

    public function clientSecret(): ?string
    {
        return get_setting('seo_gsc_client_secret') ?: env('SEO_GSC_CLIENT_SECRET');
    }

    public function refreshToken(): ?string
    {
        return get_setting('seo_gsc_refresh_token') ?: env('SEO_GSC_REFRESH_TOKEN');
    }

    public function redirectUri(): string
    {
        return rtrim(url('/'), '/') . '/admin/seo-suite/oauth/google/callback';
    }

    /**
     * Build the consent URL and stash a CSRF state in session.
     */
    public function authUrl(): string
    {
        $state = Str::random(40);
        session(['seo_gsc_oauth_state' => $state]);

        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => $this->clientId(),
            'redirect_uri'  => $this->redirectUri(),
            'response_type' => 'code',
            'scope'         => implode(' ', $this->scopes()),
            'access_type'   => 'offline',           // ensures a refresh_token
            'prompt'        => 'consent',           // forces a fresh refresh_token even on re-auth
            'state'         => $state,
            'include_granted_scopes' => 'true',
        ]);
    }

    /**
     * @return array{success:bool, refresh_token:?string, access_token:?string, email:?string, error:?string}
     */
    public function exchangeCodeForTokens(string $code): array
    {
        if (!$this->isConfigured()) {
            return $this->fail('OAuth client_id / client_secret not configured.');
        }

        try {
            $resp = Http::asForm()
                ->timeout(30)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->post(self::TOKEN_URL, [
                    'code'          => $code,
                    'client_id'     => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'redirect_uri'  => $this->redirectUri(),
                    'grant_type'    => 'authorization_code',
                ]);

            if (!$resp->successful()) {
                return $this->fail('Token exchange HTTP ' . $resp->status() . ': ' . $resp->body());
            }

            $j = $resp->json();
            $refresh = $j['refresh_token'] ?? null;
            $access  = $j['access_token']  ?? null;

            // Optional: fetch the connected account's email for display in UI.
            $email = null;
            if ($access) {
                try {
                    $u = Http::withToken($access)
                        ->timeout(15)
                        ->withOptions(['verify' => config('seo.ssl_verify', true)])
                        ->get(self::USERINFO_URL);
                    if ($u->successful()) {
                        $email = $u->json('email');
                    }
                } catch (Throwable $e) {
                    // non-fatal
                }
            }

            return [
                'success'       => $refresh !== null,
                'refresh_token' => $refresh,
                'access_token'  => $access,
                'email'         => $email,
                'error'         => $refresh ? null : 'No refresh_token returned (re-consent may be required).',
            ];
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Verify the OAuth state echoed back by Google against the session value.
     */
    public function verifyState(?string $incoming): bool
    {
        $expected = session('seo_gsc_oauth_state');
        session()->forget('seo_gsc_oauth_state');
        return $incoming && $expected && hash_equals((string) $expected, $incoming);
    }

    /**
     * List the verified Search Console properties for the connected account.
     * Used by the settings UI to pre-fill the site URL dropdown.
     */
    public function listSites(string $accessToken): array
    {
        try {
            $resp = Http::withToken($accessToken)
                ->timeout(20)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->get('https://searchconsole.googleapis.com/webmasters/v3/sites');

            if (!$resp->successful()) {
                return [];
            }

            return collect($resp->json('siteEntry') ?? [])
                ->pluck('siteUrl')
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function scopes(): array
    {
        $raw = get_setting('seo_gsc_scopes');
        if (is_string($raw) && trim($raw) !== '') {
            $parts = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw))));
            if (!empty($parts)) {
                return $parts;
            }
        }
        return self::DEFAULT_SCOPES;
    }

    protected function fail(string $msg): array
    {
        return [
            'success'       => false,
            'refresh_token' => null,
            'access_token'  => null,
            'email'         => null,
            'error'         => $msg,
        ];
    }
}
