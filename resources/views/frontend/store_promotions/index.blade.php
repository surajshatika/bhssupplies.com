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

@php
    $topDefault = '
        <h2>Welcome to BHS Supplies Store</h2>
        <p>At <strong>BHS Supplies</strong>, we are proud to be your trusted destination for premium HVAC, plumbing,
        bathroom, electrical, and hardware supplies in Canada. Conveniently located in <strong>Mississauga, Ontario</strong>,
        we provide high-quality products at <strong>wholesale pricing</strong> for homeowners, contractors, builders,
        renovators, and businesses.</p>
        <p>Whether you are upgrading your bathroom, renovating your home, or sourcing professional HVAC and plumbing
        equipment, BHS Supplies offers reliable products, competitive prices, and exceptional customer service — all
        under one roof.</p>
        <h3>Why Choose BHS Supplies?</h3>
        <ul class="seo-check-list">
            <li>Wholesale &amp; distributor pricing</li>
            <li>Premium quality HVAC and plumbing supplies</li>
            <li>Modern bathroom vanities, LED mirrors &amp; shower doors</li>
            <li>Trusted by contractors and professionals</li>
            <li>Large inventory with fast availability</li>
            <li>Reliable customer support</li>
            <li>Convenient Mississauga showroom location</li>
            <li>Delivery options available across Canada</li>
        </ul>';
    $topContent = get_setting('store_page_top_content') ?: $topDefault;
@endphp

@if(trim(strip_tags($topContent)) !== '')
<section class="store-seo pt-4">
    <div class="container">
        <div class="bg-white rounded shadow-sm p-4 p-md-5 promo-content seo-content">
            {!! $topContent !!}
        </div>
    </div>
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

@php
    $siteName  = get_setting('website_name', 'BHS Supplies');
    $bottomDefault = '
        <h2>Our Featured Product Categories</h2>
        <div class="seo-cards">
            <div class="seo-card">
                <h3>Premium Bathroom Vanities</h3>
                <p>Upgrade your bathroom with stylish and modern vanity collections available in multiple sizes and
                finishes. Our vanities are designed for durability, functionality, and elegant aesthetics that fit both
                residential and commercial spaces.</p>
                <p class="seo-card-label">Features:</p>
                <ul class="seo-check-list">
                    <li>Soft-close drawers</li>
                    <li>Water-resistant materials</li>
                    <li>Modern &amp; luxury finishes</li>
                    <li>Easy installation</li>
                    <li>Affordable wholesale pricing</li>
                </ul>
            </div>
            <div class="seo-card">
                <h3>LED Anti-Fog Mirrors</h3>
                <p>Experience premium bathroom comfort with our LED anti-fog mirrors featuring smart touch controls,
                bright LED lighting, Bluetooth speakers, and modern designs.</p>
                <p class="seo-card-label">Perfect for:</p>
                <ul class="seo-check-list">
                    <li>Luxury bathrooms</li>
                    <li>Home renovations</li>
                    <li>Hotels &amp; commercial projects</li>
                    <li>Smart home upgrades</li>
                </ul>
            </div>
            <div class="seo-card">
                <h3>Premium Shower Doors</h3>
                <p>Our premium shower doors combine modern style with durable construction for long-lasting performance.
                Available in multiple sizes and configurations to suit any bathroom layout.</p>
                <p class="seo-card-label">Benefits:</p>
                <ul class="seo-check-list">
                    <li>Sleek frameless designs</li>
                    <li>Easy-to-clean glass</li>
                    <li>Durable hardware</li>
                    <li>Professional-quality appearance</li>
                    <li>Perfect for bathroom renovations</li>
                </ul>
            </div>
        </div>

        <h3>More Products Available</h3>
        <p>At BHS Supplies, we also offer a wide range of:</p>
        <div class="seo-tags">
            <span>HVAC supplies</span><span>Air conditioners &amp; furnaces</span><span>Plumbing supplies</span>
            <span>Water heaters</span><span>Electrical products</span><span>Industrial tools</span>
            <span>Knipex hand tools</span><span>Unilite LED work lights</span><span>Automotive supplies</span>
            <span>Contractor tools &amp; accessories</span>
        </div>

        <h3>Serving Contractors, Builders &amp; Homeowners</h3>
        <p>We proudly serve:</p>
        <ul class="seo-check-list seo-check-inline">
            <li>HVAC technicians</li><li>Plumbers</li><li>Contractors</li><li>Builders</li>
            <li>Renovation companies</li><li>Property managers</li><li>DIY homeowners</li>
        </ul>
        <p>Our goal is to provide premium-quality products with reliable service and competitive pricing to help you
        complete every project successfully.</p>

        <h3>Shop With Confidence</h3>
        <p>At BHS Supplies, we believe in delivering quality products, dependable service, and unbeatable value.
        Whether you are shopping for HVAC equipment, plumbing supplies, bathroom upgrades, or contractor tools, we are
        committed to helping you find the right products at the right price.</p>
        <p>Visit our showroom or shop online today and discover why customers across Canada trust BHS Supplies for their
        residential and commercial supply needs.</p>';
    $bottomContent = get_setting('store_page_bottom_content') ?: $bottomDefault;
