<?php

namespace App\Console\Commands;

use App\Models\SeoProject;
use App\Services\Seo\Optimization\OptimizationService;
use Illuminate\Console\Command;

class RunWeeklySeoSnapshot extends Command
{
    protected $signature = 'seo:weekly-snapshot';

    protected $description = 'Capture weekly SEO health snapshots and refresh sitemap/robots artifacts.';

    public function handle()
    {
        $service = app(OptimizationService::class);
        $project = SeoProject::query()->first();

        $service->generateSitemap(['persist' => true]);
        $service->optimizeRobotsTxt(['persist' => true]);
        $rows = $service->snapshotProjectScores($project);

        $this->info('SEO snapshot completed for '.count($rows).' URLs.');

        return 0;
    }
}
