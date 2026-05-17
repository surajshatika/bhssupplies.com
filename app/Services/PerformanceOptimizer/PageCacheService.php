<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\CacheRule;
use App\Models\PerformanceOptimizer\OptimizationLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PageCacheService
{
    protected string $cacheDir;
    protected string $driver;

    public function __construct()
    {
        $this->cacheDir = storage_path('framework/page-cache');
        $this->driver   = (string) get_setting('perf_page_cache_driver', 'file');
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    public function shouldCache(Request $request): bool
    {
        if ((int) get_setting('perf_page_cache_status', 0) !== 1) return false;
        if (!$request->isMethod('GET'))                            return false;
        if ($request->ajax())                                      return false;
        if ($request->wantsJson())                                 return false;

        // Don't cache for authenticated users
        if (auth()->check())                                       return false;

        // Path excludes (supports prefix match + simple wildcard via fnmatch)
        $path = ltrim($request->path(), '/');
        foreach ($this->excludedPaths() as $ex) {
            if ($ex === '') continue;
            if (str_starts_with($path, $ex) || fnmatch($ex, $path)) return false;
        }

        // Database-driven cache rules (v2.0). First match wins by priority asc.
        if ($matched = $this->matchedRule($path)) {
            if ($matched->action === 'bypass') return false;
            // 'cache' / 'vary_*' fall through to the rest of the checks below
        }

        // Cookie excludes (auth cookies, cart cookies, anything user-specific)
        foreach ($this->excludedCookies() as $ck) {
            if ($ck !== '' && $request->cookies->has($ck)) return false;
        }

        // Don't cache when the user has a real session cookie (logged-in or has cart state)
        $sessionName = config('session.cookie');
        if ($sessionName && $request->cookies->has($sessionName)) {
            // Check if session is actually populated with user state (auth checked above already)
            if (session()->has('cart') || session()->has('user') || session()->has('temp_user_id')) {
                return false;
            }
        }

        // Don't cache query strings except a small allowlist
        if (!empty($request->query()) && !$this->isQueryAllowed($request)) return false;

        return true;
    }

    /**
     * Find the first enabled, priority-ordered cache rule matching the given path.
     * Returns null when rules are disabled or none match.
     */
    public function matchedRule(string $path): ?CacheRule
    {
        if ((int) get_setting('perf_cache_rules_status', 1) !== 1) return null;

        $rules = Cache::remember('perf_cache_rules_cached', 300, function () {
            return CacheRule::where('enabled', true)->orderBy('priority')->get()->all();
        });

        foreach ($rules as $rule) {
            if ($this->patternMatches((string) $rule->pattern, $path)) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Glob/prefix match. Supports '*', '?', and a fallback prefix check.
     */
    protected function patternMatches(string $pattern, string $path): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '') return false;
        if ($pattern === '/') return $path === '' || $path === '/';
        $pattern = ltrim($pattern, '/');
        if ($pattern === '*') return true;
        if (fnmatch($pattern, $path)) return true;
        $bare = rtrim($pattern, '*/');
        return $bare !== '' && str_starts_with($path, $bare);
    }

    /**
     * Resolve effective TTL minutes for a given request — rule override beats global setting.
     */
    public function ttlMinutesFor(Request $request): int
    {
        $matched = $this->matchedRule(ltrim($request->path(), '/'));
        if ($matched && $matched->ttl_minutes && $matched->ttl_minutes > 0) {
            return (int) $matched->ttl_minutes;
        }
        return (int) get_setting('perf_page_cache_ttl_minutes', 720);
    }

    public function flushRuleCache(): void
    {
        Cache::forget('perf_cache_rules_cached');
    }

    protected function isQueryAllowed(Request $request): bool
    {
        $allowed = ['page', 'sort', 'category'];
        foreach (array_keys($request->query()) as $k) {
            if (!in_array($k, $allowed, true)) return false;
        }
        return true;
    }

    protected function excludedPaths(): array
    {
        return array_filter(array_map('trim', explode("\n", (string) get_setting('perf_page_cache_exclude_paths', ''))));
    }

    protected function excludedCookies(): array
    {
        return array_filter(array_map('trim', explode("\n", (string) get_setting('perf_page_cache_exclude_cookies', ''))));
    }

    /**
     * Cache key includes locale + currency + device so multi-lang/currency users don't bleed.
     */
    protected function cacheVariant(?Request $request = null): string
    {
        try {
            $locale = session('locale') ?: app()->getLocale();
        } catch (Exception $e) {
            $locale = 'en';
        }
        try {
            $currency = function_exists('default_currency')
                ? (string) (\App\Models\Currency::where('id', \App\Models\BusinessSetting::where('type', 'system_default_currency')->value('value'))->value('code') ?? 'USD')
                : 'USD';
        } catch (Exception $e) {
            $currency = 'USD';
        }
        $device = 'd';
        if ($request) {
            $ua = (string) $request->userAgent();
            if ($ua && preg_match('/Mobi|Android|iPhone|iPad/i', $ua)) $device = 'm';
        }
        return "{$locale}_{$currency}_{$device}";
    }

    // ── Read / Write ─────────────────────────────────────────────────

    public function key(string $url, ?Request $request = null): string
    {
        return 'perf_page_cache_' . md5($url . '|' . $this->cacheVariant($request));
    }

    public function pathFor(string $url, ?Request $request = null): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . md5($url . '|' . $this->cacheVariant($request)) . '.html';
    }

    public function has(string $url, ?Request $request = null): bool
    {
        if ($this->driver === 'redis') {
            try { return Cache::store('redis')->has($this->key($url, $request)); }
            catch (Exception $e) { return file_exists($this->pathFor($url, $request)); }
        }
        $path = $this->pathFor($url, $request);
        if (!file_exists($path)) return false;
        $ttl  = $request ? $this->ttlMinutesFor($request) : (int) get_setting('perf_page_cache_ttl_minutes', 720);
        if ($ttl > 0 && (time() - @filemtime($path)) > $ttl * 60) {
            @unlink($path);
            return false;
        }
        return true;
    }

    public function get(string $url, ?Request $request = null): ?string
    {
        if ($this->driver === 'redis') {
            try { return Cache::store('redis')->get($this->key($url, $request)); }
            catch (Exception $e) {}
        }
        $path = $this->pathFor($url, $request);
        return file_exists($path) ? @file_get_contents($path) ?: null : null;
    }

    public function store(string $url, string $html, ?Request $request = null): bool
    {
        try {
            $html .= "\n<!-- Cached by Performance Optimizer @ " . date('Y-m-d H:i:s') . " -->";
            $ttlMin = $request ? $this->ttlMinutesFor($request) : (int) get_setting('perf_page_cache_ttl_minutes', 720);
            if ($this->driver === 'redis') {
                Cache::store('redis')->put($this->key($url, $request), $html, max(1, $ttlMin) * 60);
                return true;
            }
            @file_put_contents($this->pathFor($url, $request), $html);
            return true;
        } catch (Exception $e) {
            Log::error('[PerfOptimizer] Page cache store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function forget(string $url, ?Request $request = null): void
    {
        if ($this->driver === 'redis') {
            try { Cache::store('redis')->forget($this->key($url, $request)); } catch (Exception $e) {}
        }
        $path = $this->pathFor($url, $request);
        if (file_exists($path)) @unlink($path);
    }

    public function clearAll(): int
    {
        $count = 0;
        if ($this->driver === 'redis') {
            try {
                $redis = Cache::store('redis')->getStore()->getRedis();
                foreach ($redis->keys('*perf_page_cache_*') as $k) {
                    $redis->del($k);
                    $count++;
                }
            } catch (Exception $e) {}
        }
        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir . '/*.html') ?: [] as $f) {
                @unlink($f);
                $count++;
            }
        }
        OptimizationLog::create([
            'type' => 'page_cache', 'action' => 'clear_all', 'status' => 'success',
            'meta' => ['pages_cleared' => $count, 'driver' => $this->driver],
        ]);
        return $count;
    }

    public function getStats(): array
    {
        if (!is_dir($this->cacheDir)) return ['pages' => 0, 'size' => '0 B', 'driver' => $this->driver];
        $files = glob($this->cacheDir . '/*.html') ?: [];
        $size  = 0;
        foreach ($files as $f) $size += @filesize($f) ?: 0;
        return [
            'pages'  => count($files),
            'size'   => $this->humanSize($size),
            'driver' => $this->driver,
        ];
    }

    // ── Warm cache from sitemap ──────────────────────────────────────

    public function warmFromSitemap(int $max = 100): array
    {
        $base       = rtrim(url('/'), '/');
        $sitemapUrl = $base . '/sitemap.xml';
        $urls       = [$base];
        $warmed     = 0;
        $failed     = 0;
        $verifyTls  = !str_contains($base, 'localhost') && !str_contains($base, '127.0.0.1');

        try {
            $http = $verifyTls ? Http::timeout(10) : Http::timeout(10)->withoutVerifying();
            $response = $http->get($sitemapUrl);
            if ($response->successful()) {
                preg_match_all('/<loc>(.*?)<\/loc>/s', $response->body(), $matches);
                if (!empty($matches[1])) {
                    $urls = array_merge($urls, array_map('trim', $matches[1]));
                }
            }
        } catch (Exception $e) {
            Log::warning('[PerfOptimizer] sitemap fetch failed: ' . $e->getMessage());
        }

        $urls = array_unique($urls);
        foreach (array_slice($urls, 0, $max) as $u) {
            try {
                $http = $verifyTls ? Http::timeout(20) : Http::timeout(20)->withoutVerifying();
                $resp = $http->get($u);
                if ($resp->successful()) {
                    $this->store($u, $resp->body());
                    $warmed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
            }
        }

        OptimizationLog::create([
            'type' => 'page_cache', 'action' => 'warm', 'status' => 'success',
            'meta' => ['warmed' => $warmed, 'failed' => $failed, 'total_urls' => count($urls)],
        ]);
        return ['warmed' => $warmed, 'failed' => $failed, 'total' => count($urls)];
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
