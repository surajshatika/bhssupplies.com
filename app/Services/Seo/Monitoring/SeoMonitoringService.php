<?php

namespace App\Services\Seo\Monitoring;

use App\Models\SeoAnalytic;
use App\Models\SeoBrokenLink;
use App\Models\SeoFixBatch;
use App\Models\SeoKeyword;
use App\Models\SeoMeta;
use App\Models\SeoRun;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Board\AiSeoBoardService;
use App\Services\Seo\Budget\SeoBudgetGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Aggregates suite-wide telemetry for the monitoring dashboard.
 *
 * Every method tolerates missing tables (returns empty datasets) so the
 * dashboard renders cleanly even on fresh installs where some features are
 * unused (e.g. no GSC sync, no keyword tracking).
 */
class SeoMonitoringService
{
    public function snapshot(int $windowDays = 30): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $start = $today->subDays($windowDays - 1);

        return [
            'window_days'   => $windowDays,
            'window_start'  => $start->toDateString(),
            'today'         => $today->toDateString(),

            'totals'        => $this->totals(),
            'cost_series'   => $this->dailyCostSeries($start, $today),
            'run_series'    => $this->dailyRunSeries($start, $today),
            'score_series'  => $this->dailyScoreSeries($start, $today),
            'top_features'  => $this->topFailingFeatures($start),
            'recent_batches'=> $this->recentBatches(),
            'gsc_queries'   => $this->topGscQueries(),
            'gsc_pages'     => $this->topGscPages(),
            'keyword_movers'=> $this->keywordMovers(),
            'broken_links'  => $this->brokenLinkSummary(),
            'score_buckets' => $this->scoreBuckets(),
            'autopilot'     => $this->autopilotHealth(),
        ];
    }

    protected function autopilotHealth(): array
    {
        $enabled = (int) get_setting('seo_auto_seo_enabled', 1) === 1;
        $batchSize = (int) get_setting('seo_auto_seo_batch_size', 10);
        $breakdown = [];
        $pendingTotal = 0;
        $activeBatch = null;
        $recentFailureCount = 0;

        try {
            if (Schema::hasTable('seo_meta')) {
                $breakdown = app(AiSeoBoardService::class)->pendingBreakdownByType(['product', 'category', 'page']);
                $pendingTotal = collect($breakdown)->sum('pending');
            }
            if (Schema::hasTable('seo_fix_batches')) {
                $activeBatch = SeoFixBatch::query()
                    ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
                    ->latest()
                    ->first();
                $recentFailureCount = SeoFixBatch::query()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->whereIn('status', [SeoFixBatch::STATUS_FAILED, SeoFixBatch::STATUS_CANCELLED])
                    ->count();
            }
        } catch (Throwable $e) {
            $breakdown = [];
        }

        $budget = app(SeoBudgetGuard::class);
        $cap = $budget->dailyCapUsd();
        $spent = round($budget->spendToday(), 4);

        return [
            'enabled' => $enabled,
            'batch_size' => $batchSize,
            'pending_total' => $pendingTotal,
            'days_to_completion' => $batchSize > 0 ? (int) ceil($pendingTotal / $batchSize) : null,
            'active_batch' => $activeBatch ? [
                'id' => $activeBatch->id,
                'status' => $activeBatch->status,
                'percent' => $activeBatch->progressPercent(),
                'processed' => $activeBatch->processed,
                'total' => $activeBatch->total,
            ] : null,
            'recent_failure_count' => $recentFailureCount,
            'budget_cap' => $cap,
            'spent_today' => $spent,
            'remaining_today' => $cap > 0 ? round($budget->remainingUsd(), 4) : null,
            'breakdown' => $breakdown,
        ];
    }

    protected function totals(): array
    {
        return [
            'fix_batches_30d' => $this->safeCount(SeoFixBatch::class, fn($q) => $q->where('created_at', '>=', now()->subDays(30))),
            'ai_spend_30d'    => round((float) $this->safeSum(SeoFixBatch::class, 'actual_cost_usd',
                fn($q) => $q->where('created_at', '>=', now()->subDays(30))), 4),
            'runs_30d'        => $this->safeCount(SeoRun::class, fn($q) => $q->where('created_at', '>=', now()->subDays(30))),
            'failed_runs_30d' => $this->safeCount(SeoRun::class,
                fn($q) => $q->where('status', 'failed')->where('created_at', '>=', now()->subDays(30))),
            'entities_scored' => Schema::hasTable('seo_meta') ? SeoMeta::query()->whereNotNull('seo_score')->count() : 0,
            'avg_score'       => Schema::hasTable('seo_meta')
                ? (int) round((float) (SeoMeta::query()->whereNotNull('seo_score')->avg('seo_score') ?? 0))
                : 0,
            'active_keywords' => $this->safeCount(SeoKeyword::class, fn($q) => $q->where('is_active', true)),
            'broken_links'    => $this->safeCount(SeoBrokenLink::class, fn($q) => $q->where('state', 'broken')),
        ];
    }

    protected function dailyCostSeries(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (!Schema::hasTable('seo_fix_batches')) {
            return [];
        }

        try {
            $rows = DB::table('seo_fix_batches')
                ->selectRaw('DATE(created_at) as date, SUM(actual_cost_usd) as usd, COUNT(*) as batches')
                ->whereBetween('created_at', [$start->toDateTimeString(), $end->endOfDay()->toDateTimeString()])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');
        } catch (Throwable $e) {
            return [];
        }

        return $this->fillDateGaps($start, $end, fn($date) => [
            'date'    => $date,
            'usd'     => round((float) ($rows[$date]->usd ?? 0), 4),
            'batches' => (int) ($rows[$date]->batches ?? 0),
        ]);
    }

    protected function dailyRunSeries(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (!Schema::hasTable('seo_runs')) {
            return [];
        }

        try {
            $rows = DB::table('seo_runs')
                ->selectRaw("DATE(created_at) as date,
                             SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                             SUM(CASE WHEN status='failed'    THEN 1 ELSE 0 END) as failed,
                             SUM(CASE WHEN status NOT IN ('completed','failed') THEN 1 ELSE 0 END) as other")
                ->whereBetween('created_at', [$start->toDateTimeString(), $end->endOfDay()->toDateTimeString()])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');
        } catch (Throwable $e) {
            return [];
        }

        return $this->fillDateGaps($start, $end, fn($date) => [
            'date'      => $date,
            'completed' => (int) ($rows[$date]->completed ?? 0),
            'failed'    => (int) ($rows[$date]->failed    ?? 0),
            'other'     => (int) ($rows[$date]->other     ?? 0),
        ]);
    }

    protected function dailyScoreSeries(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (!Schema::hasTable('seo_score_histories')) {
            return [];
        }

        try {
            $rows = DB::table('seo_score_histories')
                ->selectRaw('DATE(recorded_at) as date, AVG(score) as avg_score, COUNT(*) as n')
                ->whereBetween('recorded_at', [$start->toDateTimeString(), $end->endOfDay()->toDateTimeString()])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');
        } catch (Throwable $e) {
            return [];
        }

        return $this->fillDateGaps($start, $end, fn($date) => [
            'date'      => $date,
            'avg_score' => isset($rows[$date]) ? round((float) $rows[$date]->avg_score, 1) : null,
            'samples'   => (int) ($rows[$date]->n ?? 0),
        ]);
    }

    protected function topFailingFeatures(CarbonImmutable $start, int $limit = 8): array
    {
        if (!Schema::hasTable('seo_runs')) {
            return [];
        }

        try {
            return DB::table('seo_runs')
                ->selectRaw("feature,
                             SUM(CASE WHEN status='failed'    THEN 1 ELSE 0 END) as failed,
                             SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                             COUNT(*) as total")
                ->where('created_at', '>=', $start->toDateTimeString())
                ->groupBy('feature')
                ->orderByDesc('failed')
                ->limit($limit)
                ->get()
                ->map(fn($r) => [
                    'feature'   => $r->feature,
                    'failed'    => (int) $r->failed,
                    'completed' => (int) $r->completed,
                    'total'     => (int) $r->total,
                    'rate'      => $r->total > 0 ? round(($r->failed / $r->total) * 100, 1) : 0,
                ])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function recentBatches(int $limit = 8): array
    {
        if (!Schema::hasTable('seo_fix_batches')) {
            return [];
        }

        return SeoFixBatch::query()
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'label', 'status', 'total', 'succeeded', 'failed', 'skipped', 'actual_cost_usd', 'created_at', 'completed_at'])
            ->map(fn($b) => [
                'id'           => $b->id,
                'label'        => $b->label,
                'status'       => $b->status,
                'total'        => $b->total,
                'succeeded'    => $b->succeeded,
                'failed'       => $b->failed,
                'skipped'      => $b->skipped,
                'cost_usd'     => round((float) $b->actual_cost_usd, 4),
                'started_at'   => optional($b->created_at)->format('Y-m-d H:i'),
                'completed_at' => optional($b->completed_at)->format('Y-m-d H:i'),
            ])
            ->all();
    }

    protected function topGscQueries(int $limit = 10): array
    {
        return $this->topGscDimension('query', $limit);
    }

    protected function topGscPages(int $limit = 10): array
    {
        return $this->topGscDimension('page', $limit);
    }

    protected function topGscDimension(string $dim, int $limit): array
    {
        if (!Schema::hasTable('seo_analytics')) {
            return [];
        }

        try {
            return DB::table('seo_analytics')
                ->select('value')
                ->selectRaw('SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(position) as position, AVG(ctr) as ctr')
                ->where('source', 'gsc')
                ->where('dimension', $dim)
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->groupBy('value')
                ->orderByDesc('clicks')
                ->limit($limit)
                ->get()
                ->map(fn($r) => [
                    'value'       => $r->value,
                    'clicks'      => (int) $r->clicks,
                    'impressions' => (int) $r->impressions,
                    'ctr'         => round((float) $r->ctr * 100, 2),
                    'position'    => round((float) $r->position, 1),
                ])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function keywordMovers(int $limit = 10): array
    {
        if (!Schema::hasTable('seo_keywords')) {
            return [];
        }

        try {
            return SeoKeyword::query()
                ->whereNotNull('rank_current')
                ->whereNotNull('rank_previous')
                ->whereRaw('rank_current <> rank_previous')
                ->orderByRaw('ABS(COALESCE(rank_previous,0) - COALESCE(rank_current,0)) DESC')
                ->limit($limit)
                ->get(['keyword', 'rank_current', 'rank_previous', 'target_url', 'last_checked_at'])
                ->map(fn($k) => [
                    'keyword'        => $k->keyword,
                    'current'        => $k->rank_current,
                    'previous'       => $k->rank_previous,
                    'delta'          => (int) $k->rank_previous - (int) $k->rank_current,
                    'target_url'     => $k->target_url,
                    'last_checked_at'=> optional($k->last_checked_at)->format('Y-m-d H:i'),
                ])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function brokenLinkSummary(): array
    {
        if (!Schema::hasTable('seo_broken_links')) {
            return ['broken' => 0, 'timeout' => 0, 'resolved' => 0, 'samples' => []];
        }

        try {
            $counts = DB::table('seo_broken_links')
                ->selectRaw("SUM(CASE WHEN state='broken'   THEN 1 ELSE 0 END) as broken,
                             SUM(CASE WHEN state='timeout'  THEN 1 ELSE 0 END) as timeout,
                             SUM(CASE WHEN state='resolved' THEN 1 ELSE 0 END) as resolved")
                ->first();

            $samples = DB::table('seo_broken_links')
                ->where('state', 'broken')
                ->orderByDesc('hit_count')
                ->limit(8)
                ->get(['source_url', 'target_url', 'status_code', 'hit_count', 'last_checked_at'])
                ->all();

            return [
                'broken'   => (int) ($counts->broken   ?? 0),
                'timeout'  => (int) ($counts->timeout  ?? 0),
                'resolved' => (int) ($counts->resolved ?? 0),
                'samples'  => $samples,
            ];
        } catch (Throwable $e) {
            return ['broken' => 0, 'timeout' => 0, 'resolved' => 0, 'samples' => []];
        }
    }

    protected function scoreBuckets(): array
    {
        if (!Schema::hasTable('seo_meta')) {
            return ['critical' => 0, 'warning' => 0, 'good' => 0, 'unrated' => 0];
        }

        try {
            return [
                'critical' => SeoMeta::query()->whereNotNull('seo_score')->where('seo_score', '<', 50)->count(),
                'warning'  => SeoMeta::query()->whereBetween('seo_score', [50, 79])->count(),
                'good'     => SeoMeta::query()->where('seo_score', '>=', 80)->count(),
                'unrated'  => SeoMeta::query()->whereNull('seo_score')->count(),
            ];
        } catch (Throwable $e) {
            return ['critical' => 0, 'warning' => 0, 'good' => 0, 'unrated' => 0];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────

    protected function fillDateGaps(CarbonImmutable $start, CarbonImmutable $end, callable $builder): array
    {
        $out  = [];
        $date = $start;
        while ($date <= $end) {
            $key = $date->toDateString();
            $out[] = $builder($key);
            $date = $date->addDay();
        }
        return $out;
    }

    protected function safeCount(string $class, ?callable $apply = null): int
    {
        try {
            if (!class_exists($class)) return 0;
            $table = (new $class)->getTable();
            if (!Schema::hasTable($table)) return 0;
            $q = $class::query();
            if ($apply) $apply($q);
            return $q->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    protected function safeSum(string $class, string $column, ?callable $apply = null): float
    {
        try {
            if (!class_exists($class)) return 0.0;
            $table = (new $class)->getTable();
            if (!Schema::hasTable($table)) return 0.0;
            $q = $class::query();
            if ($apply) $apply($q);
            return (float) $q->sum($column);
        } catch (Throwable $e) {
            return 0.0;
        }
    }
}
