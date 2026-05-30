<?php

namespace App\Jobs\Seo;

use App\Models\SeoFixBatch;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Processes one SeoFixBatch: walks its `target_ids` array, calls the existing
 * single-entity fix for each, and persists counts to the row after every step.
 *
 * Design notes:
 *   - Re-entrant: a re-dispatch picks up at the next un-processed offset by
 *     reading `processed`. Safe if the queue worker dies mid-batch.
 *   - Self-cancelling: status flipped to "cancelled" on the row stops the loop
 *     on the next iteration boundary.
 *   - Cost tracking is conservative — adds the estimated per-entity cost on
 *     success and skips it on failure. Real token usage is provider-specific
 *     and not exposed here; the estimate stays close enough for budgeting.
 */
class AiAutoFixSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;  // 30 min — enough for a 100-URL run with AI calls
    public int $tries   = 1;     // we handle entity-level retries ourselves

    public function __construct(public int $batchId)
    {
        $this->onQueue(config('seo.queue.optimization', 'default'));
    }

    public function handle(AiSeoBoardService $board): void
    {
        $batch = SeoFixBatch::find($this->batchId);
        if (!$batch || $batch->isTerminal()) {
            return;
        }

        $targets = $batch->target_ids ?? [];
        $total   = count($targets);

        $batch->update([
            'status'     => SeoFixBatch::STATUS_RUNNING,
            'started_at' => $batch->started_at ?: Carbon::now(),
            'total'      => max($batch->total, $total),
        ]);

        $perEntityCost = $this->perEntityCost($batch);
        $optimizedUrls = [];

        for ($i = $batch->processed; $i < $total; $i++) {
            // Re-read every 5 entities so an admin's "Cancel" click is picked up.
            if ($i % 5 === 0) {
                $batch->refresh();
                if ($batch->status === SeoFixBatch::STATUS_CANCELLED) {
                    return;
                }
            }

            $target = $targets[$i];
            $type   = $target['type'] ?? null;
            $id     = (int) ($target['id'] ?? 0);

            if (!$type || !$id) {
                $batch->skipped++;
                $batch->processed++;
                $batch->save();
                continue;
            }

            $label = ($type ? ucfirst($type) : 'item') . '#' . $id;
            $batch->update(['current_label' => $label]);

            try {
                $result = $board->applyAiFix($type, $id, $batch->provider);

                $applied = $result['applied'] ?? [];
                if (empty($applied)) {
                    $batch->skipped++;
                } else {
                    $batch->succeeded++;
                    $batch->actual_cost_usd = round((float) $batch->actual_cost_usd + $perEntityCost, 4);
                    $url = $result['row']['url'] ?? null;
                    if ($url) {
                        $optimizedUrls[] = $url;
                    }
                }
            } catch (Throwable $e) {
                $batch->failed++;
                $batch->appendError("[{$label}] " . $e->getMessage());
                logger()->warning('AiAutoFixSeoJob entity failed', [
                    'batch' => $batch->id,
                    'type'  => $type,
                    'id'    => $id,
                    'err'   => $e->getMessage(),
                ]);
            }

            $batch->processed++;
            $batch->save();
        }

        $batch->update([
            'status'        => SeoFixBatch::STATUS_COMPLETED,
            'current_label' => null,
            'completed_at'  => Carbon::now(),
        ]);

        $this->runPostOptimizationActions($optimizedUrls, $batch);
    }

    public function failed(Throwable $exception): void
    {
        $batch = SeoFixBatch::find($this->batchId);
        if ($batch && !$batch->isTerminal()) {
            $batch->appendError('Job crashed: ' . $exception->getMessage());
            $batch->update([
                'status'       => SeoFixBatch::STATUS_FAILED,
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    protected function perEntityCost(SeoFixBatch $batch): float
    {
        if ($batch->total <= 0 || (float) $batch->estimated_cost_usd <= 0) {
            return 0.0;
        }
        return (float) $batch->estimated_cost_usd / $batch->total;
    }

    protected function runPostOptimizationActions(array $optimizedUrls, SeoFixBatch $batch): void
    {
        if (empty($optimizedUrls)) {
            return;
        }

        try {
            app(SmartSitemapService::class)->handle(['persist' => true, 'base_url' => url('/')]);
        } catch (Throwable $e) {
            $batch->appendError('Post-action sitemap refresh failed: ' . $e->getMessage());
            logger()->warning('SEO post-action sitemap refresh failed', ['batch' => $batch->id, 'err' => $e->getMessage()]);
        }

        if ((int) get_setting('seo_auto_indexnow', 0) !== 1) {
            return;
        }

        try {
            app(IndexNowService::class)->handle(['urls' => array_values(array_unique($optimizedUrls))]);
        } catch (Throwable $e) {
            $batch->appendError('Post-action IndexNow failed: ' . $e->getMessage());
            logger()->warning('SEO post-action IndexNow failed', ['batch' => $batch->id, 'err' => $e->getMessage()]);
        }
    }
}
