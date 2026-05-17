<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Wraps cache writes so callers don't have to know whether the active driver
 * supports tags. Maintains a per-tag index of plain cache keys for drivers
 * (file, database, array) that don't support `Cache::tags()` natively.
 *
 * Use this everywhere the SEO suite caches data — never call Cache::tags()
 * or Cache::flush() directly from SEO code.
 */
class SeoCacheManager
{
    public const TAG_SITEMAP   = 'seo:sitemap';
    public const TAG_REDIRECTS = 'seo:redirects';
    public const TAG_META      = 'seo:meta';
    public const TAG_SETTINGS  = 'seo:settings';
    public const TAG_RANKINGS  = 'seo:rankings';
    public const TAG_ANALYTICS = 'seo:analytics';

    protected const KEY_INDEX_PREFIX = 'seo:tag-index:';

    public function put(string $key, mixed $value, int $ttlSeconds, array $tags = []): void
    {
        if ($this->supportsTags() && !empty($tags)) {
            Cache::tags($tags)->put($key, $value, $ttlSeconds);
            return;
        }

        Cache::put($key, $value, $ttlSeconds);
        foreach ($tags as $tag) {
            $this->registerKey($tag, $key);
        }
    }

    public function remember(string $key, int $ttlSeconds, callable $callback, array $tags = []): mixed
    {
        if ($this->supportsTags() && !empty($tags)) {
            return Cache::tags($tags)->remember($key, $ttlSeconds, $callback);
        }

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->put($key, $value, $ttlSeconds, $tags);
        return $value;
    }

    public function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable $e) {
            // noop
        }
    }

    /**
     * Invalidate every cache entry tagged with one of the given tags. Falls
     * back to walking the maintained key index for file/database drivers.
     */
    public function flushTags(array $tags): void
    {
        if ($this->supportsTags()) {
            try {
                Cache::tags($tags)->flush();
                return;
            } catch (Throwable $e) {
                // continue with manual fallback
            }
        }

        foreach ($tags as $tag) {
            $indexKey = self::KEY_INDEX_PREFIX . $tag;
            $keys = (array) (Cache::get($indexKey) ?? []);
            foreach ($keys as $k) {
                $this->forget($k);
            }
            Cache::forget($indexKey);
        }
    }

    public function supportsTags(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached'], true);
    }

    protected function registerKey(string $tag, string $key): void
    {
        $indexKey = self::KEY_INDEX_PREFIX . $tag;
        try {
            $list = (array) (Cache::get($indexKey) ?? []);
            if (!in_array($key, $list, true)) {
                $list[] = $key;
                if (count($list) > 5000) {
                    $list = array_slice($list, -5000);
                }
                Cache::put($indexKey, $list, 86400 * 30);
            }
        } catch (Throwable $e) {
            // index updates are best-effort
        }
    }
}
