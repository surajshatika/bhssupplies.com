@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0"><i class="las la-users mr-2 text-success"></i>{{ translate('Real User Field Data (CrUX)') }}</h4>
        <small class="text-muted">{{ translate('Actual Core Web Vitals measured from real Chrome users — the data Google ranks on.') }}</small>
    </div>
    <a href="{{ route('admin.seo-suite.core_web_vitals') }}" class="btn btn-sm btn-outline-secondary">
        <i class="las la-flask mr-1"></i>{{ translate('View Lab Data') }}
    </a>
</div>

@include('backend.seo.partials.suite_nav')

<div class="alert alert-info py-2 small mb-4">
    <i class="las la-info-circle mr-1"></i>
    <strong>{{ translate('Field data vs lab data:') }}</strong>
    {{ translate('The PageSpeed/Lighthouse page shows LAB data — a simulated load on synthetic hardware. This page shows FIELD data: the 28-day rolling distribution of real Chrome users on real devices and networks. Google ranks on field data.') }}
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('admin.seo-suite.field_data') }}" method="GET" class="form-row align-items-end">
            <div class="col-md-7 mb-2">
                <label class="form-label font-weight-600 small">{{ translate('URL') }}</label>
                <input type="url" name="url" class="form-control" required
                       value="{{ $url ?: $siteUrl }}" placeholder="https://yourdomain.com/page">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label font-weight-600 small">{{ translate('Device') }}</label>
                <select name="form_factor" class="form-control">
                    <option value="PHONE" {{ $formFactor === 'PHONE' ? 'selected' : '' }}>{{ translate('Mobile') }}</option>
                    <option value="DESKTOP" {{ $formFactor === 'DESKTOP' ? 'selected' : '' }}>{{ translate('Desktop') }}</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button type="submit" class="btn btn-success btn-block"><i class="las la-search mr-1"></i>{{ translate('Look Up') }}</button>
            </div>
        </form>
    </div>
</div>

@if($result)
    @if(!$result['has_data'])
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="las la-user-slash text-muted" style="font-size:3rem;opacity:.35;"></i>
                <h6 class="mt-3">{{ translate('No field data available') }}</h6>
                <p class="text-muted small mb-0" style="max-width:560px;margin:0 auto;">{{ $result['message'] }}</p>
                @if($result['reason'] === 'insufficient_traffic')
                    <p class="text-muted small mt-3 mb-0">
                        <em>{{ translate('This is a normal result for lower-traffic pages, not an error. We deliberately do not substitute simulated lab numbers here — they would not reflect what real users experience.') }}</em>
                    </p>
                @endif
            </div>
        </div>
    @else
        @if(!empty($result['fell_back_to_origin']))
            <div class="alert alert-warning py-2 small">
                <i class="las la-exclamation-triangle mr-1"></i>{{ $result['note'] }}
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <div class="small text-muted">{{ translate('Core Web Vitals Assessment') }}</div>
                    @if($result['cwv_pass'] === true)
                        <h5 class="mb-0 text-success"><i class="las la-check-circle mr-1"></i>{{ translate('Passed') }}</h5>
                    @elseif($result['cwv_pass'] === false)
                        <h5 class="mb-0 text-danger"><i class="las la-times-circle mr-1"></i>{{ translate('Failed') }}</h5>
                    @else
                        <h5 class="mb-0 text-muted">{{ translate('Incomplete data') }}</h5>
                    @endif
                    <small class="text-muted">{{ translate('LCP, INP, and CLS must all be "good" at the 75th percentile.') }}</small>
                </div>
                <div class="text-md-right mt-2 mt-md-0">
                    <span class="badge badge-soft-secondary">{{ $result['origin_level'] ? translate('Origin-wide') : translate('URL-level') }}</span>
                    <span class="badge badge-soft-info">{{ $result['form_factor'] === 'PHONE' ? translate('Mobile') : translate('Desktop') }}</span>
                    <div class="small text-muted mt-1">{{ $result['data_source'] }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach($result['metrics'] as $metric)
                @php
                    $ratingColor = ['good' => 'success', 'needs-improvement' => 'warning', 'poor' => 'danger'][$metric['rating']];
                @endphp
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-0">{{ $metric['label'] }}</h6>
                                    <small class="text-muted">{{ translate($metric['name']) }}</small>
                                </div>
                                <span class="badge badge-soft-{{ $ratingColor }}">{{ translate(ucfirst(str_replace('-', ' ', $metric['rating']))) }}</span>
                            </div>

                            <div class="text-{{ $ratingColor }}" style="font-size:1.9rem;font-weight:700;line-height:1.1;">{{ $metric['display'] }}</div>
                            <div class="small text-muted mb-3">{{ translate('75th percentile of real users') }}</div>

                            {{-- The real distribution, not a single synthetic number. --}}
                            <div class="progress mb-2" style="height:8px;">
                                <div class="progress-bar bg-success" style="width:{{ $metric['good_pct'] }}%"></div>
                                <div class="progress-bar bg-warning" style="width:{{ $metric['medium_pct'] }}%"></div>
                                <div class="progress-bar bg-danger" style="width:{{ $metric['poor_pct'] }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="las la-circle text-success"></i> {{ $metric['good_pct'] }}%</span>
                                <span><i class="las la-circle text-warning"></i> {{ $metric['medium_pct'] }}%</span>
                                <span><i class="las la-circle text-danger"></i> {{ $metric['poor_pct'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@else
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="las la-users" style="font-size:3rem;opacity:.3;"></i>
            <p class="mt-3 mb-0">{{ translate('Enter a URL to look up its real-user Core Web Vitals.') }}</p>
        </div>
    </div>
@endif
@endsection
