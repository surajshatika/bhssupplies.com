@extends('frontend.layouts.app')

@section('lcp_preload')
@php
    $_lcp_lang    = get_system_language()->code;
    $_lcp_raw     = get_setting('home_slider_images', null, $_lcp_lang);
    $_lcp_url     = null;
    if ($_lcp_raw) {
        $_lcp_decoded = json_decode($_lcp_raw, true);
        $_lcp_sliders = get_slider_images($_lcp_decoded);
        if (!empty($_lcp_sliders)) {
            $_lcp_url = my_asset($_lcp_sliders[0]->file_name);
        }
    }
    // Flash deal banner is often the largest image in the viewport — preload it too
    $_lcp_flash   = cache()->remember('featured_flash_deal', 1800, fn() => get_featured_flash_deal());
    $_lcp_flash_url = ($_lcp_flash && $_lcp_flash->banner) ? uploaded_asset($_lcp_flash->banner) : null;
@endphp
{{-- Preload the flash deal banner first (largest above-fold image on desktop) --}}
@if($_lcp_flash_url)
<link rel="preload" as="image" href="{{ $_lcp_flash_url }}" fetchpriority="high">
@endif
{{-- Preload first slider image (LCP on mobile) --}}
@if($_lcp_url)
<link rel="preload" as="image" href="{{ $_lcp_url }}" fetchpriority="high">
@endif
@endsection

@section('content')
<style>
    @media (max-width: 767px) {
        #flash_deal .flash-deals-baner {
            height: 203px !important;
        }
    }
</style>
@php $lang = get_system_language()->code; @endphp

