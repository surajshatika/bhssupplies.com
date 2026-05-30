@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')
@php
    $providers = ['openai' => 'OpenAI (ChatGPT)', 'claude' => 'Claude', 'gemini' => 'Gemini', 'grok' => 'Grok (xAI)'];
    $optimizationDashboard = $optimizationDashboard ?? [];
    $settings = $settings ?? [];

    // Feature groups with icons and colors — clean, no duplicates
    $toolGroups = [
        'Sitemaps' => [
            'icon'  => 'la-sitemap',
            'color' => 'primary',
            'tools' => [
                'smart_sitemap'  => ['label' => 'Smart XML Sitemap',    'icon' => 'la-sitemap',   'desc' => 'Full site sitemap: products, categories, blogs, pages'],
                'video_sitemap'  => ['label' => 'Video SEO Sitemap',    'icon' => 'la-video',     'desc' => 'Video sitemap for embedded/hosted videos'],
                'blog_sitemap'   => ['label' => 'Blog / News Sitemap',  'icon' => 'la-newspaper', 'desc' => 'Blog & news articles sitemap for Google'],
                'rss_content'    => ['label' => 'RSS Content Feed',     'icon' => 'la-rss',       'desc' => 'RSS 2.0 feed for feed readers & aggregators'],
            ],
        ],
        'Technical SEO' => [
            'icon'  => 'la-code',
            'color' => 'info',
            'tools' => [
                'page_speed'     => ['label' => 'Page Speed Analyzer',      'icon' => 'la-tachometer-alt', 'desc' => 'Core Web Vitals analysis & fix recommendations'],
                'technical_audit'=> ['label' => 'Technical SEO Audit',      'icon' => 'la-search',         'desc' => '50+ technical SEO checks'],
                'robots'         => ['label' => 'Robots.txt Optimizer',     'icon' => 'la-robot',          'desc' => 'AI-optimized robots.txt generation'],
                'canonical'      => ['label' => 'Canonical URL Manager',    'icon' => 'la-link',           'desc' => 'Manage canonical tags to prevent duplication'],
                'broken_links'   => ['label' => 'Broken Link Checker',      'icon' => 'la-unlink',         'desc' => 'Detect & fix 404 broken links'],
                'competitor_gap' => ['label' => 'Competitor Gap Analyzer',  'icon' => 'la-chess',          'desc' => 'Find keyword gaps vs competitors'],
            ],
        ],
        'Content & Schema' => [
            'icon'  => 'la-code-branch',
            'color' => 'success',
            'tools' => [
                'faq_schema'     => ['label' => 'FAQ Schema Generator',     'icon' => 'la-question-circle','desc' => 'Auto-generate FAQ schema markup'],
                'ecommerce_seo'  => ['label' => 'E-commerce Product SEO',   'icon' => 'la-shopping-cart',  'desc' => 'Product schema & e-commerce optimization'],
                'local_seo'      => ['label' => 'Local SEO Optimizer',      'icon' => 'la-map-marker',     'desc' => 'Google Business & local citations'],
                'small_business_seo'=>['label'=> 'Small Business SEO',      'icon' => 'la-store',          'desc' => 'SMB SEO action plan & local schema'],
            ],
        ],
        'URL & Redirects' => [
            'icon'  => 'la-exchange-alt',
            'color' => 'warning',
            'tools' => [
                'redirection_manager'=>['label'=> 'Redirection Manager',    'icon' => 'la-exchange-alt',   'desc' => 'Manage 301/302 redirects'],
                'post_index_status'  =>['label'=> 'Post Index Status',      'icon' => 'la-search',         'desc' => 'Check if pages are indexed by Google'],
                'indexnow'           =>['label'=> 'IndexNow Submission',    'icon' => 'la-bolt',           'desc' => 'Instant URL submission to Bing/Yandex'],
                'webmaster_tools'    =>['label'=> 'Webmaster Tools',        'icon' => 'la-tools',          'desc' => 'Google, Bing, Yandex verification codes'],
            ],
        ],
        'AI Tools' => [
            'icon'  => 'la-robot',
            'color' => 'secondary',
            'tools' => [
                'ai_writing_assistant'=>['label'=> 'AI Writing Assistant',  'icon' => 'la-pen-nib',        'desc' => 'Generate, improve & paraphrase content'],
                'ai_image_generator'  =>['label'=> 'AI Image Generator',   'icon' => 'la-image',          'desc' => 'DALL-E 3 SEO-optimized image generation'],
                'ai_assistant'        =>['label'=> 'AI SEO Chat Assistant', 'icon' => 'la-comments',       'desc' => 'Chat with AI for SEO strategies'],
                'link_assistant'      =>['label'=> 'Link Assistant',        'icon' => 'la-link',           'desc' => 'Internal & external link opportunities'],
                'keyword_rank_tracker'=>['label'=> 'Keyword Rank Tracker',  'icon' => 'la-chart-line',     'desc' => 'Track keyword positions in Google'],
                'search_statistics'   =>['label'=> 'Search Statistics',     'icon' => 'la-chart-bar',      'desc' => 'SEO score trends & performance analytics'],
                'seo_revisions'       =>['label'=> 'SEO Revisions',         'icon' => 'la-history',        'desc' => 'Track & compare SEO score history'],
                'llms_txt'            =>['label'=> 'LLMs.txt Generator',    'icon' => 'la-file-code',      'desc' => 'AI crawler directive file'],
                'score_dashboard'     =>['label'=> 'SEO Score Dashboard',   'icon' => 'la-tachometer-alt', 'desc' => 'Weekly automated score tracking'],
            ],
        ],
    ];

    // Direct action routes (run immediately without modal)
    $directActions = [
        'smart_sitemap'  => 'admin.seo-suite.sitemap',
        'video_sitemap'  => 'admin.seo-suite.sitemap.video',
        'blog_sitemap'   => 'admin.seo-suite.sitemap.news',
        'rss_content'    => 'admin.seo-suite.rss',
        'robots'         => 'admin.seo-suite.robots',
        'post_index_status' => 'admin.seo-suite.index_status',
        'indexnow'       => 'admin.seo-suite.indexnow',
        'webmaster_tools'=> 'admin.seo-suite.webmaster',
        'ai_writing_assistant' => 'admin.seo-suite.ai_writing_page',
        'ai_image_generator'   => 'admin.seo-suite.ai_images',
        'ai_assistant'         => 'admin.seo-suite.ai_assistant',
        'link_assistant'       => 'admin.seo-suite.link_assistant',
        'keyword_rank_tracker' => 'admin.seo-suite.keyword_tracker',
        'search_statistics'    => 'admin.seo-suite.search_stats',
        'seo_revisions'        => 'admin.seo-suite.revisions',
        'llms_txt'             => 'admin.seo-suite.llms_txt',
        'redirection_manager'  => 'admin.seo-suite.index',
    ];
    $postActionFeatures = ['smart_sitemap','video_sitemap','blog_sitemap','rss_content','robots','indexnow','llms_txt'];
    $readiness = (int) ($optimizationDashboard['technical_readiness'] ?? 0);
    $readinessColor = $readiness >= 80 ? 'success' : ($readiness >= 50 ? 'warning' : 'danger');
