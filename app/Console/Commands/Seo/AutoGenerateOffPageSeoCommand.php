<?php

namespace App\Console\Commands\Seo;

use App\Jobs\GenerateSeoContentJob;
use App\Models\SeoProject;
use App\Models\SeoRun;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AutoGenerateOffPageSeoCommand extends Command
{
    protected $signature = 'seo:auto-offpage-campaign
                            {--limit= : Number of URLs to create campaigns for}
                            {--provider= : AI provider override}
                            {--dry-run : Show targets without queueing runs}';

    protected $description = 'Automatically queue AI off-page SEO backlink campaigns for SEO-ready protected URLs.';

    public function handle(AiSeoBoardService $board): int
    {
        if ((int) get_setting('seo_auto_offpage_enabled', 1) !== 1 && !$this->option('dry-run')) {
            $this->info('Automatic off-page SEO campaign generation is disabled in SEO settings.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('seo_runs') || !Schema::hasTable('seo_projects')) {
            $this->warn('Required SEO tables are missing. Run migrations first.');
            return self::FAILURE;
        }

        $limit = (int) ($this->option('limit') ?: get_setting('seo_auto_offpage_batch_size', 3));
        $limit = max(1, min(10, $limit));
        $provider = $this->option('provider') ?: get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));
        $targets = $board->offPageCampaignTargetPreview($limit, ['page', 'category', 'product']);

        if ($targets->isEmpty()) {
            $this->info('No SEO-ready URLs found for off-page campaign generation.');
            $this->line('This is expected until on-page autopilot creates URLs with meta, focus keyword, schema, and SEO score 70+.');
            return self::SUCCESS;
        }

        $this->info('SEO-ready off-page campaign targets: ' . $targets->count());

        if ($this->option('dry-run')) {
            foreach ($targets as $target) {
                $this->line('- ' . $target['type'] . '#' . $target['id'] . ' [' . ($target['offpage_score'] ?? 0) . '/100] ' . $target['url']);
            }
            return self::SUCCESS;
        }

        $project = SeoProject::query()->firstOrCreate(
            ['slug' => 'default-seo-suite'],
            [
                'name' => get_setting('website_name', config('app.name')) . ' SEO Suite',
                'base_url' => url('/'),
                'default_provider' => $provider,
            ]
        );

        $queued = 0;
        foreach ($targets as $target) {
            $payload = [
                'url' => $target['url'],
                'title' => $target['title'],
                'topic' => $target['title'],
                'keyword' => $target['focus_keyword'] ?? $target['title'],
                'target_type' => $target['type'],
                'target_id' => $target['id'],
                'competitor_urls' => get_setting('seo_competitor_urls', get_setting('ai_blog_competitor_urls', '')),
            ];

            $run = SeoRun::create([
                'project_id' => $project->id,
                'module' => 'off_page',
                'feature' => 'ai_backlink_campaign',
                'provider' => $provider,
                'status' => 'queued',
                'target_type' => Arr::get($payload, 'target_type'),
                'target_id' => Arr::get($payload, 'target_id'),
                'url' => Arr::get($payload, 'url'),
                'input_payload' => $payload,
            ]);

            GenerateSeoContentJob::dispatch($run->id);
            $queued++;
        }

        $this->info('Queued ' . $queued . ' AI off-page backlink campaign run(s).');
        return self::SUCCESS;
    }
}