@endphp

@if(trim(strip_tags($bottomContent)) !== '')
<section class="store-seo pb-5">
    <div class="container">
        <div class="bg-white rounded shadow-sm p-4 p-md-5 promo-content seo-content">
            {!! $bottomContent !!}
        </div>
    </div>
</section>
@endif

@php
    $storeAddress = get_setting('store_page_address', '7040 Torbram Rd #8, Mississauga, ON L4T 3Z4');
    $storePhone   = get_setting('store_page_phone', '+1 (647) 456-2244');
    $storeEmail   = get_setting('store_page_email', 'support@bhssupplies.com');
    $storeWebsite = get_setting('store_page_website', 'www.bhssupplies.com');
    $mapQuery     = urlencode($storeAddress);
@endphp

<section class="store-contact pb-5">
    <div class="container">
        <div class="bg-white rounded shadow-sm overflow-hidden">
            <div class="row no-gutters">
                <div class="col-12 col-lg-5 p-4 p-md-5">
                    <h2 class="fs-20 fw-700 mb-1">{{ translate('Visit Our Showroom') }}</h2>
                    <p class="text-muted mb-2">{{ get_setting('website_name', 'BHS HVAC Plumbing & Hardware Supplies') }}</p>
                    <div class="d-flex align-items-center mb-4 small">
                        <span class="text-warning mr-1">★★★★★</span>
                        <span class="fw-600 mr-2">5.0</span>
                        <span class="text-muted">· {{ translate('Plumbing supply store') }} · <span class="text-success fw-600">{{ translate('Open') }}</span></span>
                    </div>

                    <div class="d-flex mb-3">
                        <span class="store-contact-icon"><i class="las la-map-marker-alt"></i></span>
                        <div>
                            <div class="fw-600 mb-1">{{ translate('Address') }}</div>
                            <a class="text-reset" target="_blank" rel="noopener"
                               href="https://maps.google.com/?q={{ $mapQuery }}">{{ $storeAddress }}</a>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <span class="store-contact-icon"><i class="las la-phone-volume"></i></span>
                        <div>
                            <div class="fw-600 mb-1">{{ translate('Phone') }}</div>
                            <a class="text-reset" href="tel:{{ preg_replace('/[^0-9+]/', '', $storePhone) }}">{{ $storePhone }}</a>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <span class="store-contact-icon"><i class="las la-envelope"></i></span>
                        <div>
                            <div class="fw-600 mb-1">{{ translate('Email') }}</div>
                            <a class="text-reset" href="mailto:{{ $storeEmail }}">{{ $storeEmail }}</a>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <span class="store-contact-icon"><i class="las la-globe"></i></span>
                        <div>
                            <div class="fw-600 mb-1">{{ translate('Website') }}</div>
                            <a class="text-reset" target="_blank" rel="noopener" href="https://{{ ltrim(preg_replace('#^https?://#', '', $storeWebsite), '/') }}">{{ $storeWebsite }}</a>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap">
                        <a href="{{ route('home') }}" class="btn btn-primary mr-2 mb-2"><i class="las la-shopping-cart mr-1"></i>{{ translate('Shop Online Now') }}</a>
                        <a href="https://maps.google.com/?q={{ $mapQuery }}" target="_blank" rel="noopener" class="btn btn-outline-primary mb-2"><i class="las la-directions mr-1"></i>{{ translate('Directions') }}</a>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <iframe
                        src="https://maps.google.com/maps?q={{ $mapQuery }}&z=15&output=embed"
                        title="{{ translate('Store location map') }}"
                        class="store-map" width="100%" height="100%"
                        style="border:0; min-height:340px;" allowfullscreen=""
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "HardwareStore",
    "name": {!! json_encode($siteName) !!},
    "image": {!! json_encode(uploaded_asset(get_setting('header_logo'))) !!},
    "url": {!! json_encode(route('promotions')) !!},
    "telephone": {!! json_encode($storePhone) !!},
    "email": {!! json_encode($storeEmail) !!},
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "7040 Torbram Rd #8",
        "addressLocality": "Mississauga",
        "addressRegion": "ON",
        "postalCode": "L4T 3Z4",
        "addressCountry": "CA"
    },
    "areaServed": ["Mississauga", "Brampton", "Toronto", "Greater Toronto Area", "Ontario", "Canada"],
    "description": {!! json_encode($metaDesc) !!},
    "keywords": "HVAC supplies, plumbing supplies, electrical supplies, AC supplies, auto supplies, hardware supplies, wholesale distributor, liquidation deals, Mississauga, Ontario, Canada"
}
</script>

