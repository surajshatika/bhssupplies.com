@extends('backend.layouts.app')
@section('content')
@include('backend.partials.modern_module_styles')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0"><i class="las la-chart-area mr-2 text-primary"></i>{{ translate('Predictive Traffic & ROI Forecasting') }}</h4>
            <small class="text-muted">{{ translate('Identify high-value keyword opportunities and forecast the traffic and revenue gains from ranking improvements.') }}</small>
        </div>
        <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
        </a>
    </div>

    {{-- How it works alert --}}
    <div class="alert alert-info alert-dismissible fade show py-2 small mb-4" role="alert">
        <i class="las la-info-circle mr-1"></i>
        <strong>{{ translate('How it works:') }}</strong>
        {{ translate('The AI analyzes your current Google Search Console performance and identifies "striking distance" keywords (positions 4-20). It uses a Click-Through Rate (CTR) curve model to estimate how much extra traffic and revenue ($1.50 average value per click) you could gain by moving up 3 positions in Google.') }}
        <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header border-bottom-0">
                    <h6 class="mb-0"><i class="las la-chart-area text-primary mr-1"></i> {{ translate('Estimated Traffic Growth Curve') }}</h6>
                </div>
                <div class="card-body">
                    <div id="chart-roi-forecast" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="las la-bullseye text-primary mr-1"></i> {{ translate('Top ROI Opportunities (Striking Distance)') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Target Query & Page') }}</th>
                            <th>{{ translate('Current Rank') }}</th>
                            <th>{{ translate('Target Rank') }}</th>
                            <th>{{ translate('Traffic Gain (Est. Clicks)') }}</th>
                            <th>{{ translate('ROI Potential') }}</th>
                            <th class="text-right">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecasts as $forecast)
                            <tr>
                                <td>
                                    <div class="font-weight-600">{{ $forecast['query'] }}</div>
                                    <a href="{{ $forecast['page'] }}" target="_blank" class="small text-muted text-truncate d-inline-block" style="max-width: 250px;" title="{{ $forecast['page'] }}">{{ str_replace(url('/'), '', $forecast['page']) ?: '/' }}</a>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $forecast['current_position'] }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-success"><i class="las la-arrow-up mr-1"></i>{{ $forecast['target_position'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted mr-2">{{ $forecast['current_clicks'] }}</span>
                                        <i class="las la-long-arrow-alt-right text-success mr-2"></i>
                                        <span class="font-weight-bold text-success">+{{ $forecast['potential_gain'] }} ({{ $forecast['predicted_clicks'] }})</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-success font-weight-bold">${{ number_format($forecast['roi'], 2) }}</span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.seo_optimization.index') }}" class="btn btn-xs btn-primary" title="{{ translate('Queue for AI Rewrite') }}">
                                        <i class="las la-robot"></i> {{ translate('Optimize') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="las la-chart-bar la-3x text-muted mb-3 d-block"></i>
                                    {{ translate('Not enough data to calculate forecasts. Ensure Google Search Console is connected and has gathered data.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(function() {
    var forecasts = {!! json_encode(collect($forecasts)->take(10)->toArray()) !!};
    if (forecasts.length > 0) {
        var categories = forecasts.map(function(f) { return f.query; });
        var currentTraffic = forecasts.map(function(f) { return f.current_clicks; });
        var projectedTraffic = forecasts.map(function(f) { return f.predicted_clicks; });
        
        var options = {
            series: [
                { name: 'Estimated Future Traffic', data: projectedTraffic },
                { name: 'Current Traffic Baseline', data: currentTraffic }
            ],
            chart: { type: 'area', height: 350, toolbar: { show: false } },
            colors: ['#1cc88a', '#4e73df'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { categories: categories, labels: { show: false } },
            tooltip: { x: { show: true } },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1, stops: [0, 90, 100] }
            }
        };
        new ApexCharts(document.querySelector("#chart-roi-forecast"), options).render();
    }
});
</script>
@endsection
