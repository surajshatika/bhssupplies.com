@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

@php
    $modelLabels = [
        'first_touch'    => 'First Touch',
        'last_touch'     => 'Last Touch',
        'linear'         => 'Linear',
        'time_decay'     => 'Time Decay',
        'position_based' => 'Position Based (U-Shape)',
    ];
@endphp

<div class="mm-hero" style="background:linear-gradient(135deg,#0EA5E9 0%,#7B61FF 50%,#FF61D2 100%);">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><path d="M6 21V9a9 9 0 0 1 9 9"/></svg>
            </div>
            <div>
                <h2>{{ translate('Attribution & Funnels') }}</h2>
                <p>{{ translate('Multi-touch attribution (5 models), conversion funnel drop-off & cohort retention — all from first-party warehouse.') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-route"></i> {{ $attr['orders'] }} {{ translate('attributed orders') }}</span>
                    <span class="mm-chip"><i class="las la-dollar-sign"></i> ${{ number_format($attr['revenue'], 2) }} {{ translate('revenue') }}</span>
                    <span class="mm-chip"><i class="las la-percentage"></i> {{ $funnel['overall_conversion_pct'] ?? 0 }}% {{ translate('funnel conversion') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-pills mb-4" id="attrTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tab-attribution" role="tab">
            <i class="las la-project-diagram"></i> {{ translate('Attribution') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-funnels" role="tab">
            <i class="las la-filter"></i> {{ translate('Funnels') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-cohorts" role="tab">
            <i class="las la-th"></i> {{ translate('Cohort Retention') }}
        </a>
    </li>
</ul>

<div class="tab-content">
    {{-- ============================ ATTRIBUTION ============================ --}}
    <div class="tab-pane fade show active" id="tab-attribution" role="tabpanel">

        {{-- Toolbar --}}
        <form method="GET" class="mm-card mb-3">
            <div class="mm-card-body d-flex flex-wrap align-items-end" style="gap:.75rem;">
                <div>
                    <label class="small text-muted mb-1">{{ translate('Model') }}</label>
                    <select name="model" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach($modelLabels as $key => $label)
                            <option value="{{ $key }}" {{ $model===$key?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="small text-muted mb-1">{{ translate('Window') }}</label>
                    <select name="window" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([7,14,30,60,90] as $d)
                            <option value="{{ $d }}" {{ $window==$d?'selected':'' }}>{{ $d }} {{ translate('days') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="row">
            {{-- Stats --}}
            <div class="col-md-3 mb-3">
                <div class="mm-stat">
                    <div class="mm-stat-icon mm-tint-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3 class="mm-stat-value">${{ number_format($attr['revenue'], 2) }}</h3>
                    <div class="mm-stat-label">{{ translate('Total Revenue') }}</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="mm-stat">
                    <div class="mm-stat-icon mm-tint-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <h3 class="mm-stat-value">${{ number_format($attr['attributed'], 2) }}</h3>
                    <div class="mm-stat-label">{{ translate('Attributed') }}</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="mm-stat">
                    <div class="mm-stat-icon mm-tint-slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <h3 class="mm-stat-value">${{ number_format($attr['unattributed'], 2) }}</h3>
                    <div class="mm-stat-label">{{ translate('Unattributed (direct)') }}</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="mm-stat">
                    <div class="mm-stat-icon mm-tint-purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </div>
                    <h3 class="mm-stat-value">{{ number_format($attr['orders']) }}</h3>
                    <div class="mm-stat-label">{{ translate('Attributed Orders') }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="mm-card">
                    <div class="mm-card-header">
                        <h5 class="mm-card-title"><i class="las la-tags"></i> {{ translate('Revenue by Source') }}</h5>
                        <small class="text-muted">{{ $modelLabels[$model] }} {{ translate('model') }}</small>
                    </div>
                    <div class="mm-card-body">
                        <canvas id="src-chart" height="180"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="mm-card">
                    <div class="mm-card-header"><h5 class="mm-card-title"><i class="las la-bullhorn"></i> {{ translate('Revenue by Campaign') }}</h5></div>
                    <div class="mm-card-body">
                        @if(empty($attr['totals_by_campaign']))
                            <div class="text-muted text-center py-4">{{ translate('No campaign-tagged conversions yet.') }}</div>
                        @else
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr><th>{{ translate('Campaign') }}</th><th class="text-right">{{ translate('Attributed') }}</th></tr>
                                </thead>
                                <tbody>
                                @foreach($attr['totals_by_campaign'] as $cmp => $v)
                                    <tr><td>{{ $cmp }}</td><td class="text-right"><strong>${{ number_format($v, 2) }}</strong></td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Compare table --}}
            <div class="col-12 mb-4">
                <div class="mm-card">
                    <div class="mm-card-header">
                        <h5 class="mm-card-title"><i class="las la-balance-scale"></i> {{ translate('Side-by-side Model Comparison') }}</h5>
                        <small class="text-muted">{{ translate('See how attribution changes by model. Pick the model that matches your funnel.') }}</small>
                    </div>
                    <div class="mm-card-body">
                        @if(empty($compare['compare']))
                            <div class="text-muted text-center py-4">{{ translate('No attributed conversions yet — keep capturing UTM traffic.') }}</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{ translate('Source') }}</th>
                                            @foreach($modelLabels as $label)<th class="text-right">{{ $label }}</th>@endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($compare['compare'] as $src => $cols)
                                        <tr>
                                            <td><strong>{{ $src }}</strong></td>
                                            @foreach($modelLabels as $k => $_)
                                                <td class="text-right">${{ number_format($cols[$k] ?? 0, 2) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ FUNNELS ============================ --}}
    <div class="tab-pane fade" id="tab-funnels" role="tabpanel">
        <form method="GET" class="mm-card mb-3">
            <input type="hidden" name="model" value="{{ $model }}">
            <div class="mm-card-body d-flex flex-wrap align-items-end" style="gap:.75rem;">
                <div>
                    <label class="small text-muted mb-1">{{ translate('Funnel') }}</label>
                    <select name="funnel" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach($available as $f)
                            <option value="{{ $f['id'] }}" {{ $funnelId===$f['id']?'selected':'' }}>{{ $f['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="small text-muted mb-1">{{ translate('Window') }}</label>
                    <select name="window" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([7,14,30,60,90] as $d)
                            <option value="{{ $d }}" {{ $window==$d?'selected':'' }}>{{ $d }} {{ translate('days') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if(isset($funnel['error']))
            <div class="alert alert-warning">{{ $funnel['error'] }}</div>
        @else
            <div class="mm-card mb-3">
                <div class="mm-card-header">
                    <h5 class="mm-card-title"><i class="las la-filter"></i> {{ $funnel['funnel_name'] ?? '' }}</h5>
                    <span class="badge badge-soft-success">{{ $funnel['overall_conversion_pct'] }}% {{ translate('overall') }}</span>
                </div>
                <div class="mm-card-body">
                    @foreach($funnel['steps'] as $i => $step)
                        @php
                            $w = max(2, $step['pct_from_top']);
                            $tint = $i === 0 ? '#3B82F6' : ($i === count($funnel['steps']) - 1 ? '#10B981' : ($step['dropoff'] > 0 ? '#F59E0B' : '#7B61FF'));
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <div>
                                    <strong>{{ $i + 1 }}. {{ $step['name'] }}</strong>
                                    <small class="text-muted ml-2">
                                        {{ number_format($step['count']) }} {{ translate('users') }} —
                                        {{ $step['pct_from_top'] }}% {{ translate('of top') }}
                                        @if($i > 0)
                                            | {{ $step['pct_step_to_step'] }}% {{ translate('from previous') }}
                                        @endif
                                    </small>
                                </div>
                                @if($i > 0 && $step['dropoff'] > 0)
                                    <small class="text-danger">
                                        <i class="las la-arrow-down"></i> {{ number_format($step['dropoff']) }} {{ translate('dropoff') }}
                                    </small>
                                @endif
                            </div>
                            <div style="height:38px; background:#f3f4f6; border-radius:8px; overflow:hidden; position:relative;">
                                <div style="height:100%; width:{{ $w }}%; background: linear-gradient(90deg, {{ $tint }}, {{ $tint }}cc); display:flex; align-items:center; padding-left:12px; color:#fff; font-weight:600; border-radius:8px; transition: width .4s;">
                                    {{ $step['count'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($funnel['top_dropoff_step'])
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong><i class="las la-exclamation-triangle"></i> {{ translate('Biggest drop-off') }}:</strong>
                            {{ translate('Most users are abandoning at') }} <strong>{{ $funnel['top_dropoff_step'] }}</strong>.
                            {{ translate('Consider testing copy, CTAs, or friction at this step.') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ============================ COHORTS ============================ --}}
    <div class="tab-pane fade" id="tab-cohorts" role="tabpanel">
        <form method="GET" class="mm-card mb-3">
            <input type="hidden" name="model" value="{{ $model }}">
            <input type="hidden" name="funnel" value="{{ $funnelId }}">
            <div class="mm-card-body d-flex flex-wrap align-items-end" style="gap:.75rem;">
                <div>
                    <label class="small text-muted mb-1">{{ translate('Cohort range') }}</label>
                    <select name="cohort_months" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach([3,6,9,12] as $m)
                            <option value="{{ $m }}" {{ $cohortMonths==$m?'selected':'' }}>{{ $m }} {{ translate('months') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="mm-card">
            <div class="mm-card-header">
                <h5 class="mm-card-title"><i class="las la-th"></i> {{ translate('Cohort Retention Heatmap') }}</h5>
                <small class="text-muted">{{ $cohort['total_unique'] }} {{ translate('unique visitors total') }}</small>
            </div>
            <div class="mm-card-body">
                @if(empty($cohort['cohorts']))
                    <div class="text-muted text-center py-5">
                        <i class="las la-hourglass-half" style="font-size:40px;"></i>
                        <p class="mt-2">{{ translate('Need at least one full month of data — come back next month.') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 cohort-table">
                            <thead>
                                <tr>
                                    <th class="bg-light">{{ translate('Cohort') }}</th>
                                    <th class="bg-light text-right">{{ translate('Size') }}</th>
                                    @foreach($cohort['month_labels'] as $lbl)
                                        <th class="bg-light text-center" style="min-width:60px;">{{ $lbl }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($cohort['cohorts'] as $month => $row)
                                <tr>
                                    <td><strong>{{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }}</strong></td>
                                    <td class="text-right text-muted">{{ number_format($row['size']) }}</td>
                                    @foreach($cohort['month_labels'] as $i => $_)
                                        @php
                                            $pct = $row['retention'][$i] ?? null;
                                            $col = $pct === null ? '#f3f4f6' : 'rgba(123,97,255,' . max(0.05, min(1, $pct / 100)) . ')';
                                            $tcol = ($pct !== null && $pct > 50) ? '#fff' : '#1f2937';
                                        @endphp
                                        <td class="text-center" style="background:{{ $col }}; color:{{ $tcol }}; font-weight:600;">
                                            {{ $pct === null ? '' : $pct . '%' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-muted mt-3">
                        <i class="las la-info-circle"></i>
                        {{ translate('Darker cell = higher retention. +0 = signup month (always 100%). Use this to spot leaky weeks/months.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('css')
<style>
.cohort-table th, .cohort-table td { padding:.5rem .6rem; font-size:13px; }
</style>
@endpush
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    var sources  = @json(array_keys($attr['totals_by_source'] ?? []));
    var values   = @json(array_values($attr['totals_by_source'] ?? []));
    var ctx = document.getElementById('src-chart');
    if (ctx && sources.length) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: sources,
                datasets: [{
                    data: values,
                    backgroundColor: ['#7B61FF','#0EA5E9','#10B981','#F59E0B','#EF4444','#FF61D2','#FBBC04','#FF9900','#34A853','#232F3E']
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { position: 'right' } }
            }
        });
    } else if (ctx) {
        ctx.parentElement.innerHTML = '<div class="text-muted text-center py-4">{{ translate("No source data yet.") }}</div>';
    }
})();
</script>
@endsection
