<?php

namespace App\Services\PerformanceOptimizer;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ThirdPartyLocalizerService
{
    /**
     * Known external scripts that impact PageSpeed.
     */
    protected array $scripts = [
        'analytics.js' => 'https://www.google-analytics.com/analytics.js',
        'fbevents.js'  => 'https://connect.facebook.net/en_US/fbevents.js',
        'gtm.js'       => 'https://www.googletagmanager.com/gtm.js',
    ];

    /**
     * Download all configured external scripts.
     */
    public function localizeAll(): array
    {
        $dir = public_path('perf/scripts');
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($this->scripts as $filename => $url) {
            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $content = $response->body();
                    if (trim($content) !== '') {
                        File::put($dir . DIRECTORY_SEPARATOR . $filename, $content);
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Empty response from $url";
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to download $url: HTTP " . $response->status();
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Exception downloading $url: " . $e->getMessage();
                Log::error('[PerfOptimizer] Script localizer failed for ' . $url, ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }
}
