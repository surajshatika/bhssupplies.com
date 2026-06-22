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

        // 1. Gather all targets (URLs we want to link TO)
        // We'll use Products and Blogs that have an SEO keyword defined.
        $targets = [];

        // Assuming you have an SeoMeta model or a meta_title/meta_keyword column.
        // For simplicity, we'll try to get keywords from Product/Blog or SeoMeta.
        // Since we don't know the exact schema, let's look at SeoKeyword model we saw earlier.
        
        $keywords = \App\Models\SeoKeyword::whereNotNull('entity_type')->whereNotNull('entity_id')->get();
        
        foreach ($keywords as $kw) {
            if (empty($kw->keyword)) continue;
            
            $url = '';
            if ($kw->entity_type === 'product') {
                $product = Product::find($kw->entity_id);
                if ($product) $url = url('product/' . $product->slug);
            } elseif ($kw->entity_type === 'blog') {
                $blog = Blog::find($kw->entity_id);
                if ($blog) $url = url('blog/' . $blog->slug);
            }
            
            if ($url) {
                $targets[] = [
                    'keyword' => trim($kw->keyword),
                    'url' => $url,
                    'type' => $kw->entity_type,
                    'id' => $kw->entity_id
                ];
            }
        }

        $this->info("Found " . count($targets) . " link targets.");
        if (count($targets) === 0) {
            $this->warn('No targets found. Run seo optimization first to generate focus keywords.');
            return 0;
        }

        $linksAdded = 0;

        // 2. Scan Products
        $products = Product::where('published', 1)->get();
        foreach ($products as $product) {
            $content = $product->description;
            if (!$content) continue;

            $updatedContent = $this->injectLinks($content, $targets, $product->id, 'product');
            if ($updatedContent !== $content) {
                $product->description = $updatedContent;
                $product->save();
                $linksAdded++;
                $this->info("Injected links into Product ID {$product->id}");
            }
        }

        // 3. Scan Blogs
        if (class_exists(Blog::class)) {
            $blogs = Blog::where('status', 1)->get();
            foreach ($blogs as $blog) {
                $content = $blog->description ?? $blog->content;
                if (!$content) continue;

                $updatedContent = $this->injectLinks($content, $targets, $blog->id, 'blog');
                if ($updatedContent !== $content) {
                    if (isset($blog->description)) {
                        $blog->description = $updatedContent;
                    } else {
                        $blog->content = $updatedContent;
                    }
                    $blog->save();
                    $linksAdded++;
                    $this->info("Injected links into Blog ID {$blog->id}");
                }
            }
        }

        $this->info("Auto-Linker completed! Modified {$linksAdded} items.");
        return 0;
    }

    protected function injectLinks($content, $targets, $currentId, $currentType)
    {
        // Don't inject more than 3 links per content piece to avoid spam
        $linksInjected = 0;
        $maxLinks = 3;

        foreach ($targets as $target) {
            if ($linksInjected >= $maxLinks) break;
            
            // Don't link to itself
            if ($target['id'] == $currentId && $target['type'] === $currentType) continue;

            $keyword = preg_quote($target['keyword'], '/');
            
            // Check if the exact keyword exists in the content
            // Ensure we don't inject inside an existing <a> tag or inside HTML attributes
            // Negative lookahead to ensure we aren't already inside an <a> tag
            $pattern = '/\b(' . $keyword . ')\b(?![^<]*>|[^<>]*<\/a>)/i';

            if (preg_match($pattern, $content)) {
                // Only replace the FIRST occurrence
                $replacement = '<a href="' . $target['url'] . '" class="seo-auto-link" title="Read more about ' . htmlentities($target['keyword']) . '">$1</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
                $linksInjected++;
            }
        }

        return $content;
    }
}
