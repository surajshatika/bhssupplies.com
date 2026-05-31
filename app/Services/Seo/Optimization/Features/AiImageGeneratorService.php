<?php

namespace App\Services\Seo\Optimization\Features;

use App\Models\SeoGeneratedImage;
use App\Models\Upload;
use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiImageGeneratorService extends AbstractSeoService
{
    protected const MAX_DOWNLOAD_BYTES = 10 * 1024 * 1024;

    /** Style presets → prompt suffixes (AIOSEO-style style selector). */
    public static function styles(): array
    {
        return [
            'professional product photo' => 'Professional Product Photo',
            'minimalist white background' => 'Minimalist White Background',
            'cinematic'                  => 'Cinematic (dramatic lighting)',
            'vibrant'                    => 'Vibrant & Modern',
            'lifestyle photography'      => 'Lifestyle Photography',
            'flat design illustration'   => 'Flat Design Illustration',
            'technical diagram'          => 'Technical / Industrial',
            '3D render'                  => '3D Render',
            'photorealistic'             => 'Photorealistic',
        ];
    }

    public function handle(array $payload): array
    {
        $keyword   = $payload['keyword'] ?? '';
        $style     = $payload['style'] ?? 'professional product photo';
        $size      = $this->normalizeSize($payload['size'] ?? '1024x1024');
        $purpose   = $payload['purpose'] ?? 'product'; // product, blog, social, banner
        $quality   = in_array(($payload['quality'] ?? 'standard'), ['standard', 'hd'], true) ? $payload['quality'] : 'standard';
        $saveLocal = (bool) ($payload['save_local'] ?? false);

        $prompt = $this->buildImagePrompt($keyword, $style, $purpose, $payload);

        $apiKey   = config('seo.providers.openai.api_key') ?? get_setting('seo_openai_api_key');
        $images   = [];
        $error    = null;

        if (empty($apiKey)) {
            return ['error' => 'OpenAI API key required for image generation.', 'prompt' => $prompt];
        }

        try {
            $response = Http::timeout(90)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model'   => config('seo.image_generation.model', 'dall-e-3'),
                    'prompt'  => $prompt,
                    'n'       => 1, // DALL-E 3 supports n=1 only
                    'size'    => $size,
                    'quality' => $quality,
                    'response_format' => 'url',
                ]);

            if ($response->successful()) {
                $rawImages = $response->json('data', []);
                foreach ($rawImages as $img) {
                    $imageUrl = $img['url'] ?? null;
                    if (!$imageUrl) {
                        continue;
                    }

                    $altText  = $this->altText($keyword, $purpose);
                    $filename = $this->generateFilename($keyword);
                    $media    = $saveLocal ? $this->saveToMediaLibrary($imageUrl) : null;

                    $record = $this->recordHistory([
                        'keyword'        => $keyword,
                        'prompt'         => $prompt,
                        'revised_prompt' => $img['revised_prompt'] ?? $prompt,
                        'style'          => $style,
                        'purpose'        => $purpose,
                        'size'           => $size,
                        'quality'        => $quality,
                        'source_url'     => $imageUrl,
                        'local_url'      => $media['url'] ?? null,
                        'upload_id'      => $media['id'] ?? null,
                        'alt_text'       => $altText,
                        'filename'       => $filename,
                    ]);

                    $images[] = [
                        'id'            => $record?->id,
                        'url'           => $imageUrl,
                        'local_url'     => $media['url'] ?? null,
                        'upload_id'     => $media['id'] ?? null,
                        'alt_text'      => $altText,
                        'filename'      => $filename,
                        'revised_prompt'=> $img['revised_prompt'] ?? $prompt,
                    ];
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
            'quality'     => $quality,
            'purpose'     => $purpose,
            'error'       => $error,
        ];
    }

    protected function normalizeSize(string $size): string
    {
        return in_array($size, ['1024x1024', '1792x1024', '1024x1792'], true) ? $size : '1024x1024';
    }

    protected function altText(string $keyword, string $purpose): string
    {
        $kw = trim($keyword);
        return $kw === '' ? 'AI generated image' : Str::limit(ucfirst($kw) . ' — ' . get_setting('website_name', config('app.name')), 125, '');
    }

    /**
     * Persist the generated image into the platform media library so it can be
     * reused as a product image or set as an Open Graph image. Mirrors
     * AizUploadController's storage convention (public/uploads/all + Upload row).
     *
     * @return array{id:int,url:string}|null
     */
    public function saveToMediaLibrary(string $sourceUrl): ?array
    {
        try {
            $response = Http::timeout(45)
                ->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->get($sourceUrl);

            if (!$response->successful()) {
                logger()->warning('AI image download failed', ['status' => $response->status()]);
                return null;
            }

            $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            if ($contentType !== 'image/png') {
                logger()->warning('AI image download rejected: invalid content type', ['content_type' => $contentType]);
                return null;
            }

            $contentLength = (int) $response->header('Content-Length', 0);
            if ($contentLength > self::MAX_DOWNLOAD_BYTES) {
                logger()->warning('AI image download rejected: content length exceeds limit', ['bytes' => $contentLength]);
                return null;
            }

            $bytes     = $response->body();
            $byteCount = strlen($bytes);
            if ($byteCount === 0 || $byteCount > self::MAX_DOWNLOAD_BYTES) {
                logger()->warning('AI image download rejected: invalid body size', ['bytes' => $byteCount]);
                return null;
            }

            if (!str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
                logger()->warning('AI image download rejected: body is not PNG data');
                return null;
            }

            $filename = Str::random(40) . '.png';
            $dir      = public_path('uploads/all');
            if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
                return null;
            }
            if (file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $bytes, LOCK_EX) !== $byteCount) {
                return null;
            }

            $upload = new Upload();
            $upload->file_original_name = 'ai-seo-image';
            $upload->file_name = 'uploads/all/' . $filename;
            $upload->user_id   = optional(auth()->user())->id;
            $upload->extension = 'png';
            $upload->type      = 'image';
            $upload->file_size = round($byteCount / 1024, 2) . ' kb';
            $upload->save();

            return ['id' => $upload->id, 'url' => uploaded_asset($upload->id)];
        } catch (\Throwable $e) {
            logger()->warning('AI image media-library save failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function recordHistory(array $data): ?SeoGeneratedImage
    {
        if (!Schema::hasTable('seo_generated_images')) {
            return null;
        }
        try {
            return SeoGeneratedImage::create($data + ['user_id' => optional(auth()->user())->id]);
        } catch (\Throwable $e) {
            logger()->warning('AI image history record failed', ['error' => $e->getMessage()]);
            return null;
        }
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

        // Richer descriptors for the style presets.
        $styleSuffix = [
            'cinematic'         => 'cinematic lighting, dramatic shadows, shallow depth of field, professional photography',
            'vibrant'          => 'vibrant saturated colors, high contrast, energetic modern composition',
            'minimalist white background' => 'minimalist, clean white studio background, soft even lighting',
            'technical diagram' => 'technical industrial style, precise, clean, neutral background',
            'flat design illustration' => 'flat vector illustration, clean lines, minimal palette',
            '3D render'         => 'high-quality 3D render, soft global illumination, realistic materials',
        ];
        $styleText = $styleSuffix[$style] ?? $style;

        $base = $contextMap[$purpose] ?? "professional image of {$keyword}";

        if (!empty($payload['custom_prompt'])) {
            return trim($payload['custom_prompt']);
        }

        return "{$base}, {$styleText}, SEO-optimized visual, high resolution, no text overlay, no watermark";
    }

    protected function generateFilename(string $keyword): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $keyword), '-'));
        $slug = $slug !== '' ? $slug : 'ai-seo-image';

        return $slug . '-' . time() . '.png';
    }
}
