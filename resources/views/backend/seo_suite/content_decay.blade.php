@extends('backend.layouts.app')
@section('content')
@include('backend.partials.modern_module_styles')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0"><i class="las la-project-diagram mr-2 text-primary"></i>{{ translate('Content Decay & Cannibalization Detection') }}</h4>
            <small class="text-muted">{{ translate('Automatically detect pages losing Google traffic over the last 56 days and instances of keyword cannibalization.') }}</small>
        </div>
        <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
        </a>
    </div>

    {{-- How it works alert --}}
    <div class="alert alert-info alert-dismissible fade show py-2 small mb-4" role="alert">
        <i class="las la-info-circle mr-1"></i>
        <strong>{{ translate('How it works:') }}</strong>
        {{ translate('The AI engine analyzes historical Google Search Console data. "Content Decay" finds URLs whose clicks dropped more than 15% comparing the last 28 days to the 28 days prior. "Cannibalization" finds queries where multiple pages on your site are competing for the exact same clicks.') }}
        <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
    </div>

    <div class="row">
        <!-- Content Decay -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="las la-arrow-down text-danger mr-1"></i> {{ translate('Decaying Content') }}</h6>
                    <span class="badge badge-soft-danger">{{ count($decayedPages) }} {{ translate('Issues') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Page URL') }}</th>
                                    <th>{{ translate('Past Clicks') }}</th>
                                    <th>{{ translate('Recent Clicks') }}</th>
                                    <th>{{ translate('Drop') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($decayedPages as $decay)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 200px;" title="{{ $decay['url'] }}">
                                            <a href="{{ $decay['url'] }}" target="_blank">{{ str_replace(url('/'), '', $decay['url']) ?: '/' }}</a>
                                        </td>
                                        <td>{{ number_format($decay['past_clicks']) }}</td>
                                        <td>{{ number_format($decay['recent_clicks']) }}</td>
                                        <td class="text-danger font-weight-bold">-{{ $decay['drop_percentage'] }}%</td>
                                        <td>
                                            <a href="{{ route('admin.seo_optimization.index') }}" class="btn btn-xs btn-soft-primary" title="{{ translate('Queue for AI Rewrite') }}">
                                                <i class="las la-robot"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="las la-check-circle la-3x text-success mb-3 d-block"></i>
                                            {{ translate('No decaying content detected.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cannibalization -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="las la-skull-crossbones text-warning mr-1"></i> {{ translate('Cannibalized Queries') }}</h6>
                    <span class="badge badge-soft-warning">{{ count($cannibalizedQueries) }} {{ translate('Issues') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Query') }}</th>
                                    <th>{{ translate('Competing Pages') }}</th>
                                    <th>{{ translate('Total Clicks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cannibalizedQueries as $cannibal)
                                    <tr>
                                        <td class="font-weight-600">{{ $cannibal['query'] }}</td>
                                        <td>
                                            @foreach($cannibal['pages'] as $url => $clicks)
                                                <div class="small mb-1">
                                                    <a href="{{ $url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 150px; vertical-align: bottom;" title="{{ $url }}">{{ str_replace(url('/'), '', $url) ?: '/' }}</a>
                                                    <span class="badge badge-secondary ml-1">{{ $clicks }} {{ translate('clicks') }}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>{{ number_format($cannibal['total_clicks']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="las la-check-circle la-3x text-success mb-3 d-block"></i>
                                            {{ translate('No cannibalization issues detected.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
