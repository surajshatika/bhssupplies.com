@extends('backend.layouts.app')

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('assets/performance_optimizer/css/performance_optimizer.css') }}?v=2.0">
@endpush

@php
    use App\Services\PerformanceOptimizer\ImageOptimizerService;
    use App\Services\PerformanceOptimizer\PageCacheService;
    use App\Services\PerformanceOptimizer\WebVitalsService;
    use App\Models\PerformanceOptimizer\OptimizationLog;

    $tab = $tab ?? 'dashboard';

    // ── Global top-section data ─────────────────────────────────────
    try { $g_img    = app(ImageOptimizerService::class)->getStats(); } catch (\Exception $e) { $g_img = ['total'=>0,'converted'=>0,'space_saved'=>'0 B','backup_size'=>'0 B']; }
    try { $g_cache  = app(PageCacheService::class)->getStats(); }      catch (\Exception $e) { $g_cache = ['pages'=>0,'size'=>'0 B','driver'=>'file']; }
    try { $g_vitals = app(WebVitalsService::class)->summary(7); }      catch (\Exception $e) { $g_vitals = []; }
    try {
        $g_db_size_bytes = (int) (\DB::selectOne(
            "SELECT SUM(data_length + index_length) AS s FROM information_schema.tables WHERE table_schema = ?",
            [\DB::connection()->getDatabaseName()]
        )->s ?? 0);
    } catch (\Exception $e) { $g_db_size_bytes = 0; }
    $g_db_size = $g_db_size_bytes >= 1073741824
        ? round($g_db_size_bytes/1073741824, 2) . ' GB'
        : ($g_db_size_bytes >= 1048576 ? round($g_db_size_bytes/1048576, 2) . ' MB' : round($g_db_size_bytes/1024, 1) . ' KB');

    try { $g_err_log_count = OptimizationLog::where('status', 'failed')->count(); } catch (\Exception $e) { $g_err_log_count = 0; }

    // ── Vitals gauge values ─────────────────────────────────────────
    $perfTabs = [
        'dashboard'  => ['Dashboard',           'las la-tachometer-alt', 'performance_optimizer.dashboard'],
        'images'     => ['Image Optimization',  'las la-image',          'performance_optimizer.images'],
        'cssjs'      => ['CSS / JS',            'las la-code',           'performance_optimizer.cssjs'],
        'scripts'    => ['Script Manager',      'las la-tasks',          'performance_optimizer.scripts.index'],
        'caching'    => ['Caching',             'las la-bolt',           'performance_optimizer.cache'],
        'cache_rules'=> ['Cache Rules',         'las la-filter',         'performance_optimizer.cache_rules.index'],
        'edge'       => ['Edge / CDN',          'las la-cloud',          'performance_optimizer.edge.index'],
        'database'   => ['Database',            'las la-database',       'performance_optimizer.database'],
        'fonts'      => ['Fonts',               'las la-font',           'performance_optimizer.fonts'],
        'monitor'    => ['System Monitor',      'las la-heartbeat',      'performance_optimizer.monitor'],
        'logs'       => ['Error Logs',          'las la-bug',            'performance_optimizer.logs'],
        'secplus'    => ['Security+',           'las la-user-shield',    'performance_optimizer.secplus.index'],
        'security'   => ['Security Audit',      'las la-shield-alt',     'performance_optimizer.security'],
        'ai'         => ['AI Recommendations',  'las la-robot',          'performance_optimizer.ai.index'],
        'vitals'     => ['Web Vitals',          'las la-chart-line',     'performance_optimizer.vitals'],
        'advanced'   => ['Advanced',            'las la-cogs',           'performance_optimizer.index'],
    ];

    // AI tab pending-count badge (graceful if model not yet present)
    $g_ai_pending = 0;
    try {
        if (class_exists(\App\Models\PerformanceOptimizer\AiRecommendation::class)) {
            $g_ai_pending = \App\Models\PerformanceOptimizer\AiRecommendation::whereNull('applied_at')
                ->whereNull('dismissed_at')->count();
        }
    } catch (\Throwable $e) { $g_ai_pending = 0; }

    $vitalsCfg = [
        'TTFB' => ['label' => 'Time to First Byte',      'unit' => 'ms', 'fmt' => 0],
        'LCP'  => ['label' => 'Largest Contentful Paint','unit' => 'ms', 'fmt' => 0],
        'INP'  => ['label' => 'Interaction to Next Paint','unit'=> 'ms', 'fmt' => 0],
        'CLS'  => ['label' => 'Cumulative Layout Shift', 'unit' => '',   'fmt' => 3],
    ];
@endphp

@section('content')

