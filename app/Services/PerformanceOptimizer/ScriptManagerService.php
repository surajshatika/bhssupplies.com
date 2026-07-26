<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\ScriptRule;
use Illuminate\Support\Facades\Cache;

/**
 * Per-route allow/deny/defer/async/delay matrix for <script> tags.
 *
 * Rules are matched against a script's `src` attribute OR inline body content
 * via case-insensitive substring search. First matching rule (ordered by
 * priority asc) wins per script tag.
 */
class ScriptManagerService
{
    /**
     * Return enabled rules applicable to (routePath, device) — sorted by priority.
     *
     * @return array<int, array{script_pattern:string, action:string}>
     */
    public function rulesFor(string $routePath, string $device): array
    {
        if ((int) get_setting('perf_script_manager_status', 0) !== 1) {
            return [];
        }

        $all = Cache::remember('perf_script_rules_cached', 300, function () {
            return ScriptRule::where('enabled', true)
                ->orderBy('priority')
                ->get(['script_pattern', 'route_pattern', 'device_filter', 'action'])
                ->toArray();
        });

        $out = [];
        foreach ($all as $r) {
            if (!$this->routeMatches($r['route_pattern'] ?? '*', $routePath)) continue;
            if (!$this->deviceMatches($r['device_filter'] ?? 'any', $device))  continue;
            $out[] = [
                'script_pattern' => (string) ($r['script_pattern'] ?? ''),
                'action'         => (string) ($r['action'] ?? 'allow'),
            ];
        }
        return $out;
    }

    /**
     * Apply the given rules to a chunk of HTML. Returns the rewritten HTML.
     */
    public function applyToHtml(string $html, array $rules): string
    {
        if (empty($rules)) return $html;

        // Safety baseline: scripts whose src contains any of these are NEVER deferred/denied/delayed,
        // regardless of user rules. The layout has inline <script> blocks that call $() / AIZ.* at parse
        // time — touching these breaks home-section AJAX loaders, infinite scroll, modals, etc.
        $protectedSrcSubstrings = [
            'jquery', 'vendors.js', 'aiz-core', 'bootstrap', 'slick',
            'checkout', 'stripe', 'recaptcha',
        ];

        return preg_replace_callback('/<script\b([^>]*)>([\s\S]*?)<\/script>/i', function ($m) use ($rules, $protectedSrcSubstrings) {
            $attrs   = $m[1];
            $content = $m[2];
            $src     = '';
            if (preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $sm)) {
                $src = $sm[2];
            }

            // Hard skip for core scripts — short-circuit before any rule can match them
            if ($src !== '') {
                foreach ($protectedSrcSubstrings as $p) {
                    if (stripos($src, $p) !== false) return $m[0];
                }
            }

            foreach ($rules as $rule) {
                $needle = $rule['script_pattern'];
                if ($needle === '') continue;

                $hit = $src !== '' && stripos($src, $needle) !== false;
                if (!$hit && $content !== '') {
                    $hit = stripos($content, $needle) !== false;
                }
                if (!$hit) continue;

                switch ($rule['action']) {
                    case 'allow':
                        return $m[0];

                    case 'deny':
                        return '<!-- script blocked by perf:' . htmlspecialchars($needle, ENT_QUOTES | ENT_HTML5) . ' -->';

                    case 'defer':
                        if (!preg_match('/\bdefer\b/i', $attrs) && !preg_match('/\basync\b/i', $attrs) && !preg_match('/\btype\s*=\s*["\']?module/i', $attrs)) {
                            return '<script defer' . $attrs . '>' . $content . '</script>';
                        }
                        return $m[0];

                    case 'async':
                        if (!preg_match('/\basync\b/i', $attrs) && !preg_match('/\bdefer\b/i', $attrs) && !preg_match('/\btype\s*=\s*["\']?module/i', $attrs)) {
                            return '<script async' . $attrs . '>' . $content . '</script>';
                        }
                        return $m[0];

                    case 'delay':
                        // Strip an existing type=... so we can replace it cleanly.
                        $newAttrs = preg_replace('/\btype\s*=\s*(["\'])[^"\']*\1/i', '', $attrs);
                        $newAttrs = trim($newAttrs);
                        $marker   = ' data-perf-delay="1"';
                        if (stripos($newAttrs, 'data-perf-delay') === false) $newAttrs .= $marker;
                        return '<script type="text/perf-delay" ' . $newAttrs . '>' . $content . '</script>';
                }
            }
            return $m[0];
        }, $html);
    }

    /**
     * Invalidate the in-memory rule cache. Call after CRUD.
     */
    public function flushCache(): void
    {
        Cache::forget('perf_script_rules_cached');
    }

    /**
     * fnmatch-style route match, with prefix-match fallback.
     * Examples:
     *   '*'         matches anything
     *   'checkout*' matches 'checkout', 'checkout/step-1'
     *   'product/*' matches 'product/123'
     */
    protected function routeMatches(string $pattern, string $path): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '' || $pattern === '*') return true;
        if (fnmatch($pattern, $path)) return true;
        $bare = rtrim($pattern, '*/');
        return $bare !== '' && str_starts_with($path, $bare);
    }

    protected function deviceMatches(string $filter, string $device): bool
    {
        return $filter === 'any' || $filter === $device;
    }

    /**
     * Sensible defaults for Active eCommerce CMS — used by the "Apply Defaults" button.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultRules(): array
    {
        // NOTE: jquery / vendors.js / aiz-core.js must NEVER appear here with defer/deny/delay actions.
        // The layout has inline <script> blocks that call $() / AIZ.* at parse time; touching the core
        // bundle breaks home-section AJAX loaders, infinite scroll, modals, etc. The applyToHtml() guard
        // in this service also short-circuits those scripts, but keeping them out of defaults is the
        // first line of defence so a user clicking "Apply Defaults" doesn't even see them in the table.
        return [
            ['script_pattern' => 'firebase',          'route_pattern' => '*',          'device_filter' => 'any', 'action' => 'delay', 'priority' => 20, 'enabled' => 1, 'note' => 'Push notifications — load on first interaction'],
            ['script_pattern' => 'recaptcha',         'route_pattern' => 'checkout*',  'device_filter' => 'any', 'action' => 'allow', 'priority' => 25, 'enabled' => 1, 'note' => 'Required for checkout'],
            ['script_pattern' => 'recaptcha',         'route_pattern' => 'login*',     'device_filter' => 'any', 'action' => 'allow', 'priority' => 26, 'enabled' => 1, 'note' => 'Required for login'],
            ['script_pattern' => 'recaptcha',         'route_pattern' => '*',          'device_filter' => 'any', 'action' => 'deny',  'priority' => 30, 'enabled' => 1, 'note' => 'Block recaptcha outside auth/checkout'],
            ['script_pattern' => 'sweetalert',        'route_pattern' => '*',          'device_filter' => 'any', 'action' => 'defer', 'priority' => 40, 'enabled' => 1, 'note' => null],
            ['script_pattern' => 'slick',             'route_pattern' => 'cart*',      'device_filter' => 'any', 'action' => 'deny',  'priority' => 45, 'enabled' => 1, 'note' => 'No sliders on cart'],
            ['script_pattern' => 'slick',             'route_pattern' => 'checkout*',  'device_filter' => 'any', 'action' => 'deny',  'priority' => 46, 'enabled' => 1, 'note' => 'No sliders on checkout'],
        ];
    }
}
