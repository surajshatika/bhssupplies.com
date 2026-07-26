<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoKeyword;
use App\Models\SeoMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class EvergreenSeoLoopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:evergreen-loop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans keyword rankings for drops and forces the AI Autopilot to re-optimize decaying pages.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!Schema::hasTable('seo_keywords') || !Schema::hasTable('seo_meta')) {
            $this->warn('Missing required SEO tables.');
            return self::FAILURE;
        }

        $this->info('Starting Evergreen SEO Loop...');

        // 1. Find keywords that dropped in rank since last check
        // rank_current > rank_previous means the rank number went UP (e.g., from 3 to 10), which is a DROP in performance.
        $decayingKeywords = SeoKeyword::whereNotNull('rank_previous')
            ->whereNotNull('rank_current')
            ->where('rank_current', '>', 0)
            ->where('rank_previous', '>', 0)
            ->whereColumn('rank_current', '>', 'rank_previous')
            ->get();

        $this->info("Found {$decayingKeywords->count()} keywords that dropped in ranking.");

        // 2. Find keywords where a competitor is outranking us
        $outrankedKeywords = SeoKeyword::with('competitors')
            ->whereNotNull('rank_current')
            ->where('rank_current', '>', 0)
            ->get()
            ->filter(function ($kw) {
                // If a competitor's rank > 0 AND competitor rank < our rank, we are outranked!
                return $kw->competitors->contains(function ($comp) use ($kw) {
                    return $comp->rank_current > 0 && $comp->rank_current < $kw->rank_current;
                });
            });

        $this->info("Found {$outrankedKeywords->count()} keywords where competitors are outranking us.");

        // Merge both collections for processing
        $keywordsToProcess = $decayingKeywords->merge($outrankedKeywords)->unique('id');

        $rescoreCount = 0;

        foreach ($keywordsToProcess as $kw) {
            // Find the SeoMeta entry that targets this keyword
            $meta = SeoMeta::where('focus_keyword', trim($kw->keyword))
                ->where('seo_score', '>=', 80)
                ->first();

            if ($meta) {
                $newScore = 75; 
                $reasons = $meta->score_reasons ?? [];

                // Determine why we are rescoring this
                if ($decayingKeywords->contains('id', $kw->id)) {
                    $reasons['decay'] = "Content Decay: Google rank dropped from {$kw->rank_previous} to {$kw->rank_current}. Retargeting needed.";
                }

                if ($outrankedKeywords->contains('id', $kw->id)) {
                    // Find the best competitor
                    $bestComp = $kw->competitors->where('rank_current', '>', 0)->sortBy('rank_current')->first();
                    if ($bestComp) {
                        $reasons['semantic_gap'] = "Competitor Gap: {$bestComp->domain} is ranking #{$bestComp->rank_current} vs our #{$kw->rank_current}. Needs Semantic Gap Injection.";
                        // Save the competitor domain so AiSeoBoardService can use it
                        $meta->analysis_checks = array_merge($meta->analysis_checks ?? [], [
                            'target_competitor_domain' => $bestComp->domain
                        ]);
                    }
                }

                $meta->seo_score = $newScore;
                $meta->score_reasons = $reasons;
                $meta->save();

                $this->info("Rescored '{$kw->keyword}' entity (Type: {$meta->model_type}, ID: {$meta->model_id}) to {$newScore} due to rank decay or competitor gap.");
                $rescoreCount++;
            }
        }

        $this->info("Evergreen Loop completed. {$rescoreCount} pages pushed back to Autopilot queue for AI improvement.");
        return self::SUCCESS;
    }
}
