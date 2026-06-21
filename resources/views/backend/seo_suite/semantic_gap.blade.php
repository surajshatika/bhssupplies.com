@extends('backend.layouts.app')
@section('content')
@include('backend.partials.modern_module_styles')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0"><i class="las la-brain mr-2 text-primary"></i>{{ translate('Semantic Entity Gap Analysis') }}</h4>
            <small class="text-muted">{{ translate('Extract NLP entities and Latent Semantic Indexing (LSI) keywords from top-ranking competitors.') }}</small>
        </div>
        <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
        </a>
    </div>

    {{-- How it works alert --}}
    <div class="alert alert-info alert-dismissible fade show py-2 small mb-4" role="alert">
        <i class="las la-info-circle mr-1"></i>
        <strong>{{ translate('How it works:') }}</strong>
        {{ translate('Select a target keyword and provide your page URL. The AI will analyze top competitors for that keyword and identify missing semantic entities your content needs to rank higher.') }}
        <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ translate('Run Analysis') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.seo-suite.semantic_gap.analyze') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-600">{{ translate('Target Keyword') }}</label>
                            <select name="keyword" class="form-control aiz-selectpicker" data-live-search="true" required>
                                <option value="">{{ translate('Select a keyword...') }}</option>
                                @foreach($targetKeywords as $kw)
                                    <option value="{{ $kw }}">{{ $kw }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">{{ translate('Select from your saved Target Keywords.') }}</small>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label font-weight-600">{{ translate('Your Page URL') }}</label>
                            <input type="url" name="url" class="form-control" placeholder="https://yourdomain.com/page-to-analyze" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="las la-search mr-1"></i> {{ translate('Run Semantic Analysis') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ translate('Missing Entities (Gap Results)') }}</h6>
                </div>
                <div class="card-body p-0">
                    @if(session('gap_results'))
                        <div class="p-3 bg-light border-bottom">
                            <span class="text-muted small">{{ translate('Analyzed Keyword:') }} <strong>{{ session('analyzed_keyword') }}</strong></span><br>
                            <span class="text-muted small">{{ translate('Analyzed URL:') }} <a href="{{ session('analyzed_url') }}" target="_blank">{{ session('analyzed_url') }}</a></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Entity / LSI Keyword') }}</th>
                                        <th>{{ translate('Relevance') }}</th>
                                        <th class="text-right">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(session('gap_results') as $result)
                                        <tr>
                                            <td class="font-weight-600">{{ $result['entity'] ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $relevance = $result['relevance'] ?? 'Medium';
                                                    $badgeClass = 'secondary';
                                                    if ($relevance === 'High') $badgeClass = 'danger';
                                                    if ($relevance === 'Medium') $badgeClass = 'warning';
                                                    if ($relevance === 'Low') $badgeClass = 'success';
                                                @endphp
                                                <span class="badge badge-soft-{{ $badgeClass }}">{{ $relevance }}</span>
                                            </td>
                                            <td class="text-right">
                                                <button type="button" class="btn btn-xs btn-outline-primary" title="{{ translate('Copy to clipboard') }}" onclick="navigator.clipboard.writeText('{{ $result['entity'] ?? '' }}'); showToast('{{ translate('Copied!') }}');">
                                                    <i class="las la-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 text-center bg-light">
                            <small class="text-muted">{{ translate('Tip: Ask the AI Assistant to weave these entities into your content.') }}</small>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="las la-clipboard-list la-3x text-muted mb-3 d-block"></i>
                            <span class="text-muted">{{ translate('Run an analysis to see semantic gaps here.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast notification --}}
<div class="toast-kw">
    <div id="kw-toast" class="toast" role="alert" data-delay="2800" style="position:fixed; bottom:20px; right:20px; z-index:9999; min-width:260px;">
        <div class="toast-header">
            <i class="las la-check-circle text-success mr-2" id="toast-icon"></i>
            <strong class="mr-auto" id="toast-title">Done</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast"><span>&times;</span></button>
        </div>
        <div class="toast-body" id="toast-msg"></div>
    </div>
</div>

<script>
function showToast(msg, success = true) {
    document.getElementById('toast-icon').className = success
        ? 'las la-check-circle text-success mr-2'
        : 'las la-exclamation-circle text-danger mr-2';
    document.getElementById('toast-title').textContent = success ? '{{ translate("Success") }}' : '{{ translate("Error") }}';
    document.getElementById('toast-msg').textContent   = msg;
    $('#kw-toast').toast('show');
}
</script>
@endsection
