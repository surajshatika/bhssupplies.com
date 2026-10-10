<?php

namespace App\Console\Commands\Seo;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Adds a small "related" block of real internal links to product, category and
 * blog content: product -> category/brand/sibling products, category ->
 * parent/children/top products, blog -> categories it mentions. The block is
 * wrapped in a marker so re-runs replace it instead of stacking.
 */
class BuildInternalLinksCommand extends Command
{
    protected $signature = 'seo:build-internal-links {--dry-run : Report counts without saving}';

    protected $description = 'Build a related-links block (category, brand, sibling products) into product, category and blog content';

    protected const START = '<!--seo-related-links-->';
    protected const END = '<!--/seo-related-links-->';

    protected Collection $categories;
    protected Collection $brands;
    protected array $productsByCategory = [];

    public function handle(): int
    {
        $this->categories = Category::select('id', 'parent_id', 'name', 'slug')->get()->keyBy('id');
        $this->brands = Brand::select('id', 'name', 'slug')->get()->keyBy('id');
        Product::where('published', 1)->whereNotNull('category_id')
            ->orderByDesc('num_of_sale')->select('id', 'category_id', 'name', 'slug')
            ->each(function ($p) {
                $this->productsByCategory[$p->category_id][] = ['id' => $p->id, 'name' => $p->name, 'slug' => $p->slug];
            }, 1000);

        $changed = ['product' => 0, 'category' => 0, 'blog' => 0];

        Product::where('published', 1)->select('id', 'category_id', 'brand_id', 'name', 'slug', 'description')
            ->chunkById(200, function ($rows) use (&$changed) {
                foreach ($rows as $p) {
                    $links = $this->productLinks($p);
                    if ($this->save($p, 'description', $links)) $changed['product']++;
                }
            });

        Category::select('id', 'parent_id', 'name', 'slug', 'bottom_description')
            ->chunkById(200, function ($rows) use (&$changed) {
                foreach ($rows as $c) {
                    if ($this->save($c, 'bottom_description', $this->categoryLinks($c))) $changed['category']++;
                }
            });

        Blog::where('status', 1)->select('id', 'title', 'slug', 'description')
            ->chunkById(100, function ($rows) use (&$changed) {
                foreach ($rows as $b) {
                    if ($this->save($b, 'description', $this->blogLinks($b))) $changed['blog']++;
                }
            });

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '') . 'Updated: ' . json_encode($changed));
        return self::SUCCESS;
    }

    protected function productLinks(Product $p): array
    {
        $links = [];
        $cat = $this->categories->get($p->category_id);
        if ($cat && $cat->slug) {
            $links[] = [route('products.category', $cat->slug), 'More ' . $cat->name];
            $parent = $this->categories->get($cat->parent_id);
            if ($parent && $parent->slug) $links[] = [route('products.category', $parent->slug), $parent->name];
        }
        $brand = $this->brands->get($p->brand_id);
        if ($brand && $brand->slug) $links[] = [route('products.brand', $brand->slug), $brand->name . ' products'];

        $siblings = collect($this->productsByCategory[$p->category_id] ?? [])->where('id', '!=', $p->id)->take(3);
        foreach ($siblings as $s) $links[] = [url('product/' . $s['slug']), $s['name']];

        return $links;
    }

    protected function categoryLinks(Category $c): array
    {
        $links = [];
        $parent = $this->categories->get($c->parent_id);
        if ($parent && $parent->slug) $links[] = [route('products.category', $parent->slug), 'All ' . $parent->name];
        foreach ($this->categories->where('parent_id', $c->id)->take(6) as $child) {
            if ($child->slug) $links[] = [route('products.category', $child->slug), $child->name];
        }
        foreach (array_slice($this->productsByCategory[$c->id] ?? [], 0, 6) as $p) {
            $links[] = [url('product/' . $p['slug']), $p['name']];
        }
        return $links;
    }

    protected function blogLinks(Blog $b): array
    {
        $text = mb_strtolower(strip_tags((string) $b->title . ' ' . $this->stripBlock((string) $b->description)));
        $links = [];
        foreach ($this->categories as $cat) {
            $name = mb_strtolower(trim($cat->name));
            if ($cat->slug && mb_strlen($name) >= 4 && str_contains($text, $name)) {
                $links[] = [route('products.category', $cat->slug), 'Shop ' . $cat->name];
            }
            if (count($links) >= 5) break;
        }
        return $links;
    }

    /** @param array<int, array{0:string,1:string}> $links */
    protected function save($model, string $field, array $links): bool
    {
        $current = (string) $model->{$field};
        $base = $this->stripBlock($current);
        $links = collect($links)->unique(0)->take(8);

        $updated = $links->isEmpty() ? $base : rtrim($base) . "\n" . self::START
            . '<div class="seo-related-links"><h3>' . e(translate('Related')) . '</h3><ul>'
            . $links->map(fn($l) => '<li><a href="' . e($l[0]) . '">' . e($l[1]) . '</a></li>')->implode('')
            . '</ul></div>' . self::END;

        if ($updated === $current) return false;
        if (!$this->option('dry-run')) {
            $model->{$field} = $updated;
            $model->saveQuietly();
        }
        return true;
    }

    protected function stripBlock(string $html): string
    {
        return preg_replace('/\s*' . preg_quote(self::START, '/') . '.*?' . preg_quote(self::END, '/') . '/s', '', $html);
    }
}
