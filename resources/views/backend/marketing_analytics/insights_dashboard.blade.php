@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

@php
    $headline   = $summary['summary']['headline']  ?? 'No AI summary yet.';
    $wins       = $summary['summary']['wins']      ?? [];
    $concerns   = $summary['summary']['concerns']  ?? [];
    $anomalyAI  = $summary['summary']['anomalies'] ?? [];
    $actions    = $summary['summary']['recommended_actions'] ?? [];
    $provider   = $summary['provider'] ?? 'none';
    $genAt      = $summary['generated_at'] ?? null;
    $confidence = $summary['summary']['confidence'] ?? 'low';
    $revenueToday   = $latest['revenue']           ?? 0;
    $visitorsToday  = $latest['unique_visitors']   ?? 0;
    $ordersToday    = $latest['purchases']         ?? 0;
    $atcToday       = $latest['add_to_cart']       ?? 0;
    $convRate       = $visitorsToday > 0 ? round(($ordersToday / $visitorsToday) * 100, 2) : 0;
@endphp

<div class="mm-hero" style="background:linear-gradient(135deg,#7B61FF 0%,#FF6B6B 50%,#FBBC04 100%);">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/></svg>
            </div>
            <div>
                <h2>{{ translate('AI Marketing Insights') }}</h2>
                <p>{{ translate('First-party warehouse + AI-generated wins, anomalies, recommendations. Ask anything in natural language.') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-robot"></i> {{ ucfirst($provider) }}</span>
                    <span class="mm-chip"><i class="las la-database"></i> {{ count($aggregates) }} {{ translate('days warehouse') }}</span>
                    <span class="mm-chip"><span class="mm-dot {{ $confidence==='high'?'ok':($confidence==='medium'?'warn':'err') }}"></span> {{ ucfirst($confidence) }} {{ translate('confidence') }}</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
            <button id="regen-summary-btn" class="mm-btn mm-btn-light">
                <i class="las la-sync"></i> {{ translate('Regenerate') }}
            </button>
            <button id="forecast-btn" class="mm-btn mm-btn-ghost">
                <i class="las la-chart-line"></i> {{ translate('7-day Forecast') }}
            </button>
        </div>
    </div>
</div>

{{-- TODAY KPIs --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3 class="mm-stat-value">${{ number_format($revenueToday, 2) }}</h3>
            <div class="mm-stat-label">{{ translate('Revenue (yesterday)') }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($visitorsToday) }}</h3>
            <div class="mm-stat-label">{{ translate('Unique Visitors') }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($ordersToday) }}</h3>
            <div class="mm-stat-label">{{ translate('Orders') }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-pink">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ $convRate }}<small style="font-size:14px;">%</small></h3>
            <div class="mm-stat-label">{{ translate('Conversion Rate') }}</div>
        </div>
    </div>
</div>

<div class="row">
    {{-- AI SUMMARY CARD --}}
    <div class="col-lg-8 mb-4">
        <div class="mm-card h-100">
            <div class="mm-card-header">
                <h5 class="mm-card-title"><i class="las la-magic text-primary"></i> {{ translate('AI Daily Summary') }}</h5>
                @if($genAt)
                    <small class="text-muted">{{ translate('Generated') }} {{ \Carbon\Carbon::parse($genAt)->diffForHumans() }}</small>
                @endif
            </div>
            <div class="mm-card-body" id="ai-summary-body">
                <div class="alert" style="background:linear-gradient(135deg,#f5f3ff,#fce7f3); border:none; border-radius:12px;">
                    <strong style="color:#7B61FF;">{{ $headline }}</strong>
                </div>

                @if(count($wins))
                    <h6 class="mt-3 mb-2 text-success"><i class="las la-trophy"></i> {{ translate('Wins') }}</h6>
                    <ul class="pl-3 mb-3">
                        @foreach($wins as $w) <li>{{ $w }}</li> @endforeach
                    </ul>
                @endif

                @if(count($concerns))
                    <h6 class="mt-3 mb-2 text-danger"><i class="las la-exclamation-circle"></i> {{ translate('Concerns') }}</h6>
                    <ul class="pl-3 mb-3">
                        @foreach($concerns as $c) <li>{{ $c }}</li> @endforeach
                    </ul>
                @endif

                @if(count($anomalyAI))
                    <h6 class="mt-3 mb-2 text-warning"><i class="las la-bolt"></i> {{ translate('AI-detected anomalies') }}</h6>
                    <ul class="pl-3 mb-3">
                        @foreach($anomalyAI as $a) <li>{{ $a }}</li> @endforeach
                    </ul>
                @endif

                @if(count($actions))
                    <h6 class="mt-3 mb-2 text-primary"><i class="las la-list-ul"></i> {{ translate('Recommended actions') }}</h6>
                    @foreach($actions as $a)
                        @php
                            $impact = strtolower($a['impact'] ?? 'medium');
                            $bg = $impact === 'high' ? '#fee2e2' : ($impact === 'medium' ? '#fef3c7' : '#dbeafe');
                            $border = $impact === 'high' ? '#EF4444' : ($impact === 'medium' ? '#F59E0B' : '#3B82F6');
                        @endphp
                        <div class="mb-2 p-3" style="background:{{ $bg }}; border-left:4px solid {{ $border }}; border-radius:8px;">
                            <strong>{{ $a['action'] ?? '' }}</strong>
                            <span class="badge ml-2" style="background:{{ $border }}; color:white;">{{ ucfirst($impact) }} {{ translate('impact') }}</span>
                            <div class="small text-muted mt-1">{{ $a['why'] ?? '' }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- DETECTED ANOMALIES --}}
    <div class="col-lg-4 mb-4">
        <div class="mm-card h-100">
            <div class="mm-card-header">
                <h5 class="mm-card-title"><i class="las la-bell text-warning"></i> {{ translate('Anomaly Detector') }}</h5>
                <span class="badge badge-warning">{{ count($anomalies) }}</span>
            </div>
            <div class="mm-card-body">
                @if(count($anomalies))
                    @foreach($anomalies as $a)
                        @php $up = $a['direction'] === 'up'; @endphp
                        <div class="d-flex align-items-center p-2 mb-2"
                             style="background:{{ $up ? '#ecfdf5' : '#fef2f2' }}; border-radius:8px;">
                            <i class="las la-arrow-{{ $up ? 'up' : 'down' }} mr-2"
                               style="color:{{ $up ? '#10B981' : '#EF4444' }}; font-size:20px;"></i>
                            <div class="flex-grow-1">
                                <strong style="text-transform:capitalize;">{{ str_replace('_', ' ', $a['metric']) }}</strong>
                                <div class="small text-muted">
                                    {{ str_replace('_', ' ', $a['period']) }}:
                                    <strong style="color:{{ $up ? '#10B981' : '#EF4444' }};">
                                        {{ $a['change_pct'] > 0 ? '+' : '' }}{{ $a['change_pct'] }}%
                                    </strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="las la-check-circle text-success" style="font-size:40px;"></i>
                        <p class="mb-0 mt-2 text-muted">{{ translate('No anomalies detected') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TREND CHART --}}
    <div class="col-lg-8 mb-4">
        <div class="mm-card">
            <div class="mm-card-header">
                <h5 class="mm-card-title"><i class="las la-chart-area text-primary"></i> {{ translate('30-Day Trend') }}</h5>
            </div>
            <div class="mm-card-body">
                <canvas id="trend-chart" height="120"></canvas>
            </div>
        </div>

        {{-- FORECAST CARD --}}
        <div class="mm-card mt-3" id="forecast-card" style="display:none;">
            <div class="mm-card-header">
                <h5 class="mm-card-title"><i class="las la-crystal-ball text-info"></i> {{ translate('AI Forecast — Next 7 Days') }}</h5>
            </div>
            <div class="mm-card-body" id="forecast-body">
                <div class="text-muted small">{{ translate('Click Forecast button above to generate.') }}</div>
            </div>
        </div>
    </div>

    {{-- NLP QUERY BOX --}}
    <div class="col-lg-4 mb-4">
        <div class="mm-card h-100" style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 100%); color:#fff; border:none;">
            <div class="mm-card-body">
                <h5 class="mb-1" style="color:#fff;"><i class="las la-comment-dots"></i> {{ translate('Ask the Data') }}</h5>
                <small style="color:rgba(255,255,255,.7);">{{ translate('Natural-language analytics query — try “best UTM source last week”') }}</small>

                <textarea id="nlp-question" class="form-control mt-3" rows="3"
                          placeholder="{{ translate('e.g. Which day had the highest revenue last week?') }}"
                          style="background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2);"></textarea>

                <button id="nlp-ask-btn" class="mm-btn mm-btn-light w-100 mt-2">
                    <i class="las la-paper-plane"></i> {{ translate('Ask AI') }}
                </button>

                <div id="nlp-answer" class="mt-3" style="display:none; background:rgba(255,255,255,.1); padding:12px; border-radius:8px; font-size:13px; max-height:300px; overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    {{-- TOP UTM / REFERRERS --}}
    <div class="col-lg-6 mb-4">
        <div class="mm-card h-100">
            <div class="mm-card-header"><h5 class="mm-card-title"><i class="las la-link"></i> {{ translate('Top UTM Sources (yesterday)') }}</h5></div>
            <div class="mm-card-body">
                @if(!empty($latest['top_utm_source']))
                    @foreach($latest['top_utm_source'] as $src => $cnt)
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="las la-tag text-primary"></i> {{ $src }}</span>
                            <strong>{{ $cnt }}</strong>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted">{{ translate('No UTM data yet.') }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="mm-card h-100">
            <div class="mm-card-header"><h5 class="mm-card-title"><i class="las la-external-link-alt"></i> {{ translate('Top Referrers (yesterday)') }}</h5></div>
            <div class="mm-card-body">
                @if(!empty($latest['top_referrers']))
                    @foreach($latest['top_referrers'] as $host => $cnt)
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="las la-globe text-info"></i> {{ $host }}</span>
                            <strong>{{ $cnt }}</strong>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted">{{ translate('No referrers yet.') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    // Trend chart
    var ctx = document.getElementById('trend-chart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($trendDates),
                datasets: [
                    {
                        label: '{{ translate("Revenue") }} ($)',
                        data: @json($trendRevenue),
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16,185,129,0.1)',
                        tension: 0.35, fill: true, yAxisID: 'y'
                    },
                    {
                        label: '{{ translate("Visitors") }}',
                        data: @json($trendVisitors),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59,130,246,0.05)',
                        tension: 0.35, fill: false, yAxisID: 'y1'
                    },
                    {
                        label: '{{ translate("Orders") }}',
                        data: @json($trendOrders),
                        borderColor: '#7B61FF',
                        backgroundColor: 'rgba(123,97,255,0.05)',
                        tension: 0.35, fill: false, yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y:  { type: 'linear', position: 'left', beginAtZero: true, title: { display: true, text: 'Revenue' }},
                    y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Visitors / Orders' }}
                }
            }
        });
    }

    // NLP question
    document.getElementById('nlp-ask-btn').addEventListener('click', function () {
        var q = document.getElementById('nlp-question').value.trim();
        if (!q) { AIZ.plugins.notify('warning', 'Please type a question.'); return; }
        var btn = this; btn.disabled = true;
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="las la-spinner la-spin"></i> {{ translate("Thinking...") }}';
        fetch('{{ route("analytics.insights.ask") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ question: q })
        })
        .then(r => r.json())
        .then(data => {
            var box = document.getElementById('nlp-answer');
            box.style.display = 'block';
            box.innerHTML = data.success
                ? (data.answer.replace(/\n/g, '<br>') + '<br><br><small style="opacity:.6;">via ' + (data.provider || 'ai') + '</small>')
                : '<span style="color:#fca5a5;">' + (data.message || 'Failed') + '</span>';
            btn.disabled = false; btn.innerHTML = orig;
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; AIZ.plugins.notify('danger', 'Network error'); });
    });

    // Regenerate summary
    document.getElementById('regen-summary-btn').addEventListener('click', function () {
        var btn = this; btn.disabled = true;
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="las la-spinner la-spin"></i> {{ translate("Regenerating...") }}';
        fetch('{{ route("analytics.insights.regenerate") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                AIZ.plugins.notify('success', 'AI summary regenerated.');
                setTimeout(() => location.reload(), 800);
            } else {
                btn.disabled = false; btn.innerHTML = orig;
                AIZ.plugins.notify('danger', 'Regeneration failed.');
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; });
    });

    // Forecast
    document.getElementById('forecast-btn').addEventListener('click', function () {
        var btn = this; btn.disabled = true;
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="las la-spinner la-spin"></i> {{ translate("Forecasting...") }}';
        document.getElementById('forecast-card').style.display = 'block';
        document.getElementById('forecast-body').innerHTML = '<div class="text-muted small">{{ translate("Running AI forecast...") }}</div>';
        fetch('{{ route("analytics.insights.forecast") }}?horizon=7', {
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.innerHTML = orig;
            var body = document.getElementById('forecast-body');
            if (data.error) { body.innerHTML = '<div class="alert alert-warning">' + data.error + '</div>'; return; }
            var rows = (data.forecast || []).map(d =>
                `<tr><td>${d.date}</td><td class="text-success"><strong>$${(d.revenue||0).toFixed(2)}</strong></td><td>${d.visitors||0}</td><td>${d.orders||0}</td><td><span class="badge badge-${d.confidence==='high'?'success':d.confidence==='medium'?'warning':'secondary'}">${d.confidence||'low'}</span></td></tr>`
            ).join('');
            body.innerHTML = `<table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Revenue</th><th>Visitors</th><th>Orders</th><th>Confidence</th></tr></thead><tbody>${rows}</tbody></table>`;
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; });
    });
})();
</script>
@endsection
