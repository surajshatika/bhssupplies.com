<?php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Consumes the seo_redirects table and emits 301/302 responses before route
 * resolution. The redirect map is cached for 1 hour and busted by the
 * SeoRedirect model's saved/deleted events (see App\Providers\SeoServiceProvider).
 *
 * Match rules:
 *   - The request path is normalised to "/path" (no querystring).
 *   - Lookup checks the path, the path + "/", and the full URL as keys.
 *   - Admin/api/storage requests are bypassed.
 */
class SeoRedirectMiddleware
{
    public const CACHE_KEY = 'seo:redirects:map';
    public const CACHE_TTL = 3600;
    public const MAX_RULES = 5000;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldEvaluate($request)) {
            return $next($request);
        }

        $map = $this->loadMap();
        if (empty($map)) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');
        $candidates = [
            $path,
            rtrim($path, '/') . '/',
            $request->fullUrl(),
            $request->url(),
        ];

        foreach ($candidates as $key) {
            if (!isset($map[$key])) {
                continue;
            }

            $rule = $map[$key];
            $this->recordHit($rule['id'] ?? null);
            return redirect()->away($rule['target'], $rule['status']);
        }

        return $next($request);
    }

    protected function shouldEvaluate(Request $request): bool
    {
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return false;
        }
        $path = $request->path();
        foreach (['admin', 'admin/', 'api/', 'storage/', 'assets/', 'public/', 'livewire/'] as $skip) {
            if (str_starts_with($path, $skip)) {
                return false;
            }
        }
        return true;
    }

    protected function loadMap(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                if (!Schema::hasTable('seo_redirects')) {
                    return [];
                }

                return SeoRedirect::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->limit(self::MAX_RULES)
                    ->get(['id', 'source_url', 'target_url', 'status_code'])
                    ->keyBy('source_url')
                    ->map(fn($r) => [
                        'id'     => $r->id,
                        'target' => $r->target_url,
                        'status' => (int) $r->status_code ?: 301,
                    ])
                    ->toArray();
            });
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function recordHit(?int $id): void
    {
        if (!$id) {
            return;
        }
        try {
            DB::table('seo_redirects')
                ->where('id', $id)
                ->update([
                    'hit_count' => DB::raw('hit_count + 1'),
                    'last_hit_at' => now(),
                ]);
        } catch (Throwable $e) {
            // Hit-tracking is best-effort. Never block a redirect on this.
        }
    }
}
