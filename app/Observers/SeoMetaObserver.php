<?php

namespace App\Observers;

use App\Models\SeoMeta;
use App\Services\PerformanceOptimizer\PageCacheService;

/**
 * Invalidates the full-page cache for an entity the instant its SEO meta
 * changes, so SEO Suite / autopilot edits appear on the live page immediately
 * instead of waiting for the page-cache TTL (default 12h) to expire.
 *
 * Without this, optimized title/description/robots/canonical/schema are written
 * to seo_meta but visitors keep getting the stale cached HTML.
 */
class SeoMetaObserver
{
    public function saved(SeoMeta $meta): void
    {
        $this->purge($meta);
    }

    public function deleted(SeoMeta $meta): void
    {
        $this->purge($meta);
    }

    protected function purge(SeoMeta $meta): void
    {
        try {
            if ((int) get_setting('perf_page_cache_status', 0) !== 1) {
                return;
            }
            $url = $this->entityUrl((string) $meta->model_type, (int) $meta->model_id);
            if ($url) {
                app(PageCacheService::class)->forgetUrl($url);
            }
        } catch (\Throwable $e) {
            // Cache invalidation must never break a save.
        }
    }

    /** Resolve the public URL for a polymorphic seo_meta owner. */
    protected function entityUrl(string $type, int $id): ?string
    {
        if ($type === '' || $id <= 0 || !class_exists($type)) {
            return null;
        }

        try {
            $model = $type::query()->find($id);
            if (!$model || empty($model->slug)) {
                return null;
            }

            return match (class_basename($type)) {
                'Product'  => route('product', $model->slug),
                'Category' => route('products.category', $model->slug),
                'Blog'     => route('blog.details', $model->slug),
                'Page'     => route('custom-pages.show_custom_page', $model->slug),
                default    => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }
}
