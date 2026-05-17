<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateOptimizationJob;
use App\Models\SeoRedirect;
use App\Models\SeoRun;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Optimization\OptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OptimizationController extends Controller
{
    use SeoPayloadTrait;

    public function index()
    {
        $setupRequired = !$this->seoTablesReady();
        $project = $setupRequired ? null : $this->defaultProject();
        $features = config('seo.features.optimization', []);
        $runs = $setupRequired ? collect() : SeoRun::where('module', 'optimization')->latest()->limit(15)->get();
        $redirects = $setupRequired ? collect() : SeoRedirect::query()->latest()->limit(10)->get();
        $histories = $setupRequired ? collect() : SeoScoreHistory::query()->latest('recorded_at')->limit(12)->get();
        $dashboard = $setupRequired
            ? ['current_score' => 0, 'current_grade' => 'N/A', 'trend' => [], 'average_score' => 0, 'provider' => 'aggregate']
            : app(OptimizationService::class)->buildScoreDashboard(['project_id' => $project->id]);

        return view('backend.seo.optimization.index', compact('project', 'features', 'runs', 'redirects', 'histories', 'dashboard', 'setupRequired'));
    }

    public function run(Request $request)
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first to enable the AI SEO Suite.'))->warning();
            return redirect()->route('admin.seo_optimization.index');
        }

        $request->validate([
            'feature' => 'required|string',
        ]);

        $project = $this->defaultProject();
        $payload = $this->buildPayload($request);
        $provider = $request->provider ?: get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));
        
        $run = SeoRun::create([
            'project_id' => $project->id,
            'module' => 'optimization',
            'feature' => $request->feature,
            'provider' => $provider,
            'status' => 'queued',
            'url' => Arr::get($payload, 'url'),
            'input_payload' => $payload,
        ]);

        GenerateOptimizationJob::dispatch($run->id);

        flash(translate('Optimization task queued successfully'))->success();
        return redirect()->route('admin.seo_optimization.index');
    }

    public function generateSitemap()
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo_optimization.index');
        }

        app(OptimizationService::class)->generateSitemap(['persist' => true]);
        flash(translate('Sitemap generated successfully'))->success();

        return redirect()->route('admin.seo_optimization.index');
    }

    public function generateRobots()
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo_optimization.index');
        }

        app(OptimizationService::class)->optimizeRobotsTxt(['persist' => true]);
        flash(translate('Robots file generated successfully'))->success();

        return redirect()->route('admin.seo_optimization.index');
    }

    public function storeRedirect(Request $request)
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo_optimization.index');
        }

        $request->validate([
            'source_url' => 'required',
            'target_url' => 'required',
        ]);

        SeoRedirect::updateOrCreate(
            ['source_url' => $request->source_url],
            [
                'target_url' => $request->target_url,
                'status_code' => $request->input('status_code', 301),
                'is_active' => true,
                'notes' => $request->input('notes'),
            ]
        );

        flash(translate('Redirect saved successfully'))->success();
        return redirect()->route('admin.seo_optimization.index');
    }

    /**
     * AI-powered Meta Tag generation for a product/category/page.
     * Called via AJAX. Always returns success — falls back to template if AI fails.
     */
    public function generateMetaTags(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name     = trim($request->input('name'));
        $desc     = strip_tags($request->input('description', ''));
        $siteName = get_setting('website_name', config('app.name'));

        if (strlen($desc) > 500) {
            $desc = substr($desc, 0, 500);
        }

        // ── Try AI generation ──────────────────────────────────────────────────
        $aiResult = $this->tryAiGenerate($name, $desc);
        if ($aiResult) {
            return response()->json(array_merge(['success' => true, 'source' => 'ai'], $aiResult));
        }

        // ── Template fallback (always succeeds) ───────────────────────────────
        $title       = $this->templateTitle($name, $siteName);
        $description = $this->templateDescription($name, $desc);

        return response()->json([
            'success'     => true,
            'source'      => 'template',
            'title'       => $title,
            'description' => $description,
            'note'        => 'Generated from template. Add a valid AI provider API key in SEO Settings for AI-powered tags.',
        ]);
    }

    private function tryAiGenerate(string $name, string $desc): ?array
    {
        $providerName = config('seo.default_provider', 'openai');

        try {
            $seoProvider = match ($providerName) {
                'claude' => new \App\Services\Seo\Providers\ClaudeProvider(),
                'gemini' => new \App\Services\Seo\Providers\GeminiProvider(),
                'openai' => new \App\Services\Seo\Providers\OpenAIProvider(),
                default  => null,
            };
        } catch (\Throwable $e) {
            \Log::warning('SEO: Provider instantiation failed: ' . $e->getMessage());
            return null;
        }

        if (!$seoProvider || !$seoProvider->isConfigured()) {
            return null;
        }

        $systemPrompt = 'You are an expert SEO copywriter. Output ONLY valid JSON with no markdown, no explanation, no code fences.';
        $prompt = 'Write an SEO meta title (max 60 chars) and meta description (max 160 chars).'
            . ' Name: "' . $name . '".'
            . ($desc ? ' Details: "' . $desc . '".' : '')
            . ' Return ONLY this JSON: {"title":"...","description":"..."}';

        try {
            $raw = $seoProvider->generate($prompt, $systemPrompt, ['max_tokens' => 300]);

            if (empty($raw)) {
                \Log::warning('SEO: AI provider returned empty response', ['provider' => $providerName]);
                return null;
            }

            // Extract JSON — handle code fences and extra whitespace
            $raw = preg_replace('/```(?:json)?|```/', '', $raw);
            preg_match('/\{[^{}]*"title"[^{}]*"description"[^{}]*\}/s', $raw, $matches);
            if (empty($matches)) {
                preg_match('/\{.*\}/s', $raw, $matches);
            }

            $decoded = json_decode($matches[0] ?? $raw, true);

            if (is_array($decoded) && !empty($decoded['title']) && !empty($decoded['description'])) {
                return [
                    'title'       => substr(trim($decoded['title']), 0, 60),
                    'description' => substr(trim($decoded['description']), 0, 160),
                ];
            }

            \Log::warning('SEO: Could not parse AI JSON response', ['raw' => substr($raw, 0, 200)]);
        } catch (\Throwable $e) {
            \Log::warning('SEO: AI generation exception: ' . $e->getMessage());
        }

        return null;
    }

    public function generateProductContent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name     = trim($request->input('name'));
        $siteName = get_setting('website_name', config('app.name'));
        $providerName = config('seo.default_provider', 'openai');

        try {
            $seoProvider = match ($providerName) {
                'claude' => new \App\Services\Seo\Providers\ClaudeProvider(),
                'gemini' => new \App\Services\Seo\Providers\GeminiProvider(),
                'openai' => new \App\Services\Seo\Providers\OpenAIProvider(),
                default  => null,
            };
        } catch (\Throwable $e) {
            $seoProvider = null;
        }

        if ($seoProvider && $seoProvider->isConfigured()) {
            $systemPrompt = 'You are an expert ecommerce product copywriter. Output ONLY valid JSON with no markdown, no explanation, no code fences.';
            $prompt = 'Write product descriptions for an ecommerce product named "' . $name . '" sold on ' . $siteName . '.'
                . ' short_description: 1-2 sentences summarizing the key benefit (max 200 chars).'
                . ' description: 3-5 sentences covering features, use cases, and why to buy (max 600 chars).'
                . ' Return ONLY this JSON: {"short_description":"...","description":"..."}';

            try {
                $raw = $seoProvider->generate($prompt, $systemPrompt, ['max_tokens' => 700]);
                $raw = preg_replace('/```(?:json)?|```/', '', $raw);
                preg_match('/\{.*\}/s', $raw, $matches);
                $decoded = json_decode($matches[0] ?? $raw, true);

                if (is_array($decoded) && !empty($decoded['short_description']) && !empty($decoded['description'])) {
                    return response()->json([
                        'success'           => true,
                        'source'            => 'ai',
                        'short_description' => trim($decoded['short_description']),
                        'description'       => trim($decoded['description']),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('Product content AI failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'           => true,
            'source'            => 'template',
            'short_description' => 'High-quality ' . $name . ' — built for performance, reliability, and value.',
            'description'       => 'Discover the ' . $name . ', a premium product available at ' . $siteName . '. Designed to deliver outstanding performance and durability, it is the ideal choice for professionals and everyday users alike. Shop with confidence and enjoy fast delivery and competitive pricing on every order.',
            'note'              => 'Generated from template. Add an AI provider API key in SEO Settings for AI-powered content.',
        ]);
    }

    public function generateCategoryContent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name     = trim($request->input('name'));
        $siteName = get_setting('website_name', config('app.name'));
        $providerName = config('seo.default_provider', 'openai');

        try {
            $seoProvider = match ($providerName) {
                'claude' => new \App\Services\Seo\Providers\ClaudeProvider(),
                'gemini' => new \App\Services\Seo\Providers\GeminiProvider(),
                'openai' => new \App\Services\Seo\Providers\OpenAIProvider(),
                default  => null,
            };
        } catch (\Throwable $e) {
            $seoProvider = null;
        }

        if ($seoProvider && $seoProvider->isConfigured()) {
            $systemPrompt = 'You are an expert ecommerce copywriter. Output ONLY valid JSON with no markdown, no explanation, no code fences.';
            $prompt = 'Write a Top Description and a Bottom Description for a product category page named "' . $name . '" on ' . $siteName . '.'
                . ' Top Description: 2-3 sentences welcoming visitors and introducing the category (shown above products).'
                . ' Bottom Description: 3-4 sentences with SEO-rich content about the category, benefits, and why to buy (shown below products).'
                . ' Return ONLY this JSON: {"top_description":"...","bottom_description":"..."}';

            try {
                $raw = $seoProvider->generate($prompt, $systemPrompt, ['max_tokens' => 600]);
                $raw = preg_replace('/```(?:json)?|```/', '', $raw);
                preg_match('/\{.*\}/s', $raw, $matches);
                $decoded = json_decode($matches[0] ?? $raw, true);

                if (is_array($decoded) && !empty($decoded['top_description']) && !empty($decoded['bottom_description'])) {
                    return response()->json([
                        'success'          => true,
                        'source'           => 'ai',
                        'top_description'  => trim($decoded['top_description']),
                        'bottom_description' => trim($decoded['bottom_description']),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('Category content AI failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'          => true,
            'source'           => 'template',
            'top_description'  => 'Welcome to our ' . $name . ' collection. Browse our wide range of high-quality ' . $name . ' products, carefully selected to meet your needs.',
            'bottom_description' => 'At ' . $siteName . ', we offer an extensive range of ' . $name . ' products to suit every requirement. Our ' . $name . ' selection features top brands and premium quality items, all available at competitive prices. Shop with confidence knowing you\'ll receive fast shipping and excellent customer service on every order.',
            'note'             => 'Generated from template. Add an AI provider API key in SEO Settings for AI-powered content.',
        ]);
    }

    private function templateTitle(string $name, string $siteName): string
    {
        $full  = $name . ' | ' . $siteName;
        $short = $name;
        return substr(strlen($full) <= 60 ? $full : $short, 0, 60);
    }

    private function templateDescription(string $name, string $desc): string
    {
        if ($desc) {
            $text = 'Shop ' . $name . '. ' . $desc;
            return substr($text, 0, 160);
        }
        return substr('Shop our full range of ' . $name . '. Quality products, fast delivery, and competitive prices guaranteed.', 0, 160);
    }
}
