<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use App\Models\SeoScoreHistory;
use Illuminate\Support\Facades\Http;

class SearchStatisticsService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $siteUrl = $payload['site_url'] ?? get_setting('seo_search_console_site', url('/'));
        $days    = (int) ($payload['days'] ?? 28);
        $token   = get_setting('seo_search_console_token') ?? env('GOOGLE_SEARCH_CONSOLE_TOKEN');

        $gscData = [];
        if ($token) {
            $gscData = $this->fetchGscData($siteUrl, $token, $days);
        }

        $localStats = $this->buildLocalStats($days);
        $topPages   = $this->getTopPages();

        $prompt = "You are an SEO analytics expert. Here is search performance data for {$siteUrl}:\n"
            . "- Period: last {$days} days\n"
            . "- Local SEO Score History: " . json_encode($localStats) . "\n"
            . (empty($gscData) ? "- Google Search Console: not connected\n" : "- GSC Data: " . json_encode($gscData) . "\n")
            . "\nProvide:\n"
            . "1. Performance trend analysis\n"
            . "2. Key improvements noticed\n"
            . "3. Areas needing attention\n"
            . "4. Forecasting next 30 days";

        $aiAnalysis = $this->ai()->generate($prompt, 'You are an expert in SEO analytics and search performance.');

        return [
            'site_url'    => $siteUrl,
            'period_days' => $days,
            'local_stats' => $localStats,
            'gsc_data'    => $gscData,
            'top_pages'   => $topPages,
            'ai_analysis' => $aiAnalysis,
            'gsc_connected' => !empty($token),
        ];
    }

    protected function fetchGscData(string $siteUrl, string $token, int $days): array
    {
        try {
            $endDate   = now()->format('Y-m-d');
            $startDate = now()->subDays($days)->format('Y-m-d');
            $encoded   = urlencode($siteUrl);

            $response = Http::timeout(20)
                ->withToken($token)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->post("https://searchconsole.googleapis.com/webmasters/v3/sites/{$encoded}/searchAnalytics/query", [
                    'startDate'  => $startDate,
                    'endDate'    => $endDate,
                    'dimensions' => ['query'],
                    'rowLimit'   => 25,
                ]);

            if ($response->successful()) {
                return $response->json('rows', []);
            }
        } catch (\Throwable $e) {
            // GSC API unavailable
        }
        return [];
    }

    protected function buildLocalStats(int $days): array
    {
        try {
            return SeoScoreHistory::where('recorded_at', '>=', now()->subDays($days))
                ->orderBy('recorded_at')
                ->get(['url', 'score', 'grade', 'recorded_at'])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getTopPages(): array
    {
        try {
            return SeoScoreHistory::orderByDesc('score')
                ->limit(10)
                ->get(['url', 'score', 'grade'])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
