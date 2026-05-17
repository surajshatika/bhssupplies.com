<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Illuminate\Support\Facades\Http;
use Throwable;

class BunnyCdnService
{
    protected string $apiBase = 'https://api.bunny.net';

    public function isConfigured(): bool
    {
        return (int) get_setting('perf_bunny_status', 0) === 1
            && trim((string) get_setting('perf_bunny_pull_zone', '')) !== ''
            && trim((string) get_setting('perf_bunny_api_key', '')) !== '';
    }

    public function test(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bunny.net is not enabled or missing Pull Zone ID / API Key'];
        }
        try {
            $zone = (string) get_setting('perf_bunny_pull_zone');
            $r = $this->client()->get("/pullzone/{$zone}");
            if ($r->successful()) {
                $this->log('test', $r, true);
                return [
                    'ok'        => true,
                    'name'      => $r->json('Name'),
                    'hostname'  => $r->json('Hostnames.0.Value'),
                    'enabled'   => $r->json('Enabled'),
                ];
            }
            $this->log('test', $r, false);
            return ['ok' => false, 'error' => 'Bunny returned ' . $r->status() . ' (' . substr((string) $r->body(), 0, 200) . ')'];
        } catch (Throwable $e) {
            $this->logException('test', $e);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function purgeAll(): array
    {
        if (!$this->isConfigured()) return ['ok' => false, 'error' => 'Not configured'];
        try {
            $zone = (string) get_setting('perf_bunny_pull_zone');
            $r = $this->client()->post("/pullzone/{$zone}/purgeCache");
            $this->log('purge_all', $r, $r->successful());
            return ['ok' => $r->successful()];
        } catch (Throwable $e) {
            $this->logException('purge_all', $e);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function purgeUrl(string $url): array
    {
        if (!$this->isConfigured()) return ['ok' => false, 'error' => 'Not configured'];
        try {
            $r = $this->client()->get('/purge', ['url' => $url]);
            $this->log('purge_url', $r, $r->successful(), ['url' => $url]);
            return ['ok' => $r->successful()];
        } catch (Throwable $e) {
            $this->logException('purge_url', $e, ['url' => $url]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function client()
    {
        return Http::baseUrl($this->apiBase)
            ->withHeaders([
                'AccessKey' => (string) get_setting('perf_bunny_api_key'),
                'Accept'    => 'application/json',
            ])
            ->timeout(15);
    }

    protected function log(string $action, $response, bool $ok, array $meta = []): void
    {
        try {
            OptimizationLog::create([
                'type'   => 'bunny_cdn',
                'action' => $action,
                'status' => $ok ? 'success' : 'failed',
                'meta'   => array_merge($meta, ['status_code' => $response->status()]),
                'error_message' => $ok ? null : substr((string) $response->body(), 0, 1000),
            ]);
        } catch (Throwable $e) {}
    }

    protected function logException(string $action, Throwable $e, array $meta = []): void
    {
        try {
            OptimizationLog::create([
                'type'   => 'bunny_cdn',
                'action' => $action,
                'status' => 'failed',
                'meta'   => $meta,
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);
        } catch (Throwable $ig) {}
    }
}
