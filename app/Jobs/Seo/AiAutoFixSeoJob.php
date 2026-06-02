<?php

namespace App\Jobs\Seo;

use App\Models\SeoFixBatch;
use App\Services\Seo\Budget\SeoBudgetGuard;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use App\Services\Seo\Board\AiSeoBoardService;
use App\Services\Seo\Providers\SeoProviderManager;
use App\Services\Seo\Providers\SeoProviderReliability;
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

    public function __construct(public int $batchId, public ?int $maxEntities = null)
    {
        $this->onQueue(config('seo.queue.optimization', 'default'));
    }

    public function handle(AiSeoBoardService $board, SeoBudgetGuard $budget): void
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

        $processedThisRun = 0;

        for ($i = $batch->processed; $i < $total; $i++) {
            if ($this->maxEntities !== null && $processedThisRun >= max(1, $this->maxEntities)) {
                break;
            }

            // Re-read before every entity so an admin queue removal stops before
            // another AI request starts. Cron chunks are intentionally small.
            $batch->refresh();
            if ($batch->status === SeoFixBatch::STATUS_CANCELLED) {
                return;
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

            $reservedCost = $this->reservedCostForNextEntity($batch);
            if (!$budget->allowsAdditional($reservedCost)) {
                $this->pauseForBudget($batch, $budget, $reservedCost);
                $this->runPostOptimizationActions($optimizedUrls, $batch);
                return;
            }

            $label = ($type ? ucfirst($type) : 'item') . '#' . $id;
            $batch->update(['current_label' => $label]);

            try {
                $result = $board->applyAiFix($type, $id, $batch->provider);
                $attemptCost = $this->estimatedAttemptCost($result, $perEntityCost);
                $this->recordSeoOutcome($batch, $result, $type, $id, $label, $attemptCost);

                if ($attemptCost > 0) {
                    $batch->actual_cost_usd = round((float) $batch->actual_cost_usd + $attemptCost, 6);
                }

                $applied = $result['applied'] ?? [];
                if (empty($applied)) {
                    $batch->skipped++;
                } else {
                    $batch->succeeded++;
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
            $processedThisRun++;
            $batch->save();
            $budget->bustCache();
        }

        $batch->refresh();

        $this->runPostOptimizationActions($optimizedUrls, $batch);

        if ($batch->processed >= $total) {
            $batch->update([
                'status'        => SeoFixBatch::STATUS_COMPLETED,
                'current_label' => null,
                'completed_at'  => Carbon::now(),
            ]);
            return;
        }

        $batch->update([
            'status'        => SeoFixBatch::STATUS_RUNNING,
            'current_label' => 'Waiting for next scheduled chunk',
        ]);
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

    protected function recordSeoOutcome(
        SeoFixBatch $batch,
        array $result,
        string $type,
        int $id,
        string $label,
        float $estimatedSpendUsd = 0.0
    ): void
    {
        $before = (int) ($result['score_before'] ?? 0);
        $after = (int) ($result['score_after'] ?? $before);
        $row = $result['row'] ?? [];
        $applied = $result['applied'] ?? [];
        $source = (string) ($result['source'] ?? 'unknown');
        $provider = $applied['ai_provider'] ?? ($source === 'template' ? 'template' : null);
        $providerAttempts = array_values(array_filter($result['ai_attempts'] ?? []));
        $providerAttemptDetails = array_values(array_filter($result['ai_attempt_details'] ?? []));
        $done = !empty($row['has_meta'])
            && !empty($row['has_focus_kw'])
            && !empty($row['has_schema'])
            && $after >= AiSeoBoardService::SEO_DONE_SCORE;

        $options = $batch->options ?? [];
        $stats = $options['seo_stats'] ?? [];
        $stats['improved'] = (int) ($stats['improved'] ?? 0);
        $stats['seo_done'] = (int) ($stats['seo_done'] ?? 0);
        $stats['no_gain'] = (int) ($stats['no_gain'] ?? 0);
        $stats['protected'] = (int) ($stats['protected'] ?? 0);
        $stats['last_checked_at'] = now()->toDateTimeString();

        if ($after > $before) {
            $stats['improved']++;
        } elseif ($source === 'protected') {
            $stats['protected']++;
        } else {
            $stats['no_gain']++;
        }

        if ($done) {
            $stats['seo_done']++;
        }

        if ($provider) {
            $providers = $stats['providers'] ?? [];
            $providers[$provider] = (int) ($providers[$provider] ?? 0) + 1;
            $stats['providers'] = $providers;
        }
        if (count($providerAttempts) > 1) {
            $stats['provider_failovers'] = (int) ($stats['provider_failovers'] ?? 0) + 1;
        }
        $stats['provider_attempts'] = (int) ($stats['provider_attempts'] ?? 0) + count($providerAttempts);
        $stats['estimated_ai_spend_usd'] = round(
            (float) ($stats['estimated_ai_spend_usd'] ?? 0) + $estimatedSpendUsd,
            6
        );

        $lastResults = $options['last_results'] ?? [];
        array_unshift($lastResults, [
            'at' => now()->toDateTimeString(),
            'type' => $type,
            'id' => $id,
            'label' => $label,
            'title' => $row['title'] ?? $label,
            'url' => $row['url'] ?? null,
            'before' => $before,
            'after' => $after,
            'delta' => $after - $before,
            'seo_done' => $done,
            'source' => $source,
            'provider' => $provider,
            'provider_attempts' => $providerAttempts,
            'provider_attempt_details' => $providerAttemptDetails,
            'estimated_ai_spend_usd' => round($estimatedSpendUsd, 6),
        ]);

        $options['seo_stats'] = $stats;
        $options['last_results'] = array_slice($lastResults, 0, 25);
        $batch->options = $options;
    }

    protected function estimatedAttemptCost(array $result, float $perEntityCost): float
    {
        $details = array_values(array_filter($result['ai_attempt_details'] ?? []));
        if (!empty($details)) {
            return round((float) collect($details)->sum(function (array $attempt): float {
                if (in_array($attempt['status'] ?? null, ['cooldown', 'not_configured'], true)) {
                    return 0.0;
                }

                return max(0.0, (float) ($attempt['estimated_cost_usd'] ?? 0.0));
            }), 6);
        }

        $attempts = array_values(array_filter($result['ai_attempts'] ?? []));

        return round(max(0.0, $perEntityCost) * count($attempts), 6);
    }

    protected function reservedCostForNextEntity(SeoFixBatch $batch): float
    {
        $reliability = app(SeoProviderReliability::class);

        return round((float) collect(SeoProviderManager::fallbackOrder($batch->provider))
            ->sum(function (string $provider) use ($reliability): float {
                if ($reliability->shouldSkip($provider)) {
                    return 0.0;
                }

                try {
                    if (!SeoProviderManager::makeDirect($provider)->isConfigured()) {
                        return 0.0;
                    }
                } catch (Throwable $e) {
                    return 0.0;
                }

                return $reliability->estimateAttemptCost($provider);
            }), 6);
    }

    protected function pauseForBudget(SeoFixBatch $batch, SeoBudgetGuard $budget, float $reservedCost): void
    {
        $options = $batch->options ?? [];
        $stats = $options['seo_stats'] ?? [];
        $stats['budget_pauses'] = (int) ($stats['budget_pauses'] ?? 0) + 1;
        $stats['last_checked_at'] = now()->toDateTimeString();
        $options['seo_stats'] = $stats;
        $options['budget_pause'] = [
            'at' => now()->toDateTimeString(),
            'reserved_cost_usd' => round($reservedCost, 6),
            'remaining_usd' => round($budget->remainingUsd(), 6),
        ];

        $batch->update([
            'current_label' => 'Paused: daily AI budget reached',
            'options' => $options,
        ]);
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
