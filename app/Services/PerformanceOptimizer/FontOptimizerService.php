<?php

namespace App\Services\PerformanceOptimizer;

class FontOptimizerService
{
    public function getPreloadList(): array
    {
        $raw = (string) get_setting('perf_fonts_preload_list', '');
        return array_filter(array_map('trim', explode("\n", $raw)));
    }

    public function renderPreloadTags(): string
    {
        if ((int) get_setting('perf_fonts_preload_status', 0) !== 1) return '';
        $tags = [];
        foreach ($this->getPreloadList() as $url) {
            $url   = htmlspecialchars($url, ENT_QUOTES);
            $type  = $this->mimeOf($url);
            $cross = ' crossorigin="anonymous"';
            $tags[] = '<link rel="preload" as="font" type="' . $type . '" href="' . $url . '"' . $cross . '>';
        }
        return implode("\n", $tags);
    }

    public function renderFontDisplaySwap(): string
    {
        if ((int) get_setting('perf_fonts_swap_status', 1) !== 1) return '';
        return '<style data-perfopt="font-swap">@font-face{font-display:swap !important;}</style>';
    }

    protected function mimeOf(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? $url, PATHINFO_EXTENSION));
        return match ($ext) {
            'woff2' => 'font/woff2',
            'woff'  => 'font/woff',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            'eot'   => 'application/vnd.ms-fontobject',
            default => 'font/woff2',
        };
    }
}
