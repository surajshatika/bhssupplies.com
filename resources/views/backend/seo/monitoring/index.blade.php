@extends('backend.layouts.app')

@section('content')
@php
    $totals = $data['totals'];
    $costSeries  = collect($data['cost_series']);
    $runSeries   = collect($data['run_series']);
    $scoreSeries = collect($data['score_series']);
    $buckets     = $data['score_buckets'];
@endphp

<style>
.mon-stat { background: #fff; border: 1px solid #eef0f4; border-radius: 8px; padding: .9rem 1rem; }
.mon-stat .num { font-size: 1.7rem; font-weight: 700; line-height: 1.05; }
.mon-stat .label { font-size: .72rem; color: #6b7280; letter-spacing: .04em; text-transform: uppercase; }
.mon-card { background: #fff; border: 1px solid #eef0f4; border-radius: 8px; }
.mon-card .mon-card-head { padding: .65rem 1rem; border-bottom: 1px solid #eef0f4; font-weight: 600; font-size: .9rem; display:flex; justify-content:space-between; align-items:center; }
.mon-card .mon-card-body { padding: .85rem 1rem; }
.bucket-bar { display: flex; height: 10px; border-radius: 5px; overflow: hidden; background: #eef0f4; margin-top: .5rem; }
.bucket-bar > div { transition: width .25s ease; }
.kv-row { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px solid #f3f4f6; font-size: .85rem; }
.kv-row:last-child { border-bottom: 0; }
.kv-row .k { color: #6b7280; }
.delta-up   { color: #1cc88a; font-weight: 600; }
.delta-down { color: #e74a3b; font-weight: 600; }
.mini-table { width: 100%; }
.mini-table th { text-transform: uppercase; font-size: .7rem; color: #9ca3af; font-weight: 600; padding: .25rem .5rem; border-bottom: 1px solid #eef0f4; }
.mini-table td { padding: .35rem .5rem; font-size: .82rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.mini-table tr:last-child td { border-bottom: 0; }
.text-truncate-cell { max-width: 220px; }
</style>

<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-7">
            <h1 class="h3 mb-0"><i class="las la-chart-line mr-2"></i>{{ translate('SEO Monitoring') }}</h1>
            <p class="text-muted mb-0 small">{{ translate('Cost, runs, score trend, GSC traffic and rank movements over the last') }} {{ $data['window_days'] }} {{ translate('days.') }}</p>
        </div>
        <div class="col-md-5 text-md-right mt-2 mt-md-0">
            <form method="GET" class="d-inline-block mr-2">
                <select name="days" class="form-control form-control-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                    @foreach ([7, 14, 30, 60, 90] as $d)
                        <option value="{{ $d }}" {{ $days === $d ? 'selected' : '' }}>{{ $d }} {{ translate('days') }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.seo.ai_board.index') }}" class="btn btn-soft-primary btn-sm">{{ translate('AI Board') }}</a>
            <a href="{{ route('admin.seo-suite.settings.view') }}" class="btn btn-soft-secondary btn-sm">{{ translate('Settings') }}</a>
        </div>
    </div>
</div>

{{-- Top stats --}}
<div class="row gutters-8 mb-3">
    <div class="col-6 col-md-3 col-xl-2 mb-2">
        <div class="mon-stat">
            <div class="num text-primary">${{ number_format($totals['ai_spend_30d'], 4) }}</div>
            <div class="label">{{ translate('AI spend 30d') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2 mb-2">
        <div class="mon-stat">
            <div class="num">{{ number_format($totals['fix_batches_30d']) }}</div>
            <div class="label">{{ translate('Fix batches 30d') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2 mb-2">
        <div class="mon-stat">
            <div class="num">{{ number_format($totals['runs_30d']) }}</div>
            <div class="label">{{ translate('SEO runs 30d') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2 mb-2">
        <div class="mon-stat">
            <div class="num text-danger">{{ number_format($totals['failed_runs_30d']) }}</div>
            <div class="label">{{ translate('Failed runs') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2 mb-2">
        <div class="mon-stat">
            <div class="num text-success">{{ $totals['avg_score'] }}</div>
            <div class="label">{{ translate('Avg score') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2 mb-2">
        <div class="mon-stat">
            <div class="num">{{ number_format($totals['entities_scored']) }}</div>
            <div class="label">{{ translate('Entities scored') }}</div>
        </div>
    </div>
</div>

{{-- Charts row --}}
<div class="row gutters-16">
    <div class="col-lg-6 mb-3">
        <div class="mon-card h-100">
            <div class="mon-card-head">
                {{ translate('AI spend (USD/day)') }}
                <span class="text-muted small">{{ translate('From') }} {{ $data['window_start'] }}</span>
            </div>
            <div class="mon-card-body">
                <canvas id="costChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="mon-card h-100">
            <div class="mon-card-head">{{ translate('SEO runs (completed vs failed)') }}</div>
            <div class="mon-card-body">
                <canvas id="runChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="mon-card h-100">
            <div class="mon-card-head">{{ translate('Average SEO score trend') }}</div>
            <div class="mon-card-body">
                <canvas id="scoreChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="mon-card h-100">
            <div class="mon-card-head">{{ translate('Score distribution') }}</div>
            <div class="mon-card-body">
                @php
                    $bucketTotal = max(1, array_sum($buckets));
                @endphp
                <div class="kv-row"><span class="k"><span class="badge badge-soft-danger mr-1">●</span>{{ translate('Critical (<50)') }}</span><span>{{ $buckets['critical'] }}</span></div>
                <div class="kv-row"><span class="k"><span class="badge badge-soft-warning mr-1">●</span>{{ translate('Warning (50–79)') }}</span><span>{{ $buckets['warning'] }}</span></div>
                <div class="kv-row"><span class="k"><span class="badge badge-soft-success mr-1">●</span>{{ translate('Good (80+)') }}</span><span>{{ $buckets['good'] }}</span></div>
                <div class="kv-row"><span class="k"><span class="badge badge-soft-secondary mr-1">●</span>{{ translate('Unrated') }}</span><span>{{ $buckets['unrated'] }}</span></div>
                <div class="bucket-bar">
                    <div style="background:#e74a3b; width:{{ round($buckets['critical']/$bucketTotal*100, 1) }}%"></div>
                    <div style="background:#f6c23e; width:{{ round($buckets['warning']/$bucketTotal*100, 1) }}%"></div>
                    <div style="background:#1cc88a; width:{{ round($buckets['good']/$bucketTotal*100, 1) }}%"></div>
                    <div style="background:#cbd0d8; width:{{ round($buckets['unrated']/$bucketTotal*100, 1) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tables row --}}
<div class="row gutters-16">
    {{-- Recent batches --}}
    <div class="col-lg-6 mb-3">
        <div class="mon-card">
            <div class="mon-card-head">{{ translate('Recent AI fix batches') }}</div>
            <div class="mon-card-body p-0">
                <table class="mini-table">
                    <thead><tr><th>{{ translate('Label') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Done') }}</th><th>{{ translate('Cost') }}</th><th>{{ translate('When') }}</th></tr></thead>
                    <tbody>
                    @forelse($data['recent_batches'] as $b)
                        <tr>
                            <td class="text-truncate-cell"><span class="text-truncate d-inline-block" style="max-width:200px;" title="{{ $b['label'] }}">{{ $b['label'] }}</span></td>
                            <td>
                                @if ($b['status'] === 'completed') <span class="badge badge-soft-success">done</span>
                                @elseif ($b['status'] === 'running') <span class="badge badge-soft-primary">{{ $b['status'] }}</span>
                                @elseif ($b['status'] === 'cancelled') <span class="badge badge-soft-warning">{{ $b['status'] }}</span>
                                @elseif ($b['status'] === 'failed') <span class="badge badge-soft-danger">{{ $b['status'] }}</span>
                                @else <span class="badge badge-soft-secondary">{{ $b['status'] }}</span>
                                @endif
                            </td>
                            <td>{{ $b['succeeded'] }}/{{ $b['total'] }} <span class="text-muted small">({{ $b['failed'] }} fail)</span></td>
                            <td>${{ number_format($b['cost_usd'], 4) }}</td>
                            <td class="text-muted small">{{ $b['started_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('No batches yet') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top failing features --}}
    <div class="col-lg-6 mb-3">
        <div class="mon-card">
            <div class="mon-card-head">{{ translate('Features with most failures') }}</div>
            <div class="mon-card-body p-0">
                <table class="mini-table">
                    <thead><tr><th>{{ translate('Feature') }}</th><th>{{ translate('Failed') }}</th><th>{{ translate('Total') }}</th><th>{{ translate('Fail rate') }}</th></tr></thead>
                    <tbody>
                    @forelse($data['top_features'] as $f)
                        <tr>
                            <td>{{ $f['feature'] }}</td>
                            <td>{{ $f['failed'] }}</td>
                            <td>{{ $f['total'] }}</td>
                            <td>
                                @if ($f['rate'] >= 20) <span class="text-danger font-weight-600">{{ $f['rate'] }}%</span>
                                @elseif ($f['rate'] >= 5) <span class="text-warning font-weight-600">{{ $f['rate'] }}%</span>
                                @else <span class="text-success">{{ $f['rate'] }}%</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No failures in this window') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top GSC queries --}}
    <div class="col-lg-6 mb-3">
        <div class="mon-card">
            <div class="mon-card-head">{{ translate('Top Search Console queries (28d)') }}</div>
            <div class="mon-card-body p-0">
                <table class="mini-table">
                    <thead><tr><th>{{ translate('Query') }}</th><th>{{ translate('Clicks') }}</th><th>{{ translate('Impr.') }}</th><th>{{ translate('CTR') }}</th><th>{{ translate('Pos.') }}</th></tr></thead>
                    <tbody>
                    @forelse($data['gsc_queries'] as $q)
                        <tr>
                            <td class="text-truncate-cell" title="{{ $q['value'] }}">{{ $q['value'] }}</td>
                            <td>{{ $q['clicks'] }}</td>
                            <td>{{ $q['impressions'] }}</td>
                            <td>{{ $q['ctr'] }}%</td>
                            <td>{{ $q['position'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('GSC not synced yet — configure in settings.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Keyword movers --}}
    <div class="col-lg-6 mb-3">
        <div class="mon-card">
            <div class="mon-card-head">{{ translate('Biggest keyword rank changes') }}</div>
            <div class="mon-card-body p-0">
                <table class="mini-table">
                    <thead><tr><th>{{ translate('Keyword') }}</th><th>{{ translate('Prev') }}</th><th>{{ translate('Now') }}</th><th>Δ</th></tr></thead>
                    <tbody>
                    @forelse($data['keyword_movers'] as $k)
                        <tr>
                            <td class="text-truncate-cell" title="{{ $k['keyword'] }}">{{ $k['keyword'] }}</td>
                            <td>{{ $k['previous'] }}</td>
                            <td>{{ $k['current'] }}</td>
                            <td>
                                @if ($k['delta'] > 0)
                                    <span class="delta-up">▲ {{ $k['delta'] }}</span>
                                @elseif ($k['delta'] < 0)
                                    <span class="delta-down">▼ {{ abs($k['delta']) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No tracked keywords yet — add via Keyword Tracker.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Broken links --}}
    <div class="col-lg-12 mb-3">
        <div class="mon-card">
            <div class="mon-card-head">
                {{ translate('Broken links') }}
                <span class="text-muted small">
                    {{ translate('open') }}: <strong class="text-danger">{{ $data['broken_links']['broken'] }}</strong> &nbsp;
                    {{ translate('timeout') }}: <strong class="text-warning">{{ $data['broken_links']['timeout'] }}</strong> &nbsp;
                    {{ translate('resolved') }}: <strong class="text-success">{{ $data['broken_links']['resolved'] }}</strong>
                </span>
            </div>
            <div class="mon-card-body p-0">
                <table class="mini-table">
                    <thead><tr><th>{{ translate('From') }}</th><th>{{ translate('To') }}</th><th>{{ translate('Code') }}</th><th>{{ translate('Hits') }}</th><th>{{ translate('Last check') }}</th></tr></thead>
                    <tbody>
                    @forelse($data['broken_links']['samples'] as $b)
                        <tr>
                            <td class="text-truncate-cell" title="{{ $b->source_url }}">{{ $b->source_url }}</td>
                            <td class="text-truncate-cell" title="{{ $b->target_url }}">{{ $b->target_url }}</td>
                            <td><span class="badge badge-soft-danger">{{ $b->status_code ?: '—' }}</span></td>
                            <td>{{ $b->hit_count }}</td>
                            <td class="text-muted small">{{ $b->last_checked_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">{{ translate('No broken links detected yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const costSeries  = @json($data['cost_series']);
    const runSeries   = @json($data['run_series']);
    const scoreSeries = @json($data['score_series']);

    const labels = costSeries.map(r => r.date);
    const opts = {
        responsive: true,
        plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: { x: { ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { font: { size: 10 } } } }
    };

    if (window.Chart && document.getElementById('costChart')) {
        new Chart(document.getElementById('costChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'USD spent',
                    data: costSeries.map(r => r.usd),
                    backgroundColor: 'rgba(78,115,223,.4)',
                    borderColor: '#4e73df',
                    borderWidth: 1,
                }]
            },
            options: opts
        });
    }

    if (window.Chart && document.getElementById('runChart')) {
        new Chart(document.getElementById('runChart'), {
            type: 'bar',
            data: {
                labels: runSeries.map(r => r.date),
                datasets: [
                    { label: 'completed', data: runSeries.map(r => r.completed), backgroundColor: '#1cc88a' },
                    { label: 'failed',    data: runSeries.map(r => r.failed),    backgroundColor: '#e74a3b' },
                    { label: 'other',     data: runSeries.map(r => r.other),     backgroundColor: '#b0b4bc' },
                ]
            },
            options: Object.assign({}, opts, { scales: Object.assign({}, opts.scales, { x: Object.assign({ stacked: true }, opts.scales.x), y: Object.assign({ stacked: true }, opts.scales.y) }) })
        });
    }

    if (window.Chart && document.getElementById('scoreChart')) {
        new Chart(document.getElementById('scoreChart'), {
            type: 'line',
            data: {
                labels: scoreSeries.map(r => r.date),
                datasets: [{
                    label: 'avg score',
                    data: scoreSeries.map(r => r.avg_score),
                    borderColor: '#36b9cc',
                    backgroundColor: 'rgba(54,185,204,.15)',
                    fill: true,
                    spanGaps: true,
                    tension: .25,
                    pointRadius: 2,
                }]
            },
            options: Object.assign({}, opts, { scales: Object.assign({}, opts.scales, { y: { suggestedMin: 0, suggestedMax: 100, ticks: { font: { size: 10 } } } }) })
        });
    }
})();
</script>
@endsection
