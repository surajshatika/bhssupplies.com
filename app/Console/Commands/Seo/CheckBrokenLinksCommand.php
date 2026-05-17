<?php

namespace App\Console\Commands\Seo;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Crawl outbound href targets from products/categories/pages/blogs and persist
 * 4xx/5xx hits into seo_broken_links so the admin UI can display them.
 *
 * Intentionally bounded: --limit caps total entities scanned per run, and the
 * HTTP timeout is short. The command is designed for weekly cron, not real-time
 * detection.
 */
class CheckBrokenLinksCommand extends Command
{
    protected $signature = 'seo:check-broken-links
                            {--limit=500 : Max entities to scan in this run}
                            {--per-entity=10 : Max links to check per entity}
                            {--timeout=8 : HTTP timeout in seconds}';

    protected $description = 'Sweep internal entities for broken outbound links and persist findings.';

    protected int $totalChecked = 0;
    protected int $totalBroken  = 0;
    protected int $skippedEntities = 0;

    public function handle(): int
    {
        if (!Schema::hasTable('seo_broken_links')) {
            $this->warn('seo_broken_links table missing — run migrations first.');
            return self::FAILURE;
        }

        $limit     = (int) $this->option('limit');
        $perEntity = (int) $this->option('per-entity');
        $timeout   = (int) $this->option('timeout');

        $budget    = $limit;
        $sources   = [
            ['model' => Product::class,  'column' => 'description', 'urlfn' => fn($p) => '/product/' . $p->slug],
            ['model' => Category::class, 'column' => 'bottom_description', 'urlfn' => fn($c) => '/category/' . $c->slug],
            ['model' => Page::class,     'column' => 'content',     'urlfn' => fn($p) => '/' . $p->slug],
            ['model' => Blog::class,     'column' => 'description', 'urlfn' => fn($b) => '/blog/' . $b->slug],
        ];

        foreach ($sources as $src) {
            if ($budget <= 0) {
                break;
            }
            $consumed = $this->processSource($src, $budget, $perEntity, $timeout);
            $budget  -= $consumed;
        }

        $this->info("Done. checked={$this->totalChecked} broken={$this->totalBroken} skipped_entities={$this->skippedEntities}");
        return self::SUCCESS;
    }

    protected function processSource(array $src, int $budget, int $perEntity, int $timeout): int
    {
        $class  = $src['model'];
        if (!class_exists($class) || !Schema::hasTable((new $class)->getTable())) {
            return 0;
        }

        $entities = $class::query()->take($budget)->get();
        $count    = 0;

        foreach ($entities as $entity) {
            $count++;
            $content = $entity->{$src['column']} ?? '';
            $links   = $this->extractLinks($content, $perEntity);
            if (empty($links)) {
                $this->skippedEntities++;
                continue;
            }

            $sourceUrl = url(($src['urlfn'])($entity));
            foreach ($links as $href) {
                $this->checkAndPersist($sourceUrl, $href, $timeout);
            }
        }
        return $count;
    }

    protected function extractLinks(?string $html, int $cap): array
    {
        if (!$html) {
            return [];
        }
        if (!preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }
        $urls = array_values(array_unique(array_filter(
            array_map(fn($u) => $this->normalize($u), $matches[1]),
            fn($u) => $u && !str_starts_with($u, '#') && !str_starts_with($u, 'mailto:') && !str_starts_with($u, 'tel:')
        )));
        return array_slice($urls, 0, $cap);
    }

    protected function normalize(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || $url[0] === '#') {
            return null;
        }
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }
        if ($url[0] === '/') {
            return rtrim(url('/'), '/') . $url;
        }
        return null;
    }

    protected function checkAndPersist(string $source, string $target, int $timeout): void
    {
        $this->totalChecked++;
        $state  = 'ok';
        $status = null;

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['verify' => config('seo.ssl_verify', false), 'allow_redirects' => true])
                ->withHeaders(['User-Agent' => 'BHS-SeoBot/1.0 (+broken-link-sweep)'])
                ->head($target);
            $status = $response->status();
            if ($status >= 400) {
                $state = 'broken';
            }
        } catch (Throwable $e) {
            $state  = 'timeout';
            $status = 0;
        }

        $hash = sha1($source . '|' . $target);

        if ($state === 'ok') {
            DB::table('seo_broken_links')
                ->where('pair_hash', $hash)
                ->where('state', 'broken')
                ->update(['state' => 'resolved', 'resolved_at' => now(), 'last_checked_at' => now(), 'updated_at' => now()]);
            return;
        }

        $this->totalBroken++;

        $existing = DB::table('seo_broken_links')->where('pair_hash', $hash)->first();

        if ($existing) {
            DB::table('seo_broken_links')
                ->where('id', $existing->id)
                ->update([
                    'status_code'     => $status,
                    'state'           => $state,
                    'hit_count'       => $existing->hit_count + 1,
                    'last_checked_at' => now(),
                    'updated_at'      => now(),
                ]);
        } else {
            DB::table('seo_broken_links')->insert([
                'pair_hash'       => $hash,
                'source_url'      => $source,
                'target_url'      => $target,
                'status_code'     => $status,
                'state'           => $state,
                'hit_count'       => 1,
                'first_seen_at'   => now(),
                'last_checked_at' => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
