@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-chart-line mr-2 text-success"></i>{{ translate('Keyword Rank Tracker') }}</h1>
            <p class="text-muted mb-0">{{ translate('Track your keyword positions in Google and get AI-powered improvement insights.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Check Keyword Rankings') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('Keywords') }} <small class="text-muted">({{ translate('one per line or comma-separated') }})</small></label>
                    <textarea id="kw-keywords" class="form-control" rows="8"
                        placeholder="industrial safety gloves&#10;PPE equipment India&#10;buy safety helmets online"></textarea>
                </div>
                <div class="form-group">
                    <label>{{ translate('Search Engine') }}</label>
                    <select id="kw-engine" class="form-control">
                        <option value="google">Google</option>
                        <option value="bing">Bing</option>
                    </select>
                </div>
                <button id="kw-check-btn" class="btn btn-primary w-100">
                    <i class="las la-search mr-1"></i>{{ translate('Check Rankings') }}
                </button>
                <div class="mt-3 p-3 bg-light rounded small text-muted">
                    <strong>{{ translate('Note:') }}</strong> {{ translate('For accurate ranking data, configure Google Custom Search API key in SEO Settings. Without it, results are estimated.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div id="kw-loading" class="card d-none">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-success mb-3"></div>
                <div>{{ translate('Checking keyword rankings...') }}</div>
            </div>
        </div>

        <div id="kw-results-card" class="card d-none">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Ranking Results') }}</h6>
                <span id="kw-summary" class="badge badge-soft-success"></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="kw-results-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Keyword') }}</th>
                                <th>{{ translate('Rank') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Ranking URL') }}</th>
                                <th>{{ translate('Checked') }}</th>
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

        {{-- Score History --}}
        @if(!empty($histories))
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('SEO Score History') }}</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ translate('URL') }}</th><th>{{ translate('Score') }}</th><th>{{ translate('Grade') }}</th><th>{{ translate('Date') }}</th></tr></thead>
                        <tbody>
                            @foreach($histories as $h)
                            <tr>
                                <td class="text-truncate" style="max-width:250px;">{{ $h['url'] ?? '' }}</td>
                                <td>
                                    <div class="progress" style="height:6px;width:80px;">
                                        <div class="progress-bar bg-{{ ($h['score'] ?? 0) >= 80 ? 'success' : (($h['score'] ?? 0) >= 50 ? 'warning' : 'danger') }}"
                                             style="width:{{ $h['score'] ?? 0 }}%"></div>
                                    </div>
                                    <small>{{ $h['score'] ?? 0 }}</small>
                                </td>
                                <td><span class="badge badge-soft-info">{{ $h['grade'] ?? '-' }}</span></td>
                                <td class="text-nowrap">{{ isset($h['recorded_at']) ? \Carbon\Carbon::parse($h['recorded_at'])->format('M d, Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
var statusBadges = {
    'top_3':   '<span class="badge badge-success">Top 3</span>',
    'page_1':  '<span class="badge badge-info">Page 1</span>',
    'page_2+': '<span class="badge badge-warning">Page 2+</span>',
    'not_found':'<span class="badge badge-secondary">Not Found</span>'
};

$(function() {
    $('#kw-check-btn').on('click', function() {
        var keywords = $('#kw-keywords').val().trim();
        if (!keywords) { alert('Please enter keywords.'); return; }

        $('#kw-loading').removeClass('d-none');
        $('#kw-results-card').addClass('d-none');
        $('#kw-ai-card').addClass('d-none');
        $('#kw-check-btn').prop('disabled', true);

        $.ajax({
            url: '{{ route('admin.seo-suite.keyword_tracker.check') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', keywords: keywords, engine: $('#kw-engine').val() },
            success: function(res) {
                $('#kw-loading').addClass('d-none');
                $('#kw-check-btn').prop('disabled', false);

                if (res.error) {
                    alert(res.error); return;
                }

                var found = (res.results || []).filter(function(r) { return r.rank !== null; }).length;
                $('#kw-summary').text(found + ' / ' + (res.keyword_count || 0) + ' ranked');

                var tbody = $('#kw-results-table tbody').empty();
                (res.results || []).forEach(function(r) {
                    tbody.append(
                        '<tr>' +
                        '<td><strong>' + r.keyword + '</strong></td>' +
                        '<td>' + (r.rank ? '<strong>#' + r.rank + '</strong>' : '<span class="text-muted">—</span>') + '</td>' +
                        '<td>' + (statusBadges[r.status] || r.status) + '</td>' +
                        '<td class="text-truncate" style="max-width:180px;">' + (r.url ? '<a href="' + r.url + '" target="_blank" class="small">' + r.url + '</a>' : '<span class="text-muted small">—</span>') + '</td>' +
                        '<td class="small text-muted">' + r.checked_at + '</td>' +
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
