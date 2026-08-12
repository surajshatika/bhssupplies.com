@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0"><i class="las la-project-diagram mr-2 text-info"></i>{{ translate('Semantic Keyword Clustering') }}</h4>
        <small class="text-muted">{{ translate('Group keywords by meaning using vector embeddings — one cluster, one page.') }}</small>
    </div>
    <a href="{{ route('admin.seo-suite.keyword_manager') }}" class="btn btn-sm btn-outline-secondary">
        <i class="las la-key mr-1"></i>{{ translate('Keyword Manager') }}
    </a>
</div>

@include('backend.seo.partials.suite_nav')

@if(session('error'))
    <div class="alert alert-danger py-2 small"><i class="las la-exclamation-circle mr-1"></i>{{ session('error') }}</div>
@endif

<div class="alert alert-info py-2 small mb-4">
    <i class="las la-info-circle mr-1"></i>
    <strong>{{ translate('The AI only produces vectors — it never picks the groups.') }}</strong>
    {{ translate('Clustering is done with deterministic cosine-similarity union-find, so the same keywords and threshold always yield the same clusters. Asking a chat model to "group these keywords" would give a different answer every run and silently drop inputs.') }}
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="mb-0"><i class="las la-sliders-h text-info mr-1"></i>{{ translate('Cluster Settings') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.keyword_clusters.run') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-600">{{ translate('Keywords') }}</label>
                        <textarea name="keywords" rows="10" class="form-control" required
                                  placeholder="{{ translate('One keyword per line, or comma separated') }}">{{ old('keywords', implode("\n", $targetKeywords)) }}</textarea>
                        <small class="text-muted">{{ translate('Up to 300 keywords per run.') }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label font-weight-600">{{ translate('Embedding Provider') }}</label>
                        <select name="provider" class="form-control">
                            @foreach($providers as $key => $config)
                                <option value="{{ $key }}" {{ old('provider') === $key ? 'selected' : '' }}>
                                    {{ $config['label'] }} — {{ $config['model'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ translate('Only these providers expose a real embeddings API.') }}</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label font-weight-600">
                            {{ translate('Similarity Threshold') }}
                            <span class="text-muted small" id="threshold-value">{{ old('threshold', '0.82') }}</span>
                        </label>
                        <input type="range" name="threshold" class="form-control-range" min="0.5" max="0.99" step="0.01"
                               value="{{ old('threshold', '0.82') }}"
                               oninput="document.getElementById('threshold-value').textContent = this.value;">
                        <small class="text-muted">{{ translate('Higher = tighter, more specific clusters. Lower = broader groups.') }}</small>
                    </div>

                    <button type="submit" class="btn btn-info btn-block text-white">
                        <i class="las la-project-diagram mr-1"></i>{{ translate('Cluster Keywords') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if(session('cluster_result'))
            @php $result = session('cluster_result'); @endphp

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3 border-right">
                            <div class="h4 mb-0 text-info">{{ $result['cluster_count'] }}</div>
                            <small class="text-muted">{{ translate('Clusters') }}</small>
                        </div>
                        <div class="col-3 border-right">
                            <div class="h4 mb-0">{{ $result['keyword_count'] }}</div>
                            <small class="text-muted">{{ translate('Keywords') }}</small>
                        </div>
                        <div class="col-3 border-right">
                            <div class="h4 mb-0">{{ $result['dimensions'] }}</div>
                            <small class="text-muted">{{ translate('Vector dims') }}</small>
                        </div>
                        <div class="col-3">
                            <div class="h4 mb-0">{{ $result['threshold'] }}</div>
                            <small class="text-muted">{{ translate('Threshold') }}</small>
                        </div>
                    </div>
                    <hr>
                    <div class="small text-muted mb-0">
                        {{ translate('Embeddings from') }} <strong>{{ $result['provider'] }}</strong> ({{ $result['model'] }}).
                        @if($result['truncated'])
                            <span class="text-warning">{{ translate('Input was truncated to the first 300 keywords.') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @foreach($result['clusters'] as $i => $cluster)
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <div>
                            <span class="badge badge-soft-info mr-2">#{{ $i + 1 }}</span>
                            <strong>{{ $cluster['head'] }}</strong>
                            @if($cluster['is_single'])
                                <span class="badge badge-soft-secondary ml-1">{{ translate('Unclustered') }}</span>
                            @endif
                        </div>
                        <div class="small text-muted">
                            {{ $cluster['size'] }} {{ translate('keyword(s)') }}
                            @if(!$cluster['is_single'])
                                · {{ translate('cohesion') }} {{ number_format($cluster['cohesion'], 3) }}
                            @endif
                        </div>
                    </div>
                    <div class="card-body py-2">
                        @foreach($cluster['keywords'] as $keyword)
                            <span class="badge badge-soft-{{ $keyword === $cluster['head'] ? 'primary' : 'secondary' }} mr-1 mb-1" style="font-size:.78rem;">
                                {{ $keyword }}
                            </span>
                        @endforeach
                        @if(!$cluster['is_single'])
                            <div class="small text-muted mt-2 pt-2 border-top">
                                <i class="las la-lightbulb text-warning mr-1"></i>
                                {{ translate('Build one page targeting') }} "<strong>{{ $cluster['head'] }}</strong>"
                                {{ translate('and cover the rest as sections — splitting these across pages causes cannibalisation.') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="las la-project-diagram" style="font-size:3rem;opacity:.3;"></i>
                    <p class="mt-3 mb-0">{{ translate('Paste your keyword list to group it into page-level topics.') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