<div class="pt-32px pb-26px" style="background: {{ get_setting('hero_bg_color', '#f5f5f5') }}">
    <div class="container">
        <div class="row">
            <!-- Sliders -->
            <div class="col-lg-5 col-md-7 col-12">
                @if (get_setting('home_slider_images', null, $lang) != null)
                <div class="aiz-carousel dots-inside-bottom thecore-hero-slider" data-autoplay="true" data-infinite="true">
                    @php
                    $decoded_slider_images = json_decode(
                    get_setting('home_slider_images', null, $lang),
                    true,
                    );
                    $sliders = get_slider_images($decoded_slider_images);
                    $home_slider_links = get_setting('home_slider_links', null, $lang);
                    @endphp
                    @foreach ($sliders as $key => $slider)
                    <div class="carousel-box">
                        <a href="{{ isset(json_decode($home_slider_links, true)[$key]) ? json_decode($home_slider_links, true)[$key] : '' }}">
                            <div class="thecore-square-box overflow-hidden h-400px h-xl-500px h-xxl-516px">
                                <img class="img-fluid rounded-75 border border-light h-100"
                                    src="{{ $slider ? my_asset($slider->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                    alt="{{ env('APP_NAME') }} promo"
                                    @if($key === 0) fetchpriority="high" loading="eager" @else loading="lazy" @endif
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </div>
                        </a>
                    </div>

                    @endforeach
                </div>
                @endif
            </div>
            
            <div class="col-lg-7 col-md-5 pl-4 col-12">
                <div class="row">
                    @php
                    $flash_deal = cache()->remember('featured_flash_deal', 1800, fn() => get_featured_flash_deal());
                    @endphp
                    @if ($flash_deal != null)
                    <div class="col-lg-5 col-12 pl-2 pl-md-3 pl-xl-4">
                        <section class="mb-2" id="flash_deal">
                            <!-- Mobile view Countdown -->
                            <div class="mobile-countdown-simple d-md-none w-100 mb-2 mt-1"
                                data-end-date="{{ date('Y/m/d H:i:s', $flash_deal->end_date) }}">
                                <div class="countdown-text text-center">
                                    Ends in:
                                    <span id="simple-days">00</span> days
                                    <span id="simple-hours">00</span> hrs
                                    <span id="simple-mins">00</span> min
                                    <span id="simple-secs">00</span> sec
                                </div>
                            </div>

                            <div class="gutters-md-16 pb-1">
                                <!-- Flash Deals Baner & Countdown -->
                                

                                <div class="flash-deals-baner h-md-200px h-lg-220px h-xl-300px h-xxl-316px">
                                    <a href="{{ route('flash-deal-details', $flash_deal->slug) }}" class="d-block h-100 position-relative overflow-hidden rounded-75">
                                        {{-- <img> instead of CSS background-image so browser preload scanner discovers it early (LCP fix) --}}
                                        <img src="{{ uploaded_asset($flash_deal->banner) }}"
                                             alt="{{ translate('Flash Sale') }}"
                                             loading="eager"
                                             fetchpriority="high"
                                             class="position-absolute rounded-75"
                                             style="top:0;left:0;width:100%;height:100%;object-fit:cover;object-position:center center"
                                             onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">

                                        <div class="position-absolute bottom-0 w-100 py-3 d-none d-md-block">
                                            <div class="d-flex justify-content-center">
                                                <div class="aiz-count-down-circle rounded-2 p-0 p-xl-2 mx-3 bg-white shadow-lg"
                                                    end-date="{{ date('Y/m/d H:i:s', $flash_deal->end_date) }}">
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </section>
                    </div>
                    @endif

                    @if (count($hot_categories) > 0)
                    <!-- HOT Category -->
                    <div class="col-lg-{{ $flash_deal != null ? '7' : '12' }} col-12 pl-0 pl-lg-4 hot-categories">
                        <div class="mb-2 mb-sm-0 pl-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="24" viewBox="0 0 188 255" class="mb-2">
                                <path d="M187.899,164.809C185.803,214.868,144.574,254.812,94,254.812,42.085,254.812,0,211.312,0,160.812,0,154.062-.121,140.572,10,117.812c6.057-13.621,9.856-22.178,12-30,1.178-4.299,3.469-11.129,10,0,3.851,6.562,4,16,4,16s14.328-10.995,24-32c14.179-30.793,2.866-49.2-1-62-1.338-4.428-2.178-12.386,7,0,9.352,3.451,34.076,20.758,47,39,18.445,26.035,25,51,25,51s5.906-7.33,8-15c2.365-8.661,2.4-17.239,10-8.999,7.227,8.787,17.96,25.3,24,41C190.969,137.321,187.899,164.809,187.899,164.809Z" fill="#ff4c0d"/>
                                <path d="M94,254.812C58.101,254.812,29,225.711,29,189.812c0-21.661,8.729-34.812,26.896-52.646C67.528,125.747,78.415,111.722,83.042,102.172c.911-1.88,2.984-11.677,10.977-.206,4.193,6.016,10.766,16.715,14.981,25.846,7.266,15.743,9,31,9,31s7.121-4.196,12-15c1.573-3.482,4.753-16.664,13.643-3.484,6.523,9.672,15.484,27.062,15.357,49.484C159,225.711,129.898,254.812,94,254.812Z" fill="#fc9502"/>
                                <path d="M95,183.812c9.25,0,9.25,17.129,21,40,7.824,15.229-3.879,31-21,31s-26-13.879-26-31S85.75,183.812,95,183.812Z" fill="#fce202"/>
                            </svg>
                            <span class="d-inline-block fs-16 fw-700">{{ translate('Hot Categories') }}</span>
                        </div>
                        
                        <div class="aiz-carousel  arrow-inactive-transparent arrow-x-0 carousel-arrow"
                            data-rows="2" data-items="{{ $flash_deal != null ? '4' : '6' }}" data-xxl-items="{{ $flash_deal != null ? '4' : '6' }}" data-xl-items="{{ $flash_deal != null ? '4' : '6' }}" data-lg-items="{{ $flash_deal != null ? '4' : '6' }}"
                            data-md-items="{{ $flash_deal != null ? '4' : '6' }}" data-sm-items="5" data-xs-items="4" data-arrows="false" data-dots="false" data-autoplay="true" data-infinite="true">
                        
                            @foreach ($hot_categories as $key => $category)
                            @php
                                $category_name = $category->getTranslation('name');
                            @endphp
                            <div class="carousel-box hot-category-box mt-2 mt-md-1 mt-lg-2 mt-xl-3 mb-1 mb-md-0 mb-lg-1">
                                <div class="img h-80px w-80px h-md-60px w-md-60px h-lg-60px w-lg-60px h-xl-80px w-xl-80px h-xxl-90px w-xxl-90px rounded-2 overflow-hidden bg-white hov-scale-img">
                                    <a href="{{ route('products.category', $category->slug) }}">
                                        <img class="lazyload img-fit m-auto has-transition rounded-2"
                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                        data-src="{{ isset($category->banner) ? uploaded_asset($category->banner) : static_asset('assets/img/placeholder.jpg') }}"
                                        alt="{{ $category_name }}"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    </a>
                                </div>
                                <!-- Name -->
                                <div class="fs-11 mr-1 mt-1 mt-lg-2 mt-xl-3 text-center " title="{{ $category_name }}">
                                    <a href="{{ route('products.category', $category->slug) }}" class="fw-400 text-truncate-1 text-reset hov-text-primary"> {{ $category_name }}</a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12 d-none d-lg-block pl-md-0 pl-4 ml-0 ml-xl-2 featured-product">
                        @include('frontend.thecore.partials.featured_products')
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-12 d-block d-lg-none mt-3">
                @include('frontend.thecore.partials.featured_products')
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="selected_homepage" value="{{get_setting('homepage_select')}}">

