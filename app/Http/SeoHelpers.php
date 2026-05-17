<?php

if (!function_exists('seo_defer_script')) {
    /**
     * Render a <script defer> tag. Use in Blade for any non-critical JS so
     * it doesn't block the parser or LCP.
     *
     *   {!! seo_defer_script(asset('js/aiz-inline.js')) !!}
     */
    function seo_defer_script(string $src, array $attrs = []): string
    {
        $extras = '';
        foreach ($attrs as $k => $v) {
            $extras .= ' ' . htmlspecialchars($k, ENT_QUOTES) . '="' . htmlspecialchars((string) $v, ENT_QUOTES) . '"';
        }
        return '<script defer src="' . htmlspecialchars($src, ENT_QUOTES) . '"' . $extras . '></script>';
    }
}

if (!function_exists('seo_async_script')) {
    /** Render a <script async> tag for fully independent third-party tags. */
    function seo_async_script(string $src, array $attrs = []): string
    {
        $extras = '';
        foreach ($attrs as $k => $v) {
            $extras .= ' ' . htmlspecialchars($k, ENT_QUOTES) . '="' . htmlspecialchars((string) $v, ENT_QUOTES) . '"';
        }
        return '<script async src="' . htmlspecialchars($src, ENT_QUOTES) . '"' . $extras . '></script>';
    }
}

if (!function_exists('seo_critical_css_inline')) {
    /**
     * Inline a per-route critical-CSS file from public/assets/css/critical/
     * if it exists, otherwise return an empty string. Production setups can
     * pre-generate these files via any external critical-CSS tool (Penthouse,
     * critical.css npm package) and drop them in this folder per route slug.
     *
     *   {!! seo_critical_css_inline('home') !!}
     */
    function seo_critical_css_inline(string $routeSlug): string
    {
        $path = public_path('assets/css/critical/' . preg_replace('/[^a-z0-9_-]/i', '', $routeSlug) . '.css');
        if (!file_exists($path) || filesize($path) === 0) {
            return '';
        }

        $css = @file_get_contents($path);
        if ($css === false || $css === '') {
            return '';
        }

        return '<style data-source="critical-' . htmlspecialchars($routeSlug, ENT_QUOTES) . '">' . $css . '</style>';
    }
}

if (!function_exists('seo_preload_stylesheet')) {
    /**
     * Async-load a full CSS file via <link rel="preload"> + onload swap.
     * Pair with seo_critical_css_inline() to avoid render-blocking CSS.
     */
    function seo_preload_stylesheet(string $href): string
    {
        $h = htmlspecialchars($href, ENT_QUOTES);
        return '<link rel="preload" as="style" href="' . $h . '" onload="this.onload=null;this.rel=\'stylesheet\'">'
             . '<noscript><link rel="stylesheet" href="' . $h . '"></noscript>';
    }
}
