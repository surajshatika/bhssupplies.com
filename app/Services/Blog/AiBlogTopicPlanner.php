<?php

namespace App\Services\Blog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Picks the next blog topic: every category with products first (largest
 * first), then products (best sellers first). Each source is used once; a
 * source that failed 3 times is skipped. Returns generator options with real
 * catalogue data so the AI writes specific, useful content.
 */
class AiBlogTopicPlanner
{
    public const MAX_ATTEMPTS = 3;

    public function next(): ?array
    {
        return $this->nextCategory() ?? $this->nextProduct();
    }

    public function remaining(): array
    {
        $used = fn($type) => DB::table('ai_blog_topics')->where('source_type', $type)
            ->where(fn($q) => $q->where('status', 'done')->orWhere('attempts', '>=', self::MAX_ATTEMPTS))->pluck('source_id');
        return [
            'categories' => Category::whereHas('products', fn($q) => $q->where('published', 1))->whereNotIn('id', $used('category'))->count(),
            'products' => Product::where('published', 1)->whereNotIn('id', $used('product'))->count(),
        ];
    }

    public function record(string $type, int $id, ?int $blogId, ?string $error = null): void
    {
        $row = DB::table('ai_blog_topics')->where('source_type', $type)->where('source_id', $id)->first();
        $data = ['blog_id' => $blogId, 'status' => $blogId ? 'done' : 'failed',
            'error' => $error ? Str::limit($error, 1000) : null, 'updated_at' => now()];
        if ($row) {
            DB::table('ai_blog_topics')->where('id', $row->id)->update($data + ['attempts' => $row->attempts + 1]);
        } else {
            DB::table('ai_blog_topics')->insert($data + ['source_type' => $type, 'source_id' => $id, 'attempts' => 1, 'created_at' => now()]);
        }
    }

    protected function excluded(string $type)
    {
        return DB::table('ai_blog_topics')->where('source_type', $type)
            ->where(fn($q) => $q->where('status', 'done')->orWhere('attempts', '>=', self::MAX_ATTEMPTS))
            ->pluck('source_id');
    }

    protected function nextCategory(): ?array
    {
        $cat = Category::withCount(['products' => fn($q) => $q->where('published', 1)])
            ->having('products_count', '>', 0)
            ->whereNotIn('id', $this->excluded('category'))
            ->orderByDesc('products_count')->first();
        if (!$cat) return null;

        $products = $cat->products()->where('published', 1)
            ->orderByDesc('num_of_sale')->limit(12)->get(['products.id', 'name', 'slug', 'brand_id', 'unit_price']);
        $brands = Brand::whereIn('id', $products->pluck('brand_id')->filter())->pluck('name')->implode(', ');
        $name = trim($cat->name);

        $context = "Category: {$name} ({$cat->products_count} products in stock)\n"
            . ($brands ? "Brands carried: {$brands}\n" : '')
            . "Example products:\n" . $products->map(fn($p) => '- ' . $p->name)->implode("\n");

        $links = [[route('products.category', $cat->slug), $name . ' (category)']];
        foreach ($products->take(5) as $p) $links[] = [url('product/' . $p->slug), $p->name];

        return [
            'source_type' => 'category',
            'source_id' => $cat->id,
            'topic' => 'A practical buyer\'s guide to ' . Str::title(Str::lower($name)) . ' for contractors and homeowners in Canada',
            'keywords' => $this->keywordsFor($cat, [$name, $name . ' Canada', $name . ' supplier'], $products),
            'product_category_id' => $cat->id,
            'category_name' => 'Buying Guides',
            'source_context' => $context,
            'internal_links' => $links,
        ];
    }

    protected function nextProduct(): ?array
    {
        $p = Product::where('published', 1)->whereNotIn('id', $this->excluded('product'))
            ->orderByDesc('num_of_sale')->orderByDesc('id')->first();
        if (!$p) return null;

        $cat = Category::find($p->category_id);
        $brand = Brand::find($p->brand_id);
        $lang = env('DEFAULT_LANGUAGE', 'en');
        $desc = DB::table('product_translations')->where('product_id', $p->id)->where('lang', $lang)->value('description')
            ?? $p->getRawOriginal('description');
        // Merchant text only: drop SEO blocks added by automation.
        $desc = preg_replace(['/<!--seo-ai-content-->.*?<!--\/seo-ai-content-->/s', '/<!--seo-related-links-->.*?<!--\/seo-related-links-->/s'], '', (string) $desc);
        $desc = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($desc))), 1200);

        $siblings = $cat ? $cat->products()->where('published', 1)->where('products.id', '!=', $p->id)
            ->orderByDesc('num_of_sale')->limit(3)->get(['name', 'slug']) : collect();

        $context = "Product: {$p->name}\n"
            . ($brand ? "Brand: {$brand->name}\n" : '')
            . ($cat ? "Category: {$cat->name}\n" : '')
            . ($desc ? "Manufacturer/store description: {$desc}\n" : '')
            . ($siblings->isNotEmpty() ? "Related products: " . $siblings->pluck('name')->implode('; ') : '');

        $links = [[url('product/' . $p->slug), $p->name]];
        if ($cat) $links[] = [route('products.category', $cat->slug), $cat->name];
        foreach ($siblings as $s) $links[] = [url('product/' . $s->slug), $s->name];

        return [
            'source_type' => 'product',
            'source_id' => $p->id,
            'topic' => $p->name . ': uses, how to choose the right one, and installation tips',
            'keywords' => $this->keywordsFor($p, [$p->name, $cat?->name, $brand?->name], collect()),
            'product_category_id' => $p->category_id,
            'category_name' => 'Product Guides',
            'source_context' => $context,
            'internal_links' => $links,
        ];
    }

    /** Focus/secondary keywords already assigned by the SEO suite, plus seeds. */
    protected function keywordsFor($model, array $seeds, $products): string
    {
        $meta = SeoMeta::where('model_type', get_class($model))->where('model_id', $model->id)->first();
        $kw = collect($seeds)->push($meta?->focus_keyword)
            ->merge(is_array($meta?->secondary_keywords) ? $meta->secondary_keywords : [])
            ->merge($products->take(4)->pluck('name'));
        // Drop "<keyword> + <city>" variants: one per GTA city is keyword stuffing.
        $cities = '/\b(brampton|toronto|etobicoke|vaughan|oakville|scarborough|markham|north york|burlington|gta|leave a review|trade account)\b/i';
        return $kw->filter()->map(fn($k) => trim((string) $k))->filter()
            ->reject(fn($k) => preg_match($cities, $k))
            ->unique(fn($k) => Str::lower($k))->take(10)->implode(', ');
    }
}
