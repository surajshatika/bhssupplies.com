<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class PostIndexStatusService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $urls    = $payload['urls'] ?? [];
        $domain  = $payload['domain'] ?? parse_url(url('/'), PHP_URL_HOST);
        $apiKey  = get_setting('seo_google_search_api_key') ?? env('GOOGLE_SEARCH_API_KEY');
        $cx      = get_setting('seo_google_search_cx') ?? env('GOOGLE_SEARCH_CX');

        if (is_string($urls)) {
            $urls = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $urls)));
        }

        if (empty($urls)) {
            $urls = $this->getRecentUrls($domain);
        }

        $results = [];
        foreach (array_slice($urls, 0, 50) as $url) {
            $results[] = $this->checkIndexStatus($url, $apiKey, $cx);
        }

        $indexed    = count(array_filter($results, fn($r) => $r['status'] === 'indexed'));
        $notIndexed = count(array_filter($results, fn($r) => $r['status'] === 'not_indexed'));
        $errors     = count(array_filter($results, fn($r) => !empty($r['error'])));

        $prompt = "SEO Index Status Report for {$domain}:\n"
            . "- Total checked: " . count($results) . "\n"
            . "- Indexed: {$indexed}\n"
            . "- Not indexed: {$notIndexed}\n"
            . "- Check errors: {$errors}\n\n"
            . "List of not-indexed URLs: " . json_encode(array_column(
                array_filter($results, fn($r) => $r['status'] === 'not_indexed'), 'url'
            ), JSON_PRETTY_PRINT) . "\n\n"
            . "Provide recommendations to get these pages indexed faster.";

        $aiAdvice = $this->ai()->generate($prompt, 'You are an expert in Google indexing and Search Console.');

        return [
            'total'      => count($results),
            'indexed'    => $indexed,
            'not_indexed'=> $notIndexed,
            'errors'     => $errors,
            'results'    => $results,
            'ai_advice'  => $aiAdvice,
            'api_available' => !empty($apiKey && $cx),
        ];
    }

    protected function checkIndexStatus(string $url, ?string $apiKey, ?string $cx): array
    {
        if ($apiKey && $cx) {
            $method = 'google_search_api';

            try {
                $response = Http::timeout(10)
                    ->withOptions(['verify' => config('seo.ssl_verify', true)])
                    ->get('https://www.googleapis.com/customsearch/v1', [
                        'key' => $apiKey,
                        'cx'  => $cx,
                        'q'   => 'site:' . $url,
                        'num' => 1,
                    ]);
                if ($response->successful()) {
                    $total = $response->json('searchInformation.totalResults', '0');
                    $indexed = (int) $total > 0;

                    return $this->indexStatusResult($url, $indexed, $method);
                }

                return $this->indexStatusError(
                    $url,
                    $method,
                    'Google Search API HTTP ' . $response->status() . ': '
                        . $response->json('error.message', 'Request failed.')
                );
            } catch (\Throwable $e) {
                return $this->indexStatusError($url, $method, $e->getMessage());
            }
        }

        // Fallback: check via Google cache URL
        $method = 'cache_check';

        try {
            $cacheUrl = 'https://webcache.googleusercontent.com/search?q=cache:' . urlencode($url);
            $response = Http::timeout(8)->get($cacheUrl);

            if (!$response->successful()) {
                return $this->indexStatusError($url, $method, 'Google cache check HTTP ' . $response->status() . '.');
            }

            return $this->indexStatusResult($url, !str_contains($response->body(), 'Error 404'), $method);
        } catch (\Throwable $e) {
            return $this->indexStatusError($url, $method, $e->getMessage());
        }
    }

    protected function indexStatusResult(string $url, bool $indexed, string $method): array
    {
        return [
            'url'      => $url,
            'indexed'  => $indexed,
            'status'   => $indexed ? 'indexed' : 'not_indexed',
            'method'   => $method,
            'error'    => null,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    protected function indexStatusError(string $url, string $method, string $error): array
    {
        return [
            'url'        => $url,
            'indexed'    => false,
            'status'     => 'api_error',
            'method'     => $method,
            'error'      => $error,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    protected function getRecentUrls(string $domain): array
    {
        $urls = [url('/')];
        try {
            $products = \App\Models\Product::where('published', 1)->latest()->limit(20)->pluck('slug');
            foreach ($products as $slug) {
                $urls[] = url('/product/' . $slug);
            }
        } catch (\Throwable $e) {}
        return $urls;
    }
}