@if (count($featured_categories) > 0)
<!-- Featured Category -->
<div class="pt-32px" style="background: #ffffffff;">
    <div class="container">
        <div class="featured-categories rounded-75 px-3" style="background: {{ get_setting('featured_category_section_bg_color', '#ffffff') }} ; @if(get_setting('featured_category_section_outline') == 1) border: 2px solid {{ get_setting('featured_category_section_outline_color', '#000') }}; @endif">
            <div class="row pt-32px pb-26px">
                <div class="col-sm-6 col-md-4 col-lg-3 col-12 mb-3 mb-sm-0">
                    <div class="px-0 px-md-3">
                        <p class="fs-16 fw-700  font-weight-bold mb-1 mb-sm-3">{{translate('Featured Categories')}}</p>
                        <p class="fs-13 fs-lg-14 fw-400 text-truncate-2" title="{{translate('Categories catching eyes & winning hearts across our marketplace')}}">{{translate('Categories catching eyes & winning hearts across our marketplace')}}</p>
                        <a class="btn fs-10 fs-md-16 custom-hov-btn py-2" href="{{route('categories.all')}}" style="background: {{ get_setting('featured_category_btn_color', '#F94C10') }}; color: {{ get_setting('featured_category_section_btn_text_color', '#f5f5f5') }};">
                            <span class="d-inline">{{ translate('All Categories') }}</span>
                        </a>
                    </div>
                </div>

                <div class="col-sm-6 col-md-8 col-lg-9 col-12">
                    <div class="aiz-carousel  arrow-inactive-transparent arrow-x-0  carousel-arrow"
                        data-rows="1" data-items="6" data-xxl-items="6" data-xl-items="5" data-lg-items="4"
                        data-md-items="3" data-sm-items="1" data-xs-items="4" data-arrows="true" data-dots="false" data-autoplay="true" data-infinite="true">
                    
                        @foreach ($featured_categories as $key => $category)
                        @php
                            $category_name = $category->getTranslation('name');
                        @endphp
                        <div class="carousel-box">
                            
                            <div class="img w-60px h-60px w-sm-70px h-sm-70px  h-md-100px w-md-100px h-lg-120px w-lg-120px rounded overflow-hidden mx-auto hov-scale-img">
                                <a href="{{ route('products.category', $category->slug) }}">
                                    <img class="lazyload img-fit m-auto has-transition"
                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ isset($category->cover_image) ? uploaded_asset($category->cover_image) : static_asset('assets/img/placeholder.jpg') }}"
                                    alt="{{ $category_name }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                </a>
                            </div>
                            <!-- Name -->
                            <div class="fs-11 mr-1 mt-3 text-center mt-2" title="{{ $category_name }}">
                                <a class="fw-400 text-reset hov-text-primary" href="{{ route('products.category', $category->slug) }}"> {{ strlen($category_name) > 18 ? substr($category_name, 0,18).'...' : $category_name }}</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
  
