<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class RssContentService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $feedTitle       = $payload['title'] ?? get_setting('website_name', config('app.name'));
        $feedDescription = $payload['description'] ?? 'Latest products and updates';
        $feedUrl         = $payload['feed_url'] ?? url('/rss.xml');
        $siteUrl         = $payload['site_url'] ?? url('/');
        $itemCount       = (int) ($payload['item_count'] ?? 20);
        $feedType        = $payload['feed_type'] ?? 'products'; // products, blog, all

        $items = $this->collectFeedItems($feedType, $itemCount, $siteUrl);

        $rss = $this->buildRssFeed($feedTitle, $feedDescription, $feedUrl, $siteUrl, $items);

        if ($payload['persist'] ?? false) {
            file_put_contents(public_path('rss.xml'), $rss);
        }

        $aiOptimize = null;
        if ($this->ai()->isConfigured()) {
            $prompt = "You are an RSS SEO expert. Optimize this RSS feed metadata:\n"
                . "Feed Title: {$feedTitle}\n"
                . "Feed Description: {$feedDescription}\n"
                . "Site: {$siteUrl}\n"
                . "Feed Type: {$feedType}\n\n"
                . "Suggest:\n"
                . "1. Improved feed title (SEO-optimized, under 60 chars)\n"
                . "2. Improved feed description (keyword-rich, 100-160 chars)\n"
                . "3. Best items to feature for SEO value\n"
                . "4. Recommended feed submission directories";
            $aiOptimize = $this->ai()->generate($prompt, 'You are an RSS feed and content SEO specialist.');
        }

        return [
            'xml'         => $rss,
            'item_count'  => count($items),
            'feed_url'    => $feedUrl,
            'ai_optimize' => $aiOptimize,
        ];
    }

    protected function collectFeedItems(string $feedType, int $limit, string $siteUrl): array
    {
        $items = [];

        if (in_array($feedType, ['products', 'all'])) {
            try {
                $products = \App\Models\Product::where('published', 1)
                    ->latest()
                    ->limit($limit)
                    ->get(['name', 'slug', 'description', 'unit_price', 'thumbnail_img', 'created_at']);
                foreach ($products as $p) {
                    $items[] = [
                        'title'       => $p->name,
                        'link'        => $siteUrl . '/product/' . $p->slug,
                        'description' => strip_tags(substr($p->description ?? '', 0, 300)),
                        'pubDate'     => optional($p->created_at)->toRfc2822String() ?? now()->toRfc2822String(),
                        'image'       => $p->thumbnail_img ? uploaded_asset($p->thumbnail_img) : null,
                        'type'        => 'product',
                    ];
                }
            } catch (\Throwable $e) {}
        }

        return array_slice($items, 0, $limit);
    }

    protected function buildRssFeed(string $title, string $description, string $feedUrl, string $siteUrl, array $items): string
    {
        $now   = now()->toRfc2822String();
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">';
        $lines[] = '  <channel>';
        $lines[] = '    <title>' . htmlspecialchars($title) . '</title>';
        $lines[] = '    <link>' . htmlspecialchars($siteUrl) . '</link>';
        $lines[] = '    <description>' . htmlspecialchars($description) . '</description>';
        $lines[] = '    <language>en-us</language>';
        $lines[] = '    <pubDate>' . $now . '</pubDate>';
        $lines[] = '    <lastBuildDate>' . $now . '</lastBuildDate>';
        $lines[] = '    <atom:link href="' . htmlspecialchars($feedUrl) . '" rel="self" type="application/rss+xml"/>';

        foreach ($items as $item) {
            $lines[] = '    <item>';
            $lines[] = '      <title>' . htmlspecialchars($item['title'] ?? '') . '</title>';
            $lines[] = '      <link>' . htmlspecialchars($item['link'] ?? '') . '</link>';
            $lines[] = '      <description>' . htmlspecialchars($item['description'] ?? '') . '</description>';
            $lines[] = '      <pubDate>' . ($item['pubDate'] ?? $now) . '</pubDate>';
            $lines[] = '      <guid isPermaLink="true">' . htmlspecialchars($item['link'] ?? '') . '</guid>';
            if (!empty($item['image'])) {
                $lines[] = '      <media:content url="' . htmlspecialchars($item['image']) . '" medium="image"/>';
            }
            $lines[] = '    </item>';
        }

        $lines[] = '  </channel>';
        $lines[] = '</rss>';
        return implode("\n", $lines);
    }
}
