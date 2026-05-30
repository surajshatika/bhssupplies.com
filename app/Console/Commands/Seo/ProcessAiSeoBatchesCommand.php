<?php

namespace App\Console\Commands\Seo;

use App\Jobs\Seo\AiAutoFixSeoJob;
use App\Models\SeoFixBatch;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProcessAiSeoBatchesCommand extends Command
{
    protected $signature = 'seo:process-ai-batches
                            {--batch= : Process one specific batch ID}
                            {--limit=10 : Maximum URLs to process in this run}
                            {--max-batches=3 : Maximum active batches to process per run}
                            {--dry-run : Show the next batch without changing data}';

    protected $description = 'Process queued AI SEO Board batches in small cron-friendly chunks.';

    public function handle(AiSeoBoardService $board): int
    {
        if (!Schema::hasTable('seo_fix_batches')) {
            $this->warn('seo_fix_batches table is missing. Run migrations first.');
            return self::FAILURE;
        }

        $limit = max(1, min(50, (int) $this->option('limit')));
        $maxBatches = $this->option('batch')
            ? 1
            : max(1, min(10, (int) $this->option('max-batches')));

        $query = SeoFixBatch::query()
            ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [SeoFixBatch::STATUS_QUEUED])
            ->orderBy('updated_at')
            ->orderBy('id');

        if ($this->option('batch')) {
            $query->where('id', (int) $this->option('batch'));
        }

        $batches = $query->limit($maxBatches)->get();
        if ($batches->isEmpty()) {
            $this->info('No queued or running AI SEO batches found.');
            return self::SUCCESS;
        }

        foreach ($batches as $batch) {
            $this->info(sprintf(
                'AI SEO batch #%d: %s, %d/%d processed. Chunk limit: %d',
                $batch->id,
                $batch->status,
                (int) $batch->processed,
                (int) $batch->total,
                $limit
            ));
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $failed = false;

        foreach ($batches as $batch) {
            $job = new AiAutoFixSeoJob((int) $batch->id, $limit);

            try {
                $job->handle($board);
            } catch (Throwable $e) {
                $job->failed($e);
                $this->error('Batch #' . $batch->id . ' failed: ' . $e->getMessage());
                $failed = true;
                continue;
            }

            $batch->refresh();
            $this->info(sprintf(
                'AI SEO batch #%d now %s: %d/%d processed, %d succeeded, %d skipped, %d failed.',
                $batch->id,
                $batch->status,
                (int) $batch->processed,
                (int) $batch->total,
                (int) $batch->succeeded,
                (int) $batch->skipped,
                (int) $batch->failed
            ));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