@endphp

<style>
.optimization-metric { min-height: 108px; border: 1px solid #edf0f5; border-radius: 8px; padding: 16px; background: #fff; }
.optimization-metric .metric-value { font-size: 1.55rem; font-weight: 700; line-height: 1; }
.optimization-panel { border: 1px solid #edf0f5; border-radius: 8px; background: #fff; }
.optimization-panel .panel-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid #f0f2f6; }
.optimization-panel .panel-row:last-child { border-bottom: 0; }
.optimization-action { border: 1px solid #edf0f5; border-left-width: 4px; border-radius: 8px; padding: 12px; background: #fff; height: 100%; }
.optimization-action.critical { border-left-color: #e74a3b; }
.optimization-action.high { border-left-color: #f6c23e; }
.optimization-action.medium { border-left-color: #36b9cc; }
.optimization-action.low { border-left-color: #858796; }
.automation-command { border: 1px dashed #cfd7e6; border-radius: 8px; background: #f8fafc; padding: 10px 12px; font-size: .78rem; word-break: break-all; }
.tool-filter-btn.active { color: #fff !important; background: #4e73df !important; border-color: #4e73df !important; }
.tool-card { border: 1px solid #edf0f5 !important; }
.tool-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(31,45,61,.1) !important; }
</style>

<div class="mm-hero mm-hero--seo mb-3">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 17h16"/><path d="M4 7h16"/><path d="M7 4v6"/><path d="M17 14v6"/><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/></svg>
            </div>
            <div>
                <h2>{{ translate('Technical SEO Optimization') }}</h2>
                <p>{{ optional($project)->name ?? get_setting('website_name', config('app.name')) }} - {{ optional($project)->base_url ?? url('/') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-shield-alt"></i> {{ translate('Readiness') }} {{ $readiness }}%</span>
                    <span class="mm-chip"><i class="las la-bolt"></i> {{ !empty($optimizationDashboard['automation']['master_enabled']) ? translate('Hourly automation ON') : translate('Hourly automation OFF') }}</span>
                    <span class="mm-chip"><i class="las la-map-marker"></i> {{ translate('Mississauga / Brampton / Toronto') }}</span>
                </div>
            </div>
        </div>
        <div class="mt-3 mt-md-0 text-md-right">
            <form action="{{ route('admin.seo-suite.sitemap') }}" method="POST" class="d-inline-block">
                @csrf
                <button class="btn btn-light mr-2">
                    <i class="las la-sitemap mr-1"></i>{{ translate('Generate Sitemap') }}
                </button>
            </form>
            <form action="{{ route('admin.seo-suite.robots') }}" method="POST" class="d-inline-block">
                @csrf
                <button class="btn btn-light mr-2">
                    <i class="las la-robot mr-1"></i>{{ translate('Generate Robots') }}
                </button>
            </form>
            <a href="{{ route('admin.seo-suite.settings.view') }}" class="btn btn-outline-light">
                <i class="las la-sliders-h mr-1"></i>{{ translate('Automation Settings') }}
            </a>
        </div>
    </div>
</div>

<div class="aiz-titlebar mt-2 mb-4 d-none">
    <div class="row align-items-center">
        <div class="col-md-5">
            <h1 class="h3">{{ translate('Technical Optimization & SEO Tools') }}</h1>
            @if($project)
                <p class="text-muted mb-0">{{ $project->name }} — {{ $project->base_url }}</p>
            @endif
        </div>
        <div class="col-md-7 text-md-right">
            <form action="{{ route('admin.seo-suite.sitemap') }}" method="POST" class="d-inline-block">
                @csrf
                <button class="btn btn-soft-primary mr-2">
                    <i class="las la-sitemap mr-1"></i>{{ translate('Generate Sitemap') }}
                </button>
            </form>
            <form action="{{ route('admin.seo-suite.robots') }}" method="POST" class="d-inline-block">
                @csrf
                <button class="btn btn-soft-success mr-2">
                    <i class="las la-robot mr-1"></i>{{ translate('Generate Robots') }}
                </button>
            </form>
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-tachometer-alt mr-1"></i>{{ translate('Dashboard') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

@if($setupRequired)
    <div class="alert alert-warning">
        <i class="las la-exclamation-triangle mr-1"></i>
        {{ translate('SEO suite database tables are missing. Run the four SEO migrations to activate full features.') }}
    </div>
@endif

{{-- Optimization Automation Center --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h5 class="mb-0 h6">{{ translate('Optimization Automation Center') }}</h5>
            <small class="text-muted">{{ translate('Hourly command covers on-page, off-page, backlinks, technical files, scores, GSC, ranks, PageSpeed, and broken-link checks.') }}</small>
        </div>
        <div class="d-flex flex-wrap mt-2 mt-md-0" style="gap:.35rem;">
            <span class="badge badge-{{ !empty($optimizationDashboard['automation']['master_enabled']) ? 'success' : 'secondary' }}">{{ !empty($optimizationDashboard['automation']['master_enabled']) ? translate('Master ON') : translate('Master OFF') }}</span>
            <span class="badge badge-soft-info">{{ translate('Cron') }}: {{ translate('Hourly') }}</span>
            <span class="badge badge-soft-success">{{ translate('Done SEO protected') }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row gutters-16 mb-4">
            <div class="col-md-3 mb-3">
                <div class="optimization-metric">
                    <span class="text-muted small">{{ translate('Technical Readiness') }}</span>
                    <div class="metric-value text-{{ $readinessColor }} mt-3">{{ $readiness }}%</div>
                    <small class="text-muted">{{ translate('files, APIs, automation') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="optimization-metric">
                    <span class="text-muted small">{{ translate('Run Success Rate') }}</span>
                    <div class="metric-value text-success mt-3">{{ $optimizationDashboard['success_rate'] ?? 100 }}%</div>
                    <small class="text-muted">{{ $optimizationDashboard['failed_runs'] ?? 0 }} {{ translate('failed recent runs') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="optimization-metric">
                    <span class="text-muted small">{{ translate('Active Redirects') }}</span>
                    <div class="metric-value text-warning mt-3">{{ $optimizationDashboard['active_redirects'] ?? 0 }}</div>
                    <small class="text-muted">{{ translate('301/302 rules') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="optimization-metric">
                    <span class="text-muted small">{{ translate('AI Providers') }}</span>
                    <div class="metric-value text-primary mt-3">{{ $optimizationDashboard['providers_configured'] ?? 0 }}/4</div>
                    <small class="text-muted">{{ translate('available for AI tools') }}</small>
                </div>
            </div>
        </div>

        <div class="row gutters-16">
            <div class="col-lg-4 mb-3 mb-lg-0">
                <h6 class="font-weight-600 mb-3">{{ translate('Hourly Cron Setup') }}</h6>
                <div class="automation-command mb-2">{{ $optimizationDashboard['automation']['direct_hourly_cron'] ?? '0 * * * * php artisan seo:automation-run' }}</div>
                <div class="automation-command">{{ $optimizationDashboard['automation']['dry_run_command'] ?? 'php artisan seo:automation-run --dry-run' }}</div>
                <small class="text-muted d-block mt-2">{{ translate('Use dry-run first. On-page runs every hour; heavier tasks are interval-gated unless --force-all is used.') }}</small>
            </div>
            <div class="col-lg-4 mb-3 mb-lg-0">
                <h6 class="font-weight-600 mb-3">{{ translate('Automation Scope') }}</h6>
                <div class="optimization-panel">
                    <div class="panel-row"><span>{{ translate('On-page pending SEO') }}</span><span class="badge badge-{{ !empty($optimizationDashboard['automation']['onpage_enabled']) ? 'success' : 'secondary' }}">{{ !empty($optimizationDashboard['automation']['onpage_enabled']) ? translate('ON') : translate('OFF') }}</span></div>
                    <div class="panel-row"><span>{{ translate('Off-page backlink campaigns') }}</span><span class="badge badge-{{ !empty($optimizationDashboard['automation']['offpage_enabled']) ? 'success' : 'secondary' }}">{{ !empty($optimizationDashboard['automation']['offpage_enabled']) ? translate('ON') : translate('OFF') }}</span></div>
                    <div class="panel-row"><span>{{ translate('Technical refresh') }}</span><span class="badge badge-{{ !empty($optimizationDashboard['automation']['technical_enabled']) ? 'success' : 'secondary' }}">{{ !empty($optimizationDashboard['automation']['technical_enabled']) ? translate('ON') : translate('OFF') }}</span></div>
                    <div class="panel-row"><span>{{ translate('IndexNow') }}</span><span class="badge badge-{{ !empty($optimizationDashboard['automation']['auto_indexnow']) ? 'success' : 'secondary' }}">{{ !empty($optimizationDashboard['automation']['auto_indexnow']) ? translate('ON') : translate('OFF') }}</span></div>
                </div>
            </div>
            <div class="col-lg-4">
                <h6 class="font-weight-600 mb-3">{{ translate('Canada Targeting') }}</h6>
                <div class="optimization-panel p-3">
                    <div class="mb-2">
                        <strong class="small d-block mb-1">{{ translate('Primary') }}</strong>
                        @foreach(($optimizationDashboard['local_targets']['primary'] ?? []) as $city)
                            <span class="badge badge-soft-primary mr-1 mb-1">{{ $city }}</span>
                        @endforeach
                    </div>
                    <div class="mb-2">
                        <strong class="small d-block mb-1">{{ translate('Secondary') }}</strong>
                        @foreach(($optimizationDashboard['local_targets']['secondary'] ?? []) as $city)
                            <span class="badge badge-soft-info mr-1 mb-1">{{ $city }}</span>
                        @endforeach
                    </div>
                    @foreach(($optimizationDashboard['local_targets']['conversion'] ?? []) as $intent)
                        <span class="badge badge-soft-warning mr-1">{{ $intent }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Technical File Health --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0 h6">{{ translate('Technical File Health') }}</h5>
        <span class="badge badge-soft-secondary">{{ translate('Auto refreshed by seo:automation-run') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('File') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Updated') }}</th>
                        <th>{{ translate('Size') }}</th>
                        <th>{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($optimizationDashboard['files'] ?? []) as $file)
                        <tr>
                            <td><i class="las {{ $file['icon'] }} mr-1 text-primary"></i>{{ translate($file['label']) }}</td>
                            <td><span class="badge badge-{{ $file['exists'] ? 'success' : 'danger' }}">{{ $file['exists'] ? translate('Ready') : translate('Missing') }}</span></td>
                            <td class="small text-muted">{{ $file['updated_at'] ?? '-' }}</td>
                            <td class="small">{{ $file['exists'] ? number_format(($file['size'] ?? 0) / 1024, 1) . ' KB' : '-' }}</td>
                            <td>
                                @if(($file['method'] ?? 'get') === 'post')
                                    <form action="{{ route($file['route']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-xs btn-soft-primary">{{ translate('Refresh') }}</button>
                                    </form>
                                @else
                                    <a href="{{ route($file['route']) }}" class="btn btn-xs btn-soft-primary">{{ translate('Open') }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(!empty($optimizationDashboard['actions']))
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Priority Technical Actions') }}</h5></div>
    <div class="card-body">
        <div class="row gutters-16">
            @foreach($optimizationDashboard['actions'] as $action)
                <div class="col-md-6 col-xl-4 mb-3">
                    <div class="optimization-action {{ $action['severity'] }}">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="font-weight-600"><i class="las {{ $action['icon'] }} mr-1"></i>{{ translate($action['title']) }}</div>
                                <p class="small text-muted mb-2">{{ translate($action['detail']) }}</p>
                            </div>
                            <span class="badge badge-soft-secondary text-uppercase">{{ translate($action['severity']) }}</span>
                        </div>
                        @if(($action['method'] ?? 'get') === 'post')
                            <form action="{{ route($action['route']) }}" method="POST">
                                @csrf
                                <button class="btn btn-xs btn-soft-primary">{{ translate('Run') }}</button>
                            </form>
                        @else
                            <a href="{{ route($action['route']) }}" class="btn btn-xs btn-soft-primary">{{ translate('Open') }}</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Score Cards --}}
<div class="row gutters-16 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                @php
                    $score = $dashboard['current_score'] ?? 0;
                    $scoreColor = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                @endphp
                <div class="h2 mb-1 text-{{ $scoreColor }}">{{ $score }}</div>
                <div class="text-muted small">{{ translate('Current SEO Score') }}</div>
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar bg-{{ $scoreColor }}" style="width:{{ $score }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-primary">{{ $dashboard['current_grade'] ?? 'N/A' }}</div>
                <div class="text-muted small">{{ translate('Grade') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-info">{{ $dashboard['average_score'] ?? 0 }}</div>
                <div class="text-muted small">{{ translate('Avg Score (History)') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-success">{{ $runs->where('status','completed')->count() }}</div>
                <div class="text-muted small">{{ translate('Completed Runs') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Tool Groups --}}
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0" style="gap:.4rem;">
            <button type="button" class="btn btn-xs btn-soft-primary tool-filter-btn active" data-tool-filter="all">{{ translate('All Tools') }}</button>
            @foreach(array_keys($toolGroups) as $groupName)
                <button type="button" class="btn btn-xs btn-soft-secondary tool-filter-btn" data-tool-filter="{{ \Illuminate\Support\Str::slug($groupName) }}">{{ translate($groupName) }}</button>
            @endforeach
        </div>
        <input type="text" id="optimizationToolSearch" class="form-control form-control-sm" style="max-width:260px;" placeholder="{{ translate('Search optimization tools') }}">
    </div>
</div>

@foreach($toolGroups as $groupName => $group)
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="las {{ $group['icon'] }} text-{{ $group['color'] }} mr-2 la-lg"></i>
        <h6 class="mb-0 font-weight-600">{{ translate($groupName) }}</h6>
    </div>
    <div class="card-body">
        <div class="row gutters-16">
            @foreach($group['tools'] as $featureKey => $tool)
            <div class="col-xl-2 col-lg-3 col-md-4 col-6 mb-3 seo-tool-item"
                 data-tool-group="{{ \Illuminate\Support\Str::slug($groupName) }}"
                 data-tool-text="{{ strtolower($featureKey . ' ' . $tool['label'] . ' ' . $tool['desc']) }}">
                @if(isset($directActions[$featureKey]) && in_array($featureKey, $postActionFeatures))
                {{-- Direct POST action --}}
                <form action="{{ route($directActions[$featureKey]) }}" method="POST" class="h-100">
                    @csrf
                    <button type="submit" class="btn btn-block h-100 p-0 border-0 bg-transparent text-left w-100">
                        <div class="card h-100 shadow-sm border-0 tool-card tool-card-direct text-center"
                             style="cursor:pointer; transition:all 0.2s;" title="{{ $tool['desc'] }}">
                            <div class="card-body d-flex flex-column justify-content-center py-3 px-2">
                                <i class="las {{ $tool['icon'] }} la-2x text-{{ $group['color'] }} mb-2"></i>
                                <h6 class="font-weight-600 mb-1" style="font-size:0.78rem; line-height:1.3;">{{ $tool['label'] }}</h6>
                                <p class="text-muted mb-0" style="font-size:0.68rem; line-height:1.3;">{{ $tool['desc'] }}</p>
                            </div>
                        </div>
                    </button>
                </form>
                @elseif(isset($directActions[$featureKey]))
                {{-- Direct GET link --}}
                <a href="{{ route($directActions[$featureKey]) }}" class="h-100 d-block text-decoration-none">
                    <div class="card h-100 shadow-sm border-0 tool-card text-center"
                         style="cursor:pointer; transition:all 0.2s;" title="{{ $tool['desc'] }}">
                        <div class="card-body d-flex flex-column justify-content-center py-3 px-2">
                            <i class="las {{ $tool['icon'] }} la-2x text-{{ $group['color'] }} mb-2"></i>
                            <h6 class="font-weight-600 mb-1" style="font-size:0.78rem; line-height:1.3;">{{ $tool['label'] }}</h6>
                            <p class="text-muted mb-0" style="font-size:0.68rem; line-height:1.3;">{{ $tool['desc'] }}</p>
                        </div>
                    </div>
                </a>
                @else
                {{-- Modal trigger --}}
                <div class="card h-100 shadow-sm border-0 tool-card text-center"
                     style="cursor:pointer; transition:all 0.2s;"
                     title="{{ $tool['desc'] }}"
                     onclick="openToolModal('{{ $featureKey }}', '{{ addslashes($tool['label']) }}', '{{ addslashes($tool['desc']) }}')">
                    <div class="card-body d-flex flex-column justify-content-center py-3 px-2">
                        <i class="las {{ $tool['icon'] }} la-2x text-{{ $group['color'] }} mb-2"></i>
                        <h6 class="font-weight-600 mb-1" style="font-size:0.78rem; line-height:1.3;">{{ $tool['label'] }}</h6>
                        <p class="text-muted mb-0" style="font-size:0.68rem; line-height:1.3;">{{ $tool['desc'] }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

{{-- Recent Runs + Score Trend --}}
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('Recent Optimization Runs') }}</h5>
                <a href="{{ route('admin.seo-suite.revisions') }}" class="btn btn-xs btn-soft-primary">{{ translate('All History') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Feature') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                                <tr>
                                    <td>{{ data_get($features, $run->feature, $run->feature) }}</td>
                                    <td>
                                        <span class="badge badge-inline badge-{{ $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ $run->status }}
                                        </span>
                                    </td>
                                    <td class="text-truncate" style="max-width:180px;">{{ $run->url ?: '—' }}</td>
                                    <td class="text-nowrap small text-muted">{{ optional($run->created_at)->format('M d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No runs yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        {{-- Quick Redirect --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Quick Redirect') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.redirects.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small">{{ translate('From') }}</label>
                        <input type="text" class="form-control form-control-sm" name="source_url" placeholder="/old-page" required>
                    </div>
                    <div class="form-group">
                        <label class="small">{{ translate('To') }}</label>
                        <input type="text" class="form-control form-control-sm" name="target_url" placeholder="/new-page" required>
                    </div>
                    <div class="d-flex">
                        <select class="form-control form-control-sm mr-2" name="status_code" style="width:auto;">
                            <option value="301">301 Permanent</option>
                            <option value="302">302 Temporary</option>
                        </select>
                        <button class="btn btn-warning btn-sm flex-shrink-0">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Score Trend --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Score Trend') }}</h5></div>
            <div class="card-body p-0">
                @if(count($dashboard['trend'] ?? []) > 0)
                    @foreach($dashboard['trend'] as $trend)
                        <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                            <span class="text-muted small">{{ $trend['date'] }}</span>
                            <div>
                                <span class="badge badge-soft-{{ ($trend['score'] ?? 0) >= 80 ? 'success' : (($trend['score'] ?? 0) >= 50 ? 'warning' : 'danger') }}">
                                    {{ $trend['score'] }}
                                </span>
                                <span class="badge badge-soft-info ml-1">{{ $trend['grade'] }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center py-3 mb-0">{{ translate('No score history yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Run Tool Modal --}}
<div class="modal fade" id="toolModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toolModalLabel">{{ translate('Run SEO Tool') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('admin.seo_optimization.run') }}" method="POST">
                @csrf
                <input type="hidden" name="feature" id="toolFeatureInput">
                <div class="modal-body">
                    <p id="toolModalDesc" class="text-muted small mb-3"></p>
                    <div class="form-group">
                        <label>{{ translate('AI Provider') }}</label>
                        <select name="provider" class="form-control">
                            <option value="">{{ translate('System Default') }}</option>
                            @foreach($providers as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- URL field --}}
                    <div class="form-group tool-field" data-tools="page_speed,technical_audit,canonical,broken_links,competitor_gap,score_dashboard">
                        <label>{{ translate('Target URL') }}</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com/page">
                    </div>
                    <div class="form-group tool-field d-none" data-tools="broken_links">
                        <label>{{ translate('Links to Check') }}</label>
                        <textarea class="form-control" rows="4" name="links" placeholder="https://example.com/old-page&#10;/missing-product"></textarea>
                        <small class="text-muted">{{ translate('One URL per line. Relative links are allowed.') }}</small>
                    </div>
                    {{-- Competitor keywords --}}
                    <div class="form-group tool-field d-none" data-tools="competitor_gap">
                        <label>{{ translate('Your Keywords') }}</label>
                        <textarea class="form-control" rows="2" name="our_keywords" placeholder="safety gloves, PPE equipment"></textarea>
                        <label class="mt-2">{{ translate('Competitor Keywords') }}</label>
                        <textarea class="form-control" rows="2" name="comp1_keywords"></textarea>
                    </div>
                    {{-- FAQ / E-commerce --}}
                    <div class="form-group tool-field d-none" data-tools="faq_schema,ecommerce_seo">
                        <label>{{ translate('FAQ Pairs (Q | A) or Product JSON') }}</label>
                        <textarea class="form-control" rows="4" name="faq" placeholder="What is it? | It is a safety product."></textarea>
                    </div>
                    {{-- Local SEO --}}
                    <div class="form-group tool-field d-none" data-tools="local_seo,small_business_seo">
                        <label>{{ translate('Business Name') }}</label>
                        <input type="text" class="form-control" name="business_name" value="{{ get_setting('seo_local_business_name', get_setting('website_name')) }}">
                        <label class="mt-2">{{ translate('Business Type') }}</label>
                        <input type="text" class="form-control" name="business_type" value="{{ get_setting('seo_local_business_type', 'Store') }}">
                        <label class="mt-2">{{ translate('City / Location') }}</label>
                        <input type="text" class="form-control" name="city" value="Mississauga, Brampton, Toronto">
                        <label class="mt-2">{{ translate('Phone') }}</label>
                        <input type="text" class="form-control" name="phone" value="{{ get_setting('contact_phone', get_setting('business_phone')) }}">
                        <label class="mt-2">{{ translate('Address') }}</label>
                        <input type="text" class="form-control" name="address" value="{{ get_setting('contact_address', get_setting('business_address')) }}">
                        <label class="mt-2">{{ translate('Niche') }}</label>
                        <input type="text" class="form-control" name="niche" value="Canada safety and business supplies">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="las la-play mr-1"></i>{{ translate('Run Tool') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    // Tool card hover effects
    $(document).on('mouseenter', '.tool-card', function() {
        $(this).addClass('shadow').css({'transform':'translateY(-3px)', 'border-color':'rgba(0,0,0,.15)'});
    }).on('mouseleave', '.tool-card', function() {
        $(this).removeClass('shadow').css({'transform':'translateY(0)', 'border-color':''});
    });

    function filterOptimizationTools() {
        var activeGroup = $('.tool-filter-btn.active').data('tool-filter') || 'all';
        var query = ($('#optimizationToolSearch').val() || '').toLowerCase();

        $('.seo-tool-item').each(function() {
            var groupMatch = activeGroup === 'all' || $(this).data('tool-group') === activeGroup;
            var textMatch = !query || String($(this).data('tool-text') || '').indexOf(query) !== -1;
            $(this).toggle(groupMatch && textMatch);
        });
    }

    $('.tool-filter-btn').on('click', function() {
        $('.tool-filter-btn').removeClass('active');
        $(this).addClass('active');
        filterOptimizationTools();
    });

    $('#optimizationToolSearch').on('input', filterOptimizationTools);
});

function openToolModal(featureKey, label, desc) {
    $('#toolFeatureInput').val(featureKey);
    $('#toolModalLabel').text(label);
    $('#toolModalDesc').text(desc || '');

    // Show relevant fields
    $('.tool-field').addClass('d-none').removeClass('d-block');
    var featureFields = {
        'page_speed':    ['page_speed'],
        'technical_audit':['page_speed'],
        'canonical':     ['page_speed'],
        'broken_links':  ['page_speed', 'broken_links'],
        'competitor_gap':['page_speed', 'competitor_gap'],
        'faq_schema':    ['faq_schema'],
        'ecommerce_seo': ['faq_schema'],
        'local_seo':     ['local_seo'],
        'small_business_seo': ['local_seo'],
        'score_dashboard': ['page_speed'],
    };

    var relevantGroups = featureFields[featureKey] || ['page_speed'];
    relevantGroups.forEach(function(group) {
        $('[data-tools*="' + group + '"]').removeClass('d-none').addClass('d-block');
    });

    if ($('.tool-field:not(.d-none)').length === 0) {
        $('[data-tools*="page_speed"]').first().removeClass('d-none').addClass('d-block');
    }

    $('#toolModal').modal('show');
}
</script>
@endsection
