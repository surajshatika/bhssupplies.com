<?php

namespace App\Services\Marketing;

use App\Models\BusinessSetting;
use Carbon\Carbon;

/**
 * Configurable conversion funnels.
 *
 * A funnel is a sequence of event names a visitor must pass through. We compute,
 * for the window, how many unique anonymous IDs reached each step in order.
 *
 * Default funnel (e-commerce):
 *   PageView → ViewContent → AddToCart → InitiateCheckout → Purchase
 *
 * Custom funnels are JSON-stored in business_setting `marketing_funnels`.
 */
class FunnelService
{
    public function __construct(protected EventStore $store) {}

    public function defaultFunnels(): array
    {
        return [
            [
                'id'    => 'ecommerce_default',
                'name'  => 'E-commerce Core',
                'steps' => ['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase'],
            ],
            [
                'id'    => 'pdp_quick_buy',
                'name'  => 'Quick Buy from PDP',
                'steps' => ['ViewContent', 'AddToCart', 'Purchase'],
            ],
            [
                'id'    => 'lead_capture',
                'name'  => 'Lead Capture',
                'steps' => ['PageView', 'Lead'],
            ],
        ];
    }

    public function all(): array
    {
        $raw = BusinessSetting::where('type', 'marketing_funnels')->value('value');
        $custom = $raw ? json_decode($raw, true) : [];
        if (!is_array($custom)) $custom = [];
        return array_values(array_merge($this->defaultFunnels(), $custom));
    }

    public function saveCustom(array $funnels): void
    {
        BusinessSetting::updateOrCreate(
            ['type' => 'marketing_funnels'],
            ['value' => json_encode(array_values($funnels))]
        );
    }

    /**
     * Compute step-by-step funnel counts (unique anonymous visitors).
     * Returns:
     *   [
     *     'funnel_id' => 'ecommerce_default',
     *     'window_days' => 30,
     *     'steps' => [
     *        ['name'=>'PageView', 'count'=>1234, 'pct_from_top'=>100, 'pct_step_to_step'=>100, 'dropoff'=>0],
     *        ['name'=>'ViewContent','count'=>620,'pct_from_top'=>50, 'pct_step_to_step'=>50, 'dropoff'=>614],
     *        ...
     *     ],
     *     'top_dropoff_step' => 'AddToCart',
     *   ]
     */
    public function compute(string $funnelId, int $windowDays = 30): array
    {
        $funnel = collect($this->all())->firstWhere('id', $funnelId);
        if (!$funnel) {
            return ['error' => "Funnel '{$funnelId}' not found."];
        }
        $steps = $funnel['steps'] ?? [];
        if (empty($steps)) return ['error' => 'Funnel has no steps.'];

        // Per anon_id: track which steps they've reached (ordered).
        $progress = []; // anon_id => max step index reached

        for ($i = $windowDays - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            foreach ($this->store->readDate($date) as $row) {
                $anon = $row['anon_id'] ?? null;
                if (!$anon) continue;

                $stepIdx = array_search($row['event'] ?? '', $steps, true);
                if ($stepIdx === false) continue;

                $current = $progress[$anon] ?? -1;
                // Only advance: visitor must hit step in order. If they hit step 3
                // without having hit steps 0..2, we don't count it as progress.
                if ($stepIdx === $current + 1) {
                    $progress[$anon] = $stepIdx;
                } elseif ($stepIdx === 0 && $current === -1) {
                    $progress[$anon] = 0;
                }
            }
        }

        // Count unique anon_ids who reached at least step N.
        $counts = array_fill(0, count($steps), 0);
        foreach ($progress as $maxStep) {
            for ($i = 0; $i <= $maxStep; $i++) $counts[$i]++;
        }

        $top = $counts[0] ?: 1;
        $rows = [];
        $maxDrop = -1;
        $topDropStep = $steps[0] ?? null;

        foreach ($steps as $i => $stepName) {
            $prevCount = $i === 0 ? $counts[$i] : $counts[$i - 1];
            $dropoff   = $i === 0 ? 0 : max(0, $prevCount - $counts[$i]);
            $pctTop    = $top > 0 ? round(($counts[$i] / $top) * 100, 1) : 0;
            $pctStep   = $prevCount > 0 ? round(($counts[$i] / $prevCount) * 100, 1) : 0;

            if ($dropoff > $maxDrop) {
                $maxDrop = $dropoff;
                $topDropStep = $i === 0 ? null : $steps[$i];
            }

            $rows[] = [
                'name'              => $stepName,
                'count'             => $counts[$i],
                'pct_from_top'      => $pctTop,
                'pct_step_to_step'  => $pctStep,
                'dropoff'           => $dropoff,
            ];
        }

        return [
            'funnel_id'        => $funnel['id'],
            'funnel_name'      => $funnel['name'],
            'window_days'      => $windowDays,
            'steps'            => $rows,
            'top_dropoff_step' => $topDropStep,
            'overall_conversion_pct' => count($rows) > 0 ? $rows[count($rows) - 1]['pct_from_top'] : 0,
        ];
    }
}
