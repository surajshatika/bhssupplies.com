@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')
@php
    $activeTab    = request('tab', 'dashboard');
    $providers    = ['openai' => 'OpenAI (ChatGPT)', 'claude' => 'Claude (Anthropic)', 'gemini' => 'Gemini (Google)', 'grok' => 'Grok (xAI)'];
    $totalFeatures= collect($features)->flatten()->count();
    $seoScore     = $dashboard['current_score'] ?? 0;
    $seoGrade     = $dashboard['current_grade'] ?? 'N/A';
    $avgScore     = $dashboard['average_score'] ?? 0;
    $scoreColor   = $seoScore >= 80 ? '#1cc88a' : ($seoScore >= 50 ? '#f6c23e' : '#e74a3b');

    // Setup steps
    $setupSteps = [
        ['label' => 'AI Provider Configured',   'done' => !empty($settings['default_provider'])],
        ['label' => 'OpenAI / Claude API Key',  'done' => !empty($settings['openai_api_key']) || !empty($settings['anthropic_api_key'])],
        ['label' => 'Sitemap Generated',        'done' => file_exists(base_path('sitemap.xml'))],
        ['label' => 'Robots.txt Generated',     'done' => file_exists(public_path('robots.txt'))],
        ['label' => 'LLMs.txt Created',         'done' => file_exists(public_path('llms.txt'))],
        ['label' => 'Webmaster Tools Verified', 'done' => !empty($settings['google_verification']) || !empty($settings['bing_verification'])],
    ];
    $completedSteps = collect($setupSteps)->where('done', true)->count();
    $totalSteps     = count($setupSteps);
    $setupPct       = round(($completedSteps / $totalSteps) * 100);

    // TruSEO stats
    $runsCompleted  = $runs->where('status', 'completed')->count();
    $runsFailed     = $runs->where('status', 'failed')->count();
    $runsQueued     = $runs->where('status', 'queued')->count();
    $runsTotal      = $runs->count();
    $advancedDashboard = $advancedDashboard ?? [];
    $advancedActions   = $advancedDashboard['actions'] ?? [];
    $advancedFiles     = $advancedDashboard['files'] ?? [];
    $providerHealth    = $advancedDashboard['providers'] ?? [];
    $automationReady   = $advancedDashboard['automation_readiness'] ?? 0;
    $successRate       = $advancedDashboard['success_rate'] ?? 0;
    $trendDelta        = $advancedDashboard['trend_delta'] ?? 0;
    $riskLevel         = $advancedDashboard['risk_level'] ?? 'high';
    $competitorCount   = count(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($settings['competitor_urls'] ?? '')))));
    $offpageAutoOn     = !empty($settings['auto_offpage_enabled']);
@endphp

