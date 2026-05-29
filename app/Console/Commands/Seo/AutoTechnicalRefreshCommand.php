<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoProject;
use App\Models\SeoRun;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\LlmsTxtService;
use App\Services\Seo\Optimization\Features\RssContentService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use App\Services\Seo\Optimization\OptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AutoTechnicalRefreshCommand extends Command
{
    protected $signature = 'seo:auto-technical-refresh
                            {--sample=80 : Recent entities per type to rescore}
                            {--skip-indexnow : Skip IndexNow submission even when enabled}
                            {--dry-run : Show the automation plan without writing files or queueing pings}';

    protected $description = 'Automatically refresh technical SEO artifacts: sitemap, robots, llms.txt, RSS, score snapshots, and IndexNow.';

    public function handle(): int
    {
        if ((int) get_setting('seo_auto_optimization_enabled', 1) !== 1 && !$this->option('dry-run')) {
            $this->info('Automatic technical SEO optimization is disabled in SEO settings.');
            return self::SUCCESS;
        }

        $sample = max(1, min(500, (int) $this->option('sample')));
        $willIndexNow = (int) get_setting('seo_auto_indexnow', 0) === 1 && !$this->option('skip-indexnow');

        $plan = [
            'Smart XML sitemap',
            'Robots.txt',
            'LLMs.txt',
            'RSS feed',
            'SEO score snapshot sample=' . $sample,
            $willIndexNow ? 'IndexNow ping' : 'IndexNow skipped',
        ];

        if ($this->option('dry-run')) {
            $this->info('Technical SEO refresh plan:');
            foreach ($plan as $item) {
                $this->line('- ' . $item);
            }
            return self::SUCCESS;
        }

        $run = $this->createRun($sample, $willIndexNow);
        $results = [];

        try {
            $results['smart_sitemap'] = app(SmartSitemapService::class)->handle(['persist' => true]);
            $this->info('Smart sitemap refreshed.');

            $results['robots'] = app(OptimizationService::class)->optimizeRobotsTxt(['persist' => true]);
            $this->info('Robots.txt refreshed.');

            $results['llms_txt'] = app(LlmsTxtService::class)->handle([
                'persist' => true,
                'site_name' => get_setting('website_name', config('app.name')),
                'site_url' => url('/'),
                'description' => 'Canada-focused ecommerce site serving Mississauga, Brampton, Toronto, and the GTA.',
            ]);
            $this->info('LLMs.txt refreshed.');

            $results['rss'] = app(RssContentService::class)->handle([
                'persist' => true,
                'feed_type' => 'products',
                'item_count' => 30,
            ]);
            $this->info('RSS feed refreshed.');

            if (Schema::hasTable('seo_meta') && Schema::hasTable('seo_score_histories')) {
                $exitCode = Artisan::call('seo:snapshot-scores', ['--sample' => $sample]);
                $results['score_snapshot'] = [
                    'exit_code' => $exitCode,
                    'output' => Str::limit(trim(Artisan::output()), 2000),
                ];
                $this->info('SEO score snapshot completed.');
            } else {
                $results['score_snapshot'] = ['skipped' => 'seo_meta or seo_score_histories table missing'];
                $this->warn('SEO score snapshot skipped because required tables are missing.');
            }

            if ($willIndexNow) {
                $results['indexnow'] = app(IndexNowService::class)->handle([
                    'urls' => array_values(array_unique([
                        url('/'),
                        url('/sitemap.xml'),
                        url('/robots.txt'),
                        url('/rss.xml'),
                        url('/llms.txt'),
                    ])),
                ]);
                $this->info('IndexNow submission attempted.');
            }

            if ($run) {
                $run->update([
                    'status' => 'completed',
                    'result_payload' => $results,
                    'completed_at' => now(),
                ]);
            }

            $this->info('Automated technical SEO refresh completed.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($run) {
                $run->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'result_payload' => $results,
                    'completed_at' => now(),
                ]);
            }

            $this->error('Automated technical SEO refresh failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function createRun(int $sample, bool $willIndexNow): ?SeoRun
    {
        if (!Schema::hasTable('seo_runs') || !Schema::hasTable('seo_projects')) {
            return null;
        }

        $project = SeoProject::query()->firstOrCreate(
            ['slug' => 'default-seo-suite'],
            [
                'name' => get_setting('website_name', config('app.name')) . ' SEO Suite',
                'base_url' => url('/'),
                'default_provider' => get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai')),
            ]
        );

        return SeoRun::create([
            'project_id' => $project->id,
            'module' => 'optimization',
            'feature' => 'technical_refresh',
            'provider' => get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai')),
            'status' => 'processing',
            'url' => url('/'),
            'started_at' => now(),
            'input_payload' => [
                'sample' => $sample,
                'indexnow' => $willIndexNow,
                'automation' => true,
            ],
        ]);
    }
}
