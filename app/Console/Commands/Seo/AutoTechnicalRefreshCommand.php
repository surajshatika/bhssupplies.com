<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoProject;
use App\Models\SeoRun;
use App\Services\Seo\Optimization\Features\GoogleNewsSitemapService;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\LlmsTxtService;
use App\Services\Seo\Optimization\Features\RssContentService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use App\Services\Seo\Optimization\Features\VideoSitemapService;
use App\Services\Seo\Optimization\Features\WebmasterToolsService;
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

    protected $description = 'Automatically refresh technical SEO artifacts and safe audits: sitemaps, robots, llms.txt, RSS, local SEO, webmaster, canonical, redirects, score snapshots, and IndexNow.';

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
            'Video sitemap',
            'Google News sitemap',
            'Robots.txt',
            'LLMs.txt',
            'RSS feed',
            'Canonical homepage audit',
            'Redirect chain audit',
            'Local SEO snapshot',
            'Webmaster verification snapshot',
            'SEO revisions snapshot',
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
        $failures = [];
        $requiredFailures = [];

        try {
            $optimization = app(OptimizationService::class);

            $this->captureFeature($results, $failures, $requiredFailures, 'smart_sitemap', 'Smart sitemap refreshed.', function () {
                return app(SmartSitemapService::class)->handle(['persist' => true]);
            }, true);

            $this->captureFeature($results, $failures, $requiredFailures, 'video_sitemap', 'Video sitemap refreshed.', function () {
                return app(VideoSitemapService::class)->handle(['persist' => true]);
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'news_sitemap', 'Google News sitemap refreshed.', function () {
                return app(GoogleNewsSitemapService::class)->handle(['persist' => true]);
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'robots', 'Robots.txt refreshed.', function () use ($optimization) {
                return $optimization->optimizeRobotsTxt(['persist' => true]);
            }, true);

            $this->captureFeature($results, $failures, $requiredFailures, 'llms_txt', 'LLMs.txt refreshed.', function () {
                return app(LlmsTxtService::class)->handle([
                    'persist' => true,
                    'site_name' => get_setting('website_name', config('app.name')),
                    'site_url' => url('/'),
                    'description' => 'Canada-focused ecommerce site serving Mississauga, Brampton, Toronto, and the GTA.',
                ]);
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'rss', 'RSS feed refreshed.', function () {
                return app(RssContentService::class)->handle([
                    'persist' => true,
                    'feed_type' => 'products',
                    'item_count' => 30,
                ]);
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'canonical_home', 'Canonical homepage audit completed.', function () use ($optimization) {
                return $optimization->buildCanonicalUrl(['url' => url('/')]);
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'redirect_audit', 'Redirect chain audit completed.', function () use ($optimization) {
                return $optimization->analyzeRedirectChains();
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'local_seo', 'Local SEO snapshot completed.', function () use ($optimization) {
                return $optimization->optimizeLocalSeo();
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'webmaster_tools', 'Webmaster verification snapshot completed.', function () {
                return app(WebmasterToolsService::class)->handle([]);
            });

            $this->captureFeature($results, $failures, $requiredFailures, 'seo_revisions', 'SEO revisions snapshot completed.', function () use ($optimization) {
                return $optimization->runSeoRevisions(['action' => 'list']);
            });

            if (Schema::hasTable('seo_meta') && Schema::hasTable('seo_score_histories')) {
                $this->captureFeature($results, $failures, $requiredFailures, 'score_snapshot', 'SEO score snapshot completed.', function () use ($sample) {
                    $exitCode = Artisan::call('seo:snapshot-scores', ['--sample' => $sample]);
                    return [
                        'exit_code' => $exitCode,
                        'output' => Str::limit(trim(Artisan::output()), 2000),
                    ];
                });
            } else {
                $results['score_snapshot'] = ['skipped' => 'seo_meta or seo_score_histories table missing'];
                $this->warn('SEO score snapshot skipped because required tables are missing.');
            }

            if ($willIndexNow) {
                $this->captureFeature($results, $failures, $requiredFailures, 'indexnow', 'IndexNow submission attempted.', function () {
                    return app(IndexNowService::class)->handle([
                        'urls' => array_values(array_unique([
                            url('/'),
                            url('/sitemap.xml'),
                            url('/sitemap-index.xml'),
                            url('/video-sitemap.xml'),
                            url('/news-sitemap.xml'),
                            url('/robots.txt'),
                            url('/rss.xml'),
                            url('/llms.txt'),
                        ])),
                    ]);
                });
            }

            $results['_summary'] = [
                'status' => empty($requiredFailures) ? 'completed' : 'failed',
                'feature_failures' => $failures,
            ];

            if ($run) {
                $run->update([
                    'status' => empty($requiredFailures) ? 'completed' : 'failed',
                    'error_message' => empty($failures) ? null : implode(' | ', $failures),
                    'result_payload' => $results,
                    'completed_at' => now(),
                ]);
            }

            if (!empty($requiredFailures)) {
                $this->error('Automated technical SEO refresh failed: ' . implode(' | ', $requiredFailures));
                return self::FAILURE;
            }

            if (!empty($failures)) {
                $this->warn('Automated technical SEO refresh completed with optional feature warnings.');
                return self::SUCCESS;
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

    protected function captureFeature(
        array &$results,
        array &$failures,
        array &$requiredFailures,
        string $key,
        string $successMessage,
        callable $callback,
        bool $required = false
    ): void {
        try {
            $results[$key] = $callback();
            $this->info($successMessage);
        } catch (Throwable $e) {
            $message = $key . ': ' . $e->getMessage();
            $results[$key] = ['status' => 'failed', 'error' => $e->getMessage()];
            $failures[] = $message;
            if ($required) {
                $requiredFailures[] = $message;
            }
            $this->warn(ucwords(str_replace('_', ' ', $key)) . ' failed: ' . $e->getMessage());
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
