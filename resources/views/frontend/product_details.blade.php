@extends('frontend.layouts.app')

@section('meta_title'){{ $detailedProduct->meta_title }}@stop

@section('meta_description'){{ $detailedProduct->meta_description }}@stop

@section('meta_keywords'){{ $detailedProduct->tags }}@stop

@section('canonical'){{ route('product', $detailedProduct->slug) }}@endsection

@section('meta')
    @php
        $_pd_img   = uploaded_asset($detailedProduct->meta_img) ?: uploaded_asset($detailedProduct->thumbnail_img);
        $_pd_title = $detailedProduct->meta_title ?: $detailedProduct->getTranslation('name');
        $_pd_desc  = $detailedProduct->meta_description ?: '';
        $_pd_url   = route('product', $detailedProduct->slug);
        $_pd_price = $detailedProduct->unit_price;
        $_og_locale = str_replace('-', '_', str_replace('_', '-', app()->getLocale()));
        $_site_name = get_setting('website_name') ?: env('APP_NAME');
        try {
            $_pd_currency = \App\Models\Currency::findOrFail(get_setting('system_default_currency'))->code;
        } catch (\Exception $e) {
            $_pd_currency = 'CAD';
        }
    @endphp

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $_pd_title }}">
    <meta name="twitter:description" content="{{ $_pd_desc }}">
    <meta name="twitter:image" content="{{ $_pd_img }}">

    <!-- Open Graph -->
    <meta property="og:locale" content="{{ $_og_locale }}">
    <meta property="og:title" content="{{ $_pd_title }}">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ $_pd_url }}">
    <meta property="og:image" content="{{ $_pd_img }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:description" content="{{ $_pd_desc }}">
    <meta property="og:site_name" content="{{ $_site_name }}">
    <meta property="og:price:amount" content="{{ $_pd_price }}">
    <meta property="product:price:currency" content="{{ $_pd_currency }}">
    @if(env('FACEBOOK_PIXEL_ID'))
    <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">
    @endif

    {{-- LCP preload: first product gallery image --}}
    @php
        $pdFirstPhoto = $detailedProduct->photos ? uploaded_asset(explode(',', $detailedProduct->photos)[0]) : uploaded_asset($detailedProduct->meta_img);
    @endphp
    @if($pdFirstPhoto)
    @section('lcp_preload')
        <link rel="preload" as="image" href="{{ $pdFirstPhoto }}" fetchpriority="high">
    @endsection
    @endif

    {{-- Product Schema (JSON-LD) --}}
    @php
        $pdCurrencyCode = $_pd_currency;
        $pdQty          = $detailedProduct->stocks->sum('qty');
        $pdInStock      = ($pdQty > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        $pdRating       = $detailedProduct->rating ?: null;
        $pdReviewCount  = $detailedProduct->reviews->count();
        $pdImages       = [];
        if ($detailedProduct->photos) {
            foreach (explode(',', $detailedProduct->photos) as $photo) {
                $pdImages[] = uploaded_asset(trim($photo));
            }
        }
        if (empty($pdImages)) { $pdImages[] = $_pd_img; }
        $pdPriceValidUntil = now()->addYear()->format('Y-m-d');
    @endphp
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "@id": "{{ $_pd_url }}",
      "name": "{{ addslashes($_pd_title) }}",
      "description": "{{ addslashes($_pd_desc) }}",
      "image": @json($pdImages),
      "sku": "{{ $detailedProduct->sku }}",
      "brand": {
        "@type": "Brand",
        "name": "{{ $detailedProduct->brand ? addslashes($detailedProduct->brand->getTranslation('name')) : $_site_name }}"
      },
      "offers": {
        "@type": "Offer",
        "url": "{{ $_pd_url }}",
        "priceCurrency": "{{ $pdCurrencyCode }}",
        "price": "{{ number_format($detailedProduct->unit_price, 2, '.', '') }}",
        "priceValidUntil": "{{ $pdPriceValidUntil }}",
        "availability": "{{ $pdInStock }}",
        "itemCondition": "https://schema.org/NewCondition",
        "seller": {
          "@type": "Organization",
          "name": "{{ $_site_name }}"
        }
      }@if($pdRating && $pdReviewCount > 0),
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ number_format($pdRating, 1) }}",
        "reviewCount": "{{ $pdReviewCount }}",
        "bestRating": "5",
        "worstRating": "1"
      }@endif
    }
    </script>

    {{-- BreadcrumbList Schema --}}
    @php
        $pdBreadcrumbs = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
        ];
        if ($detailedProduct->category) {
            $pdBreadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $detailedProduct->category->getTranslation('name'), 'item' => route('products.category', $detailedProduct->category->slug)];
            $pdBreadcrumbs[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $detailedProduct->getTranslation('name'), 'item' => route('product', $detailedProduct->slug)];
        } else {
            $pdBreadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $detailedProduct->getTranslation('name'), 'item' => route('product', $detailedProduct->slug)];
        }
    @endphp
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": @json($pdBreadcrumbs)
    }
    </script>
@endsection

