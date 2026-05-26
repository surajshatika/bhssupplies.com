<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateOnPageSeoJob;
use App\Jobs\GenerateOptimizationJob;
use App\Jobs\GenerateSeoContentJob;
use App\Models\BusinessSetting;
use App\Models\SeoProject;
use App\Models\SeoRedirect;
use App\Models\SeoRun;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Optimization\OptimizationService;
use App\Services\Seo\Optimization\Features\IndexNowService;
use App\Services\Seo\Optimization\Features\LlmsTxtService;
use App\Services\Seo\Optimization\Features\SmartSitemapService;
use App\Services\Seo\Optimization\Features\VideoSitemapService;
use App\Services\Seo\Optimization\Features\GoogleNewsSitemapService;
use App\Services\Seo\Optimization\Features\RssContentService;
use App\Services\Seo\Optimization\Features\WebmasterToolsService;
use App\Services\Seo\Optimization\Features\KeywordRankTrackerService;
use App\Services\Seo\Optimization\Features\SearchStatisticsService;
use App\Services\Seo\Optimization\Features\PostIndexStatusService;
use App\Services\Seo\Optimization\Features\AiWritingAssistantService;
use App\Services\Seo\Optimization\Features\AiImageGeneratorService;
use App\Services\Seo\Optimization\Features\AiAssistantService;
use App\Services\Seo\Optimization\Features\SeoRevisionsService;
use App\Services\Seo\Optimization\Features\LinkAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoSuiteController extends Controller
{
    public function index()
    {
        $setupRequired = !$this->seoTablesReady();
        $project = $setupRequired ? new SeoProject([
            'name'     => get_setting('website_name', config('app.name')) . ' SEO Suite',
            'base_url' => url('/'),
        ]) : $this->defaultProject();

        $features  = config('seo.features');
        $runs      = $setupRequired ? collect() : SeoRun::query()->latest()->limit(15)->get();
        $histories = $setupRequired ? collect() : SeoScoreHistory::query()->latest('recorded_at')->limit(12)->get();
        $redirects = $setupRequired ? collect() : SeoRedirect::query()->latest()->limit(10)->get();
        $dashboard = $setupRequired
            ? ['current_score' => 0, 'current_grade' => 'N/A', 'trend' => [], 'average_score' => 0, 'provider' => 'aggregate']
            : app(OptimizationService::class)->buildScoreDashboard(['project_id' => $project->id]);

        $settings = $this->loadSettings();

        return view('backend.seo_suite.index', compact(
            'project', 'features', 'runs', 'histories', 'redirects', 'dashboard', 'settings', 'setupRequired'
        ));
    }

    public function run(Request $request)
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first to enable the AI SEO Suite.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }

        $request->validate([
            'module'  => 'required|in:on_page,off_page,optimization',
            'feature' => 'required|string',
        ]);

        $project  = $this->defaultProject();
        $payload  = $this->buildPayload($request);
        $provider = $request->provider ?: get_setting('seo_suite_default_provider', config('seo.default_provider'));

        $run = SeoRun::create([
            'project_id'   => $project->id,
            'module'       => $request->module,
            'feature'      => $request->feature,
            'provider'     => $provider,
            'status'       => 'queued',
            'target_type'  => $request->input('target_type'),
            'target_id'    => $request->input('target_id'),
            'url'          => Arr::get($payload, 'url'),
            'input_payload'=> $payload,
        ]);

        if ($request->module === 'on_page') {
            GenerateOnPageSeoJob::dispatch($run->id);
        } elseif ($request->module === 'off_page') {
            GenerateSeoContentJob::dispatch($run->id);
        } else {
            GenerateOptimizationJob::dispatch($run->id);
        }

        flash(translate('SEO task queued successfully'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    // ── Sitemaps ──────────────────────────────────────────────────────────────

    public function generateSitemap()
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }
        app(OptimizationService::class)->generateSitemap(['persist' => true]);
        flash(translate('Sitemap generated successfully'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    public function generateSmartSitemap()
    {
        $result = app(SmartSitemapService::class)->handle(['persist' => true]);
        flash(translate('Smart XML sitemap generated successfully'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    public function generateVideoSitemap(Request $request)
    {
        $result = app(VideoSitemapService::class)->handle([
            'persist'  => true,
            'base_url' => url('/'),
        ]);
        flash(translate('Video sitemap generated: ' . $result['video_count'] . ' videos'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    public function generateNewsSitemap(Request $request)
    {
        $result = app(GoogleNewsSitemapService::class)->handle([
            'persist'          => true,
            'base_url'         => url('/'),
            'publication_name' => get_setting('website_name', config('app.name')),
        ]);
        flash(translate('Google News sitemap generated: ' . $result['article_count'] . ' articles'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    public function generateRss(Request $request)
    {
        $result = app(RssContentService::class)->handle([
            'persist'     => true,
            'feed_type'   => $request->input('feed_type', 'products'),
            'item_count'  => (int) $request->input('item_count', 20),
        ]);
        flash(translate('RSS feed generated: ' . $result['item_count'] . ' items'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    // ── Robots.txt ─────────────────────────────────────────────────────────────

    public function generateRobots()
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }
        app(OptimizationService::class)->optimizeRobotsTxt(['persist' => true]);
        flash(translate('Robots.txt generated successfully'))->success();
        return redirect()->route('admin.seo-suite.index');
    }

    // ── Redirects ──────────────────────────────────────────────────────────────

    public function storeRedirect(Request $request)
    {
        if (!$this->seoTablesReady()) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }

        $request->validate(['source_url' => 'required', 'target_url' => 'required']);

        SeoRedirect::updateOrCreate(
            ['source_url' => $request->source_url],
            [
                'target_url'  => $request->target_url,
                'status_code' => $request->input('status_code', 301),
                'is_active'   => true,
                'notes'       => $request->input('notes'),
            ]
        );

        flash(translate('Redirect saved successfully'))->success();
        return redirect()->back();
    }

    public function deleteRedirect(Request $request, $id)
    {
        try {
            SeoRedirect::findOrFail($id)->delete();
            flash(translate('Redirect deleted'))->success();
        } catch (\Throwable $e) {
            flash(translate('Redirect not found'))->error();
        }
        return redirect()->back();
    }

    // ── IndexNow ───────────────────────────────────────────────────────────────

    public function indexNow(Request $request)
    {
        $urls = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $request->input('urls', ''))));

        if (empty($urls)) {
            $urls = [url('/')];
        }

        $result = app(IndexNowService::class)->handle(['urls' => $urls]);

        if (isset($result['error'])) {
            flash($result['error'])->warning();
        } else {
            $successCount = count(array_filter($result['results'] ?? [], fn($r) => $r['success']));
            flash(translate("IndexNow: submitted {$result['submitted']} URLs, {$successCount} batches succeeded"))->success();
        }

        return redirect()->back();
    }

    public function generateIndexNowKey()
    {
        $key = app(IndexNowService::class)->generateKey();
        $this->saveSetting('seo_indexnow_key', $key);
        $this->overWriteEnvFile('SEO_INDEXNOW_KEY', $key);
        flash(translate('IndexNow key generated: ' . $key))->success();
        return redirect()->back();
    }

    // ── LLMs.txt ───────────────────────────────────────────────────────────────

    public function generateLlmsTxt(Request $request)
    {
        $result = app(LlmsTxtService::class)->handle([
            'persist'      => true,
            'site_name'    => get_setting('website_name', config('app.name')),
            'site_url'     => url('/'),
            'description'  => $request->input('description', ''),
            'custom_rules' => $request->input('custom_rules', ''),
        ]);

        flash(translate('LLMs.txt generated (' . $result['size'] . ' bytes)'))->success();
        return redirect()->back();
    }

    // ── AI Assistant ───────────────────────────────────────────────────────────

    public function aiAssistant()
    {
        $settings = $this->loadSettings();
        return view('backend.seo_suite.ai_assistant', compact('settings'));
    }

    public function aiChat(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $result = app(AiAssistantService::class)->chat([
            'message'  => $request->input('message'),
            'history'  => $request->input('history', []),
            'provider' => $request->input('provider', get_setting('seo_suite_default_provider', 'openai')),
            'context'  => $request->input('context', 'general'),
        ]);

        return response()->json($result);
    }

    public function aiQuickAction(Request $request)
    {
        $action = $request->input('action', 'audit_checklist');
        $result = app(AiAssistantService::class)->quickAction($action, $request->all());
        return response()->json($result);
    }

    // ── AI Writing Assistant ────────────────────────────────────────────────────

    public function aiWritingAssistant(Request $request)
    {
        $request->validate(['task' => 'required|string']);

        $result = app(AiWritingAssistantService::class)->handle([
            'task'         => $request->input('task'),
            'content'      => $request->input('content', ''),
            'keyword'      => $request->input('keyword', ''),
            'tone'         => $request->input('tone', 'professional'),
            'length'       => $request->input('length', 'medium'),
            'content_type' => $request->input('content_type', 'product_description'),
        ]);

        return response()->json($result);
    }

    // ── AI Image Generator ─────────────────────────────────────────────────────

    public function aiImageGenerator()
    {
        $settings = $this->loadSettings();
        return view('backend.seo_suite.ai_image_generator', compact('settings'));
    }

    public function generateAiImage(Request $request)
    {
        $request->validate(['keyword' => 'required|string|max:500']);

        $result = app(AiImageGeneratorService::class)->handle([
            'keyword'       => $request->input('keyword'),
            'style'         => $request->input('style', 'professional product photo'),
            'size'          => $request->input('size', '1024x1024'),
            'count'         => (int) $request->input('count', 1),
            'purpose'       => $request->input('purpose', 'product'),
            'custom_prompt' => $request->input('custom_prompt'),
            'save_local'    => (bool) $request->input('save_local', false),
        ]);

        return response()->json($result);
    }

    // ── Keyword Rank Tracker ────────────────────────────────────────────────────

    public function keywordTracker()
    {
        $settings  = $this->loadSettings();
        $histories = [];
        try {
            $histories = SeoScoreHistory::query()->latest('recorded_at')->limit(20)->get()->toArray();
        } catch (\Throwable $e) {}
        return view('backend.seo_suite.keyword_tracker', compact('settings', 'histories'));
    }

    public function checkKeywordRanks(Request $request)
    {
        $request->validate(['keywords' => 'required|string']);

        $result = app(KeywordRankTrackerService::class)->handle([
            'keywords' => $request->input('keywords'),
            'domain'   => parse_url(url('/'), PHP_URL_HOST),
            'engine'   => $request->input('engine', 'google'),
        ]);

        return response()->json($result);
    }

    // ── Search Statistics ───────────────────────────────────────────────────────

    public function searchStatistics()
    {
        $settings  = $this->loadSettings();
        $result    = app(SearchStatisticsService::class)->handle([
            'days' => 28,
        ]);
        return view('backend.seo_suite.search_statistics', compact('settings', 'result'));
    }

    // ── Post Index Status ───────────────────────────────────────────────────────

    public function postIndexStatus()
    {
        $settings = $this->loadSettings();
        return view('backend.seo_suite.post_index_status', compact('settings'));
    }

    public function checkIndexStatus(Request $request)
    {
        $result = app(PostIndexStatusService::class)->handle([
            'urls'   => $request->input('urls', ''),
            'domain' => parse_url(url('/'), PHP_URL_HOST),
        ]);
        return response()->json($result);
    }

    // ── Webmaster Tools ─────────────────────────────────────────────────────────

    public function webmasterTools()
    {
        $settings = $this->loadSettings();
        $result   = app(WebmasterToolsService::class)->handle([]);
        return view('backend.seo_suite.webmaster_tools', compact('settings', 'result'));
    }

    public function saveWebmasterTools(Request $request)
    {
        $pairs = [
            'seo_google_verification'    => $request->google_verification,
            'seo_bing_verification'      => $request->bing_verification,
            'seo_yandex_verification'    => $request->yandex_verification,
            'seo_pinterest_verification' => $request->pinterest_verification,
            'seo_baidu_verification'     => $request->baidu_verification,
        ];

        foreach ($pairs as $type => $value) {
            if ($value !== null) {
                // Strip full <meta> tag if user pasted it — save only the content value
                $value = trim($value);
                if (str_contains($value, 'content=')) {
                    if (preg_match('/content=["\']([^"\']+)["\']/', $value, $m)) {
                        $value = trim($m[1]);
                    }
                }
                $this->saveSetting($type, $value);
            }
        }

        flash(translate('Webmaster verification codes saved'))->success();
        return redirect()->route('admin.seo-suite.webmaster');
    }

    // ── SEO Revisions ───────────────────────────────────────────────────────────

    public function seoRevisions()
    {
        $settings  = $this->loadSettings();
        $histories = [];
        try {
            $histories = SeoScoreHistory::query()->latest('recorded_at')->limit(50)->get()->toArray();
        } catch (\Throwable $e) {}
        return view('backend.seo_suite.seo_revisions', compact('settings', 'histories'));
    }

    // ── Link Assistant ──────────────────────────────────────────────────────────

    public function linkAssistant(Request $request)
    {
        $settings = $this->loadSettings();
        $result   = null;

        if ($request->isMethod('post')) {
            $result = app(LinkAssistantService::class)->handle($request->all());
        }

        return view('backend.seo_suite.link_assistant', compact('settings', 'result'));
    }

    // ── Public endpoints ────────────────────────────────────────────────────────

    public function publicSitemap()
    {
        $path = base_path('sitemap.xml');
        if (!file_exists($path)) {
            app(OptimizationService::class)->generateSitemap(['persist' => true]);
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'application/xml');
    }

    public function publicRobots()
    {
        $path = public_path('robots.txt');
        if (!file_exists($path)) {
            app(OptimizationService::class)->optimizeRobotsTxt(['persist' => true]);
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'text/plain');
    }

    public function publicVideoSitemap()
    {
        $path = public_path('video-sitemap.xml');
        if (!file_exists($path)) {
            app(VideoSitemapService::class)->handle(['persist' => true, 'base_url' => url('/')]);
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'application/xml');
    }

    public function publicNewsSitemap()
    {
        $path = public_path('news-sitemap.xml');
        if (!file_exists($path)) {
            app(GoogleNewsSitemapService::class)->handle(['persist' => true, 'base_url' => url('/')]);
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'application/xml');
    }

    public function publicRss()
    {
        $path = public_path('rss.xml');
        if (!file_exists($path)) {
            app(RssContentService::class)->handle(['persist' => true]);
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'application/rss+xml');
    }

    public function publicLlmsTxt()
    {
        $path = public_path('llms.txt');
        if (!file_exists($path)) {
            app(LlmsTxtService::class)->handle(['persist' => true]);
        }
        if (!file_exists($path)) {
            return response('# ' . get_setting('website_name', config('app.name')), 200)->header('Content-Type', 'text/plain');
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'text/plain');
    }

    // ── Settings ────────────────────────────────────────────────────────────────

    public function settings()
    {
        $settings = $this->loadSettings();
        return view('backend.seo.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error) {
                logger()->error('SEO settings fatal error', $error);
            }
        });

        logger()->info('SEO settings save start', [
            'user_id'       => optional(auth()->user())->id,
            'openai_len'    => strlen((string) $request->openai_api_key),
            'anthropic_len' => strlen((string) $request->anthropic_api_key),
            'gemini_len'    => strlen((string) $request->gemini_api_key),
            'grok_len'      => strlen((string) $request->grok_api_key),
        ]);

        $pairs = [
            'seo_suite_default_provider'    => $request->default_provider,
            'seo_default_robots'            => $request->default_robots,
            'seo_local_business_name'       => $request->local_business_name,
            'seo_local_business_type'       => $request->local_business_type,
            'seo_search_console_site'       => $request->search_console_site,
            'seo_optimization_minify'       => $request->enable_minify,
            'seo_optimization_lazyload'     => $request->enable_lazyload,
            'seo_google_verification'       => $request->google_verification,
            'seo_bing_verification'         => $request->bing_verification,
            'seo_yandex_verification'       => $request->yandex_verification,
            'seo_pinterest_verification'    => $request->pinterest_verification,
            'seo_baidu_verification'        => $request->baidu_verification,
            'seo_indexnow_key'              => $request->indexnow_key,
            'seo_google_search_api_key'     => $request->google_search_api_key,
            'seo_google_search_cx'          => $request->google_search_cx,

            // GSC OAuth (refresh_token flow)
            'seo_gsc_client_id'             => $request->gsc_client_id,

            // SERP rank tracking
            'seo_rank_provider'             => $request->rank_provider,

            // Cloudflare
            'seo_cloudflare_zone_id'        => $request->cloudflare_zone_id,
            'seo_auto_cloudflare_purge'     => $request->boolean('auto_cloudflare_purge') ? 1 : 0,

            // Auto-actions + safety caps
            'seo_auto_indexnow'             => $request->boolean('auto_indexnow') ? 1 : 0,
            'seo_daily_budget_usd'          => $request->daily_budget_usd,
            'seo_ai_rate_per_min'           => $request->ai_rate_per_min,
        ];

        foreach ($pairs as $type => $value) {
            if ($value !== null) {
                $this->saveSetting($type, $value);
            }
        }

        $secretPairs = [
            'seo_openai_api_key'        => $request->openai_api_key,
            'seo_anthropic_api_key'     => $request->anthropic_api_key,
            'seo_gemini_api_key'        => $request->gemini_api_key,
            'seo_grok_api_key'          => $request->grok_api_key,
            'seo_gsc_client_secret'     => $request->gsc_client_secret,
            'seo_gsc_refresh_token'     => $request->gsc_refresh_token,
            'seo_serpapi_key'           => $request->serpapi_key,
            'seo_cloudflare_api_token'  => $request->cloudflare_api_token,
            'seo_pagespeed_api_key'     => $request->pagespeed_api_key,
        ];

        // Skip placeholder bullets (•••) — those mean "leave existing value alone".
        foreach ($secretPairs as $k => $v) {
            if (is_string($v) && str_starts_with(trim($v), '•')) {
                unset($secretPairs[$k]);
            }
        }

        foreach ($secretPairs as $type => $value) {
            if ($value !== null && $value !== '') {
                $this->saveSetting($type, preg_replace('/\s+/', '', $value));
            }
        }

        $envPairs = [
            'SEO_AI_PROVIDER'                 => $request->default_provider,
            'SEO_LOCAL_BUSINESS_NAME'         => $request->local_business_name,
            'SEO_LOCAL_BUSINESS_TYPE'         => $request->local_business_type,
            'SEO_GOOGLE_SEARCH_CONSOLE_SITE'  => $request->search_console_site,
            'OPENAI_API_KEY'                  => $request->openai_api_key,
            'ANTHROPIC_API_KEY'               => $request->anthropic_api_key,
            'GEMINI_API_KEY'                  => $request->gemini_api_key,
            'GROK_API_KEY'                    => $request->grok_api_key,
            'SEO_INDEXNOW_KEY'                => $request->indexnow_key,
        ];

        $this->overWriteEnvFileMany($envPairs);

        try {
            // Targeted invalidation: bust the BusinessSetting cache that get_setting() reads
            // from, plus any SEO-specific tagged caches. Do NOT call Cache::flush() — that
            // wipes product/cart/category caches used elsewhere on the site.
            Cache::forget('business_settings');
            app('seo.cache')->flushTags([
                \App\Services\Seo\SeoCacheManager::TAG_SETTINGS,
                \App\Services\Seo\SeoCacheManager::TAG_META,
                \App\Services\Seo\SeoCacheManager::TAG_REDIRECTS,
            ]);
            Cache::forget(\App\Http\Middleware\SeoRedirectMiddleware::CACHE_KEY);
            Cache::forget('seo:gsc:access_token');
        } catch (\Throwable $e) {
            logger()->warning('SEO suite settings cache clear failed', ['error' => $e->getMessage()]);
        }

        logger()->info('SEO settings save complete', ['user_id' => optional(auth()->user())->id]);
        flash(translate('SEO suite settings updated successfully'))->success();
        return redirect()->route('admin.seo-suite.settings.view');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    protected function loadSettings(): array
    {
        return [
            'default_provider'       => get_setting('seo_suite_default_provider', config('seo.default_provider')),
            'default_robots'         => get_setting('seo_default_robots', 'index, follow'),
            'local_business_name'    => get_setting('seo_local_business_name', get_setting('website_name')),
            'local_business_type'    => get_setting('seo_local_business_type', config('seo.local_business.type', 'Store')),
            'search_console_site'    => get_setting('seo_search_console_site', config('seo.search_console.site_url')),
            'enable_minify'          => get_setting('seo_optimization_minify', 0),
            'enable_lazyload'        => get_setting('seo_optimization_lazyload', 0),
            'google_verification'    => get_setting('seo_google_verification', config('seo.webmaster.google_verification')),
            'bing_verification'      => get_setting('seo_bing_verification', config('seo.webmaster.bing_verification')),
            'yandex_verification'    => get_setting('seo_yandex_verification', config('seo.webmaster.yandex_verification')),
            'pinterest_verification' => get_setting('seo_pinterest_verification', config('seo.webmaster.pinterest_verification')),
            'baidu_verification'     => get_setting('seo_baidu_verification', config('seo.webmaster.baidu_verification')),
            'indexnow_key'           => get_setting('seo_indexnow_key', config('seo.indexnow.key')),
            'google_search_api_key'  => get_setting('seo_google_search_api_key', env('GOOGLE_SEARCH_API_KEY')),
            'google_search_cx'       => get_setting('seo_google_search_cx', env('GOOGLE_SEARCH_CX')),
            'openai_api_key'         => env('OPENAI_API_KEY') ?? get_setting('seo_openai_api_key'),
            'anthropic_api_key'      => env('ANTHROPIC_API_KEY') ?? get_setting('seo_anthropic_api_key'),
            'gemini_api_key'         => env('GEMINI_API_KEY') ?? get_setting('seo_gemini_api_key'),
            'grok_api_key'           => env('GROK_API_KEY') ?? get_setting('seo_grok_api_key'),

            // GSC
            'gsc_client_id'          => get_setting('seo_gsc_client_id'),
            'gsc_client_secret'      => get_setting('seo_gsc_client_secret'),
            'gsc_refresh_token'      => get_setting('seo_gsc_refresh_token'),
            'gsc_connected_email'    => get_setting('seo_gsc_connected_email'),
            'gsc_expected_email'     => get_setting('seo_gsc_expected_email'),

            // SERP
            'rank_provider'          => get_setting('seo_rank_provider', 'serpapi'),
            'serpapi_key'            => get_setting('seo_serpapi_key'),

            // Cloudflare
            'cloudflare_api_token'   => get_setting('seo_cloudflare_api_token'),
            'cloudflare_zone_id'     => get_setting('seo_cloudflare_zone_id'),
            'auto_cloudflare_purge'  => (int) get_setting('seo_auto_cloudflare_purge', 0),

            // Auto-actions + safety
            'auto_indexnow'          => (int) get_setting('seo_auto_indexnow', 0),
            'daily_budget_usd'       => get_setting('seo_daily_budget_usd', 5),
            'ai_rate_per_min'        => get_setting('seo_ai_rate_per_min', 30),

            // PageSpeed Insights
            'pagespeed_api_key'      => get_setting('seo_pagespeed_api_key'),
        ];
    }

    protected function saveSetting(string $type, $value): void
    {
        $setting = BusinessSetting::where('type', $type)->first();
        if (!$setting) {
            $setting = new BusinessSetting();
            $setting->type = $type;
        }
        $setting->value = $value;
        $setting->save();
    }

    protected function buildPayload(Request $request): array
    {
        $payload = [
            'url'           => $request->input('url'),
            'title'         => $request->input('title'),
            'description'   => $request->input('description'),
            'content'       => $request->input('content'),
            'keyword'       => $request->input('keyword'),
            'topic'         => $request->input('topic'),
            'images'        => $this->splitTextList($request->input('images')),
            'links'         => $this->splitTextList($request->input('links')),
            'our_keywords'  => $request->input('our_keywords'),
            'comp1_keywords'=> $request->input('comp1_keywords'),
            'comp2_keywords'=> $request->input('comp2_keywords'),
            'faq'           => $this->faqPayload($request->input('faq')),
            'product_id'    => $request->input('product_id'),
            'cta'           => $request->input('cta'),
            'anchor_text'   => $request->input('anchor_text'),
            'link'          => $request->input('link'),
            'contact_name'  => $request->input('contact_name'),
            'target_site'   => $request->input('target_site'),
        ];

        if ($request->filled('extra_payload')) {
            $extra = json_decode($request->input('extra_payload'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($extra)) {
                $payload = array_replace_recursive($payload, $extra);
            }
        }

        return array_filter($payload, fn($v) => !($v === null || $v === '' || $v === []));
    }

    protected function splitTextList($value): array
    {
        if (!$value) return [];
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value))));
    }

    protected function faqPayload($value): array
    {
        if (!$value) return [];
        $items = [];
        foreach (preg_split('/[\r\n]+/', $value) as $line) {
            if (Str::contains($line, '|')) {
                [$question, $answer] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);
                if ($question && $answer) $items[] = compact('question', 'answer');
            }
        }
        return $items;
    }

    protected function defaultProject(): SeoProject
    {
        return SeoProject::firstOrCreate(
            ['slug' => 'default-seo-suite'],
            [
                'name'             => get_setting('website_name', config('app.name')) . ' SEO Suite',
                'base_url'         => url('/'),
                'default_provider' => get_setting('seo_suite_default_provider', config('seo.default_provider')),
                'created_by'       => auth()->id(),
            ]
        );
    }

    protected function seoTablesReady(): bool
    {
        return Schema::hasTable('seo_projects')
            && Schema::hasTable('seo_runs')
            && Schema::hasTable('seo_score_histories')
            && Schema::hasTable('seo_redirects');
    }

    protected function overWriteEnvFile(string $type, $val): void
    {
        $this->overWriteEnvFileMany([$type => $val]);
    }

    protected function overWriteEnvFileMany(array $pairs): void
    {
        if (env('DEMO_MODE') == 'On') return;

        $pairs = array_filter($pairs, fn($value) => $value !== null && $value !== '');
        if (empty($pairs)) return;

        $path = base_path('.env');
        if (!file_exists($path)) return;

        $contents = file_get_contents($path);
        $updated = $contents;

        foreach ($pairs as $type => $val) {
            $line = $type . '=' . $this->quoteEnvValue($val);
            $pattern = '/^' . preg_quote($type, '/') . '=.*$/m';

            if (preg_match($pattern, $updated)) {
                $updated = preg_replace_callback($pattern, fn() => $line, $updated);
            } else {
                $updated = rtrim($updated, "\r\n") . PHP_EOL . $line . PHP_EOL;
            }
        }

        if ($updated === $contents) return;

        file_put_contents($path, $updated, LOCK_EX);
    }

    protected function quoteEnvValue($value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], trim((string) $value)) . '"';
    }
}
