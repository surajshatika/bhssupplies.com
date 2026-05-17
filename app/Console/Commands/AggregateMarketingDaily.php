<?php

namespace App\Console\Commands;

use App\Services\Marketing\EventStore;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateMarketingDaily extends Command
{
    protected $signature = 'marketing:aggregate-daily {--date= : YYYY-MM-DD (defaults to yesterday)} {--purge : also delete raw jsonl past retention}';

    protected $description = 'Compress one day of raw first-party marketing events into a daily summary JSON.';

    public function handle(EventStore $store): int
    {
        $date = $this->option('date') ?: Carbon::yesterday()->toDateString();

        $this->info("Aggregating {$date}…");
        $summary = $store->aggregateDate($date);
        $store->storeAggregate($summary);

        $this->info(sprintf(
            'Aggregated: %d events / %d visitors / %d sessions / %d purchases / %.2f revenue',
            array_sum($summary['event_counts']),
            $summary['unique_visitors'],
            $summary['unique_sessions'],
            $summary['purchases'],
            $summary['revenue']
        ));

        if ($this->option('purge')) {
            $deleted = $store->purgeOldRawEvents();
            $this->info("Purged {$deleted} raw event files past retention.");
        }

        return 0;
    }
}
