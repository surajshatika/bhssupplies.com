<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoAnalytic;
use App\Services\Seo\SearchConsole\GoogleSearchConsoleService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncSearchConsoleCommand extends Command
{
    protected $signature = 'seo:sync-search-console
                            {--days=7 : Number of past days to fetch}
                            {--dimensions=query,page,query_page : Comma-separated GSC dimensions to pull}';

    protected $description = 'Sync Google Search Console performance data into seo_analytics.';

    public function handle(GoogleSearchConsoleService $gsc): int
    {
        if (!Schema::hasTable('seo_analytics')) {
            $this->warn('seo_analytics table missing — run migrations first.');
            return self::FAILURE;
        }

        if (!$gsc->isConfigured()) {
            $this->warn('Search Console not configured. Set seo_gsc_client_id/secret/refresh_token + seo_search_console_site.');
            return self::SUCCESS;
        }

        $days       = max(1, (int) $this->option('days'));
        $dimensions = array_values(array_filter(array_map('trim', explode(',', $this->option('dimensions')))));

        $end   = Carbon::today();
        $start = (clone $end)->subDays($days);

        $totalInserted = 0;

        foreach ($dimensions as $dim) {
            $this->info("Fetching dimension={$dim} window {$start->toDateString()} → {$end->toDateString()}");
            try {
                $apiDimensions = $dim === 'query_page' ? ['query', 'page'] : [$dim];
                $result = $gsc->fetchPerformance($start->toDateString(), $end->toDateString(), $apiDimensions, 5000);
                if (!$result['success']) {
                    $this->error('  ' . ($result['error'] ?? 'unknown error'));
                    continue;
                }

                foreach ($result['rows'] as $row) {
                    $value = $dim === 'query_page'
                        ? json_encode([
                            'query' => mb_substr((string) ($row['keys'][0] ?? ''), 0, 180),
                            'page' => mb_substr((string) ($row['keys'][1] ?? ''), 0, 280),
                        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        : ($row['keys'][0] ?? null);
                    if (!$value) {
                        continue;
                    }
                    $value = mb_substr((string) $value, 0, 500);
                    $date  = $end->toDateString();
                    $hash  = sha1($date . '|gsc|' . $dim . '|' . $value);

                    SeoAnalytic::updateOrCreate(
                        ['row_hash' => $hash],
                        [
                            'date'        => $date,
                            'source'      => 'gsc',
                            'dimension'   => $dim,
                            'value'       => $value,
                            'clicks'      => (int) ($row['clicks']      ?? 0),
                            'impressions' => (int) ($row['impressions'] ?? 0),
                            'ctr'         => (float) ($row['ctr']       ?? 0),
                            'position'    => (float) ($row['position']  ?? 0),
                        ]
                    );
                    $totalInserted++;
                }
            } catch (Throwable $e) {
                $this->error('  Exception: ' . $e->getMessage());
            }
        }

        $this->info("GSC sync complete — {$totalInserted} rows upserted.");
        return self::SUCCESS;
    }
}
