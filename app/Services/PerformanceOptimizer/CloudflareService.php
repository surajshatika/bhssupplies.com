<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Illuminate\Support\Facades\Http;
use Throwable;

class CloudflareService
{
    protected string $apiBase = 'https://api.cloudflare.com/client/v4';

    public function isConfigured(): bool
    {
        return (int) get_setting('perf_cloudflare_status', 0) === 1
            && trim((string) get_setting('perf_cloudflare_zone_id', '')) !== ''
            && trim((string) get_setting('perf_cloudflare_api_token', '')) !== '';
    }

    public function test(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Cloudflare is not enabled or missing Zone ID / API Token'];
        }
        try {
            $zone = (string) get_setting('perf_cloudflare_zone_id');
            $r = $this->client()->get("/zones/{$zone}");
            if ($r->successful()) {
                $this->log('test', $r, true);
                return [
                    'ok'     => true,
                    'name'   => $r->json('result.name'),
                    'status' => $r->json('result.status'),
                    'plan'   => $r->json('result.plan.name'),
                ];
            }
            $this->log('test', $r, false);
            return ['ok' => false, 'error' => $r->json('errors.0.message', 'Cloudflare returned ' . $r->status())];
        } catch (Throwable $e) {
            $this->logException('test', $e);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function purgeAll(): array
    {
        if (!$this->isConfigured()) return ['ok' => false, 'error' => 'Not configured'];
        try {
            $zone = (string) get_setting('perf_cloudflare_zone_id');
            $r = $this->client()->post("/zones/{$zone}/purge_cache", ['purge_everything' => true]);
            $this->log('purge_all', $r, $r->successful());
            return ['ok' => $r->successful(), 'response' => $r->json()];
        } catch (Throwable $e) {
            $this->logException('purge_all', $e);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function purgeUrls(array $urls): array
    {
        if (!$this->isConfigured() || empty($urls)) return ['ok' => false, 'error' => 'Not configured or no URLs'];
        try {
            $zone = (string) get_setting('perf_cloudflare_zone_id');
            $r = $this->client()->post("/zones/{$zone}/purge_cache", ['files' => array_values($urls)]);
            $this->log('purge_urls', $r, $r->successful(), ['urls' => $urls]);
            return ['ok' => $r->successful(), 'response' => $r->json()];
        } catch (Throwable $e) {
            $this->logException('purge_urls', $e, ['urls' => $urls]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function client()
    {
        return Http::baseUrl($this->apiBase)
            ->withToken((string) get_setting('perf_cloudflare_api_token'))
            ->acceptJson()
            ->timeout(15);
    }

    protected function log(string $action, $response, bool $ok, array $meta = []): void
    {
        try {
            OptimizationLog::create([
                'type'   => 'cloudflare',
                'action' => $action,
                'status' => $ok ? 'success' : 'failed',
                'meta'   => array_merge($meta, ['status_code' => $response->status()]),
                'error_message' => $ok ? null : substr((string) $response->body(), 0, 1000),
            ]);
        } catch (Throwable $e) { /* swallow logging errors */ }
    }

    protected function logException(string $action, Throwable $e, array $meta = []): void
    {
        try {
            OptimizationLog::create([
                'type'   => 'cloudflare',
                'action' => $action,
                'status' => 'failed',
                'meta'   => $meta,
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);
        } catch (Throwable $ig) {}
    }
}