<!-- Best Selling And Todays Deal -->
<section class="pt-4 pt-lg-5 pb-4">
    <div class="container">    
        <div class="d-sm-flex">
            <!-- Best Selling -->
            @php
             $best_selling_products = cache()->remember('home_best_selling_20', 1800, fn() => get_best_selling_products(20));
            @endphp
            @if (count($best_selling_products) > 0)
            <div class="px-0 px-sm-4 w-100 overflow-hidden rounded-75 best-salling-section pt-32px pb-26px mb-4 mb-sm-0" style="background-color: {{ get_setting('best_selling_section_bg_color', '#E7EFEC') }}">
                <!-- Top Section -->
                <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between px-3 px-md-2">
                    <!-- Title -->
                    <h3 class="fs-16 fw-600 mb-2 mb-sm-0">
                        <span class="">{{ translate('Best Selling') }}</span>
                    </h3>
                    <a type="button" class="arrow-next text-white bg-dark view-more-slide-btn d-flex align-items-center" href="{{route('best-selling')}}">
                        <span><i class="las la-angle-right fs-20 fw-600"></i></span>
                        <span class="fs-12 mr-2 text">{{translate('View All')}}</span>
                    </a>
                </div>
                <div class="aiz-carousel arrow-x-0 arrow-inactive-none" data-items="5"
                    data-xxl-items="5" data-xl-items="5" data-lg-items="5" data-md-items="3" data-sm-items="1"
                    data-xs-items="3" data-arrows="false" data-dots="false" data-autoplay="false" data-infinite="true">
                    @foreach ($best_selling_products as $key => $product)
                        <div class="px-3">
                            <div class="img h-80px w-80px h-lg-100px w-lg-100px  h-xl-130px w-xl-130px h-xxl-170px w-xxl-170px rounded overflow-hidden mx-auto position-relative image-hover-effect">
                                <a href="{{ route('product', $product->slug) }}" title="{{ $product->getTranslation('name') }}">
                                    <img class="lazyload img-fit m-auto has-transition product-main-image"
                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ get_image($product->thumbnail) }}"
                                    alt="{{ $product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">

                                    <img
                                    class="lazyload img-fit m-auto has-transition product-main-image product-hover-image position-absolute"
                                    src="{{ get_first_product_image($product->thumbnail, $product->photos) }}"
                                    alt="{{ $product->getTranslation('name') }}"
                                    title="{{ $product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                </a>
                            </div>

                            <!-- Name -->
                            <div class="fs-13 mr-sm-1 mt-3 text-center mt-2 px-xs-0 px-sm-4" title="{{ $product->getTranslation('name') }}">
                                <a class="fw-400 text-truncate-2 hov-text-primary text-reset" href="{{ route('product', $product->slug) }}">{{ $product->getTranslation('name') }}</a>
                            </div>

                            <!-- Price -->
                            <div class="fs-14 mr-1 mt-1 text-center">
                                <span class="d-block fw-700">{{ home_discounted_base_price($product) }}</span>
                                @if (home_base_price($product) != home_discounted_base_price($product))
                                    <del class="d-block text-secondary fs-12 fw-400">{{ home_base_price($product) }}</del>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <!-- Todays Deal -->
            @endif
            @php
             $todays_deal_products = cache()->remember('home_todays_deal_20', 1800, fn() => get_todays_deal_products(20));
            @endphp
            @if (count($todays_deal_products) > 0)
            <div class="px-0 mt-sm-0 ml-sm-4 w-100  w-md-50 w-lg-35 overflow-hidden border border-2 border-dark rounded-75 todays-deal pt-32px pb-26px" style="background-color: {{ get_setting('todays_deal_bg_color', '#ffffff') }}">
                <div class="d-flex mx-3 mb-3 align-items-baseline justify-content-between">
                    <!-- Title -->
                    <h3 class="fs-16 fw-600 mb-2 mb-sm-0">
                        <span class="">{{ translate('Todays Deal') }}</span>
                    </h3>
                    <!-- Links -->
                    <a type="button" class="arrow-next text-white bg-dark view-more-slide-btn d-flex align-items-center" href="{{ route('todays-deal') }}">
                        <span><i class="las la-angle-right fs-20 fw-600"></i></span>
                        <span class="fs-12 mr-2 text">View All</span>
                    </a>
                </div>  
        
                <div class="aiz-carousel arrow-x-0 arrow-inactive-none" data-items="1"
                    data-xxl-items="1" data-xl-items="1" data-lg-items="1" data-md-items="1" data-sm-items="1"
                    data-xs-items="1" data-arrows="true" data-dots="false" data-autoplay="true" data-infinite="true">
                    @foreach ($todays_deal_products as $key => $product)
                        <div class="px-3">
                            <div class="img h-80px w-80px h-lg-100px w-lg-100px  h-xl-130px w-xl-130px h-xxl-170px w-xxl-170px rounded overflow-hidden mx-auto position-relative image-hover-effect">
                                <a href="{{ route('product', $product->slug) }}" title="{{ $product->getTranslation('name') }}">
                                    <img class="lazyload img-fit m-auto has-transition product-main-image"
                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ get_image($product->thumbnail) }}"
                                    alt="{{ $product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">

                                    <img
                                    class="lazyload img-fit m-auto has-transition product-main-image product-hover-image position-absolute"
                                    src="{{ get_first_product_image($product->thumbnail, $product->photos) }}"
                                    alt="{{ $product->getTranslation('name') }}"
                                    title="{{ $product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                </a>
                            </div>

                            <!-- Name -->
                            <div class="fs-13 mr-1 mt-3 text-center px-xs-0 px-sm-4" title="{{ $product->getTranslation('name') }}">
                                <a class="fw-400 text-truncate-2 hov-text-primary text-reset" href="{{ route('product', $product->slug) }}">{{ $product->getTranslation('name') }}</a>
                            </div>

                            <!-- Price -->
                            <div class="fs-14 mr-1 mt-1 text-center">
                                <span class="d-block fw-700">{{ home_discounted_base_price($product) }}</span>
                                @if (home_base_price($product) != home_discounted_base_price($product))
                                    <del
                                        class="d-block text-secondary fs-12 fw-400">{{ home_base_price($product) }}</del>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Banner section 1 -->
