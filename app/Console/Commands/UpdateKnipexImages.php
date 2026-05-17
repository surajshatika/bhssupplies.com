<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductStock;

class UpdateKnipexImages extends Command
{
    protected $signature = 'knipex:update-images
                            {--force : Re-download even if image already exists}
                            {--limit=0 : Limit number of products to process}
                            {--sku= : Process only a specific SKU}';

    protected $description = 'Download and attach images for KNIPEX products missing images';

    public function handle(): int
    {
        $force = $this->option('force');
        $limit = (int) $this->option('limit');
        $skuFilter = $this->option('sku');

        $query = Product::where('tags', 'LIKE', '%KNIPEX%');

        if ($skuFilter) {
            $query->where('barcode', $skuFilter);
        } elseif (!$force) {
            // Only products with no image
            $query->where(function ($q) {
                $q->whereNull('photos')->orWhere('photos', '');
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('No KNIPEX products need image updates.');
            return 0;
        }

        $this->info("Processing {$products->count()} products...");

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $updated = 0;
        $failed  = 0;

        foreach ($products as $product) {
            $sku = $product->barcode ?? '';

            $imageUrl = '';

            // Try Zoro first (most reliable, no bot protection)
            if ($sku) {
                $imageUrl = $this->tryZoro($sku);
            }

            // Fallback: KNIPEX CDN
            if (!$imageUrl && $sku) {
                $imageUrl = $this->tryKnipexCdn($sku);
            }

            if ($imageUrl) {
                $saved = $this->downloadAndSave($imageUrl);
                if ($saved) {
                    $product->photos        = $saved;
                    $product->thumbnail_img = $saved;
                    $product->meta_img      = $saved;
                    $product->save();

                    // Update stock record too
                    ProductStock::where('product_id', $product->id)->update(['image' => $saved]);

                    $updated++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Updated', 'No image found'],
            [[$updated, $failed]]
        );

        return 0;
    }

    private function tryZoro(string $sku): string
    {
        $compact = str_replace('-', '', $sku);
        $html    = $this->fetchUrlHtml("https://www.zoro.com/search?q={$compact}");
        if (empty($html)) return '';

        // First product listing image from Zoro CDN
        if (preg_match('#src=["\']+(https?://www\.zoro\.com/static/cms/product/[^"\'>\s]+\.(jpg|JPG|jpeg|png|webp))#i', $html, $m)) {
            return $m[1];
        }

        return '';
    }

    private function fetchUrlHtml(string $url): string
    {
        return (string)(@file_get_contents($url, false, $this->httpContext()) ?? '');
    }

    private function tryKnipexCdn(string $sku): string
    {
        $digitsOnly = preg_replace('/[^0-9]/', '', $sku);
        if (strlen($digitsOnly) < 5) return '';

        $variants = [
            "https://www.knipex.com/medias/{$digitsOnly}-a1-1400-1400.jpg",
            "https://www.knipex.com/medias/{$digitsOnly}-a0-1400-1400.jpg",
        ];

        $context = $this->httpContext();
        foreach ($variants as $url) {
            $binary = @file_get_contents($url, false, $context);
            if ($binary && strlen($binary) > 500) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                if (str_starts_with($finfo->buffer($binary), 'image/')) {
                    return $url;
                }
            }
        }
        return '';
    }

    private function downloadAndSave(string $url): string
    {
        try {
            $context = $this->httpContext();
            $binary  = @file_get_contents($url, false, $context);
            if (empty($binary) || strlen($binary) < 500) return '';

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($binary);
            if (!str_starts_with($mime, 'image/')) return '';

            $ext  = match(true) {
                str_contains($mime, 'png')  => 'png',
                str_contains($mime, 'gif')  => 'gif',
                str_contains($mime, 'webp') => 'webp',
                default                     => 'jpg',
            };

            $dir = public_path('uploads/all');
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $filename = 'knipex_' . uniqid() . '.' . $ext;
            file_put_contents($dir . '/' . $filename, $binary);

            return 'uploads/all/' . $filename;
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function httpContext()
    {
        return stream_context_create([
            'http' => [
                'timeout'         => 15,
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
                'ignore_errors'   => true,
                'follow_location' => 1,
                'max_redirects'   => 5,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);
    }
}
