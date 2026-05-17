<?php

namespace App\Console\Commands\Seo;

use App\Jobs\Seo\PingSitemapToSearchEnginesJob;
use App\Services\Seo\Optimization\OptimizationService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use Illuminate\Console\Command;
use Throwable;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap
                            {--smart : Use SmartSitemapService (image+lastmod) instead of base generator}
                            {--no-ping : Skip the post-generation ping to Bing/Google}';

    protected $description = 'Regenerate sitemap.xml and persist it to the project root. Safe to run from cron.';

    public function handle(): int
    {
        try {
            if ($this->option('smart')) {
                $result = app(SmartSitemapService::class)->handle(['persist' => true]);
                $count  = $result['url_count'] ?? ($result['count'] ?? null);
                $this->info('Smart sitemap regenerated' . ($count !== null ? " ({$count} URLs)." : '.'));
            } else {
                $result = app(OptimizationService::class)->generateSitemap(['persist' => true]);
                $this->info('Sitemap regenerated (' . ($result['url_count'] ?? '?') . ' URLs).');
            }

            if (!$this->option('no-ping')) {
                try {
                    PingSitemapToSearchEnginesJob::dispatch();
                    $this->line('Sitemap ping dispatched to Bing/Google.');
                } catch (Throwable $e) {
                    $this->warn('Sitemap ping dispatch failed: ' . $e->getMessage());
                }
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Sitemap generation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
