<?php

namespace App\Services\Seo;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Resolves a "complete SEO bundle" for any entity or for the current request.
 *
 * Used by the public meta-tags partial to render <title>, description,
 * canonical, OG, Twitter Card, and JSON-LD. Values are sourced from
 * (in priority order):
 *   1. seo_meta row matching {model_type, model_id, lang}
 *   2. inline columns on the entity model
 *   3. site-wide defaults from business_settings
 *
 * Stays defensive: every external lookup is wrapped so a misconfigured
 * environment never crashes a public page render.
 */
class SeoMetaResolver
{
    /** @var array<string,array> in-request memoization */
    protected array $cache = [];

    public function resolveForRequest(?Request $request = null): array
    {
        $request = $request ?: request();
        $route   = optional($request->route());

        $entity = $this->detectEntityFromRoute($route);

        if ($entity instanceof Model) {
            $type = $this->modelToType($entity);
            return $this->resolveForEntity($entity, $type, $request);
        }

        return $this->siteDefaults($request);
    }

    public function resolveForEntity(Model $entity, string $type, ?Request $request = null): array
    {
        $request = $request ?: request();
        $key = $type . ':' . $entity->getKey() . ':' . app()->getLocale();
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $defaults = $this->siteDefaults($request);
        $meta     = $this->loadSeoMeta($entity, $type);
        $inline   = $this->extractInline($entity, $type);
        $name     = $this->entityName($entity, $type);

        $title       = $meta['meta_title']       ?? $inline['meta_title']       ?? $this->buildTitle($name, $defaults['site_name']);
        $description = $meta['meta_description'] ?? $inline['meta_description'] ?? $defaults['meta_description'];
        $focus       = $meta['focus_keyword']    ?? null;
        $robots      = $meta['robots_meta']      ?? $defaults['robots'];
        $canonical   = $meta['canonical_url']    ?? $request->url();
        $ogImage     = $meta['og_image']         ?? $inline['og_image']         ?? $defaults['og_image'];
        $twImage     = $meta['twitter_image']    ?? $inline['og_image']         ?? $defaults['og_image'];

        $schemas = [];
        if (!empty($meta['schema_json']) && is_array($meta['schema_json'])) {
            $schemas[] = $meta['schema_json'];
        }
        if (!empty($meta['breadcrumbs_json']) && is_array($meta['breadcrumbs_json'])) {
            $schemas[] = $meta['breadcrumbs_json'];
        }

        $bundle = [
            'entity'         => $entity,
            'type'           => $type,
            'title'          => $this->trim($title, 60),
            'description'    => $this->trim($description, 200),
            'keywords'       => $this->normalizeKeywords($meta['secondary_keywords'] ?? null) ?: $defaults['meta_keywords'],
            'focus_keyword'  => $focus,
            'robots'         => $robots,
            'canonical'      => $canonical,
            'og_type'        => $meta['og_type'] ?? ($type === 'product' ? 'product' : ($type === 'blog' ? 'article' : 'website')),
            'og_title'       => $meta['og_title']       ?? $this->trim($title, 80),
            'og_description' => $meta['og_description'] ?? $this->trim($description, 200),
            'og_image'       => $ogImage,
            'twitter_card'   => $meta['twitter_card']   ?? 'summary_large_image',
            'twitter_title'  => $meta['twitter_title']  ?? $this->trim($title, 80),
            'twitter_description' => $meta['twitter_description'] ?? $this->trim($description, 200),
            'twitter_image'  => $twImage,
            'site_name'      => $defaults['site_name'],
            'locale'         => $defaults['locale'],
            'schemas'        => $schemas,
        ];

        return $this->cache[$key] = $bundle;
    }

