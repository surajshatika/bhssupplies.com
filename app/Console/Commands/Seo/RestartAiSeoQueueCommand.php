<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoFixBatch;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RestartAiSeoQueueCommand extends Command
{
    protected $signature = 'seo:restart-ai-queue
                            {--limit= : Override the backend automated SEO batch size}
                            {--provider= : AI provider override}
                            {--dry-run : Show what would be removed and queued without changing data}';

    protected $description = 'Cancel unfinished AI SEO batches and start a clean automated batch using the backend SEO limit.';

    public function handle(AiSeoBoardService $board): int
    {
        if (!Schema::hasTable('seo_fix_batches')) {
            $this->warn('seo_fix_batches table is missing. Run migrations first.');
            return self::FAILURE;
        }

        $activeBatches = SeoFixBatch::query()
            ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
            ->orderBy('id')
            ->get();
        $removedPending = $activeBatches->sum(fn(SeoFixBatch $batch) => $batch->remainingCount());
        $limit = max(1, min(
            AiSeoBoardService::MAX_AUTO_BATCH_TARGETS,
            (int) ($this->option('limit') ?: get_setting('seo_auto_seo_batch_size', 10))
        ));

        $this->info('Active batches to cancel: ' . $activeBatches->count());
        $this->info('Unfinished queued URLs to remove: ' . $removedPending);
        $this->info('Fresh automated batch limit: ' . $limit . ' (backend setting unless overridden)');

        if ($this->option('dry-run')) {
            $this->line('Dry run only. No queue rows changed.');
            return self::SUCCESS;
        }

        foreach ($activeBatches as $batch) {
            $board->cancelBatch($batch);
        }

        $this->info('Old unfinished queue removed. Completed SEO work remains saved.');

        return $this->call('seo:auto-optimize-pending', array_filter([
            '--limit' => $limit,
            '--provider' => $this->option('provider'),
        ], fn($value) => $value !== null && $value !== ''));
    }
}
