<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Throwable;

class CloudFrontService
{
    public function isConfigured(): bool
    {
        return (int) get_setting('perf_cloudfront_status', 0) === 1
            && trim((string) get_setting('perf_cloudfront_distribution', '')) !== ''
            && trim((string) get_setting('perf_cloudfront_access_key', '')) !== ''
            && trim((string) get_setting('perf_cloudfront_secret', '')) !== ''
            && class_exists(\Aws\CloudFront\CloudFrontClient::class);
    }

    public function invalidate(array $paths = ['/*']): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'CloudFront not enabled, missing credentials, or AWS SDK not installed'];
        }
        try {
            $client = new \Aws\CloudFront\CloudFrontClient([
                'version'     => 'latest',
                'region'      => (string) (get_setting('perf_cloudfront_region') ?: 'us-east-1'),
                'credentials' => [
                    'key'    => (string) get_setting('perf_cloudfront_access_key'),
                    'secret' => (string) get_setting('perf_cloudfront_secret'),
                ],
            ]);

            $result = $client->createInvalidation([
                'DistributionId'    => (string) get_setting('perf_cloudfront_distribution'),
                'InvalidationBatch' => [
                    'CallerReference' => 'perf_optimizer_' . time(),
                    'Paths' => [
                        'Quantity' => count($paths),
                        'Items'    => array_values($paths),
                    ],
                ],
            ]);

            $invalidationId = $result['Invalidation']['Id'] ?? null;
            $this->log('invalidate', true, ['invalidation_id' => $invalidationId, 'paths' => $paths]);
            return ['ok' => true, 'invalidation_id' => $invalidationId];
        } catch (Throwable $e) {
            $this->log('invalidate', false, ['paths' => $paths], $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function log(string $action, bool $ok, array $meta = [], ?string $error = null): void
    {
        try {
            OptimizationLog::create([
                'type'   => 'cloudfront',
                'action' => $action,
                'status' => $ok ? 'success' : 'failed',
                'meta'   => $meta,
                'error_message' => $error,
            ]);
        } catch (Throwable $e) {}
    }
}
