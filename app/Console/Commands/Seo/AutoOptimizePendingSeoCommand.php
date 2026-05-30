<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoFixBatch;
use App\Services\Seo\Board\AiSeoBoardService;
use App\Services\Seo\Budget\SeoBudgetGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AutoOptimizePendingSeoCommand extends Command
{
    protected $signature = 'seo:auto-optimize-pending
                            {--limit= : Number of pending URLs to optimize this run}
                            {--provider= : AI provider override}
                            {--dry-run : Show what would be queued without creating a batch}';

    protected $description = 'Automatically generate advanced Canada SEO for pending Product, Category, and Page URLs only.';

    public function handle(AiSeoBoardService $board, SeoBudgetGuard $budget): int
    {
        if ((int) get_setting('seo_auto_seo_enabled', 1) !== 1 && !$this->option('dry-run')) {
            $this->info('Automatic SEO generation is disabled in SEO settings.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('seo_meta') || !Schema::hasTable('seo_fix_batches')) {
            $this->warn('Required seo_* tables are missing. Run migrations first.');
            return self::FAILURE;
        }

        $activeBatch = SeoFixBatch::query()
            ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
            ->latest()
            ->first();

        if ($activeBatch && !$this->option('dry-run')) {
            $this->info('No new SEO batch created because active batch #' . $activeBatch->id . ' is still ' . $activeBatch->status . '.');
            $this->line('Active batch progress: ' . (int) $activeBatch->processed . '/' . (int) $activeBatch->total . ' (' . $activeBatch->progressPercent() . '%).');
            $this->line('The master automation runner will process active batches next via seo:process-ai-batches.');
            return self::SUCCESS;
        }

        $configuredLimit = (int) get_setting('seo_auto_seo_batch_size', 10);
        $limit = (int) ($this->option('limit') ?: $configuredLimit);
        $limit = max(1, min(100, $limit));

        $provider = $this->option('provider') ?: get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));
        $targetRows = $board->nextAutopilotTargetPreview($limit, ['product', 'category', 'page']);
        $targets = $targetRows
            ->map(fn(array $row) => ['type' => $row['type'], 'id' => (int) $row['id']])
            ->values()
            ->all();

        if (empty($targets)) {
            $this->info('No pending Product, Category, or Page URLs found.');
            return self::SUCCESS;
        }

        $estimate = $board->estimateBatchCost($targets, $provider);
        $this->info('Pending targets: ' . count($targets));
        $this->info('Provider: ' . $estimate['provider'] . ' | Estimated cost: $' . number_format($estimate['usd'], 4));

        if ($this->option('dry-run')) {
            $this->line('Dry-run preview. Priority is queue urgency, not the current SEO score.');
            foreach ($targetRows as $target) {
                $this->line(sprintf(
                    '- %s#%d | priority %d/100 | current SEO %d/100 | issues: %s',
                    $target['type'],
                    (int) $target['id'],
                    (int) ($target['priority_score'] ?? 0),
                    (int) ($target['score'] ?? 0),
                    implode(', ', $target['priority_reasons'] ?? [])
                ));
            }
            return self::SUCCESS;
        }

        if (!$budget->allowsAdditional($estimate['usd'])) {
            $this->warn(sprintf(
                'Daily SEO AI budget cap would be exceeded. Cap: $%.2f, spent today: $%.4f, batch: $%.4f.',
                $budget->dailyCapUsd(),
                $budget->spendToday(),
                $estimate['usd']
            ));
            return self::FAILURE;
        }

        $batch = $board->createBatch(
            $targets,
            $provider,
            null,
            'Scheduled Canada SEO auto-fix - ' . count($targets) . ' URLs'
        );

        $budget->bustCache();
        $this->info('Queued SEO batch #' . $batch->id . ' for ' . count($targets) . ' pending URLs.');

        return self::SUCCESS;
    }
}
