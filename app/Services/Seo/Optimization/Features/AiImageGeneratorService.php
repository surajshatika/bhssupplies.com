<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AiImageGeneratorService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $keyword   = $payload['keyword'] ?? '';
        $style     = $payload['style'] ?? 'professional product photo';
        $size      = $payload['size'] ?? '1024x1024';
        $count     = min((int) ($payload['count'] ?? 1), 4);
        $purpose   = $payload['purpose'] ?? 'product'; // product, blog, social, banner
        $saveLocal = $payload['save_local'] ?? false;

        $prompt = $this->buildImagePrompt($keyword, $style, $purpose, $payload);

        $apiKey   = config('seo.providers.openai.api_key') ?? get_setting('seo_openai_api_key');
        $images   = [];
        $error    = null;

        if (empty($apiKey)) {
            return ['error' => 'OpenAI API key required for image generation.', 'prompt' => $prompt];
        }

        try {
            $response = Http::timeout(60)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model'   => config('seo.image_generation.model', 'dall-e-3'),
                    'prompt'  => $prompt,
                    'n'       => min($count, 1), // DALL-E 3 supports n=1 only
                    'size'    => $size,
                    'quality' => 'standard',
                    'response_format' => 'url',
                ]);

            if ($response->successful()) {
                $rawImages = $response->json('data', []);
                foreach ($rawImages as $img) {
                    $imageUrl = $img['url'] ?? null;
                    if ($imageUrl) {
                        $saved = null;
                        if ($saveLocal) {
                            $saved = $this->downloadAndSave($imageUrl, $keyword);
                        }
                        $images[] = [
                            'url'           => $imageUrl,
                            'local_path'    => $saved,
                            'alt_text'      => $keyword . ' - SEO image',
                            'filename'      => $this->generateFilename($keyword),
                            'revised_prompt'=> $img['revised_prompt'] ?? $prompt,
                        ];
                    }
                }
            } else {
                $error = $response->json('error.message', 'Image generation failed.');
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'images'      => $images,
            'prompt'      => $prompt,
            'keyword'     => $keyword,
            'size'        => $size,
            'purpose'     => $purpose,
            'error'       => $error,
        ];
    }

    protected function buildImagePrompt(string $keyword, string $style, string $purpose, array $payload): string
    {
        $contextMap = [
            'product'  => "high-quality product photography, white background, commercial quality, showing {$keyword}",
            'blog'     => "editorial illustration for a blog post about {$keyword}, modern flat design style",
            'social'   => "eye-catching social media graphic for {$keyword}, vibrant colors, engaging composition",
            'banner'   => "website banner image for {$keyword}, wide format, professional, clean design",
            'infographic' => "clean infographic about {$keyword}, data visualization, professional design",
        ];

        $base = $contextMap[$purpose] ?? "professional image of {$keyword}";
        $style = $payload['custom_prompt'] ?? "{$base}, {$style}, SEO-optimized visual, high resolution, no text overlay";

        return $style;
    }

    protected function generateFilename(string $keyword): string
    {
        return strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9\s]/', '', $keyword))) . '-' . time() . '.webp';
    }

    protected function downloadAndSave(string $url, string $keyword): ?string
    {
        try {
            $contents = Http::timeout(30)->get($url)->body();
            $filename = 'seo/images/' . $this->generateFilename($keyword);
            Storage::disk('public')->put($filename, $contents);
            return Storage::disk('public')->url($filename);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
