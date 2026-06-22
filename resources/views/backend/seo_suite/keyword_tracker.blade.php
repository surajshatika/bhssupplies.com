@extends('backend.layouts.app')

@section('content')
@php
    $keywordIntelligence = $keywordIntelligence ?? [];
    $targetKeywords = collect($keywordIntelligence['target_keywords'] ?? []);
    $trackedKeywords = collect($keywordIntelligence['tracked_keywords'] ?? []);
    $gscKeywordPages = collect($keywordIntelligence['gsc_keyword_pages'] ?? []);
@endphp

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-chart-line mr-2 text-success"></i>{{ translate('Keyword Intelligence & Google Rankings') }}</h1>
            <p class="text-muted mb-0">{{ translate('Track Canada/GTA target keywords, Google result pages, ranking URLs, and Search Console landing-page visibility.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

<div class="row gutters-10 mb-3">
    @foreach([
        [translate('Target Keywords'), $keywordIntelligence['target_keyword_count'] ?? 0, 'primary'],
        [translate('Tracked Keywords'), $keywordIntelligence['tracked_count'] ?? 0, 'info'],
        [translate('Ranking Keywords'), $keywordIntelligence['ranked_count'] ?? 0, 'success'],
        [translate('Google Page 1'), $keywordIntelligence['page_one_count'] ?? 0, 'warning'],
        [translate('Autopilot Focus'), $keywordIntelligence['generated_focus_keyword_count'] ?? 0, 'secondary'],
        [translate('GSC Query URLs'), $keywordIntelligence['gsc_keyword_page_count'] ?? 0, 'danger'],
    ] as [$label, $value, $color])
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="card">
                <div class="card-body py-3">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h3 mb-0 text-{{ $color }}">{{ number_format($value) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if(($keywordIntelligence['gsc_keyword_page_count'] ?? 0) === 0)
    <div class="alert alert-soft-info d-flex flex-wrap align-items-center justify-content-between mb-3">
        <span>
            <i class="las la-info-circle mr-1"></i>
            {{ translate('Google Search Console landing-page data is not synced yet. Connect GSC to see which keyword displays which website page in Google.') }}
        </span>
        <a href="{{ route('admin.seo-suite.settings.view') }}" class="btn btn-xs btn-soft-primary mt-2 mt-md-0">{{ translate('Open GSC Settings') }}</a>
    </div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Check & Track Google Rankings') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('Keywords') }} <small class="text-muted">({{ translate('one per line or comma-separated') }})</small></label>
                    <textarea id="kw-keywords" class="form-control" rows="12">{{ $targetKeywords->take(20)->implode("\n") }}</textarea>
                </div>
                <div class="form-group">
                    <label>{{ translate('Search Engine') }}</label>
                    <select id="kw-engine" class="form-control">
                        <option value="google">Google Canada</option>
                    </select>
                </div>
                <button id="kw-check-btn" class="btn btn-primary w-100">
                    <i class="las la-search mr-1"></i>{{ translate('Check & Save Rankings') }}
                </button>
                <div class="mt-3 p-3 bg-light rounded small text-muted">
                    {{ translate('Configure SerpAPI in SEO Settings for accurate Google top-100 ranking checks. Saved keywords are refreshed automatically by cron.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div id="kw-loading" class="card d-none">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-success mb-3"></div>
                <div>{{ translate('Checking Google Canada keyword rankings...') }}</div>
            </div>
        </div>

        <div id="kw-results-card" class="card d-none">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Latest Ranking Results') }}</h6>
                <span id="kw-summary" class="badge badge-soft-success"></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="kw-results-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Keyword') }}</th>
                                <th>{{ translate('Rank') }}</th>
                                <th>{{ translate('Google Page') }}</th>
                                <th>{{ translate('Move') }}</th>
                                <th>{{ translate('Ranking URL') }}</th>
                                <th>{{ translate('Source') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="kw-ai-card" class="card mt-3 d-none">
            <div class="card-header"><h6 class="mb-0"><i class="las la-robot mr-1 text-primary"></i>{{ translate('AI Ranking Insights') }}</h6></div>
            <div class="card-body">
                <div id="kw-ai-content" style="white-space:pre-wrap; font-size:0.875rem; line-height:1.6;"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Canada/GTA Target Strategy') }}</h6></div>
            <div class="card-body">
                @foreach(($keywordIntelligence['groups'] ?? []) as $label => $values)
                    <div class="mb-3">
                        <div class="font-weight-bold small text-muted mb-2">{{ translate($label) }}</div>
                        <div class="d-flex flex-wrap" style="gap:.35rem;">
                            @foreach($values as $value)
                                <span class="badge badge-soft-primary px-2 py-2">{{ $value }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="font-weight-bold small text-muted mb-2">{{ translate('All targeted keywords') }}</div>
                <div class="border rounded p-2" style="max-height:280px; overflow:auto;">
                    <div class="d-flex flex-wrap" style="gap:.35rem;">
                        @foreach($targetKeywords as $keyword)
                            <span class="badge badge-soft-secondary px-2 py-2">{{ $keyword }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    {{ number_format($keywordIntelligence['generated_focus_keyword_count'] ?? 0) }} {{ translate('distinct URL-level focus keywords are also generated by SEO autopilot.') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Share of Voice Chart --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Share of Voice vs Competitors (Estimated)') }}</h6>
            </div>
            <div class="card-body">
                <div id="chart-sov" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h6 class="mb-0">{{ translate('Saved Google Rank Tracker') }}</h6>
        <small class="text-muted">{{ translate('Cron refreshes active keywords every 6 hours.') }}</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Keyword') }}</th>
                        <th>{{ translate('Rank') }}</th>
                        <th>{{ translate('Share of Voice') }}</th>
                        <th>{{ translate('Competitors') }}</th>
                        <th>{{ translate('Movement') }}</th>
                        <th>{{ translate('SERP Features') }}</th>
                        <th>{{ translate('Checked') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trackedKeywords as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['keyword'] }}</strong>
                                @if(!empty($row['search_volume']))
                                    <div class="small text-muted">Vol: {{ number_format($row['search_volume']) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($row['rank'] > 0)
                                    <span class="badge badge-{{ $row['rank'] <= 3 ? 'success' : ($row['rank'] <= 10 ? 'primary' : 'secondary') }}">#{{ $row['rank'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="progress" style="height: 6px; width: 80px; margin-top: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $row['sov'] }}%;" aria-valuenow="{{ $row['sov'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="small text-muted">{{ $row['sov'] }}% SoV</div>
                            </td>
                            <td>
                                @if(!empty($row['competitors']))
                                    @foreach($row['competitors'] as $comp)
                                        <div class="small text-truncate" style="max-width:150px;">
                                            <span class="text-muted">{{ $comp['domain'] }}:</span> 
                                            <strong>{{ $comp['rank'] > 0 ? '#' . $comp['rank'] : '-' }}</strong>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if(($row['movement'] ?? null) > 0)
                                    <span class="text-success"><i class="las la-arrow-up"></i> {{ $row['movement'] }}</span>
                                @elseif(($row['movement'] ?? null) < 0)
                                    <span class="text-danger"><i class="las la-arrow-down"></i> {{ abs($row['movement']) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($row['serp_features']))
                                    <div class="d-flex flex-wrap" style="gap: 2px;">
                                        @foreach($row['serp_features'] as $feature)
                                            @php
                                                $icon = match($feature) {
                                                    'Featured Snippet' => 'la-crown text-warning',
                                                    'Local Pack' => 'la-map-marker text-danger',
                                                    'People Also Ask' => 'la-question-circle text-info',
                                                    'Knowledge Panel' => 'la-book text-primary',
                                                    'Videos' => 'la-play-circle text-danger',
                                                    'Images' => 'la-image text-success',
                                                    default => 'la-cube text-secondary'
                                                };
                                            @endphp
                                            <span class="badge badge-soft-secondary" title="{{ $feature }}"><i class="las {{ $icon }}"></i></span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-nowrap small text-muted">{{ $row['checked_at'] ? \Carbon\Carbon::parse($row['checked_at'])->format('M d, Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ translate('No tracked keywords yet. Run a Google ranking check above to save your first keywords.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h6 class="mb-0">{{ translate('Google Search Console: Keyword to Landing Page') }}</h6>
        <small class="text-muted">{{ translate('Real Google visibility data synced by cron every 6 hours.') }}</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Google Query') }}</th>
                        <th>{{ translate('Avg Position') }}</th>
                        <th>{{ translate('Google Page') }}</th>
                        <th>{{ translate('Impressions') }}</th>
                        <th>{{ translate('Clicks') }}</th>
                        <th>{{ translate('Landing URL') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gscKeywordPages as $row)
                        <tr>
                            <td><strong>{{ $row['query'] }}</strong></td>
                            <td>{{ number_format($row['position'], 1) }}</td>
                            <td>{{ $row['google_page'] ? 'Page ' . $row['google_page'] : '-' }}</td>
                            <td>{{ number_format($row['impressions']) }}</td>
                            <td>{{ number_format($row['clicks']) }}</td>
                            <td class="text-truncate" style="max-width:360px;"><a href="{{ $row['page'] }}" target="_blank" rel="noopener">{{ $row['page'] }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ translate('No query-to-page rows yet. Connect Google Search Console and run seo:sync-search-console once.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Parse SoV data
var keywordData = {!! json_encode($trackedKeywords->map(function($kw) {
    return [
        'keyword' => $kw['keyword'],
        'sov' => $kw['sov'],
        'competitors' => collect($kw['competitors'] ?? [])->mapWithKeys(fn($c) => [$c['domain'] => ($c['rank'] > 0 ? (100 - $c['rank']) : 0)])->toArray()
    ];
})->take(15)->toArray()) !!};

var chartCategories = keywordData.map(function(k) { return k.keyword; });
var mySov = keywordData.map(function(k) { return k.sov; });

var compDomains = [];
keywordData.forEach(function(k) {
    Object.keys(k.competitors).forEach(function(domain) {
        if (!compDomains.includes(domain)) compDomains.push(domain);
    });
});

var chartSeries = [{ name: 'Your Domain', data: mySov }];
compDomains.forEach(function(domain) {
    chartSeries.push({
        name: domain,
        data: keywordData.map(function(k) { return k.competitors[domain] || 0; })
    });
});

if (chartCategories.length > 0) {
    var options = {
        series: chartSeries,
        chart: { type: 'bar', height: 300, stacked: true, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, borderRadius: 2 } },
        xaxis: { categories: chartCategories },
        yaxis: { max: 100 },
        colors: ['#1cc88a', '#f6c23e', '#4e73df', '#e74a3b'],
        fill: { opacity: 1 },
        legend: { position: 'top', horizontalAlign: 'right' },
        dataLabels: { enabled: false }
    };
    new ApexCharts(document.querySelector("#chart-sov"), options).render();
}

var statusBadges = {
    'top_3': '<span class="badge badge-success">Top 3</span>',
    'page_1': '<span class="badge badge-info">Page 1</span>',
    'page_2': '<span class="badge badge-warning">Page 2</span>',
    'page_3_10': '<span class="badge badge-soft-warning">Page 3-10</span>',
    'not_found': '<span class="badge badge-secondary">Not Found</span>',
    'error': '<span class="badge badge-danger">Setup Required</span>'
};

function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function safeUrl(value) {
    try {
        var url = new URL(value);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
    } catch (e) {
        return '';
    }
}

$(function() {
    $('#kw-check-btn').on('click', function() {
        var keywords = $('#kw-keywords').val().trim();
        if (!keywords) { alert('Please enter keywords.'); return; }

        $('#kw-loading').removeClass('d-none');
        $('#kw-results-card, #kw-ai-card').addClass('d-none');
        $('#kw-check-btn').prop('disabled', true);

        $.ajax({
            url: '{{ route('admin.seo-suite.keyword_tracker.check') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', keywords: keywords, engine: $('#kw-engine').val() },
            success: function(res) {
                $('#kw-loading').addClass('d-none');
                $('#kw-check-btn').prop('disabled', false);

                if (res.error) { alert(res.error); return; }

                var results = res.results || [];
                var found = results.filter(function(r) { return Number(r.rank) > 0; }).length;
                $('#kw-summary').text(found + ' / ' + (res.keyword_count || 0) + ' ranking');

                var tbody = $('#kw-results-table tbody').empty();
                results.forEach(function(r) {
                    var url = safeUrl(r.url);
                    var movement = r.movement > 0
                        ? '<span class="text-success">+' + Number(r.movement) + '</span>'
                        : (r.movement < 0 ? '<span class="text-danger">' + Number(r.movement) + '</span>' : '<span class="text-muted">-</span>');
                    tbody.append(
                        '<tr>' +
                        '<td><strong>' + escapeHtml(r.keyword) + '</strong><div>' + (statusBadges[r.status] || '') + '</div></td>' +
                        '<td>' + (Number(r.rank) > 0 ? '<strong>#' + Number(r.rank) + '</strong>' : '<span class="text-muted">-</span>') + '</td>' +
                        '<td>' + escapeHtml(r.google_page_label || '-') + '</td>' +
                        '<td>' + movement + '</td>' +
                        '<td class="text-truncate" style="max-width:240px;">' + (url ? '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" class="small">' + escapeHtml(url) + '</a>' : '<span class="text-muted small">-</span>') + '</td>' +
                        '<td class="small">' + escapeHtml(r.source || '-') + (r.error ? '<div class="text-danger">' + escapeHtml(r.error) + '</div>' : '') + '</td>' +
                        '</tr>'
                    );
                });
                $('#kw-results-card').removeClass('d-none');

                if (res.ai_insights) {
                    $('#kw-ai-content').text(res.ai_insights);
                    $('#kw-ai-card').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#kw-loading').addClass('d-none');
                $('#kw-check-btn').prop('disabled', false);
                alert('Error: ' + (xhr.responseJSON?.message || 'Request failed'));
            }
        });
    });
});
</script>
@endsection
