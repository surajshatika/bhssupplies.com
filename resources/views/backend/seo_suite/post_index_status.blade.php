@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-search mr-2 text-warning"></i>{{ translate('Post Index Status') }}</h1>
            <p class="text-muted mb-0">{{ translate('Check whether your pages are indexed by Google.') }}</p>
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
            <div class="card-header"><h6 class="mb-0">{{ translate('Check URLs') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('URLs to Check') }} <small class="text-muted">({{ translate('one per line, max 50') }})</small></label>
                    <textarea id="idx-urls" class="form-control" rows="10"
                        placeholder="{{ url('/') }}/product/example&#10;{{ url('/') }}/category/example&#10;{{ url('/') }}/about-us"></textarea>
                    <small class="text-muted">{{ translate('Leave empty to auto-check recent product pages') }}</small>
                </div>
                <button id="idx-check-btn" class="btn btn-primary w-100">
                    <i class="las la-search mr-1"></i>{{ translate('Check Index Status') }}
                </button>
                <div class="mt-3 p-2 bg-light rounded small text-muted">
                    <strong>{{ translate('Methods Used:') }}</strong><br>
                    <i class="las la-check text-success mr-1"></i>{{ translate('Google Custom Search API (if configured)') }}<br>
                    <i class="las la-check text-warning mr-1"></i>{{ translate('Google Cache check (fallback)') }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div id="idx-loading" class="card d-none">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-warning mb-3"></div>
                <div>{{ translate('Checking index status... This may take a moment.') }}</div>
            </div>
        </div>

        <div id="idx-results-card" class="card d-none">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Index Status Results') }}</h6>
                <div>
                    <span id="idx-indexed-count" class="badge badge-success mr-1"></span>
                    <span id="idx-not-indexed-count" class="badge badge-danger"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Method') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="idx-results-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="idx-ai-card" class="card mt-3 d-none">
            <div class="card-header"><h6 class="mb-0"><i class="las la-robot mr-1 text-primary"></i>{{ translate('AI Recommendations') }}</h6></div>
            <div class="card-body">
                <div id="idx-ai-content" style="white-space:pre-wrap; font-size:0.875rem; line-height:1.6;"></div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Why Pages Don\'t Get Indexed?') }}</h6></div>
            <div class="card-body">
                <div class="row small text-muted">
                    <div class="col-md-6">
                        <ul class="pl-3">
                            <li>{{ translate('Robots.txt blocking crawlers') }}</li>
                            <li>{{ translate('noindex meta tag present') }}</li>
                            <li>{{ translate('Duplicate content issues') }}</li>
                            <li>{{ translate('Thin or low-quality content') }}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="pl-3">
                            <li>{{ translate('No internal links pointing to page') }}</li>
                            <li>{{ translate('Server errors (5xx)') }}</li>
                            <li>{{ translate('Page not in sitemap') }}</li>
                            <li>{{ translate('Crawl budget exhausted') }}</li>
                        </ul>
                    </div>
                </div>
                <a href="{{ route('admin.seo-suite.sitemap') }}" class="btn btn-sm btn-soft-primary mr-2">
                    {{ translate('Regenerate Sitemap') }}
                </a>
                <a href="{{ route('admin.seo-suite.indexnow') }}" class="btn btn-sm btn-soft-success">
                    {{ translate('Submit via IndexNow') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    $('#idx-check-btn').on('click', function() {
        $('#idx-loading').removeClass('d-none');
        $('#idx-results-card, #idx-ai-card').addClass('d-none');
        $('#idx-check-btn').prop('disabled', true);

        $.ajax({
            url: '{{ route('admin.seo-suite.index_status.check') }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', urls: $('#idx-urls').val() },
            success: function(res) {
                $('#idx-loading').addClass('d-none');
                $('#idx-check-btn').prop('disabled', false);

                $('#idx-indexed-count').text(res.indexed + ' Indexed');
                $('#idx-not-indexed-count').text(res.not_indexed + ' Not Indexed');

                var tbody = $('#idx-results-tbody').empty();
                (res.results || []).forEach(function(r) {
                    var badge = r.indexed
                        ? '<span class="badge badge-success"><i class="las la-check mr-1"></i>Indexed</span>'
                        : '<span class="badge badge-danger"><i class="las la-times mr-1"></i>Not Indexed</span>';
                    tbody.append(
                        '<tr>' +
                        '<td class="text-truncate" style="max-width:260px;"><a href="' + r.url + '" target="_blank" class="small">' + r.url + '</a></td>' +
                        '<td>' + badge + '</td>' +
                        '<td><small class="text-muted">' + r.method + '</small></td>' +
                        '<td>' + (!r.indexed ? '<a href="https://search.google.com/search-console/inspect?resource_id=' + encodeURIComponent(r.url) + '" target="_blank" class="btn btn-xs btn-soft-warning">Inspect</a>' : '') + '</td>' +
                        '</tr>'
                    );
                });
                $('#idx-results-card').removeClass('d-none');

                if (res.ai_advice) {
                    $('#idx-ai-content').text(res.ai_advice);
                    $('#idx-ai-card').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#idx-loading').addClass('d-none');
                $('#idx-check-btn').prop('disabled', false);
                alert('Error: ' + (xhr.responseJSON?.message || 'Request failed'));
            }
        });
    });
});
</script>
@endsection
