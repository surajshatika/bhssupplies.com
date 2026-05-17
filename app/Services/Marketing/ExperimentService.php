<?php

namespace App\Services\Marketing;

use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Lightweight A/B testing framework.
 *
 * Experiments live as JSON in business_setting `marketing_experiments`:
 *   [
 *     {
 *       "id": "uuid",
 *       "key": "hero_cta",
 *       "name": "Hero CTA wording",
 *       "status": "running|paused|completed",
 *       "variants": [{"key":"A","weight":50},{"key":"B","weight":50}],
 *       "goal_event": "Purchase",
 *       "created_at": "...", "started_at":"..."
 *     }
 *   ]
 *
 * Assignment cookie: `mm_ab_{experimentKey}` holds the chosen variant key for 30 days.
 * Variant exposure + conversion are recorded as standard events (ExperimentExposure,
 * ExperimentConversion) in the EventStore — analytics + stat sig computed on demand.
 */
class ExperimentService
{
    public function __construct(protected EventStore $store) {}

    public function all(): array
    {
        $raw = BusinessSetting::where('type', 'marketing_experiments')->value('value');
        $arr = $raw ? json_decode($raw, true) : [];
        return is_array($arr) ? $arr : [];
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $e) if ($e['id'] === $id) return $e;
        return null;
    }

    public function findByKey(string $key): ?array
    {
        foreach ($this->all() as $e) if (($e['key'] ?? '') === $key) return $e;
        return null;
    }

    public function save(array $data): array
    {
        $all = $this->all();
        $now = Carbon::now()->toIso8601String();

        if (empty($data['id'])) {
            $data['id']         = (string) Str::uuid();
            $data['created_at'] = $now;
        }
        $data['updated_at'] = $now;
        if (($data['status'] ?? null) === 'running' && empty($data['started_at'])) {
            $data['started_at'] = $now;
        }

        // Normalise variants — strip empties, default 50/50 if not specified.
        $variants = collect($data['variants'] ?? [])
            ->filter(fn ($v) => !empty($v['key']))
            ->values()
            ->all();
        if (count($variants) < 2) {
            $variants = [['key' => 'A', 'weight' => 50], ['key' => 'B', 'weight' => 50]];
        } else {
            // Normalise weights to 100
            $totalW = array_sum(array_map(fn ($v) => (int) ($v['weight'] ?? 1), $variants)) ?: 1;
            foreach ($variants as &$v) {
                $v['weight'] = (int) round(((int) ($v['weight'] ?? 1) / $totalW) * 100);
            }
            unset($v);
        }
        $data['variants'] = $variants;

        $found = false;
        foreach ($all as $i => $e) {
            if ($e['id'] === $data['id']) { $all[$i] = $data; $found = true; break; }
        }
        if (!$found) $all[] = $data;

        $this->persist($all);
        return $data;
    }

    public function delete(string $id): bool
    {
        $all = $this->all();
        $filtered = array_values(array_filter($all, fn ($e) => $e['id'] !== $id));
        if (count($filtered) === count($all)) return false;
        $this->persist($filtered);
        return true;
    }

    /**
     * Assign a variant to the current visitor (deterministic by anon_id, sticky via cookie).
     * Returns the variant key or null if experiment not found / not running.
     */
    public function assignVariant(string $experimentKey): ?string
    {
        $exp = $this->findByKey($experimentKey);
        if (!$exp || ($exp['status'] ?? '') !== 'running') return null;

        $cookieName = 'mm_ab_' . $experimentKey;
        $existing   = request()->cookie($cookieName);
        if ($existing && collect($exp['variants'])->contains(fn ($v) => $v['key'] === $existing)) {
            return $existing;
        }

        // Deterministic hash on anon_id so same visitor → same variant across sessions
        $anon = request()->cookie('mm_anon_id') ?: request()->session()->getId();
        $hash = hexdec(substr(md5($exp['id'] . '|' . $anon), 0, 8));
        $bucket = $hash % 100;

        $cursor = 0;
        $chosen = $exp['variants'][0]['key'] ?? 'A';
        foreach ($exp['variants'] as $v) {
            $cursor += (int) ($v['weight'] ?? 0);
            if ($bucket < $cursor) { $chosen = $v['key']; break; }
        }

        // Queue cookie + record exposure event
        cookie()->queue(cookie($cookieName, $chosen, 60 * 24 * 30));
        $this->logEvent('ExperimentExposure', $exp, $chosen, 0);

        return $chosen;
    }

    public function recordConversion(string $experimentKey, ?string $variantKey = null, float $value = 0): void
    {
        $exp = $this->findByKey($experimentKey);
        if (!$exp) return;
        $variant = $variantKey ?: (request()->cookie('mm_ab_' . $experimentKey) ?: null);
        if (!$variant) return;
        $this->logEvent('ExperimentConversion', $exp, $variant, $value);
    }

    /** Append-only log dedicated to experiment events (so results() can read it). */
    protected function logEvent(string $event, array $exp, string $variant, float $value): void
    {
        try {
            $row = [
                'ts'             => Carbon::now()->toIso8601String(),
                'event'          => $event,
                'experiment_id'  => $exp['id'],
                'experiment_key' => $exp['key'],
                'variant'        => $variant,
                'value'          => $value,
                'anon_id'        => request()->cookie('mm_anon_id'),
            ];
            $dir  = storage_path('app' . DIRECTORY_SEPARATOR . 'marketing' . DIRECTORY_SEPARATOR . 'experiments');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $path = $dir . DIRECTORY_SEPARATOR . Carbon::now()->toDateString() . '.jsonl';
            file_put_contents($path, json_encode($row) . "\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) { /* never block */ }
    }

    /**
     * Compute results: per-variant exposure, conversion, conv-rate, plus z-score
     * vs control (first variant) for two-proportion test.
     */
    public function results(string $experimentId, int $windowDays = 90): array
    {
        $exp = $this->find($experimentId);
        if (!$exp) return ['error' => 'Experiment not found.'];

        $exposures   = [];
        $conversions = [];
        foreach ($exp['variants'] as $v) {
            $exposures[$v['key']]   = 0;
            $conversions[$v['key']] = 0;
        }
        $revenue = array_fill_keys(array_keys($exposures), 0.0);

        // Read the dedicated experiments log (written by logEvent())
        for ($i = $windowDays - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $logPath = storage_path('app/marketing/experiments/' . $date . '.jsonl');
            if (!is_file($logPath)) continue;
            $fh = fopen($logPath, 'r');
            if (!$fh) continue;
            try {
                while (($line = fgets($fh)) !== false) {
                    $row = json_decode(trim($line), true);
                    if (!is_array($row)) continue;
                    if (($row['experiment_id'] ?? null) !== $experimentId) continue;
                    $v = $row['variant'] ?? null;
                    if (!isset($exposures[$v])) continue;
                    if (($row['event'] ?? '') === 'ExperimentExposure')   $exposures[$v]++;
                    if (($row['event'] ?? '') === 'ExperimentConversion') {
                        $conversions[$v]++;
                        $revenue[$v] += (float) ($row['value'] ?? 0);
                    }
                }
            } finally { fclose($fh); }
        }

        $rows = [];
        $variantKeys = array_keys($exposures);
        $control = $variantKeys[0] ?? null;
        $controlExp = $exposures[$control] ?? 0;
        $controlConv= $conversions[$control] ?? 0;
        $controlP   = $controlExp > 0 ? $controlConv / $controlExp : 0;

        foreach ($variantKeys as $v) {
            $e = $exposures[$v];
            $c = $conversions[$v];
            $p = $e > 0 ? $c / $e : 0;
            $z = null;
            $sig = null;

            if ($v !== $control && $controlExp > 0 && $e > 0) {
                $pooledP = ($controlConv + $c) / ($controlExp + $e);
                $se = sqrt($pooledP * (1 - $pooledP) * (1/$controlExp + 1/$e));
                if ($se > 0) {
                    $z = ($p - $controlP) / $se;
                    $sig = abs($z) >= 1.96; // ~95% confidence
                }
            }

            $rows[] = [
                'variant'      => $v,
                'is_control'   => $v === $control,
                'exposures'    => $e,
                'conversions'  => $c,
                'revenue'      => round($revenue[$v], 2),
                'conv_rate_pct'=> $e > 0 ? round($p * 100, 2) : 0,
                'lift_pct'     => $controlP > 0 ? round((($p - $controlP) / $controlP) * 100, 1) : null,
                'z_score'      => $z !== null ? round($z, 2) : null,
                'significant'  => $sig,
            ];
        }

        return [
            'experiment'  => $exp,
            'window_days' => $windowDays,
            'rows'        => $rows,
            'winner'      => $this->detectWinner($rows),
        ];
    }

    protected function detectWinner(array $rows): ?string
    {
        $best = null; $bestRate = -1;
        foreach ($rows as $r) {
            if (($r['exposures'] ?? 0) < 100) continue; // require minimum sample
            if (!$r['is_control'] && !($r['significant'] ?? false)) continue;
            if ($r['conv_rate_pct'] > $bestRate) { $bestRate = $r['conv_rate_pct']; $best = $r['variant']; }
        }
        return $best;
    }

    protected function persist(array $all): void
    {
        BusinessSetting::updateOrCreate(
            ['type' => 'marketing_experiments'],
            ['value' => json_encode(array_values($all))]
        );
    }
}
