<?php

namespace App\Console\Commands\Seo;

use App\Jobs\Seo\AiAutoFixSeoJob;
use App\Models\SeoFixBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProcessAiSeoBatchesCommand extends Command
{
    protected $signature = 'seo:process-ai-batches
                            {--batch= : Process one specific batch ID}
                            {--limit=10 : Maximum URLs to process in this run}
                            {--max-batches=3 : Maximum active batches to process per run}
                            {--compact-only : Remove duplicate pending URLs without processing AI fixes}
                            {--dry-run : Show the next batch without changing data}';

    protected $description = 'Process queued AI SEO Board batches in small cron-friendly chunks.';

    public function handle(): int
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
            ->orderBy('id');

        if ($this->option('batch')) {
            $query->where('id', (int) $this->option('batch'));
        }

        $activeBatches = (clone $query)->get();
        $duplicates = $this->compactDuplicatePendingTargets($activeBatches, !$this->option('dry-run'));
        if ($duplicates > 0) {
            $verb = $this->option('dry-run') ? 'can be removed' : 'removed';
            $this->info("Queue compaction: {$duplicates} duplicate pending URLs {$verb} from newer active batches.");
        }
        if ($this->option('compact-only')) {
            $this->info('Queue compaction finished without processing AI fixes.');
            return self::SUCCESS;
        }

        $batches = (clone $query)->limit($maxBatches)->get();
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
                app()->call([$job, 'handle']);
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

    protected function compactDuplicatePendingTargets(Collection $batches, bool $persist): int
    {
        $claimed = [];
        $removed = 0;

        foreach ($batches as $batch) {
            $targets = array_values($batch->target_ids ?? []);
            $processed = min((int) $batch->processed, count($targets));
            $prefix = array_slice($targets, 0, $processed);
            $pending = [];

            foreach ($prefix as $target) {
                $claimed[$this->targetKey($target)] = true;
            }

            foreach (array_slice($targets, $processed) as $target) {
                $key = $this->targetKey($target);
                if (isset($claimed[$key])) {
                    $removed++;
                    continue;
                }

                $claimed[$key] = true;
                $pending[] = $target;
            }

            $compacted = array_merge($prefix, $pending);
            if (!$persist || count($compacted) === count($targets)) {
                continue;
            }

            $patch = [
                'target_ids' => $compacted,
                'total' => count($compacted),
            ];
            if (count($compacted) <= $processed) {
                $patch['status'] = SeoFixBatch::STATUS_COMPLETED;
                $patch['current_label'] = null;
                $patch['completed_at'] = now();
            }
            $batch->update($patch);
        }

        return $removed;
    }

    protected function targetKey($target): string
    {
        if (!is_array($target)) {
            return 'invalid:' . md5(serialize($target));
        }

        return (string) ($target['type'] ?? '') . ':' . (int) ($target['id'] ?? 0);
    }
}
