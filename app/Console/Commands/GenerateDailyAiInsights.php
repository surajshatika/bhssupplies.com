<?php

namespace App\Console\Commands;

use App\Services\Marketing\AnalyticsAiService;
use Illuminate\Console\Command;

class GenerateDailyAiInsights extends Command
{
    protected $signature = 'marketing:daily-ai-insights {--date=}';

    protected $description = 'Generate the daily AI marketing summary (wins, concerns, anomalies, recommended actions).';

    public function handle(AnalyticsAiService $svc): int
    {
        $payload = $svc->dailySummary($this->option('date') ?: null);

        $this->info('Generated AI insights for ' . ($payload['date'] ?? 'unknown'));
        $this->line('Headline: ' . ($payload['summary']['headline'] ?? 'n/a'));
        $this->line('Provider: ' . ($payload['provider'] ?? 'n/a'));

        return 0;
    }
}
