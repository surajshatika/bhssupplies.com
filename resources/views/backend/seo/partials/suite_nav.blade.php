@php
    $settings = $settings ?? [];
    $seoSuiteNavItems = [
        ['route' => 'admin.seo-suite.index', 'icon' => 'la-tachometer-alt', 'label' => 'Suite'],
        ['route' => 'admin.seo_on_page.index', 'icon' => 'la-file-alt', 'label' => 'On-Page'],
        ['route' => 'admin.seo_off_page.index', 'icon' => 'la-bullhorn', 'label' => 'Off-Page'],
        ['route' => 'admin.seo_optimization.index', 'icon' => 'la-cogs', 'label' => 'Optimization'],
        ['route' => 'admin.seo.ai_board.index', 'icon' => 'la-brain', 'label' => 'AI Board'],
        ['route' => 'admin.seo.monitoring.index', 'icon' => 'la-heartbeat', 'label' => 'Monitoring'],
        ['route' => 'admin.seo-suite.ai_assistant', 'icon' => 'la-robot', 'label' => 'Assistant'],
        ['route' => 'admin.seo-suite.ai_writing_page', 'icon' => 'la-pen-nib', 'label' => 'Writer'],
        ['route' => 'admin.seo-suite.keyword_tracker', 'icon' => 'la-chart-line', 'label' => 'Keywords'],
        ['route' => 'admin.seo-suite.search_stats', 'icon' => 'la-chart-bar', 'label' => 'Stats'],
        ['route' => 'admin.seo-suite.webmaster', 'icon' => 'la-tools', 'label' => 'Webmaster'],
        ['route' => 'admin.seo-suite.link_assistant', 'icon' => 'la-link', 'label' => 'Links'],
        ['route' => 'admin.seo-suite.revisions', 'icon' => 'la-history', 'label' => 'Revisions'],
        ['route' => 'admin.seo-suite.settings.view', 'icon' => 'la-sliders-h', 'label' => 'Settings'],
    ];
    $configuredProviders = collect([
        'openai' => $settings['openai_api_key'] ?? env('OPENAI_API_KEY') ?? get_setting('seo_openai_api_key'),
        'claude' => $settings['anthropic_api_key'] ?? env('ANTHROPIC_API_KEY') ?? get_setting('seo_anthropic_api_key'),
        'gemini' => $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') ?? get_setting('seo_gemini_api_key'),
        'grok' => $settings['grok_api_key'] ?? env('GROK_API_KEY') ?? get_setting('seo_grok_api_key'),
    ])->filter()->count();
    $seoAutopilotEnabled = (int) get_setting('seo_auto_seo_enabled', 1) === 1;
    $seoAutopilotPending = null;
    $seoActiveBatch = null;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('seo_meta')) {
            $seoBreakdown = app(\App\Services\Seo\Board\AiSeoBoardService::class)->pendingBreakdownByType(['product', 'category', 'page']);
            $seoAutopilotPending = collect($seoBreakdown)->sum('pending');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('seo_fix_batches')) {
            $seoActiveBatch = \App\Models\SeoFixBatch::query()
                ->whereIn('status', [\App\Models\SeoFixBatch::STATUS_QUEUED, \App\Models\SeoFixBatch::STATUS_RUNNING])
                ->latest()
                ->first();
        }
    } catch (\Throwable $e) {
        $seoAutopilotPending = null;
        $seoActiveBatch = null;
    }
@endphp

<style>
.seo-suite-strip { border: 1px solid #edf0f5; border-radius: 8px; background: #fff; }
.seo-suite-strip .nav-scroll { overflow-x: auto; white-space: nowrap; }
.seo-suite-strip .seo-nav-link { display: inline-flex; align-items: center; padding: 9px 11px; border-radius: 6px; color: #4a5568; font-size: .82rem; font-weight: 600; }
.seo-suite-strip .seo-nav-link:hover { background: #f7f9fc; color: #1f2937; text-decoration: none; }
.seo-suite-strip .seo-nav-link.active { background: #eaf1ff; color: #2f5fb8; }
.seo-suite-strip .seo-nav-link i { font-size: 1rem; margin-right: 5px; }
.seo-suite-strip .seo-chip { display: inline-flex; align-items: center; padding: 5px 8px; border-radius: 999px; background: #f7f9fc; color: #6b7280; font-size: .74rem; font-weight: 600; }
.seo-suite-strip .seo-chip.good { background: rgba(28,200,138,.12); color: #168a61; }
.seo-suite-strip .seo-chip.warn { background: rgba(246,194,62,.16); color: #946400; }
.seo-suite-strip .seo-chip.bad { background: rgba(231,74,59,.12); color: #b23125; }
</style>

<div class="seo-suite-strip mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between p-2">
        <div class="nav-scroll flex-grow-1 mr-md-3">
            @foreach($seoSuiteNavItems as $item)
                @if(Route::has($item['route']))
                    <a href="{{ route($item['route']) }}"
                       class="seo-nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <i class="las {{ $item['icon'] }}"></i>{{ translate($item['label']) }}
                    </a>
                @endif
            @endforeach
        </div>
        <div class="d-flex flex-wrap mt-2 mt-md-0" style="gap:.35rem;">
            <span class="seo-chip {{ $seoAutopilotEnabled ? 'good' : 'warn' }}">
                <i class="las la-bolt mr-1"></i>{{ $seoAutopilotEnabled ? translate('Autopilot ON') : translate('Autopilot OFF') }}
            </span>
            @if(!is_null($seoAutopilotPending))
                <span class="seo-chip {{ $seoAutopilotPending > 0 ? 'warn' : 'good' }}">
                    <i class="las la-list-ol mr-1"></i>{{ $seoAutopilotPending }} {{ translate('pending') }}
                </span>
            @endif
            <span class="seo-chip {{ $configuredProviders > 0 ? 'good' : 'bad' }}"><i class="las la-key mr-1"></i>{{ $configuredProviders }}/4 {{ translate('AI keys') }}</span>
            <span class="seo-chip {{ file_exists(base_path('sitemap.xml')) ? 'good' : 'bad' }}"><i class="las la-sitemap mr-1"></i>{{ file_exists(base_path('sitemap.xml')) ? translate('Sitemap ready') : translate('Sitemap missing') }}</span>
            <span class="seo-chip {{ file_exists(public_path('robots.txt')) ? 'good' : 'bad' }}"><i class="las la-robot mr-1"></i>{{ file_exists(public_path('robots.txt')) ? translate('Robots ready') : translate('Robots missing') }}</span>
            @if($seoActiveBatch)
                <span class="seo-chip warn"><i class="las la-spinner mr-1"></i>#{{ $seoActiveBatch->id }} {{ $seoActiveBatch->status }} {{ $seoActiveBatch->progressPercent() }}%</span>
            @endif
        </div>
    </div>
</div>