@php $homeBanner1Images = get_setting('home_banner1_images', null, $lang); @endphp
@if ($homeBanner1Images != null)
<div class="pt-3 pt-lg-4 pb-2 pb-lg-3 mb-1">
    <div class="container">
        @php
        $banner_1_imags = json_decode($homeBanner1Images);
        $data_md = count($banner_1_imags) >= 2 ? 2 : 1;
        $home_banner1_links = get_setting('home_banner1_links', null, $lang);
        @endphp
        <div class="w-100 pr-3 pr-md-0">
            <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15 home-banner-1"
                data-items="{{ count($banner_1_imags) }}" data-xxl-items="{{ count($banner_1_imags) }}"
                data-xl-items="{{ count($banner_1_imags) }}" data-lg-items="{{ $data_md }}"
                data-md-items="2.5" data-sm-items="2.5" data-xs-items="1.5" data-arrows="false"
                data-dots="false" data-autoplay="true" data-infinite="true">
                @foreach ($banner_1_imags as $key => $value)
                <div class="carousel-box overflow-hidden hov-scale-img">
                    <a href="{{ isset(json_decode($home_banner1_links, true)[$key]) ? json_decode($home_banner1_links, true)[$key] : '' }}"
                        class="d-block text-reset overflow-hidden rounded-75 h-100">
                        <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                            data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                            class="lazyload img-fit h-100 has-transition"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif



<!-- Auction Product -->
@if (addon_is_activated('auction'))
<div id="auction_products">

</div>
@endif



<!-- Classified Product -->
@if (get_setting('classified_product') == 1)
    @php
        $classified_products = get_home_page_classified_products();
    @endphp
    @if (count($classified_products) > 0)
        <section class="pt-32px pb-26px my-4" style="background: {{ get_setting('classified_bg_color', '#f5f5f5') }}">
            <div class="container">
                    <div class="d-sm-flex">
                        <div class=" w-100 overflow-hidden">
                            <!-- Top Section -->
                            <div class="d-flex align-items-baseline justify-content-between">
                                <!-- Title -->
                                <div class="mb-sm-0 ml-3 pb-2">
                                    <h4 class="fs-16 fw-700 mb-0">{{ translate('Classified Ads') }}</h4>
                                    <p class="fs-12 mb-0 fw-400">{{translate('products')}} ({{count($classified_products)}})</p>
                                </div>
                                <a type="button" class="arrow-next text-white bg-dark view-more-slide-btn d-flex align-items-center" href="{{ route('customer.products') }}">
                                    <span><i class="las la-angle-right fs-20 fw-600"></i></span>
                                    <span class="fs-12 mr-2 text">View All</span>
                                </a>
                            </div>
                            <div class="aiz-carousel arrow-x-0 arrow-inactive-none" data-items="7"
                                data-xxl-items="7" data-xl-items="6" data-lg-items="5" data-md-items="4" data-sm-items="4"
                                data-xs-items="3" data-arrows="false" data-dots="false" data-autoplay="true" data-infinite="true">
                                @foreach ($classified_products as $key => $product)
                                    <div class="px-3">
                                        <div class="img h-100px w-100px h-md-150px w-md-150px h-lg-170px w-lg-170px rounded-2 overflow-hidden mx-auto position-relative image-hover-effect">
                                            <a href="{{ route('customer.product', $product->slug) }}"title="{{ $product->getTranslation('name') }}">
                                                <img class="lazyload img-fit m-auto has-transition product-main-image"
                                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                data-src="{{ get_image($product->thumbnail) }}"
                                                alt="{{ $product->getTranslation('name') }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">

                                                <img
                                                class="lazyload img-fit m-auto has-transition product-main-image product-hover-image position-absolute"
                                                src="{{ get_first_product_image($product->thumbnail, $product->photos) }}"
                                                alt="{{ $product->getTranslation('name') }}"
                                                title="{{ $product->getTranslation('name') }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            </a>
                                        </div>

                                        <div class="text-center mt-2">
                                            <h3 class="fw-400 fs-13 text-truncate-2 lh-1-4 mb-1 h-35px">
                                                <a href="{{ route('customer.product', $product->slug) }}"
                                                    class="text-reset hov-text-primary hov-text-primary">{{ $product->getTranslation('name') }}</a>
                                            </h3>
                                            <div class="fw-700 fs-14 mb-1 mt-2">
                                                {{ single_price($product->unit_price) }}
                                            </div>
                                            <div class="m-2">
                                                @if ($product->conditon == 'new')
                                                <span
                                                    class="badge-sm badge-dark fs-13 fw-600 px-2 py-1 text-white rounded">{{ translate('New') }}</span>
                                                @elseif($product->conditon == 'used')
                                                <span
                                                    class="badge-sm badge-soft-primary fs-13 fw-600 px-2 py-1 text-primary rounded">{{ translate('Used') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
            </div>
        </section>
    @endif
@endif

@if (addon_is_activated('preorder'))
<!-- Newest Preorder Products -->
@include('preorder.frontend.home_page.thecore.newest_preorder')
@endif


<!-- Banner Section 2 -->
@php $homeBanner2Images = get_setting('home_banner2_images', null, $lang); 
$homeBanner2SmallImages = get_setting('home_banner2_sm_images', null, $lang); 
@endphp
@if ($homeBanner2Images != null)
<div class="py-32px mt-2 mb-32px">
    <div class="container">
        @php
        $banner_2_imags = json_decode($homeBanner2Images, true) ?? [];
        $banner_2_small_imags = json_decode($homeBanner2SmallImages, true) ?? [];
        $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
        $data_small_md = count($banner_2_small_imags) >= 2 ? 2 : 1;
        $home_banner2_links = get_setting('home_banner2_links', null, $lang);
        @endphp
        <div class="d-none d-md-block aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
            data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
            data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
            data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
            data-dots="false">
            @foreach ($banner_2_imags as $key => $value)
            <div class="carousel-box overflow-hidden hov-scale-img">
                <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}"
                    class="d-block text-reset overflow-hidden rounded-75">
                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                        class="img-fluid lazyload w-100 has-transition"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                </a>
            </div>
            @endforeach
        </div>


        <div class="d-md-none aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
            data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
            data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_small_md }}"
            data-md-items="{{ $data_small_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
            data-dots="false">
            @foreach ($banner_2_small_imags as $key => $value)
            <div class="carousel-box overflow-hidden hov-scale-img">
                <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}"
                    class="d-block text-reset overflow-hidden rounded-75">
                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                        class="img-fluid lazyload w-100 has-transition"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- New Products -->