<div class="perf-page">

    {{-- ──────────────────────────────────────────────────────────────
         Page header
    ──────────────────────────────────────────────────────────────── --}}
    <div class="perf-header">
        <div class="perf-breadcrumb">
            <a href="{{ route('dashboard') }}"><i class="las la-home"></i> {{ translate('Dashboard') }}</a>
            <span>/</span>
            <span>{{ translate('Performance Optimizer') }}</span>
            <span>/</span>
            <span class="text-muted">{{ translate($perfTabs[$tab][0] ?? 'Dashboard') }}</span>
        </div>
        <div class="perf-header-row">
            <div>
                <h1 class="perf-title"><span class="perf-title-icon">🚀</span> {{ translate('Performance Dashboard') }}</h1>
                <small class="text-muted">{{ translate('Advanced site speed, caching, image / CSS / JS optimization & Web Vitals') }}</small>
            </div>
            <div class="perf-header-actions">
                <form action="{{ route('performance_optimizer.toggle') }}" method="POST" id="perf-emergency-form" class="d-inline">
                    @csrf
                    <input type="hidden" name="type"  value="perf_status">
                    <input type="hidden" name="value" value="0">
                    <button type="button" class="btn btn-soft-danger btn-sm" onclick="perfEmergencyDisable()">
                        <i class="las la-power-off"></i> {{ translate('Emergency Disable All') }}
                    </button>
                </form>
                <a href="{{ route('performance_optimizer.logs') }}" class="btn btn-light btn-sm">
                    <i class="las la-history"></i> {{ translate('Activity Logs') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ──────────────────────────────────────────────────────────────
         Core Web Vitals (always visible on all tabs)
    ──────────────────────────────────────────────────────────────── --}}
    <div class="perf-card perf-vitals-strip">
        <div class="perf-card-header">
            <h5><i class="las la-chart-line"></i> {{ translate('Core Web Vitals') }} <small class="text-muted">({{ translate('Last 7 Days') }})</small></h5>
        </div>
        <div class="perf-card-body">
            <div class="perf-vitals-grid">
                @foreach($vitalsCfg as $metric => $cfg)
                    @php
                        $s = $g_vitals[$metric] ?? ['p75' => null, 'rating' => 'no-data', 'samples' => 0];
                        $val = $s['p75'];
                        $rating = $s['rating'] ?? 'no-data';
                        $hasData = $val !== null && ($s['samples'] ?? 0) > 0;

                        // For gauge progress (0-100 fraction)
                        $thresh = ['LCP'=>4000,'INP'=>500,'TTFB'=>1800,'CLS'=>0.25,'FCP'=>3000,'FID'=>300];
                        $maxVal = $thresh[$metric] ?? 100;
                        $frac   = $hasData ? min(100, max(5, ($val / $maxVal) * 100)) : 100;

                        $colors = ['good'=>'#28a745','needs-improvement'=>'#ffc107','poor'=>'#dc3545','no-data'=>'#cbd3da'];
                        $color  = $colors[$rating] ?? '#cbd3da';

                        $labels = ['good'=>'Good','needs-improvement'=>'Needs','poor'=>'Poor','no-data'=>''];
                        $label  = $labels[$rating] ?? '';

                        $disp = $hasData
                            ? ($cfg['fmt'] === 0 ? number_format((float)$val) : number_format((float)$val, $cfg['fmt']))
                            : '—';
                    @endphp
                    <div class="perf-vital-gauge">
                        @if($hasData && $rating !== 'no-data')
                            <span class="perf-vital-badge perf-vital-badge-{{ $rating }}">{{ translate($label) }}</span>
                        @endif
                        <svg class="perf-gauge" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" stroke="#eef0f3" stroke-width="9" fill="none"/>
                            <circle cx="60" cy="60" r="52" stroke="{{ $color }}" stroke-width="9" fill="none"
                                    stroke-dasharray="326.7" stroke-dashoffset="{{ 326.7 - (326.7 * $frac / 100) }}"
                                    stroke-linecap="round" transform="rotate(-90 60 60)"/>
                        </svg>
                        <div class="perf-vital-value" style="color: {{ $color }}">
                            {{ $disp }}<span class="perf-vital-unit">{{ $cfg['unit'] }}</span>
                        </div>
                        <div class="perf-vital-label">{{ $metric }}</div>
                        <div class="perf-vital-sub">{{ translate($cfg['label']) }}</div>
                    </div>
                @endforeach
            </div>
            @if(empty($g_vitals) || array_sum(array_column($g_vitals, 'samples')) === 0)
                <div class="perf-vital-notice">
                    <i class="las la-info-circle"></i>
                    {{ translate('No Web Vitals data yet. Enable') }} <strong>{{ translate('Collect Web Vitals') }}</strong> {{ translate('in the Web Vitals tab and let real visitors generate samples.') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ──────────────────────────────────────────────────────────────
         4 top-level stat cards
    ──────────────────────────────────────────────────────────────── --}}
    <div class="perf-top-stats">
        <div class="perf-stat-card perf-stat-blue">
            <div class="perf-stat-value">{{ number_format($g_img['total']) }}</div>
            <div class="perf-stat-label">{{ translate('Total Images') }}</div>
            <div class="perf-stat-sub">{{ number_format($g_img['converted']) }} {{ translate('converted to WebP') }}</div>
        </div>
        <div class="perf-stat-card perf-stat-green">
            <div class="perf-stat-value">{{ $g_img['space_saved'] }}</div>
            <div class="perf-stat-label">{{ translate('Space Saved (Images)') }}</div>
            <div class="perf-stat-sub">{{ translate('Backup') }}: {{ $g_img['backup_size'] }}</div>
        </div>
        <div class="perf-stat-card perf-stat-cyan">
            <div class="perf-stat-value">{{ number_format($g_cache['pages']) }}</div>
            <div class="perf-stat-label">{{ translate('Pages Cached') }}</div>
            <div class="perf-stat-sub">{{ ($g_cache['driver'] ?? '') === 'litespeed' ? translate('LiteSpeed + local safety copies') : ($g_cache['size'] . ' ' . translate('stored')) }} · {{ strtoupper($g_cache['driver']) }}</div>
        </div>
        <div class="perf-stat-card perf-stat-yellow">
            <div class="perf-stat-value">{{ $g_db_size }}</div>
            <div class="perf-stat-label">{{ translate('Database Size') }}</div>
            <div class="perf-stat-sub">{{ $g_err_log_count }} {{ translate('failed operations logged') }}</div>
        </div>
    </div>

    {{-- ──────────────────────────────────────────────────────────────
         Tab bar
    ──────────────────────────────────────────────────────────────── --}}
    <div class="perf-tab-bar">
        @foreach($perfTabs as $k => $t)
            @php $routeUrl = $t[2] === 'performance_optimizer.index' ? route('performance_optimizer.index', ['tab' => $k]) : route($t[2]); @endphp
            <a href="{{ $routeUrl }}" class="perf-tab {{ $tab === $k ? 'active' : '' }}">
                <i class="{{ $t[1] }}"></i>
                <span>{{ translate($t[0]) }}</span>
                @if($k === 'logs' && $g_err_log_count > 0)
                    <span class="perf-tab-badge perf-tab-badge-danger">{{ $g_err_log_count > 99 ? '99+' : $g_err_log_count }}</span>
                @endif
                @if($k === 'security')
                    <span class="perf-tab-badge perf-tab-badge-warning">{{ translate('Pro') }}</span>
                @endif
                @if($k === 'ai' && $g_ai_pending > 0)
                    <span class="perf-tab-badge perf-tab-badge-warning">{{ $g_ai_pending > 99 ? '99+' : $g_ai_pending }}</span>
                @endif
            </a>
        @endforeach
        <a href="#" class="perf-tab perf-tab-settings" onclick="document.getElementById('perf-master-toggle').focus(); return false;">
            <i class="las la-cog"></i>
            <span>{{ translate('Settings') }}</span>
        </a>
    </div>

    {{-- ──────────────────────────────────────────────────────────────
         Tab content
    ──────────────────────────────────────────────────────────────── --}}
    <div class="perf-tab-content">
        @includeIf('backend.performance_optimizer.tabs.' . $tab)
    </div>

    {{-- ──────────────────────────────────────────────────────────────
         Master toggle (hidden but accessible from settings)
    ──────────────────────────────────────────────────────────────── --}}
    <div class="perf-master-toggle-bar">
        <label class="d-flex align-items-center mb-0">
            <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                <input type="checkbox" id="perf-master-toggle"
                       onchange="perfToggle(this, 'perf_status')"
                       @if(get_setting('perf_status') == 1) checked @endif>
                <span class="slider round"></span>
            </label>
            <strong>{{ translate('Master switch') }}</strong>
            <small class="text-muted ml-2">{{ translate('Disable to bypass all optimizations site-wide') }}</small>
        </label>
        <small class="text-muted">{{ translate('Performance Optimizer Addon') }} v1.1</small>
    </div>

</div>

@endsection

@push('after-scripts')
<script src="{{ asset('assets/performance_optimizer/js/performance_optimizer.js') }}?v=2.0"></script>
<script>
function perfToggle(el, key){
    if (typeof AIZ !== 'undefined') AIZ.plugins.notify('info', '{{ translate("Saving...") }}');
    $.post('{{ route("performance_optimizer.toggle") }}', {
        _token: (typeof AIZ !== 'undefined' ? AIZ.data.csrf : $('meta[name=csrf-token]').attr('content')),
        type: key,
        value: el.checked ? 1 : 0
    }).done(function(){
        if (typeof AIZ !== 'undefined') AIZ.plugins.notify('success', '{{ translate("Saved.") }}');
    }).fail(function(){
        if (typeof AIZ !== 'undefined') AIZ.plugins.notify('danger', '{{ translate("Failed.") }}');
    });
}
function perfEmergencyDisable(){
    if (!confirm('{{ translate("This will turn OFF the master switch and bypass ALL optimizations site-wide. Continue?") }}')) return;
    document.getElementById('perf-emergency-form').submit();
}
</script>
@endpush