    public function siteDefaults(?Request $request = null): array
    {
        $request = $request ?: request();
        $siteName = (string) (get_setting('website_name') ?: config('app.name'));
        $title    = (string) (get_setting('meta_title') ?: $siteName);
        $desc     = (string) (get_setting('meta_description') ?? '');
        $kw       = (string) (get_setting('meta_keywords') ?? '');
        $robots   = (string) (get_setting('seo_default_robots') ?: 'index, follow, max-image-preview:large, max-snippet:-1');
        $ogImage  = $this->safeAsset((string) get_setting('meta_image'));
        $canonical = $request->url();

        return [
            'site_name'        => $siteName,
            'title'            => $this->trim($title, 60),
            'meta_description' => $this->trim($desc, 200),
            'meta_keywords'    => $kw,
            'robots'           => $robots,
            'canonical'        => $canonical,
            'og_image'         => $ogImage,
            'twitter_card'     => 'summary_large_image',
            'og_type'          => 'website',
            'og_title'         => $title,
            'og_description'   => $desc,
            'twitter_title'    => $title,
            'twitter_description' => $desc,
            'twitter_image'    => $ogImage,
            'locale'           => str_replace('-', '_', app()->getLocale()),
            'schemas'          => [],
            'entity'           => null,
            'type'             => null,
            'keywords'         => $kw,
            'focus_keyword'    => null,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────────────

    protected function detectEntityFromRoute($route): ?Model
    {
        if (!$route) {
            return null;
        }

        $name = $route->getName();

        try {
            // Active eCommerce uses `route('product')` and `route('products.category')`.
            if ($name === 'product' && $slug = $route->parameter('slug')) {
                return Product::query()->where('slug', $slug)->first();
            }

            if (in_array($name, ['products.category', 'product.categories.all'], true)) {
                $slug = $route->parameter('category_slug') ?? $route->parameter('slug');
                return $slug ? Category::query()->where('slug', $slug)->first() : null;
            }

            if (in_array($name, ['blog.details', 'blog'], true) && $slug = $route->parameter('slug')) {
                return Blog::query()->where('slug', $slug)->first();
            }

            // Custom pages: /{slug} mapped to Page model
            if ($name === 'custom-pages.show_custom_page' && $slug = $route->parameter('slug')) {
                return Page::query()->where('slug', $slug)->first();
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    protected function modelToType(Model $entity): string
    {
        return match (true) {
            $entity instanceof Product  => 'product',
            $entity instanceof Category => 'category',
            $entity instanceof Page     => 'page',
            $entity instanceof Blog     => 'blog',
            default                     => Str::snake(class_basename($entity)),
        };
    }

    protected function loadSeoMeta(Model $entity, string $type): array
    {
        if (!Schema::hasTable('seo_meta')) {
            return [];
        }

        $row = SeoMeta::query()
            ->where('model_type', get_class($entity))
            ->where('model_id', $entity->getKey())
            ->where('lang', config('app.locale', 'en'))
            ->first();

        return $row ? $row->toArray() : [];
    }

    protected function extractInline(Model $entity, string $type): array
    {
        $data = [
            'meta_title'       => null,
            'meta_description' => null,
            'og_image'         => null,
        ];

        // Pull raw inline column WITHOUT going through accessors (avoid recursion
        // since HasSeoMeta::getMetaTitleAttribute consults seo_meta itself).
        $attrs = $entity->getAttributes();
        $data['meta_title']       = $attrs['meta_title']       ?? null;
        $data['meta_description'] = $attrs['meta_description'] ?? null;

        $data['og_image'] = match ($type) {
            'product' => $this->resolveUpload($entity->meta_img ?? null) ?: $this->resolveUpload($entity->thumbnail_img ?? null),
            'category'=> $this->safeAsset($entity->banner ?? null),
            'page'    => $entity->meta_image ?? null,
            'blog'    => $this->resolveUpload($entity->meta_img ?? null) ?: $this->safeAsset($entity->banner ?? null),
            default   => null,
        };

        return $data;
    }

    protected function entityName(Model $entity, string $type): string
    {
        $col = in_array($type, ['page', 'blog'], true) ? 'title' : 'name';
        return (string) ($entity->{$col} ?? '');
    }

    protected function buildTitle(string $name, string $siteName): string
    {
        if ($name === '') {
            return $siteName;
        }
        return $this->trim($name . ' | ' . $siteName, 60);
    }

    protected function trim($value, int $max): string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return '';
        }
        return mb_strlen($v) > $max ? rtrim(mb_substr($v, 0, $max - 1)) . '…' : $v;
    }

    protected function normalizeKeywords($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (is_array($value)) {
            return implode(', ', array_filter(array_map('trim', $value)));
        }
        return (string) $value;
    }

    protected function resolveUpload($value): ?string
    {
        if (!$value) {
            return null;
        }
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (is_numeric($value) && function_exists('uploaded_asset')) {
            try {
                return uploaded_asset($value) ?: null;
            } catch (Throwable $e) {
                return null;
            }
        }
        return null;
    }

    protected function safeAsset($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (is_string($value)) {
            return asset('public/' . ltrim($value, '/'));
        }
        if (is_numeric($value)) {
            return $this->resolveUpload($value);
        }
        return null;
    }
}
