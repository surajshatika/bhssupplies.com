@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-link mr-2 text-info"></i>{{ translate('Link Assistant') }}</h1>
            <p class="text-muted mb-0">{{ translate('Find internal linking opportunities and external link building suggestions.') }}</p>
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
            <div class="card-header"><h6 class="mb-0">{{ translate('Find Link Opportunities') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.link_assistant') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>{{ translate('Page URL') }}</label>
                        <input type="text" class="form-control" name="url" value="{{ old('url') }}" placeholder="{{ url('/') }}/product/example">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Focus Keyword') }}</label>
                        <input type="text" class="form-control" name="keyword" value="{{ old('keyword') }}" placeholder="safety gloves">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Page Content') }} <small class="text-muted">({{ translate('optional, for better suggestions') }})</small></label>
                        <textarea class="form-control" name="content" rows="5" placeholder="{{ translate('Paste page content or description...') }}">{{ old('content') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Link Type') }}</label>
                        <select class="form-control" name="link_type">
                            <option value="both">{{ translate('Both Internal & External') }}</option>
                            <option value="internal">{{ translate('Internal Only') }}</option>
                            <option value="external">{{ translate('External Only') }}</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100"><i class="las la-search mr-1"></i>{{ translate('Find Opportunities') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($result)
            <div class="row gutters-16 mb-3">
                <div class="col-4">
                    <div class="card text-center"><div class="card-body py-3">
                        <div class="h4 mb-1 text-primary">{{ $result['total_found'] ?? 0 }}</div>
                        <div class="text-muted small">{{ translate('Total Found') }}</div>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card text-center"><div class="card-body py-3">
                        <div class="h4 mb-1 text-success">{{ count($result['opportunities']['internal'] ?? []) }}</div>
                        <div class="text-muted small">{{ translate('Internal') }}</div>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card text-center"><div class="card-body py-3">
                        <div class="h4 mb-1 text-info">{{ count($result['opportunities']['external'] ?? []) }}</div>
                        <div class="text-muted small">{{ translate('External') }}</div>
                    </div></div>
                </div>
            </div>

            @if(!empty($result['opportunities']['internal']))
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="las la-sitemap mr-1 text-success"></i>{{ translate('Internal Linking Opportunities') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>{{ translate('Page') }}</th><th>{{ translate('Anchor Text') }}</th><th>{{ translate('Type') }}</th><th>{{ translate('Action') }}</th></tr></thead>
                            <tbody>
                                @foreach($result['opportunities']['internal'] as $link)
                                <tr>
                                    <td class="text-truncate" style="max-width:200px;">
                                        <a href="{{ $link['url'] }}" target="_blank" class="small">{{ $link['url'] }}</a>
                                    </td>
                                    <td><code class="small">{{ $link['anchor_text'] }}</code></td>
                                    <td><span class="badge badge-soft-success">{{ $link['type'] }}</span></td>
                                    <td>
                                        <button class="btn btn-xs btn-soft-primary copy-link-btn" data-url="{{ $link['url'] }}" data-anchor="{{ $link['anchor_text'] }}">
                                            {{ translate('Copy Link') }}
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(!empty($result['opportunities']['external']))
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="las la-external-link-alt mr-1 text-info"></i>{{ translate('External Authority Links') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>{{ translate('Domain') }}</th><th>{{ translate('DA') }}</th><th>{{ translate('Type') }}</th></tr></thead>
                            <tbody>
                                @foreach($result['opportunities']['external'] as $link)
                                <tr>
                                    <td><a href="{{ $link['url'] }}" target="_blank">{{ $link['url'] }}</a></td>
                                    <td><span class="badge badge-soft-primary">{{ $link['domain_authority'] ?? '—' }}</span></td>
                                    <td><span class="badge badge-soft-info">{{ $link['type'] ?? '—' }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(!empty($result['ai_suggestions']))
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="las la-robot mr-1 text-primary"></i>{{ translate('AI Link Building Strategy') }}</h6></div>
                <div class="card-body">
                    <div style="white-space:pre-wrap; font-size:0.875rem; line-height:1.6;">{{ $result['ai_suggestions'] }}</div>
                </div>
            </div>
            @endif
        @else
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="las la-link la-3x d-block mb-3 text-light"></i>
                    <p>{{ translate('Enter a URL and keyword to discover link opportunities.') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    $(document).on('click', '.copy-link-btn', function() {
        var url = $(this).data('url');
        var anchor = $(this).data('anchor');
        var html = '<a href="' + url + '">' + anchor + '</a>';
        navigator.clipboard.writeText(html).then(function() {
            alert('Link HTML copied to clipboard!');
        });
    });
});
</script>
@endsection
