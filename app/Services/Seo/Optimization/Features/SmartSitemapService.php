<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Schema;

class SmartSitemapService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $baseUrl = rtrim($payload['base_url'] ?? url('/'), '/');
        $split   = (bool) ($payload['split'] ?? false);

        $groups  = $this->collectAllUrls($baseUrl);
        $allUrls = array_merge(...array_values($groups));

        // Sort by priority descending
        usort($allUrls, fn($a, $b) => strcmp($b['priority'], $a['priority']));

        $xml   = $this->buildSitemap($allUrls);
        $index = $this->buildSitemapIndex($baseUrl);

        if ($payload['persist'] ?? false) {
            file_put_contents(base_path('sitemap.xml'), $xml);
            file_put_contents(public_path('sitemap-index.xml'), $index);
            // Write split sitemaps
            foreach ($groups as $type => $urls) {
                if (!empty($urls)) {
                    file_put_contents(public_path("sitemap-{$type}.xml"), $this->buildSitemap($urls));
                }
            }
        }

        return [
            'xml'       => $xml,
            'index_xml' => $index,
            'url_count' => count($allUrls),
            'groups'    => array_map('count', $groups),
            'url'       => url('/sitemap.xml'),
        ];
    }

    protected function collectAllUrls(string $base): array
    {
        $groups = [
            'pages'             => [],
            'categories'        => [],
            'subcategories'     => [],
            'subsubcategories'  => [],
            'brands'            => [],
            'products'          => [],
            'blogs'             => [],
            'blog_categories'   => [],
            'static'            => [],
        ];

        // ── Homepage ──────────────────────────────────────────────────────
        $groups['static'][] = [
            'loc'        => $base . '/',
            'priority'   => '1.0',
            'changefreq' => 'daily',
            'lastmod'    => now()->toDateString(),
        ];

        // ── Static / CMS Pages ─────────────────────────────────────────────
        if (Schema::hasTable('pages')) {
            try {
                \App\Models\Page::select('slug', 'updated_at')->get()->each(function ($p) use ($base, &$groups) {
                    if (!empty($p->slug)) {
                        $groups['pages'][] = [
                            'loc'        => $base . '/page/' . $p->slug,
                            'priority'   => '0.6',
                            'changefreq' => 'monthly',
                            'lastmod'    => optional($p->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });
            } catch (\Throwable $e) {}
        }

        // ── Product Categories (use /category/{slug} route) ────────────────
        if (Schema::hasTable('categories')) {
            try {
                \App\Models\Category::select('slug', 'updated_at')->get()->each(function ($c) use ($base, &$groups) {
                    if (!empty($c->slug)) {
                        $groups['categories'][] = [
                            'loc'        => $base . '/category/' . $c->slug,
                            'priority'   => '0.8',
                            'changefreq' => 'weekly',
                            'lastmod'    => optional($c->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });
            } catch (\Throwable $e) {}
        }

        // ── Sub-Categories (listed under parent category page) ────────────
        if (Schema::hasTable('sub_categories')) {
            try {
                \App\Models\SubCategory::select('slug', 'updated_at')->get()->each(function ($c) use ($base, &$groups) {
                    if (!empty($c->slug)) {
                        $groups['subcategories'][] = [
                            'loc'        => $base . '/category/' . $c->slug,
                            'priority'   => '0.7',
                            'changefreq' => 'weekly',
                            'lastmod'    => optional($c->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });
            } catch (\Throwable $e) {}
        }

        // ── Sub-Sub-Categories ────────────────────────────────────────────
        if (Schema::hasTable('sub_sub_categories')) {
            try {
                \App\Models\SubSubCategory::select('slug', 'updated_at')->get()->each(function ($c) use ($base, &$groups) {
                    if (!empty($c->slug)) {
                        $groups['subsubcategories'][] = [
                            'loc'        => $base . '/category/' . $c->slug,
                            'priority'   => '0.6',
                            'changefreq' => 'weekly',
                            'lastmod'    => optional($c->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });
            } catch (\Throwable $e) {}
        }

        // ── Brands (use /brand/{slug} route) ─────────────────────────────
        if (Schema::hasTable('brands')) {
            try {
                \App\Models\Brand::select('slug', 'updated_at')->get()->each(function ($b) use ($base, &$groups) {
                    if (!empty($b->slug)) {
                        $groups['brands'][] = [
                            'loc'        => $base . '/brand/' . $b->slug,
                            'priority'   => '0.6',
                            'changefreq' => 'monthly',
                            'lastmod'    => optional($b->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });
            } catch (\Throwable $e) {}
        }

        // ── Products (use /product/{slug} route) ─────────────────────────
        if (Schema::hasTable('products')) {
            try {
                \App\Models\Product::where('published', 1)->where('approved', 1)
                    ->select('slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->chunk(500, function ($products) use ($base, &$groups) {
                        foreach ($products as $p) {
                            if (!empty($p->slug)) {
                                $groups['products'][] = [
                                    'loc'        => $base . '/product/' . $p->slug,
                                    'priority'   => '0.9',
                                    'changefreq' => 'weekly',
                                    'lastmod'    => optional($p->updated_at)->toDateString() ?? now()->toDateString(),
                                ];
                            }
                        }
                    });
            } catch (\Throwable $e) {}
        }

        // ── Blog Posts ───────────────────────────────────────────────────
        if (Schema::hasTable('blogs')) {
            try {
                \App\Models\Blog::where('published', 1)->whereNull('deleted_at')
                    ->select('slug', 'updated_at', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->chunk(200, function ($posts) use ($base, &$groups) {
                        foreach ($posts as $post) {
                            if (!empty($post->slug)) {
                                $groups['blogs'][] = [
                                    'loc'        => $base . '/blog/' . $post->slug,
                                    'priority'   => '0.7',
                                    'changefreq' => 'monthly',
                                    'lastmod'    => optional($post->updated_at)->toDateString() ?? now()->toDateString(),
                                ];
                            }
                        }
                    });
            } catch (\Throwable $e) {}
        }

        // ── Blog Categories ──────────────────────────────────────────────
        if (Schema::hasTable('blog_categories')) {
            try {
                \App\Models\BlogCategory::select('slug', 'updated_at')->get()->each(function ($bc) use ($base, &$groups) {
                    if (!empty($bc->slug)) {
                        $groups['blog_categories'][] = [
                            'loc'        => $base . '/blog/category/' . $bc->slug,
                            'priority'   => '0.6',
                            'changefreq' => 'weekly',
                            'lastmod'    => optional($bc->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });
            } catch (\Throwable $e) {}
        }

        // ── Known static pages ────────────────────────────────────────────
        foreach ([
            ['path' => '/about-us',   'priority' => '0.6', 'changefreq' => 'monthly'],
            ['path' => '/contact',    'priority' => '0.6', 'changefreq' => 'monthly'],
            ['path' => '/faq',        'priority' => '0.5', 'changefreq' => 'monthly'],
            ['path' => '/shop',       'priority' => '0.8', 'changefreq' => 'daily'],
            ['path' => '/blog',       'priority' => '0.7', 'changefreq' => 'daily'],
            ['path' => '/brands',     'priority' => '0.6', 'changefreq' => 'weekly'],
            ['path' => '/categories', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ] as $static) {
            $groups['static'][] = [
                'loc'        => $base . $static['path'],
                'priority'   => $static['priority'],
                'changefreq' => $static['changefreq'],
                'lastmod'    => now()->toDateString(),
            ];
        }

        // Remove duplicates
        foreach ($groups as $type => $urls) {
            $seen = [];
            $groups[$type] = array_values(array_filter($urls, function ($u) use (&$seen) {
                if (in_array($u['loc'], $seen)) return false;
                $seen[] = $u['loc'];
                return true;
            }));
        }

        return $groups;
    }

    protected function buildSitemap(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
            '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc']) . '</loc>';
            $lines[] = '    <lastmod>' . ($url['lastmod'] ?? now()->toDateString()) . '</lastmod>';
            $lines[] = '    <changefreq>' . ($url['changefreq'] ?? 'weekly') . '</changefreq>';
            $lines[] = '    <priority>' . ($url['priority'] ?? '0.5') . '</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';
        return implode("\n", $lines);
    }

    protected function buildSitemapIndex(string $base): string
    {
        $sitemaps = [
            ['loc' => $base . '/sitemap.xml',         'desc' => 'Main Sitemap'],
            ['loc' => $base . '/video-sitemap.xml',   'desc' => 'Video Sitemap'],
            ['loc' => $base . '/news-sitemap.xml',    'desc' => 'Blog/News Sitemap'],
        ];

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        foreach ($sitemaps as $sm) {
            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>' . htmlspecialchars($sm['loc']) . '</loc>';
            $lines[] = '    <lastmod>' . now()->toAtomString() . '</lastmod>';
            $lines[] = '  </sitemap>';
        }
        $lines[] = '</sitemapindex>';
        return implode("\n", $lines);
    }
}
