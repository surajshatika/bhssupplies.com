<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class IndexNowService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $urls   = $payload['urls'] ?? [];
        $host   = $payload['host'] ?? parse_url(url('/'), PHP_URL_HOST);
        $apiKey = $this->getIndexNowKey();

        if (empty($apiKey)) {
            return ['error' => 'IndexNow API key not configured. Set SEO_INDEXNOW_KEY in your .env or Global Settings.'];
        }

        if (empty($urls)) {
            return ['error' => 'No URLs provided for IndexNow submission.'];
        }

        $results = [];
        $endpoint = 'https://' . config('seo.indexnow.host', 'api.indexnow.org') . '/IndexNow';

        // Batch submit (IndexNow supports batch up to 10,000 URLs)
        $batches = array_chunk($urls, 100);
        foreach ($batches as $batch) {
            try {
                $response = Http::timeout(30)
                    ->withOptions(['verify' => config('seo.ssl_verify', true)])
                    ->post($endpoint, [
                        'host'    => $host,
                        'key'     => $apiKey,
                        'keyLocation' => url('/' . $apiKey . '.txt'),
                        'urlList' => $batch,
                    ]);
                $results[] = [
                    'batch_size' => count($batch),
                    'status'     => $response->status(),
                    'success'    => $response->successful(),
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'batch_size' => count($batch),
                    'status'     => 0,
                    'success'    => false,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        $this->generateKeyFile($apiKey);

        return [
            'submitted'     => count($urls),
            'key'           => $apiKey,
            'key_url'       => url('/' . $apiKey . '.txt'),
            'results'       => $results,
            'all_success'   => !in_array(false, array_column($results, 'success'), true),
        ];
    }

    public function generateKey(): string
    {
        return substr(md5(uniqid('indexnow', true)), 0, 32);
    }

    protected function generateKeyFile(string $apiKey): void
    {
        file_put_contents(public_path($apiKey . '.txt'), $apiKey);
    }

    protected function getIndexNowKey(): ?string
    {
        $key = config('seo.indexnow.key');
        if ($key) return $key;
        if (function_exists('get_setting')) {
            return get_setting('seo_indexnow_key');
        }
        return null;
    }
}
