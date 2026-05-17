@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="row">
    {{-- HERO BAND --}}
    <div class="col-12">
        <div class="mm-hero mm-hero--marketing">
            <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="mm-hero-icon mr-3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 6-6"/></svg>
                    </div>
                    <div>
                        <h2>{{ translate('Google Analytics (GA4) Dashboard') }}</h2>
                        <p>{{ translate('Live traffic insights, audience geography, content performance — all in one professional view.') }}</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
                    <span class="mm-chip"><i class="las la-bolt"></i> {{ translate('Live') }}</span>
                    <span class="mm-chip"><i class="las la-globe"></i> GA4</span>
                </div>
            </div>
        </div>
    </div>

    {{-- DATE RANGE TOOLBAR --}}
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div class="text-muted small">
                <i class="las la-calendar"></i>
                {{ translate('Showing') }} <strong>{{ $rangeLabel ?? 'Last '.$days.' days' }}</strong>
            </div>
            <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('google-analytics-test.result', ['days' => 1]) }}"  class="btn {{ (empty($fromDate) && $days == 1)  ? 'btn-primary' : 'btn-outline-primary' }}">{{translate('Today')}}</a>
                    <a href="{{ route('google-analytics-test.result', ['days' => 7]) }}"  class="btn {{ (empty($fromDate) && $days == 7)  ? 'btn-primary' : 'btn-outline-primary' }}">7 {{translate('Days')}}</a>
                    <a href="{{ route('google-analytics-test.result', ['days' => 30]) }}" class="btn {{ (empty($fromDate) && $days == 30) ? 'btn-primary' : 'btn-outline-primary' }}">30 {{translate('Days')}}</a>
                    <a href="{{ route('google-analytics-test.result', ['days' => 90]) }}" class="btn {{ (empty($fromDate) && $days == 90) ? 'btn-primary' : 'btn-outline-primary' }}">90 {{translate('Days')}}</a>
                    <a href="{{ route('google-analytics-test.result', ['days' => 365]) }}" class="btn {{ (empty($fromDate) && $days == 365)? 'btn-primary' : 'btn-outline-primary' }}">1 {{translate('Year')}}</a>
                </div>
                <form action="{{ route('google-analytics-test.result') }}" method="GET" class="d-inline-flex align-items-center" style="gap:.4rem;">
                    <input type="date" name="from" value="{{ $fromDate ?? '' }}" class="form-control form-control-sm" style="width:auto;" max="{{ now()->toDateString() }}">
                    <span class="text-muted small">{{ translate('to') }}</span>
                    <input type="date" name="to" value="{{ $toDate ?? '' }}" class="form-control form-control-sm" style="width:auto;" max="{{ now()->toDateString() }}">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="las la-search"></i></button>
                </form>
            </div>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($realtimeUsers) }}</h3>
            <div class="mm-stat-label">{{ translate('Active Users Now') }}</div>
            <span class="mm-stat-delta up"><span class="mm-dot ok"></span> {{ translate('Live') }}</span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($totals['activeUsers'] ?? 0) }}</h3>
            <div class="mm-stat-label">{{ translate('Total Active Users') }}</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($totals['screenPageViews'] ?? 0) }}</h3>
            <div class="mm-stat-label">{{ translate('Page Views') }}</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ $realtimePerMinute }}<small class="text-muted" style="font-size:14px;">/min</small></h3>
            <div class="mm-stat-label">{{ translate('Avg. Per Minute') }}</div>
        </div>
    </div>

    <!-- Visitors Trend Chart -->
    <div class="col-lg-8">
        <div class="dashboard-box card mb-2rem h-lg-510px">
            <div class="card-header border-0 p-0 h-auto">
                <h5 class="mb-0 fs-16 fw-600">{{ translate('Visitors & Page Views Trend') }}</h5>
            </div>
            <div class="card-body p-0">
                <canvas id="visitors-trend-chart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Browsers & Countries -->
    <div class="col-lg-4">
        <!-- Realtime Live Block (true GA4 runRealtimeReport) -->
        <div class="mm-card mb-3">
            <div class="mm-card-body" style="padding:1rem 1.25rem;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="d-flex align-items-center mb-1">
                            <span class="mm-dot ok" style="width:10px;height:10px;"></span>
                            <h6 class="mb-0 fw-600">{{ translate('Realtime — Last 30 min') }}</h6>
                        </div>
                        <h2 class="mm-stat-value mb-0">{{ number_format($realtimeUsers) }}</h2>
                        <div class="mm-stat-label">{{ translate('active users') }}</div>
                    </div>
                    <div class="spinner-grow text-success" role="status" style="width:1.5rem;height:1.5rem;">
                        <span class="sr-only">{{ translate('Live')}}</span>
                    </div>
                </div>
                <hr class="my-2">
                <div class="small text-muted mb-1">
                    <strong>{{ $realtimeLast5Min }}</strong> {{ translate('users in last 5 min') }}
                </div>
                @if(!empty($realtimeByCountry))
                    <div class="mt-2" style="max-height:110px;overflow-y:auto;">
                        @foreach($realtimeByCountry as $rc)
                            <div class="d-flex justify-content-between align-items-center py-1" style="font-size:12.5px;">
                                <span><i class="las la-map-marker-alt text-muted"></i> {{ $rc['country'] }}</span>
                                <strong>{{ $rc['active_users'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="text-muted" style="font-size:11px;margin-top:.5rem;">
                    <i class="las la-sync la-spin"></i> {{ translate('Auto-refresh every 30s') }}
                </div>
            </div>
        </div>
        <div class="dashboard-box g-analytics-card mb-2rem">
            <div class="card-header border-0 p-0 bg-white">
                <h5 class="mb-0 fs-16 fw-600">{{ translate('Top Browsers') }}</h5>
            </div>
            <div class="card-body p-0 h-260px c-scrollbar-light">
                <table class="table table-vertical-middle mb-0">
                    <thead>
                        <tr>
                            <th class="pl-0">{{ translate('Browser') }}</th>
                            <th class="text-right">{{ translate('Views') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topBrowsers as $browser)
                        <tr>
                            <td class="pl-0">
                                <span class="badge badge-md badge-dot badge-circle badge-info mr-2"></span>
                                {{ $browser['browser'] }}
                            </td>
                            <td class="text-right">{{ $browser['screenPageViews'] }}</td>
                        </tr>
                        @endforeach 
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Pages -->
    <div class="col-lg-6">
        <div class="dashboard-box card mb-2rem g-analytics-card">
            <div class="card-header border-0 p-0 h-auto mb-3">
                <h5 class="mb-1 fs-16 fw-600">{{ translate('Most Visited Pages') }}</h5>
            </div>
            <div class="card-body px-0 py-0 h-400px c-scrollbar-light">
                <table class="table table-vertical-middle aiz-table mb-0">
                    <thead class="bg-soft-secondary">
                        <tr>
                            <th>{{ translate('Page Title') }}</th>
                            <th class="text-center">{{ translate('Views') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topPages as $page)
                        <tr>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;" title="{{ $page['pageTitle'] }}">
                                    {{ $page['pageTitle'] }}
                                </div>
                                <small class="text-muted text-truncate d-block">
                                    {{ Str::limit($page['fullPageUrl'], 50) }}
                                </small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-inline text-dark badge-soft-primary">{{ $page['screenPageViews'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Countries & Referrers -->
    <div class="col-lg-6">
        <div class="row gutters-16">
            <!-- Top Countries -->
            <div class="col-md-6">
                <div class="dashboard-box card mb-2rem g-analytics-card">
                    <div class="card-header border-0 p-0 h-auto">
                        <h5 class="mb-0 fs-16 fw-600">{{ translate('Top Countries') }}</h5>
                    </div>
                    <div class="card-body p-0 h-290px h-lg-420px c-scrollbar-light">
                        <table class="table table-vertical-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-0">{{ translate('Country') }}</th>
                                    <th class="text-right">{{ translate('Views') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCountries as $country)
                                @if(!empty($country['country']))
                                <tr>
                                    <td class="pl-0">
                                        <span class="mr-2"><svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#1433D3"><path d="M480.28-96Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q122 0 210-81t100-200q-9 8-20.5 12.5T744-432H600q-29.7 0-50.85-21.15Q528-474.3 528-504v-48H360v-96q0-29.7 21.15-50.85Q402.3-720 432-720h48v-24q0-14 5-26t13-21q-3-1-10-1h-8q-130 0-221 91t-91 221h216q60 0 102 42t42 102v48H384v105q23 8 46.73 11.5Q454.45-168 480-168Z"/></svg></span>
                                        {{ $country['country'] }}
                                    </td>
                                    <td class="text-right">{{ $country['screenPageViews'] }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Referrers -->
            <div class="col-md-6">
                <div class="dashboard-box card mb-3 g-analytics-card">
                    <div class="card-header border-0 p-0">
                        <h5 class="mb-0 fs-16 fw-600">{{ translate('Top Referrers') }}</h5>
                    </div>
                    <div class="card-body p-0 h-290px h-lg-420px c-scrollbar-light">
                        <table class="table table-vertical-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-0">{{ translate('Source') }}</th>
                                    <th class="text-right">{{ translate('Views') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topReferrers as $referrer)
                                <tr>
                                    <td class="pl-0">
                                        <div class="text-truncate" style="max-width: 150px;" title="{{ $referrer['pageReferrer'] ?: 'Direct' }}">
                                            @if(empty($referrer['pageReferrer']))
                                                {{ translate('Direct') }}
                                            @else
                                                {{ Str::limit($referrer['pageReferrer'], 30) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right">{{ $referrer['screenPageViews'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Auto-refresh page every 60s while the realtime block is visible — keeps numbers fresh
    // without burning GA quota (server-side block is 30s-cached).
    (function() {
        var refreshTimer = setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 60000);
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState !== 'visible') clearInterval(refreshTimer);
        });
    })();

    (function() {
        // Visitors Trend Chart
        var ctx = document.getElementById('visitors-trend-chart').getContext('2d');

        var trendData = @json($visitorsTrend);
        var labels      = trendData.map(item => (item.pageTitle || 'Page').split(' ').slice(0, 3).join(' ') + (item.pageTitle && item.pageTitle.split(' ').length > 3 ? '…' : ''));
        var activeUsers = trendData.map(item => item.activeUsers);
        var pageViews   = trendData.map(item => item.screenPageViews);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '{{ translate('Active Users') }}',
                        data: activeUsers,
                        borderColor: '#009ef7',
                        backgroundColor: 'rgba(0, 158, 247, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '{{ translate('Page Views') }}',
                        data: pageViews,
                        borderColor: '#19c553',
                        backgroundColor: 'rgba(25, 197, 83, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Add tooltips for truncated text
        $('[title]').tooltip({
            placement: 'auto',
            trigger: 'hover'
        });

    })();
</script>
@endsection