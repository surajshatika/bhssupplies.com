<?php

namespace App\Services\Seo\SearchConsole;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Search Console connector.
 *
 * Authentication model:
 *   The admin generates a refresh_token through Google's OAuth Playground
 *   (https://developers.google.com/oauthplayground) with the
 *   webmasters.readonly scope, then pastes it (plus client_id/client_secret
 *   from a Google Cloud project) into the SEO settings:
 *     - seo_gsc_client_id
 *     - seo_gsc_client_secret
 *     - seo_gsc_refresh_token
 *     - seo_search_console_site   (e.g. https://www.bhssupplies.com/)
 *
 *   This service exchanges the refresh_token for a short-lived access token
 *   and caches it for the response TTL. Building a full OAuth UI is out of
 *   scope for this phase — refresh_token paste is the lowest-friction path
 *   that works on shared hosting.
 */
class GoogleSearchConsoleService
{
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    protected const API_BASE  = 'https://searchconsole.googleapis.com/webmasters/v3';
    protected const TOKEN_CACHE_KEY = 'seo:gsc:access_token';

    public function isConfigured(): bool
    {
        return (bool) ($this->clientId() && $this->clientSecret() && $this->refreshToken() && $this->siteUrl());
    }

    public function siteUrl(): ?string
    {
        return get_setting('seo_search_console_site') ?: config('seo.search_console.site_url');
    }

    /**
     * Fetch performance rows for a date range.
     *
     * @param string $startDate Y-m-d
     * @param string $endDate   Y-m-d
     * @param array<string>  $dimensions  e.g. ['query'], ['page'], ['date']
     * @param int    $rowLimit
     * @return array{success:bool, rows:array, error:?string}
     */
    public function fetchPerformance(string $startDate, string $endDate, array $dimensions = ['query'], int $rowLimit = 1000): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'rows' => [], 'error' => 'Search Console credentials not configured.'];
        }

        $token = $this->accessToken();
        if (!$token) {
            return ['success' => false, 'rows' => [], 'error' => 'Could not obtain access token (check refresh_token + client credentials).'];
        }

        $site = $this->siteUrl();
        $endpoint = self::API_BASE . '/sites/' . rawurlencode($site) . '/searchAnalytics/query';

        try {
            $resp = Http::withToken($token)
                ->timeout(60)
                ->acceptJson()
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->post($endpoint, [
                    'startDate'  => $startDate,
                    'endDate'    => $endDate,
                    'dimensions' => $dimensions,
                    'rowLimit'   => max(1, min($rowLimit, 25000)),
                ]);

            if (!$resp->successful()) {
                return ['success' => false, 'rows' => [], 'error' => 'GSC HTTP ' . $resp->status() . ': ' . $resp->body()];
            }

            $rows = (array) ($resp->json('rows') ?? []);
            return ['success' => true, 'rows' => $rows, 'error' => null];
        } catch (Throwable $e) {
            return ['success' => false, 'rows' => [], 'error' => $e->getMessage()];
        }
    }

    protected function accessToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        try {
            $resp = Http::asForm()
                ->timeout(30)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->post(self::TOKEN_URL, [
                    'client_id'     => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'refresh_token' => $this->refreshToken(),
                    'grant_type'    => 'refresh_token',
                ]);

            if (!$resp->successful()) {
                logger()->warning('GSC token refresh failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                return null;
            }

            $token   = $resp->json('access_token');
            $expires = (int) ($resp->json('expires_in') ?? 3600);

            if (!$token) {
                return null;
            }

            Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, $expires - 60));
            return $token;
        } catch (Throwable $e) {
            logger()->warning('GSC token exchange exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function clientId(): ?string
    {
        return get_setting('seo_gsc_client_id') ?: env('SEO_GSC_CLIENT_ID');
    }

    protected function clientSecret(): ?string
    {
        return get_setting('seo_gsc_client_secret') ?: env('SEO_GSC_CLIENT_SECRET');
    }

    protected function refreshToken(): ?string
    {
        return get_setting('seo_gsc_refresh_token') ?: env('SEO_GSC_REFRESH_TOKEN');
    }
}
