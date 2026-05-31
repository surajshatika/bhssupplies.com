@include('backend.seo.partials.module_styles')
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
            $seoBreakdown = app(\App\Services\Seo\Board\AiSeoBoardService::class)->pendingBreakdownByType(['page', 'category', 'product']);
            $seoAutopilotPending = collect($seoBreakdown)->sum('pending');
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('seo_fix_batches')) {
            $seoActiveBatch = \App\Models\SeoFixBatch::query()
                ->whereIn('status', [\App\Models\SeoFixBatch::STATUS_QUEUED, \App\Models\SeoFixBatch::STATUS_RUNNING])
                ->orderBy('id')
                ->first();
        }
    } catch (\Throwable $e) {
        $seoAutopilotPending = null;
        $seoActiveBatch = null;
    }
@endphp

<style>
.seo-suite-strip { border: 1px solid #e6eaf0; border-radius: 8px; background: #fff; box-shadow: 0 2px 8px rgba(23,33,43,.035); overflow: hidden; }
.seo-suite-strip .seo-nav-row { padding: .42rem .5rem .28rem; border-bottom: 1px solid #edf0f4; }
.seo-suite-strip .nav-scroll { overflow-x: auto; overflow-y: hidden; white-space: nowrap; scrollbar-width: thin; }
.seo-suite-strip .seo-nav-link { display: inline-flex; align-items: center; padding: 8px 10px; border-radius: 6px; color: #52606d; font-size: .79rem; font-weight: 700; }
.seo-suite-strip .seo-nav-link:hover { background: #f4f8f9; color: #164f5b; text-decoration: none; }
.seo-suite-strip .seo-nav-link.active { background: #e9f6f8; color: #146c7e; }
.seo-suite-strip .seo-nav-link i { font-size: .95rem; margin-right: 5px; }
.seo-suite-strip .seo-health-row { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; padding: .48rem .62rem; background: #fbfcfd; }
.seo-suite-strip .seo-health-label { margin-right: .12rem; color: #667085; font-size: .68rem; font-weight: 800; text-transform: uppercase; }
.seo-suite-strip .seo-chip { display: inline-flex; align-items: center; padding: 4px 7px; border-radius: 999px; background: #f1f4f7; color: #667085; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.seo-suite-strip .seo-chip.good { background: rgba(21,128,93,.11); color: #127052; }
.seo-suite-strip .seo-chip.warn { background: rgba(166,106,0,.12); color: #8a5900; }
.seo-suite-strip .seo-chip.bad { background: rgba(195,63,74,.11); color: #a3313b; }
@media (max-width: 767.98px) {
    .seo-suite-strip .seo-health-label { display: block; width: 100%; }
    .seo-suite-strip .seo-nav-link { padding: 7px 8px; font-size: .75rem; }
}
</style>

<div class="seo-suite-strip mb-4">
    <div class="seo-nav-row">
        <div class="nav-scroll">
            @foreach($seoSuiteNavItems as $item)
                @if(Route::has($item['route']))
                    <a href="{{ route($item['route']) }}"
                       class="seo-nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <i class="las {{ $item['icon'] }}"></i>{{ translate($item['label']) }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
    <div class="seo-health-row">
        <span class="seo-health-label">{{ translate('Live SEO Health') }}</span>
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
