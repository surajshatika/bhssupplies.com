<?php

namespace App\Console\Commands;

use App\Services\Marketing\GoogleReviewService;
use Illuminate\Console\Command;

class SyncGoogleReviews extends Command
{
    protected $signature = 'marketing:sync-google-reviews {--lang=}';

    protected $description = 'Fetch latest Google Business reviews via Places API and cache them locally (no 3rd-party script).';

    public function handle(GoogleReviewService $service): int
    {
        if (!$service->isEnabled()) {
            $this->warn('Google Reviews is disabled or not configured. Skipping.');
            return 0;
        }

        $this->info('Fetching reviews from Google Places API...');

        $result = $service->syncAndStore($this->option('lang') ?: null);

        if (!($result['success'] ?? false)) {
            $this->error('Sync failed: '.($result['error'] ?? 'unknown'));
            return 1;
        }

        $this->info(sprintf(
            'Synced %d review(s). Business: %s | Rating: %s (%d total).',
            (int) ($result['count'] ?? 0),
            $result['data']['business']['name'] ?? 'n/a',
            $result['data']['business']['rating'] ?? 'n/a',
            (int) ($result['data']['business']['user_ratings_total'] ?? 0)
        ));

        return 0;
    }
}