<style>
    .seo-content h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: .75rem; color:#13315c; }
    .seo-content h3 { font-size: 1.15rem; font-weight: 700; margin: 1.25rem 0 .5rem; color:#0b2545; }
    .seo-content p { color:#4a5568; line-height: 1.7; }
    .seo-card-label { font-weight: 600; color:#0b2545 !important; margin-bottom:.35rem; }
    .seo-check-list { list-style: none; padding-left: 0; margin: 0 0 .5rem; }
    .seo-check-list li { position: relative; padding-left: 1.6rem; margin-bottom: .4rem; color:#4a5568; line-height:1.5; }
    .seo-check-list li::before {
        content: "\f00c"; font-family: "Line Awesome Free"; font-weight: 900;
        position: absolute; left: 0; top: 1px; color:#1a9d4b; font-size: .95rem;
    }
    .seo-check-inline { display: flex; flex-wrap: wrap; gap: .25rem 1.5rem; }
    .seo-check-inline li { flex: 0 0 auto; }
    .seo-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin: 1rem 0 1.5rem; }
    .seo-card { border: 1px solid #e6eaf0; border-radius: 10px; padding: 1.25rem; background:#fafbfd; }
    .seo-card h3 { margin-top: 0; }
    .seo-tags { display: flex; flex-wrap: wrap; gap: .5rem; margin: .5rem 0 1rem; }
    .seo-tags span {
        background:#eef2f8; color:#13315c; border-radius: 30px;
        padding: .35rem .9rem; font-size: .85rem; font-weight: 500; white-space: nowrap;
    }
    .store-contact-icon {
        flex: 0 0 42px; width: 42px; height: 42px; margin-right: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #eef2f8; color: #13315c; border-radius: 50%; font-size: 20px;
    }
    .store-contact a:hover { color:#e7141a !important; }
    .store-map { display:block; height:100%; }
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
