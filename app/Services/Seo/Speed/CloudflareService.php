<?php

namespace App\Services\Seo\Speed;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Minimal Cloudflare API v4 connector — exposes the operations the SEO suite
 * actually needs (purge cache, dev-mode toggle, zone summary). Credentials are
 * read from business_settings:
 *   - seo_cloudflare_api_token  (preferred — scoped token)
 *   - seo_cloudflare_zone_id
 *
 * Each public method returns ['success' => bool, 'data' => …, 'error' => …]
 * so callers don't have to wrap Http exceptions themselves.
 */
class CloudflareService
{
    protected const BASE = 'https://api.cloudflare.com/client/v4';

    public function isConfigured(): bool
    {
        return !empty($this->token()) && !empty($this->zoneId());
    }

    public function token(): ?string
    {
        return get_setting('seo_cloudflare_api_token') ?: env('SEO_CLOUDFLARE_API_TOKEN');
    }

    public function zoneId(): ?string
    {
        return get_setting('seo_cloudflare_zone_id') ?: env('SEO_CLOUDFLARE_ZONE_ID');
    }

    public function purgeEverything(): array
    {
        return $this->call('POST', "/zones/{$this->zoneId()}/purge_cache", ['purge_everything' => true]);
    }

    /**
     * Purge specific URLs. Cloudflare accepts up to 30 URLs per call on the
     * free plan — we chunk and report aggregate success.
     */
    public function purgeUrls(array $urls): array
    {
        $urls = array_values(array_unique(array_filter($urls)));
        if (empty($urls)) {
            return ['success' => true, 'data' => ['purged' => 0]];
        }

        $purged = 0;
        $errors = [];

        foreach (array_chunk($urls, 30) as $chunk) {
            $res = $this->call('POST', "/zones/{$this->zoneId()}/purge_cache", ['files' => $chunk]);
            if ($res['success']) {
                $purged += count($chunk);
            } else {
                $errors[] = $res['error'] ?? 'unknown error';
            }
        }

        return [
            'success' => empty($errors),
            'data'    => ['purged' => $purged, 'requested' => count($urls)],
            'error'   => $errors ? implode('; ', $errors) : null,
        ];
    }

    public function setDevelopmentMode(bool $on): array
    {
        return $this->call('PATCH', "/zones/{$this->zoneId()}/settings/development_mode", [
            'value' => $on ? 'on' : 'off',
        ]);
    }

    public function zoneSummary(): array
    {
        $res = $this->call('GET', "/zones/{$this->zoneId()}");
        if (!$res['success']) {
            return $res;
        }
        $z = $res['data'] ?? [];
        return [
            'success' => true,
            'data'    => [
                'name'        => $z['name']        ?? null,
                'status'      => $z['status']      ?? null,
                'plan'        => $z['plan']['name'] ?? null,
                'name_servers'=> $z['name_servers']?? [],
                'development_mode' => $z['development_mode'] ?? null,
            ],
        ];
    }

    protected function call(string $method, string $path, array $body = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloudflare API token and zone ID not configured.'];
        }

        try {
            $req = Http::timeout(30)
                ->withToken($this->token())
                ->acceptJson()
                ->withOptions(['verify' => config('seo.ssl_verify', true)]);

            $resp = match (strtoupper($method)) {
                'GET'    => $req->get(self::BASE . $path, $body),
                'POST'   => $req->post(self::BASE . $path, $body),
                'PATCH'  => $req->patch(self::BASE . $path, $body),
                'DELETE' => $req->delete(self::BASE . $path, $body),
                default  => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            $json = $resp->json();
            if ($resp->successful() && ($json['success'] ?? false)) {
                return ['success' => true, 'data' => $json['result'] ?? null];
            }

            $errors = $json['errors'] ?? [];
            $msg = empty($errors)
                ? "HTTP {$resp->status()}"
                : implode('; ', array_map(fn($e) => $e['message'] ?? json_encode($e), $errors));

            return ['success' => false, 'error' => $msg, 'data' => $json];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
