<?php

namespace App\Http\Middleware;

use App\Services\Marketing\EventStore;
use Closure;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * First-party marketing tracking middleware.
 *
 *  - Assigns a 13-month anonymous ID cookie (`mm_anon_id`)
 *  - Assigns a 30-min session ID cookie (`mm_session_id`) — refreshed on each request
 *  - On landing with utm_* query params, persists them as 30-day cookies so they
 *    follow the user through their session and attach to any later Purchase.
 *  - Records a lightweight PageView event for non-admin GET html requests.
 */
class MarketingTracking
{
    /** UTM keys we capture. */
    protected array $utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function handle(Request $request, Closure $next)
    {
        $cookies = app(CookieJar::class);
        $response = $next($request);

        try {
            $consent = $this->consent($request);

            // 1) Anonymous ID — only if analytics OR consent disabled in admin (legacy)
            if ($consent['analytics'] && !$request->cookie('mm_anon_id')) {
                $anonId = (string) Str::uuid();
                $cookies->queue('mm_anon_id', $anonId, 60 * 24 * 395);
            }

            // 2) Session ID — sliding 30 min
            if ($consent['analytics']) {
                $sessionId = $request->cookie('mm_session_id') ?: (string) Str::uuid();
                $cookies->queue('mm_session_id', $sessionId, 30);
            }

            // 3) UTM persistence — 30 days (marketing consent)
            if ($consent['marketing']) {
                foreach ($this->utmKeys as $k) {
                    $cookieKey = 'mm_' . $k;
                    if ($request->query($k)) {
                        $cookies->queue($cookieKey, (string) $request->query($k), 60 * 24 * 30);
                    }
                }
            }

            // 4) PageView record — only when analytics consent granted
            if ($consent['analytics'] && $this->shouldRecordPageView($request)) {
                app(EventStore::class)->record('PageView', [
                    'value'    => null,
                    'currency' => null,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::debug('[MarketingTracking] middleware suppressed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Read the mm_consent cookie set by the consent banner. Defaults to "all
     * granted" when consent feature is disabled in admin (backwards-compatible).
     */
    protected function consent(Request $request): array
    {
        if ((int) get_setting('marketing_consent_enabled') !== 1) {
            return ['analytics' => true, 'marketing' => true, 'preferences' => true];
        }
        $raw = $request->cookie('mm_consent');
        if (!$raw) {
            return ['analytics' => false, 'marketing' => false, 'preferences' => false];
        }
        $decoded = json_decode($raw, true);
        return [
            'analytics'   => (bool) ($decoded['analytics']   ?? false),
            'marketing'   => (bool) ($decoded['marketing']   ?? false),
            'preferences' => (bool) ($decoded['preferences'] ?? false),
        ];
    }

    protected function shouldRecordPageView(Request $request): bool
    {
        if (!$request->isMethod('GET')) return false;
        if ($request->ajax() || $request->wantsJson()) return false;
        if ($request->is('admin*', 'api/*', 'public/*', 'storage/*', '*.js', '*.css', '*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg', '*.ico', '*.woff*', '*.map')) return false;
        $accept = (string) $request->header('Accept');
        if ($accept && !str_contains($accept, 'text/html') && $accept !== '*/*') return false;
        return true;
    }
}
