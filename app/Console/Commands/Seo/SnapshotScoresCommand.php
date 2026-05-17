<?php

namespace App\Console\Commands\Seo;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\SeoProject;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Daily score snapshot — re-scores a sampled slice of every entity type and
 * persists the result to seo_score_histories AND seo_meta.seo_score so the AI
 * Board dashboards stay current.
 *
 * Designed for cron use: bounded sample sizes per type, transactional updates,
 * and per-entity exception isolation so one bad row never sinks the run.
 */
class SnapshotScoresCommand extends Command
{
    protected $signature = 'seo:snapshot-scores
                            {--sample=50 : Entities per type to score (most-recently-updated first)}
                            {--all : Score every entity (overrides --sample)}';

    protected $description = 'Re-score recent entities and persist scores into seo_score_histories + seo_meta.';

    public function handle(AiSeoBoardService $board): int
    {
        if (!Schema::hasTable('seo_meta') || !Schema::hasTable('seo_score_histories')) {
            $this->warn('Required seo_* tables are missing — run migrations first.');
            return self::FAILURE;
        }

        $sample = $this->option('all') ? null : (int) $this->option('sample');
        $project = SeoProject::query()->first();
        $projectId = optional($project)->id;

        $types = [
            'product'  => Product::class,
            'category' => Category::class,
            'page'     => Page::class,
            'blog'     => Blog::class,
        ];

        $totalScored = 0;
        $totalFailed = 0;

        foreach ($types as $type => $class) {
            if (!class_exists($class)) {
                continue;
            }

            $query = $class::query()->latest('updated_at');
            if ($sample !== null) {
                $query->take($sample);
            }

            $entities = $query->get();
            $bar = $this->output->createProgressBar($entities->count());
            $bar->setFormat("  %message% [%bar%] %current%/%max%");
            $bar->setMessage(str_pad($type, 8));
            $bar->start();

            foreach ($entities as $entity) {
                try {
                    $score = $board->scoreEntity($entity, $type);
                    SeoScoreHistory::create([
                        'project_id'  => $projectId,
                        'seo_run_id'  => null,
                        'target_type' => $class,
                        'target_id'   => $entity->getKey(),
                        'url'         => $this->urlFor($entity, $type),
                        'score'       => $score['score'],
                        'grade'       => $score['grade'],
                        'metrics'     => json_encode($score),
                        'recorded_at' => now(),
                    ]);
                    SeoMeta::updateOrCreate(
                        ['model_type' => $class, 'model_id' => $entity->getKey(), 'lang' => config('app.locale', 'en')],
                        [
                            'seo_score'        => $score['score'],
                            'seo_grade'        => $score['grade'],
                            'analysis_checks'  => $score['checks'],
                            'last_analyzed_at' => now(),
                        ]
                    );
                    $totalScored++;
                } catch (Throwable $e) {
                    $totalFailed++;
                    logger()->warning('snapshot-scores: scoring failed', [
                        'type' => $type, 'id' => $entity->getKey(), 'err' => $e->getMessage(),
                    ]);
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info("Snapshot complete. scored={$totalScored} failed={$totalFailed}");
        return self::SUCCESS;
    }

    protected function urlFor($entity, string $type): string
    {
        $slug = $entity->slug ?? '';
        $base = rtrim(url('/'), '/');
        return match ($type) {
            'product'  => $base . '/product/'  . $slug,
            'category' => $base . '/category/' . $slug,
            'page'     => $base . '/'          . $slug,
            'blog'     => $base . '/blog/'     . $slug,
        };
    }
}
