<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class ImageSeoService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $images  = $payload['images'] ?? [];
        $keyword = $payload['keyword'] ?? '';
        $title   = $payload['title'] ?? '';

        if (empty($images)) {
            return ['error' => 'No images provided.'];
        }

        $optimized = [];
        foreach ($images as $src) {
            $optimized[] = $this->optimizeImage($src, $keyword, $title);
        }

        $prompt = "You are an image SEO specialist. Review these " . count($images) . " image URLs for a page about '{$title}' "
            . "targeting keyword '{$keyword}'.\n"
            . "Images: " . implode(', ', array_slice($images, 0, 5)) . "\n\n"
            . "Provide:\n"
            . "1. SEO-optimized alt text for each image\n"
            . "2. SEO-friendly file naming recommendations\n"
            . "3. Image compression and format recommendations (WebP, AVIF)\n"
            . "4. Lazy loading and srcset recommendations\n"
            . "5. Image structured data recommendations";

        $aiAdvice = $this->ai()->generate($prompt, 'You are an image SEO and Core Web Vitals optimization expert.');

        return [
            'optimized_images' => $optimized,
            'ai_advice'        => $aiAdvice,
            'total'            => count($images),
        ];
    }

    protected function optimizeImage(string $src, string $keyword, string $title): array
    {
        $filename = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_FILENAME);
        $ext      = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION);
        $cleanName = str_replace(['-', '_'], ' ', $filename);

        $suggestedAlt = trim(($keyword ? $keyword . ' - ' : '') . ucfirst($cleanName));
        $suggestedFilename = strtolower(str_replace(' ', '-', $keyword ?: $cleanName)) . '.' . ($ext ?: 'jpg');

        return [
            'src'               => $src,
            'original_filename' => basename($src),
            'suggested_alt'     => $suggestedAlt,
            'suggested_filename'=> $suggestedFilename,
            'title_attr'        => $title ?: $suggestedAlt,
            'loading'           => 'lazy',
            'decoding'          => 'async',
        ];
    }
}
