<?php

namespace App\Services\Blog;

use App\Jobs\PostToSocialMediaJob;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\SocialAutomationSetting;
use App\Models\Upload;
use App\Services\SocialMedia\AI\SocialAiProviderManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiBlogGeneratorService
{
    private const BLOG_BANNER_WIDTH = 1300;
    private const BLOG_BANNER_HEIGHT = 650;

    private string $provider;
    private string $siteName;
    private string $siteUrl;

    public function __construct(?string $provider = null)
    {
        $this->provider = $provider
            ?? SocialAutomationSetting::get('ai_blog_provider', 'openai');
        $this->siteName = get_setting('website_name', config('app.name'));
        $this->siteUrl  = url('/');
    }

    // ─── Main Entry Point ────────────────────────────────────────────────────

    /**
     * Generate a complete blog post and save it to the database.
     *
     * @param array $options {
     *   blog_title?: string,       // exact title to use
     *   slug?: string,             // preferred slug, made unique
     *   blog_category_id?: int,    // existing blog category
     *   category_name?: string,   // blog category name (creates if not exists)
     *   banner_upload_id?: int,    // existing Upload ID for banner
     *   meta_image_upload_id?: int,// existing Upload ID for meta image
     *   topic?: string,            // custom topic override
     *   use_product_category?: bool, // pull topic from product categories
     *   product_category_id?: int,
     *   keywords?: string,         // extra seed keywords
     *   competitor_urls?: string,  // comma-separated competitor URLs for kw research
     *   tone?: string,
     *   publish?: bool,            // immediately publish (status=1)
     *   post_to_social?: bool,     // dispatch social posts after saving
     *   social_platforms?: array,
     *   ai_provider?: string,
     * }
     */
    public function generate(array $options = []): Blog
    {
        $provider = $options['ai_provider'] ?? $this->provider;
        $ai       = SocialAiProviderManager::make($provider);

        // 1. Resolve or create blog category
        $category = $this->resolveCategory($options);

        // 2. Build topic
        $topic    = (string)($options['blog_title'] ?? $options['topic'] ?? $this->buildTopicFromCategory($category, $options));
        $keywords = (string)($options['keywords'] ?? '');
        $tone     = (string)($options['tone'] ?? SocialAutomationSetting::get('ai_blog_tone', 'professional'));

        // 3. Research competitor keywords
        $competitorKeywords = (string)$this->researchCompetitorKeywords($topic, $options, $provider);
        $allKeywords        = trim(trim($keywords, ', ') . ($keywords && $competitorKeywords ? ', ' : '') . trim($competitorKeywords, ', '), ', ');

        // 4. Generate full blog content
        $content = $this->generateBlogContent($topic, $category->category_name, $allKeywords, $tone, $provider, $options);
        $content = $this->enforceFullBlogBlueprint($content, $topic, $category->category_name, $allKeywords, $options);

        // 5. Fetch image — prefer product images from category, fallback to Pexels/Unsplash
        $uploadId = !empty($options['banner_upload_id']) ? (int) $options['banner_upload_id'] : null;
        if (!$uploadId) {
            $uploadId = $this->fetchAndStoreImage(
            $topic . ' ' . $category->category_name,
            $options['product_category_id'] ?? null,
            $category->id,
            );
        }
        if (!$uploadId) {
            $uploadId = $this->buildFallbackBanner($topic, $category->category_name);
        }
        $metaImageId = !empty($options['meta_image_upload_id']) ? (int) $options['meta_image_upload_id'] : $uploadId;

        // 6. Save blog to DB
        $blog = $this->saveBlog($content, $category->id, $uploadId, (bool)($options['publish'] ?? false), $metaImageId);

        // 7. Auto-post to social media if requested
        if (!empty($options['post_to_social'])) {
            $this->postBlogToSocial($blog, $options);
        }

        Log::info("AI Blog generated: [{$blog->id}] {$blog->title}");

        return $blog;
    }

    // ─── Category Resolution ─────────────────────────────────────────────────

    private function resolveCategory(array $options): BlogCategory
    {
        if (!empty($options['blog_category_id'])) {
            $existing = BlogCategory::find((int) $options['blog_category_id']);
            if ($existing) {
                return $existing;
            }
        }

        // Explicit category name passed
        if (!empty($options['category_name'])) {
            $categoryName = trim((string) $options['category_name']);
            return BlogCategory::firstOrCreate(
                ['category_name' => $categoryName],
                ['slug' => $this->uniqueCategorySlug($categoryName)]
            );
        }

        // Pull from product category
        if (!empty($options['product_category_id'])) {
            $productCat = \App\Models\Category::find($options['product_category_id']);
            if ($productCat) {
                $translated = $productCat->getTranslation('name', app()->getLocale());
                $name = is_string($translated) && $translated !== ''
                    ? $translated
                    : (string)($productCat->name ?? 'General');
                return BlogCategory::firstOrCreate(
                    ['category_name' => $name],
                    ['slug' => $this->uniqueCategorySlug($name)]
                );
            }
        }

        // Auto-select a category from existing ones or create a default
        $existing = BlogCategory::inRandomOrder()->first();
        if ($existing) return $existing;

        // Create default category based on site name
        $defaultName = $this->siteName . ' Tips & Guides';
        return BlogCategory::firstOrCreate(
            ['category_name' => $defaultName],
            ['slug' => $this->uniqueCategorySlug($defaultName)]
        );
    }

    private function uniqueCategorySlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'blog-category';
        $base = $slug;
        $i = 1;

        while (BlogCategory::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function buildTopicFromCategory(BlogCategory $category, array $options): string
    {
        $siteName = $this->siteName;
        $catName  = $category->category_name;

        $topics = [
            "Best {$catName} products and buying guide for Canadians",
            "How to choose the right {$catName} — expert tips",
            "Top {$catName} trends you need to know in 2026",
            "{$catName}: complete beginner's guide from {$siteName}",
            "Why Canadians love {$catName} — a complete overview",
        ];

        return $topics[array_rand($topics)];
    }

    // ─── Competitor Keyword Research ─────────────────────────────────────────

    private function researchCompetitorKeywords(string $topic, array $options, string $provider): string
    {
        $raw = $options['competitor_urls'] ?? SocialAutomationSetting::get('ai_blog_competitor_urls', '');
        $competitorUrls = is_array($raw) ? implode(',', $raw) : trim((string)$raw);

        $ai     = SocialAiProviderManager::make($provider);
        $country = SocialAutomationSetting::get('ai_blog_target_country', 'Canada');

        $urlContext = '';
        if ($competitorUrls) {
            // Handle URLs stored without comma separators (e.g. "https://a.comhttps://b.com")
            $normalized = preg_replace('/(https?:\/\/)/', '|||$1', $competitorUrls);
            $urls = array_filter(array_map('trim', explode('|||', $normalized)));
            if (count($urls) <= 1) {
                // Fallback: split on comma or whitespace
                $urls = array_filter(array_map('trim', preg_split('/[,\s]+/', $competitorUrls)));
            }
            $urls = array_values(array_filter($urls, fn($u) => strlen($u) > 8));
            $urlContext = "Competitor websites to analyse: " . implode(', ', array_slice($urls, 0, 5)) . "\n";
        }

        $prompt = "You are an SEO expert targeting {$country}.\n"
            . "Topic: {$topic}\n"
            . $urlContext
            . "Generate 20 high-value SEO keywords and long-tail phrases that:\n"
            . "1. Have search intent matching this topic\n"
            . "2. Include keywords competitors likely rank for\n"
            . "3. Include Canada-specific variants where relevant\n"
            . "Output ONLY a comma-separated list of keywords. No explanations.";

        $result = $ai->generate($prompt, null, ['max_tokens' => 300]);

        return $result ? trim($result) : '';
    }

    // ─── Blog Content Generation ──────────────────────────────────────────────

    private function generateBlogContent(
        string $topic,
        string $category,
        string $keywords,
        string $tone,
        string $provider,
        array $options = []
    ): array {
        $ai       = SocialAiProviderManager::make($provider);
        $siteName = $this->siteName;
        $siteUrl  = $this->siteUrl;
        $country  = SocialAutomationSetting::get('ai_blog_target_country', 'Canada');
        $wordCount = (int) SocialAutomationSetting::get('ai_blog_word_count', 1200);
        $primaryLocations = SocialAutomationSetting::get('ai_blog_primary_locations', 'Mississauga, Brampton, Toronto');
        $secondaryLocations = SocialAutomationSetting::get('ai_blog_secondary_locations', 'Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, Burlington');
        $conversionIntents = SocialAutomationSetting::get('ai_blog_conversion_intents', 'Trade Account, Leave a Review');

        // Build competitor context for content depth
        $raw = $options['competitor_urls'] ?? SocialAutomationSetting::get('ai_blog_competitor_urls', '');
        $competitorUrls = is_array($raw) ? implode(',', $raw) : trim((string)$raw);
        $competitorContext = '';
        if ($competitorUrls) {
            $normalized = preg_replace('/(https?:\/\/)/', '|||$1', $competitorUrls);
            $urls = array_filter(array_map('trim', explode('|||', $normalized)));
            if (count($urls) <= 1) {
                $urls = array_filter(array_map('trim', preg_split('/[,\s]+/', $competitorUrls)));
            }
            $urls = array_values(array_filter($urls, fn($u) => strlen($u) > 8));
            if ($urls) {
                $competitorContext = "\nCompetitor websites to outrank: " . implode(', ', array_slice($urls, 0, 5))
                    . "\nStudy the topics, angles, and subtopics these competitors cover for this keyword. "
                    . "Make this article more comprehensive, more useful, and better structured than any of them. "
                    . "Cover every subtopic they likely address, plus gaps they miss.\n";
            }
        }

        $system = "You are a senior local SEO content writer for {$siteName} ({$siteUrl}), "
            . "a supply store serving {$primaryLocations} and the Greater Toronto Area (GTA), {$country}. "
            . "Write service-page style blog posts that rank for local searches. "
            . "Style reference: open with a strong 2-paragraph intro, then use H2 sections like "
            . "\"Our [Category] Include\", \"Why Choose {$siteName}\", \"Serving Mississauga and the GTA\", \"Visit Our Store\". "
            . "Use bullet lists for products/services and benefits. Keep paragraphs short and direct. "
            . "ALL target keywords MUST appear naturally in both the short description and the body — no omissions. "
            . "Always write in a {$tone} tone. Never mention competitor brand names.";

        $prompt = "Write a local SEO service-page blog post about: {$topic}\n"
            . "Store name: {$siteName} | Store URL: {$siteUrl}\n"
            . "Primary locations: {$primaryLocations}\n"
            . "Secondary locations: {$secondaryLocations}\n"
            . "Conversion intents to include naturally: {$conversionIntents}\n"
            . "Location: Mississauga, ON — also serves Toronto, Brampton, Vaughan, Markham, GTA\n"
            . "Category: {$category}\n"
            . "Target keywords (every single one MUST appear in both the short description AND body): {$keywords}\n"
            . "Word count: ~{$wordCount} words\n"
            . $competitorContext
            . "\nHTML body structure (follow this exactly):\n"
            . "1. <h1> — Topic + Location (e.g. \"HVAC Supplies in Mississauga, Ontario\")\n"
            . "2. Two intro <p> tags — hook the reader, introduce {$siteName}, naturally include at least 6 keywords\n"
            . "3. <h2>Our {$category} Include</h2> — <ul> with 8-10 specific products/items from this category\n"
            . "4. <h2>Why Choose {$siteName} for {$category}</h2> — <ul> with 6-8 benefits (pricing, quality, expertise, GTA service)\n"
            . "5. <h2>Serving {$primaryLocations} and the GTA</h2> — paragraph listing cities served, weave in location + category keywords\n"
            . "6. <h2>Frequently Asked Questions</h2> — 4 questions using long-tail keywords, concise answers\n"
            . "7. <h2>Visit Our Store or Shop Online</h2> — strong CTA paragraph linking to {$siteUrl}, include address and phone if appropriate\n"
            . "Format: clean HTML body only (no doctype). Use <ul><li> for lists. No inline styles.\n\n"
            . "After the HTML, output metadata EXACTLY in this format (no extra text):\n"
            . "META_TITLE: [under 60 chars — primary keyword + location]\n"
            . "META_DESC: [150-160 chars — question hook + brand + top 3 keywords + GTA/Mississauga]\n"
            . "META_KEYWORDS: [every target keyword comma-separated, no omissions]\n"
            . "SHORT_DESC: [3-4 sentences. Must naturally include EVERY target keyword. Written as a compelling blog listing excerpt that also works as a standalone SEO paragraph. Mention {$siteName}, Mississauga/GTA, and the product category. Do NOT cut off mid-sentence.]\n"
            . "SLUG: [lowercase-url-slug]";

        $raw = $ai->generate($prompt, $system, ['max_tokens' => 4000]);

        if (!$raw) {
            return $this->fallbackContent($topic, $category);
        }

        return $this->parseGeneratedContent($raw, $topic);
    }

    private function parseGeneratedContent(string $raw, string $topic): array
    {
        // Split HTML body from metadata block
        $parts = preg_split('/\nMETA_TITLE:/i', $raw, 2);
        $htmlBody  = trim($parts[0] ?? $raw);
        $metaBlock = isset($parts[1]) ? 'META_TITLE:' . $parts[1] : '';

        // Single-line fields
        $extractLine = function (string $key) use ($metaBlock): string {
            if (preg_match("/{$key}:\s*(.+)/i", $metaBlock, $m)) {
                return trim($m[1]);
            }
            return '';
        };

        // Multi-line field: capture everything until the next ALL_CAPS_KEY: line or end
        $extractBlock = function (string $key) use ($metaBlock): string {
            if (preg_match("/{$key}:\s*([\s\S]+?)(?=\n[A-Z_]+:|$)/i", $metaBlock, $m)) {
                return trim($m[1]);
            }
            return '';
        };

        $title     = $extractLine('META_TITLE') ?: Str::title(Str::limit($topic, 55));
        $metaDesc  = $extractLine('META_DESC') ?: Str::limit(strip_tags($htmlBody), 160);
        $metaKw    = $extractBlock('META_KEYWORDS') ?: '';
        $shortDesc = $extractBlock('SHORT_DESC') ?: Str::limit(strip_tags($htmlBody), 500);
        $slug      = $extractLine('SLUG') ?: Str::slug($title);

        // Ensure slug is URL-safe
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = $slug ?: Str::slug($title);

        // Make slug unique
        $baseSlug = $slug;
        $i = 1;
        while (Blog::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        return [
            'title'            => $title,
            'slug'             => $slug,
            'description'      => $htmlBody,
            'short_description'=> $shortDesc,
            'meta_title'       => Str::limit($title, 60),
            'meta_description' => Str::limit($metaDesc, 160),
            'meta_keywords'    => $metaKw,
        ];
    }

    private function enforceFullBlogBlueprint(array $content, string $topic, string $category, string $keywords, array $options): array
    {
        $title = trim((string)($options['blog_title'] ?? $content['title'] ?? $topic));
        $title = $title !== '' ? $title : Str::title(Str::limit($topic, 70, ''));

        $slugSeed = trim((string)($options['slug'] ?? $content['slug'] ?? $title));
        $content['title'] = Str::limit($title, 190, '');
        $content['slug'] = $this->uniqueBlogSlug($slugSeed);

        $keywordList = $this->keywordList($keywords, $title, $category);
        $content['meta_keywords'] = trim((string)($options['meta_keywords'] ?? $content['meta_keywords'] ?? ''));
        if ($content['meta_keywords'] === '') {
            $content['meta_keywords'] = implode(', ', $keywordList);
        }

        $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags((string)($content['description'] ?? ''))));
        if ($plainDescription === '') {
            $content['description'] = $this->fallbackContent($topic, $category)['description'];
            $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags($content['description'])));
        }

        if (!preg_match('/<h1[\s>]/i', (string)$content['description'])) {
            $content['description'] = '<h1>' . e($content['title']) . '</h1>' . "\n" . (string)$content['description'];
        }

        $shortDescription = trim(strip_tags((string)($content['short_description'] ?? '')));
        if (str_word_count($shortDescription) < 25) {
            $shortDescription = $this->buildShortDescription($content['title'], $category, $keywordList);
        }
        $content['short_description'] = Str::limit($shortDescription, 700, '');

        $metaTitle = trim((string)($options['meta_title'] ?? $content['meta_title'] ?? ''));
        $content['meta_title'] = Str::limit($metaTitle !== '' ? $metaTitle : $content['title'] . ' | Canada', 60, '');

        $metaDescription = trim(strip_tags((string)($options['meta_description'] ?? $content['meta_description'] ?? '')));
        if ($metaDescription === '') {
            $metaDescription = Str::limit($plainDescription ?: $shortDescription, 158, '');
        }
        $content['meta_description'] = Str::limit($metaDescription, 160, '');

        return $content;
    }

    private function uniqueBlogSlug(string $seed): string
    {
        $slug = Str::slug($seed) ?: 'ai-blog';
        $base = $slug;
        $i = 1;

        while (Blog::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function keywordList(string $keywords, string $title, string $category): array
    {
        $items = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $keywords)));
        $fallbacks = [
            $title,
            $category . ' Mississauga',
            $category . ' Brampton',
            $category . ' Toronto',
            $category . ' Canada',
            $category . ' GTA',
            'trade account',
            'leave a review',
        ];

        return array_values(array_unique(array_filter(array_merge($items, $fallbacks))));
    }

    private function buildShortDescription(string $title, string $category, array $keywords): string
    {
        $keywordText = implode(', ', array_slice($keywords, 0, 8));

        return "{$this->siteName} explains {$title} for Canadian buyers, contractors, and trade customers. "
            . "This guide covers {$category} options for Mississauga, Brampton, Toronto, and the GTA. "
            . "It includes practical buying tips, local supply guidance, and related search topics such as {$keywordText}.";
    }

    private function fallbackContent(string $topic, string $category): array
    {
        $title = Str::title(Str::limit($topic, 55));
        $slug  = Str::slug($title);
        $html = '<h1>' . e($title) . '</h1>'
            . '<p>' . e($this->siteName . ' helps Canadian buyers source dependable ' . $category . ' with practical product guidance, local availability, and support for Mississauga, Brampton, Toronto, and the GTA.') . '</p>'
            . '<p>' . e('Use this guide to compare options, understand common applications, and prepare a confident purchase for your business, trade, or facility requirements.') . '</p>'
            . '<h2>Our ' . e($category) . ' Include</h2>'
            . '<ul><li>Popular product options for trade and business buyers.</li><li>Helpful specifications for comparison.</li><li>Canada-focused supply and purchasing guidance.</li><li>Support for local customers across the GTA.</li></ul>'
            . '<h2>Why Choose ' . e($this->siteName) . '</h2>'
            . '<ul><li>Local supply support for Mississauga, Brampton, and Toronto.</li><li>Clear product information and practical buying help.</li><li>Trade-friendly service and dependable availability.</li></ul>'
            . '<h2>Frequently Asked Questions</h2>'
            . '<p><strong>Can I buy ' . e($category) . ' in Canada?</strong> Yes. ' . e($this->siteName) . ' supports Canadian customers with product guidance and local service.</p>'
            . '<p><strong>Do you support trade customers?</strong> Yes. Trade Account intent is supported where suitable.</p>';
        return [
            'title'             => $title,
            'slug'              => $slug,
            'description'       => $html,
            'short_description' => "{$this->siteName} explains {$topic} for Canadian buyers across Mississauga, Brampton, Toronto, and the GTA. This guide covers {$category} options, buying tips, and trade-friendly supply guidance.",
            'meta_title'        => $title,
            'meta_description'  => Str::limit("Learn about {$topic} at {$this->siteName}. Canada-focused guide for Mississauga, Brampton, Toronto, and GTA buyers.", 160, ''),
            'meta_keywords'     => strtolower($topic) . ', ' . strtolower($category),
        ];
    }

    // ─── Image Fetching ───────────────────────────────────────────────────────

    /**
     * Priority: 1) Product images from the category  2) Pexels  3) Unsplash Source
     */
    private function fetchAndStoreImage(string $keyword, ?int $productCategoryId = null, ?int $blogCategoryId = null): ?int
    {
        try {
            // 1. Try to find a product image in the matching category
            $uploadId = $this->getProductImageForCategory($productCategoryId, $blogCategoryId, $keyword);
            if ($uploadId) return $uploadId;

            // 2. DALL-E 3 image generation (uses same OpenAI key as blog content)
            $openAiKey = SocialAutomationSetting::get('social_ai_openai_key')
                ?: get_setting('seo_openai_api_key');
            if ($openAiKey) {
                $uploadId = $this->generateDalleImage($keyword, $openAiKey);
                if ($uploadId) return $uploadId;
            }

            // 3. Pexels API (if key set)
            $pexelsKey = SocialAutomationSetting::get('ai_blog_pexels_api_key', '');
            if ($pexelsKey) {
                $uploadId = $this->fetchFromPexels($keyword, $pexelsKey);
                if ($uploadId) return $uploadId;
            }

            // 4. Fallback: Unsplash Source (free, no key needed)
            return $this->fetchFromUnsplashSource($keyword);
        } catch (\Throwable $e) {
            Log::warning("AI Blog image fetch failed: " . $e->getMessage());
            return null;
        }
    }

    private function generateDalleImage(string $keyword, string $apiKey): ?int
    {
        $siteName = $this->siteName;
        $prompt   = "Professional, high-quality product photography banner image for a blog post about '{$keyword}'. "
            . "Clean white or light background, sharp details, commercial e-commerce style. "
            . "Wide landscape format 1300x650. No text, no logos, no watermarks. "
            . "Photorealistic, well-lit, attractive marketing image for {$siteName}.";

        try {
            $response = Http::timeout(60)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model'   => 'dall-e-3',
                    'prompt'  => $prompt,
                    'n'       => 1,
                    'size'    => '1792x1024',
                    'quality' => 'standard',
                    'style'   => 'natural',
                ]);

            if (!$response->successful()) {
                Log::warning('DALL-E generation failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $imageUrl = data_get($response->json(), 'data.0.url');
            if (!$imageUrl) return null;

            return $this->downloadAndSaveUpload($imageUrl, 'dalle-' . $keyword);
        } catch (\Throwable $e) {
            Log::warning('DALL-E exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Collect 4-5 product images from the category and build a collage banner.
     * Returns the Upload ID of the generated collage, or null on failure.
     */
    private function getProductImageForCategory(?int $productCategoryId, ?int $blogCategoryId, string $keyword): ?int
    {
        $query = \App\Models\Product::query()->whereNotNull('thumbnail_img');

        if ($productCategoryId) {
            $query->where('category_id', $productCategoryId);
        } else {
            $catName    = Str::words($keyword, 2, '');
            $matchedCat = \App\Models\Category::where('name', 'like', "%{$catName}%")->first();
            if ($matchedCat) {
                $query->where('category_id', $matchedCat->id);
            }
        }

        // Grab 5 random products with thumbnails
        $products = $query->inRandomOrder()->limit(5)->get();
        if ($products->isEmpty()) return null;

        // Resolve local file paths for each product thumbnail
        $imagePaths = [];
        foreach ($products as $product) {
            $upload = Upload::find($product->thumbnail_img);
            if (!$upload) continue;

            // Normalise the stored path to an absolute disk path
            $filePath = $this->resolveUploadPath($upload->file_name);
            if ($filePath && file_exists($filePath)) {
                $imagePaths[] = $filePath;
            }
            if (count($imagePaths) >= 5) break;
        }

        if (empty($imagePaths)) return null;

        // Build collage and save as a new Upload record
        return $this->buildCollage($imagePaths, $keyword);
    }

    /**
     * Resolve an upload file_name to an absolute path on disk.
     */
    private function resolveUploadPath(string $fileName): ?string
    {
        // Strip leading "public/" used by Storage::disk('public')
        $relative = ltrim(str_replace('public/', '', $fileName), '/');

        // Try storage_path first (Laravel storage/app/public/...)
        $storagePath = storage_path('app/public/' . $relative);
        if (file_exists($storagePath)) return $storagePath;

        // Fallback: public_path (uploads directly in public/)
        $publicPath = public_path($relative);
        if (file_exists($publicPath)) return $publicPath;

        return null;
    }

    /**
     * Use PHP GD to create a 1200×630 banner collage from 2-5 images.
     */
    private function buildCollage(array $imagePaths, string $keyword): ?int
    {
        if (!extension_loaded('gd')) {
            // GD not available — just return first image's upload id directly
            foreach ($imagePaths as $path) {
                $filename = basename($path);
                $upload   = Upload::where('file_name', 'like', "%{$filename}")->first();
                if ($upload) return $upload->id;
            }
            return null;
        }

        $count    = min(count($imagePaths), 5);
        $canvasW  = self::BLOG_BANNER_WIDTH;
        $canvasH  = self::BLOG_BANNER_HEIGHT;

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        $bg     = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $bg);

        // Layout grids: [cols, rows] for 2-5 images
        $layouts = [2 => [2,1], 3 => [3,1], 4 => [2,2], 5 => [3,2]];
        [$cols, $rows] = $layouts[$count] ?? [2,2];
        $cellW = intdiv($canvasW, $cols);
        $cellH = intdiv($canvasH, $rows);
        $gap   = 4;

        foreach (array_slice($imagePaths, 0, $count) as $i => $path) {
            $src = $this->loadGdImage($path);
            if (!$src) continue;

            $col = $i % $cols;
            $row = intdiv($i, $cols);
            $dstX = $col * $cellW + $gap;
            $dstY = $row * $cellH + $gap;
            $dstW = $cellW - $gap * 2;
            $dstH = $cellH - $gap * 2;

            imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $dstW, $dstH, imagesx($src), imagesy($src));
            imagedestroy($src);
        }

        // Save collage directly to public/uploads/all so asset('uploads/all/...') resolves correctly
        $filename = 'ai-blog-collage-' . Str::slug(Str::limit($keyword, 30)) . '-' . time() . '.jpg';
        $dir      = public_path('uploads/all');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $absPath  = $dir . '/' . $filename;

        try {
            imagejpeg($canvas, $absPath, 88);
        } finally {
            imagedestroy($canvas);
        }

        $fileSize = filesize($absPath);
        $upload   = Upload::create([
            'file_original_name' => $filename,
            'file_name'          => 'uploads/all/' . $filename,
            'user_id'            => 1,
            'extension'          => 'jpg',
            'type'               => 'image',
            'file_size'          => $fileSize,
        ]);

        return $upload->id;
    }

    private function loadGdImage(string $path): mixed
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png'         => @imagecreatefrompng($path),
            'gif'         => @imagecreatefromgif($path),
            'webp'        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default       => null,
        };
    }

    private function fetchFromPexels(string $keyword, string $apiKey): ?int
    {
        $response = Http::withHeaders(['Authorization' => $apiKey])
            ->get('https://api.pexels.com/v1/search', [
                'query'       => $keyword,
                'per_page'    => 1,
                'orientation' => 'landscape',
            ]);

        if (!$response->successful()) return null;

        $imageUrl = data_get($response->json(), 'photos.0.src.large2x');
        if (!$imageUrl) return null;

        return $this->downloadAndSaveUpload($imageUrl, $keyword);
    }

    private function fetchFromUnsplashSource(string $keyword): ?int
    {
        $query    = urlencode(Str::limit($keyword, 50));
        $imageUrl = "https://source.unsplash.com/" . self::BLOG_BANNER_WIDTH . "x" . self::BLOG_BANNER_HEIGHT . "/?{$query}";

        return $this->downloadAndSaveUpload($imageUrl, $keyword);
    }

    private function downloadAndSaveUpload(string $imageUrl, string $keyword): ?int
    {
        // withOptions(['allow_redirects' => true]) ensures Unsplash Source redirects are followed
        $response = Http::timeout(20)->withOptions(['allow_redirects' => true])->get($imageUrl);
        $imageContent = $response->successful() ? $response->body() : null;
        if (!$imageContent) return null;

        return $this->saveBannerUploadFromContent($imageContent, $keyword);
    }

    private function saveBannerUploadFromContent(string $imageContent, string $keyword): ?int
    {
        $filename = 'ai-blog-' . Str::slug(Str::limit($keyword, 30)) . '-' . time() . '.jpg';
        $path = 'uploads/all/' . $filename;
        $dir = public_path('uploads/all');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $absPath = public_path($path);
        $saved = false;

        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring($imageContent);
            if ($src) {
                $canvas = imagecreatetruecolor(self::BLOG_BANNER_WIDTH, self::BLOG_BANNER_HEIGHT);
                $bg = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $bg);

                $srcW = imagesx($src);
                $srcH = imagesy($src);
                $scale = max(self::BLOG_BANNER_WIDTH / max(1, $srcW), self::BLOG_BANNER_HEIGHT / max(1, $srcH));
                $newW = (int) ceil($srcW * $scale);
                $newH = (int) ceil($srcH * $scale);
                $dstX = (int) floor((self::BLOG_BANNER_WIDTH - $newW) / 2);
                $dstY = (int) floor((self::BLOG_BANNER_HEIGHT - $newH) / 2);

                imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
                imagejpeg($canvas, $absPath, 88);
                imagedestroy($src);
                imagedestroy($canvas);
                $saved = true;
            }
        }

        if (!$saved) {
            file_put_contents($absPath, $imageContent);
        }

        $upload = Upload::create([
            'file_original_name' => $filename,
            'file_name' => $path,
            'user_id' => 1,
            'extension' => 'jpg',
            'type' => 'image',
            'file_size' => filesize($absPath) ?: strlen($imageContent),
        ]);

        return $upload->id;
    }

    private function buildFallbackBanner(string $topic, string $category): ?int
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $canvas = imagecreatetruecolor(self::BLOG_BANNER_WIDTH, self::BLOG_BANNER_HEIGHT);
        $bg = imagecolorallocate($canvas, 245, 248, 252);
        $line = imagecolorallocate($canvas, 42, 98, 158);
        $text = imagecolorallocate($canvas, 30, 41, 59);
        $muted = imagecolorallocate($canvas, 91, 105, 122);
        imagefill($canvas, 0, 0, $bg);

        for ($i = 0; $i < self::BLOG_BANNER_WIDTH; $i += 26) {
            imageline($canvas, $i, 0, $i - 220, self::BLOG_BANNER_HEIGHT, $line);
        }

        $title = Str::limit($topic, 58, '');
        $subtitle = Str::limit($category . ' | ' . $this->siteName, 70, '');
        imagestring($canvas, 5, 70, 260, $title, $text);
        imagestring($canvas, 4, 70, 305, $subtitle, $muted);
        imagestring($canvas, 3, 70, 340, 'Mississauga - Brampton - Toronto - GTA', $muted);

        ob_start();
        imagejpeg($canvas, null, 88);
        $content = (string) ob_get_clean();
        imagedestroy($canvas);

        return $this->saveBannerUploadFromContent($content, 'fallback-' . $topic);
    }

    // ─── Blog Save ────────────────────────────────────────────────────────────

    private function saveBlog(array $content, int $categoryId, ?int $uploadId, bool $publish, ?int $metaImageId = null): Blog
    {
        $str = fn($v) => is_array($v) ? implode(', ', $v) : (string)($v ?? '');

        $blog = new Blog();
        $blog->category_id       = $categoryId;
        $blog->title             = $str($content['title'] ?? '');
        $blog->slug              = $str($content['slug'] ?? Str::slug($content['title'] ?? 'blog'));
        $blog->short_description = $str($content['short_description'] ?? '');
        $blog->description       = $str($content['description'] ?? '');
        $blog->banner            = $uploadId;
        $blog->meta_title        = $str($content['meta_title'] ?? '');
        $blog->meta_img          = $metaImageId ?: $uploadId;
        $blog->meta_description  = $str($content['meta_description'] ?? '');
        $blog->meta_keywords     = $str($content['meta_keywords'] ?? '');
        $blog->status            = $publish ? 1 : 0;
        $blog->news              = 0;
        $blog->event             = 0;
        $blog->going_on          = 0;
        $blog->save();

        return $blog;
    }

    // ─── Social Media Post ────────────────────────────────────────────────────

    private function postBlogToSocial(Blog $blog, array $options): void
    {
        $rawPlatforms = $options['social_platforms'] ?? SocialAutomationSetting::get('ai_blog_social_platforms', '[]');
        if (is_array($rawPlatforms)) {
            $platforms = $rawPlatforms;
        } elseif (is_string($rawPlatforms)) {
            $platforms = json_decode($rawPlatforms, true) ?? [];
        } else {
            $platforms = [];
        }

        if (empty($platforms)) return;

        $blogUrl = route('frontend.blog', ['slug' => $blog->slug], true);

        // Build social post content
        $socialContent = "📖 New Blog: {$blog->title}\n\n"
            . Str::limit(strip_tags($blog->short_description), 200)
            . "\n\nRead more: {$blogUrl}\n\n"
            . ($blog->meta_keywords ? '#' . implode(' #', array_slice(
                array_map(fn($k) => Str::camel(trim($k)), explode(',', $blog->meta_keywords)), 0, 5
            )) : '');

        $imageUrl = null;
        if ($blog->banner) {
            $upload   = Upload::find($blog->banner);
            $imageUrl = $upload ? asset($upload->file_name) : null;
        }

        foreach ($platforms as $platform) {
            $enabled = (bool) SocialAutomationSetting::get("social_{$platform}_enabled", false);
            if (!$enabled) continue;

            $opts = [];
            if ($imageUrl) {
                $opts['image_url'] = $imageUrl;
                $opts['link']      = $blogUrl;
            }

            PostToSocialMediaJob::dispatch(
                $platform,
                $socialContent,
                'ai_blog',
                $opts,
                null,
                $options['ai_provider'] ?? $this->provider,
            );
        }
    }

    // ─── Batch Generation ────────────────────────────────────────────────────

    public function generateBatch(int $count = 3, array $options = []): array
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            try {
                $results[] = $this->generate($options);
            } catch (\Throwable $e) {
                Log::error("AI Blog batch generation error #{$i}: " . $e->getMessage());
            }
        }
        return $results;
    }
}