<div id="section_newest">
</div>
<div class="text-center d-none" id="view-more-container">
    <button type="button" class="btn btn-lg py-19px w-20 bg-light fs-12 fs-md-16 my-32px" id="view-more-btn">
        {{ translate('Load More') }}
        <i id="spinner-icon" class="las la-lg la-spinner la-spin d-none"></i>
    </button>
</div>

<!-- Service Areas Grid — internal linking hub for local SEO -->
<section class="mt-4 mb-0">
    <div class="container">
        <div class="bg-white p-4 rounded border" style="box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 fw-700 text-dark mb-0">Serving HVAC & Plumbing Contractors Across the GTA</h2>
                <a href="{{ route('trade-account') }}" class="btn btn-sm btn-primary fw-600 d-none d-md-inline-block">Set Up Trade Account</a>
            </div>
            <p class="fs-14 text-gray mb-3">Same-day pickup from our Mississauga warehouse — 7040 Torbram Rd #8. Open 7 days a week.</p>
            <div class="row no-gutters">
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.mississauga') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Mississauga</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.brampton') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Brampton</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.toronto') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Toronto</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.etobicoke') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Etobicoke</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.vaughan') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Vaughan</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.oakville') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Oakville</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.scarborough') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Scarborough</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.markham') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Markham</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.north-york') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">North York</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('locations.burlington') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-map-marker text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Burlington</span>
                    </a>
                </div>
                <div class="col-6 col-sm-4 col-lg-3 mb-2 pr-2">
                    <a href="{{ route('trade-account') }}" class="d-flex align-items-center p-2 border rounded has-transition hov-bg-soft-primary text-dark text-decoration-none">
                        <i class="las la-id-card text-primary mr-2 fs-16"></i>
                        <span class="fs-14 fw-600">Trade Account</span>
                    </a>
                </div>
            </div>
            {{-- Blog links row --}}
            <div class="border-top pt-3 mt-1">
                <p class="fs-13 fw-700 text-gray mb-2 text-uppercase" style="letter-spacing:0.5px;">Contractor Guides</p>
                <div class="row">
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'sheet-metal-duct-fittings-supplier-mississauga') }}" class="fs-13 text-primary">Sheet Metal Duct Fittings</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'where-to-buy-pex-pipe-wholesale-mississauga') }}" class="fs-13 text-primary">PEX Pipe Wholesale</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'brass-fittings-wholesale-mississauga-contractor-pricing') }}" class="fs-13 text-primary">Brass Fittings Wholesale</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'gas-valve-black-iron-pipe-supplier-mississauga') }}" class="fs-13 text-primary">Gas Valve & Black Iron Pipe</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'refrigerant-supplier-mississauga-r410a-r32-r454b') }}" class="fs-13 text-primary">Refrigerant Supplier</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'plumbing-rough-in-supplies-mississauga-same-day-pickup') }}" class="fs-13 text-primary">Plumbing Rough-In Supplies</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog.details', 'best-hvac-supply-store-mississauga-contractors') }}" class="fs-13 text-primary">Best HVAC Store Mississauga</a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-1">
                        <a href="{{ route('blog') }}" class="fs-13 text-primary fw-600">All Articles →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SEO Content Section -->
