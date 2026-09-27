@include('backend.seo.partials.module_styles')
@php
    $settings = $settings ?? [];
    // Six task-based menus instead of a 23-item scrolling strip.
    $seoSuiteNavGroups = [
        'Autopilot' => ['icon' => 'la-brain', 'items' => [
            ['route' => 'admin.seo.ai_board.index', 'icon' => 'la-brain', 'label' => 'AI Board'],
            ['route' => 'admin.seo.monitoring.index', 'icon' => 'la-heartbeat', 'label' => 'Monitoring'],
            ['route' => 'admin.seo-suite.revisions', 'icon' => 'la-history', 'label' => 'Revisions'],
        ]],
        'Content' => ['icon' => 'la-pen-nib', 'items' => [
            ['route' => 'admin.seo-suite.ai_writing_page', 'icon' => 'la-pen-nib', 'label' => 'Writer'],
            ['route' => 'admin.seo-suite.ai_assistant', 'icon' => 'la-robot', 'label' => 'Assistant'],
            ['route' => 'admin.seo-suite.research_agent', 'icon' => 'la-brain', 'label' => 'Research Agent'],
            ['route' => 'admin.seo-suite.semantic_gap', 'icon' => 'la-search-plus', 'label' => 'Semantic Gap'],
            ['route' => 'admin.seo-suite.content_decay', 'icon' => 'la-chart-line', 'label' => 'Content Decay'],
        ]],
        'Keywords' => ['icon' => 'la-key', 'items' => [
            ['route' => 'admin.seo-suite.keyword_tracker', 'icon' => 'la-chart-line', 'label' => 'Keywords'],
            ['route' => 'admin.seo-suite.keyword_clusters', 'icon' => 'la-project-diagram', 'label' => 'Clusters'],
            ['route' => 'admin.seo-suite.search_stats', 'icon' => 'la-chart-bar', 'label' => 'Stats'],
            ['route' => 'admin.seo-suite.predictive_traffic', 'icon' => 'la-chart-area', 'label' => 'Predict ROI'],
        ]],
        'Technical' => ['icon' => 'la-cogs', 'items' => [
            ['route' => 'admin.seo_on_page.index', 'icon' => 'la-file-alt', 'label' => 'On-Page'],
            ['route' => 'admin.seo_optimization.index', 'icon' => 'la-cogs', 'label' => 'Optimization'],
            ['route' => 'admin.seo-suite.geo_readiness', 'icon' => 'la-robot', 'label' => 'GEO Readiness'],
            ['route' => 'admin.seo-suite.core_web_vitals', 'icon' => 'la-tachometer-alt', 'label' => 'Web Vitals'],
            ['route' => 'admin.seo-suite.field_data', 'icon' => 'la-users', 'label' => 'Field Data'],
            ['route' => 'admin.seo-suite.webmaster', 'icon' => 'la-tools', 'label' => 'Webmaster'],
        ]],
        'Links' => ['icon' => 'la-link', 'items' => [
            ['route' => 'admin.seo-suite.link_assistant', 'icon' => 'la-link', 'label' => 'Links'],
            ['route' => 'admin.seo-suite.link_graph', 'icon' => 'la-network-wired', 'label' => 'Link Graph'],
            ['route' => 'admin.seo_off_page.index', 'icon' => 'la-bullhorn', 'label' => 'Off-Page'],
        ]],
    ];

    // Count every registered provider, not a hard-coded 7 of the 14.
    $seoProviderMeta = \App\Services\Seo\Providers\SeoProviderManager::meta();
    $seoProviderTotal = count($seoProviderMeta);
    $configuredProviders = collect($seoProviderMeta)
        ->filter(fn($meta, $name) => !empty($settings[$meta['field']] ?? null)
            || !empty(config("seo.providers.{$name}.api_key"))
            || !empty(get_setting($meta['setting'])))
        ->count();

    $seoAutopilotEnabled = (int) get_setting('seo_auto_seo_enabled', 1) === 1;
    $seoAutopilotPending = null;
    $seoActiveBatch = null;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('seo_meta')) {
            // Shown on every SEO page; recomputing it each time cost ~0.5-1s.
            $seoAutopilotPending = \Illuminate\Support\Facades\Cache::remember('seo:nav-pending-count', 300, fn() =>
                collect(app(\App\Services\Seo\Board\AiSeoBoardService::class)
                    ->pendingBreakdownByType(['page', 'category', 'product']))->sum('pending'));
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
    $seoSitemapReady = file_exists(base_path('sitemap.xml'));
    $seoRobotsReady = file_exists(public_path('robots.txt'));
@endphp

<style>
.seo-suite-bar { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; padding: .45rem .55rem; border: 1px solid #e6eaf0; border-radius: 10px; background: #fff; box-shadow: 0 4px 14px rgba(23,33,43,.06); }
.seo-suite-bar .seo-nav-btn { display: inline-flex; align-items: center; padding: 7px 11px; border: 0; border-radius: 7px; background: transparent; color: #52606d; font-size: .82rem; font-weight: 700; line-height: 1.2; white-space: nowrap; }
.seo-suite-bar .seo-nav-btn:hover, .seo-suite-bar .show > .seo-nav-btn { background: #f4f8f9; color: #164f5b; text-decoration: none; }
.seo-suite-bar .seo-nav-btn.active { background: #e9f6f8; color: #146c7e; }
.seo-suite-bar .seo-nav-btn i { font-size: 1rem; margin-right: 5px; }
.seo-suite-bar .seo-nav-btn .seo-current { margin-left: 5px; padding-left: 6px; border-left: 1px solid #b9dbe1; font-weight: 600; }
.seo-suite-bar .dropdown-menu { min-width: 190px; padding: .35rem; border: 1px solid #e6eaf0; border-radius: 9px; box-shadow: 0 10px 28px rgba(23,33,43,.12); }
.seo-suite-bar .dropdown-item { display: flex; align-items: center; padding: .45rem .6rem; border-radius: 6px; color: #3d4852; font-size: .82rem; font-weight: 600; }
.seo-suite-bar .dropdown-item i { width: 20px; margin-right: 6px; color: #8a96a3; font-size: 1rem; }
.seo-suite-bar .dropdown-item.active, .seo-suite-bar .dropdown-item:active { background: #e9f6f8; color: #146c7e; }
.seo-suite-bar .dropdown-item.active i { color: #146c7e; }
.seo-suite-bar .seo-bar-status { display: flex; align-items: center; flex-wrap: wrap; gap: .3rem; margin-left: auto; }
.seo-suite-bar .seo-chip { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #f1f4f7; color: #667085; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.seo-suite-bar .seo-chip:hover { text-decoration: none; filter: brightness(.97); }
.seo-suite-bar .seo-chip.good { background: rgba(21,128,93,.11); color: #127052; }
.seo-suite-bar .seo-chip.warn { background: rgba(166,106,0,.12); color: #8a5900; }
.seo-suite-bar .seo-chip.bad { background: rgba(195,63,74,.11); color: #a3313b; }
@media (max-width: 991.98px) { .seo-suite-bar .seo-bar-status { margin-left: 0; width: 100%; } }
</style>

<nav class="seo-suite-bar mb-4" aria-label="{{ translate('SEO Suite') }}">
    @if(Route::has('admin.seo-suite.index'))
        <a href="{{ route('admin.seo-suite.index') }}"
           class="seo-nav-btn {{ request()->routeIs('admin.seo-suite.index') ? 'active' : '' }}">
            <i class="las la-tachometer-alt"></i>{{ translate('Dashboard') }}
        </a>
    @endif

    @foreach($seoSuiteNavGroups as $groupLabel => $group)
        @php
            $visibleItems = array_values(array_filter($group['items'], fn($item) => Route::has($item['route'])));
            $activeItem = collect($visibleItems)->first(fn($item) => request()->routeIs($item['route']));
        @endphp
        @if(!empty($visibleItems))
            <div class="dropdown">
                <button type="button" class="seo-nav-btn dropdown-toggle {{ $activeItem ? 'active' : '' }}"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="las {{ $group['icon'] }}"></i>{{ translate($groupLabel) }}
                    @if($activeItem)<span class="seo-current">{{ translate($activeItem['label']) }}</span>@endif
                </button>
                <div class="dropdown-menu">
                    @foreach($visibleItems as $item)
                        <a class="dropdown-item {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <i class="las {{ $item['icon'] }}"></i>{{ translate($item['label']) }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <div class="seo-bar-status">
        <span class="seo-chip {{ $seoAutopilotEnabled ? 'good' : 'warn' }}" title="{{ translate('Autopilot') }}">
            <i class="las la-bolt mr-1"></i>{{ $seoAutopilotEnabled ? translate('Autopilot ON') : translate('Autopilot OFF') }}
        </span>
        @if(!is_null($seoAutopilotPending))
            <span class="seo-chip {{ $seoAutopilotPending > 0 ? 'warn' : 'good' }}"><i class="las la-list-ol mr-1"></i>{{ $seoAutopilotPending }} {{ translate('pending') }}</span>
        @endif
        @if($seoActiveBatch)
            <span class="seo-chip warn"><i class="las la-spinner mr-1"></i>#{{ $seoActiveBatch->id }} {{ $seoActiveBatch->progressPercent() }}%</span>
        @endif
        <span class="seo-chip {{ $configuredProviders > 0 ? 'good' : 'bad' }}" title="{{ translate('AI providers with an API key') }}"><i class="las la-key mr-1"></i>{{ $configuredProviders }}/{{ $seoProviderTotal }} {{ translate('AI') }}</span>
        <span class="seo-chip {{ $seoSitemapReady && $seoRobotsReady ? 'good' : 'bad' }}" title="sitemap.xml / robots.txt">
            <i class="las la-sitemap mr-1"></i>{{ $seoSitemapReady && $seoRobotsReady ? translate('Sitemap & robots OK') : (!$seoSitemapReady ? translate('Sitemap missing') : translate('Robots missing')) }}
        </span>
        @if(Route::has('admin.seo-suite.settings.view'))
            <a href="{{ route('admin.seo-suite.settings.view') }}"
               class="seo-nav-btn {{ request()->routeIs('admin.seo-suite.settings.view') ? 'active' : '' }}" title="{{ translate('Settings') }}">
                <i class="las la-sliders-h"></i>{{ translate('Settings') }}
            </a>
        @endif
    </div>
</nav>
