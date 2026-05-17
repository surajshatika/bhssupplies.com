<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\SlowQuery;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Captures slow queries from Laravel's DB::listen hook. Per-statement hash is a
 * normalized form (numeric literals → ?, quoted strings → '?') so similar
 * queries are deduped and counted.
 */
class SlowQueryAnalyzerService
{
    /** Whether DB::listen has already been attached this request. */
    protected static bool $listening = false;

    public function attachListener(): void
    {
        if (self::$listening) return;
        if ((int) get_setting('perf_slow_query_status', 0) !== 1) return;

        self::$listening = true;
        DB::listen(function ($query) {
            try {
                $threshold = (int) get_setting('perf_slow_query_threshold_ms', 500);
                if ($query->time < $threshold) return;
                // Don't record queries on our own table — prevents infinite loop
                if (stripos($query->sql, 'perf_slow_queries') !== false) return;
                $this->record($query->sql, $query->bindings ?? [], (float) $query->time);
            } catch (Throwable $e) {
                // Never blow up the request on our analyzer
            }
        });
    }

    public function record(string $sql, array $bindings, float $timeMs): void
    {
        $normalized = $this->normalize($sql);
        $hash = md5($normalized);
        $existing = SlowQuery::where('query_hash', $hash)->first();

        $now = now();
        if ($existing) {
            $newOcc = $existing->occurrences + 1;
            $existing->update([
                'avg_time_ms' => (int) (($existing->avg_time_ms * $existing->occurrences + $timeMs) / $newOcc),
                'max_time_ms' => (int) max($existing->max_time_ms, $timeMs),
                'occurrences' => $newOcc,
                'last_seen'   => $now,
            ]);
            return;
        }

        $explain = [];
        try {
            $explain = DB::select('EXPLAIN ' . $sql, $bindings);
            $explain = array_map(fn($r) => (array) $r, $explain);
        } catch (Throwable $e) {
            $explain = [];
        }

        SlowQuery::create([
            'query_hash'      => $hash,
            'query_text'      => mb_substr($sql, 0, 65000),
            'avg_time_ms'     => (int) $timeMs,
            'max_time_ms'     => (int) $timeMs,
            'occurrences'     => 1,
            'explain_result'  => $explain,
            'suggested_index' => $this->suggestIndex($explain),
            'first_seen'      => $now,
            'last_seen'       => $now,
        ]);
    }

    public function topSlow(int $limit = 50): \Illuminate\Support\Collection
    {
        return SlowQuery::orderByDesc('avg_time_ms')->limit($limit)->get();
    }

    public function dismiss(int $id): bool
    {
        return (bool) SlowQuery::where('id', $id)->delete();
    }

    public function clearAll(): int
    {
        return SlowQuery::count() > 0 ? SlowQuery::query()->delete() : 0;
    }

    public function scanOnce(): array
    {
        // Trigger a few common ORM patterns to surface obvious slow queries.
        // (No-op when threshold/status is off.)
        $samples = 0; $found = 0;
        if ((int) get_setting('perf_slow_query_status', 0) !== 1) {
            return ['samples' => 0, 'found' => 0, 'status' => 'disabled'];
        }
        $before = SlowQuery::count();
        $this->attachListener();
        try {
            DB::select('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = ?', [DB::connection()->getDatabaseName()]);
            $samples++;
        } catch (Throwable $e) {}
        $found = max(0, SlowQuery::count() - $before);
        return ['samples' => $samples, 'found' => $found, 'status' => 'ok'];
    }

    protected function normalize(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', $sql) ?? $sql;
        $sql = preg_replace('/\'[^\']*\'/', "'?'", $sql) ?? $sql;
        $sql = preg_replace('/"[^"]*"/', '"?"', $sql) ?? $sql;
        $sql = preg_replace('/\b\d+\b/', '?', $sql) ?? $sql;
        return trim($sql);
    }

    protected function suggestIndex(array $explain): ?string
    {
        foreach ($explain as $row) {
            $type = (string) ($row['type'] ?? '');
            $tbl  = (string) ($row['table'] ?? '');
            $rows = (int) ($row['rows'] ?? 0);
            if ($type === 'ALL' && $tbl !== '') {
                return sprintf(
                    'Consider adding an index on `%s` covering the WHERE columns (rows scanned: %d).',
                    $tbl,
                    $rows
                );
            }
        }
        return null;
    }
}
