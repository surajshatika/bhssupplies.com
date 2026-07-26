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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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
        $settings = $this->loadOptimizationSettings();
        $optimizationDashboard = $this->buildOptimizationDashboard($runs, $redirects, $histories, $dashboard, $settings);

        return view('backend.seo.optimization.index', compact(
            'project',
            'features',
            'runs',
            'redirects',
            'histories',
            'dashboard',
            'setupRequired',
            'settings',
            'optimizationDashboard'
        ));
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

    protected function loadOptimizationSettings(): array
    {
        return [
            'master_automation_enabled' => (int) get_setting('seo_master_automation_enabled', 1),
            'auto_optimization_enabled' => (int) get_setting('seo_auto_optimization_enabled', 1),
            'auto_seo_enabled' => (int) get_setting('seo_auto_seo_enabled', 1),
            'auto_seo_batch_size' => (int) get_setting('seo_auto_seo_batch_size', 10),
            'auto_offpage_enabled' => (int) get_setting('seo_auto_offpage_enabled', 1),
            'auto_offpage_batch_size' => (int) get_setting('seo_auto_offpage_batch_size', 3),
            'auto_indexnow' => (int) get_setting('seo_auto_indexnow', 0),
            'indexnow_key' => get_setting('seo_indexnow_key', config('seo.indexnow.key')),
            'search_console_site' => get_setting('seo_search_console_site', config('seo.search_console.site_url')),
            'gsc_refresh_token' => get_setting('seo_gsc_refresh_token'),
            'pagespeed_api_key' => get_setting('seo_pagespeed_api_key'),
            'enable_minify' => (int) get_setting('seo_optimization_minify', 0),
            'enable_lazyload' => (int) get_setting('seo_optimization_lazyload', 0),
            'cloudflare_api_token' => get_setting('seo_cloudflare_api_token'),
            'cloudflare_zone_id' => get_setting('seo_cloudflare_zone_id'),
            'auto_cloudflare_purge' => (int) get_setting('seo_auto_cloudflare_purge', 0),
            'openai_api_key' => env('OPENAI_API_KEY') ?? get_setting('seo_openai_api_key'),
            'anthropic_api_key' => env('ANTHROPIC_API_KEY') ?? get_setting('seo_anthropic_api_key'),
            'gemini_api_key' => env('GEMINI_API_KEY') ?? get_setting('seo_gemini_api_key'),
            'grok_api_key' => env('GROK_API_KEY') ?? get_setting('seo_grok_api_key'),
            'perplexity_api_key' => env('PERPLEXITY_API_KEY') ?? get_setting('seo_perplexity_api_key'),
            'mistral_api_key' => env('MISTRAL_API_KEY') ?? get_setting('seo_mistral_api_key'),
            'deepseek_api_key' => env('DEEPSEEK_API_KEY') ?? get_setting('seo_deepseek_api_key'),
        ];
    }

    protected function buildOptimizationDashboard($runs, $redirects, $histories, array $dashboard, array $settings): array
    {
        $runs = collect($runs);
        $redirects = collect($redirects);
        $histories = collect($histories);

        $files = [
            'sitemap' => $this->fileHealth('Smart Sitemap', base_path('sitemap.xml'), 'la-sitemap', 'admin.seo-suite.sitemap', 'post'),
            'robots' => $this->fileHealth('Robots.txt', public_path('robots.txt'), 'la-robot', 'admin.seo-suite.robots', 'post'),
            'llms' => $this->fileHealth('LLMs.txt', public_path('llms.txt'), 'la-file-code', 'admin.seo-suite.llms_txt', 'post'),
            'rss' => $this->fileHealth('RSS Feed', public_path('rss.xml'), 'la-rss', 'admin.seo-suite.rss', 'post'),
        ];

        $totalRuns = $runs->count();
        $completedRuns = $runs->where('status', 'completed')->count();
        $failedRuns = $runs->where('status', 'failed')->count();
        $queuedRuns = $runs->whereIn('status', ['queued', 'processing', 'running'])->count();
        $successRate = $totalRuns > 0 ? (int) round(($completedRuns / $totalRuns) * 100) : 100;

        $providersConfigured = collect([
            $settings['openai_api_key'] ?? null,
            $settings['anthropic_api_key'] ?? null,
            $settings['gemini_api_key'] ?? null,
            $settings['grok_api_key'] ?? null,
            $settings['perplexity_api_key'] ?? null,
            $settings['mistral_api_key'] ?? null,
            $settings['deepseek_api_key'] ?? null,
        ])->filter()->count();

        $readinessParts = [
            $files['sitemap']['exists'] ? 15 : 0,
            $files['robots']['exists'] ? 12 : 0,
            $files['llms']['exists'] ? 10 : 0,
            $files['rss']['exists'] ? 8 : 0,
            !empty($settings['indexnow_key']) ? 10 : 0,
            !empty($settings['gsc_refresh_token']) ? 12 : 0,
            !empty($settings['pagespeed_api_key']) ? 10 : 0,
            !empty($settings['enable_minify']) ? 6 : 0,
            !empty($settings['enable_lazyload']) ? 6 : 0,
            !empty($settings['master_automation_enabled']) ? 11 : 0,
        ];

        $actions = [];
        if (empty($settings['master_automation_enabled'])) {
            $actions[] = $this->optimizationAction('critical', 'la-bolt', 'Enable master hourly automation', 'Turn on the master SEO automation command so cron can run pending SEO every hour.', 'admin.seo-suite.settings.view');
        }
        foreach ($files as $file) {
            if (!$file['exists']) {
                $actions[] = $this->optimizationAction('high', $file['icon'], 'Generate ' . $file['label'], 'Required technical SEO artifact is missing.', $file['route'], $file['method']);
            } elseif (($file['age_days'] ?? 0) > 7) {
                $actions[] = $this->optimizationAction('medium', $file['icon'], 'Refresh ' . $file['label'], 'Last generated ' . $file['age_days'] . ' days ago.', $file['route'], $file['method']);
            }
        }
        if (empty($settings['indexnow_key'])) {
            $actions[] = $this->optimizationAction('high', 'la-bolt', 'Generate IndexNow key', 'Required for instant URL discovery on Bing/Yandex.', 'admin.seo-suite.indexnow.generate_key', 'post');
        }
        if (empty($settings['gsc_refresh_token'])) {
            $actions[] = $this->optimizationAction('high', 'la-chart-line', 'Connect Search Console', 'Unlock query, click, impression, and coverage automation.', 'admin.seo-suite.settings.view');
        }
        if (empty($settings['pagespeed_api_key'])) {
            $actions[] = $this->optimizationAction('medium', 'la-tachometer-alt', 'Add PageSpeed API key', 'Enable scheduled Core Web Vitals checks.', 'admin.seo-suite.settings.view');
        }
        if (empty($settings['enable_minify']) || empty($settings['enable_lazyload'])) {
            $actions[] = $this->optimizationAction('medium', 'la-compress-arrows-alt', 'Enable performance switches', 'Minify and lazy-load settings improve crawl and Core Web Vitals readiness.', 'admin.seo-suite.settings.view');
        }
        if ($failedRuns > 0) {
            $actions[] = $this->optimizationAction('medium', 'la-exclamation-circle', 'Review failed optimization runs', $failedRuns . ' recent optimization task(s) failed.', 'admin.seo-suite.revisions');
        }

        $actions = collect($actions)->sortBy(fn($action) => [
            'critical' => 0,
            'high' => 1,
            'medium' => 2,
            'low' => 3,
        ][$action['severity']] ?? 4)->take(6)->values()->all();

        return [
            'technical_readiness' => min(100, array_sum($readinessParts)),
            'success_rate' => $successRate,
            'failed_runs' => $failedRuns,
            'queued_runs' => $queuedRuns,
            'providers_configured' => $providersConfigured,
            'active_redirects' => Schema::hasTable('seo_redirects') ? SeoRedirect::query()->where('is_active', true)->count() : $redirects->where('is_active', true)->count(),
            'history_count' => $histories->count(),
            'files' => $files,
            'actions' => $actions,
            'automation' => [
                'master_enabled' => !empty($settings['master_automation_enabled']),
                'technical_enabled' => !empty($settings['auto_optimization_enabled']),
                'onpage_enabled' => !empty($settings['auto_seo_enabled']),
                'offpage_enabled' => !empty($settings['auto_offpage_enabled']),
                'auto_indexnow' => !empty($settings['auto_indexnow']),
                'cron_command' => 'php artisan seo:automation-run',
                'dry_run_command' => 'php artisan seo:automation-run --dry-run',
                'scheduler_cron' => '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
                'direct_hourly_cron' => '0 * * * * cd ' . base_path() . ' && php artisan seo:automation-run >> storage/logs/seo-automation.log 2>&1',
                'onpage_batch_size' => (int) ($settings['auto_seo_batch_size'] ?? 10),
                'offpage_batch_size' => (int) ($settings['auto_offpage_batch_size'] ?? 3),
            ],
            'local_targets' => [
                'primary' => ['Mississauga', 'Brampton', 'Toronto'],
                'secondary' => ['Etobicoke', 'Vaughan', 'Oakville', 'Scarborough', 'Markham', 'North York', 'Burlington'],
                'conversion' => ['Trade Account', 'Leave a Review'],
            ],
            'current_score' => (int) ($dashboard['current_score'] ?? 0),
            'current_grade' => $dashboard['current_grade'] ?? 'N/A',
        ];
    }

    protected function fileHealth(string $label, string $path, string $icon, string $route, string $method = 'get'): array
    {
        $exists = File::exists($path);
        $updatedAt = $exists ? File::lastModified($path) : null;

        return [
            'label' => $label,
            'icon' => $icon,
            'route' => $route,
            'method' => $method,
            'exists' => $exists,
            'size' => $exists ? File::size($path) : 0,
            'updated_at' => $updatedAt ? date('M d, Y H:i', $updatedAt) : null,
            'age_days' => $updatedAt ? now()->diffInDays(\Carbon\Carbon::createFromTimestamp($updatedAt)) : null,
            'path' => $path,
        ];
    }

    protected function optimizationAction(string $severity, string $icon, string $title, string $detail, string $route, string $method = 'get'): array
    {
        return compact('severity', 'icon', 'title', 'detail', 'route', 'method');
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
