<?php

namespace App\Services\Seo\Budget;

use App\Models\SeoFixBatch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Tracks AI spend within a UTC day and enforces a daily USD cap.
 *
 * Cap source order:
 *   1. `seo_daily_budget_usd` business setting
 *   2. SEO_DAILY_BUDGET_USD env var
 *   3. Default $5
 *
 * Cap of 0 disables the guard. Negative values are clamped to 0.
 */
class SeoBudgetGuard
{
    public const CACHE_PREFIX = 'seo:budget:';

    public function dailyCapUsd(): float
    {
        $value = get_setting('seo_daily_budget_usd', env('SEO_DAILY_BUDGET_USD', 5));
        return max(0.0, (float) $value);
    }

    public function spendToday(): float
    {
        $today = CarbonImmutable::now('UTC')->toDateString();
        $key   = self::CACHE_PREFIX . $today;

        $cached = Cache::get($key);
        if ($cached !== null) {
            return (float) $cached;
        }

        try {
            $sum = (float) SeoFixBatch::query()
                ->whereDate('created_at', $today)
                ->sum('actual_cost_usd');
        } catch (Throwable $e) {
            $sum = 0.0;
        }

        Cache::put($key, $sum, 300);   // 5-minute window — batch counters move in real time
        return $sum;
    }

    public function remainingUsd(): float
    {
        $cap = $this->dailyCapUsd();
        if ($cap <= 0) {
            return PHP_FLOAT_MAX;
        }
        return max(0.0, $cap - $this->spendToday());
    }

    public function allowsAdditional(float $estimatedCostUsd): bool
    {
        $cap = $this->dailyCapUsd();
        if ($cap <= 0) {
            return true;
        }
        return ($this->spendToday() + max(0.0, $estimatedCostUsd)) <= $cap;
    }

    /** Force a refresh of today's spend cache (used after a batch finishes). */
    public function bustCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . CarbonImmutable::now('UTC')->toDateString());
    }
}
