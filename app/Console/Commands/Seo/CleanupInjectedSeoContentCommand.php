<?php

namespace App\Console\Commands\Seo;

use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * One-off repair for the May-June autopilot content-doubling bug: descriptions
 * accumulated the same injected SEO block (template "<h2>… for Canada and GTA
 * Buyers</h2> … <h3>Buying Guidance</h3>" + the data-seo-context-links link
 * paragraph) multiple times. Google reads that as low-quality templated content
 * at scale, which correlates with the GSC impression decline from mid-May.
 *
 * Keeps exactly ONE injected block (the newest — blocks were prepended) and one
 * link paragraph per entity. Saves quietly to avoid firing a full cache+CDN
 * purge per row, then clears the page cache once at the end.
 */
class CleanupInjectedSeoContentCommand extends Command
{
    protected $signature = 'seo:cleanup-injected-content {--dry-run : Preview affected URLs without saving}';

    protected $description = 'Remove duplicated autopilot-injected SEO content blocks, keeping one clean block per entity.';

    private const MARKER = 'data-seo-context-links';
    private const BLOCK_REGEX  = '/<h2>[^<]*for Canada and GTA Buyers<\/h2>.*?<h3>Buying Guidance<\/h3>\s*<p>.*?<\/p>/su';
    private const MARKER_REGEX = '/<p[^>]*data-seo-context-links[^>]*>.*?<\/p>/su';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $targets = [
            ['class' => \App\Models\Product::class,  'field' => 'description'],
            ['class' => \App\Models\Category::class, 'field' => 'bottom_description'],
            ['class' => \App\Models\Page::class,     'field' => 'content'],
            ['class' => \App\Models\Blog::class,     'field' => 'description'],
        ];

        $cleaned = 0;
        $wordsRemoved = 0;

        foreach ($targets as $target) {
            $class = $target['class'];
            $field = $target['field'];

            if (!class_exists($class)) {
                continue;
            }
            $table = (new $class)->getTable();
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $field)) {
                continue;
            }

            $class::query()
                ->where($field, 'like', '%' . self::MARKER . '%')
                ->select(['id', $field])
                ->chunkById(100, function ($rows) use ($class, $field, $dryRun, &$cleaned, &$wordsRemoved) {
                    foreach ($rows as $row) {
                        $original = (string) $row->{$field};
                        $repaired = $this->dedupeInjectedBlocks($original);
                        if ($repaired === null || trim($repaired) === trim($original)) {
                            continue;
                        }

                        $before = str_word_count(strip_tags($original));
                        $after  = str_word_count(strip_tags($repaired));
                        $cleaned++;
                        $wordsRemoved += max(0, $before - $after);

                        $this->line(sprintf(
                            '%s %s#%d  %d -> %d words',
                            $dryRun ? '[dry]' : '[fix]',
                            class_basename($class),
                            $row->id,
                            $before,
                            $after
                        ));

                        if (!$dryRun) {
                            // saveQuietly: a normal save would fire the provider's
                            // full cache+CDN purge per row — we purge once at the end.
                            $fresh = $class::find($row->id);
                            if ($fresh) {
                                $fresh->{$field} = $repaired;
                                $fresh->saveQuietly();
                            }
                        }
                    }
                });
        }

        $this->newLine();
        $this->info(sprintf(
            '%s%d entities, ~%d duplicated words %s.',
            $dryRun ? 'Would clean ' : 'Cleaned ',
            $cleaned,
            $wordsRemoved,
            $dryRun ? 'would be removed' : 'removed'
        ));

        if (!$dryRun && $cleaned > 0) {
            try {
                $pages = app(PageCacheService::class)->clearAll();
                $this->info("Page cache cleared ({$pages} pages) so Google re-crawls the cleaned HTML.");
            } catch (Throwable $e) {
                $this->warn('Page cache clear failed: ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    /**
     * Keep the FIRST template block (newest — blocks were prepended on each
     * batch) and the first link paragraph; strip every later duplicate.
     * Returns null when the content has no duplication to repair.
     */
    protected function dedupeInjectedBlocks(string $html): ?string
    {
        $blockCount  = preg_match_all(self::BLOCK_REGEX, $html);
        $markerCount = preg_match_all(self::MARKER_REGEX, $html);

        if ($blockCount <= 1 && $markerCount <= 1) {
            return null;
        }

        $repaired = $html;

        if ($blockCount > 1) {
            $seen = 0;
            $repaired = (string) preg_replace_callback(self::BLOCK_REGEX, function ($m) use (&$seen) {
                $seen++;
                return $seen === 1 ? $m[0] : '';
            }, $repaired);
        }

        if (preg_match_all(self::MARKER_REGEX, $repaired) > 1) {
            $seen = 0;
            $repaired = (string) preg_replace_callback(self::MARKER_REGEX, function ($m) use (&$seen) {
                $seen++;
                return $seen === 1 ? $m[0] : '';
            }, $repaired);
        }

        // Collapse the blank runs left behind by removed blocks.
        $repaired = (string) preg_replace("/(\r?\n){3,}/", "\n\n", $repaired);

        return trim($repaired);
    }
}
