<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoProject;
use App\Models\SeoRun;
use App\Services\Seo\Board\AiSeoBoardService;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\PostIndexStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AutoCheckIndexCoverageCommand extends Command
{
    protected $signature = 'seo:auto-index-coverage
                            {--limit= : Maximum SEO-ready URLs to verify}
                            {--skip-indexnow : Do not resubmit confirmed non-indexed URLs}
                            {--dry-run : Show the verification plan without contacting Google or IndexNow}';

    protected $description = 'Verify protected SEO-ready URL indexing through Google Custom Search and optionally resubmit confirmed gaps through IndexNow.';

    public function handle(AiSeoBoardService $board, PostIndexStatusService $indexStatus): int
    {
        if ((int) get_setting('seo_auto_index_coverage_enabled', 1) !== 1 && !$this->option('dry-run')) {
            $this->info('Automatic index coverage verification is disabled in SEO settings.');
            return self::SUCCESS;
        }

        $limit = max(1, min(50, (int) ($this->option('limit') ?: get_setting('seo_auto_index_coverage_limit', 20))));
        $willResubmit = (int) get_setting('seo_auto_indexnow', 0) === 1 && !$this->option('skip-indexnow');
        $urls = collect([url('/')])
            ->merge($board->offPageCampaignTargetPreview($limit, ['page', 'category', 'product'])->pluck('url'))
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();

        if ($this->option('dry-run')) {
            $this->info('Index coverage verification plan:');
            $this->line('- Google Custom Search API: ' . ($indexStatus->isApiConfigured() ? 'configured' : 'setup required'));
            $this->line('- SEO-ready URLs selected: ' . count($urls));
            $this->line('- AI advice: skipped for cron');
            $this->line('- Confirmed non-indexed URL resubmission: ' . ($willResubmit ? 'IndexNow enabled' : 'skipped'));
            return self::SUCCESS;
        }

        $run = $this->createRun($urls, $willResubmit);

        try {
            $result = $indexStatus->handle([
                'urls' => $urls,
                'domain' => parse_url(url('/'), PHP_URL_HOST),
                'require_api' => true,
                'generate_advice' => false,
            ]);

            if (!empty($result['skipped'])) {
                $this->warn($result['message'] ?? 'Index coverage verification skipped because API configuration is incomplete.');
            } else {
                $this->info('Index coverage checked: ' . $result['indexed'] . ' indexed; ' . $result['not_indexed'] . ' not indexed; ' . $result['errors'] . ' errors.');
            }

            $notIndexedUrls = collect($result['results'] ?? [])
                ->where('status', 'not_indexed')
                ->pluck('url')
                ->filter()
                ->values()
                ->all();

            if ($willResubmit && !empty($notIndexedUrls)) {
                $result['indexnow'] = app(IndexNowService::class)->handle(['urls' => $notIndexedUrls]);
                $this->info('IndexNow resubmission attempted for ' . count($notIndexedUrls) . ' confirmed non-indexed URL(s).');
            }

            if ($run) {
                $run->update([
                    'status' => 'completed',
                    'result_payload' => $result,
                    'completed_at' => now(),
                ]);
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($run) {
                $run->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            $this->error('Index coverage verification failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function createRun(array $urls, bool $willResubmit): ?SeoRun
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
            'feature' => 'index_coverage',
            'provider' => 'google_custom_search',
            'status' => 'processing',
            'url' => url('/'),
            'started_at' => now(),
            'input_payload' => [
                'automation' => true,
                'url_count' => count($urls),
                'indexnow_resubmit' => $willResubmit,
            ],
        ]);
    }
}