<section class="mt-4 mb-4">
    <div class="container">
        <div class="bg-white p-4 p-lg-5 rounded border" style="box-shadow: 0 4px 15px rgba(0,0,0,0.05);">

            <h1 class="h3 fw-700 mb-3 text-dark">BHS Supplies — Wholesale HVAC, Plumbing & Hardware Supplier in Mississauga</h1>
            <p class="fs-15 text-dark mb-3" style="line-height: 1.8;">
                <strong>BHS Supplies</strong> is Mississauga's go-to <strong>wholesale HVAC equipment supplier</strong> for licensed contractors, plumbers, and tradespeople across the GTA. Located at <strong>7040 Torbram Rd #8, Mississauga, ON</strong>, we stock 2,000+ SKUs of <strong>HVAC supplies, plumbing parts, and hardware</strong> — all available for same-day walk-in pickup. No minimum order. Trade accounts available. Open <strong>7 days a week</strong> including Sundays.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-2 text-dark">Sheet Metal Duct Fittings & Flexible Duct — Fully Stocked</h2>
            <p class="fs-15 text-dark mb-3" style="line-height: 1.8;">
                We carry a complete range of <strong>sheet metal duct fittings</strong> — round and rectangular elbows, tees, reducers, takeoffs, end caps, and transitions — alongside <strong>flexible duct in R4.2, R6, and R8 insulation ratings</strong>. Whether you need duct board, <strong>HVAC tape, mastic sealant</strong>, or insulated flexible duct for a residential installation or a large commercial project, our Mississauga warehouse has it ready for same-day pickup. We are a preferred <strong>sheet metal duct fittings supplier</strong> for HVAC contractors across Mississauga, Brampton, and the Greater Toronto Area.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-2 text-dark">PEX Pipe, Brass Fittings & Plumbing Supplies — Wholesale Prices</h2>
            <p class="fs-15 text-dark mb-3" style="line-height: 1.8;">
                Our plumbing inventory is built for working contractors. We stock <strong>PEX pipe (A and B types, all sizes, oxygen barrier and standard)</strong>, <strong>brass fittings</strong> (elbows, tees, couplings, ball valves), <strong>copper fittings</strong>, <strong>push-fit connectors</strong> (SharkBite compatible), <strong>gas valves</strong>, <strong>black iron pipe</strong> in all schedules, <strong>CSST flexible gas piping</strong>, and <strong>water heater parts</strong>. Plumbers across Mississauga, Brampton, Toronto, and Etobicoke rely on BHS for <strong>wholesale plumbing supplies</strong> with no minimum order and same-day availability.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-2 text-dark">Refrigerants, Air Filters & HVAC Accessories</h2>
            <p class="fs-15 text-dark mb-3" style="line-height: 1.8;">
                BHS Supplies is a fully stocked <strong>HVAC accessories supplier in Mississauga</strong>. We carry <strong>refrigerants including R-410A, R-32, and R-454B</strong>, <strong>air filters in all sizes (1", 2", 4" including HEPA)</strong>, programmable and smart <strong>thermostats and controls</strong>, exhaust fans, HRV accessories, and indoor air quality equipment. We also stock <strong>refrigerant recovery machines, vacuum pumps, manifold gauges</strong>, and all HVAC service tools for licensed HVAC technicians across the GTA.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-2 text-dark">Hardware, Fasteners & Safety Supplies</h2>
            <p class="fs-15 text-dark mb-3" style="line-height: 1.8;">
                Beyond HVAC and plumbing, BHS is a complete <strong>hardware and fastener supplier</strong> for trade professionals. We stock screws, bolts, nuts, washers in bulk packs, <strong>HVAC-specific hangers, brackets, clamps, and strapping</strong>, drill bits, hole saws, step bits, and a full range of <strong>safety and PPE equipment</strong> including gloves, masks, and goggles. One stop — HVAC, plumbing, hardware, and safety — all at <strong>wholesale contractor pricing</strong>.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-2 text-dark">Trade Accounts — Wholesale Pricing for Licensed Contractors</h2>
            <p class="fs-15 text-dark mb-3" style="line-height: 1.8;">
                Licensed HVAC technicians, plumbers, and contractors can set up a <strong>BHS trade account</strong> for volume pricing, priority stock access, and no minimum order requirements. Our <strong>B2B contractor portal at bhssupplies.com</strong> lets you browse inventory, place orders, and arrange same-day pickup — saving you time on every job. Call <strong>(647) 456-2244</strong> or email <strong>support@bhssupplies.com</strong> to register your trade account today.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-2 text-dark">Serving GTA Contractors — Mississauga, Brampton, Toronto & Beyond</h2>
            <p class="fs-15 text-dark mb-0" style="line-height: 1.8;">
                Conveniently located at <strong>7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</strong>, BHS Supplies is the closest <strong>wholesale HVAC and plumbing supply store</strong> for contractors working across Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, and all of the Greater Toronto Area. We are open <strong>Monday–Saturday 10am–6pm and Sunday 10am–2pm</strong>. Walk in, call ahead, or order online — we have the parts you need, when you need them. <strong>Call: (647) 456-2244 | Shop: bhssupplies.com</strong>
            </p>

        </div>
    </div>
</section>

@endsection

@section('script')
<script>
    // Countdown for mobile view
    function startSimpleCountdown(endDate) {
        function update() {
            const now = new Date();
            const diff = endDate - now;
            if (diff > 0) {
                const totalSeconds = Math.floor(diff / 1000);
                const days = Math.floor(totalSeconds / (60 * 60 * 24));
                const hours = Math.floor((totalSeconds % (60 * 60 * 24)) / (60 * 60));
                const mins = Math.floor((totalSeconds % (60 * 60)) / 60);
                const secs = totalSeconds % 60;

                document.getElementById("simple-days").textContent = days.toString().padStart(2, '0');
                document.getElementById("simple-hours").textContent = hours.toString().padStart(2, '0');
                document.getElementById("simple-mins").textContent = mins.toString().padStart(2, '0');
                document.getElementById("simple-secs").textContent = secs.toString().padStart(2, '0');
            } else {
                document.querySelector(".mobile-countdown-simple").textContent = "Sale ended";
                clearInterval(timer);
            }
        }

        update();
        const timer = setInterval(update, 1000);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const countdownEl = document.querySelector('.mobile-countdown-simple');
        if (!countdownEl) return;

        const endDateStr = countdownEl.dataset.endDate;
        if (endDateStr) {
            const parsedEndDate = new Date(endDateStr.replace(/-/g, '/'));
            startSimpleCountdown(parsedEndDate);
        }
    });



    let page = 1;        
    $(document).on('click', '#view-more-btn', function() {
        const $button = $(this);
        const originalText = $button.html(); 

        page++;
        $button.html('{{ translate("Loading...") }} <i id="spinner-icon" class="las la-lg la-spinner la-spin"></i>');
        $button.prop('disabled', true); 

        $.post('{{ route('home.section.newest_products') }}', {
            _token: '{{ csrf_token() }}',
            page: page
        }, function(data) {
            $button.prop('disabled', false);
            $button.html(originalText);
            
            if ($.trim(data) === '') {
                $button.prop('disabled', true).text('{{ translate("No More Products") }}');
            } else {
                $('#newest-products-list').append(data);
                AIZ.plugins.slickCarousel();
            }
        }).fail(function() {
            $button.prop('disabled', false);
            $button.html('{{ translate("Error, Try Again") }} <i id="spinner-icon" class="las la-lg la-spinner la-spin d-none"></i>');
        });
    });

    $(window).on('load', function() {
        $('.hot-category-box').addClass('d-flex flex-column justify-content-center align-items-center');
    });

    function toggleViewMoreButton() {
        if ($.trim($('#section_newest').html()).length > 0) {
            $('#view-more-container').removeClass('d-none').addClass('d-block');
        } else {
            $('#view-more-container').removeClass('d-block').addClass('d-none');
        }
    }

</script>
@endsection