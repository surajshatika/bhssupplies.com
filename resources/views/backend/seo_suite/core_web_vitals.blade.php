@extends('backend.layouts.app')
@section('content')
@include('backend.partials.modern_module_styles')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0"><i class="las la-tachometer-alt mr-2 text-primary"></i>{{ translate('Core Web Vitals & PageSpeed Insights') }}</h4>
            <small class="text-muted">{{ translate('Monitor your site\'s technical performance and user experience metrics.') }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
            </a>
            <!-- Note: Running the audit command is done via CLI or a scheduled task. This just displays the recorded scores -->
        </div>
    </div>

    {{-- How it works alert --}}
    <div class="alert alert-info alert-dismissible fade show py-2 small mb-4" role="alert">
        <i class="las la-info-circle mr-1"></i>
        <strong>{{ translate('How it works:') }}</strong>
        {{ translate('The system periodically runs Google Lighthouse audits on your core pages. Pages with a Performance score below 90 should be optimized to avoid SEO ranking penalties. Core Web Vitals are a direct ranking factor for Google Search.') }}
        <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="las la-list text-primary mr-1"></i> {{ translate('Recent Audits') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('URL') }}</th>
                            <th>{{ translate('Strategy') }}</th>
                            <th>{{ translate('Performance') }}</th>
                            <th>{{ translate('SEO') }}</th>
                            <th>{{ translate('Accessibility') }}</th>
                            <th>{{ translate('Best Practices') }}</th>
                            <th>{{ translate('Date Checked') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vitals as $vital)
                            <tr>
                                <td>
                                    <a href="{{ $vital['url'] }}" target="_blank" class="font-weight-600 text-truncate d-inline-block" style="max-width: 250px;" title="{{ $vital['url'] }}">
                                        {{ str_replace(url('/'), '', $vital['url']) ?: '/' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-soft-{{ $vital['strategy'] === 'mobile' ? 'primary' : 'info' }}">
                                        <i class="las {{ $vital['strategy'] === 'mobile' ? 'la-mobile-alt' : 'la-desktop' }} mr-1"></i>
                                        {{ ucfirst($vital['strategy']) }}
                                    </span>
                                </td>
                                <td>
                                    @php $perfClass = $vital['performance'] >= 90 ? 'success' : ($vital['performance'] >= 50 ? 'warning' : 'danger'); @endphp
                                    <span class="badge badge-{{ $perfClass }}">{{ round((float)$vital['performance']) }}</span>
                                </td>
                                <td>
                                    @php $seoClass = $vital['seo'] >= 90 ? 'success' : ($vital['seo'] >= 50 ? 'warning' : 'danger'); @endphp
                                    <span class="badge badge-{{ $seoClass }}">{{ round((float)$vital['seo']) }}</span>
                                </td>
                                <td>
                                    @php $a11yClass = $vital['accessibility'] >= 90 ? 'success' : ($vital['accessibility'] >= 50 ? 'warning' : 'danger'); @endphp
                                    <span class="badge badge-soft-{{ $a11yClass }}">{{ round((float)$vital['accessibility']) }}</span>
                                </td>
                                <td>
                                    @php $bpClass = $vital['best_practices'] >= 90 ? 'success' : ($vital['best_practices'] >= 50 ? 'warning' : 'danger'); @endphp
                                    <span class="badge badge-soft-{{ $bpClass }}">{{ round((float)$vital['best_practices']) }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ \Carbon\Carbon::parse($vital['date'])->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="las la-clipboard-list la-3x text-muted mb-3 d-block"></i>
                                    {{ translate('No PageSpeed audits found. Run the "seo:pagespeed" artisan command to collect data.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
