<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoKeyword;
use App\Services\Seo\Ranking\RankerManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckKeywordRanksCommand extends Command
{
    protected $signature = 'seo:check-keyword-ranks
                            {--limit=50 : Max keywords to check per run}
                            {--provider= : Override SERP provider (defaults to seo_rank_provider setting)}';

    protected $description = 'Re-check active keyword rankings via the configured SERP provider.';

    public function handle(): int
    {
        if (!Schema::hasTable('seo_keywords')) {
            $this->warn('seo_keywords table missing — run migrations first.');
            return self::FAILURE;
        }

        $ranker = RankerManager::make($this->option('provider'));
        if (!$ranker->isConfigured()) {
            $this->warn("Ranker '{$ranker->name()}' not configured. Skipping.");
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $defaultDomain = parse_url(url('/'), PHP_URL_HOST) ?: '';

        $keywords = SeoKeyword::query()
            ->with('competitors')
            ->where('is_active', true)
            ->orderByRaw('last_checked_at IS NULL DESC, last_checked_at ASC')
            ->limit($limit)
            ->get();

        if ($keywords->isEmpty()) {
            $this->info('No active keywords to check.');
            return self::SUCCESS;
        }

        $checked = 0;
        $errors  = 0;

        $bar = $this->output->createProgressBar($keywords->count());
        $bar->start();

        foreach ($keywords as $kw) {
            $target = $kw->target_url ?: $defaultDomain;

            try {
                $result = $ranker->rank($kw->keyword, $target, $kw->country, $kw->device);
                if ($result['error']) {
                    $errors++;
                } else {
                    if (!empty($result['found_url'])) {
                        $kw->target_url = $result['found_url'];
                    }
                    
                    // Extract advanced data from raw JSON if available
                    if (!empty($result['raw'])) {
                        $raw = $result['raw'];
                        
                        // Extract SERP Features
                        $features = [];
                        if (isset($raw['answer_box'])) $features[] = 'Featured Snippet';
                        if (isset($raw['local_results'])) $features[] = 'Local Pack';
                        if (isset($raw['related_questions'])) $features[] = 'People Also Ask';
                        if (isset($raw['knowledge_graph'])) $features[] = 'Knowledge Panel';
                        if (isset($raw['top_stories'])) $features[] = 'Top Stories';
                        if (isset($raw['shopping_results'])) $features[] = 'Shopping';
                        if (isset($raw['inline_videos'])) $features[] = 'Videos';
                        if (isset($raw['inline_images'])) $features[] = 'Images';
                        
                        $kw->serp_features = $features;
                        
                        // Check Competitors
                        if ($kw->competitors && $kw->competitors->isNotEmpty()) {
                            $organicResults = $raw['organic_results'] ?? [];
                            foreach ($kw->competitors as $competitor) {
                                $compDomain = preg_replace('/^www\./i', '', parse_url('http://' . $competitor->domain, PHP_URL_HOST) ?: $competitor->domain);
                                $compRank = 0;
                                
                                foreach ($organicResults as $orgResult) {
                                    $link = $orgResult['link'] ?? '';
                                    if ($link && stripos($link, $compDomain) !== false) {
                                        $compRank = (int) ($orgResult['position'] ?? 0);
                                        break;
                                    }
                                }
                                $competitor->recordRank($compRank);
                            }
                        }
                    }

                    $kw->recordRank((int) ($result['rank'] ?? 0));
                    $checked++;
                }
            } catch (Throwable $e) {
                $errors++;
                logger()->warning('check-keyword-ranks: exception', ['kw' => $kw->keyword, 'err' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Rank check done — checked={$checked} errors={$errors} provider={$ranker->name()}");
        return self::SUCCESS;
    }
}
