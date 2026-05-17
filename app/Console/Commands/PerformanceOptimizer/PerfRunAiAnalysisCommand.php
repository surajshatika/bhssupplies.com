<?php

namespace App\Console\Commands\PerformanceOptimizer;

use App\AddonAi\PerformanceOptimizer\Engine\AutoFixEngine;
use App\AddonAi\PerformanceOptimizer\Engine\RecommendationEngine;
use App\Models\PerformanceOptimizer\AiRecommendation;
use Illuminate\Console\Command;

class PerfRunAiAnalysisCommand extends Command
{
    protected $signature   = 'perf:ai-analysis {--auto-apply : Apply high-confidence auto-fixable recommendations}';
    protected $description = 'Run the AI recommendation engine and optionally auto-apply high-confidence fixes';

    public function handle(RecommendationEngine $engine, AutoFixEngine $fixer): int
    {
        if ((int) get_setting('perf_ai_recs_status', 1) !== 1) {
            $this->info('AI Recommendations are disabled (perf_ai_recs_status=0). Nothing to do.');
            return self::SUCCESS;
        }

        $created = $engine->run();
        $this->info("Generated {$created} new recommendations.");

        if ($this->option('auto-apply')) {
            if ((int) get_setting('perf_ai_recs_auto_apply', 0) !== 1) {
                $this->warn('--auto-apply was passed but perf_ai_recs_auto_apply is disabled. Skipping.');
                return self::SUCCESS;
            }

            $threshold = (int) get_setting('perf_ai_recs_auto_apply_threshold', 85);
            $applied = 0; $failed = 0;
            AiRecommendation::pending()
                ->where('auto_fixable', true)
                ->where('confidence', '>=', $threshold)
                ->get()
                ->each(function ($r) use ($fixer, &$applied, &$failed) {
                    $res = $fixer->apply($r);
                    if (!empty($res['ok'])) { $applied++; } else { $failed++; }
                });
            $this->info("Auto-applied {$applied} fixes (failed: {$failed}).");
        }

        return self::SUCCESS;
    }
}
