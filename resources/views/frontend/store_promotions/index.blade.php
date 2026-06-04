@extends('frontend.layouts.app')

@php
    $heading    = get_setting('store_page_heading', 'Promotions & Deals');
    $subheading = get_setting('store_page_subheading', 'Limited-time offers, liquidation sales and wholesale pricing.');
    $intro      = get_setting('store_page_intro', '');
    $metaTitle  = get_setting('store_page_meta_title') ?: ($heading . ' | ' . get_setting('website_name'));
    $metaDesc   = get_setting('store_page_meta_description') ?: 'Current promotions, liquidation sales and wholesale deals at ' . get_setting('website_name') . '.';
    $showCrumb  = (int) get_setting('store_page_show_breadcrumb', 1) === 1;
@endphp

@section('meta_title', $metaTitle)
@section('meta_description', $metaDesc)

@section('content')
@php
    $colClass = function ($w) {
        return $w === 'half' ? 'col-12 col-md-6' : ($w === 'third' ? 'col-12 col-md-4' : 'col-12');
    };
@endphp

<section class="py-4" style="background:linear-gradient(135deg,#0b2545,#13315c);">
    <div class="container text-center text-white">
        <h1 class="fw-700 fs-24 fs-md-30 mb-1">{{ $heading }}</h1>
        @if($subheading)<p class="opacity-80 mb-0">{{ $subheading }}</p>@endif
        @if($showCrumb)
        <ul class="breadcrumb bg-transparent p-0 justify-content-center mt-2 mb-0">
            <li class="breadcrumb-item"><a class="text-white opacity-70" href="{{ route('home') }}">{{ translate('Home') }}</a></li>
            <li class="breadcrumb-item text-white">{{ translate('Promotions') }}</li>
        </ul>
        @endif
    </div>
</section>

@if(trim(strip_tags($intro)) !== '')
<section class="pt-4">
    <div class="container"><div class="promo-content">{!! $intro !!}</div></div>
</section>
@endif

<section class="mb-5 pt-4">
    <div class="container">
        @if($blocks->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="las la-tags la-3x d-block mb-2"></i>
                {{ translate('No active promotions right now. Please check back soon.') }}
            </div>
        @else
            <div class="row gutters-10">
                @foreach($blocks as $block)
                    <div class="{{ $colClass($block->width) }} mb-3">
                        @if($block->type === 'content')
                            <div class="bg-white rounded shadow-sm p-3 p-md-4 h-100">
                                @if($block->title)<h2 class="fs-18 fw-700 mb-2">{{ $block->title }}</h2>@endif
                                <div class="promo-content">{!! $block->content !!}</div>
                            </div>
                        @else
                            @php $hasLink = !empty($block->link_url); @endphp
                            <{{ $hasLink ? 'a' : 'div' }}
                                @if($hasLink) href="{{ $block->link_url }}" @endif
                                class="d-block position-relative promo-tile rounded overflow-hidden shadow-sm {{ $block->featured ? 'promo-featured' : '' }}">
                                <img src="{{ uploaded_asset($block->image) }}"
                                     alt="{{ $block->title ?: translate('Promotion') }}"
                                     class="img-fluid w-100" loading="lazy">
                                @if($block->subtitle)
                                    <span class="promo-badge">{{ $block->subtitle }}</span>
                                @endif
                            </{{ $hasLink ? 'a' : 'div' }}>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<style>
    .promo-tile { transition: transform .2s, box-shadow .2s; background:#fff; }
    .promo-tile:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
    .promo-featured { outline: 3px solid #f6b100; outline-offset: -3px; }
    .promo-badge {
        position: absolute; top: 12px; right: 12px; background:#e7141a; color:#fff;
        font-weight:700; font-size:.8rem; padding:6px 12px; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,.25);
    }
    .promo-content img { max-width:100%; height:auto; }
</style>
@endsection
