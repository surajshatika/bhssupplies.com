<?php

namespace App\Services\Marketing;

use Carbon\Carbon;

/**
 * Multi-touch attribution against the EventStore JSONL warehouse.
 *
 * Algorithms:
 *   - first_touch    — 100% credit to the first non-direct UTM source the
 *                      visitor saw inside the window.
 *   - last_touch     — 100% credit to the last UTM source before purchase.
 *   - linear         — equal weight across all touches.
 *   - time_decay     — exponential decay; touches closer to purchase get more.
 *   - position_based — 40% first / 40% last / 20% spread across middle (Google
 *                      Ads "U-shaped").
 */
class AttributionService
{
    public function __construct(protected EventStore $store) {}

    public array $supportedModels = ['first_touch', 'last_touch', 'linear', 'time_decay', 'position_based'];

    /**
     * Build attribution table for the given window.
     * Returns:
     *   [
     *      'model' => 'last_touch',
     *      'window_days' => 30,
     *      'totals_by_source' => ['google'=>123.45, 'tiktok'=>67.89, 'direct'=>10.00, ...],
     *      'totals_by_channel'=> ['organic'=>..., 'cpc'=>..., 'social'=>...],
     *      'orders'    => 42,
     *      'revenue'   => 1234.56,
     *      'attributed'=> 1100.00,
     *      'unattributed' => 134.56,
     *   ]
     */
    public function compute(string $model = 'last_touch', int $windowDays = 30): array
    {
        if (!in_array($model, $this->supportedModels, true)) {
            $model = 'last_touch';
        }

        // 1. Walk EventStore, group all events per anon_id within window.
        $journeys = $this->buildJourneys($windowDays);

        $bySource  = [];
        $byMedium  = [];
        $byCampaign= [];
        $totalAttr = 0.0;
        $totalUn   = 0.0;
        $orders    = 0;
        $revenue   = 0.0;

        foreach ($journeys as $journey) {
            $touches  = $journey['touches'];
            $purchase = $journey['purchase'];
            if (!$purchase) continue;

            $value = (float) ($purchase['value'] ?? 0);
            $orders++;
            $revenue += $value;

            $weights = $this->weightTouches($touches, $model);
            if (empty($weights)) {
                $totalUn += $value;
                continue;
            }

            foreach ($weights as $i => $w) {
                $t = $touches[$i];
                $src = $t['utm']['source']   ?? 'direct';
                $med = $t['utm']['medium']   ?? '(none)';
                $cmp = $t['utm']['campaign'] ?? '(none)';
                $share = $value * $w;
                $bySource[$src]    = ($bySource[$src]    ?? 0) + $share;
                $byMedium[$med]    = ($byMedium[$med]    ?? 0) + $share;
                $byCampaign[$cmp]  = ($byCampaign[$cmp]  ?? 0) + $share;
                $totalAttr        += $share;
            }
        }

        arsort($bySource);
        arsort($byMedium);
        arsort($byCampaign);

        return [
            'model'             => $model,
            'window_days'       => $windowDays,
            'orders'            => $orders,
            'revenue'           => round($revenue, 2),
            'attributed'        => round($totalAttr, 2),
            'unattributed'      => round($revenue - $totalAttr, 2),
            'totals_by_source'  => array_map(fn ($v) => round($v, 2), $bySource),
            'totals_by_medium'  => array_map(fn ($v) => round($v, 2), $byMedium),
            'totals_by_campaign'=> array_map(fn ($v) => round($v, 2), $byCampaign),
        ];
    }

    /**
     * Compare all 5 models side-by-side for the dashboard.
     * Returns ['source' => [model => attributedValue]].
     */
    public function compareModels(int $windowDays = 30): array
    {
        $all = [];
        foreach ($this->supportedModels as $m) {
            $all[$m] = $this->compute($m, $windowDays);
        }

        $sources = [];
        foreach ($all as $m => $row) {
            foreach ($row['totals_by_source'] as $src => $v) {
                $sources[$src][$m] = $v;
            }
        }
        // ensure all sources have all models (zero-fill)
        foreach ($sources as $src => &$cols) {
            foreach ($this->supportedModels as $m) {
                $cols[$m] = $cols[$m] ?? 0;
            }
        }
        return ['by_model' => $all, 'compare' => $sources];
    }

    /* ============================================================
     * Internal: journey reconstruction + weighting
     * ============================================================ */

    /**
     * Walk JSONL files for the window, group events by anon_id.
     * A "journey" = list of touches + the conversion event (Purchase) if any.
     */
    protected function buildJourneys(int $windowDays): array
    {
        $byAnon = []; // anon_id => ['touches' => [...], 'purchase' => row|null]

        for ($i = $windowDays - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            foreach ($this->store->readDate($date) as $row) {
                $anon = $row['anon_id'] ?? null;
                if (!$anon) continue;

                $byAnon[$anon] ??= ['touches' => [], 'purchase' => null];

                // A "touch" is any event that has utm.source OR a non-empty referrer
                // host that isn't our own domain.
                if (!empty($row['utm']['source']) || $this->isExternalReferrer($row['referrer'] ?? null)) {
                    $byAnon[$anon]['touches'][] = $row;
                }

                if (($row['event'] ?? '') === 'Purchase') {
                    $byAnon[$anon]['purchase'] = $row;
                }
            }
        }

        // Drop journeys without a purchase
        return array_filter($byAnon, fn ($j) => $j['purchase'] !== null);
    }

    protected function isExternalReferrer(?string $ref): bool
    {
        if (!$ref) return false;
        $host = parse_url($ref, PHP_URL_HOST);
        if (!$host) return false;
        $own  = parse_url(config('app.url'), PHP_URL_HOST);
        return $host !== $own;
    }

    /** @return float[] same length as $touches summing to 1.0 (or empty if no touches) */
    protected function weightTouches(array $touches, string $model): array
    {
        $n = count($touches);
        if ($n === 0) return [];

        switch ($model) {
            case 'first_touch':
                $w = array_fill(0, $n, 0.0); $w[0] = 1.0; return $w;

            case 'last_touch':
                $w = array_fill(0, $n, 0.0); $w[$n - 1] = 1.0; return $w;

            case 'linear':
                return array_fill(0, $n, 1.0 / $n);

            case 'time_decay':
                // Half-life of 7 days from purchase.
                $purchaseTime = null;
                foreach ($touches as $t) {
                    if (!empty($t['ts'])) $purchaseTime = strtotime($t['ts']);
                }
                if (!$purchaseTime) return array_fill(0, $n, 1.0 / $n);
                $halfLifeSec = 7 * 86400;
                $raw = [];
                foreach ($touches as $t) {
                    $dt = $purchaseTime - strtotime($t['ts']);
                    $raw[] = pow(0.5, max(0, $dt) / $halfLifeSec);
                }
                $sum = array_sum($raw) ?: 1.0;
                return array_map(fn ($v) => $v / $sum, $raw);

            case 'position_based':
                if ($n === 1) return [1.0];
                if ($n === 2) return [0.5, 0.5];
                $w = array_fill(0, $n, 0.0);
                $w[0] = 0.4;
                $w[$n - 1] = 0.4;
                $mid = 0.2 / ($n - 2);
                for ($i = 1; $i < $n - 1; $i++) $w[$i] = $mid;
                return $w;

            default:
                return array_fill(0, $n, 1.0 / $n);
        }
    }
}
