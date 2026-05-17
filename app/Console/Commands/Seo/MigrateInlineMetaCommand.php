<?php

namespace App\Console\Commands\Seo;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MigrateInlineMetaCommand extends Command
{
    protected $signature = 'seo:migrate-inline-meta
                            {--types=product,category,page,blog : Comma-separated entity types to backfill}
                            {--chunk=200 : Rows per chunk}
                            {--overwrite : Replace existing seo_meta rows instead of skipping}
                            {--dry-run : Show counts without writing}';

    protected $description = 'Backfill polymorphic seo_meta rows from inline meta_* columns on products/categories/pages/blogs.';

    public function handle(): int
    {
        if (!Schema::hasTable('seo_meta')) {
            $this->error('seo_meta table is missing. Run migrations first: php artisan migrate');
            return self::FAILURE;
        }

        $types = array_filter(array_map('trim', explode(',', $this->option('types'))));
        $chunk = max(50, (int) $this->option('chunk'));
        $dry   = (bool) $this->option('dry-run');
        $over  = (bool) $this->option('overwrite');
        $lang  = config('app.locale', 'en');

        $totals = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($types as $type) {
            $this->newLine();
            $this->info(">> Processing {$type}...");

            try {
                match ($type) {
                    'product'  => $this->backfillProducts($lang, $chunk, $dry, $over, $totals),
                    'category' => $this->backfillCategories($lang, $chunk, $dry, $over, $totals),
                    'page'     => $this->backfillPages($lang, $chunk, $dry, $over, $totals),
                    'blog'     => $this->backfillBlogs($lang, $chunk, $dry, $over, $totals),
                    default    => $this->warn("Unknown type: {$type}"),
                };
            } catch (Throwable $e) {
                $this->error("Failed on {$type}: {$e->getMessage()}");
                $totals['errors']++;
            }
        }

        $this->newLine();
        $this->info('── Summary ──');
        $this->line("  created:  {$totals['created']}");
        $this->line("  updated:  {$totals['updated']}");
        $this->line("  skipped:  {$totals['skipped']}");
        $this->line("  errors:   {$totals['errors']}");
        $this->line('  mode:     ' . ($dry ? 'DRY RUN (no writes)' : 'WRITE'));

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function backfillProducts(string $lang, int $chunk, bool $dry, bool $over, array &$totals): void
    {
        if (!Schema::hasTable('products')) {
            $this->warn('  products table missing — skipped.');
            return;
        }

        $columns = $this->pickColumns('products', [
            'id', 'meta_title', 'meta_description', 'meta_keywords',
            'meta_img', 'thumbnail_img',
        ]);

        $bar = $this->output->createProgressBar((int) DB::table('products')->count());
        $bar->start();

        Product::query()
            ->select($columns)
            ->chunkById($chunk, function ($rows) use ($lang, $dry, $over, &$totals, $bar) {
                foreach ($rows as $p) {
                    $this->writeMeta(
                        type: Product::class,
                        id: $p->id,
                        lang: $lang,
                        data: [
                            'meta_title'       => $p->meta_title ?? null,
                            'meta_description' => $p->meta_description ?? null,
                            'secondary_keywords' => $this->splitKeywords($p->meta_keywords ?? null),
                            'og_image'         => $this->resolveImage(($p->meta_img ?? null) ?: ($p->thumbnail_img ?? null)),
                            'twitter_image'    => $this->resolveImage(($p->meta_img ?? null) ?: ($p->thumbnail_img ?? null)),
                            'og_type'          => 'product',
                        ],
                        dry: $dry,
                        over: $over,
                        totals: $totals
                    );
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }

    protected function pickColumns(string $table, array $wanted): array
    {
        return array_values(array_filter($wanted, fn($c) => Schema::hasColumn($table, $c)));
    }

    protected function backfillCategories(string $lang, int $chunk, bool $dry, bool $over, array &$totals): void
    {
        if (!Schema::hasTable('categories')) {
            $this->warn('  categories table missing — skipped.');
            return;
        }

        $bar = $this->output->createProgressBar((int) DB::table('categories')->count());
        $bar->start();

        $columns = $this->pickColumns('categories', ['id', 'meta_title', 'meta_description', 'banner']);

        Category::query()
            ->select($columns)
            ->chunkById($chunk, function ($rows) use ($lang, $dry, $over, &$totals, $bar) {
                foreach ($rows as $c) {
                    $this->writeMeta(
                        type: Category::class,
                        id: $c->id,
                        lang: $lang,
                        data: [
                            'meta_title'       => $c->meta_title ?? null,
                            'meta_description' => $c->meta_description ?? null,
                            'og_image'         => $this->resolveImage($c->banner ?? null),
                            'twitter_image'    => $this->resolveImage($c->banner ?? null),
                        ],
                        dry: $dry,
                        over: $over,
                        totals: $totals
                    );
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }

    protected function backfillPages(string $lang, int $chunk, bool $dry, bool $over, array &$totals): void
    {
        if (!Schema::hasTable('pages')) {
            $this->warn('  pages table missing — skipped.');
            return;
        }

        $bar = $this->output->createProgressBar((int) DB::table('pages')->count());
        $bar->start();

        $columns = $this->pickColumns('pages', ['id', 'meta_title', 'meta_description', 'keywords', 'meta_image']);

        Page::query()
            ->select($columns)
            ->chunkById($chunk, function ($rows) use ($lang, $dry, $over, &$totals, $bar) {
                foreach ($rows as $p) {
                    $this->writeMeta(
                        type: Page::class,
                        id: $p->id,
                        lang: $lang,
                        data: [
                            'meta_title'       => $p->meta_title ?? null,
                            'meta_description' => $p->meta_description ?? null,
                            'secondary_keywords' => $this->splitKeywords($p->keywords ?? null),
                            'og_image'         => ($p->meta_image ?? null) ?: null,
                            'twitter_image'    => ($p->meta_image ?? null) ?: null,
                            'og_type'          => 'article',
                        ],
                        dry: $dry,
                        over: $over,
                        totals: $totals
                    );
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }

    protected function backfillBlogs(string $lang, int $chunk, bool $dry, bool $over, array &$totals): void
    {
        if (!Schema::hasTable('blogs')) {
            $this->warn('  blogs table missing — skipped.');
            return;
        }

        $bar = $this->output->createProgressBar((int) DB::table('blogs')->count());
        $bar->start();

        $columns = $this->pickColumns('blogs', ['id', 'meta_title', 'meta_description', 'meta_keywords', 'meta_img', 'banner']);

        Blog::query()
            ->select($columns)
            ->chunkById($chunk, function ($rows) use ($lang, $dry, $over, &$totals, $bar) {
                foreach ($rows as $b) {
                    $this->writeMeta(
                        type: Blog::class,
                        id: $b->id,
                        lang: $lang,
                        data: [
                            'meta_title'       => $b->meta_title ?? null,
                            'meta_description' => $b->meta_description ?? null,
                            'secondary_keywords' => $this->splitKeywords($b->meta_keywords ?? null),
                            'og_image'         => $this->resolveImage(($b->meta_img ?? null) ?: ($b->banner ?? null)),
                            'twitter_image'    => $this->resolveImage(($b->meta_img ?? null) ?: ($b->banner ?? null)),
                            'og_type'          => 'article',
                        ],
                        dry: $dry,
                        over: $over,
                        totals: $totals
                    );
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }

    protected function writeMeta(string $type, int $id, string $lang, array $data, bool $dry, bool $over, array &$totals): void
    {
        $clean = array_filter($data, fn($v) => $v !== null && $v !== '' && $v !== []);

        if (empty($clean)) {
            $totals['skipped']++;
            return;
        }

        $existing = SeoMeta::query()
            ->where('model_type', $type)
            ->where('model_id', $id)
            ->where('lang', $lang)
            ->first();

        if ($existing && !$over) {
            $totals['skipped']++;
            return;
        }

        if ($dry) {
            $existing ? $totals['updated']++ : $totals['created']++;
            return;
        }

        try {
            SeoMeta::updateOrCreate(
                ['model_type' => $type, 'model_id' => $id, 'lang' => $lang],
                $clean
            );
            $existing ? $totals['updated']++ : $totals['created']++;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("  Failed for {$type}#{$id}: {$e->getMessage()}");
            $totals['errors']++;
        }
    }

    protected function splitKeywords($value): ?array
    {
        if (empty($value)) {
            return null;
        }
        $parts = preg_split('/[,\r\n]+/', (string) $value);
        $parts = array_values(array_filter(array_map('trim', $parts ?: [])));
        return $parts ?: null;
    }

    protected function resolveImage($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        if (is_numeric($value) && Schema::hasTable('uploads')) {
            try {
                $upload = Upload::find($value);
                return $upload && $upload->file_name ? asset('public/' . $upload->file_name) : null;
            } catch (Throwable $e) {
                return null;
            }
        }

        return is_string($value) ? $value : null;
    }
}