@section('content')
<style>
/* ── BHS Supplies – Professional Product Detail Page ── */
.pd-gallery-wrap { background:#f8f9fa; border-radius:10px; overflow:hidden; }
.product-gallery .carousel-box { background:#fff; padding:8px; border-radius:8px; display:flex; align-items:center; justify-content:center; min-height:320px; }
.product-gallery-thumb .carousel-box { border-radius:6px; border:2px solid transparent !important; transition:border-color .2s; }
.product-gallery-thumb .slick-current .carousel-box { border-color:#ff6b00 !important; }

/* title & badges */
.pd-title { font-size:1.35rem; font-weight:800; color:#1a1a2e; line-height:1.45; margin-bottom:.5rem; }
.pd-sku-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:.85rem; }
.pd-sku-chip { background:#f1f3f5; border-radius:4px; padding:3px 10px; font-size:12px; color:#555; }
.pd-sku-chip b { color:#222; }
.pd-stock-in  { background:#e8f5e9; color:#2e7d32; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:700; }
.pd-stock-out { background:#fce4ec; color:#c62828; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:700; }

/* price box */
.pd-price-box { background:linear-gradient(135deg,#fff8f0,#fff); border:1.5px solid #ffe0b2; border-radius:12px; padding:16px 20px; margin:14px 0; }
.pd-price-box .price-original { font-size:15px; color:#aaa; text-decoration:line-through; margin-right:6px; }
.pd-price-box .price-final { font-size:2.1rem; font-weight:900; color:#e65100; line-height:1; }
.pd-price-box .price-unit { font-size:13px; color:#999; margin-left:4px; }
.pd-price-box .price-save-badge { background:#e65100; color:#fff; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; margin-left:8px; vertical-align:middle; }

/* action buttons */
.btn-pd-cart { background:#fff; border:2px solid #ff6b00; color:#ff6b00; font-weight:700; font-size:15px; padding:11px 28px; border-radius:8px; transition:all .2s; }
.btn-pd-cart:hover { background:#ff6b00; color:#fff; }
.btn-pd-buy  { background:#ff6b00; color:#fff; font-weight:700; font-size:15px; padding:11px 28px; border-radius:8px; border:2px solid #ff6b00; transition:all .2s; }
.btn-pd-buy:hover  { background:#e65100; border-color:#e65100; color:#fff; }

/* trust strip */
.pd-trust-strip { display:flex; gap:0; border:1px solid #e9ecef; border-radius:10px; overflow:hidden; margin:16px 0; }
.pd-trust-item { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:10px 6px; font-size:11px; color:#555; border-right:1px solid #e9ecef; background:#fafafa; text-align:center; gap:3px; }
.pd-trust-item:last-child { border-right:none; }
.pd-trust-item i { font-size:20px; color:#ff6b00; }
.pd-trust-item b { font-size:12px; color:#222; }

/* seller info strip */
.pd-seller-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:13px; color:#555; margin-bottom:12px; }
.pd-seller-row a { color:#ff6b00; font-weight:600; }

/* tabs */
.aiz-nav-tabs { border-bottom:2px solid #e9ecef !important; }
.aiz-nav-tabs a { font-size:14px !important; font-weight:700 !important; padding:14px 22px !important; color:#555 !important; border-bottom:3px solid transparent !important; margin-bottom:-2px; transition:all .2s; text-decoration:none; }
.aiz-nav-tabs a:hover { color:#ff6b00 !important; }
.aiz-nav-tabs a.active.show { color:#ff6b00 !important; border-bottom-color:#ff6b00 !important; background:transparent !important; }

/* description layout */
.pd-short-description {
  background:linear-gradient(180deg,#fffdf8,#fff8ef);
  border:1px solid #f4dfc6;
  border-left:4px solid #ff6b00;
  border-radius:12px;
  padding:16px 18px;
  color:#4b5563;
  box-shadow:0 10px 24px rgba(17,24,39,.05);
}
.pd-short-description > *:last-child { margin-bottom:0 !important; }
.pd-description-shell {
  background:linear-gradient(180deg,#ffffff,#fbfcfe);
  border:1px solid #edf1f5;
  border-radius:18px;
  padding:24px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.8), 0 18px 45px rgba(15,23,42,.04);
}
.pd-description-head {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:18px;
  padding-bottom:14px;
  border-bottom:1px solid #edf1f5;
}
.pd-description-kicker {
  display:inline-flex;
  align-items:center;
  gap:8px;
  font-size:11px;
  font-weight:800;
  letter-spacing:.16em;
  text-transform:uppercase;
  color:#ff6b00;
}
.pd-description-kicker::before {
  content:"";
  width:24px;
  height:2px;
  background:#ff6b00;
  border-radius:999px;
}
.pd-description-note {
  font-size:12px;
  color:#94a3b8;
}
.pd-description-wrap {
  font-size:15px;
  line-height:1.8;
  color:#334155;
  text-align:left;
}
.pd-description-wrap > *:first-child { margin-top:0 !important; }
.pd-description-wrap > *:last-child { margin-bottom:0 !important; }
.pd-description-wrap,
.pd-description-wrap * {
  direction:ltr;
}
.pd-description-wrap p {
  margin:0 0 1rem;
  color:inherit;
  text-align:left !important;
}
.pd-description-wrap h1,
.pd-description-wrap h2,
.pd-description-wrap h3,
.pd-description-wrap h4 {
  color:#162033;
  font-weight:800;
  line-height:1.3;
  letter-spacing:-.02em;
  margin:1.55rem 0 .85rem;
}
.pd-description-wrap h1 { font-size:1.65rem; }
.pd-description-wrap h2 {
  font-size:1.3rem;
  padding-bottom:.55rem;
  border-bottom:1px solid #edf1f5;
}
.pd-description-wrap h3 { font-size:1.08rem; }
.pd-description-wrap h4 { font-size:1rem; }
.pd-description-wrap strong,
.pd-description-wrap b {
  color:#111827;
  font-weight:700;
}
.pd-description-wrap a {
  color:#d76500;
  text-decoration:none;
  border-bottom:1px solid rgba(215,101,0,.2);
}
.pd-description-wrap a:hover {
  color:#b45309;
  border-bottom-color:rgba(180,83,9,.4);
}
.pd-description-wrap ul,
.pd-description-wrap ol {
  margin:1rem 0 1.2rem;
  padding-left:1.25rem;
}
.pd-description-wrap li { margin-bottom:.45rem; }
.pd-description-wrap hr {
  border:none;
  border-top:1px solid #e7ecf1;
  margin:1.6rem 0;
}
.pd-description-wrap blockquote {
  margin:1.2rem 0;
  padding:1rem 1.1rem;
  border-left:4px solid #ff6b00;
  background:#fff8ef;
  color:#475569;
  border-radius:0 12px 12px 0;
}
.pd-description-wrap img {
  display:block;
  max-width:100% !important;
  height:auto !important;
  margin:18px auto;
  border-radius:14px;
  box-shadow:0 14px 28px rgba(15,23,42,.08);
}
.pd-description-wrap table {
  width:100% !important;
  border-collapse:separate;
  border-spacing:0;
  margin:18px 0;
  font-size:13.5px;
  background:#fff;
  border:1px solid #e6ebf0;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 12px 30px rgba(15,23,42,.05);
  margin-left:0 !important;
  margin-right:auto !important;
  text-align:left !important;
  float:none !important;
  display:table !important;
}
.pd-description-wrap table td,
.pd-description-wrap table th {
  padding:11px 14px;
  border:1px solid #e6ebf0;
  vertical-align:top;
  text-align:left !important;
}
.pd-description-wrap table th,
.pd-description-wrap table tr:first-child td {
  background:#172033;
  color:#fff;
  font-weight:700;
}
.pd-description-wrap table tr:nth-child(even) td { background:#f8fafc; }
.pd-description-wrap table tr,
.pd-description-wrap table tbody,
.pd-description-wrap table thead {
  text-align:left !important;
}
.pd-description-wrap iframe,
.pd-description-wrap video {
  max-width:100%;
  border-radius:14px;
}

/* related products */
.aiz-card-box { border-radius:10px !important; transition:transform .2s,box-shadow .2s; }
.aiz-card-box:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1) !important; }

/* top-selling sidebar */
.pd-sidebar-card { border-radius:10px; overflow:hidden; border:none; box-shadow:0 2px 10px rgba(0,0,0,.06); }
.pd-sidebar-card .card-heading { background:#1a1a2e; color:#fff; padding:12px 16px; font-size:14px; font-weight:700; }

/* breadcrumb hero */
.pd-hero {
    background:linear-gradient(135deg,#1a1a2e 0%,#0f3460 100%);
    color:#fff; padding:18px 0 14px; margin-bottom:20px;
}
.pd-hero-breadcrumb { font-size:12px; opacity:.65; margin-bottom:6px; }
.pd-hero-breadcrumb a { color:#fff; text-decoration:none; }
.pd-hero-breadcrumb a:hover { text-decoration:underline; }
.pd-hero-breadcrumb .sep { margin:0 6px; }
.pd-hero-title {
    font-size:1.35rem; font-weight:800; margin:0;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
    overflow:hidden;
}

@media(max-width:767px){
  .pd-price-box .price-final { font-size:1.7rem; }
  .btn-pd-cart,.btn-pd-buy { width:100%; margin-bottom:6px; }
  .pd-trust-strip { flex-wrap:wrap; }
  .pd-trust-item { flex:0 0 50%; border-bottom:1px solid #e9ecef; }
  .pd-description-shell { padding:18px; border-radius:14px; }
  .pd-description-head { display:block; }
  .pd-description-note { display:block; margin-top:8px; }
  .pd-hero-title { font-size:1.1rem; }
}
</style>
    {{-- Hero Breadcrumb --}}
    <div class="pd-hero">
        <div class="container">
            <nav class="pd-hero-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('home') }}">{{ translate('Home') }}</a>
                @if($detailedProduct->category)
                    <span class="sep">/</span>
                    <a href="{{ route('products.category', $detailedProduct->category->slug) }}">{{ $detailedProduct->category->getTranslation('name') }}</a>
                @endif
                <span class="sep">/</span>
                <span>{{ Str::limit($detailedProduct->getTranslation('name'), 60) }}</span>
            </nav>
            <h2 class="pd-hero-title">{{ $detailedProduct->getTranslation('name') }}</h2>
        </div>
    </div>

    <section class="mb-4">
        <div class="container">
            <div class="bg-white shadow-sm rounded p-3">
                <div class="row">
                    <div class="col-xl-5 col-lg-6 mb-4">
                        <div class="sticky-top z-3 row gutters-10">
                            @php
                                $photos = explode(',', $detailedProduct->photos);
                            @endphp
                            <div class="col order-1 order-md-2">
                                <div class="aiz-carousel product-gallery" data-nav-for='.product-gallery-thumb'
                                    data-fade='true' data-auto-height='true'>
                                    @foreach ($photos as $key => $photo)
                                        <div class="carousel-box img-zoom rounded">
                                            @if($key === 0)
                                            <img class="img-fluid"
                                                src="{{ uploaded_asset($photo) }}"
                                                fetchpriority="high"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            @else
                                            <img class="img-fluid lazyload"
                                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                data-src="{{ uploaded_asset($photo) }}"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            @endif
                                        </div>
                                    @endforeach
                                    @foreach ($detailedProduct->stocks as $key => $stock)
                                        @if ($stock->image != null)
                                            <div class="carousel-box img-zoom rounded">
                                                <img class="img-fluid lazyload"
                                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                    data-src="{{ uploaded_asset($stock->image) }}"
                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12 col-md-auto w-md-80px order-2 order-md-1 mt-3 mt-md-0">
                                <div class="aiz-carousel product-gallery-thumb" data-items='5'
                                    data-nav-for='.product-gallery' data-vertical='true' data-vertical-sm='false'
                                    data-focus-select='true' data-arrows='true'>
                                    @foreach ($photos as $key => $photo)
                                        <div class="carousel-box c-pointer border p-1 rounded">
                                            <img class="lazyload mw-100 size-50px mx-auto"
                                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                data-src="{{ uploaded_asset($photo) }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        </div>
                                    @endforeach
                                    @foreach ($detailedProduct->stocks as $key => $stock)
                                        @if ($stock->image != null)
                                            <div class="carousel-box c-pointer border p-1 rounded"
                                                data-variation="{{ $stock->variant }}">
                                                <img class="lazyload mw-100 size-50px mx-auto"
                                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                    data-src="{{ uploaded_asset($stock->image) }}"
                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-7 col-lg-6">
                        <div class="text-left">
                            {{-- Title --}}
                            <h1 class="pd-title">{{ $detailedProduct->getTranslation('name') }}</h1>

                            {{-- SKU below title --}}
                            @php
                                $displaySku = $detailedProduct->sku ?: optional($detailedProduct->stocks->first())->sku;
                            @endphp
                            @if($displaySku)
                                <div class="mb-2" style="font-size:13px; color:#666;">
                                    <i class="las la-barcode mr-1"></i><span class="font-weight-600 text-dark">{{ translate('SKU') }}:</span> {{ $displaySku }}
                                </div>
                            @endif

                            {{-- Stock badge + Brand --}}
                            @php
                                $total = 0;
                                $total += $detailedProduct->reviews->count();
                                $totalQtyBadge = 0;
                                foreach ($detailedProduct->stocks as $s) { $totalQtyBadge += $s->qty; }
                            @endphp
                            <div class="pd-sku-row">
                                @if($totalQtyBadge > 0)
                                    <span class="pd-stock-in"><i class="las la-check-circle mr-1"></i>{{ translate('In Stock') }}</span>
                                @else
                                    <span class="pd-stock-out"><i class="las la-times-circle mr-1"></i>{{ translate('Out of Stock') }}</span>
                                @endif
                                @if($detailedProduct->brand)
                                    <a href="{{ route('products.brand', $detailedProduct->brand->slug) }}" class="pd-sku-chip text-decoration-none">
                                        <b>{{ $detailedProduct->brand->getTranslation('name') }}</b>
                                    </a>
                                @endif
                            </div>

                            {{-- Ratings row --}}
                            <div class="d-flex align-items-center flex-wrap mb-2" style="gap:10px;">
                                <span class="rating">{{ renderStarRating($detailedProduct->rating) }}</span>
                                <span class="text-muted" style="font-size:13px;">({{ $total }} {{ translate('reviews') }})</span>
                                @if ($detailedProduct->est_shipping_days)
                                    <span class="text-muted" style="font-size:13px;">
                                        <i class="las la-truck mr-1 text-warning"></i>
                                        {{ translate('Ships in') }} {{ $detailedProduct->est_shipping_days }} {{ translate('days') }}
                                    </span>
                                @endif
                                {{-- Sold by --}}
                                <span class="text-muted" style="font-size:13px;">
                                    {{ translate('Sold by') }}:
                                    @if ($detailedProduct->added_by == 'seller' && get_setting('vendor_system_activation') == 1)
                                        <a href="{{ route('shop.visit', $detailedProduct->user->shop->slug) }}" style="color:#ff6b00; font-weight:600;">{{ $detailedProduct->user->shop->name }}</a>
                                    @else
                                        <b>{{ translate('BHS Supplies') }}</b>
                                    @endif
                                </span>
                                @if (get_setting('conversation_system') == 1)
                                    <button class="btn btn-xs btn-soft-primary py-1 px-2" style="font-size:12px;" onclick="show_chat_modal()">
                                        <i class="las la-comment mr-1"></i>{{ translate('Message') }}
                                    </button>
                                @endif
                            </div>

                            {{-- Short description --}}
                            @if($detailedProduct->getTranslation('short_description'))
                                <div class="mb-3 aiz-editor-data pd-short-description">
                                    {!! $detailedProduct->getTranslation('short_description') !!}
                                </div>
                            @endif

                            <hr class="my-3">

                            {{-- Price Box --}}
                            <div class="pd-price-box">
                                @if ($detailedProduct->wholesale_product)
                                    <div class="mb-1" style="font-size:12px; font-weight:700; text-transform:uppercase; color:#888; letter-spacing:.5px;">{{ translate('Wholesale Pricing') }}</div>
                                    <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>{{ translate('Min Qty') }}</th>
                                                <th>{{ translate('Max Qty') }}</th>
                                                <th>{{ translate('Unit Price') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($detailedProduct->stocks->first()->wholesalePrices as $wholesalePrice)
                                                <tr>
                                                    <td>{{ $wholesalePrice->min_qty }}</td>
                                                    <td>{{ $wholesalePrice->max_qty }}</td>
                                                    <td class="fw-600 text-danger">{{ single_price($wholesalePrice->price) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="mb-1" style="font-size:12px; font-weight:700; text-transform:uppercase; color:#888; letter-spacing:.5px;">{{ translate('Price') }}</div>
                                    <div class="d-flex align-items-baseline flex-wrap" style="gap:4px;">
                                        @if (home_price($detailedProduct) != home_discounted_price($detailedProduct))
                                            <span class="price-original">{{ home_price($detailedProduct) }}</span>
                                        @endif
                                        <span class="price-final" id="chosen_price_display">{{ home_discounted_price($detailedProduct) }}</span>
                                        @if ($detailedProduct->unit != null)
                                            <span class="price-unit">/{{ $detailedProduct->getTranslation('unit') }}</span>
                                        @endif
                                        @if (home_price($detailedProduct) != home_discounted_price($detailedProduct))
                                            @php
                                                preg_match('/[\d,.]+/', home_price($detailedProduct), $origMatch);
                                                preg_match('/[\d,.]+/', home_discounted_price($detailedProduct), $discMatch);
                                                $origNum = isset($origMatch[0]) ? (float)str_replace(',','',$origMatch[0]) : 0;
                                                $discNum = isset($discMatch[0]) ? (float)str_replace(',','',$discMatch[0]) : 0;
                                                $savePct = $origNum > 0 ? round((($origNum - $discNum) / $origNum) * 100) : 0;
                                            @endphp
                                            @if($savePct > 0)
                                                <span class="price-save-badge">{{ translate('SAVE') }} {{ $savePct }}%</span>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if (addon_is_activated('club_point') && $detailedProduct->earn_point > 0)
                                <div class="row no-gutters mt-4">
                                    <div class="col-sm-2">
                                        <div class="opacity-50 my-2">{{ translate('Club Point') }}:</div>
                                    </div>
                                    <div class="col-sm-10">
                                        <div
                                            class="d-inline-block rounded px-2 bg-soft-primary border-soft-primary border">
                                            <span class="strong-700">{{ $detailedProduct->earn_point }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <hr>

                            <form id="option-choice-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $detailedProduct->id }}">

                                @if ($detailedProduct->choice_options != null)
                                    @foreach (json_decode($detailedProduct->choice_options) as $key => $choice)
                                        <div class="row no-gutters">
                                            <div class="col-sm-2">
                                                <div class="opacity-50 my-2">
                                                    {{ \App\Models\Attribute::find($choice->attribute_id)->getTranslation('name') }}:
                                                </div>
                                            </div>
                                            <div class="col-sm-10">
                                                <div class="aiz-radio-inline">
                                                    @foreach ($choice->values as $key => $value)
                                                        <label class="aiz-megabox pl-0 mr-2">
                                                            <input type="radio"
                                                                name="attribute_id_{{ $choice->attribute_id }}"
                                                                value="{{ $value }}"
                                                                @if ($key == 0) checked @endif>
                                                            <span
                                                                class="aiz-megabox-elem rounded d-flex align-items-center justify-content-center py-2 px-3 mb-2">
                                                                {{ $value }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if (count(json_decode($detailedProduct->colors)) > 0)
                                    <div class="row no-gutters">
                                        <div class="col-sm-2">
                                            <div class="opacity-50 my-2">{{ translate('Color') }}:</div>
                                        </div>
                                        <div class="col-sm-10">
                                            <div class="aiz-radio-inline">
                                                @foreach (json_decode($detailedProduct->colors) as $key => $color)
                                                    <label class="aiz-megabox pl-0 mr-2" data-toggle="tooltip"
                                                        data-title="{{ \App\Models\Color::where('code', $color)->first()->name }}">
                                                        <input type="radio" name="color"
                                                            value="{{ \App\Models\Color::where('code', $color)->first()->name }}"
                                                            @if ($key == 0) checked @endif>
                                                        <span
                                                            class="aiz-megabox-elem rounded d-flex align-items-center justify-content-center p-1 mb-2">
                                                            <span class="size-30px d-inline-block rounded"
                                                                style="background: {{ $color }};"></span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                @endif

                                <!-- Quantity + Add to cart -->
                                <div class="row no-gutters">
                                    <div class="col-sm-2">
                                        <div class="opacity-50 my-2">{{ translate('Quantity') }}:</div>
                                    </div>
                                    <div class="col-sm-10">
                                        <div class="product-quantity d-flex align-items-center">
                                            <div class="row no-gutters align-items-center aiz-plus-minus mr-3"
                                                style="width: 130px;">
                                                <button class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                    type="button" data-type="minus" data-field="quantity"
                                                    disabled="">
                                                    <i class="las la-minus"></i>
                                                </button>
                                                <input type="number" name="quantity"
                                                    class="col border-0 text-center flex-grow-1 fs-16 input-number"
                                                    placeholder="1" value="{{ $detailedProduct->min_qty }}"
                                                    min="{{ $detailedProduct->min_qty }}" max="10"
                                                    lang="en">
                                                <button class="btn  col-auto btn-icon btn-sm btn-circle btn-light"
                                                    type="button" data-type="plus" data-field="quantity">
                                                    <i class="las la-plus"></i>
                                                </button>
                                            </div>
                                            @php
                                                $qty = 0;
                                                foreach ($detailedProduct->stocks as $key => $stock) {
                                                    $qty += $stock->qty;
                                                }
                                            @endphp
                                            <div class="avialable-amount opacity-60">
                                                @if ($detailedProduct->stock_visibility_state == 'quantity')
                                                    (<span id="available-quantity">{{ $qty }}</span>
                                                    {{ translate('available') }})
                                                @elseif($detailedProduct->stock_visibility_state == 'text' && $qty >= 1)
                                                    (<span id="available-quantity">{{ translate('In Stock') }}</span>)
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row no-gutters pb-3 d-none" id="chosen_price_div">
                                    <div class="col-sm-2">
                                        <div class="opacity-50 my-2">{{ translate('Total Price') }}:</div>
                                    </div>
                                    <div class="col-sm-10">
                                        <div class="product-price">
                                            <strong id="chosen_price" class="h4 fw-600 text-primary">

                                            </strong>
                                        </div>
                                    </div>
                                </div>

                            </form>

                            <div class="mt-3">
                                @if ($detailedProduct->external_link != null)
                                    <a type="button" class="btn-pd-buy buy-now d-inline-flex align-items-center"
                                        href="{{ $detailedProduct->external_link }}">
                                        <i class="la la-share mr-2"></i> {{ translate($detailedProduct->external_link_btn) }}
                                    </a>
                                @else
                                    <div class="d-flex flex-wrap" style="gap:10px;">
                                        <button type="button" class="btn-pd-cart add-to-cart d-inline-flex align-items-center"
                                            onclick="addToCart()">
                                            <i class="las la-shopping-bag mr-2"></i>{{ translate('Add to Cart') }}
                                        </button>
                                        <button type="button" class="btn-pd-buy buy-now d-inline-flex align-items-center" onclick="buyNow()">
                                            <i class="la la-bolt mr-2"></i>{{ translate('Buy Now') }}
                                        </button>
                                    </div>
                                @endif
                                <button type="button" class="btn btn-secondary out-of-stock fw-600 d-none mt-2" disabled style="width:100%;">
                                    <i class="la la-cart-arrow-down mr-2"></i>{{ translate('Out of Stock') }}
                                </button>
                            </div>

                            {{-- Trust Strip --}}
                            <div class="pd-trust-strip">
                                <div class="pd-trust-item">
                                    <i class="las la-shield-alt"></i>
                                    <b>{{ translate('Secure Payment') }}</b>
                                </div>
                                <div class="pd-trust-item">
                                    <i class="las la-truck"></i>
                                    <b>{{ translate('Fast Delivery') }}</b>
                                </div>
                                <div class="pd-trust-item">
                                    <i class="las la-undo-alt"></i>
                                    <b>{{ translate('Easy Returns') }}</b>
                                </div>
                                <div class="pd-trust-item">
                                    <i class="las la-headset"></i>
                                    <b>{{ translate('24/7 Support') }}</b>
                                </div>
                            </div>



                            <div class="d-table width-100 mt-3">
                                <div class="d-table-cell">
                                    <!-- Add to wishlist button -->
                                    <button type="button" class="btn pl-0 btn-link fw-600"
                                        onclick="addToWishList({{ $detailedProduct->id }})">
                                        {{ translate('Add to wishlist') }}
                                    </button>
                                    <!-- Add to compare button -->
                                    <button type="button" class="btn btn-link btn-icon-left fw-600"
                                        onclick="addToCompare({{ $detailedProduct->id }})">
                                        {{ translate('Add to compare') }}
                                    </button>
                                    @if (Auth::check() && addon_is_activated('affiliate_system') && (\App\Models\AffiliateOption::where('type', 'product_sharing')->first()->status || \App\Models\AffiliateOption::where('type', 'category_wise_affiliate')->first()->status) && Auth::user()->affiliate_user != null && Auth::user()->affiliate_user->status)
                                        @php
                                            if (Auth::check()) {
                                                if (Auth::user()->referral_code == null) {
                                                    Auth::user()->referral_code = substr(Auth::user()->id . Str::random(10), 0, 10);
                                                    Auth::user()->save();
                                                }
                                                $referral_code = Auth::user()->referral_code;
                                                $referral_code_url = URL::to('/product') . '/' . $detailedProduct->slug . "?product_referral_code=$referral_code";
                                            }
                                        @endphp
                                        <div>
                                            <button type=button id="ref-cpurl-btn" class="btn btn-sm btn-secondary"
                                                data-attrcpy="{{ translate('Copied') }}"
                                                onclick="CopyToClipboard(this)"
                                                data-url="{{ $referral_code_url }}">{{ translate('Copy the Promote Link') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </div>


                            @php
                                $refund_sticker = get_setting('refund_sticker');
                            @endphp
                            @if (addon_is_activated('refund_request'))
                                <div class="row no-gutters mt-3">
                                    <div class="col-2">
                                        <div class="opacity-50 mt-2">{{ translate('Refund') }}:</div>
                                    </div>
                                    <div class="col-10">
                                        <a href="{{ route('returnpolicy') }}" target="_blank">
                                            @if ($refund_sticker != null)
                                                <img src="{{ uploaded_asset($refund_sticker) }}" height="36">
                                            @else
                                                <img src="{{ static_asset('assets/img/refund-sticker.jpg') }}"
                                                    height="36">
                                            @endif
                                        </a>
                                        <a href="{{ route('returnpolicy') }}" class="ml-2"
                                            target="_blank">{{ translate('View Policy') }}</a>
                                    </div>
                                </div>
                            @endif
                            <div class="row no-gutters mt-4">
                                <div class="col-sm-2">
                                    <div class="opacity-50 my-2">{{ translate('Share') }}:</div>
                                </div>
                                <div class="col-sm-10">
                                    <div class="aiz-share"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="container">
            <div class="row gutters-10">
                <div class="col-xl-3 order-1 order-xl-0">
                    @if ($detailedProduct->added_by == 'seller' && $detailedProduct->user->shop != null)
                        <div class="bg-white shadow-sm mb-3">
                            <div class="position-relative p-3 text-left">
                                @if ($detailedProduct->user->shop->verification_status)
                                    <div class="absolute-top-right p-2 bg-white z-1">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"
                                            viewBox="0 0 287.5 442.2" width="22" height="34">
                                            <polygon style="fill:#F8B517;"
                                                points="223.4,442.2 143.8,376.7 64.1,442.2 64.1,215.3 223.4,215.3 " />
                                            <circle style="fill:#FBD303;" cx="143.8" cy="143.8"
                                                r="143.8" />
                                            <circle style="fill:#F8B517;" cx="143.8" cy="143.8"
                                                r="93.6" />
                                            <polygon style="fill:#FCFCFD;"
                                                points="143.8,55.9 163.4,116.6 227.5,116.6 175.6,154.3 195.6,215.3 143.8,177.7 91.9,215.3 111.9,154.3
                                            60,116.6 124.1,116.6 " />
                                        </svg>
                                    </div>
                                @endif
                                <div class="opacity-50 fs-12 border-bottom">{{ translate('Sold by') }}</div>
                                <a href="{{ route('shop.visit', $detailedProduct->user->shop->slug) }}"
                                    class="text-reset d-block fw-600">
                                    {{ $detailedProduct->user->shop->name }}
                                    @if ($detailedProduct->user->shop->verification_status == 1)
                                        <span class="ml-2"><i class="fa fa-check-circle"
                                                style="color:green"></i></span>
                                    @else
                                        <span class="ml-2"><i class="fa fa-times-circle" style="color:red"></i></span>
                                    @endif
                                </a>
                                <div class="location opacity-70">{{ $detailedProduct->user->shop->address }}</div>
                                <div class="text-center border rounded p-2 mt-3">
                                    <div class="rating">
                                        @if ($total > 0)
                                            {{ renderStarRating($detailedProduct->user->shop->rating) }}
                                        @else
                                            {{ renderStarRating(0) }}
                                        @endif
                                    </div>
                                    <div class="opacity-60 fs-12">({{ $total }}
                                        {{ translate('customer reviews') }})</div>
                                </div>
                            </div>
                            <div class="row no-gutters align-items-center border-top">
                                <div class="col">
                                    <a href="{{ route('shop.visit', $detailedProduct->user->shop->slug) }}"
                                        class="d-block btn btn-soft-primary rounded-0">{{ translate('Visit Store') }}</a>
                                </div>
                                <div class="col">
                                    <ul class="social list-inline mb-0">
                                        <li class="list-inline-item mr-0">
                                            <a href="{{ $detailedProduct->user->shop->facebook }}" class="facebook"
                                                target="_blank">
                                                <i class="lab la-facebook-f opacity-60"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item mr-0">
                                            <a href="{{ $detailedProduct->user->shop->google }}" class="google"
                                                target="_blank">
                                                <i class="lab la-google opacity-60"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item mr-0">
                                            <a href="{{ $detailedProduct->user->shop->twitter }}" class="twitter"
                                                target="_blank">
                                                <i class="lab la-twitter opacity-60"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item">
                                            <a href="{{ $detailedProduct->user->shop->youtube }}" class="youtube"
                                                target="_blank">
                                                <i class="lab la-youtube opacity-60"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="bhs-section-card mb-3">
                        <div class="bhs-section-header" style="padding:12px 16px;">
                            <h3 class="bhs-section-title" style="font-size:14px;">
                                <i class="las la-fire mr-1" style="color:#e8241a;"></i>{{ translate('Top Selling Products') }}
                            </h3>
                        </div>
                        <div class="p-3">
                            @foreach (filter_products(\App\Models\Product::where('user_id', $detailedProduct->user_id)->orderBy('num_of_sale', 'desc'))->limit(5)->get()
    as $key => $top_product)
                                <div class="d-flex gap-2 py-2 {{ $key > 0 ? 'border-top' : '' }}" style="gap:10px;">
                                    <a href="{{ route('product', $top_product->slug) }}" class="flex-shrink-0" style="width:70px;height:70px;display:block;border-radius:6px;overflow:hidden;background:#f9f9fb;">
                                        <img class="lazyload"
                                            src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                            data-src="{{ uploaded_asset($top_product->thumbnail_img) }}"
                                            alt="{{ $top_product->getTranslation('name') }}"
                                            style="width:70px;height:70px;object-fit:contain;padding:4px;"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    </a>
                                    <div class="flex-grow-1 text-left" style="min-width:0;">
                                        <a href="{{ route('product', $top_product->slug) }}" class="bhs-product-name-link d-block text-truncate-2" style="font-size:12px;font-weight:600;color:#1a1a2e;line-height:1.4;text-decoration:none;">{{ $top_product->getTranslation('name') }}</a>
                                        @php $topSku = $top_product->sku ?: optional($top_product->stocks->first())->sku; @endphp
                                        @if($topSku)
                                            <div class="bhs-product-sku">SKU: {{ $topSku }}</div>
                                        @endif
                                        <div class="bhs-product-rating" style="font-size:11px;">{{ renderStarRating($top_product->rating) }}</div>
                                        <span class="bhs-price-final" style="font-size:14px;">{{ home_discounted_base_price($top_product) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-9 order-0 order-xl-1">
                    <div class="bg-white mb-3 shadow-sm rounded">
                        <div class="nav border-bottom aiz-nav-tabs">
                            <a href="#tab_default_1" data-toggle="tab"
                                class="p-3 fs-16 fw-600 text-reset active show">{{ translate('Description') }}</a>
                            @if ($detailedProduct->video_link != null)
                                <a href="#tab_default_2" data-toggle="tab"
                                    class="p-3 fs-16 fw-600 text-reset">{{ translate('Video') }}</a>
                            @endif
                            @if ($detailedProduct->pdf != null)
                                <a href="#tab_default_3" data-toggle="tab"
                                    class="p-3 fs-16 fw-600 text-reset">{{ translate('Downloads') }}</a>
                            @endif
                            <a href="#tab_default_4" data-toggle="tab"
                                class="p-3 fs-16 fw-600 text-reset">{{ translate('Reviews') }}</a>
                        </div>

                        <div class="tab-content pt-0">
                            <div class="tab-pane fade active show" id="tab_default_1">
                                <div class="p-4 p-lg-5">
                                    <div class="pd-description-shell">
                                        <div class="pd-description-head">
                                            <span class="pd-description-kicker">{{ translate('Product Overview') }}</span>
                                            <span class="pd-description-note">{{ translate('Technical details, features and specifications') }}</span>
                                        </div>
                                        <div class="mw-100 overflow-hidden text-left aiz-editor-data pd-description-wrap" id="product-description-content">
                                            <?php echo $detailedProduct->getTranslation('description'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab_default_2">
                                <div class="p-4">
                                    <div class="embed-responsive embed-responsive-16by9">
                                        @if ($detailedProduct->video_provider == 'youtube' && isset(explode('=', $detailedProduct->video_link)[1]))
                                            <iframe class="embed-responsive-item"
                                                src="https://www.youtube.com/embed/{{ get_url_params($detailedProduct->video_link, 'v') }}"></iframe>
                                        @elseif ($detailedProduct->video_provider == 'dailymotion' && isset(explode('video/', $detailedProduct->video_link)[1]))
                                            <iframe class="embed-responsive-item"
                                                src="https://www.dailymotion.com/embed/video/{{ explode('video/', $detailedProduct->video_link)[1] }}"></iframe>
                                        @elseif ($detailedProduct->video_provider == 'vimeo' && isset(explode('vimeo.com/', $detailedProduct->video_link)[1]))
                                            <iframe
                                                src="https://player.vimeo.com/video/{{ explode('vimeo.com/', $detailedProduct->video_link)[1] }}"
                                                width="500" height="281" frameborder="0" webkitallowfullscreen
                                                mozallowfullscreen allowfullscreen></iframe>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab_default_3">
                                <div class="p-4 text-center ">
                                    <a href="{{ uploaded_asset($detailedProduct->pdf) }}"
                                        class="btn btn-primary">{{ translate('Download') }}</a>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab_default_4">
                                <div class="p-4">
                                    <ul class="list-group list-group-flush">
                                        @foreach ($detailedProduct->reviews as $key => $review)
                                            @if ($review->user != null)
                                                <li class="media list-group-item d-flex">
                                                    <span class="avatar avatar-md mr-3">
                                                        <img class="lazyload"
                                                            src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                                                            @if ($review->user->avatar_original != null) data-src="{{ uploaded_asset($review->user->avatar_original) }}"
                                                        @else
                                                            data-src="{{ static_asset('assets/img/placeholder.jpg') }}" @endif>
                                                    </span>
                                                    <div class="media-body text-left">
                                                        <div class="d-flex justify-content-between">
                                                            <h3 class="fs-15 fw-600 mb-0">{{ $review->user->name }}
                                                            </h3>
                                                            <span class="rating rating-sm">
                                                                @for ($i = 0; $i < $review->rating; $i++)
                                                                    <i class="las la-star active"></i>
                                                                @endfor
                                                                @for ($i = 0; $i < 5 - $review->rating; $i++)
                                                                    <i class="las la-star"></i>
                                                                @endfor
                                                            </span>
                                                        </div>
                                                        <div class="opacity-60 mb-2">
                                                            {{ date('d-m-Y', strtotime($review->created_at)) }}</div>
                                                        <p class="comment-text">
                                                            {{ $review->comment }}
                                                        </p>
                                                    </div>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>

                                    @if (count($detailedProduct->reviews) <= 0)
                                        <div class="text-center fs-18 opacity-70">
                                            {{ translate('There have been no reviews for this product yet.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bhs-section-card">
                        <div class="bhs-section-header">
                            <h3 class="bhs-section-title">{{ translate('Related Products') }}</h3>
                        </div>
                        <div class="bhs-home-carousel aiz-carousel arrow-inactive-none px-4 pb-3"
                            data-items="4" data-xl-items="4"
                            data-lg-items="3" data-md-items="3" data-sm-items="2" data-xs-items="2"
                            data-arrows="true" data-infinite="false">
                            @foreach (filter_products(\App\Models\Product::where(function ($query) use ($detailedProduct) {
                                $query->where('category_id', $detailedProduct->category_id)
                                    ->orWhereHas('categories', function ($categoryQuery) use ($detailedProduct) {
                                        $categoryQuery->whereIn('categories.id', $detailedProduct->categories->pluck('id')->push($detailedProduct->category_id)->filter()->unique()->all());
                                    });
                            })->where('id', '!=', $detailedProduct->id))->limit(12)->get()
    as $key => $related_product)
                                <div class="carousel-box px-2 py-2">
                                    @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1', ['product' => $related_product])
                                </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- Product Query --}}
                    @if(get_setting('product_query_activation') == 1)
                        <div class="bg-white rounded shadow-sm mt-3">
                            <div class="border-bottom p-3">
                                <h3 class="fs-18 fw-600 mb-0">
                                    <span>{{ translate(' Product Queries ') }} ({{ $total_query }})</span>
                                </h3>
                            </div>
                            @guest
                                <p class="fs-14 fw-400 mb-0 ml-3 mt-2"><a
                                        href="{{ route('user.login') }}">{{ translate('Login') }}</a> or <a class="mr-1"
                                        href="{{ route('user.registration') }}">{{ translate('Register ') }}</a>{{ translate(' to submit your questions to seller') }}
                                </p>
                            @endguest
                            @auth
                                <div class="query form p-3">
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <form action="{{ route('product-queries.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="product" value="{{ $detailedProduct->id }}">
                                        <div class="form-group">
                                            <textarea class="form-control" rows="3" cols="40" name="question"
                                                placeholder="{{ translate('Write your question here...') }}" style="resize: none;"></textarea>
                                            
                                        </div>
                                        <button type="submit" class="btn btn-primary">{{ translate('Submit') }}</button>
                                    </form>
                                </div>
                                @php
                                    $own_product_queries = Auth::user()->product_queries->where('product_id',$detailedProduct->id);
                                @endphp
                                @if ($own_product_queries->count() > 0)
                                
                                    <div class="question-area my-4   mb-0 ml-3">

                                        <div class="border-bottom py-3">
                                            <h3 class="fs-18 fw-600 mb-0">
                                                <span class="mr-4">{{ translate('My Questions') }}</span>
                                            </h3>
                                        </div>
                                        @foreach ($own_product_queries as $product_query)
                                            <div class="produc-queries border-bottom">
                                                <div class="query d-flex my-4">
                                                    <span class="mt-1"><svg xmlns="http://www.w3.org/2000/svg" width="24.994"
                                                            height="24.981" viewBox="0 0 24.994 24.981">
                                                            <g id="Group_23909" data-name="Group 23909"
                                                                transform="translate(18392.496 11044.037)">
                                                                <path id="Subtraction_90" data-name="Subtraction 90"
                                                                    d="M1830.569-117.742a.4.4,0,0,1-.158-.035.423.423,0,0,1-.252-.446c0-.84,0-1.692,0-2.516v-2.2a5.481,5.481,0,0,1-2.391-.745,5.331,5.331,0,0,1-2.749-4.711c-.034-2.365-.018-4.769,0-7.094l0-.649a5.539,5.539,0,0,1,4.694-5.513,5.842,5.842,0,0,1,.921-.065q3.865,0,7.73,0l5.035,0a5.539,5.539,0,0,1,5.591,5.57c.01,2.577.01,5.166,0,7.693a5.54,5.54,0,0,1-4.842,5.506,6.5,6.5,0,0,1-.823.046l-3.225,0c-1.454,0-2.753,0-3.97,0a.555.555,0,0,0-.435.182c-1.205,1.214-2.435,2.445-3.623,3.636l-.062.062-1.005,1.007-.037.037-.069.069A.464.464,0,0,1,1830.569-117.742Zm7.37-11.235h0l1.914,1.521.817-.754-1.621-1.273a3.517,3.517,0,0,0,1.172-1.487,5.633,5.633,0,0,0,.418-2.267v-.58a5.629,5.629,0,0,0-.448-2.323,3.443,3.443,0,0,0-1.282-1.525,3.538,3.538,0,0,0-1.93-.53,3.473,3.473,0,0,0-1.905.534,3.482,3.482,0,0,0-1.288,1.537,5.582,5.582,0,0,0-.454,2.314v.654a5.405,5.405,0,0,0,.471,2.261,3.492,3.492,0,0,0,1.287,1.5,3.492,3.492,0,0,0,1.9.527,3.911,3.911,0,0,0,.947-.112Zm-.948-.9a2.122,2.122,0,0,1-1.812-.9,4.125,4.125,0,0,1-.652-2.457v-.667a4.008,4.008,0,0,1,.671-2.4,2.118,2.118,0,0,1,1.78-.863,2.138,2.138,0,0,1,1.824.869,4.145,4.145,0,0,1,.639,2.473v.673a4.07,4.07,0,0,1-.655,2.423A2.125,2.125,0,0,1,1836.991-129.881Z"
                                                                    transform="translate(-20217 -10901.814)" fill="#e62e04"
                                                                    stroke="rgba(0,0,0,0)" stroke-miterlimit="10"
                                                                    stroke-width="1" />
                                                            </g>
                                                        </svg></span>

                                                    <div class="ml-3">
                                                        <div class="fs-14">{{ strip_tags($product_query->question) }}</div>
                                                        <span class="text-secondary">{{ $product_query->user->name }} </span>
                                                    </div>
                                                </div>
                                                <div class="answer d-flex my-4">
                                                    <span class="mt-1"> <svg xmlns="http://www.w3.org/2000/svg" width="24.99"
                                                            height="24.98" viewBox="0 0 24.99 24.98">
                                                            <g id="Group_23908" data-name="Group 23908"
                                                                transform="translate(17952.169 11072.5)">
                                                                <path id="Subtraction_89" data-name="Subtraction 89"
                                                                    d="M2162.9-146.2a.4.4,0,0,1-.159-.035.423.423,0,0,1-.251-.446q0-.979,0-1.958V-151.4a5.478,5.478,0,0,1-2.39-.744,5.335,5.335,0,0,1-2.75-4.712c-.034-2.355-.018-4.75,0-7.065l0-.678a5.54,5.54,0,0,1,4.7-5.513,5.639,5.639,0,0,1,.92-.064c2.527,0,5.029,0,7.437,0l5.329,0a5.538,5.538,0,0,1,5.591,5.57c.01,2.708.01,5.224,0,7.692a5.539,5.539,0,0,1-4.843,5.506,6,6,0,0,1-.822.046l-3.234,0c-1.358,0-2.691,0-3.96,0a.556.556,0,0,0-.436.182c-1.173,1.182-2.357,2.367-3.5,3.514l-1.189,1.192-.047.048-.058.059A.462.462,0,0,1,2162.9-146.2Zm5.115-12.835h3.559l.812,2.223h1.149l-3.25-8.494h-.98l-3.244,8.494h1.155l.8-2.222Zm3.226-.915h-2.888l1.441-3.974,1.447,3.972Z"
                                                                    transform="translate(-20109 -10901.815)" fill="#f7941d"
                                                                    stroke="rgba(0,0,0,0)" stroke-miterlimit="10"
                                                                    stroke-width="1" />
                                                            </g>
                                                        </svg></span>

                                                    <div class="ml-3">
                                                        <div class="fs-14">
                                                            {{ strip_tags($product_query->reply ? $product_query->reply : translate('Seller did not respond yet')) }}
                                                        </div>
                                                        <span class=" text-secondary">
                                                            {{ $product_query->product->user->name }} </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                
                                @endif
                            @endauth

                            <div class="pagination-area my-4 mb-0 ml-3">
                                @include('frontend.partials.product_query_pagination')
                            </div>
                        </div>
                    @endif
                    {{-- End of Product Query --}}
                </div>
            </div>
        </div>
    </section>

@endsection

@section('modal')
    <div class="modal fade" id="chat_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
            <div class="modal-content position-relative">
                <div class="modal-header">
                    <h5 class="modal-title fw-600 h5">{{ translate('Any query about this product') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form class="" action="{{ route('conversations.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $detailedProduct->id }}">
                    <div class="modal-body gry-bg px-3 pt-3">
                        <div class="form-group">
                            <input type="text" class="form-control mb-3" name="title"
                                value="{{ $detailedProduct->name }}" placeholder="{{ translate('Product Name') }}"
                                required>
                        </div>
                        <div class="form-group">
                            <textarea class="form-control" rows="8" name="message" required
                                placeholder="{{ translate('Your Question') }}">{{ route('product', $detailedProduct->slug) }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary fw-600"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary fw-600">{{ translate('Send') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="login_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-zoom" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-600">{{ translate('Login') }}</h6>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="p-3">
                        <form class="form-default" role="form" action="{{ route('cart.login.submit') }}"
                            method="POST">
                            @csrf
                            <div class="form-group">
                                @if (addon_is_activated('otp_system'))
                                    <input type="text"
                                        class="form-control h-auto form-control-lg {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                        value="{{ old('email') }}" placeholder="{{ translate('Email Or Phone') }}"
                                        name="email" id="email">
                                @else
                                    <input type="email"
                                        class="form-control h-auto form-control-lg {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                        value="{{ old('email') }}" placeholder="{{ translate('Email') }}"
                                        name="email">
                                @endif
                                @if (addon_is_activated('otp_system'))
                                    <span class="opacity-60">{{ translate('Use country code before number') }}</span>
                                @endif
                            </div>

                            <div class="form-group">
                                <input type="password" name="password" class="form-control h-auto form-control-lg"
                                    placeholder="{{ translate('Password') }}">
                            </div>

                            <div class="row mb-2">
                                <div class="col-6">
                                    <label class="aiz-checkbox">
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <span class=opacity-60>{{ translate('Remember Me') }}</span>
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                                <div class="col-6 text-right">
                                    <a href="{{ route('password.request') }}"
                                        class="text-reset opacity-60 fs-14">{{ translate('Forgot password?') }}</a>
                                </div>
                            </div>

                            <div class="mb-5">
                                <button type="submit"
                                    class="btn btn-primary btn-block fw-600">{{ translate('Login') }}</button>
                            </div>
                        </form>

                        <div class="text-center mb-3">
                            <p class="text-muted mb-0">{{ translate('Dont have an account?') }}</p>
                            <a href="{{ route('user.registration') }}">{{ translate('Register Now') }}</a>
                        </div>
                        @if (get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1)
                            <div class="separator mb-3">
                                <span class="bg-white px-3 opacity-60">{{ translate('Or Login With') }}</span>
                            </div>
                            <ul class="list-inline social colored text-center mb-5">
                                @if (get_setting('facebook_login') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'facebook']) }}"
                                            class="facebook">
                                            <i class="lab la-facebook-f"></i>
                                        </a>
                                    </li>
                                @endif
                                @if (get_setting('google_login') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'google']) }}"
                                            class="google">
                                            <i class="lab la-google"></i>
                                        </a>
                                    </li>
                                @endif
                                @if (get_setting('twitter_login') == 1)
                                    <li class="list-inline-item">
                                        <a href="{{ route('social.login', ['provider' => 'twitter']) }}"
                                            class="twitter">
                                            <i class="lab la-twitter"></i>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            getVariantPrice();
            enhanceProductDescriptionBlocks();
        });

        function enhanceProductDescriptionBlocks() {
            var blocks = document.querySelectorAll('.pd-description-wrap, .pd-short-description');
            if (!blocks.length) {
                return;
            }

            Array.from(blocks).forEach(function(block) {
                removeEmptyDescriptionNodes(block);
                normalizeDescriptionMedia(block);
                normalizeDescriptionTables(block);
                normalizeDescriptionLists(block);
            });
        }

        function removeEmptyDescriptionNodes(block) {
            Array.from(block.querySelectorAll('p, div, span')).forEach(function(node) {
                if (node.closest('table, td, th, li, blockquote')) {
                    return;
                }

                var hasMedia = node.querySelector('img, iframe, video, table, ul, ol, br');
                var text = (node.textContent || '').replace(/\u00a0/g, ' ').trim();
                if (!hasMedia && !text) {
                    node.remove();
                }
            });
        }

        function normalizeDescriptionMedia(block) {
            Array.from(block.querySelectorAll('img')).forEach(function(img) {
                img.removeAttribute('width');
                img.removeAttribute('height');
                img.setAttribute('loading', 'lazy');
                img.classList.add('img-fluid');
            });
        }

        function normalizeDescriptionTables(block) {
            Array.from(block.querySelectorAll('table')).forEach(function(table) {
                table.classList.add('bhs-spec-table');
                table.removeAttribute('width');

                Array.from(table.querySelectorAll('[width]')).forEach(function(node) {
                    node.removeAttribute('width');
                });

                var rows = Array.from(table.rows || []).filter(function(row) {
                    return row.cells && row.cells.length;
                });

                if (rows.length >= 2) {
                    var twoColumnTable = rows.every(function(row) {
                        return row.cells.length === 2;
                    });

                    if (twoColumnTable) {
                        table.classList.add('bhs-spec-table--2col');
                    }
                }
            });
        }

        function normalizeDescriptionLists(block) {
            Array.from(block.querySelectorAll('ul')).forEach(function(list) {
                if (list.classList.contains('bhs-features-list')) {
                    return;
                }

                var items = Array.from(list.children).filter(function(item) {
                    return item.tagName === 'LI';
                });

                if (items.length >= 2) {
                    list.classList.add('bhs-features-list');
                }
            });
        }

        function CopyToClipboard(e) {
            var url = $(e).data('url');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(url).select();
            try {
                document.execCommand("copy");
                AIZ.plugins.notify('success', '{{ translate('Link copied to clipboard') }}');
            } catch (err) {
                AIZ.plugins.notify('danger', '{{ translate('Oops, unable to copy') }}');
            }
            $temp.remove();
            // if (document.selection) {
            //     var range = document.body.createTextRange();
            //     range.moveToElementText(document.getElementById(containerid));
            //     range.select().createTextRange();
            //     document.execCommand("Copy");

            // } else if (window.getSelection) {
            //     var range = document.createRange();
            //     document.getElementById(containerid).style.display = "block";
            //     range.selectNode(document.getElementById(containerid));
            //     window.getSelection().addRange(range);
            //     document.execCommand("Copy");
            //     document.getElementById(containerid).style.display = "none";

            // }
            // AIZ.plugins.notify('success', 'Copied');
        }

        function show_chat_modal() {
            @if (Auth::check())
                $('#chat_modal').modal('show');
            @else
                $('#login_modal').modal('show');
            @endif
        }

        // Pagination using ajax
        $(window).on('hashchange', function() {
            if (window.location.hash) {
                var page = window.location.hash.replace('#', '');
                if (page == Number.NaN || page <= 0) {
                    return false;
                } else {
                    getQuestions(page);
                }
            }
        });

        $(document).ready(function() {
            $(document).on('click', '.pagination a', function(e) {
                getQuestions($(this).attr('href').split('page=')[1]);
                e.preventDefault();
            });
        });

        function getQuestions(page) {
            $.ajax({
                url: '?page=' + page,
                dataType: 'json',
            }).done(function(data) {
                $('.pagination-area').html(data);
                location.hash = page;
            }).fail(function() {
                alert('Something went worng! Questions could not be loaded.');
            });
        }
        // Pagination end
    </script>
@endsection
