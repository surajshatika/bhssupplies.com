<?php

namespace App\Observers;

use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Purges the full-page cache for a content entity (Product, Category, Page,
 * Blog) when SEO-relevant fields change, so title/price/content edits appear
 * immediately instead of serving stale cached HTML until the TTL expires.
 *
 * Guarded by wasChanged() on a per-type field allowlist so high-frequency,
 * non-visible writes (view counters, sale counts) do NOT bust the cache.
 */
class EntityCachePurgeObserver
{
    /** Fields whose change should invalidate the entity's cached page. */
    protected array $significant = [
        'Product'  => ['name', 'slug', 'unit_price', 'discount', 'meta_title', 'meta_description', 'thumbnail_img', 'photos', 'description', 'published', 'current_stock'],
        'Category' => ['name', 'slug', 'meta_title', 'meta_description', 'banner', 'icon'],
        'Page'     => ['title', 'slug', 'content', 'meta_title', 'meta_description', 'meta_image'],
        'Blog'     => ['title', 'slug', 'description', 'short_description', 'meta_title', 'meta_description', 'banner_image', 'status'],
    ];

    public function saved(Model $model): void
    {
        // On create, always purge (new page); on update, only for meaningful fields.
        $base = class_basename($model);
        $fields = $this->significant[$base] ?? [];
        if (!$model->wasRecentlyCreated && $fields && !$model->wasChanged($fields)) {
            return;
        }
        $this->purge($model);
    }

    public function deleted(Model $model): void
    {
        $this->purge($model);
    }

    protected function purge(Model $model): void
    {
        try {
            if ((int) get_setting('perf_page_cache_status', 0) !== 1) {
                return;
            }
            $slug = $model->slug ?? null;
            if (!$slug) {
                return;
            }

            $url = match (class_basename($model)) {
                'Product'  => route('product', $slug),
                'Category' => route('products.category', $slug),
                'Blog'     => route('blog.details', $slug),
                'Page'     => route('custom-pages.show_custom_page', $slug),
                default    => null,
            };

            if ($url) {
                app(PageCacheService::class)->forgetUrl($url);
            }
        } catch (\Throwable $e) {
            // Cache invalidation must never break a save.
        }
    }
}
