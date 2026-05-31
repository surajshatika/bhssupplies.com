<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\CacheRule;
use App\Models\PerformanceOptimizer\OptimizationLog;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PageCacheService
{
    private const MEMORY_DRIVERS = ['redis', 'memcached'];
    private const INDEX_KEY = 'perf_page_cache_index';
    private const CSRF_PLACEHOLDER = '__PERFOPT_CSRF_TOKEN__';

    /**
     * Paths that are ALWAYS bypassed regardless of any admin cache settings.
     * These pages carry user-specific state and must never be served from static cache.
     * Add full path prefix (no leading slash). Exact match OR prefix + "/" is checked.
     */
    private const NEVER_CACHE_PREFIXES = [
        // Transactional
        'cart',
        'checkout',
        'order-confirmed',
        // Authentication
        'login',
        'logout',
        'register',
        'registration',
        'password',
        'password/reset',
        'password/email',
        'forgot-password',
        'reset-password',
        'confirm-password',
        'email/verify',
        'email/resend',
        'refresh-csrf',
        'users/login',
        'users/registration',
        'seller/login',
        'seller/registration',
        'deliveryboy/login',
        'social-login',
        'account-deletion',
        // Authenticated user area
        'dashboard',
        'profile',
        'purchase_history',
        'digital-purchase-history',
        'digital-products',
        'wallet',
        'wallet_recharge_success',
        'addresses',
        'wishlists',
        'all-notifications',
        'notification',
        'non-linkable-notification-read',
        'invoice',
        'support_ticket',
        'track-your-order',
        'compare',
        're-order',
        // Payment gateway callbacks
        'paypal',
        'stripe',
        'mercadopago',
        'cyber-source',
        'myfatoorah',
        // Admin area (belt-and-braces)
        'admin',
    ];

    protected string $cacheDir;
    protected string $driver;
    protected array $excludedPathsCache = [];
    protected array $excludedCookiesCache = [];
    protected array $matchedRuleCache = [];
    protected array $ttlCache = [];
    protected array $settings = [];

    public function __construct()
    {
        $this->cacheDir = storage_path('framework/page-cache');
        $selectedDriver = $this->setting('perf_page_cache_driver', 'file');
        $this->driver   = ($selectedDriver === 'memcached' && !class_exists(\Memcached::class))
            ? 'file'
            : $selectedDriver;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    public function shouldCache(Request $request): bool
    {
        if (!$request->isMethod('GET'))                            return false;
        if ($request->ajax())                                      return false;
        if ($request->wantsJson())                                 return false;
        if ((int) $this->setting('perf_status', 1) !== 1)          return false;
        if ((int) $this->setting('perf_page_cache_status', 0) !== 1) return false;

        // Don't cache for authenticated users
        if (auth()->check())                                       return false;

        // Hard-coded bypass — user-specific / transactional pages are never cached.
        $path = ltrim($request->path(), '/');
        if ($this->isNeverCachePath($path)) {
            return false;
        }

        // Admin-configured path excludes (supports prefix match + simple wildcard via fnmatch)
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

    public function diagnoseUrl(string $url): array
    {
        $url = trim($url);
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $reasons = [];
        $warnings = [];

        if ((int) $this->setting('perf_status', 1) !== 1) {
            $reasons[] = 'Master switch is OFF.';
        }

        if ((int) $this->setting('perf_page_cache_status', 0) !== 1) {
            $reasons[] = 'Page cache is OFF.';
        }

        if ($this->driver === 'memcached' && !class_exists(\Memcached::class)) {
            $warnings[] = 'Memcached extension is missing; file cache fallback is active.';
        }

        if ($matchedNeverCache = $this->matchedNeverCachePrefix($path)) {
            $reasons[] = "Path is protected by the never-cache list: {$matchedNeverCache}.";
        }

        foreach ($this->excludedPaths() as $ex) {
            if ($ex !== '' && (str_starts_with($path, $ex) || fnmatch($ex, $path))) {
                $reasons[] = "Path matches admin exclude rule: {$ex}.";
                break;
            }
        }

        if ($matched = $this->matchedRule($path)) {
            if ($matched->action === 'bypass') {
                $reasons[] = "Cache rule #{$matched->id} forces bypass.";
            } else {
                $warnings[] = "Cache rule #{$matched->id} matched with action {$matched->action}.";
            }
        }

        if (!empty($query) && !$this->isQueryAllowedKeys(array_keys($query))) {
            $reasons[] = 'Query string contains non-cacheable parameters.';
        }

        $request = Request::create($url ?: '/', 'GET');
        $ttl = 0;
        try {
            $ttl = $this->ttlMinutesFor($request);
        } catch (\Throwable $e) {
            $warnings[] = 'TTL could not be resolved: ' . $e->getMessage();
        }

        return [
            'url' => $url,
            'path' => $path === '' ? '/' : '/' . $path,
            'driver' => $this->driver,
            'cacheable' => empty($reasons),
            'reasons' => $reasons,
            'warnings' => $warnings,
            'ttl_minutes' => $ttl,
            'stored_copy' => $this->has($url, $request),
        ];
    }

    /**
     * Find the first enabled, priority-ordered cache rule matching the given path.
     * Returns null when rules are disabled or none match.
     */
    public function matchedRule(string $path): ?CacheRule
    {
        if ((int) $this->setting('perf_cache_rules_status', 1) !== 1) return null;
        if (array_key_exists($path, $this->matchedRuleCache)) {
            return $this->matchedRuleCache[$path];
        }

        $rules = Cache::remember('perf_cache_rules_cached', 300, function () {
            return CacheRule::where('enabled', true)->orderBy('priority')->get()->all();
        });

        foreach ($rules as $rule) {
            if ($this->patternMatches((string) $rule->pattern, $path)) {
                return $this->matchedRuleCache[$path] = $rule;
            }
        }
        return $this->matchedRuleCache[$path] = null;
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
        $path = ltrim($request->path(), '/');
        if (isset($this->ttlCache[$path])) {
            return $this->ttlCache[$path];
        }

        $matched = $this->matchedRule($path);
        if ($matched && $matched->ttl_minutes && $matched->ttl_minutes > 0) {
            return $this->ttlCache[$path] = (int) $matched->ttl_minutes;
        }
        return $this->ttlCache[$path] = (int) $this->setting('perf_page_cache_ttl_minutes', 720);
    }

    public function flushRuleCache(): void
    {
        Cache::forget('perf_cache_rules_cached');
    }

    protected function isQueryAllowed(Request $request): bool
    {
        return $this->isQueryAllowedKeys(array_keys($request->query()));
    }

    protected function isQueryAllowedKeys(array $keys): bool
    {
        $allowed = ['page', 'sort', 'category'];
        foreach ($keys as $k) {
            if (!in_array($k, $allowed, true)) return false;
        }
        return true;
    }

    protected function excludedPaths(): array
    {
        if (!$this->excludedPathsCache) {
            $this->excludedPathsCache = array_filter(array_map('trim', explode("\n", $this->setting('perf_page_cache_exclude_paths', ''))));
        }
        return $this->excludedPathsCache;
    }

    protected function excludedCookies(): array
    {
        if (!$this->excludedCookiesCache) {
            $this->excludedCookiesCache = array_filter(array_map('trim', explode("\n", $this->setting('perf_page_cache_exclude_cookies', ''))));
        }
        return $this->excludedCookiesCache;
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
            $currency = $this->defaultCurrencyCode();
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
        if ($this->canUseMemoryDriver()) {
            try { return Cache::store($this->driver)->has($this->key($url, $request)); }
            catch (\Throwable $e) { return file_exists($this->pathFor($url, $request)); }
        }
        $path = $this->pathFor($url, $request);
        if (!file_exists($path)) return false;
        $ttl  = $request ? $this->ttlMinutesFor($request) : (int) $this->setting('perf_page_cache_ttl_minutes', 720);
        if ($ttl > 0 && (time() - @filemtime($path)) > $ttl * 60) {
            @unlink($path);
            return false;
        }
        return true;
    }

    public function get(string $url, ?Request $request = null): ?string
    {
        if ($this->canUseMemoryDriver()) {
            try {
                $cached = Cache::store($this->driver)->get($this->key($url, $request));
                return is_string($cached) ? $this->hydrateHtmlFromSharedCache($cached) : null;
            }
            catch (\Throwable $e) {}
        }
        $path = $this->pathFor($url, $request);
        if (!file_exists($path)) {
            return null;
        }

        $ttl = $request ? $this->ttlMinutesFor($request) : (int) $this->setting('perf_page_cache_ttl_minutes', 720);
        if ($ttl > 0 && (time() - @filemtime($path)) > $ttl * 60) {
            @unlink($path);
            return null;
        }

        $html = @file_get_contents($path) ?: null;
        return is_string($html) ? $this->hydrateHtmlFromSharedCache($html) : null;
    }

    public function store(string $url, string $html, ?Request $request = null): bool
    {
        try {
            $html = $this->prepareHtmlForSharedCache($html);
            $html .= "\n<!-- PerfCache: {$url} @ " . date('Y-m-d H:i:s') . " -->";
            $ttlMin = $request ? $this->ttlMinutesFor($request) : (int) $this->setting('perf_page_cache_ttl_minutes', 720);
            if ($this->canUseMemoryDriver()) {
                try {
                    Cache::store($this->driver)->put($this->key($url, $request), $html, max(1, $ttlMin) * 60);
                    $this->indexMemoryPage($this->key($url, $request), $url, strlen($html), max(1, $ttlMin) * 60);
                    return true;
                } catch (\Throwable $e) {
                    Log::warning('[PerfOptimizer] Memory page cache unavailable, falling back to file: ' . $e->getMessage());
                }
            }
            @file_put_contents($this->pathFor($url, $request), $html);
            return true;
        } catch (\Throwable $e) {
            Log::error('[PerfOptimizer] Page cache store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function prepareHtmlForSharedCache(string $html): string
    {
        if ($html === '') return $html;

        try {
            $token = csrf_token();
            if (is_string($token) && $token !== '') {
                $html = str_replace($token, self::CSRF_PLACEHOLDER, $html);
            }
        } catch (\Throwable $e) {}

        $placeholder = self::CSRF_PLACEHOLDER;
        $html = preg_replace(
            '/(<meta\s+name=["\']csrf-token["\']\s+content=["\'])([^"\']*)(["\'][^>]*>)/i',
            '$1' . $placeholder . '$3',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/(<input\b[^>]*name=["\']_token["\'][^>]*value=["\'])([^"\']*)(["\'][^>]*>)/i',
            '$1' . $placeholder . '$3',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/((?:["\']?_token["\']?\s*:\s*)["\'])([^"\']*)(["\'])/i',
            '$1' . $placeholder . '$3',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/((?:["\']?X-CSRF-TOKEN["\']?\s*:\s*)["\'])([^"\']*)(["\'])/i',
            '$1' . $placeholder . '$3',
            $html
        ) ?? $html;

        return $html;
    }

    public function hydrateHtmlFromSharedCache(string $html): string
    {
        if (!str_contains($html, self::CSRF_PLACEHOLDER)) return $html;

        try {
            return str_replace(self::CSRF_PLACEHOLDER, csrf_token(), $html);
        } catch (\Throwable $e) {
            return $html;
        }
    }

    public function forget(string $url, ?Request $request = null): void
    {
        if ($this->canUseMemoryDriver()) {
            try {
                Cache::store($this->driver)->forget($this->key($url, $request));
                $this->removeMemoryIndex($this->key($url, $request));
            } catch (\Throwable $e) {}
        }
        $path = $this->pathFor($url, $request);
        if (file_exists($path)) @unlink($path);
    }

    public function clearAll(): int
    {
        $count = 0;
        if ($this->canUseMemoryDriver()) {
            try {
                $store = Cache::store($this->driver);
                $index = $this->memoryIndex();
                foreach (array_keys($index) as $k) {
                    $store->forget($k);
                    $count++;
                }
                $store->forget(self::INDEX_KEY);
            } catch (\Throwable $e) {}
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
        if ($this->isMemoryDriver()) {
            if (!$this->canUseMemoryDriver()) {
                return $this->fileStats($this->driver . '/file-fallback');
            }
            try {
                $index = $this->memoryIndex();
                $size = array_sum(array_map(fn($row) => (int) ($row['size_bytes'] ?? 0), $index));
                return [
                    'pages' => count($index),
                    'size' => $size > 0 ? $this->humanSize($size) . ' memory' : '0 B memory',
                    'driver' => $this->driver,
                ];
            } catch (\Throwable $e) {
                return $this->fileStats($this->driver . '/file-fallback');
            }
        }
        return $this->fileStats($this->driver);
    }

    protected function fileStats(string $driver): array
    {
        if (!is_dir($this->cacheDir)) return ['pages' => 0, 'size' => '0 B', 'driver' => $driver];
        $files = glob($this->cacheDir . '/*.html') ?: [];
        $size  = 0;
        foreach ($files as $f) $size += @filesize($f) ?: 0;
        return [
            'pages'  => count($files),
            'size'   => $this->humanSize($size),
            'driver' => $driver,
        ];
    }

    // ── Warm cache from sitemap ──────────────────────────────────────

    public function warmFromSitemap(int $max = 5): array
    {
        $max        = max(1, min($max, 20));
        $base       = rtrim(url('/'), '/');
        $baseHost   = (string) parse_url($base, PHP_URL_HOST);
        $sitemapUrl = $base . '/sitemap.xml';
        $urls       = $this->defaultWarmUrls($base);
        $warmed     = 0;
        $failed     = 0;
        $attempted  = 0;
        $verifyTls  = !str_contains($base, 'localhost') && !str_contains($base, '127.0.0.1');
        $timeout    = max(10, min(45, (int) $this->setting('perf_cache_warm_timeout_seconds', 45)));

        try {
            $http = $verifyTls
                ? Http::connectTimeout(3)->timeout($timeout)
                : Http::connectTimeout(3)->timeout($timeout)->withoutVerifying();
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

        $urls = array_values(array_filter(array_unique($urls), function ($url) use ($baseHost) {
            return $this->isWarmableUrl($url, $baseHost);
        }));

        $cursorKey = 'perf_page_cache_warm_cursor_' . md5($base);
        $cursor = (int) Cache::get($cursorKey, 0);
        if ($cursor < 0 || $cursor >= count($urls)) {
            $cursor = 0;
        }
        $batch = array_slice($urls, $cursor, $max);

        foreach ($batch as $u) {
            $attempted++;
            try {
                $http = $verifyTls
                    ? Http::connectTimeout(3)->timeout($timeout)
                    : Http::connectTimeout(3)->timeout($timeout)->withoutVerifying();
                $resp = $http
                    ->withHeaders(['User-Agent' => 'BHS-Performance-Optimizer-Warm/1.1'])
                    ->get($u);
                if ($resp->successful() && $this->isHtmlWarmResponse($resp)) {
                    if ($this->store($u, $resp->body())) {
                        $warmed++;
                    } else {
                        $failed++;
                    }
                } elseif ($resp->successful()) {
                    $warmed++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('[PerfOptimizer] cache warm URL failed', [
                    'url' => $u,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $nextCursor = count($urls) > 0 ? ($cursor + count($batch)) % count($urls) : 0;
        Cache::put($cursorKey, $nextCursor, 86400);

        try {
            OptimizationLog::create([
                'type' => 'page_cache', 'action' => 'warm', 'status' => $failed > 0 && $warmed === 0 ? 'failed' : 'success',
                'meta' => [
                    'warmed' => $warmed,
                    'failed' => $failed,
                    'attempted' => $attempted,
                    'total_urls' => count($urls),
                    'cursor' => $cursor,
                    'next_cursor' => $nextCursor,
                    'driver' => $this->driver,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PerfOptimizer] cache warm log failed: ' . $e->getMessage());
        }

        return [
            'warmed' => $warmed,
            'failed' => $failed,
            'attempted' => $attempted,
            'total' => count($urls),
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
        ];
    }

    protected function defaultWarmUrls(string $base): array
    {
        return Cache::remember('perf_default_warm_urls', 300, function () use ($base) {
            $urls = [$base];

            try {
                $categoryQuery = Category::query()
                    ->select('slug')
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->orderByDesc('id')
                    ->limit(8);

                if (Schema::hasColumn('categories', 'published')) {
                    $categoryQuery->where('published', 1);
                }

                foreach ($categoryQuery->pluck('slug') as $slug) {
                    $urls[] = route('products.category', $slug);
                }
            } catch (\Throwable $e) {
                Log::debug('[PerfOptimizer] category warm seed failed: ' . $e->getMessage());
            }

            try {
                $productQuery = Product::query()
                    ->select('slug')
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->orderByDesc('id')
                    ->limit(10);

                if (Schema::hasColumn('products', 'published')) {
                    $productQuery->where('published', 1);
                }
                if (Schema::hasColumn('products', 'approved')) {
                    $productQuery->where('approved', 1);
                }
                if (Schema::hasColumn('products', 'auction_product')) {
                    $productQuery->where(function ($query) {
                        $query->whereNull('auction_product')->orWhere('auction_product', 0);
                    });
                }

                foreach ($productQuery->pluck('slug') as $slug) {
                    $urls[] = route('product', $slug);
                }
            } catch (\Throwable $e) {
                Log::debug('[PerfOptimizer] product warm seed failed: ' . $e->getMessage());
            }

            return array_values(array_unique($urls));
        });
    }

    protected function isWarmableUrl(string $url, string $baseHost): bool
    {
        $url = trim($url);
        if ($url === '') return false;

        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($baseHost !== '' && $host !== '' && strcasecmp($host, $baseHost) !== 0) {
            return false;
        }

        $scheme = (string) parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== '' && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($this->isNeverCachePath($path)) return false;

        foreach ($this->excludedPaths() as $ex) {
            if ($ex !== '' && (str_starts_with($path, $ex) || fnmatch($ex, $path))) {
                return false;
            }
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if (!empty($query)) {
            foreach (array_keys($query) as $key) {
                if (!in_array($key, ['page', 'sort', 'category'], true)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function isHtmlWarmResponse($response): bool
    {
        $contentType = strtolower((string) $response->header('Content-Type', ''));
        return $contentType === '' || str_contains($contentType, 'text/html');
    }

    public function isNeverCachePath(string $path): bool
    {
        return $this->matchedNeverCachePrefix($path) !== null;
    }

    protected function matchedNeverCachePrefix(string $path): ?string
    {
        $path = ltrim($path, '/');
        foreach (self::NEVER_CACHE_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $prefix;
            }
        }
        return null;
    }

    /**
     * List recently cached file-backed pages (up to $limit).
     * Each entry: {url, size_bytes, cached_at}
     */
    public function getPagesList(int $limit = 50): array
    {
        if ($this->isMemoryDriver()) {
            if (!$this->canUseMemoryDriver()) {
                return [];
            }
            try {
                $pages = array_values($this->memoryIndex());
                usort($pages, fn($a, $b) => strtotime((string) ($b['cached_at'] ?? '')) <=> strtotime((string) ($a['cached_at'] ?? '')));
                return array_slice($pages, 0, $limit);
            } catch (\Throwable $e) {
                return [];
            }
        }
        if (!in_array($this->driver, ['file', 'litespeed'], true)) return [];
        if (!is_dir($this->cacheDir)) return [];

        $files = glob($this->cacheDir . '/*.html') ?: [];
        if (empty($files)) return [];

        // Sort newest first by modification time
        usort($files, fn($a, $b) => (@filemtime($b) ?: 0) - (@filemtime($a) ?: 0));
        $files = array_slice($files, 0, $limit);

        $pages = [];
        foreach ($files as $f) {
            $size  = @filesize($f) ?: 0;
            $mtime = @filemtime($f) ?: 0;
            $url   = null;

            // Extract URL from last line: <!-- PerfCache: URL @ DATETIME -->
            // Older entries may use: <!-- Cached by Performance Optimizer @ DATETIME -->
            $fp = @fopen($f, 'r');
            if ($fp) {
                @fseek($fp, max(0, $size - 350));
                $tail = (string) @fread($fp, 350);
                @fclose($fp);
                if (preg_match('/<!--\s*PerfCache:\s*(https?:\/\/[^\s]+)\s*@/', $tail, $m)) {
                    $url = $m[1];
                }
            }

            $pages[] = [
                'url'        => $url,
                'size_bytes' => $size,
                'cached_at'  => $mtime ? date('Y-m-d H:i:s', $mtime) : null,
            ];
        }

        return $pages;
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    protected function defaultCurrencyCode(): string
    {
        $currencyId = (string) get_setting('system_default_currency', '');
        return Cache::remember('perf_default_currency_code_' . $currencyId, 86400, function () use ($currencyId) {
            return (string) (Currency::where('id', $currencyId)->value('code') ?: 'USD');
        });
    }

    protected function setting(string $key, $default = null): string
    {
        return (string) get_setting($key, $default);
    }

    protected function isMemoryDriver(): bool
    {
        return in_array($this->driver, self::MEMORY_DRIVERS, true);
    }

    protected function canUseMemoryDriver(): bool
    {
        if (!$this->isMemoryDriver()) return false;
        if ($this->driver === 'memcached' && !class_exists(\Memcached::class)) return false;
        return true;
    }

    protected function memoryIndex(): array
    {
        if (!$this->isMemoryDriver()) return [];
        $index = Cache::store($this->driver)->get(self::INDEX_KEY, []);
        return is_array($index) ? $index : [];
    }

    protected function indexMemoryPage(string $key, string $url, int $sizeBytes, int $ttlSeconds): void
    {
        $index = $this->memoryIndex();
        $index[$key] = [
            'url' => $url,
            'size_bytes' => $sizeBytes,
            'cached_at' => date('Y-m-d H:i:s'),
        ];
        Cache::store($this->driver)->put(self::INDEX_KEY, $index, max($ttlSeconds, 86400));
    }

    protected function removeMemoryIndex(string $key): void
    {
        $index = $this->memoryIndex();
        if (!array_key_exists($key, $index)) return;
        unset($index[$key]);
        Cache::store($this->driver)->put(self::INDEX_KEY, $index, 86400);
    }
}
