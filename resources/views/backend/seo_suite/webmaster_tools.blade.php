@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-tools mr-2 text-danger"></i>{{ translate('Webmaster Tools') }}</h1>
            <p class="text-muted mb-0">{{ translate('Manage verification codes for Google, Bing, Yandex, Pinterest, and Baidu.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

@php
    $verifications = $result['verifications'] ?? [];
    $metaTags      = $result['meta_tags'] ?? [];
    $instructions  = $result['instructions'] ?? [];
    $webmasters = [
        'google'    => ['label' => 'Google Search Console',   'color' => 'success',  'icon' => 'la-google',    'placeholder' => 'e.g. abc123XYZ456'],
        'bing'      => ['label' => 'Bing Webmaster Tools',    'color' => 'info',     'icon' => 'la-microsoft', 'placeholder' => 'e.g. 123ABC456DEF'],
        'yandex'    => ['label' => 'Yandex.Webmaster',        'color' => 'warning',  'icon' => 'la-globe',     'placeholder' => 'e.g. abc123456789'],
        'pinterest' => ['label' => 'Pinterest Verification',  'color' => 'danger',   'icon' => 'la-pinterest', 'placeholder' => 'e.g. abc123def456'],
        'baidu'     => ['label' => 'Baidu Zhanzhang',         'color' => 'primary',  'icon' => 'la-search',    'placeholder' => 'e.g. abcdef123456'],
    ];
@endphp

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Verification Codes') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.webmaster.save') }}" method="POST">
                    @csrf
                    @foreach($webmasters as $key => $meta)
                    <div class="card mb-3 border-{{ $meta['color'] }}">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-{{ $meta['color'] }} text-white d-flex align-items-center justify-content-center mr-2 flex-shrink-0"
                                     style="width:32px;height:32px;">
                                    <i class="las {{ $meta['icon'] }}"></i>
                                </div>
                                <strong>{{ $meta['label'] }}</strong>
                                @if(!empty($verifications[$key]))
                                    <span class="badge badge-success ml-auto">{{ translate('Configured') }}</span>
                                @else
                                    <span class="badge badge-secondary ml-auto">{{ translate('Not Set') }}</span>
                                @endif
                            </div>
                            <div class="form-group mb-2">
                                <input type="text" class="form-control form-control-sm"
                                    name="{{ $key }}_verification"
                                    value="{{ $verifications[$key] ?? '' }}"
                                    placeholder="{{ $meta['placeholder'] }}">
                            </div>
                            @if(!empty($instructions[$key]))
                                <small class="text-muted"><i class="las la-info-circle mr-1"></i>{{ $instructions[$key] }}</small>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <button class="btn btn-primary w-100"><i class="las la-save mr-1"></i>{{ translate('Save Verification Codes') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if(!empty($metaTags))
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Generated Meta Tags') }}</h6></div>
            <div class="card-body">
                <p class="text-muted small">{{ translate('Add these to the') }} <code>&lt;head&gt;</code> {{ translate('section of your main layout file.') }}</p>
                <div class="bg-dark text-light rounded p-3" style="font-size:0.78rem; font-family:monospace;">
                    @foreach($metaTags as $tag)
                        <div class="mb-1">{{ $tag }}</div>
                    @endforeach
                </div>
                <button id="copy-meta-tags" class="btn btn-sm btn-soft-info mt-2">
                    <i class="las la-copy mr-1"></i>{{ translate('Copy to Clipboard') }}
                </button>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Quick Links') }}</h6></div>
            <div class="card-body p-2">
                @foreach([
                    ['https://search.google.com/search-console', 'Google Search Console', 'success'],
                    ['https://www.bing.com/webmasters', 'Bing Webmaster Tools', 'info'],
                    ['https://webmaster.yandex.com', 'Yandex.Webmaster', 'warning'],
                    ['https://analytics.pinterest.com', 'Pinterest Analytics', 'danger'],
                    ['https://zhanzhang.baidu.com', 'Baidu Zhanzhang', 'primary'],
                ] as $link)
                <a href="{{ $link[0] }}" target="_blank" class="btn btn-soft-{{ $link[2] }} btn-sm btn-block text-left mb-1">
                    <i class="las la-external-link-alt mr-1"></i>{{ $link[1] }}
                </a>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Implementation Guide') }}</h6></div>
            <div class="card-body small text-muted">
                <p>{{ translate('After saving verification codes, add the generated meta tags to your') }} <code>resources/views/layouts/app.blade.php</code> {{ translate('or frontend layout file inside') }} <code>&lt;head&gt;</code>.</p>
                <p class="mb-0">{{ translate('Alternatively, render the WebmasterToolsService html_snippet from your layout.') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    $('#copy-meta-tags').on('click', function() {
        var text = '';
        $('.bg-dark div').each(function() { text += $(this).text() + '\n'; });
        navigator.clipboard.writeText(text.trim()).then(function() {
            $('#copy-meta-tags').text('Copied!');
            setTimeout(function() { $('#copy-meta-tags').html('<i class="las la-copy mr-1"></i>Copy to Clipboard'); }, 2000);
        });
    });
});
</script>
@endsection
