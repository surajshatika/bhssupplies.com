<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Schema;

class GoogleNewsSitemapService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $publicationName = $payload['publication_name'] ?? get_setting('website_name', config('app.name'));
        $language        = $payload['language'] ?? 'en';
        $baseUrl         = rtrim($payload['base_url'] ?? url('/'), '/');
        $daysBack        = (int) ($payload['days_back'] ?? 2); // Google News only indexes last 2 days

        $articles = $this->collectBlogPosts($baseUrl, $daysBack);

        $xml = $this->buildNewsSitemap($articles, $publicationName, $language);

        if ($payload['persist'] ?? false) {
            file_put_contents(public_path('news-sitemap.xml'), $xml);
        }

        return [
            'xml'           => $xml,
            'article_count' => count($articles),
            'url'           => url('/news-sitemap.xml'),
        ];
    }

    protected function collectBlogPosts(string $baseUrl, int $daysBack): array
    {
        $articles = [];

        if (!Schema::hasTable('blogs')) {
            return $articles;
        }

        try {
            $cutoff = now()->subDays($daysBack);
            $posts  = \App\Models\Blog::where('published', 1)
                ->where('created_at', '>=', $cutoff)
                ->select('slug', 'title', 'meta_title', 'meta_description', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($posts as $post) {
                $articles[] = [
                    'url'              => $baseUrl . '/blog/' . $post->slug,
                    'title'            => $post->meta_title ?: $post->title,
                    'publication_date' => optional($post->created_at)->toAtomString() ?? now()->toAtomString(),
                    'keywords'         => '',
                ];
            }
        } catch (\Throwable $e) {}

        return $articles;
    }

    protected function buildNewsSitemap(array $articles, string $publicationName, string $language): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
            '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">',
        ];

        foreach ($articles as $article) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($article['url']) . '</loc>';
            $lines[] = '    <news:news>';
            $lines[] = '      <news:publication>';
            $lines[] = '        <news:name>' . htmlspecialchars($publicationName) . '</news:name>';
            $lines[] = '        <news:language>' . $language . '</news:language>';
            $lines[] = '      </news:publication>';
            $lines[] = '      <news:publication_date>' . ($article['publication_date'] ?? now()->toAtomString()) . '</news:publication_date>';
            $lines[] = '      <news:title>' . htmlspecialchars($article['title'] ?? '') . '</news:title>';
            if (!empty($article['keywords'])) {
                $lines[] = '      <news:keywords>' . htmlspecialchars($article['keywords']) . '</news:keywords>';
            }
            $lines[] = '    </news:news>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';
        return implode("\n", $lines);
    }
}
