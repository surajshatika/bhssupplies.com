@extends('backend.layouts.app')
@section('content')
@include('backend.partials.modern_module_styles')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0"><i class="las la-network-wired mr-2 text-primary"></i>{{ translate('Internal Link Graph & PageRank Sculpting') }}</h4>
            <small class="text-muted">{{ translate('Visualize how internal link equity flows through your site and identify isolated pages.') }}</small>
        </div>
        <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
        </a>
    </div>

    {{-- How it works alert --}}
    <div class="alert alert-info alert-dismissible fade show py-2 small mb-4" role="alert">
        <i class="las la-info-circle mr-1"></i>
        <strong>{{ translate('How it works:') }}</strong>
        {{ translate('The AI engine maps all internal links on your site to calculate PageRank distribution. "Powerful Pages" have the most internal backlinks, while "Orphaned Pages" have zero internal links pointing to them. Use Powerful Pages to pass equity to your important Orphaned Pages.') }}
        <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
    </div>

    <div class="row">
        <!-- Orphaned Pages -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100 border-left-danger">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="las la-unlink text-danger mr-1"></i> {{ translate('Orphaned Pages (0 Inlinks)') }}</h6>
                    <span class="badge badge-soft-danger">{{ count($orphanedPages) }} {{ translate('Pages') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th>{{ translate('Entity') }}</th>
                                    <th>{{ translate('Outlinks') }}</th>
                                    <th class="text-right">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orphanedPages as $page)
                                    <tr>
                                        <td>
                                            <div class="font-weight-600 text-truncate" style="max-width: 250px;" title="{{ $page['title'] }}">{{ $page['title'] }}</div>
                                            <a href="{{ $page['url'] }}" target="_blank" class="small text-muted text-truncate d-inline-block" style="max-width: 250px;" title="{{ $page['url'] }}">{{ str_replace(url('/'), '', $page['url']) ?: '/' }}</a>
                                        </td>
                                        <td><span class="badge badge-secondary">{{ $page['outlinks'] }}</span></td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.seo-suite.link_assistant', ['target_url' => $page['url']]) }}" class="btn btn-xs btn-primary" title="{{ translate('Find Linking Opportunities') }}">
                                                <i class="las la-search-plus"></i> {{ translate('Fix Orphan') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="las la-check-circle la-3x text-success mb-3 d-block"></i>
                                            {{ translate('No orphaned pages detected! Great internal linking structure.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Powerful Pages -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100 border-left-success">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="las la-bolt text-success mr-1"></i> {{ translate('Powerful Hub Pages') }}</h6>
                    <span class="badge badge-soft-success">{{ translate('Top') }} {{ count($powerfulPages) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th>{{ translate('Entity') }}</th>
                                    <th>{{ translate('Inlinks') }}</th>
                                    <th>{{ translate('PR Score') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($powerfulPages as $page)
                                    <tr>
                                        <td>
                                            <div class="font-weight-600 text-truncate" style="max-width: 250px;" title="{{ $page['title'] }}">{{ $page['title'] }}</div>
                                            <a href="{{ $page['url'] }}" target="_blank" class="small text-muted text-truncate d-inline-block" style="max-width: 250px;" title="{{ $page['url'] }}">{{ str_replace(url('/'), '', $page['url']) ?: '/' }}</a>
                                        </td>
                                        <td><span class="badge badge-success">{{ $page['inlinks'] }}</span></td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 60px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $page['pr_score'] }}%;" aria-valuenow="{{ $page['pr_score'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small text-muted">{{ $page['pr_score'] }}/100</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="las la-info-circle la-3x mb-3 d-block"></i>
                                            {{ translate('Not enough data to calculate powerful pages.') }}
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