<style>
.seo-score-ring { width: 140px; height: 140px; position: relative; }
.seo-score-ring svg { transform: rotate(-90deg); }
.seo-score-ring .score-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
.setup-step.done .step-dot { background: #1cc88a; }
.setup-step.pending .step-dot { background: #e0e0e0; }
.step-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.module-card { border-left: 3px solid transparent; transition: all 0.2s; }
.module-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,.1)!important; }
.module-card.on-page   { border-color: #4e73df; }
.module-card.off-page  { border-color: #1cc88a; }
.module-card.optim     { border-color: #36b9cc; }
.notification-item { border-left: 3px solid #4e73df; }
.advanced-metric { min-height: 108px; border: 1px solid #edf0f5; border-radius: 8px; padding: 16px; background: #fff; }
.advanced-metric .metric-value { font-size: 1.65rem; font-weight: 700; line-height: 1; }
.advanced-action { border: 1px solid #edf0f5; border-left-width: 4px; border-radius: 8px; padding: 12px; background: #fff; }
.advanced-action.critical { border-left-color: #e74a3b; }
.advanced-action.high { border-left-color: #f6c23e; }
.advanced-action.medium { border-left-color: #36b9cc; }
.advanced-action.low { border-left-color: #858796; }
.seo-file-health { border: 1px solid #edf0f5; border-radius: 8px; padding: 12px; height: 100%; }
.seo-status-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
</style>

<div class="mm-hero mm-hero--seo">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M11 7v4l3 2"/></svg>
            </div>
            <div>
                <h2>{{ translate('AI SEO Suite') }}</h2>
                <p>{{ $project->name }} — {{ $project->base_url }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-robot"></i> {{ count($providers) }} AI {{ translate('providers') }}</span>
                    <span class="mm-chip"><i class="las la-puzzle-piece"></i> {{ $totalFeatures }} {{ translate('features') }}</span>
                    <span class="mm-chip"><i class="las la-bullseye"></i> {{ translate('Score') }} {{ $seoScore }}/100</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
            <a href="{{ route('admin.seo-suite.ai_assistant') }}" class="mm-btn mm-btn-light">
                <i class="las la-robot"></i> {{ translate('AI Assistant') }}
            </a>
            <a href="{{ route('admin.seo.ai_board.index') }}" class="mm-btn mm-btn-light">
                <i class="las la-brain"></i> {{ translate('AI Board') }}
            </a>
            <a href="{{ route('admin.seo-suite.settings.view') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-cog"></i> {{ translate('Settings') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

@if(!empty($setupRequired))
<div class="alert alert-warning d-flex align-items-center">
    <i class="las la-exclamation-triangle mr-2 la-lg"></i>
    <div>{{ translate('SEO suite database tables are missing. Run the four SEO migrations first.') }}</div>
</div>
@endif

{{-- ROW 1: Setup Wizard + SEO Site Score --}}
<div class="row gutters-16 mb-4">
    {{-- Setup Card --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-600">{{ translate('SEO Setup') }}</h6>
                <span class="badge badge-soft-primary">{{ $completedSteps }} / {{ $totalSteps }}</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mr-3 flex-shrink-0"
                                 style="width:48px;height:48px;background:rgba(78,115,223,.1);">
                                <i class="las la-search la-lg text-primary"></i>
                            </div>
                            <div>
                                <strong>{{ translate('Step') }} {{ $completedSteps }} {{ translate('of') }} {{ $totalSteps }}</strong>
                                <p class="mb-0 small text-muted">
                                    @if($setupPct < 100)
                                        {{ translate('Complete setup to maximize your SEO rankings.') }}
                                    @else
                                        {{ translate('Your site is fully SEO-configured. Keep optimizing!') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="progress mb-3" style="height:8px; border-radius:4px;">
                            <div class="progress-bar bg-primary" style="width:{{ $setupPct }}%; border-radius:4px;"></div>
                        </div>
                        <a href="{{ route('admin.seo-suite.settings.view') }}" class="btn btn-primary btn-sm">
                            <i class="las la-rocket mr-1"></i>{{ translate('Complete SEO Setup') }}
                        </a>
                    </div>
                    <div class="col-md-5">
                        @foreach($setupSteps as $step)
                        <div class="setup-step {{ $step['done'] ? 'done' : 'pending' }} d-flex align-items-center mb-1">
                            <span class="step-dot"></span>
                            <small class="{{ $step['done'] ? 'text-success' : 'text-muted' }}">{{ $step['label'] }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SEO Site Score --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-600">{{ translate('SEO Site Score') }}</h6>
                <a href="{{ route('admin.seo-suite.revisions') }}" class="btn btn-xs btn-soft-primary">{{ translate('Full Report') }} →</a>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-5 text-center">
                        {{-- Circular Score Ring --}}
                        <div class="seo-score-ring mx-auto">
                            <svg width="140" height="140" viewBox="0 0 140 140">
                                <circle cx="70" cy="70" r="56" fill="none" stroke="#f0f0f0" stroke-width="12"/>
                                <circle cx="70" cy="70" r="56" fill="none" stroke="{{ $scoreColor }}" stroke-width="12"
                                        stroke-dasharray="{{ round(2 * 3.14159 * 56) }}"
                                        stroke-dashoffset="{{ round(2 * 3.14159 * 56 * (1 - $seoScore / 100)) }}"
                                        stroke-linecap="round"/>
                            </svg>
                            <div class="score-text">
                                <div class="h2 mb-0 font-weight-bold" style="color:{{ $scoreColor }};">{{ $seoScore }}</div>
                                <small class="text-muted">/ 100</small>
                                <div class="small font-weight-600" style="color:{{ $scoreColor }};">
                                    @if($seoScore >= 80) Excellent!
                                    @elseif($seoScore >= 60) Good
                                    @elseif($seoScore >= 40) Needs Work
                                    @else Poor @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="mb-3">
                            @php $goodCount = $runs->where('status','completed')->count(); @endphp
                            <div class="d-flex align-items-center mb-2">
                                <span class="rounded-circle bg-success d-inline-block mr-2" style="width:10px;height:10px;"></span>
                                <span class="mr-2 text-success font-weight-600">{{ $goodCount }}</span>
                                <span class="text-muted small">{{ translate('Completed Runs') }}</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="rounded-circle bg-warning d-inline-block mr-2" style="width:10px;height:10px;"></span>
                                <span class="mr-2 text-warning font-weight-600">{{ $runsQueued }}</span>
                                <span class="text-muted small">{{ translate('Queued / Running') }}</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="rounded-circle bg-danger d-inline-block mr-2" style="width:10px;height:10px;"></span>
                                <span class="mr-2 text-danger font-weight-600">{{ $runsFailed }}</span>
                                <span class="text-muted small">{{ translate('Failed') }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.seo_optimization.index') }}" class="btn btn-soft-primary btn-sm btn-block">
                            {{ translate('Complete Site Audit') }} →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ADVANCED COMMAND CENTER --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h5 class="mb-0 h6">{{ translate('Advanced SEO Command Center') }}</h5>
            <small class="text-muted">{{ translate('Automation readiness, run quality, technical file health, and priority actions in one view.') }}</small>
        </div>
        <span class="badge badge-{{ $riskLevel === 'low' ? 'success' : ($riskLevel === 'medium' ? 'warning' : 'danger') }} text-uppercase">
            {{ translate('Risk') }}: {{ translate(ucfirst($riskLevel)) }}
        </span>
    </div>
    <div class="card-body">
        @php
            $activeBatch = $autopilot['active_batch'] ?? null;
            $activeStats = $activeBatch ? ($activeBatch->options['seo_stats'] ?? []) : [];
            $activeStatsTracked = !empty($activeStats['last_checked_at']);
            $activeLastResults = $activeBatch ? collect($activeBatch->options['last_results'] ?? [])->take(8) : collect();
        @endphp
        <div class="row gutters-16 mb-4">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small">{{ translate('Automation Readiness') }}</span>
                        <i class="las la-rocket text-primary la-lg"></i>
                    </div>
                    <div class="metric-value text-primary mt-3">{{ $automationReady }}%</div>
                    <div class="progress mt-3" style="height:6px;">
                        <div class="progress-bar bg-primary" style="width:{{ $automationReady }}%;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small">{{ translate('Run Success Rate') }}</span>
                        <i class="las la-check-double text-success la-lg"></i>
                    </div>
                    <div class="metric-value text-success mt-3">{{ $successRate }}%</div>
                    <small class="text-muted">{{ $runsCompleted }} {{ translate('completed') }} / {{ $runsTotal }} {{ translate('recent') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small">{{ translate('Score Momentum') }}</span>
                        <i class="las la-chart-line text-info la-lg"></i>
                    </div>
                    <div class="metric-value mt-3 {{ $trendDelta >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $trendDelta >= 0 ? '+' : '' }}{{ $trendDelta }}
                    </div>
                    <small class="text-muted">{{ translate('points across recent history') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="advanced-metric">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small">{{ translate('Average Run Time') }}</span>
                        <i class="las la-stopwatch text-warning la-lg"></i>
                    </div>
                    <div class="metric-value text-warning mt-3">
                        @if(!is_null($advancedDashboard['avg_duration'] ?? null))
                            {{ $advancedDashboard['avg_duration'] }}s
                        @else
                            -
                        @endif
                    </div>
                    <small class="text-muted">{{ translate('from completed timed runs') }}</small>
                </div>
            </div>
        </div>

        <div class="row gutters-16">
            <div class="col-lg-5 mb-3 mb-lg-0">
                <h6 class="font-weight-600 mb-3">{{ translate('Priority Action Queue') }}</h6>
                @forelse($advancedActions as $action)
                    <div class="advanced-action {{ $action['severity'] }} mb-2">
                        <div class="d-flex align-items-start">
                            <i class="las {{ $action['icon'] }} la-lg mr-2 text-primary"></i>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="small">{{ translate($action['title']) }}</strong>
                                    <span class="badge badge-soft-secondary text-uppercase">{{ translate($action['severity']) }}</span>
                                </div>
                                <p class="small text-muted mb-2">{{ translate($action['detail']) }}</p>
                                @if(($action['method'] ?? 'get') === 'post')
                                    <form action="{{ route($action['route']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-xs btn-soft-primary">{{ translate('Run Now') }}</button>
                                    </form>
                                @else
                                    <a href="{{ route($action['route'], $action['params'] ?? []) }}" class="btn btn-xs btn-soft-primary">{{ translate('Open') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-success py-4 border rounded">
                        <i class="las la-check-circle la-2x d-block mb-2"></i>
                        <span class="small">{{ translate('No urgent SEO actions detected.') }}</span>
                    </div>
                @endforelse
            </div>
            <div class="col-lg-7">
                <div class="row gutters-12">
                    <div class="col-md-5 mb-3">
                        <h6 class="font-weight-600 mb-3">{{ translate('AI Provider Readiness') }}</h6>
                        @foreach($providerHealth as $provider => $ready)
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-uppercase">{{ $provider }}</span>
                                <span class="badge badge-{{ $ready ? 'success' : 'secondary' }}">
                                    {{ $ready ? translate('Ready') : translate('Missing Key') }}
                                </span>
                            </div>
                        @endforeach
                        <a href="{{ route('admin.seo-suite.settings.view') }}" class="btn btn-xs btn-soft-primary mt-2">
                            <i class="las la-sliders-h mr-1"></i>{{ translate('Manage Providers') }}
                        </a>
                    </div>
                    <div class="col-md-7">
                        <h6 class="font-weight-600 mb-3">{{ translate('Technical File Health') }}</h6>
                        <div class="row gutters-8">
                            @foreach($advancedFiles as $file)
                                <div class="col-sm-6 mb-2">
                                    <div class="seo-file-health">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="las {{ $file['icon'] }} mr-1 text-primary"></i>
                                                <strong class="small">{{ translate($file['label']) }}</strong>
                                            </div>
                                            <span class="seo-status-dot bg-{{ $file['exists'] ? 'success' : 'danger' }}"></span>
                                        </div>
                                        <div class="small text-muted mt-2">
                                            @if($file['exists'])
                                                {{ number_format(($file['size'] ?? 0) / 1024, 1) }} KB
                                                <span class="d-block">{{ $file['updated_at'] }}</span>
                                            @else
                                                {{ translate('Not generated') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SEO AUTOPILOT CENTER --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h5 class="mb-0 h6">{{ translate('SEO Autopilot Center') }}</h5>
            <small class="text-muted">{{ translate('Fully automated Canada SEO for pending URLs only. Completed SEO URLs stay protected.') }}</small>
        </div>
        <div class="d-flex flex-wrap mt-2 mt-md-0" style="gap:.4rem;">
            <span class="badge badge-{{ !empty($autopilot['enabled']) ? 'success' : 'secondary' }}">
                {{ !empty($autopilot['enabled']) ? translate('Autopilot ON') : translate('Autopilot OFF') }}
            </span>
            <span class="badge badge-soft-info">{{ translate('Next Run') }}: {{ $autopilot['next_run'] ?? 'Daily 02:45' }}</span>
            <span class="badge badge-{{ $offpageAutoOn ? 'success' : 'secondary' }}">{{ translate('Off-Page') }}: {{ $offpageAutoOn ? translate('ON') : translate('OFF') }}</span>
            <span class="badge badge-soft-info">{{ translate('Off-Page Run') }}: {{ translate('Daily 03:10') }}</span>
            <span class="badge badge-soft-secondary">{{ translate('Queue') }}: {{ $autopilot['queue_driver'] ?? '-' }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row gutters-16 mb-4">
            <div class="col-md-3 mb-3">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('URLs Per Auto Run') }}</span>
                    <div class="metric-value text-primary mt-3">{{ $autopilot['batch_size'] ?? 10 }}</div>
                    <small class="text-muted">{{ translate('max pending URLs') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Pending Queue') }}</span>
                    <div class="metric-value text-danger mt-3">{{ $autopilot['pending_total'] ?? 0 }}</div>
                    <small class="text-muted">{{ translate('protected done URLs excluded') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Completion Forecast') }}</span>
                    <div class="metric-value text-info mt-3">
                        @if(!is_null($autopilot['days_to_completion'] ?? null))
                            {{ $autopilot['days_to_completion'] }}
                        @else
                            -
                        @endif
                    </div>
                    <small class="text-muted">{{ translate('scheduled run days') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('AI Spend Today') }}</span>
                    <div class="metric-value text-warning mt-3">${{ number_format($autopilot['spent_today'] ?? 0, 4) }}</div>
                    <small class="text-muted">
                        @if(($autopilot['budget_cap'] ?? 0) > 0)
                            {{ translate('cap') }} ${{ number_format($autopilot['budget_cap'], 2) }}
                        @else
                            {{ translate('no cap') }}
                        @endif
                    </small>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Active Batch') }}</span>
                    <div class="metric-value {{ !empty($autopilot['active_batch']) ? 'text-info' : 'text-success' }} mt-3">
                        @if(!empty($autopilot['active_batch']))
                            #{{ $autopilot['active_batch']->id }}
                        @else
                            {{ translate('Clear') }}
                        @endif
                    </div>
                    <small class="text-muted">
                        @if(!empty($autopilot['active_batch']))
                            {{ ucfirst($autopilot['active_batch']->status) }} - {{ $autopilot['active_batch']->progressPercent() }}%
                            @if($activeStatsTracked)
                                <span class="d-block">
                                    {{ (int) ($activeStats['improved'] ?? 0) }} {{ translate('improved') }}
                                    / {{ (int) ($activeStats['seo_done'] ?? 0) }} {{ translate('done') }}
                                    / {{ (int) ($activeStats['no_gain'] ?? 0) }} {{ translate('no gain') }}
                                </span>
                            @else
                                <span class="d-block">{{ translate('Impact tracking starts on next chunk') }}</span>
                            @endif
                        @else
                            {{ translate('ready for next run') }}
                        @endif
                    </small>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Next Run Estimate') }}</span>
                    <div class="metric-value text-primary mt-3">{{ $autopilot['next_run_count'] ?? 0 }}</div>
                    <small class="text-muted">
                        ${{ number_format($autopilot['next_run_estimated_cost'] ?? 0, 4) }}
                        {{ !empty($autopilot['next_run_ai_call']) ? translate('AI') : translate('template') }}
                    </small>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Competitors Tracked') }}</span>
                    <div class="metric-value {{ $competitorCount > 0 ? 'text-info' : 'text-muted' }} mt-3">{{ $competitorCount }}</div>
                    <small class="text-muted">{{ translate('used for gap angles') }}</small>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Off-Page Autopilot') }}</span>
                    <div class="metric-value {{ $offpageAutoOn ? 'text-success' : 'text-muted' }} mt-3">{{ $offpageAutoOn ? translate('ON') : translate('OFF') }}</div>
                    <small class="text-muted">{{ $settings['auto_offpage_batch_size'] ?? 3 }} {{ translate('campaigns/run') }} - {{ $autopilot['offpage_ready_count'] ?? 0 }} {{ translate('ready shown') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Protection Rule') }}</span>
                    <div class="metric-value text-success mt-3"><i class="las la-lock"></i></div>
                    <small class="text-muted">{{ translate('SEO done URLs are skipped') }}</small>
                </div>
            </div>
        </div>

        <div class="row gutters-16">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <h6 class="font-weight-600 mb-3">{{ translate('Pending Breakdown') }}</h6>
                @forelse(($autopilot['breakdown'] ?? []) as $type => $item)
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <strong class="small">{{ translate($item['label']) }}</strong>
                            <span class="small text-muted">{{ $item['done'] }} / {{ $item['total'] }} {{ translate('done') }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" style="width:{{ $item['completion'] }}%;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1 small text-muted">
                            <span>{{ $item['pending'] }} {{ translate('pending') }}</span>
                            <span>{{ $item['missing_meta'] }} {{ translate('missing meta') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">{{ translate('No breakdown available until SEO tables are ready.') }}</div>
                @endforelse
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="font-weight-600 mb-0">{{ translate('Recent Automation Batches') }}</h6>
                    <a href="{{ route('admin.seo.monitoring.index') }}" class="btn btn-xs btn-soft-primary">{{ translate('Monitoring') }}</a>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Batch') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Progress') }}</th>
                                <th>{{ translate('Impact') }}</th>
                                <th>{{ translate('Cost') }}</th>
                                <th>{{ translate('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($autopilot['recent_batches'] ?? collect()) as $batch)
                                @php
                                    $batchStats = $batch->options['seo_stats'] ?? [];
                                    $batchStatsTracked = !empty($batchStats['last_checked_at']);
                                @endphp
                                <tr>
                                    <td class="small">#{{ $batch->id }}</td>
                                    <td>
                                        <span class="badge badge-{{ $batch->status === 'completed' ? 'success' : (in_array($batch->status, ['failed','cancelled']) ? 'danger' : 'warning') }}">
                                            {{ $batch->status }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $batch->processed }}/{{ $batch->total }} ({{ $batch->progressPercent() }}%)</td>
                                    <td class="small">
                                        @if($batchStatsTracked)
                                            <span class="text-success">{{ (int) ($batchStats['improved'] ?? 0) }} {{ translate('improved') }}</span>
                                            <span class="d-block text-info">{{ (int) ($batchStats['seo_done'] ?? 0) }} {{ translate('done') }}</span>
                                            @if((int) ($batchStats['no_gain'] ?? 0) > 0)
                                                <span class="d-block text-muted">{{ (int) ($batchStats['no_gain'] ?? 0) }} {{ translate('no gain') }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ translate('not tracked yet') }}</span>
                                        @endif
                                    </td>
                                    <td class="small">${{ number_format((float) $batch->actual_cost_usd, 4) }}</td>
                                    <td class="small text-muted">{{ optional($batch->created_at)->format('M d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">{{ translate('No automation batches yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($activeLastResults->isNotEmpty())
            <div class="mt-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="font-weight-600 mb-0">{{ translate('Active Batch Latest URL Results') }}</h6>
                        <small class="text-muted">{{ translate('Live batch outcomes from the current automated chunk processor.') }}</small>
                    </div>
                    <span class="badge badge-soft-primary">#{{ $activeBatch->id }}</span>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Before / After') }}</th>
                                <th>{{ translate('Provider') }}</th>
                                <th>{{ translate('Result') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeLastResults as $row)
                                @php
                                    $delta = (int) ($row['delta'] ?? 0);
                                    $deltaClass = $delta > 0 ? 'success' : ($delta < 0 ? 'danger' : 'warning');
                                @endphp
                                <tr>
                                    <td class="text-truncate" style="max-width:420px;">
                                        <a href="{{ $row['url'] ?? '#' }}" target="_blank" class="small">{{ $row['title'] ?? $row['label'] ?? '-' }}</a>
                                        <span class="d-block small text-muted">{{ $row['url'] ?? '-' }}</span>
                                    </td>
                                    <td><span class="badge badge-soft-info">{{ ucfirst($row['type'] ?? '-') }}</span></td>
                                    <td>
                                        <span class="badge badge-{{ $deltaClass }}">
                                            {{ (int) ($row['before'] ?? 0) }}/100 -> {{ (int) ($row['after'] ?? 0) }}/100
                                            ({{ $delta > 0 ? '+' : '' }}{{ $delta }})
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $row['provider'] ?? $row['source'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ !empty($row['seo_done']) ? 'success' : 'warning' }}">
                                            {{ !empty($row['seo_done']) ? translate('SEO Done') : translate('Still Pending') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        <div class="mt-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div>
                    <h6 class="font-weight-600 mb-0">{{ translate('Last 60 Minutes Real SEO Improvements') }}</h6>
                    <small class="text-muted">{{ translate('Shows only URLs where score increased or reached the SEO-done threshold.') }}</small>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge badge-soft-success">{{ $autopilot['score_improved_last_hour'] ?? 0 }} {{ translate('improved') }}</span>
                    <span class="badge badge-soft-info">{{ $autopilot['score_done_last_hour'] ?? 0 }} {{ translate('SEO done') }}</span>
                    <span class="badge badge-soft-secondary">{{ $autopilot['score_rescored_no_change_last_hour'] ?? 0 }} {{ translate('rescored no change') }}</span>
                </div>
            </div>
            <div class="table-responsive border rounded">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('URL') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Score Change') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($autopilot['recent_score_changes'] ?? collect()) as $row)
                            @php
                                $delta = $row['delta'];
                                $deltaClass = is_null($delta) ? 'secondary' : ($delta > 0 ? 'success' : ($delta < 0 ? 'danger' : 'warning'));
                            @endphp
                            <tr>
                                <td class="text-truncate" style="max-width:420px;">
                                    <a href="{{ $row['url'] }}" target="_blank" class="small">{{ $row['title'] }}</a>
                                    <span class="d-block small text-muted">{{ $row['url'] }}</span>
                                </td>
                                <td><span class="badge badge-soft-info">{{ ucfirst($row['type']) }}</span></td>
                                <td>
                                    <span class="badge badge-{{ $deltaClass }}">
                                        @if(is_null($delta))
                                            {{ $row['score_after'] }}/100
                                        @else
                                            {{ $row['score_before'] }}/100 -> {{ $row['score_after'] }}/100
                                            ({{ $delta > 0 ? '+' : '' }}{{ $delta }})
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ !empty($row['seo_done']) ? 'success' : 'warning' }}">
                                        {{ !empty($row['seo_done']) ? translate('SEO Done') : translate('Still Pending') }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ optional($row['recorded_at'])->format('M d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('No real score improvements in the last 60 minutes. No-change rescans are counted above but hidden from this table.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="alert alert-success mt-3 mb-0 py-2 small">
            <i class="las la-bolt mr-1"></i>
            {{ translate('Next-level automation: pending URLs are now ranked by missing SEO fields, weak score, page value, and Canada/GTA opportunity before each run. SEO-done URLs remain locked out of autopilot changes.') }}
        </div>
    </div>
</div>

{{-- AUTOPILOT NEXT TARGETS --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-0 h6">{{ translate('Autopilot Next Targets Preview') }}</h5>
            <small class="text-muted">{{ translate('Smart priority queue for the next automated run. Done SEO URLs are excluded.') }}</small>
        </div>
        <a href="{{ route('admin.seo.ai_board.index', ['missing' => 'meta', 'sort' => 'score_asc']) }}" class="btn btn-xs btn-soft-primary">
            {{ translate('Open AI Board') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Priority') }}</th>
                        <th>{{ translate('URL') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Score') }}</th>
                        <th>{{ translate('Why Selected') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($autopilot['next_targets'] ?? collect()) as $row)
                        @php
                            $priorityColor = $row['priority_label'] === 'Critical' ? 'danger' : ($row['priority_label'] === 'High' ? 'warning' : ($row['priority_label'] === 'Medium' ? 'info' : 'secondary'));
                        @endphp
                        <tr>
                            <td>
                                <span class="badge badge-{{ $priorityColor }}">{{ $row['priority_label'] }}</span>
                                <span class="d-block small text-muted">{{ $row['priority_score'] }}/100</span>
                            </td>
                            <td class="text-truncate" style="max-width:360px;">
                                <a href="{{ $row['url'] }}" target="_blank" class="small">{{ $row['title'] }}</a>
                                <span class="d-block small text-muted">{{ $row['url'] }}</span>
                            </td>
                            <td><span class="badge badge-soft-info">{{ ucfirst($row['type']) }}</span></td>
                            <td><span class="badge badge-{{ $row['score'] >= 70 ? 'success' : ($row['score'] >= 50 ? 'warning' : 'danger') }}">{{ $row['score'] }}/100</span></td>
                            <td class="small text-muted">{{ implode(', ', $row['priority_reasons'] ?? []) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ translate('No pending autopilot targets found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- OFF-PAGE READY TARGETS --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-0 h6">{{ translate('Off-Page Ready Backlink Targets') }}</h5>
            <small class="text-muted">{{ translate('Only SEO-ready protected URLs are eligible. Pending pages are excluded until on-page SEO is complete.') }}</small>
        </div>
        <a href="{{ route('admin.seo_off_page.index') }}" class="btn btn-xs btn-soft-success">
            {{ translate('Open Off-Page') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Authority') }}</th>
                        <th>{{ translate('URL') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('SEO Score') }}</th>
                        <th>{{ translate('Why Ready') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($autopilot['offpage_targets'] ?? collect()) as $row)
                        @php
                            $offpageScore = (int) ($row['offpage_score'] ?? 0);
                            $offpageColor = $offpageScore >= 90 ? 'success' : ($offpageScore >= 75 ? 'primary' : ($offpageScore >= 60 ? 'info' : 'secondary'));
                        @endphp
                        <tr>
                            <td>
                                <span class="badge badge-{{ $offpageColor }}">{{ $row['offpage_label'] ?? translate('Ready') }}</span>
                                <span class="d-block small text-muted">{{ $offpageScore }}/100</span>
                            </td>
                            <td class="text-truncate" style="max-width:360px;">
                                <a href="{{ $row['url'] }}" target="_blank" class="small">{{ $row['title'] }}</a>
                                <span class="d-block small text-muted">{{ $row['url'] }}</span>
                            </td>
                            <td><span class="badge badge-soft-success">{{ ucfirst($row['type']) }}</span></td>
                            <td><span class="badge badge-success">{{ $row['score'] }}/100</span></td>
                            <td class="small text-muted">{{ implode(', ', $row['offpage_reasons'] ?? []) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ translate('No SEO-ready off-page targets found. Run on-page autopilot first.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- SEO URL INVENTORY --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h5 class="mb-0 h6">{{ translate('SEO URL Inventory') }}</h5>
            <small class="text-muted">{{ translate('Completed SEO URLs with score, plus pending URLs that still need metadata, schema, keywords, or content.') }}</small>
        </div>
        <form action="{{ route('admin.seo-suite.bulk_pending') }}" method="POST" class="d-flex flex-wrap align-items-center mt-2 mt-md-0" style="gap:.4rem;">
            @csrf
            <select name="limit" class="form-control form-control-sm" style="width:90px;">
                <option value="5">5 URLs</option>
                <option value="10" selected>10 URLs</option>
            </select>
            <select name="provider" class="form-control form-control-sm" style="width:150px;">
                <option value="">{{ translate('Default AI') }}</option>
                @foreach($providers as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm">
                <i class="las la-magic mr-1"></i>{{ translate('Generate Canada SEO') }}
            </button>
        </form>
    </div>
    <div class="card-body">
        <div class="row gutters-16 mb-3">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Total Crawlable URLs') }}</span>
                    <div class="metric-value text-primary mt-3">{{ $urlInventory['total_count'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('SEO Done URLs') }}</span>
                    <div class="metric-value text-success mt-3">{{ $urlInventory['done_count'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="advanced-metric">
                    <span class="text-muted small">{{ translate('Pending SEO URLs') }}</span>
                    <div class="metric-value text-danger mt-3">{{ $urlInventory['pending_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="row gutters-16">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="font-weight-600 mb-0">{{ translate('Already Done SEO URLs') }}</h6>
                    <a href="{{ route('admin.seo.ai_board.index', ['min_score' => 70, 'sort' => 'score_desc']) }}" class="btn btn-xs btn-soft-success">{{ translate('View All') }}</a>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Score') }}</th>
                                <th>{{ translate('SEO Done') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($urlInventory['done'] ?? collect()) as $row)
                                <tr>
                                    <td class="text-truncate" style="max-width:240px;">
                                        <a href="{{ $row['url'] }}" target="_blank" class="small">{{ $row['title'] }}</a>
                                        <span class="d-block text-muted small">{{ $row['url'] }}</span>
                                    </td>
                                    <td><span class="badge badge-soft-info">{{ ucfirst($row['type']) }}</span></td>
                                    <td><span class="badge badge-success">{{ $row['score'] }}/100</span></td>
                                    <td class="small">
                                        <span class="text-success"><i class="las la-check-circle"></i> {{ translate('Meta') }}</span>
                                        @if($row['has_schema'])
                                            <span class="text-success d-block"><i class="las la-check-circle"></i> {{ translate('Schema') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No completed SEO URLs found yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="font-weight-600 mb-0">{{ translate('Non-SEO / Pending URLs') }}</h6>
                    <a href="{{ route('admin.seo.ai_board.index', ['missing' => 'meta', 'sort' => 'score_asc']) }}" class="btn btn-xs btn-soft-danger">{{ translate('Fix Board') }}</a>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Score') }}</th>
                                <th>{{ translate('Missing') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($urlInventory['pending'] ?? collect()) as $row)
                                <tr>
                                    <td class="text-truncate" style="max-width:240px;">
                                        <a href="{{ $row['url'] }}" target="_blank" class="small">{{ $row['title'] }}</a>
                                        <span class="d-block text-muted small">{{ $row['url'] }}</span>
                                    </td>
                                    <td><span class="badge badge-soft-warning">{{ ucfirst($row['type']) }}</span></td>
                                    <td><span class="badge badge-{{ $row['score'] >= 50 ? 'warning' : 'danger' }}">{{ $row['score'] }}/100</span></td>
                                    <td class="small text-muted">
                                        @if(!$row['has_meta']) {{ translate('Meta') }} @endif
                                        @if(!$row['has_focus_kw']) <span class="d-block">{{ translate('Focus keyword') }}</span> @endif
                                        @if(!$row['has_schema']) <span class="d-block">{{ translate('Schema') }}</span> @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No pending URLs found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="alert alert-info mt-3 mb-0 py-2 small">
            <i class="las la-info-circle mr-1"></i>
            {{ translate('Bulk generation uses product, category, and page-specific algorithms. It now prioritizes Mississauga, Brampton, and Toronto first, then Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, Burlington, Trade Account, and Leave a Review intent where natural.') }}
        </div>
    </div>
</div>

{{-- ROW 2: TruSEO Overview + Notifications --}}
<div class="row gutters-16 mb-4">
    {{-- TruSEO Overview --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-600">{{ translate('AI SEO Suite Overview') }}</h6>
                <span class="badge badge-soft-info">{{ $totalFeatures }} {{ translate('features') }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ translate('Below are TruSEO scores from your recent optimization runs. Improve your scores to increase rankings.') }}</p>
                <div class="row align-items-center">
                    <div class="col-md-5 text-center">
                        {{-- Mini donut --}}
                        <div class="position-relative mx-auto mb-3" style="width:120px;height:120px;">
                            <svg width="120" height="120" viewBox="0 0 120 120" style="transform:rotate(-90deg)">
                                <circle cx="60" cy="60" r="48" fill="none" stroke="#f0f0f0" stroke-width="10"/>
                                @if($runsTotal > 0)
                                <circle cx="60" cy="60" r="48" fill="none" stroke="#1cc88a" stroke-width="10"
                                    stroke-dasharray="{{ round(2*3.14159*48) }}"
                                    stroke-dashoffset="{{ round(2*3.14159*48*(1-$runsCompleted/$runsTotal)) }}"
                                    stroke-linecap="round"/>
                                @endif
                            </svg>
                            <div class="position-absolute" style="top:50%;left:50%;transform:translate(-50%,-50%); text-align:center;">
                                <div class="h4 mb-0 font-weight-bold text-success">{{ $runsTotal }}</div>
                                <small class="text-muted">{{ translate('Total Runs') }}</small>
                            </div>
                        </div>
                        <div class="small">
                            <span class="text-success mr-2"><i class="las la-circle" style="font-size:10px;"></i> {{ $runsCompleted }} {{ translate('Good') }}</span>
                            <span class="text-warning mr-2"><i class="las la-circle" style="font-size:10px;"></i> {{ $runsQueued }} {{ translate('Pending') }}</span>
                            <span class="text-danger"><i class="las la-circle" style="font-size:10px;"></i> {{ $runsFailed }} {{ translate('Issues') }}</span>
                        </div>
                    </div>
                    <div class="col-md-7">
                        {{-- Module breakdown --}}
                        @foreach(['on_page' => ['On-Page SEO', 'primary', 'la-file-alt'], 'off_page' => ['Off-Page SEO', 'success', 'la-link'], 'optimization' => ['Optimization', 'info', 'la-cog']] as $mod => $meta)
                        @php $modRuns = $runs->where('module', $mod); @endphp
                        <div class="card module-card {{ str_replace('_','-',$mod) }} shadow-sm mb-2">
                            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="las {{ $meta[2] }} text-{{ $meta[1] }} mr-2"></i>
                                    <span class="small font-weight-600">{{ translate($meta[0]) }}</span>
                                </div>
                                <div>
                                    <span class="badge badge-soft-{{ $meta[1] }}">{{ count($features[$mod] ?? []) }} {{ translate('tools') }}</span>
                                    <span class="badge badge-soft-success ml-1">{{ $modRuns->where('status','completed')->count() }} ✓</span>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        <div class="mt-3">
                            <a href="{{ route('admin.seo-suite.index', ['tab' => 'run']) }}" class="btn btn-primary btn-sm mr-1">
                                <i class="las la-play mr-1"></i>{{ translate('Run SEO Task') }}
                            </a>
                            <a href="{{ route('admin.seo_optimization.index') }}" class="btn btn-soft-info btn-sm">
                                {{ translate('Optimization Tools') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notifications & Support --}}
    <div class="col-lg-5">
        {{-- Notifications --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-600">{{ translate('SEO Notifications') }}</h6>
                @if($setupPct < 100)
                    <span class="badge badge-danger">{{ $totalSteps - $completedSteps }} {{ translate('actions') }}</span>
                @endif
            </div>
            <div class="card-body p-2">
                @php $notifs = []; @endphp
                @if(empty($settings['openai_api_key']) && empty($settings['anthropic_api_key']))
                    @php $notifs[] = ['type' => 'danger', 'icon' => 'la-key', 'text' => 'No AI API key configured. Add an OpenAI or Claude key in Settings.']; @endphp
                @endif
                @if(!file_exists(base_path('sitemap.xml')))
                    @php $notifs[] = ['type' => 'warning', 'icon' => 'la-sitemap', 'text' => 'Sitemap not generated yet. Click Generate Sitemap.']; @endphp
                @endif
                @if(!file_exists(public_path('llms.txt')))
                    @php $notifs[] = ['type' => 'info', 'icon' => 'la-file-code', 'text' => 'LLMs.txt not created. Generate it to help AI crawlers understand your site.']; @endphp
                @endif
                @if(empty($settings['google_verification']))
                    @php $notifs[] = ['type' => 'info', 'icon' => 'la-search', 'text' => 'Google Search Console not verified. Add your verification code.']; @endphp
                @endif
                @if(empty($settings['indexnow_key']))
                    @php $notifs[] = ['type' => 'info', 'icon' => 'la-bolt', 'text' => 'IndexNow key not set. Set it to submit URLs to Bing instantly.']; @endphp
                @endif

                @forelse($notifs as $notif)
                <div class="notification-item bg-{{ $notif['type'] === 'danger' ? 'danger' : ($notif['type'] === 'warning' ? 'warning' : 'light') }}
                    {{ in_array($notif['type'], ['danger','warning']) ? 'text-white' : '' }} rounded p-2 mb-2">
                    <div class="d-flex align-items-start">
                        <i class="las {{ $notif['icon'] }} mr-2 mt-1 flex-shrink-0"></i>
                        <small>{{ $notif['text'] }}</small>
                    </div>
                </div>
                @empty
                <div class="text-center py-2 text-success">
                    <i class="las la-check-circle la-lg d-block mb-1"></i>
                    <small>{{ translate('All SEO checks passed!') }}</small>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Support / Quick Links --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-600">{{ translate('Quick Navigation') }}</h6>
            </div>
            <div class="card-body p-2">
                @foreach([
                    ['route' => 'admin.seo_on_page.index',        'icon' => 'la-file-alt',    'label' => 'On-Page SEO Tools'],
                    ['route' => 'admin.seo_off_page.index',       'icon' => 'la-link',        'label' => 'Off-Page SEO Tools'],
                    ['route' => 'admin.seo_optimization.index',   'icon' => 'la-cog',         'label' => 'Optimization Tools'],
                    ['route' => 'admin.seo.ai_board.index',       'icon' => 'la-brain',       'label' => 'AI SEO Board'],
                    ['route' => 'admin.seo.monitoring.index',     'icon' => 'la-heartbeat',   'label' => 'SEO Monitoring'],
                    ['route' => 'admin.seo-suite.ai_assistant',   'icon' => 'la-robot',       'label' => 'AI SEO Assistant'],
                    ['route' => 'admin.seo-suite.keyword_tracker','icon' => 'la-chart-line',  'label' => 'Keyword Rank Tracker'],
                    ['route' => 'admin.seo-suite.search_stats',   'icon' => 'la-chart-bar',   'label' => 'Search Statistics'],
                    ['route' => 'admin.seo-suite.webmaster',      'icon' => 'la-tools',       'label' => 'Webmaster Tools'],
                    ['route' => 'admin.seo-suite.link_assistant', 'icon' => 'la-link',        'label' => 'Link Assistant'],
                    ['route' => 'admin.seo-suite.revisions',      'icon' => 'la-history',     'label' => 'SEO Revisions'],
                    ['route' => 'admin.seo-suite.settings.view',  'icon' => 'la-sliders-h',   'label' => 'Global Settings'],
                ] as $nav)
                <a href="{{ route($nav['route']) }}" class="d-flex align-items-center p-2 rounded mb-1 text-dark" style="transition:.15s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''">
                    <i class="las {{ $nav['icon'] }} mr-2 text-primary"></i>
                    <small>{{ translate($nav['label']) }}</small>
                    <i class="las la-angle-right ml-auto text-muted small"></i>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ROW 3: Run Tools + Settings --}}
<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'run' ? 'active' : '' }}" data-toggle="tab" href="#seo-runner">
                    <i class="las la-play-circle mr-1"></i>{{ translate('Run AI Tools') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'writing' ? 'active' : '' }}" data-toggle="tab" href="#seo-writing">
                    <i class="las la-pen-nib mr-1"></i>{{ translate('AI Writing') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'sitemaps' ? 'active' : '' }}" data-toggle="tab" href="#seo-sitemaps">
                    <i class="las la-sitemap mr-1"></i>{{ translate('Sitemaps & Files') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'redirects' ? 'active' : '' }}" data-toggle="tab" href="#seo-redirects">
                    <i class="las la-exchange-alt mr-1"></i>{{ translate('Redirects') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'indexnow' ? 'active' : '' }}" data-toggle="tab" href="#seo-indexnow">
                    <i class="las la-bolt mr-1"></i>{{ translate('IndexNow') }}
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            {{-- RUN TOOLS --}}
            <div class="tab-pane fade {{ $activeTab === 'run' ? 'show active' : '' }}" id="seo-runner">
                <form action="{{ route('admin.seo-suite.run') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Module') }}</label>
                                <select name="module" class="form-control" id="seo-module-select" required>
                                    <option value="on_page">{{ translate('On-Page SEO') }}</option>
                                    <option value="off_page">{{ translate('Off-Page SEO') }}</option>
                                    <option value="optimization">{{ translate('Optimization & Tools') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Feature') }}</label>
                                <select name="feature" class="form-control" id="seo-feature-select" required>
                                    @foreach($features as $module => $items)
                                        @foreach($items as $key => $label)
                                            <option value="{{ $key }}" data-module="{{ $module }}">{{ $label }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('AI Provider') }}</label>
                                <select name="provider" class="form-control">
                                    @foreach($providers as $val => $label)
                                        <option value="{{ $val }}" @if(($settings['default_provider'] ?? 'openai') === $val) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Target URL') }}</label>
                                <input type="text" class="form-control" name="url" placeholder="https://example.com/page">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Title / Topic') }}</label>
                                <input type="text" class="form-control" name="title" placeholder="Page title">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Primary Keyword') }}</label>
                                <input type="text" class="form-control" name="keyword" placeholder="industrial safety gloves">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Content') }}</label>
                                <textarea class="form-control" rows="3" name="content"></textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Extra JSON Payload') }}</label>
                                <textarea class="form-control" rows="2" name="extra_payload" placeholder='{"score":72,"lcp":2800}'></textarea>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary">
                        <i class="las la-play mr-1"></i>{{ translate('Queue SEO Task') }}
                    </button>
                </form>
            </div>

            {{-- AI WRITING --}}
            <div class="tab-pane fade {{ $activeTab === 'writing' ? 'show active' : '' }}" id="seo-writing">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="form-group">
                            <label>{{ translate('Task') }}</label>
                            <select id="wa-task" class="form-control">
                                <option value="generate">{{ translate('Generate Content') }}</option>
                                <option value="improve">{{ translate('Improve Existing') }}</option>
                                <option value="paraphrase">{{ translate('Paraphrase / Rewrite') }}</option>
                                <option value="expand">{{ translate('Expand Content') }}</option>
                                <option value="summarize">{{ translate('Summarize') }}</option>
                                <option value="meta_description">{{ translate('Write Meta Description') }}</option>
                                <option value="title_variants">{{ translate('Title Variants (5)') }}</option>
                                <option value="faq">{{ translate('Generate FAQ') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Focus Keyword') }}</label>
                            <input type="text" id="wa-keyword" class="form-control" placeholder="safety equipment">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>{{ translate('Tone') }}</label>
                                    <select id="wa-tone" class="form-control">
                                        <option value="professional">Professional</option>
                                        <option value="friendly">Friendly</option>
                                        <option value="persuasive">Persuasive</option>
                                        <option value="informative">Informative</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>{{ translate('Length') }}</label>
                                    <select id="wa-length" class="form-control">
                                        <option value="short">Short</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="long">Long</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Content Type') }}</label>
                            <select id="wa-content-type" class="form-control">
                                <option value="product_description">Product Description</option>
                                <option value="category_description">Category Description</option>
                                <option value="blog_post">Blog Post</option>
                                <option value="landing_page">Landing Page</option>
                                <option value="social_post">Social Post</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Existing Content') }}</label>
                            <textarea id="wa-content" class="form-control" rows="4" placeholder="{{ translate('Paste existing content here...') }}"></textarea>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('AI Provider') }}</label>
                            <select id="wa-provider" class="form-control">
                                @foreach($providers as $val => $label)
                                    <option value="{{ $val }}" @if(($settings['default_provider'] ?? 'openai') === $val) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button id="wa-generate-btn" class="btn btn-primary w-100">
                            <i class="las la-magic mr-1"></i>{{ translate('Generate with AI') }}
                        </button>
                    </div>
                    <div class="col-lg-7">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">{{ translate('Result') }}</h6>
                            <div>
                                <span id="wa-word-count" class="text-muted small mr-2"></span>
                                <button id="wa-copy-btn" class="btn btn-xs btn-soft-success d-none">
                                    <i class="las la-copy mr-1"></i>Copy
                                </button>
                            </div>
                        </div>
                        <div id="wa-loading" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary"></div>
                            <div class="mt-2 text-muted small">{{ translate('Generating...') }}</div>
                        </div>
                        <div id="wa-result-box" class="border rounded p-3 bg-light" style="min-height:320px; white-space:pre-wrap; font-size:0.88rem; line-height:1.6;"></div>
                    </div>
                </div>
            </div>

            {{-- SITEMAPS & FILES --}}
            <div class="tab-pane fade {{ $activeTab === 'sitemaps' ? 'show active' : '' }}" id="seo-sitemaps">
                <div class="row gutters-16">
                    @foreach([
                        ['route' => 'admin.seo-suite.sitemap',       'title' => 'Smart XML Sitemap',   'icon' => 'la-sitemap',    'color' => 'primary', 'desc' => 'All pages, categories, products, blogs'],
                        ['route' => 'admin.seo-suite.sitemap.video', 'title' => 'Video SEO Sitemap',   'icon' => 'la-video',      'color' => 'info',    'desc' => 'Product videos & embedded content'],
                        ['route' => 'admin.seo-suite.sitemap.news',  'title' => 'Blog / News Sitemap', 'icon' => 'la-newspaper',  'color' => 'success', 'desc' => 'Recent blog posts for Google News'],
                        ['route' => 'admin.seo-suite.rss',           'title' => 'RSS Content Feed',    'icon' => 'la-rss',        'color' => 'warning', 'desc' => 'RSS 2.0 feed for aggregators'],
                        ['route' => 'admin.seo-suite.robots',        'title' => 'Robots.txt',          'icon' => 'la-robot',      'color' => 'secondary','desc' => 'AI-optimized robots.txt'],
                        ['route' => 'admin.seo-suite.llms_txt',      'title' => 'LLMs.txt',            'icon' => 'la-file-code',  'color' => 'dark',    'desc' => 'AI crawler instructions'],
                    ] as $btn)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-left border-{{ $btn['color'] }}" style="border-left-width:3px!important;">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="las {{ $btn['icon'] }} text-{{ $btn['color'] }} mr-2 la-lg"></i>
                                    <strong class="small">{{ translate($btn['title']) }}</strong>
                                </div>
                                <p class="text-muted mb-2" style="font-size:0.78rem;">{{ translate($btn['desc']) }}</p>
                                <div class="d-flex">
                                    <form action="{{ route($btn['route']) }}" method="POST" class="mr-1">
                                        @csrf
                                        <button class="btn btn-xs btn-{{ $btn['color'] }}">
                                            <i class="las la-sync mr-1"></i>{{ translate('Generate') }}
                                        </button>
                                    </form>
                                    @php
                                        $fileMap = [
                                            'admin.seo-suite.sitemap'       => '/sitemap.xml',
                                            'admin.seo-suite.sitemap.video' => '/video-sitemap.xml',
                                            'admin.seo-suite.sitemap.news'  => '/news-sitemap.xml',
                                            'admin.seo-suite.rss'           => '/rss.xml',
                                            'admin.seo-suite.robots'        => '/robots.txt',
                                            'admin.seo-suite.llms_txt'      => '/llms.txt',
                                        ];
                                    @endphp
                                    <a href="{{ url($fileMap[$btn['route']] ?? '/') }}" target="_blank" class="btn btn-xs btn-soft-info">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- REDIRECTS --}}
            <div class="tab-pane fade {{ $activeTab === 'redirects' ? 'show active' : '' }}" id="seo-redirects">
                <div class="row">
                    <div class="col-md-5">
                        <h6>{{ translate('Add Redirect') }}</h6>
                        <form action="{{ route('admin.seo-suite.redirects.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ translate('From URL') }}</label>
                                <input type="text" class="form-control" name="source_url" placeholder="/old-page" required>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('To URL') }}</label>
                                <input type="text" class="form-control" name="target_url" placeholder="/new-page" required>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Type') }}</label>
                                <select class="form-control" name="status_code">
                                    <option value="301">301 — Permanent</option>
                                    <option value="302">302 — Temporary</option>
                                    <option value="410">410 — Gone (Deleted)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Notes') }}</label>
                                <input type="text" class="form-control" name="notes" placeholder="Optional reason">
                            </div>
                            <button class="btn btn-primary w-100">{{ translate('Save Redirect') }}</button>
                        </form>
                    </div>
                    <div class="col-md-7">
                        <h6>{{ translate('Redirect Manager') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>{{ translate('From') }}</th><th>{{ translate('To') }}</th><th>{{ translate('Code') }}</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($redirects as $redirect)
                                    <tr>
                                        <td class="text-truncate" style="max-width:140px;">{{ $redirect->source_url }}</td>
                                        <td class="text-truncate" style="max-width:140px;">{{ $redirect->target_url }}</td>
                                        <td><span class="badge badge-soft-{{ $redirect->status_code == 301 ? 'success' : 'warning' }}">{{ $redirect->status_code }}</span></td>
                                        <td>
                                            <form action="{{ route('admin.seo-suite.redirects.delete', $redirect->id) }}" method="POST" onsubmit="return confirm('Delete?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-xs btn-soft-danger">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No redirects yet') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- INDEXNOW --}}
            <div class="tab-pane fade {{ $activeTab === 'indexnow' ? 'show active' : '' }}" id="seo-indexnow">
                <div class="row">
                    <div class="col-md-6">
                        <h6>{{ translate('IndexNow Key') }}</h6>
                        @if(!empty($settings['indexnow_key']))
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" value="{{ $settings['indexnow_key'] }}" readonly>
                                <div class="input-group-append">
                                    <a href="{{ url('/'.$settings['indexnow_key'].'.txt') }}" target="_blank" class="btn btn-soft-info">View File</a>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning py-2 small">{{ translate('No IndexNow key set.') }}</div>
                        @endif
                        <form action="{{ route('admin.seo-suite.indexnow.generate_key') }}" method="POST" class="d-inline mb-3">
                            @csrf <button class="btn btn-sm btn-soft-primary">{{ translate('Generate New Key') }}</button>
                        </form>
                        <hr>
                        <h6>{{ translate('Submit URLs') }}</h6>
                        <form action="{{ route('admin.seo-suite.indexnow') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea class="form-control" name="urls" rows="6" placeholder="{{ url('/') }}/product/example&#10;{{ url('/') }}/category/example"></textarea>
                            </div>
                            <button class="btn btn-primary"><i class="las la-bolt mr-1"></i>{{ translate('Submit to IndexNow') }}</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body small text-muted">
                                <h6>{{ translate('Supported Engines') }}</h6>
                                <ul class="pl-3">
                                    <li><strong>Bing</strong> — api.indexnow.org</li>
                                    <li><strong>Yandex</strong> — yandex.com</li>
                                    <li><strong>Seznam.cz</strong></li>
                                    <li><strong>Naver</strong></li>
                                </ul>
                                <p>{{ translate('IndexNow lets search engines know immediately when your content changes, without waiting for the next crawl.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ROW 4: Recent Runs + Score History --}}
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('Recent SEO Runs') }}</h5>
                <a href="{{ route('admin.seo-suite.revisions') }}" class="btn btn-xs btn-soft-primary">{{ translate('View All') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Module') }}</th>
                                <th>{{ translate('Feature') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Provider') }}</th>
                                <th>{{ translate('Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                            <tr>
                                <td><span class="badge badge-soft-info small">{{ ucwords(str_replace('_',' ',$run->module)) }}</span></td>
                                <td class="small">{{ data_get($features[$run->module] ?? [], $run->feature, $run->feature) }}</td>
                                <td><span class="badge badge-{{ $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">{{ $run->status }}</span></td>
                                <td class="small text-muted">{{ $run->provider }}</td>
                                <td class="small text-muted text-nowrap">{{ optional($run->created_at)->format('M d H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('No runs yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('Score History') }}</h5>
                <a href="{{ route('admin.seo-suite.search_stats') }}" class="btn btn-xs btn-soft-primary">{{ translate('Stats') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>{{ translate('URL') }}</th><th>{{ translate('Score') }}</th><th>{{ translate('Date') }}</th></tr></thead>
                        <tbody>
                            @forelse($histories as $history)
                            <tr>
                                <td class="text-truncate small" style="max-width:180px;">{{ $history->url }}</td>
                                <td>
                                    <span class="badge badge-{{ ($history->score ?? 0) >= 80 ? 'success' : (($history->score ?? 0) >= 50 ? 'warning' : 'danger') }}">
                                        {{ $history->score }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ optional($history->recorded_at)->format('M d') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ translate('No history') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
// Module-feature sync
var seoFeatureOptions = [];
function syncSeoFeatures() {
    var module   = $('#seo-module-select').val();
    var $feature = $('#seo-feature-select');
    $feature.empty();
    seoFeatureOptions.forEach(function(item) {
        if (item.module === module) {
            $feature.append($('<option>', { value: item.value, text: item.label }));
        }
    });
}

$(function() {
    $('#seo-feature-select option').each(function() {
        seoFeatureOptions.push({ module: $(this).data('module'), value: $(this).val(), label: $(this).text() });
    });
    syncSeoFeatures();
    $('#seo-module-select').on('change', syncSeoFeatures);

    // AI Writing Assistant
    $('#wa-generate-btn').on('click', function() {
        var keyword = $('#wa-keyword').val().trim();
        var content = $('#wa-content').val().trim();
        if (!keyword && !content) { alert('Enter a keyword or content.'); return; }

        $('#wa-loading').removeClass('d-none');
        $('#wa-result-box').text('');
        $('#wa-copy-btn').addClass('d-none');
        $('#wa-word-count').text('');
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route('admin.seo-suite.ai_writing') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task: $('#wa-task').val(),
                keyword: keyword,
                content: content,
                tone: $('#wa-tone').val(),
                length: $('#wa-length').val(),
                content_type: $('#wa-content-type').val(),
                provider: $('#wa-provider').val()
            },
            success: function(res) {
                $('#wa-loading').addClass('d-none');
                $('#wa-generate-btn').prop('disabled', false);
                var result = res.result || 'No response.';
                $('#wa-result-box').text(result);
                $('#wa-word-count').text('(' + result.trim().split(/\s+/).length + ' words)');
                $('#wa-copy-btn').removeClass('d-none');
            },
            error: function(xhr) {
                $('#wa-loading').addClass('d-none');
                $('#wa-generate-btn').prop('disabled', false);
                $('#wa-result-box').text('Error: ' + (xhr.responseJSON?.message || 'Request failed'));
            }
        });
    });

    $('#wa-copy-btn').on('click', function() {
        navigator.clipboard.writeText($('#wa-result-box').text()).then(function() {
            $('#wa-copy-btn').text('Copied!');
            setTimeout(function() { $('#wa-copy-btn').html('<i class="las la-copy mr-1"></i>Copy'); }, 2000);
        });
    });
});
</script>
@endsection
