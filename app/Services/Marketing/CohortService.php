<?php

namespace App\Services\Marketing;

use Carbon\Carbon;

/**
 * Cohort retention analysis.
 *
 * For each "first-seen month" of anonymous visitors, computes the percent who
 * returned in each subsequent month, producing a triangular heatmap matrix.
 */
class CohortService
{
    public function __construct(protected EventStore $store) {}

    /**
     * @param int $months number of historical months to include (max 12)
     * @return array {
     *   cohorts: ['YYYY-MM' => ['size' => int, 'retention' => [m0_pct, m1_pct, m2_pct, ...]]],
     *   month_labels: ['+0','+1','+2',...,'+(months-1)']
     * }
     */
    public function compute(int $months = 6): array
    {
        $months = max(1, min(12, $months));

        // Walk every JSONL file in the last N months. Build:
        //   firstSeen[anon_id] = YYYY-MM
        //   active[anon_id][YYYY-MM] = true
        $firstSeen = [];
        $active    = [];

        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        for ($cursor = $startDate->copy(); $cursor <= Carbon::now(); $cursor->addDay()) {
            $date = $cursor->toDateString();
            foreach ($this->store->readDate($date) as $row) {
                $anon = $row['anon_id'] ?? null;
                if (!$anon) continue;
                $ym = Carbon::parse($row['ts'])->format('Y-m');

                if (!isset($firstSeen[$anon])) $firstSeen[$anon] = $ym;
                $active[$anon][$ym] = true;
            }
        }

        // Bucket anon_ids by first-seen month.
        $cohorts = [];
        foreach ($firstSeen as $anon => $cohortMonth) {
            $cohorts[$cohortMonth][] = $anon;
        }
        ksort($cohorts);

        // For each cohort, compute retention[i] = % of size active in month i.
        $matrix = [];
        foreach ($cohorts as $cohortMonth => $members) {
            $size = count($members);
            $row  = ['size' => $size, 'retention' => []];

            for ($i = 0; $i < $months; $i++) {
                $targetMonth = Carbon::parse($cohortMonth . '-01')->addMonths($i)->format('Y-m');
                if (Carbon::parse($targetMonth . '-01')->gt(Carbon::now()->endOfMonth())) break;

                $returners = 0;
                foreach ($members as $anon) {
                    if (!empty($active[$anon][$targetMonth])) $returners++;
                }
                $row['retention'][] = $size > 0 ? round(($returners / $size) * 100, 1) : 0;
            }

            $matrix[$cohortMonth] = $row;
        }

        $labels = [];
        for ($i = 0; $i < $months; $i++) $labels[] = '+' . $i;

        return [
            'cohorts'       => $matrix,
            'month_labels'  => $labels,
            'total_unique'  => count($firstSeen),
        ];
    }
}
