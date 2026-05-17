<?php

namespace App\Console\Commands;

use Cache;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\CustomAlert;
use App\Models\DynamicPopup;
use Illuminate\Console\Command;

class WarmCache extends Command
{
    protected $signature   = 'cache:warm {--force : Flush existing cache before warming}';
    protected $description = 'Pre-warm frequently-accessed homepage and navigation caches';

    public function handle(): int
    {
        if ($this->option('force')) {
            $keys = [
                'featured_categories', 'hot_categories', 'newest_products',
                'app.top_brands', 'custom_alerts_asc', 'custom_alerts_desc', 'dynamic_popups',
            ];
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            $this->info('Existing cache flushed.');
        }

        $this->info('Warming caches...');

        Cache::remember('featured_categories', 86400, function () {
            return Category::with('bannerImage')->where('featured', 1)->get();
        });
        $this->line('  ✓ featured_categories');

        Cache::remember('hot_categories', 86400, function () {
            return Category::with('bannerImage')->where('hot_category', '1')->get();
        });
        $this->line('  ✓ hot_categories');

        Cache::remember('newest_products', 3600, function () {
            return filter_products(Product::latest())->take(12)->get();
        });
        $this->line('  ✓ newest_products');

        Cache::remember('app.top_brands', 86400, function () {
            return Brand::whereHas('products')->withCount('products')->orderByDesc('products_count')->take(12)->get();
        });
        $this->line('  ✓ app.top_brands');

        Cache::remember('custom_alerts_asc', 3600, function () {
            return CustomAlert::where('status', 1)->orderBy('id', 'asc')->get();
        });
        Cache::remember('custom_alerts_desc', 3600, function () {
            return CustomAlert::where('status', 1)->orderBy('id', 'desc')->get();
        });
        $this->line('  ✓ custom_alerts');

        Cache::remember('dynamic_popups', 3600, function () {
            return DynamicPopup::where('status', 1)->orderBy('id', 'asc')->get();
        });
        $this->line('  ✓ dynamic_popups');

        $this->info('Cache warm-up complete.');
        return self::SUCCESS;
    }
}
