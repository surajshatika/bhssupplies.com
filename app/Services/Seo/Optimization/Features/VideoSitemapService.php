<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class VideoSitemapService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $videos = $payload['videos'] ?? [];
        $baseUrl = $payload['base_url'] ?? url('/');

        if (empty($videos)) {
            $videos = $this->collectVideosFromSite($baseUrl);
        }

        $xml = $this->buildVideoSitemap($videos, $baseUrl);

        if ($payload['persist'] ?? false) {
            file_put_contents(public_path('video-sitemap.xml'), $xml);
        }

        return [
            'xml'         => $xml,
            'video_count' => count($videos),
            'url'         => url('/video-sitemap.xml'),
        ];
    }

    protected function collectVideosFromSite(string $baseUrl): array
    {
        // Collect videos from the database (products, posts with video fields)
        $videos = [];

        try {
            $products = \App\Models\Product::whereNotNull('video')
                ->where('video', '!=', '')
                ->limit(200)
                ->get(['id', 'name', 'slug', 'video', 'thumbnail_img', 'description']);

            foreach ($products as $product) {
                $productUrl = $baseUrl . '/product/' . $product->slug;
                $videos[] = [
                    'page_url'     => $productUrl,
                    'title'        => $product->name,
                    'description'  => strip_tags(substr($product->description ?? '', 0, 200)),
                    'thumbnail_url'=> $product->thumbnail_img ? uploaded_asset($product->thumbnail_img) : '',
                    'content_url'  => $product->video,
                    'duration'     => null,
                    'publication_date' => now()->toAtomString(),
                    'family_friendly' => 'yes',
                ];
            }
        } catch (\Throwable $e) {
            // No products model available
        }

        return $videos;
    }

    protected function buildVideoSitemap(array $videos, string $baseUrl): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $lines[] = '        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';

        foreach ($videos as $video) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($video['page_url']) . '</loc>';
            $lines[] = '    <video:video>';
            if (!empty($video['thumbnail_url'])) {
                $lines[] = '      <video:thumbnail_loc>' . htmlspecialchars($video['thumbnail_url']) . '</video:thumbnail_loc>';
            }
            $lines[] = '      <video:title>' . htmlspecialchars($video['title'] ?? '') . '</video:title>';
            if (!empty($video['description'])) {
                $lines[] = '      <video:description>' . htmlspecialchars($video['description']) . '</video:description>';
            }
            if (!empty($video['content_url'])) {
                $lines[] = '      <video:content_loc>' . htmlspecialchars($video['content_url']) . '</video:content_loc>';
            }
            if (!empty($video['duration'])) {
                $lines[] = '      <video:duration>' . (int) $video['duration'] . '</video:duration>';
            }
            if (!empty($video['publication_date'])) {
                $lines[] = '      <video:publication_date>' . $video['publication_date'] . '</video:publication_date>';
            }
            $lines[] = '      <video:family_friendly>' . ($video['family_friendly'] ?? 'yes') . '</video:family_friendly>';
            $lines[] = '    </video:video>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';
        return implode("\n", $lines);
    }
}
