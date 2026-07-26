<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\SocialAutomationSetting;
use App\Services\Blog\AiBlogGeneratorService;
use App\Services\SocialMedia\AI\SocialAiProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiBlogController extends Controller
{
    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function index(): View
    {
        $recentBlogs  = Blog::with('category')->latest()->limit(10)->get();
        $blogCounts   = Blog::selectRaw('COUNT(*) as total, SUM(status=1) as published, SUM(status=0) as draft')->first();
        $totalBlogs   = $blogCounts->total;
        $publishedBlogs = $blogCounts->published;
        $draftBlogs   = $blogCounts->draft;
        $blogCategories  = BlogCategory::withCount('posts')->get();
        $productCategories = Category::select('id', 'name')->limit(100)->get();
        $aiProviders     = SocialAiProviderManager::availableProviders();
        $tones           = config('social_media.tones', []);
        $platforms       = config('social_media.platforms', []);

        $settings = $this->getSettings();

        return view('backend.ai_blog.index', compact(
            'recentBlogs', 'totalBlogs', 'publishedBlogs', 'draftBlogs',
            'blogCategories', 'productCategories', 'aiProviders', 'tones',
            'platforms', 'settings'
        ));
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    public function settings(): View
    {
        $aiProviders       = SocialAiProviderManager::availableProviders();
        $tones             = config('social_media.tones', []);
        $platforms         = config('social_media.platforms', []);
        $productCategories = Category::select('id', 'name')->limit(200)->get();
        $blogCategories    = BlogCategory::all();
        $settings          = $this->getSettings();

        return view('backend.ai_blog.settings', compact(
            'aiProviders', 'tones', 'platforms',
            'productCategories', 'blogCategories', 'settings'
        ));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $boolKeys  = ['ai_blog_auto_publish', 'ai_blog_post_to_social', 'ai_blog_enabled'];
        $arrayKeys = ['ai_blog_social_platforms'];

        foreach ($request->except(['_token'] + $arrayKeys) as $key => $value) {
            $type = in_array($key, $boolKeys) ? 'boolean' : 'string';
            SocialAutomationSetting::set($key, $value ?? '', $type, 'ai_blog');
        }

        // Handle boolean unchecked
        foreach ($boolKeys as $key) {
            if (!$request->has($key)) {
                SocialAutomationSetting::set($key, '0', 'boolean', 'ai_blog');
            }
        }

        // Handle array keys (platforms checkboxes)
        foreach ($arrayKeys as $key) {
            $val = $request->input($key, []);
            SocialAutomationSetting::set($key, json_encode($val), 'json', 'ai_blog');
        }

        flash(translate('AI Blog settings saved.'))->success();
        return back();
    }

    // ─── Generate Now ─────────────────────────────────────────────────────────

    public function generateNow(Request $request): JsonResponse
    {
        $request->validate([
            'count'    => 'nullable|integer|min:1|max:10',
            'provider' => 'nullable|in:openai,claude,gemini,grok',
            'blog_title' => 'nullable|string|max:190',
            'slug' => 'nullable|string|max:190',
            'blog_category_id' => 'nullable|integer|exists:blog_categories,id',
            'category_name' => 'nullable|string|max:190',
            'banner_upload_id' => 'nullable|integer',
            'meta_image_upload_id' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:190',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
        ]);

        $count    = (int)($request->count ?? 1);
        $provider = $request->provider ?? SocialAutomationSetting::get('ai_blog_provider', 'openai');

        $options = array_filter([
            'blog_title'          => $request->blog_title ?: null,
            'slug'                => $request->slug ?: null,
            'blog_category_id'    => $request->blog_category_id ? (int)$request->blog_category_id : null,
            'category_name'       => $request->category_name ?: null,
            'banner_upload_id'    => $request->banner_upload_id ? (int)$request->banner_upload_id : null,
            'meta_image_upload_id'=> $request->meta_image_upload_id ? (int)$request->meta_image_upload_id : null,
            'meta_title'          => $request->meta_title ?: null,
            'meta_description'    => $request->meta_description ?: null,
            'meta_keywords'       => $request->meta_keywords ?: null,
            'product_category_id' => $request->product_category_id ? (int)$request->product_category_id : null,
            'topic'               => $request->topic ?: null,
            'keywords'            => $request->keywords ?: null,
            'competitor_urls'     => $request->competitor_urls ?: null,
            'ai_provider'         => $provider,
            'tone'                => $request->tone ?: SocialAutomationSetting::get('ai_blog_tone', 'professional'),
            'publish'             => (bool)($request->publish ?? SocialAutomationSetting::get('ai_blog_auto_publish', false)),
            'post_to_social'      => (bool)($request->post_to_social ?? SocialAutomationSetting::get('ai_blog_post_to_social', false)),
            'social_platforms'    => $request->social_platforms ?: null,
        ]);

        try {
            $service = new AiBlogGeneratorService($provider);
            $blogs   = [];

            for ($i = 0; $i < $count; $i++) {
                // Auto-rotate product categories if none specified
                if (!isset($options['product_category_id']) && !isset($options['category_name'])) {
                    $productCat = Category::inRandomOrder()->first();
                    if ($productCat) $options['product_category_id'] = $productCat->id;
                }
                $blog    = $service->generate($options);
                $blogs[] = [
                    'id'         => $blog->id,
                    'title'      => $blog->title,
                    'category'   => $blog->category->category_name ?? '—',
                    'status'     => $blog->status ? 'Published' : 'Draft',
                    'edit_url'   => route('blog.edit', $blog->id),
                ];
            }

            return response()->json([
                'success' => true,
                'blogs'   => $blogs,
                'message' => 'Generated ' . count($blogs) . ' blog post(s) successfully.',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Blog generation failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => translate('Generation failed: ') . $e->getMessage() . ' (line ' . $e->getLine() . ')',
            ], 422);
        }
    }

    // ─── Quick Publish ────────────────────────────────────────────────────────

    public function publishBlog(Blog $blog): JsonResponse
    {
        $blog->update(['status' => 1]);
        return response()->json(['success' => true]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getSettings(): array
    {
        return SocialAutomationSetting::getMultiple([
            'ai_blog_enabled', 'ai_blog_provider', 'ai_blog_tone',
            'ai_blog_word_count', 'ai_blog_target_country',
            'ai_blog_competitor_urls', 'ai_blog_pexels_api_key',
            'ai_blog_auto_publish', 'ai_blog_post_to_social',
            'ai_blog_social_platforms', 'ai_blog_schedule_time',
            'ai_blog_posts_per_day',
            'ai_blog_primary_locations', 'ai_blog_secondary_locations',
            'ai_blog_conversion_intents',
        ]);
    }
}
