<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignExcaliburImages extends Command
{
    protected $signature   = 'excalibur:images';
    protected $description = 'Download and assign images to Excalibur products';

    public function handle()
    {
        $catNames = [
            'WATER SOFTENERS','SOFT-TEC SCALE CONTROL','DROP PRODUCTS','FILTERS',
            'IRON & SULPHUR FILTERS','TANNIN FILTERS','NEUTRALIZING FILTERS',
            'TURBIDITY FILTERS','SPECIALTY FILTERS','ULTRAVIOLET SYSTEMS',
            'REVERSE OSMOSIS','MINERAL TANKS','PARTS & ACCESSORIES',
            'MEDIA & RESINS','CONTROL VALVES',
        ];

        $catIds = DB::table('categories')->whereIn('name', $catNames)->pluck('id');

        if ($catIds->isEmpty()) {
            $this->error('No Excalibur categories found. Run seeders first.');
            return 1;
        }

        $products = DB::table('products')
            ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->whereIn('product_categories.category_id', $catIds)
            ->whereNull('products.thumbnail_img')
            ->select('products.id', 'products.name', 'products.slug')
            ->distinct()
            ->get();

        $this->info("Found {$products->count()} products without images.");

        $adminUserId = DB::table('users')->where('user_type', 'admin')->value('id') ?? 1;
        $uploadDir   = public_path('uploads/all/');
        $done = 0;

        foreach ($products as $product) {
            $slug     = $product->slug ?? str($product->name)->slug();
            $imageUrl = "https://excaliburwater.com/wp-content/uploads/search/{$slug}.jpg";

            $this->line("Searching image for: {$product->name}");

            $imgData = $this->downloadImage($imageUrl);

            if (!$imgData) {
                // Try Google Custom Search as fallback
                $query    = urlencode($product->name . ' water treatment product');
                $fallback = "https://source.unsplash.com/400x400/?water,treatment,filter";
                $imgData  = $this->downloadImage($fallback);
            }

            if (!$imgData) {
                $this->warn("  Skipped (no image found)");
                continue;
            }

            $ext      = 'jpg';
            $filename = 'uploads/all/' . uniqid('excalibur_') . '.' . $ext;
            $fullPath = public_path($filename);

            file_put_contents($fullPath, $imgData);

            $uploadId = DB::table('uploads')->insertGetId([
                'file_original_name' => $product->name,
                'file_name'          => $filename,
                'user_id'            => $adminUserId,
                'extension'          => $ext,
                'type'               => 'image',
                'file_size'          => strlen($imgData),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::table('products')->where('id', $product->id)->update([
                'thumbnail_img' => $uploadId,
                'photos'        => $uploadId,
            ]);

            $done++;
            $this->info("  Assigned upload #{$uploadId}");
        }

        $this->info("\nDone. {$done} products updated.");
        return 0;
    }

    private function downloadImage(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && strlen($body) > 5000) {
            return $body;
        }
        return null;
    }
}
