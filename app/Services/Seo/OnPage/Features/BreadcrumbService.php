<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class BreadcrumbService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $url   = $payload['url'] ?? url('/');
        $title = $payload['title'] ?? '';
        $items = $payload['breadcrumb_items'] ?? [];

        if (empty($items)) {
            $items = $this->buildFromUrl($url, $title);
        }

        $schema   = $this->buildSchema($url, $items);
        $aiTips   = null;

        if ($this->ai()->isConfigured()) {
            $prompt = "Generate SEO-optimized breadcrumb labels for a page titled '{$title}' at URL: {$url}\n"
                . "Current breadcrumb path: " . implode(' > ', array_column($items, 'name')) . "\n"
                . "Suggest improvements for better SEO and UX, and provide a short explanation.";
            $aiTips = $this->ai()->generate($prompt, 'You are an SEO breadcrumb optimization expert.');
        }

        return [
            'url'         => $url,
            'items'       => $items,
            'schema_json' => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'ai_tips'     => $aiTips,
        ];
    }

    protected function buildFromUrl(string $url, string $title): array
    {
        $parsed = parse_url($url);
        $path   = trim($parsed['path'] ?? '', '/');
        $parts  = $path ? explode('/', $path) : [];

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $items = [['name' => 'Home', 'url' => $base . '/']];
        $cumulative = $base;

        foreach ($parts as $index => $part) {
            $cumulative .= '/' . $part;
            $name = ucwords(str_replace(['-', '_'], ' ', $part));
            if ($index === count($parts) - 1 && $title) {
                $name = $title;
            }
            $items[] = ['name' => $name, 'url' => $cumulative];
        }

        return $items;
    }

    protected function buildSchema(string $url, array $items): array
    {
        $elements = [];
        foreach ($items as $position => $item) {
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $position + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
