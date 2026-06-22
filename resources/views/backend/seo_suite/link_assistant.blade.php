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

@include('backend.seo.partials.suite_nav')

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
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

        <div class="card border-left-success shadow-sm">
            <div class="card-header border-bottom-0 bg-soft-success">
                <h6 class="mb-0 text-success"><i class="las la-magic mr-1"></i> {{ translate('Auto-Linker Engine') }}</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted">{{ translate('Automatically scans your products and blogs to inject internal links for your established SEO focus keywords.') }}</p>
                <form action="{{ route('admin.seo-suite.run_auto_linker') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 btn-sm">
                        <i class="las la-play mr-1"></i> {{ translate('Run Auto-Linker Now') }}
                    </button>
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
                <div class="card-header"><h6 class="mb-0"><i class="las la-external-link-alt mr-1 text-info"></i>{{ translate('External Outreach Prospects') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr>
                                <th>{{ translate('Prospect URL') }}</th>
                                <th>{{ translate('Est. DA') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th class="text-right">{{ translate('Action') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach($result['opportunities']['external'] as $link)
                                <tr>
                                    <td class="text-truncate" style="max-width:250px;">
                                        <a href="{{ $link['url'] }}" target="_blank" title="{{ $link['title'] ?? '' }}">{{ $link['url'] }}</a>
                                    </td>
                                    <td><span class="badge badge-soft-primary">{{ $link['domain_authority'] ?? '—' }}</span></td>
                                    <td><span class="badge badge-soft-info">{{ $link['type'] ?? '—' }}</span></td>
                                    <td class="text-right">
                                        <button class="btn btn-xs btn-primary draft-email-btn" data-url="{{ $link['url'] }}">
                                            <i class="las la-envelope"></i> {{ translate('Draft Email') }}
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
</div>

{{-- Email Draft Modal --}}
<div class="modal fade" id="email-draft-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('AI Generated Outreach Email') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="email-draft-loading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-3"></div>
                    <p>{{ translate('Analyzing prospect and drafting personalized email...') }}</p>
                </div>
                <div id="email-draft-content" class="d-none">
                    <div class="form-group">
                        <label>{{ translate('Email Template') }}</label>
                        <textarea id="email-draft-text" class="form-control" rows="12"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                <button type="button" class="btn btn-primary" id="copy-email-btn">
                    <i class="las la-copy mr-1"></i>{{ translate('Copy to Clipboard') }}
                </button>
            </div>
        </div>
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

    $(document).on('click', '.draft-email-btn', function() {
        var prospectUrl = $(this).data('url');
        var siteUrl = $('input[name="url"]').val();
        var targetKeyword = $('input[name="keyword"]').val();

        if (!siteUrl || !targetKeyword) {
            alert('Please ensure you have filled out your Page URL and Focus Keyword before generating an email.');
            return;
        }

        $('#email-draft-modal').modal('show');
        $('#email-draft-loading').removeClass('d-none');
        $('#email-draft-content').addClass('d-none');
        $('#email-draft-text').val('');

        $.ajax({
            url: '{{ route('admin.seo-suite.link_assistant.draft') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                prospect_url: prospectUrl,
                site_url: siteUrl,
                target_keyword: targetKeyword
            },
            success: function(res) {
                $('#email-draft-loading').addClass('d-none');
                if (res.success) {
                    $('#email-draft-text').val(res.draft);
                    $('#email-draft-content').removeClass('d-none');
                } else {
                    $('#email-draft-text').val('Error generating email: ' + res.error);
                    $('#email-draft-content').removeClass('d-none');
                }
            },
            error: function() {
                $('#email-draft-loading').addClass('d-none');
                $('#email-draft-text').val('A network error occurred. Please try again.');
                $('#email-draft-content').removeClass('d-none');
            }
        });
    });

    $('#copy-email-btn').on('click', function() {
        var text = $('#email-draft-text').val();
        navigator.clipboard.writeText(text).then(function() {
            $('#copy-email-btn').text('Copied!');
            setTimeout(function() {
                $('#copy-email-btn').html('<i class="las la-copy mr-1"></i>Copy to Clipboard');
            }, 2000);
        });
    });
});
</script>
@endsection
