<?php

namespace App\Services\Seo;

use App\Models\SeoRedirect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Maps a dead public URL (old product/category/brand/blog slug) to the closest
 * live page and stores a permanent 301 in seo_redirects. Unmatched URLs stay
 * 404: redirecting them to an unrelated page would just become a soft 404.
 */
class NotFoundResolver
{
    public const MIN_SIMILARITY = 0.6;

    /** Returns the target URL (and saves the redirect), or null. */
    public function resolve(string $path): ?string
    {
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '', '/');
        if ($path === '/' || preg_match('#^/(admin|api|storage|assets|public|livewire)\b#', $path)) return null;
        if (preg_match('/\.(jpe?g|png|gif|webp|svg|css|js|ico|map|woff2?|pdf)$/i', $path)) return null;

        try {
            if (!Schema::hasTable('seo_redirects')) return null;
            if ($existing = SeoRedirect::where('source_url', $path)->where('is_active', true)->value('target_url')) {
                return $existing;
            }

            $target = $this->match($path);
            if (!$target || rtrim(parse_url($target, PHP_URL_PATH) ?: '/', '/') === rtrim($path, '/')) return null;

            SeoRedirect::updateOrCreate(['source_url' => Str::limit($path, 188, '')], [
                'target_url' => $target, 'status_code' => 301, 'is_active' => true,
                'notes' => 'Auto: 404 resolved to closest live page',
            ]);
            Cache::forget(\App\Http\Middleware\SeoRedirectMiddleware::CACHE_KEY);
            return $target;
        } catch (Throwable $e) {
            logger()->warning('NotFoundResolver failed', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function match(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));
        $prefix = count($segments) > 1 ? $segments[0] : null;
        $slug = Str::slug(urldecode(end($segments)));
        if ($slug === '') return null;

        // Typed prefix first; bare "/something" tries every type.
        $types = match ($prefix) {
            'product' => ['product'],
            'category' => ['category', 'product'],
            'brand' => ['brand'],
            'blog' => ['blog'],
            default => ['product', 'category', 'page', 'blog', 'brand'],
        };

        $best = [null, 0.0];
        foreach ($types as $type) {
            foreach ($this->candidates($type) as $candSlug => $url) {
                $score = $this->similarity($slug, (string) $candSlug);
                if ($score > $best[1]) $best = [$url, $score];
            }
            if ($best[1] >= 0.9) break;
        }
        return $best[1] >= self::MIN_SIMILARITY ? $best[0] : null;
    }

    /** slug => absolute URL, live entities only (cached 1h). */
    protected function candidates(string $type): array
    {
        return Cache::remember("seo:404-candidates:$type", 3600, function () use ($type) {
            $q = match ($type) {
                'product' => DB::table('products')->where('published', 1)->where('approved', 1),
                'category' => DB::table('categories'),
                'brand' => DB::table('brands'),
                'blog' => DB::table('blogs')->where('status', 1)->whereNull('deleted_at'),
                'page' => DB::table('pages'),
            };
            $base = ['product' => 'product/', 'category' => 'category/', 'brand' => 'brand/', 'blog' => 'blog/', 'page' => ''][$type];
            return $q->whereNotNull('slug')->where('slug', '!=', '')->pluck('slug')
                ->mapWithKeys(fn($s) => [$s => url($base . $s)])->all();
        });
    }

    /** Token overlap (handles reordered/extra words) blended with character similarity. */
    protected function similarity(string $a, string $b): float
    {
        if ($a === $b) return 1.0;
        $strip = fn($s) => preg_replace('/-(\d+|[a-z0-9]{5,6})$/', '', $s); // trailing ids / random suffixes
        $ta = array_unique(array_filter(explode('-', $strip($a)), fn($t) => strlen($t) > 1));
        $tb = array_unique(array_filter(explode('-', $strip($b)), fn($t) => strlen($t) > 1));
        if (!$ta || !$tb) return 0.0;
        $jaccard = count(array_intersect($ta, $tb)) / count(array_unique(array_merge($ta, $tb)));
        similar_text($strip($a), $strip($b), $pct);
        return max($jaccard, $pct / 100 * 0.9);
    }
}
