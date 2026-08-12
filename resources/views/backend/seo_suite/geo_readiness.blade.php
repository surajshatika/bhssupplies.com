@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0"><i class="las la-robot mr-2 text-primary"></i>{{ translate('AI Search (GEO) Readiness') }}</h4>
        <small class="text-muted">{{ translate('Score how citable a page is by ChatGPT Search, Perplexity, and Google AI Overviews.') }}</small>
    </div>
    <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
    </a>
</div>

@include('backend.seo.partials.suite_nav')

@if(session('error'))
    <div class="alert alert-danger py-2 small"><i class="las la-exclamation-circle mr-1"></i>{{ session('error') }}</div>
@endif

<div class="alert alert-info py-2 small mb-4">
    <i class="las la-info-circle mr-1"></i>
    <strong>{{ translate('No AI is used for this score.') }}</strong>
    {{ translate('All 8 factors are measured directly from the page HTML, so the result is deterministic, free, and reproducible — the same page always scores the same.') }}
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><h6 class="mb-0"><i class="las la-search text-primary mr-1"></i>{{ translate('Analyze a Page') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.geo_readiness.run') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-600">{{ translate('Page URL') }}</label>
                        <input type="url" name="url" class="form-control" required
                               value="{{ old('url', session('geo_report.url')) }}"
                               placeholder="https://yourdomain.com/page">
                        <small class="text-muted">{{ translate('Must be publicly reachable and return HTML.') }}</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="las la-bolt mr-1"></i>{{ translate('Check GEO Readiness') }}
                    </button>
                </form>

                <hr>
                <h6 class="small font-weight-600 text-muted text-uppercase mb-2">{{ translate('Scored Factors') }}</h6>
                <ul class="list-unstyled small mb-0">
                    @foreach($factors as $key => $factor)
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span>{{ translate($factor['label']) }}</span>
                            <span class="text-muted">{{ $factor['weight'] }} {{ translate('pts') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if(session('geo_report'))
            @php $report = session('geo_report'); @endphp

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center border-right">
                            @php
                                $score = $report['score'];
                                $color = $score >= 85 ? '#1cc88a' : ($score >= 70 ? '#4e9de6' : ($score >= 50 ? '#f6c23e' : '#e74a3b'));
                            @endphp
                            <div style="font-size:3.2rem;font-weight:700;line-height:1;color:{{ $color }};">{{ $score }}</div>
                            <div class="text-muted small">{{ translate('out of 100') }}</div>
                            <span class="badge mt-2" style="background:{{ $color }};color:#fff;">{{ translate($report['grade']) }}</span>
                        </div>
                        <div class="col-md-8">
                            <div class="small text-muted mb-1">{{ translate('Analyzed URL') }}</div>
                            <a href="{{ $report['url'] }}" target="_blank" rel="noopener" class="font-weight-600 d-block mb-2" style="word-break:break-all;">{{ $report['url'] }}</a>
                            <div class="small text-muted">{{ translate('Server-rendered word count') }}: <strong>{{ number_format($report['word_count']) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($report['priorities']))
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="las la-tools text-warning mr-1"></i>{{ translate('Fix These First') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 small">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Factor') }}</th>
                                    <th>{{ translate('Recommended Fix') }}</th>
                                    <th class="text-right">{{ translate('Points Available') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['priorities'] as $priority)
                                    <tr>
                                        <td class="font-weight-600">{{ translate($priority['label']) }}</td>
                                        <td>{{ $priority['fix'] }}</td>
                                        <td class="text-right"><span class="badge badge-soft-warning">+{{ $priority['points_lost'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="las la-list-ol text-primary mr-1"></i>{{ translate('Full Factor Breakdown') }}</h6></div>
                <div class="card-body">
                    @foreach($report['factors'] as $key => $factor)
                        @php
                            $pct = $factor['percent'];
                            $bar = $pct >= 85 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                        @endphp
                        <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-weight-600">{{ translate($factors[$key]['label']) }}</span>
                                <span class="small text-muted">{{ $pct }}% {{ translate('of') }} {{ $factors[$key]['weight'] }} {{ translate('pts') }}</span>
                            </div>
                            <div class="progress mb-2" style="height:6px;">
                                <div class="progress-bar bg-{{ $bar }}" style="width:{{ $pct }}%"></div>
                            </div>
                            <div class="small text-muted">{{ $factor['detail'] }}</div>
                            @if($factor['fix'])
                                <div class="small mt-1"><i class="las la-arrow-right text-primary mr-1"></i>{{ $factor['fix'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="las la-robot" style="font-size:3rem;opacity:.3;"></i>
                    <p class="mt-3 mb-0">{{ translate('Enter a URL to score it for AI-search citability.') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
