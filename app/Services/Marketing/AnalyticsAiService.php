<?php

namespace App\Services\Marketing;

use App\Services\Seo\Providers\SeoAiProviderInterface;
use App\Services\Seo\Providers\SeoProviderManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered analytics insights — daily summary, forecast, anomaly detection,
 * and natural-language query. Re-uses the SEO provider manager (Claude / OpenAI
 * / Gemini / Grok) so credentials live in one place.
 */
class AnalyticsAiService
{
    protected string $insightsDir = 'marketing/insights';

    public function __construct(protected EventStore $store) {}

    protected function provider(): SeoAiProviderInterface
    {
        $name = get_setting('marketing_ai_provider')
            ?: get_setting('seo_default_provider')
            ?: 'openai';

        return SeoProviderManager::make($name);
    }

    /**
     * Generate (or read cached) daily AI insight: top wins, anomalies, recommended actions.
     * Cached for 6 hours to avoid burning AI tokens on every dashboard refresh.
     */
    public function dailySummary(?string $date = null): array
    {
        $date  = $date ?: Carbon::yesterday()->toDateString();
        $cache = 'marketing.ai.daily.' . $date;

        return Cache::remember($cache, now()->addHours(6), function () use ($date) {
            $aggregates = $this->store->loadRecentAggregates(8); // last 8 days for context
            if (empty($aggregates)) {
                return $this->emptyState('No event data available yet. Recordings will start as soon as visitors hit the site.');
            }

            $today = end($aggregates) ?: [];
            $prior = array_slice($aggregates, 0, -1, true);

            $prompt = $this->buildSummaryPrompt($today, $prior);
            $system = "You are a senior e-commerce marketing analyst. Respond in valid compact JSON only — no prose outside JSON.";
            $raw = $this->provider()->generate($prompt, $system, ['temperature' => 0.3]);

            $parsed = $this->extractJson($raw);
            if (!$parsed) {
                return $this->emptyState('AI summary could not be parsed. Check provider configuration.');
            }

            $payload = [
                'generated_at' => Carbon::now()->toIso8601String(),
                'date'         => $date,
                'provider'     => $this->provider()->getName(),
                'summary'      => $parsed,
                'metrics'      => $today,
            ];

            $insPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->insightsDir) . DIRECTORY_SEPARATOR . $date . '.json');
            if (!is_dir(dirname($insPath))) @mkdir(dirname($insPath), 0775, true);
            file_put_contents($insPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $payload;
        });
    }

    /** Forecast next N days of revenue + visitors via simple AI extrapolation. */
    public function forecast(int $horizonDays = 7): array
    {
        $cache = 'marketing.ai.forecast.' . $horizonDays;
        return Cache::remember($cache, now()->addHours(6), function () use ($horizonDays) {
            $aggregates = $this->store->loadRecentAggregates(30);
            if (count($aggregates) < 3) {
                return ['error' => 'Need at least 3 days of data to forecast.'];
            }

            $series = [];
            foreach ($aggregates as $d => $row) {
                $series[] = [
                    'date'     => $d,
                    'revenue'  => $row['revenue']  ?? 0,
                    'visitors' => $row['unique_visitors'] ?? 0,
                    'orders'   => $row['purchases'] ?? 0,
                ];
            }

            $prompt = "Below is a 30-day daily series of revenue, visitors, orders for an e-commerce site:\n\n"
                . json_encode($series)
                . "\n\nForecast the next {$horizonDays} days as a JSON array of objects with keys: date (YYYY-MM-DD), revenue (number), visitors (integer), orders (integer), confidence (low|medium|high). Use simple trend extrapolation and respect weekly seasonality. Return ONLY the JSON array, no prose.";

            $raw = $this->provider()->generate($prompt, null, ['temperature' => 0.2]);
            $parsed = $this->extractJson($raw);
            if (!is_array($parsed)) {
                return ['error' => 'Forecast parsing failed.'];
            }
            return ['forecast' => $parsed, 'horizon_days' => $horizonDays, 'provider' => $this->provider()->getName()];
        });
    }

    /**
     * Day-over-day + week-over-week anomaly detection — flags any metric that
     * moves more than threshold percent (default 25%).
     */
    public function detectAnomalies(float $threshold = 0.25): array
    {
        $aggregates = $this->store->loadRecentAggregates(8);
        if (count($aggregates) < 2) return [];

        $arr  = array_values($aggregates);
        $today= end($arr);
        $yest = $arr[count($arr) - 2] ?? null;
        $weekAgo = $arr[0] ?? null; // 8 days back if we have full series

        $anomalies = [];
        $metrics = ['revenue', 'unique_visitors', 'purchases', 'add_to_cart'];

        foreach ($metrics as $m) {
            $tv = (float) ($today[$m] ?? 0);

            if ($yest) {
                $yv = (float) ($yest[$m] ?? 0);
                $delta = $yv > 0 ? ($tv - $yv) / $yv : ($tv > 0 ? 1 : 0);
                if (abs($delta) >= $threshold) {
                    $anomalies[] = [
                        'metric'      => $m,
                        'period'      => 'day_over_day',
                        'change_pct'  => round($delta * 100, 1),
                        'direction'   => $delta > 0 ? 'up' : 'down',
                        'today'       => $tv,
                        'previous'    => $yv,
                    ];
                }
            }
            if ($weekAgo && $weekAgo !== $today) {
                $wv = (float) ($weekAgo[$m] ?? 0);
                $delta = $wv > 0 ? ($tv - $wv) / $wv : ($tv > 0 ? 1 : 0);
                if (abs($delta) >= $threshold) {
                    $anomalies[] = [
                        'metric'      => $m,
                        'period'      => 'week_over_week',
                        'change_pct'  => round($delta * 100, 1),
                        'direction'   => $delta > 0 ? 'up' : 'down',
                        'today'       => $tv,
                        'previous'    => $wv,
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Natural-language query against the analytics warehouse. Ships a small
     * context (last 30 days aggregates) + the user question to the AI.
     */
    public function answerQuestion(string $question): array
    {
        $aggregates = $this->store->loadRecentAggregates(30);
        if (empty($aggregates)) {
            return ['answer' => 'No analytics data yet — ask again after visitors have browsed the site.', 'data' => null];
        }

        $system = "You are a marketing analyst. Answer the user's question using ONLY the provided daily marketing aggregates. If the data is insufficient, say so clearly. Keep answer under 120 words. Cite specific numbers and dates.";
        $prompt = "DATA (last 30 daily aggregates as JSON):\n"
            . json_encode($aggregates)
            . "\n\nQUESTION: " . $question;

        $answer = $this->provider()->generate($prompt, $system, ['temperature' => 0.2]);

        return [
            'answer'   => trim((string) $answer) ?: 'No answer returned by the AI provider.',
            'provider' => $this->provider()->getName(),
        ];
    }

    /* ============================================================
     * Helpers
     * ============================================================ */

    protected function buildSummaryPrompt(array $today, array $prior): string
    {
        $context = json_encode($prior, JSON_UNESCAPED_UNICODE);
        $todayJ  = json_encode($today, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You analyse e-commerce marketing performance.

PRIOR 7 DAYS (oldest → newest, as JSON):
{$context}

TODAY:
{$todayJ}

Return a strict JSON object with these keys exactly:
{
  "headline": "single sentence, <=120 chars, summarises the day",
  "wins": ["bullet", ...] — up to 3 positive findings,
  "concerns": ["bullet", ...] — up to 3 issues,
  "anomalies": ["bullet", ...] — sudden changes vs prior 7 days,
  "recommended_actions": [{"action":"...", "why":"...", "impact":"low|medium|high"}, ...] — up to 5,
  "confidence": "low|medium|high"
}
PROMPT;
    }

    protected function extractJson(?string $raw)
    {
        if (!$raw) return null;
        $clean = trim((string) $raw);
        $clean = preg_replace('/^```(json)?\s*|\s*```$/m', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if ($decoded !== null) return $decoded;

        // Fallback: find first { ... } block
        if (preg_match('/\{[\s\S]*\}/', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if ($decoded !== null) return $decoded;
        }
        return null;
    }

    protected function emptyState(string $msg): array
    {
        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'date'         => Carbon::yesterday()->toDateString(),
            'provider'     => 'none',
            'summary'      => [
                'headline'  => $msg,
                'wins'      => [],
                'concerns'  => [],
                'anomalies' => [],
                'recommended_actions' => [],
                'confidence' => 'low',
            ],
            'metrics' => [],
        ];
    }
}
