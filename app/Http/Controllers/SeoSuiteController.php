<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateOnPageSeoJob;
use App\Jobs\GenerateOptimizationJob;
use App\Jobs\GenerateSeoContentJob;
use App\Models\BusinessSetting;
use App\Models\SeoAnalytic;
use App\Models\SeoKeyword;
use App\Models\SeoMeta;
use App\Models\SeoProject;
use App\Models\SeoRedirect;
use App\Models\SeoFixBatch;
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
use App\Services\Seo\Board\AiSeoBoardService;
use App\Services\Seo\Budget\SeoBudgetGuard;
use App\Services\Seo\Automation\SeoAutomationCoverage;
use App\Services\Seo\Providers\SeoProviderManager;
use App\Services\Seo\Providers\SeoProviderReliability;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
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
        $board = $setupRequired ? null : app(AiSeoBoardService::class);
        $dashboard = $setupRequired
            ? ['current_score' => 0, 'current_grade' => 'N/A', 'trend' => [], 'average_score' => 0, 'provider' => 'aggregate']
            : app(OptimizationService::class)->buildScoreDashboard(['project_id' => $project->id]);
        $siteSummary = $setupRequired
            ? ['total' => 0, 'avg_score' => 0, 'critical' => 0, 'warning' => 0, 'good' => 0, 'done' => 0, 'pending' => 0]
            : $board->siteSummary();

        $settings = $this->loadSettings();
        $advancedDashboard = $this->buildAdvancedDashboard($runs, $histories, $redirects, $settings, $dashboard, $siteSummary);
        $urlInventory = $setupRequired
            ? ['done' => collect(), 'pending' => collect(), 'done_count' => 0, 'pending_count' => 0, 'total_count' => 0]
            : $board->dashboardUrlInventory(10, 12);
        $autopilot = $setupRequired
            ? $this->emptyAutopilotDashboard($settings)
            : $this->buildAutopilotDashboard($settings);
        $keywordIntelligence = $setupRequired ? $this->emptyKeywordIntelligence() : $this->buildKeywordIntelligence();
        $automationCoverage = app(SeoAutomationCoverage::class)->summary();

        return view('backend.seo_suite.index', compact(
            'project',
            'features',
            'runs',
            'histories',
            'redirects',
            'dashboard',
            'settings',
            'setupRequired',
            'advancedDashboard',
            'urlInventory',
            'autopilot',
            'keywordIntelligence',
            'automationCoverage'
        ));
    }

    public function bulkOptimizePendingUrls(Request $request)
    {
        if (!$this->seoTablesReady() || !Schema::hasTable('seo_meta')) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }

        $request->validate([
            'limit' => 'nullable|integer|min:5|max:10',
            'provider' => 'nullable|string',
        ]);

        $limit = (int) $request->input('limit', 10);
        $targets = app(AiSeoBoardService::class)->collectPendingTargetsAcrossTypes($limit, ['page', 'category', 'product']);

        if (empty($targets)) {
            flash(translate('No pending Product, Category, or Page URLs found for SEO generation.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }

        $board = app(AiSeoBoardService::class);
        $estimate = $board->estimateBatchCost($targets, $request->input('provider'));
        $guard = app(SeoBudgetGuard::class);

        if (!$guard->allowsAdditional($estimate['usd'])) {
            flash(translate('Daily SEO AI budget cap would be exceeded. Increase the cap in SEO settings or run fewer URLs.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }

        $runInCronChunks = config('queue.default') === 'sync';
        $batch = $board->createBatch(
            $targets,
            $request->input('provider'),
            optional(auth()->user())->id,
            'Dashboard Canada SEO auto-fix - ' . count($targets) . ' URLs',
            !$runInCronChunks
        );

        $guard->bustCache();

        $message = 'Advanced Canada SEO generation queued for ' . count($targets) . ' pending URLs. Batch #' . $batch->id;
        if ($runInCronChunks) {
            $message .= ' will run by cron in small chunks.';
        }
        flash(translate($message))->success();
        return redirect()->route('admin.seo.ai_board.index', ['missing' => 'meta']);
    }

    public function recoverSeoQueue(Request $request)
    {
        if (!$this->seoTablesReady() || !Schema::hasTable('seo_fix_batches')) {
            flash(translate('Run SEO migrations first.'))->warning();
            return redirect()->route('admin.seo-suite.index');
        }

        $request->validate([
            'mode' => 'required|in:compact,process_next,restart,cancel_batch',
            'batch_id' => 'nullable|integer|min:1',
        ]);

        $mode = (string) $request->input('mode');
        if ($mode === 'restart') {
            return $this->restartSeoQueue();
        }
        if ($mode === 'cancel_batch') {
            $batch = SeoFixBatch::find((int) $request->input('batch_id'));
            if (!$batch) {
                flash(translate('SEO batch not found.'))->warning();
            } else {
                $remaining = $batch->remainingCount();
                app(AiSeoBoardService::class)->cancelBatch($batch);
                flash(translate("Removed {$remaining} unfinished URLs from SEO batch #{$batch->id}. Completed SEO work remains saved."))->success();
            }

            return redirect()->to(route('admin.seo-suite.index') . '#seo-autopilot');
        }

        $arguments = $mode === 'compact'
            ? ['--compact-only' => true]
            : ['--limit' => 1, '--max-batches' => 1];

        try {
            $exitCode = Artisan::call('seo:process-ai-batches', $arguments);
            $output = trim(Artisan::output());

            logger()->info('Manual SEO queue recovery action', [
                'user_id' => optional(auth()->user())->id,
                'mode' => $mode,
                'exit_code' => $exitCode,
                'output' => Str::limit($output, 2000),
            ]);

            if ($exitCode !== 0) {
                flash(translate('Queue recovery needs attention. Check SEO Monitoring or the application log.'))->warning();
            } elseif ($mode === 'compact') {
                flash(translate('SEO queue duplicates compacted successfully.'))->success();
            } else {
                flash(translate('Processed the next pending SEO URL. Review the live batch impact below.'))->success();
            }
        } catch (\Throwable $e) {
            logger()->error('Manual SEO queue recovery failed', [
                'user_id' => optional(auth()->user())->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);
            flash(translate('Queue recovery could not finish. Check SEO Monitoring or the application log.'))->warning();
        }

        return redirect()->to(route('admin.seo-suite.index') . '#seo-autopilot');
    }

    protected function restartSeoQueue()
    {
        $activeBatches = SeoFixBatch::query()
            ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
            ->orderBy('id')
            ->get();
        $removedPending = $activeBatches->sum(fn(SeoFixBatch $batch) => $batch->remainingCount());
        $limit = min(
            AiSeoBoardService::MAX_AUTO_BATCH_TARGETS,
            max(1, (int) get_setting('seo_auto_seo_batch_size', 10))
        );

        try {
            $exitCode = Artisan::call('seo:restart-ai-queue');
            $output = trim(Artisan::output());

            logger()->info('SEO queue removed and restarted', [
                'user_id' => optional(auth()->user())->id,
                'cancelled_batches' => $activeBatches->pluck('id')->all(),
                'removed_pending_urls' => $removedPending,
                'new_batch_limit' => $limit,
                'exit_code' => $exitCode,
                'output' => Str::limit($output, 2000),
            ]);

            if ($exitCode !== 0) {
                flash(translate('Old SEO queue removed, but the fresh automated queue needs attention. Check SEO Monitoring.'))->warning();
            } else {
                flash(translate("Removed {$removedPending} unfinished queue URLs and started a fresh automated batch using the backend limit of {$limit}."))->success();
            }
        } catch (\Throwable $e) {
            logger()->error('SEO queue restart failed after cancellation', [
                'user_id' => optional(auth()->user())->id,
                'cancelled_batches' => $activeBatches->pluck('id')->all(),
                'removed_pending_urls' => $removedPending,
                'error' => $e->getMessage(),
            ]);
            flash(translate('Old SEO queue removed, but a fresh automated batch could not start. Check SEO Monitoring or wait for cron.'))->warning();
        }

        return redirect()->to(route('admin.seo-suite.index') . '#seo-autopilot');
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
        $history = $request->input('history', []);
        if (is_string($history)) {
            $history = json_decode($history, true);
        }
        if (!is_array($history)) {
            $history = [];
        }

        $result = app(AiAssistantService::class)->chat([
            'message'  => $request->input('message'),
            'history'  => $history,
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

    public function aiWriting()
    {
        $settings = $this->loadSettings();
        $modes    = AiWritingAssistantService::modes();

        return view('backend.seo_suite.ai_writing', compact('settings', 'modes'));
    }

    public function aiWritingAssistant(Request $request)
    {
        $request->validate(['task' => 'required|string']);

        $result = app(AiWritingAssistantService::class)->handle([
            'task'            => $request->input('task'),
            'content'         => $request->input('content', ''),
            'keyword'         => $request->input('keyword', ''),
            'tone'            => $request->input('tone', 'professional'),
            'length'          => $request->input('length', 'medium'),
            'content_type'    => $request->input('content_type', 'product_description'),
            'target_language' => $request->input('target_language', 'French'),
            'email_type'      => $request->input('email_type', 'newsletter'),
            'provider'        => $request->input('provider'),
        ]);

        return response()->json($result);
    }

    // ── AI Image Generator ─────────────────────────────────────────────────────

    public function aiImageGenerator()
    {
        $settings = $this->loadSettings();
        $styles   = AiImageGeneratorService::styles();

        $history = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('seo_generated_images')) {
            $history = \App\Models\SeoGeneratedImage::query()->latest()->limit(12)->get();
        }

        return view('backend.seo_suite.ai_image_generator', compact('settings', 'styles', 'history'));
    }

    public function generateAiImage(Request $request)
    {
        $request->validate(['keyword' => 'required|string|max:500']);

        $result = app(AiImageGeneratorService::class)->handle([
            'keyword'       => $request->input('keyword'),
            'style'         => $request->input('style', 'professional product photo'),
            'size'          => $request->input('size', '1024x1024'),
            'quality'       => $request->input('quality', 'standard'),
            'purpose'       => $request->input('purpose', 'product'),
            'custom_prompt' => $request->input('custom_prompt'),
            'save_local'    => (bool) $request->input('save_local', false),
        ]);

        return response()->json($result);
    }

    /**
     * Apply a generated image as the Open Graph / Twitter image for an entity.
     * Saves the image into the media library first (if not already) so the URL
     * is permanent, then writes it to seo_meta.
     */
    public function applyImageAsOg(Request $request)
    {
        $request->validate([
            'type'      => 'required|in:product,category,page,blog',
            'id'        => 'required|integer|min:1',
            'upload_id' => 'nullable|integer',
            'image_url' => 'nullable|string',
        ]);

        $class = [
            'product'  => \App\Models\Product::class,
            'category' => \App\Models\Category::class,
            'page'     => \App\Models\Page::class,
            'blog'     => \App\Models\Blog::class,
        ][$request->input('type')];

        $entity = $class::find((int) $request->input('id'));
        if (!$entity) {
            return response()->json(['success' => false, 'error' => 'Entity not found.'], 404);
        }

        // Prefer a permanent media-library URL over the (expiring) provider URL.
        $imageUrl = null;
        if ($request->filled('upload_id')) {
            $imageUrl = uploaded_asset((int) $request->input('upload_id'));
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->input('image_url');
            if (\Illuminate\Support\Str::contains($imageUrl, ['oaidalleapi', 'blob.core', 'openai'])) {
                // Provider URL expires — persist it into the media library.
                $media = app(AiImageGeneratorService::class)->saveToMediaLibrary($imageUrl);
                $imageUrl = $media['url'] ?? $imageUrl;
            }
        }

        if (!$imageUrl) {
            return response()->json(['success' => false, 'error' => 'No image provided.'], 422);
        }

        \App\Models\SeoMeta::updateOrCreate(
            ['model_type' => $class, 'model_id' => $entity->getKey(), 'lang' => config('app.locale', 'en')],
            ['og_image' => $imageUrl, 'twitter_image' => $imageUrl]
        );

        return response()->json(['success' => true, 'image_url' => $imageUrl]);
    }

    // ── Keyword Rank Tracker ────────────────────────────────────────────────────

    public function keywordTracker()
    {
        $settings  = $this->loadSettings();
        $histories = [];
        try {
            $histories = SeoScoreHistory::query()->latest('recorded_at')->limit(20)->get()->toArray();
        } catch (\Throwable $e) {}
        $keywordIntelligence = $this->buildKeywordIntelligence(250, 100);
        return view('backend.seo_suite.keyword_tracker', compact('settings', 'histories', 'keywordIntelligence'));
    }

    public function checkKeywordRanks(Request $request)
    {
        $request->validate(['keywords' => 'required|string']);

        $result = app(KeywordRankTrackerService::class)->handle([
            'keywords' => $request->input('keywords'),
            'domain'   => parse_url(url('/'), PHP_URL_HOST),
            'engine'   => $request->input('engine', 'google'),
            'country'  => 'ca',
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

        // Flush the 24-hour business_settings cache so get_setting() reads fresh DB values
        Cache::forget('business_settings');

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
        return $this->publicGeneratedFileResponse($path, 'application/xml');
    }

    public function publicRobots()
    {
        $path = public_path('robots.txt');
        if (!file_exists($path)) {
            app(OptimizationService::class)->optimizeRobotsTxt(['persist' => true]);
        }
        return $this->publicGeneratedFileResponse($path, 'text/plain');
    }

    public function publicVideoSitemap()
    {
        $path = public_path('video-sitemap.xml');
        if (!file_exists($path)) {
            app(VideoSitemapService::class)->handle(['persist' => true, 'base_url' => url('/')]);
        }
        return $this->publicGeneratedFileResponse($path, 'application/xml');
    }

    public function publicNewsSitemap()
    {
        $path = public_path('news-sitemap.xml');
        if (!file_exists($path)) {
            app(GoogleNewsSitemapService::class)->handle(['persist' => true, 'base_url' => url('/')]);
        }
        return $this->publicGeneratedFileResponse($path, 'application/xml');
    }

    public function publicRss()
    {
        $path = public_path('rss.xml');
        if (!file_exists($path)) {
            app(RssContentService::class)->handle(['persist' => true]);
        }
        return $this->publicGeneratedFileResponse($path, 'application/rss+xml');
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
        return $this->publicGeneratedFileResponse($path, 'text/plain');
    }

    protected function publicGeneratedFileResponse(string $path, string $contentType)
    {
        if (!is_file($path) || !is_readable($path)) {
            logger()->warning('SEO public artifact is unavailable', ['path' => $path]);
            return response('SEO artifact is temporarily unavailable.', 503)
                ->header('Content-Type', 'text/plain');
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            logger()->warning('SEO public artifact could not be read', ['path' => $path]);
            return response('SEO artifact is temporarily unavailable.', 503)
                ->header('Content-Type', 'text/plain');
        }

        return response($contents, 200)->header('Content-Type', $contentType);
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

        $failoverOrder = collect(preg_split('/[\s,]+/', (string) $request->input('ai_failover_order', '')))
            ->map(fn($provider) => SeoProviderManager::normalizeName($provider))
            ->filter()
            ->unique()
            ->implode(',');
        if ($failoverOrder === '') {
            $failoverOrder = implode(',', config('seo.provider_failover.order', ['claude', 'openai', 'gemini', 'grok']));
        }

        $pairs = [
            'seo_suite_default_provider'    => $request->default_provider,
            'seo_ai_failover_enabled'       => $request->boolean('ai_failover_enabled') ? 1 : 0,
            'seo_ai_failover_order'         => $failoverOrder,
            'seo_ai_failover_max_attempts'  => min(4, max(1, (int) $request->input('ai_failover_max_attempts', 4))),
            'seo_ai_provider_cooldown_enabled' => $request->boolean('ai_provider_cooldown_enabled') ? 1 : 0,
            'seo_ai_provider_failure_threshold' => min(10, max(1, (int) $request->input('ai_provider_failure_threshold', 3))),
            'seo_ai_provider_cooldown_minutes' => min(1440, max(1, (int) $request->input('ai_provider_cooldown_minutes', 15))),
            'seo_default_robots'            => $request->default_robots,
            'seo_local_business_name'       => $request->local_business_name,
            'seo_local_business_type'       => $request->local_business_type,
            'seo_search_console_site'       => $request->search_console_site,
            'seo_optimization_minify'       => $request->boolean('enable_minify') ? 1 : 0,
            'seo_optimization_lazyload'     => $request->boolean('enable_lazyload') ? 1 : 0,
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
            'seo_master_automation_enabled'  => $request->boolean('master_automation_enabled') ? 1 : 0,
            'seo_auto_indexnow'             => $request->boolean('auto_indexnow') ? 1 : 0,
            'seo_auto_index_coverage_enabled' => $request->boolean('auto_index_coverage_enabled') ? 1 : 0,
            'seo_auto_index_coverage_limit' => min(50, max(1, (int) $request->input('auto_index_coverage_limit', 20))),
            'seo_auto_index_coverage_interval_hours' => min(168, max(1, (int) $request->input('auto_index_coverage_interval_hours', 24))),
            'seo_auto_optimization_enabled' => $request->boolean('auto_optimization_enabled') ? 1 : 0,
            'seo_auto_seo_enabled'          => $request->boolean('auto_seo_enabled') ? 1 : 0,
            'seo_auto_seo_batch_size'       => min(AiSeoBoardService::MAX_AUTO_BATCH_TARGETS, max(1, (int) $request->input('auto_seo_batch_size', 10))),
            'seo_auto_offpage_enabled'      => $request->boolean('auto_offpage_enabled') ? 1 : 0,
            'seo_auto_offpage_batch_size'   => min(10, max(1, (int) $request->input('auto_offpage_batch_size', 3))),
            'seo_auto_offpage_interval_hours' => min(24, max(1, (int) $request->input('auto_offpage_interval_hours', 6))),

            // Automated sitemap regeneration
            'seo_auto_sitemap_realtime'       => $request->boolean('auto_sitemap_realtime') ? 1 : 0,
            'seo_auto_sitemap_interval_hours' => min(24, max(1, (int) $request->input('auto_sitemap_interval_hours', 3))),

            'seo_competitor_urls'           => $request->competitor_urls,
            'seo_target_keywords'           => $request->related_keywords,
            'seo_competitor_keywords'       => $request->competitor_keywords,
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

        // Skip masked placeholders. They mean "leave existing value alone".
        foreach ($secretPairs as $k => $v) {
            if ($this->isMaskedSecret($v)) {
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

    protected function buildKeywordIntelligence(int $trackedLimit = 20, int $gscLimit = 20): array
    {
        $groups = [
            'Primary locations' => ['Mississauga', 'Brampton', 'Toronto'],
            'Supporting locations' => ['Etobicoke', 'Vaughan', 'Oakville', 'Scarborough', 'Markham', 'North York', 'Burlington'],
            'Conversion intents' => ['Trade Account', 'Leave a Review'],
        ];
        $topics = ['HVAC supplies', 'Plumbing supplies', 'Electrical supplies', 'Hardware supplies', 'Contractor supplies', 'Wholesale supplies'];
        $locations = array_merge($groups['Primary locations'], $groups['Supporting locations']);
        $targetKeywords = collect($locations)
            ->flatMap(fn(string $location) => collect($topics)->map(fn(string $topic) => "{$topic} {$location}"))
            ->merge([
                'BHS Supplies Trade Account',
                'HVAC Trade Account',
                'Plumbing Trade Account',
                'Electrical Trade Account',
                'Hardware Trade Account',
                'Leave a Review BHS Supplies',
            ])
            ->unique()
            ->values();

        $trackedKeywords = collect();
        $trackedCount = 0;
        $rankedCount = 0;
        $pageOneCount = 0;

        if (Schema::hasTable('seo_keywords')) {
            // Real Google positions from Search Console (avg over last 28 days),
            // keyed by lowercased query — used as the source of truth when the
            // SerpAPI check is missing/rate-limited.
            $gscPositions = collect();
            if (Schema::hasTable('seo_analytics')) {
                $gscPositions = SeoAnalytic::query()
                    ->where('source', 'gsc')
                    ->where('dimension', 'query')
                    ->where('date', '>=', now()->subDays(28)->toDateString())
                    ->selectRaw('LOWER(value) as q, AVG(position) as pos')
                    ->groupBy('q')
                    ->pluck('pos', 'q');
            }

            $trackedQuery = SeoKeyword::query()->where('is_active', true);
            $trackedCount = (clone $trackedQuery)->count();
            $rankedCount = (clone $trackedQuery)->where('rank_current', '>', 0)->count();
            $pageOneCount = (clone $trackedQuery)->whereBetween('rank_current', [1, 10])->count();
            $trackedKeywords = $trackedQuery
                ->orderByRaw('rank_current IS NULL, rank_current = 0, rank_current ASC')
                ->limit($trackedLimit)
                ->get()
                ->map(function (SeoKeyword $keyword) use ($gscPositions): array {
                    $rank = (int) ($keyword->rank_current ?? 0);
                    $previous = (int) ($keyword->rank_previous ?? 0);
                    $lastStatus = (string) ($keyword->last_status ?? '');
                    $source = $keyword->engine;

                    // Prefer the real GSC position when SerpAPI has no usable rank.
                    $gscPos = (int) round((float) ($gscPositions[mb_strtolower($keyword->keyword)] ?? 0));
                    if ($rank <= 0 && $gscPos > 0) {
                        $rank = $gscPos;
                        $source = 'gsc';
                    }

                    $checkErrored = $rank <= 0 && str_starts_with($lastStatus, 'error');
                    $pageLabel = $rank > 0
                        ? 'Page ' . (int) ceil($rank / 10) . ($source === 'gsc' ? ' (GSC)' : '')
                        : ($checkErrored
                            ? (stripos($lastStatus, '429') !== false ? 'Rate limited — not checked' : 'Check failed — not recorded')
                            : 'Not found in top 100');

                    return [
                        'keyword' => $keyword->keyword,
                        'rank' => $rank,
                        'previous_rank' => $previous ?: null,
                        'movement' => $rank > 0 && $previous > 0 ? $previous - $rank : null,
                        'google_page' => $rank > 0 ? (int) ceil($rank / 10) : null,
                        'google_page_label' => $pageLabel,
                        'url' => $keyword->target_url,
                        'country' => strtoupper((string) $keyword->country),
                        'engine' => $source,
                        'checked_at' => $keyword->last_checked_at,
                    ];
                })
                ->values();
        }

        $gscKeywordPages = collect();
        $gscKeywordPageCount = 0;
        if (Schema::hasTable('seo_analytics')) {
            $gscKeywordPages = SeoAnalytic::query()
                ->where('source', 'gsc')
                ->where('dimension', 'query_page')
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->get()
                ->map(function (SeoAnalytic $row): ?array {
                    $pair = json_decode((string) $row->value, true);
                    if (empty($pair['query']) || empty($pair['page'])) {
                        return null;
                    }

                    return [
                        'query' => $pair['query'],
                        'page' => $pair['page'],
                        'clicks' => (int) $row->clicks,
                        'impressions' => (int) $row->impressions,
                        'ctr' => (float) $row->ctr,
                        'position' => (float) $row->position,
                    ];
                })
                ->filter()
                ->groupBy(fn(array $row) => $row['query'] . '|' . $row['page'])
                ->map(function ($rows): array {
                    $impressions = $rows->sum('impressions');
                    $positionWeight = $rows->sum(fn(array $row) => max(1, $row['impressions']));
                    $position = round($rows->sum(fn(array $row) => $row['position'] * max(1, $row['impressions'])) / $positionWeight, 1);
                    $clicks = $rows->sum('clicks');

                    return [
                        'query' => $rows->first()['query'],
                        'page' => $rows->first()['page'],
                        'clicks' => $clicks,
                        'impressions' => $impressions,
                        'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                        'position' => $position,
                        'google_page' => $position > 0 ? (int) ceil($position / 10) : null,
                    ];
                })
                ->sortByDesc('impressions')
                ->values();
            $gscKeywordPageCount = $gscKeywordPages->count();
            $gscKeywordPages = $gscKeywordPages->take($gscLimit)->values();
        }

        return [
            'groups' => $groups,
            'target_keywords' => $targetKeywords,
            'target_keyword_count' => $targetKeywords->count(),
            'tracked_keywords' => $trackedKeywords,
            'tracked_count' => $trackedCount,
            'ranked_count' => $rankedCount,
            'page_one_count' => $pageOneCount,
            'gsc_keyword_pages' => $gscKeywordPages,
            'gsc_keyword_page_count' => $gscKeywordPageCount,
            'generated_focus_keyword_count' => Schema::hasTable('seo_meta')
                ? SeoMeta::query()->whereNotNull('focus_keyword')->distinct()->count('focus_keyword')
                : 0,
        ];
    }

    protected function emptyKeywordIntelligence(): array
    {
        return [
            'groups' => [],
            'target_keywords' => collect(),
            'target_keyword_count' => 0,
            'tracked_keywords' => collect(),
            'tracked_count' => 0,
            'ranked_count' => 0,
            'page_one_count' => 0,
            'gsc_keyword_pages' => collect(),
            'gsc_keyword_page_count' => 0,
            'generated_focus_keyword_count' => 0,
        ];
    }

    protected function loadSettings(): array
    {
        return [
            'default_provider'       => get_setting('seo_suite_default_provider', config('seo.default_provider')),
            'ai_failover_enabled'    => (int) get_setting('seo_ai_failover_enabled', config('seo.provider_failover.enabled', true) ? 1 : 0),
            'ai_failover_order'      => get_setting('seo_ai_failover_order', implode(',', config('seo.provider_failover.order', ['claude', 'openai', 'gemini', 'grok']))),
            'ai_failover_max_attempts' => (int) get_setting('seo_ai_failover_max_attempts', config('seo.provider_failover.max_attempts', 4)),
            'ai_provider_cooldown_enabled' => (int) get_setting('seo_ai_provider_cooldown_enabled', config('seo.provider_failover.cooldown_enabled', true) ? 1 : 0),
            'ai_provider_failure_threshold' => (int) get_setting('seo_ai_provider_failure_threshold', config('seo.provider_failover.failure_threshold', 3)),
            'ai_provider_cooldown_minutes' => (int) get_setting('seo_ai_provider_cooldown_minutes', config('seo.provider_failover.cooldown_minutes', 15)),
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
            'master_automation_enabled' => (int) get_setting('seo_master_automation_enabled', 1),
            'auto_indexnow'          => (int) get_setting('seo_auto_indexnow', 0),
            'auto_index_coverage_enabled' => (int) get_setting('seo_auto_index_coverage_enabled', 1),
            'auto_index_coverage_limit' => (int) get_setting('seo_auto_index_coverage_limit', 20),
            'auto_index_coverage_interval_hours' => (int) get_setting('seo_auto_index_coverage_interval_hours', 24),
            'auto_optimization_enabled' => (int) get_setting('seo_auto_optimization_enabled', 1),
            'auto_seo_enabled'       => (int) get_setting('seo_auto_seo_enabled', 1),
            'auto_seo_batch_size'    => min(AiSeoBoardService::MAX_AUTO_BATCH_TARGETS, max(1, (int) get_setting('seo_auto_seo_batch_size', 10))),
            'auto_offpage_enabled'   => (int) get_setting('seo_auto_offpage_enabled', 1),
            'auto_offpage_batch_size'=> (int) get_setting('seo_auto_offpage_batch_size', 3),
            'auto_offpage_interval_hours' => (int) get_setting('seo_auto_offpage_interval_hours', 6),
            'auto_sitemap_realtime'  => (int) get_setting('seo_auto_sitemap_realtime', 0),
            'auto_sitemap_interval_hours' => (int) get_setting('seo_auto_sitemap_interval_hours', 3),
            'competitor_urls'        => get_setting('seo_competitor_urls', get_setting('ai_blog_competitor_urls', '')),
            'related_keywords'       => get_setting('seo_target_keywords', ''),
            'competitor_keywords'    => get_setting('seo_competitor_keywords', ''),
            'daily_budget_usd'       => get_setting('seo_daily_budget_usd', 5),
            'ai_rate_per_min'        => get_setting('seo_ai_rate_per_min', 30),

            // PageSpeed Insights
            'pagespeed_api_key'      => get_setting('seo_pagespeed_api_key'),
        ];
    }

    protected function buildAdvancedDashboard($runs, $histories, $redirects, array $settings, array $dashboard, array $siteSummary = []): array
    {
        $runs = collect($runs);
        $histories = collect($histories);
        $redirects = collect($redirects);

        $totalRuns = $runs->count();
        $completedRuns = $runs->where('status', 'completed')->count();
        $failedRuns = $runs->where('status', 'failed')->count();
        $queuedRuns = $runs->whereIn('status', ['queued', 'running', 'processing'])->count();
        $successRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100) : 0;

        $durations = $runs->filter(function ($run) {
            return $run->started_at && $run->completed_at;
        })->map(function ($run) {
            return $run->completed_at->diffInSeconds($run->started_at);
        });
        $avgDuration = $durations->count() ? round($durations->avg()) : null;

        $latestScore = (int) ($dashboard['current_score'] ?? 0);
        $oldestHistory = $histories->last();
        $newestHistory = $histories->first();
        $trendDelta = ($newestHistory && $oldestHistory)
            ? ((int) $newestHistory->score - (int) $oldestHistory->score)
            : 0;

        $providers = [
            'openai' => !empty($settings['openai_api_key']),
            'claude' => !empty($settings['anthropic_api_key']),
            'gemini' => !empty($settings['gemini_api_key']),
            'grok' => !empty($settings['grok_api_key']),
        ];
        $providerReliability = app(SeoProviderReliability::class)->dashboard();

        $files = [
            'sitemap' => $this->fileHealth('Sitemap', base_path('sitemap.xml'), 'la-sitemap', 'admin.seo-suite.sitemap'),
            'robots' => $this->fileHealth('Robots.txt', public_path('robots.txt'), 'la-robot', 'admin.seo-suite.robots'),
            'llms' => $this->fileHealth('LLMs.txt', public_path('llms.txt'), 'la-file-code', 'admin.seo-suite.llms_txt'),
            'rss' => $this->fileHealth('RSS Feed', public_path('rss.xml'), 'la-rss', 'admin.seo-suite.rss'),
        ];

        $actions = [];
        if (!in_array(true, $providers, true)) {
            $actions[] = [
                'severity' => 'critical',
                'icon' => 'la-key',
                'title' => 'Connect an AI provider',
                'detail' => 'Add at least one API key so audits, rewrites, schemas, and recommendations can run.',
                'route' => 'admin.seo-suite.settings.view',
            ];
        }
        if (empty($settings['search_console_site']) || empty($settings['gsc_refresh_token'])) {
            $actions[] = [
                'severity' => 'high',
                'icon' => 'la-chart-line',
                'title' => 'Connect Google Search Console',
                'detail' => 'Unlock clicks, impressions, coverage signals, and query intelligence inside the suite.',
                'route' => 'admin.seo-suite.settings.view',
            ];
        }
        foreach ($files as $file) {
            if (!$file['exists']) {
                $actions[] = [
                    'severity' => 'high',
                    'icon' => $file['icon'],
                    'title' => 'Generate ' . $file['label'],
                    'detail' => 'This file is missing and should be published for search engines and AI crawlers.',
                    'route' => $file['route'],
                    'method' => 'post',
                ];
            } elseif ($file['age_days'] !== null && $file['age_days'] > 14) {
                $actions[] = [
                    'severity' => 'medium',
                    'icon' => $file['icon'],
                    'title' => 'Refresh ' . $file['label'],
                    'detail' => 'Last generated ' . $file['age_days'] . ' days ago. Regenerate after product, category, or blog changes.',
                    'route' => $file['route'],
                    'method' => 'post',
                ];
            }
        }
        if ($latestScore > 0 && $latestScore < 80) {
            $actions[] = [
                'severity' => 'high',
                'icon' => 'la-clipboard-check',
                'title' => 'Run a complete optimization audit',
                'detail' => 'Current score is below 80. Prioritize technical, schema, speed, and content improvements.',
                'route' => 'admin.seo_optimization.index',
            ];
        }
        if ($failedRuns > 0) {
            $actions[] = [
                'severity' => 'medium',
                'icon' => 'la-exclamation-circle',
                'title' => 'Review failed AI runs',
                'detail' => $failedRuns . ' recent task(s) failed. Check provider keys, payloads, limits, and logs.',
                'route' => 'admin.seo-suite.revisions',
            ];
        }
        if ($redirects->count() === 0) {
            $actions[] = [
                'severity' => 'low',
                'icon' => 'la-exchange-alt',
                'title' => 'Audit legacy URL redirects',
                'detail' => 'Add 301 redirects for old product, category, and campaign URLs to preserve authority.',
                'route' => 'admin.seo-suite.index',
                'params' => ['tab' => 'redirects'],
            ];
        }

        $actions = collect($actions)->sortBy(function ($action) {
            return ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3][$action['severity']] ?? 4;
        })->take(6)->values()->all();

        $readinessParts = [
            in_array(true, $providers, true) ? 20 : 0,
            !empty($settings['google_verification']) || !empty($settings['bing_verification']) ? 15 : 0,
            !empty($settings['gsc_refresh_token']) ? 20 : 0,
            $files['sitemap']['exists'] ? 15 : 0,
            $files['robots']['exists'] ? 10 : 0,
            $files['llms']['exists'] ? 10 : 0,
            $successRate >= 80 || $totalRuns === 0 ? 10 : 5,
        ];

        $automationReadiness = array_sum($readinessParts);
        $automationRisk = match (true) {
            !in_array(true, $providers, true) => 'high',
            $totalRuns > 0 && $successRate < 70 => 'high',
            $failedRuns >= 3 => 'high',
            $automationReadiness < 80 => 'medium',
            $totalRuns > 0 && $successRate < 90 => 'medium',
            $queuedRuns > 0 => 'medium',
            default => 'low',
        };
        $entityScore = (int) ($siteSummary['avg_score'] ?? 0);
        $siteScore = $entityScore > 0 ? $entityScore : $latestScore;
        $siteTotal = (int) ($siteSummary['total'] ?? 0);
        $siteDone = (int) ($siteSummary['done'] ?? 0);
        $sitePending = (int) ($siteSummary['pending'] ?? max(0, $siteTotal - $siteDone));
        $siteCritical = (int) ($siteSummary['critical'] ?? 0);
        $siteWarning = (int) ($siteSummary['warning'] ?? 0);
        $siteGood = (int) ($siteSummary['good'] ?? 0);
        $siteCompletion = $siteTotal > 0 ? (int) round(($siteDone / $siteTotal) * 100) : 0;
        $siteRisk = match (true) {
            $siteTotal === 0 => 'medium',
            $siteDone >= $siteTotal && $siteScore >= AiSeoBoardService::SEO_DONE_SCORE => 'low',
            $siteScore >= AiSeoBoardService::SEO_DONE_SCORE && $siteCritical === 0 => 'low',
            $siteScore >= 50 || $siteCompletion >= 60 => 'medium',
            default => 'high',
        };
        $siteReason = match ($siteRisk) {
            'low' => 'Site SEO is healthy: average score ' . $siteScore . '/100 with ' . $siteDone . ' SEO-done URLs.',
            'medium' => 'Site SEO is improving: average score ' . $siteScore . '/100, ' . $sitePending . ' pending URLs, ' . $siteCritical . ' critical URLs.',
            default => 'Site SEO risk is high because average score is ' . $siteScore . '/100 with ' . $sitePending . ' pending URLs and ' . $siteCritical . ' critical URLs.',
        };

        return [
            'automation_readiness' => $automationReadiness,
            'success_rate' => $successRate,
            'failed_runs' => $failedRuns,
            'queued_runs' => $queuedRuns,
            'avg_duration' => $avgDuration,
            'trend_delta' => $trendDelta,
            'providers' => $providers,
            'provider_reliability' => $providerReliability,
            'files' => $files,
            'actions' => $actions,
            'risk_level' => $automationRisk,
            'automation_risk_level' => $automationRisk,
            'site_risk_level' => $siteRisk,
            'site_health' => [
                'score' => $siteScore,
                'history_score' => $latestScore,
                'total' => $siteTotal,
                'done' => $siteDone,
                'pending' => $sitePending,
                'critical' => $siteCritical,
                'warning' => $siteWarning,
                'good' => $siteGood,
                'completion_rate' => $siteCompletion,
                'risk' => $siteRisk,
                'reason' => $siteReason,
            ],
        ];
    }

    protected function buildAutopilotDashboard(array $settings): array
    {
        $board = app(AiSeoBoardService::class);
        $budget = app(SeoBudgetGuard::class);
        $breakdown = $board->pendingBreakdownByType(['page', 'category', 'product']);
        $batchSize = min(AiSeoBoardService::MAX_AUTO_BATCH_TARGETS, max(1, (int) ($settings['auto_seo_batch_size'] ?? 10)));
        $pendingTotal = collect($breakdown)->sum('pending');
        $nextPreview = $board->nextAutopilotTargetPreview(max(10, $batchSize), ['page', 'category', 'product']);
        $nextTargets = $nextPreview
            ->take($batchSize)
            ->map(fn(array $row) => ['type' => $row['type'], 'id' => (int) $row['id']])
            ->values()
            ->all();
        $nextEstimate = $board->estimateBatchCost($nextTargets, $settings['default_provider'] ?? null);
        $offpageTargets = $board->offPageCampaignTargetPreview(10, ['page', 'category', 'product']);
        $recentScoreActivity = $this->recentSeoScoreActivity(60, 300);
        $recentScoreChanges = $recentScoreActivity
            ->filter(fn(array $row) => ((int) ($row['delta'] ?? 0) > 0) || !empty($row['seo_done']))
            ->take(12)
            ->values();

        $activeBatches = Schema::hasTable('seo_fix_batches')
            ? SeoFixBatch::query()
                ->whereIn('status', [SeoFixBatch::STATUS_QUEUED, SeoFixBatch::STATUS_RUNNING])
                ->orderBy('id')
                ->get()
            : collect();
        $activeBatch = $activeBatches->first();
        $activeBacklog = $activeBatches->sum(fn(SeoFixBatch $batch) => $batch->remainingCount());
        $stalledBatches = $activeBatches->filter(fn(SeoFixBatch $batch) => $batch->isStalled());
        $queueDrainMinutes = $activeBacklog > 0 ? (int) ceil($activeBacklog / $batchSize) * 5 : 0;
        $freshQueueHours = $batchSize > 0 ? (int) ceil(max(0, $pendingTotal - $activeBacklog) / $batchSize) : null;
        $queueDrainHours = (int) ceil($queueDrainMinutes / 60);

        $recentBatches = Schema::hasTable('seo_fix_batches')
            ? SeoFixBatch::query()->latest()->limit(5)->get()
            : collect();

        return [
            'enabled' => (int) ($settings['auto_seo_enabled'] ?? 1) === 1,
            'batch_size' => $batchSize,
            'next_run' => 'Hourly via cron',
            'queue_driver' => config('queue.default'),
            'active_batch' => $activeBatch,
            'active_batches' => $activeBatches,
            'active_batch_count' => $activeBatches->count(),
            'active_backlog_urls' => $activeBacklog,
            'retry_attempt_limit' => AiSeoBoardService::MAX_AUTOPILOT_ATTEMPTS,
            'stalled_batches' => $stalledBatches,
            'stalled_batch_count' => $stalledBatches->count(),
            'queue_drain_minutes' => $queueDrainMinutes,
            'recent_batches' => $recentBatches,
            'breakdown' => $breakdown,
            'next_targets' => $nextPreview->take(10)->values(),
            'offpage_targets' => $offpageTargets,
            'offpage_ready_count' => $offpageTargets->count(),
            'recent_score_changes' => $recentScoreChanges,
            'score_improved_last_hour' => $recentScoreChanges->where('delta', '>', 0)->count(),
            'score_done_last_hour' => $recentScoreChanges->where('seo_done', true)->count(),
            'score_rescored_no_change_last_hour' => $recentScoreActivity
                ->filter(fn(array $row) => (int) ($row['delta'] ?? 0) === 0 && empty($row['seo_done']))
                ->count(),
            'pending_total' => $pendingTotal,
            'days_to_completion' => !is_null($freshQueueHours) ? (int) ceil(max($queueDrainHours, $freshQueueHours) / 24) : null,
            'next_run_count' => count($nextTargets),
            'next_run_estimated_cost' => $nextEstimate['usd'] ?? 0,
            'next_run_ai_call' => $nextEstimate['ai_call'] ?? false,
            'budget_cap' => $budget->dailyCapUsd(),
            'spent_today' => round($budget->spendToday(), 4),
            'remaining_today' => $budget->dailyCapUsd() > 0 ? round($budget->remainingUsd(), 4) : null,
        ];
    }

    protected function emptyAutopilotDashboard(array $settings): array
    {
        return [
            'enabled' => (int) ($settings['auto_seo_enabled'] ?? 1) === 1,
            'batch_size' => min(AiSeoBoardService::MAX_AUTO_BATCH_TARGETS, max(1, (int) ($settings['auto_seo_batch_size'] ?? 10))),
            'next_run' => 'Hourly via cron',
            'queue_driver' => config('queue.default'),
            'active_batch' => null,
            'active_batches' => collect(),
            'active_batch_count' => 0,
            'active_backlog_urls' => 0,
            'retry_attempt_limit' => AiSeoBoardService::MAX_AUTOPILOT_ATTEMPTS,
            'stalled_batches' => collect(),
            'stalled_batch_count' => 0,
            'queue_drain_minutes' => 0,
            'recent_batches' => collect(),
            'breakdown' => [],
            'next_targets' => collect(),
            'offpage_targets' => collect(),
            'offpage_ready_count' => 0,
            'recent_score_changes' => collect(),
            'score_improved_last_hour' => 0,
            'score_done_last_hour' => 0,
            'score_rescored_no_change_last_hour' => 0,
            'pending_total' => 0,
            'days_to_completion' => null,
            'next_run_count' => 0,
            'next_run_estimated_cost' => 0,
            'next_run_ai_call' => false,
            'budget_cap' => 0,
            'spent_today' => 0,
            'remaining_today' => null,
        ];
    }

    protected function recentSeoScoreActivity(int $minutes = 60, int $limit = 300): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('seo_score_histories')) {
            return collect();
        }

        $recent = SeoScoreHistory::query()
            ->where('recorded_at', '>=', now()->subMinutes($minutes))
            ->whereNotNull('target_type')
            ->whereNotNull('target_id')
            ->latest('recorded_at')
            ->limit(300)
            ->get();

        $seen = [];
        $rows = collect();

        foreach ($recent as $history) {
            $key = $history->target_type . '#' . $history->target_id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $previous = SeoScoreHistory::query()
                ->where('target_type', $history->target_type)
                ->where('target_id', $history->target_id)
                ->where('id', '<>', $history->id)
                ->where(function ($query) use ($history) {
                    if ($history->recorded_at) {
                        $query->whereNull('recorded_at')
                            ->orWhere('recorded_at', '<', $history->recorded_at);
                    }
                })
                ->latest('recorded_at')
                ->latest('id')
                ->first();

            $before = $previous ? (int) $previous->score : null;
            $after = (int) $history->score;
            $class = (string) $history->target_type;
            $entity = class_exists($class) ? $class::find($history->target_id) : null;
            $titleColumn = in_array(class_basename($class), ['Page', 'Blog'], true) ? 'title' : 'name';

            $rows->push([
                'url' => $history->url,
                'title' => $entity ? (string) ($entity->{$titleColumn} ?? $history->url) : ($history->url ?: $key),
                'type' => class_exists($class) ? Str::lower(class_basename($class)) : (string) $history->target_type,
                'score_before' => $before,
                'score_after' => $after,
                'delta' => $before === null ? null : $after - $before,
                'seo_done' => $after >= AiSeoBoardService::SEO_DONE_SCORE,
                'recorded_at' => $history->recorded_at,
            ]);

            if ($rows->count() >= $limit) {
                break;
            }
        }

        return $rows;
    }

    protected function fileHealth(string $label, string $path, string $icon, string $route): array
    {
        $exists = file_exists($path);
        $updatedAt = $exists ? filemtime($path) : null;

        return [
            'label' => $label,
            'icon' => $icon,
            'route' => $route,
            'exists' => $exists,
            'size' => $exists ? filesize($path) : 0,
            'updated_at' => $updatedAt ? date('M d, Y H:i', $updatedAt) : null,
            'age_days' => $updatedAt ? now()->diffInDays(\Carbon\Carbon::createFromTimestamp($updatedAt)) : null,
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

        $pairs = array_filter($pairs, fn($value) => $value !== null && $value !== '' && !$this->isMaskedSecret($value));
        if (empty($pairs)) return;

        $path = base_path('.env');
        if (!file_exists($path)) return;
        if (!is_readable($path) || !is_writable($path)) {
            logger()->warning('SEO settings .env update skipped: file is not readable/writable', ['path' => $path]);
            return;
        }

        try {
            $contents = file_get_contents($path);
            if ($contents === false) {
                logger()->warning('SEO settings .env update skipped: could not read .env', ['path' => $path]);
                return;
            }

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

            if (file_put_contents($path, $updated, LOCK_EX) === false) {
                logger()->warning('SEO settings .env update skipped: write failed', ['path' => $path]);
            }
        } catch (\Throwable $e) {
            logger()->warning('SEO settings .env update skipped', ['error' => $e->getMessage()]);
        }
    }

    protected function quoteEnvValue($value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], trim((string) $value)) . '"';
    }

    protected function isMaskedSecret($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return Str::startsWith($value, ['•', '●', '••', 'â€¢', '***']);
    }
}
