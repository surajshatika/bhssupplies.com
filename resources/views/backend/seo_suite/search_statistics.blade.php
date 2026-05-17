@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-chart-bar mr-2 text-info"></i>{{ translate('Search Statistics') }}</h1>
            <p class="text-muted mb-0">{{ translate('SEO performance analytics, score trends, and Google Search Console data.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

@php
    $localStats = $result['local_stats'] ?? [];
    $gscData    = $result['gsc_data'] ?? [];
    $topPages   = $result['top_pages'] ?? [];
    $avgScore   = count($localStats) ? round(array_sum(array_column($localStats, 'score')) / count($localStats)) : 0;
    $maxScore   = count($localStats) ? max(array_column($localStats, 'score')) : 0;
    $minScore   = count($localStats) ? min(array_column($localStats, 'score')) : 0;
@endphp

{{-- Summary Cards --}}
<div class="row gutters-16 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-info">{{ count($localStats) }}</div>
                <div class="text-muted small">{{ translate('Pages Tracked') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-success">{{ $avgScore }}</div>
                <div class="text-muted small">{{ translate('Avg SEO Score') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-primary">{{ $maxScore }}</div>
                <div class="text-muted small">{{ translate('Best Score') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 {{ $result['gsc_connected'] ? 'text-success' : 'text-muted' }}">
                    {{ $result['gsc_connected'] ? translate('Connected') : translate('Not Connected') }}
                </div>
                <div class="text-muted small">{{ translate('Search Console') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        {{-- Score History Chart --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">{{ translate('SEO Score History (Last 28 Days)') }}</h6></div>
            <div class="card-body">
                @if(count($localStats) > 0)
                    <canvas id="scoreChart" height="100"></canvas>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="las la-chart-line la-2x d-block mb-2"></i>
                        {{ translate('No score history yet. Run SEO audits to populate this chart.') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Top Pages --}}
        @if(count($topPages) > 0)
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">{{ translate('Top Performing Pages') }}</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ translate('URL') }}</th><th>{{ translate('Score') }}</th><th>{{ translate('Grade') }}</th></tr></thead>
                        <tbody>
                            @foreach($topPages as $page)
                            <tr>
                                <td class="text-truncate" style="max-width:320px;">
                                    <a href="{{ $page['url'] ?? '#' }}" target="_blank">{{ $page['url'] ?? '' }}</a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-2" style="height:6px;">
                                            <div class="progress-bar bg-{{ ($page['score'] ?? 0) >= 80 ? 'success' : (($page['score'] ?? 0) >= 50 ? 'warning' : 'danger') }}"
                                                 style="width:{{ $page['score'] ?? 0 }}%"></div>
                                        </div>
                                        <strong>{{ $page['score'] ?? 0 }}</strong>
                                    </div>
                                </td>
                                <td><span class="badge badge-soft-info">{{ $page['grade'] ?? '-' }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- GSC Data --}}
        @if(!empty($gscData))
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Google Search Console — Top Queries') }}</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ translate('Query') }}</th><th>{{ translate('Clicks') }}</th><th>{{ translate('Impressions') }}</th><th>{{ translate('CTR') }}</th><th>{{ translate('Position') }}</th></tr></thead>
                        <tbody>
                            @foreach($gscData as $row)
                            <tr>
                                <td>{{ $row['keys'][0] ?? '' }}</td>
                                <td>{{ number_format($row['clicks'] ?? 0) }}</td>
                                <td>{{ number_format($row['impressions'] ?? 0) }}</td>
                                <td>{{ number_format(($row['ctr'] ?? 0) * 100, 1) }}%</td>
                                <td>{{ number_format($row['position'] ?? 0, 1) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if(!empty($result['ai_analysis']))
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="las la-robot mr-1 text-primary"></i>{{ translate('AI Analysis') }}</h6></div>
            <div class="card-body">
                <div style="white-space:pre-wrap; font-size:0.8rem; line-height:1.6; max-height:400px; overflow-y:auto;">{{ $result['ai_analysis'] }}</div>
            </div>
        </div>
        @endif

        @if(!$result['gsc_connected'])
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Connect Search Console') }}</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ translate('Connect Google Search Console to see real impressions, clicks, and keyword data.') }}</p>
                <ol class="small text-muted pl-3">
                    <li>{{ translate('Go to Google Search Console') }}</li>
                    <li>{{ translate('Create/verify your property') }}</li>
                    <li>{{ translate('Generate an API access token') }}</li>
                    <li>{{ translate('Add it to SEO Settings → Search Console Token') }}</li>
                </ol>
                <a href="{{ route('admin.seo-suite.webmaster') }}" class="btn btn-soft-primary btn-sm btn-block">
                    {{ translate('Go to Webmaster Tools') }}
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
@if(count($localStats) > 0)
var labels = @json(array_map(fn($s) => \Carbon\Carbon::parse($s['recorded_at'])->format('M d'), $localStats));
var scores = @json(array_column($localStats, 'score'));

new Chart(document.getElementById('scoreChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'SEO Score',
            data: scores,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78,115,223,0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#4e73df'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { min: 0, max: 100, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
@endif
</script>
@endsection
