@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0"><i class="las la-brain mr-2 text-purple"></i>{{ translate('Autonomous Research Agent') }}</h4>
        <small class="text-muted">{{ translate('A reasoning loop that reads competitor pages one at a time and decides what to read next.') }}</small>
    </div>
    <a href="{{ route('admin.seo-suite.semantic_gap') }}" class="btn btn-sm btn-outline-secondary">
        <i class="las la-search-plus mr-1"></i>{{ translate('Semantic Gap') }}
    </a>
</div>

@include('backend.seo.partials.suite_nav')

@if(session('error'))
    <div class="alert alert-danger py-2 small"><i class="las la-exclamation-circle mr-1"></i>{{ session('error') }}</div>
@endif

<div class="alert alert-warning py-2 small mb-4">
    <i class="las la-shield-alt mr-1"></i>
    <strong>{{ translate('The agent can only read the URLs you list below.') }}</strong>
    {{ translate('It cannot follow links it finds inside those pages. That boundary is deliberate: an agent that fetches model-chosen URLs can be steered by text injected into a fetched page into requesting internal addresses and leaking the response. Private, local, and metadata addresses are rejected before any request is made.') }}
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="mb-0"><i class="las la-play-circle text-primary mr-1"></i>{{ translate('Run Research') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.research_agent.run') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-600">{{ translate('Research Question') }}</label>
                        <textarea name="question" rows="3" class="form-control" required
                                  placeholder="{{ translate('e.g. How do top competitors structure their safety-equipment category pages?') }}">{{ old('question') }}</textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label font-weight-600">{{ translate('Permitted Source URLs') }}</label>
                        <textarea name="urls" rows="7" class="form-control" required
                                  placeholder="https://competitor-a.com/page&#10;https://competitor-b.com/page">{{ old('urls') }}</textarea>
                        <small class="text-muted">{{ translate('One per line. The agent reads at most') }} {{ $maxFetches }} {{ translate('of them.') }}</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="las la-brain mr-1"></i>{{ translate('Start Research') }}
                    </button>
                    <small class="text-muted d-block mt-2 text-center">
                        {{ translate('Up to') }} {{ $maxTurns }} {{ translate('reasoning turns. This can take a minute.') }}
                    </small>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if(session('agent_result'))
            @php $agent = session('agent_result'); @endphp

            @if(!empty($agent['rejected_seeds']))
                <div class="alert alert-warning py-2 small">
                    <strong>{{ translate('Some URLs were rejected before the run:') }}</strong>
                    <ul class="mb-0 mt-1 pl-3">
                        @foreach($agent['rejected_seeds'] as $rejected)
                            <li>{{ $rejected['url'] }} — {{ $rejected['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="las la-file-alt text-success mr-1"></i>{{ translate('Research Report') }}</h6>
                    <span class="badge badge-soft-secondary">{{ $agent['provider'] }}</span>
                </div>
                <div class="card-body">
                    <div style="white-space:pre-wrap;line-height:1.6;">{{ $agent['report'] }}</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-2"><h6 class="mb-0 small"><i class="las la-check-circle text-success mr-1"></i>{{ translate('Sources Read') }} ({{ count($agent['sources_read']) }})</h6></div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush small">
                                @forelse($agent['sources_read'] as $source)
                                    <li class="list-group-item py-2" style="word-break:break-all;">
                                        <a href="{{ $source }}" target="_blank" rel="noopener">{{ $source }}</a>
                                    </li>
                                @empty
                                    <li class="list-group-item py-2 text-muted">{{ translate('None') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-2"><h6 class="mb-0 small"><i class="las la-times-circle text-danger mr-1"></i>{{ translate('Not Read') }} ({{ count($agent['sources_failed']) }})</h6></div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush small">
                                @forelse($agent['sources_failed'] as $source)
                                    <li class="list-group-item py-2 text-muted" style="word-break:break-all;">{{ $source }}</li>
                                @empty
                                    <li class="list-group-item py-2 text-muted">{{ translate('All sources were read.') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The visible reasoning trace: what it decided, and why, each turn. --}}
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="las la-stream text-info mr-1"></i>{{ translate('Reasoning Trace') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 small">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:60px;">{{ translate('Turn') }}</th>
                                    <th style="width:110px;">{{ translate('Type') }}</th>
                                    <th>{{ translate('Detail') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agent['trace'] as $step)
                                    @php
                                        $typeColor = [
                                            'thought' => 'primary', 'observation' => 'success',
                                            'guard' => 'warning', 'error' => 'danger', 'limit' => 'secondary',
                                        ][$step['type']] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $step['turn'] }}</td>
                                        <td><span class="badge badge-soft-{{ $typeColor }}">{{ $step['type'] }}</span></td>
                                        <td>
                                            {{ $step['detail'] }}
                                            @if(!empty($step['action']))
                                                <span class="badge badge-soft-secondary ml-1">→ {{ $step['action'] }}</span>
                                            @endif
                                            @if(!empty($step['url']))
                                                <div class="text-muted" style="word-break:break-all;">{{ $step['url'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="las la-brain" style="font-size:3rem;opacity:.3;"></i>
                    <p class="mt-3 mb-0">{{ translate('Ask a question and list the pages the agent may read.') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
