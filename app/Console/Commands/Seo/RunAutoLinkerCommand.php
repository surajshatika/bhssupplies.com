<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Blog;
use Illuminate\Support\Str;

class RunAutoLinkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:auto-linker {--force : Force linking even if already linked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans content and automatically injects internal links for target keywords';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting SEO Auto-Linker...');

        // Link targets come from tracked keywords that have a destination URL.
        $targets = \App\Models\SeoKeyword::where('is_active', 1)
            ->whereNotNull('target_url')->where('target_url', '!=', '')
            ->get(['keyword', 'target_url'])
            ->filter(fn($kw) => trim((string) $kw->keyword) !== '')
            // Longest keywords first so "copper pipe fittings" wins over "copper pipe".
            ->sortByDesc(fn($kw) => mb_strlen($kw->keyword))
            ->map(fn($kw) => ['keyword' => trim($kw->keyword), 'url' => $this->absoluteUrl($kw->target_url)])
            ->values()
            ->all();

        $this->info("Found " . count($targets) . " link targets.");
        if (count($targets) === 0) {
            $this->warn('No targets found. Run seo optimization first to generate focus keywords.');
            return 0;
        }

        $linksAdded = 0;

        Product::where('published', 1)->whereNotNull('description')
            ->select('id', 'slug', 'description')
            ->chunkById(200, function ($products) use ($targets, &$linksAdded) {
                foreach ($products as $product) {
                    $updated = $this->injectLinks($product->description, $targets, url('product/' . $product->slug));
                    if ($updated !== $product->description) {
                        // saveQuietly: skip observers (sitemap rebuild, cache purge) per row.
                        $product->description = $updated;
                        $product->saveQuietly();
                        $linksAdded++;
                    }
                }
            });

        Blog::where('status', 1)->whereNotNull('description')
            ->select('id', 'slug', 'description')
            ->chunkById(200, function ($blogs) use ($targets, &$linksAdded) {
                foreach ($blogs as $blog) {
                    $updated = $this->injectLinks($blog->description, $targets, url('blog/' . $blog->slug));
                    if ($updated !== $blog->description) {
                        $blog->description = $updated;
                        $blog->saveQuietly();
                        $linksAdded++;
                    }
                }
            });

        $this->info("Auto-Linker completed! Modified {$linksAdded} items.");
        return 0;
    }

    protected function injectLinks(string $content, array $targets, string $selfUrl): string
    {
        $maxLinks = 3;
        // Idempotent: count links we already added on earlier runs toward the cap,
        // and never link the same destination twice.
        $linksInjected = substr_count($content, 'class="seo-auto-link"');
        $selfUrl = rtrim($selfUrl, '/');

        foreach ($targets as $target) {
            if ($linksInjected >= $maxLinks) break;
            if (rtrim($target['url'], '/') === $selfUrl) continue;
            if (str_contains($content, 'href="' . e($target['url']) . '"')) continue;

            // Whole word, not inside a tag's attributes, not inside an existing <a>.
            $pattern = '/\b(' . preg_quote($target['keyword'], '/') . ')\b(?![^<]*>|[^<>]*<\/a>)/iu';
            if (!preg_match($pattern, $content)) continue;

            $replacement = '<a href="' . e($target['url']) . '" class="seo-auto-link" title="' . e($target['keyword']) . '">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
            $linksInjected++;
        }

        return $content;
    }

    protected function absoluteUrl(string $url): string
    {
        return preg_match('#^https?://#i', $url) ? $url : url('/' . ltrim($url, '/'));
    }
}
