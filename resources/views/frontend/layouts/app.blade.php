<!DOCTYPE html>

@php
    $rtl = get_session_language()->rtl;
@endphp

@if ($rtl == 1)
    <html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@endif

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">

    @php
        $_site_name   = get_setting('website_name') ?: env('APP_NAME');
        $_site_motto  = get_setting('site_motto');
        $_full_title  = $_site_motto ? $_site_name . ' | ' . $_site_motto : $_site_name;
        $_page_title  = mb_strlen($_full_title) > 60
            ? rtrim(mb_substr($_full_title, 0, 57)) . '...'
            : $_full_title;
        $_base_color  = get_setting('base_color', '#d43533');
        $_canonical   = url()->current();
    @endphp
    @php
        $_rendered_title = $__env->yieldContent('meta_title', $_page_title);
        if (empty(trim($_rendered_title))) { $_rendered_title = $_page_title; }
        if (mb_strlen($_rendered_title) > 60) {
            $_rendered_title = rtrim(mb_substr($_rendered_title, 0, 57)) . '...';
        }
    @endphp
    <title>{{ $_rendered_title }}</title>

    <meta name="robots" content="@yield('meta_robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')">
    <meta name="description" content="@yield('meta_description', get_setting('meta_description'))">
    <meta name="keywords" content="@yield('meta_keywords', get_setting('meta_keywords'))">
    <meta name="theme-color" content="{{ $_base_color }}">

    <link rel="canonical" href="@yield('canonical', $_canonical)">

    @yield('pagination_links')

    @hasSection('meta')
        @yield('meta')
    @else
        @include('seo.partials.meta-tags')
    @endif

    <!-- Favicon -->
    @php
        $site_icon = uploaded_asset(get_setting('site_icon'));
    @endphp
    <link rel="icon" href="{{ $site_icon }}">
    <link rel="apple-touch-icon" href="{{ $site_icon }}">

    {{-- Preload header logo so the browser starts fetching it before parsing the body --}}
    @php
        $__seoLogoUrl = ($_logo = get_setting('header_logo'))
            ? uploaded_asset($_logo)
            : static_asset('assets/img/logo.png');
    @endphp
    @if ($__seoLogoUrl)
        <link rel="preload" as="image" href="{{ $__seoLogoUrl }}">
    @endif

    @yield('lcp_preload')
    @yield('preload_assets')

    <!-- Preload critical scripts so the browser fetches them in parallel with HTML parsing -->
    <link rel="preload" href="{{ static_asset('assets/js/vendors.js?v=') }}{{ get_setting('current_version') }}" as="script">
    <link rel="preload" href="{{ static_asset('assets/js/aiz-core.min.js?v=') }}{{ get_setting('current_version') }}" as="script">

    <!-- DNS Prefetch for third-party origins -->
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
    <link rel="dns-prefetch" href="//connect.facebook.net">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//scripts.clarity.ms">
    <link rel="dns-prefetch" href="//static.getbutton.io">
    <link rel="dns-prefetch" href="//analytics.tiktok.com">
    <link rel="dns-prefetch" href="//s.pinimg.com">
    <link rel="dns-prefetch" href="//sc-static.net">

    <!-- Google Fonts — async load, 4 weights only -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap"></noscript>

    <!-- CSS — critical files load synchronously (instant render), non-critical async -->
    @php $_cv = get_setting('current_version'); @endphp
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css?v=') }}{{ $_cv }}">
    @if ($rtl == 1)
        <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-core.css?v=') }}{{ $_cv }}">
    {{-- Non-critical overrides load async — no layout impact --}}
    <link rel="preload" href="{{ static_asset('assets/css/custom-style.css?v=') }}{{ $_cv }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ static_asset('assets/css/custom-style.css?v=') }}{{ $_cv }}"></noscript>
    @if(get_setting('homepage_select') == 'thecore')
    <link rel="preload" href="{{ static_asset('assets/css/thecore.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ static_asset('assets/css/thecore.css') }}"></noscript>
    @endif

    <style>
        :root{
            --blue: #3490f3;
            --hov-blue: #2e7fd6;
            --soft-blue: rgba(0, 123, 255, 0.15);
            --secondary-base: {{ get_setting('secondary_base_color', '#ffc519') }};
            --hov-secondary-base: {{ get_setting('secondary_base_hov_color', '#dbaa17') }};
            --soft-secondary-base: {{ hex2rgba(get_setting('secondary_base_color', '#ffc519'), 0.15) }};
            --gray: #9d9da6;
            --gray-dark: #8d8d8d;
            --secondary: #919199;
            --soft-secondary: rgba(145, 145, 153, 0.15);
            --success: #85b567;
            --soft-success: rgba(133, 181, 103, 0.15);
            --warning: #f3af3d;
            --soft-warning: rgba(243, 175, 61, 0.15);
            --light: #f5f5f5;
            --soft-light: #dfdfe6;
            --soft-white: #b5b5bf;
            --dark: #292933;
            --soft-dark: #1b1b28;
            --primary: {{ get_setting('base_color', '#d43533') }};
            --hov-primary: {{ get_setting('base_hov_color', '#9d1b1a') }};
            --soft-primary: {{ hex2rgba(get_setting('base_color', '#d43533'), 0.15) }};
        }
        body{
            font-family: {!! !empty(get_setting('system_font_family')) ? get_setting('system_font_family') : "'Public Sans', sans-serif" !!}, sans-serif;
            font-weight: 400;
        }

        .pagination .page-link,
        .page-item.disabled .page-link {
            min-width: 32px;
            min-height: 32px;
            line-height: 32px;
            text-align: center;
            padding: 0;
            border: 1px solid var(--soft-light);
            font-size: 0.875rem;
            border-radius: 0 !important;
            color: var(--dark);
        }
        .pagination .page-item {
            margin: 0 5px;
        }

        .form-control:focus {
            border-width: 2px !important;
        }
        .iti__flag-container {
            padding: 2px;
        }
        .modal-content {
            border: 0 !important;
            border-radius: 0 !important;
        }

        .tagify.tagify--focus{
            border-width: 2px;
            border-color: var(--primary);
        }

        #map{
            width: 100%;
            height: 250px;
        }
        #edit_map{
            width: 100%;
            height: 250px;
        }

        .pac-container { z-index: 100000; }

        .home-category-banner::after {
            content: "{{ translate('View All') }}";
        }
    </style>

@php
    // Resolve from DB-backed settings first (config:cache-safe); env() as fallback only.
    $gaTrackingId   = get_setting('TRACKING_ID') ?: env('TRACKING_ID');
    $fbPixelId      = get_setting('FACEBOOK_PIXEL_ID') ?: env('FACEBOOK_PIXEL_ID');
@endphp

{{-- GDPR consent — must load BEFORE any analytics/marketing tags so Consent Mode v2 defaults apply --}}
@include('frontend.partials.consent_banner')

@if (get_setting('google_analytics') == 1 && !empty($gaTrackingId))
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaTrackingId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaTrackingId }}', { 'anonymize_ip': true });
    </script>
@endif

@if (get_setting('facebook_pixel') == 1 && !empty($fbPixelId))
    <!-- Facebook Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $fbPixelId }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"/>
    </noscript>
    <!-- End Facebook Pixel Code -->
@endif

{{-- Multi-channel marketing pixels (TikTok, Pinterest, Snapchat, LinkedIn, X, Clarity, Google Ads) --}}
@include('frontend.partials.marketing_pixels')

@php
    echo get_setting('header_script');
    echo get_setting('header_gtm_script');
@endphp

    <!-- LocalBusiness Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": ["HVACBusiness", "Store"],
      "name": "BHS Supplies",
      "image": "{{ uploaded_asset(get_setting('site_icon')) }}",
      "@id": "{{ url('/') }}#localbusiness",
      "url": "{{ url('/') }}",
      "telephone": "+1 (647) 456-2244",
      "email": "support@bhssupplies.com",
      "priceRange": "$$",
      "paymentAccepted": "Cash, Credit Card, Debit Card",
      "currenciesAccepted": "CAD",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "7040 Torbram Rd #8",
        "addressLocality": "Mississauga",
        "addressRegion": "ON",
        "postalCode": "L4T 3Z4",
        "addressCountry": "CA"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 43.7046894,
        "longitude": -79.6631853
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
          "opens": "10:00",
          "closes": "18:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Sunday",
          "opens": "10:00",
          "closes": "14:00"
        }
      ],
      "areaServed": [
        "Mississauga", "Brampton", "Toronto", "Etobicoke", "Vaughan",
        "Oakville", "Scarborough", "North York", "Markham", "Richmond Hill",
        "Greater Toronto Area", "Peel Region", "York Region"
      ],
      "knowsAbout": [
        "HVAC supplies", "Sheet metal duct fittings", "Flexible ductwork",
        "PEX pipe", "Brass fittings", "Copper fittings", "Push-fit connectors",
        "Gas valves", "Black iron pipe", "CSST flexible gas pipe",
        "Refrigerants R-410A R-32 R-454B", "Air filters", "Thermostats",
        "Plumbing wholesale", "HVAC contractor supplies", "Hardware fasteners"
      ],
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Wholesale HVAC, Plumbing & Hardware Supplies",
        "itemListElement": [
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Sheet Metal Duct Fittings"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Flexible Duct R4.2 R6 R8"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "PEX Pipe All Sizes"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Brass Fittings"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Copper Fittings"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Gas Valves and Black Iron Pipe"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "CSST Flexible Gas Piping"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Refrigerants R-410A R-32 R-454B"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Air Filters All Sizes"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Thermostats and Controls"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Water Heater Parts"}},
          {"@type": "Offer", "itemOffered": {"@type": "Product", "name": "Hardware Fasteners and Hangers"}}
        ]
      },
      "hasMap": "https://maps.google.com/?q=7040+Torbram+Rd+%238,+Mississauga,+ON+L4T+3Z4",
      "serviceArea": {
        "@type": "GeoCircle",
        "geoMidpoint": {
          "@type": "GeoCoordinates",
          "latitude": 43.7046894,
          "longitude": -79.6631853
        },
        "geoRadius": "50000"
      },
      "sameAs": [
        "{{ get_setting('facebook_link') }}",
        "{{ get_setting('instagram_link') }}",
        "{{ get_setting('twitter_link') }}"
      ]
    }
    </script>

    <!-- Organization Schema — E-E-A-T authority signal -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "@id": "{{ url('/') }}#organization",
      "name": "BHS Supplies",
      "url": "{{ url('/') }}",
      "logo": {
        "@type": "ImageObject",
        "url": "{{ uploaded_asset(get_setting('site_icon')) }}",
        "width": 512,
        "height": 512
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "telephone": "+1-647-456-2244",
          "contactType": "customer service",
          "areaServed": "CA",
          "availableLanguage": "English",
          "hoursAvailable": {
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
            "opens": "10:00",
            "closes": "18:00"
          }
        },
        {
          "@type": "ContactPoint",
          "telephone": "+1-647-456-2244",
          "contactType": "sales",
          "areaServed": "CA",
          "availableLanguage": "English"
        }
      ],
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "7040 Torbram Rd #8",
        "addressLocality": "Mississauga",
        "addressRegion": "ON",
        "postalCode": "L4T 3Z4",
        "addressCountry": "CA"
      },
      "email": "support@bhssupplies.com",
      "telephone": "+1-647-456-2244",
      "sameAs": [
        "{{ get_setting('facebook_link') }}",
        "{{ get_setting('instagram_link') }}",
        "{{ get_setting('twitter_link') }}"
      ]
    }
    </script>

    <!-- FAQPage Schema — targets Google "People Also Ask" rich results -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "Do you sell HVAC and plumbing supplies wholesale in Mississauga?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes — BHS Supplies is a wholesale supplier for licensed contractors and tradespeople across Mississauga and the GTA. We stock sheet metal duct fittings, flexible ducts, PEX pipe, brass and copper fittings, gas valves, refrigerants, air filters, thermostats, and 2,000+ SKUs. Walk-in same-day pickup at 7040 Torbram Rd #8, Mississauga. Shop online at bhssupplies.com or call (647) 456-2244."
          }
        },
        {
          "@type": "Question",
          "name": "What are your hours? Are you open on weekends?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "BHS Supplies is open Monday to Saturday 10am–6pm and Sunday 10am–2pm. Same-day walk-in pickup is available for all in-stock items. Call ahead for large contractor orders: (647) 456-2244."
          }
        },
        {
          "@type": "Question",
          "name": "What sheet metal duct fittings do you carry?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "We stock a full range of round and rectangular sheet metal duct fittings including elbows, tees, reducers, takeoffs, flexible duct (R4.2, R6, R8 insulation ratings), duct tape, and mastic sealant. Same-day pickup in Mississauga at our 7040 Torbram Rd location."
          }
        },
        {
          "@type": "Question",
          "name": "Can I order online and pick up in store?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes — order at bhssupplies.com and pick up same-day at our Mississauga location. We also accept phone orders at (647) 456-2244. Walk-in customers are always welcome during business hours, 7 days a week."
          }
        },
        {
          "@type": "Question",
          "name": "Do you serve contractors in Brampton, Toronto, and Vaughan?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Absolutely. BHS Supplies serves licensed HVAC technicians, plumbers, and contractors throughout Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, Scarborough, and all of the Greater Toronto Area."
          }
        },
        {
          "@type": "Question",
          "name": "What refrigerants do you carry?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "We stock R-410A, R-32, R-454B, and other refrigerant lines at wholesale contractor pricing. Call ahead for large cylinder orders: (647) 456-2244."
          }
        },
        {
          "@type": "Question",
          "name": "Do you carry gas supplies like black iron pipe and CSST?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes — BHS Supplies stocks gas valves, black iron pipe in all schedules and sizes, CSST flexible gas line, ball valves, gas cocks, and all corresponding fittings. Fully stocked for licensed gas contractors in Mississauga and the GTA."
          }
        },
        {
          "@type": "Question",
          "name": "Do you offer trade accounts or wholesale contractor pricing?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes — BHS Supplies offers trade accounts for licensed HVAC technicians, plumbers, and contractors with volume pricing and no minimum order. Call (647) 456-2244 or email support@bhssupplies.com to set up your trade account."
          }
        }
      ]
    }
    </script>

    <!-- WebSite Schema — enables Sitelinks Searchbox in Google Search -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "{{ $_site_name }}",
      "url": "{{ url('/') }}",
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "{{ url('/search') }}?keyword={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    @yield('structured_data')

</head>
<body>
    <!-- aiz-main-wrapper -->
    <div class="aiz-main-wrapper d-flex flex-column bg-white aiz-{{ get_setting('homepage_select') }}">
        @php
            $user = auth()->user();
            $user_avatar = null;
            $carts = [];
            if ($user && $user->avatar_original != null) {
                $user_avatar = uploaded_asset($user->avatar_original);
            }

            $system_language = get_system_language();
        @endphp
        <!-- Header -->
        @include('frontend.inc.nav')

        @yield('content')

        <!-- footer -->
        @include('frontend.inc.footer')

    </div>

    @if(get_setting('use_floating_buttons') == 1)
        <!-- Floating Buttons -->
        @include('frontend.inc.floating_buttons')
    @endif

    <div class="aiz-refresh">
        <div class="aiz-refresh-content"><div></div><div></div><div></div></div>
    </div>


    @if (env("DEMO_MODE") == "On")
        <!-- demo nav -->
        @include('frontend.inc.demo_nav')
    @endif

    <!-- cookies agreement -->
    @php
        $alert_location = get_setting('custom_alert_location');
        $order = in_array($alert_location, ['top-left', 'top-right']) ? 'asc' : 'desc';
        $custom_alerts = \Illuminate\Support\Facades\Cache::remember('custom_alerts_' . $order, 3600, function () use ($order) {
            return App\Models\CustomAlert::where('status', 1)->orderBy('id', $order)->get();
        });
        $hasUnreviewed = false;

        use App\Models\Order;
        use App\Models\OrderDetail;
        if(auth()->user()){
            $userId = auth()->user()->id;
            $hasUnreviewed = \Illuminate\Support\Facades\Cache::remember('user_has_unreviewed_' . $userId, 300, function () use ($userId) {
                $userOrderIds = Order::where('user_id', $userId)->pluck('id');
                return OrderDetail::whereIn('order_id', $userOrderIds)->where('delivery_status', 'delivered')->where('reviewed', 0)->exists();
            });
        }
    @endphp

    <div class="aiz-custom-alert {{ get_setting('custom_alert_location') }}" id="aiz-custom-sale-alert">
        @foreach ($custom_alerts as $custom_alert)
            @if($custom_alert->id == 1)
                <div class="aiz-cookie-alert mb-3" style="box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                    <div class="p-3 px-lg-2rem rounded-2" style="background: {{ $custom_alert->background_color }};">
                        <div class="text-{{ $custom_alert->text_color }} mb-3">
                            {!! $custom_alert->description !!}
                        </div>
                        <button class="btn btn-block btn-primary rounded-0 aiz-cookie-accept">
                            {{ translate('Ok. I Understood') }}
                        </button>
                    </div>
                </div>
            @elseif($custom_alert->id == 200)
                @php
                    $showalert = true;
                    if(auth()->user()){
                    $showalert = $hasUnreviewed;
                    }else{
                    $showalert= false;  
                    }
                @endphp
                @if(addon_is_activated('club_point') && get_setting('set_point_for_product_review') != 0 && $showalert)
                    <div class="aiz-cookie-alert mb-3 club-point-alert" style="box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                        <div class="p-3 px-lg-2rem rounded-2" style="background: {{ $custom_alert->background_color }};">
                            <div class="text-{{ $custom_alert->text_color }} mb-3 custom-alert-for-product-club-point">
                                {!! $custom_alert->description !!}
                                @if(get_setting('set_club_point_for_sellers_product_review') == 0)
                                    {{ translate('Club points are awarded only for reviews on admin’s products.') }}
                                @endif
                            </div>
                            <button class="btn btn-block btn-primary rounded-0 aiz-cookie-accept-club-point">
                                {{ translate('Ok. I Understood') }}
                            </button>
                        </div>
                    </div>
                @endif                  
            @elseif($custom_alert->id == 300) 
                @php
                    if(auth()->check()){
                    $showcustomalert = true;
                    }else{
                    $showcustomalert= false;  
                    }
                @endphp
                @if(addon_is_activated('otp_system') && $showcustomalert && auth()->user()->otp_alert_seen == 0)
                    <div class="aiz-cookie-alert mb-3" style="box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                        <div class="p-3 px-lg-2rem rounded-0" style="background: {{ $custom_alert->background_color }};">
                            <div class="text-{{ $custom_alert->text_color }} mb-3">
                                {!! $custom_alert->description !!}
                            </div>
                            <button class="btn btn-block btn-primary rounded-0 aiz-cookie-accept">
                                {{ translate('Ok. I Understood') }}
                            </button>
                        </div>
                    </div>
                @endif                  
            @else
                <div class="mb-3 custom-alert-box removable-session rounded-2 d-none" data-key="custom-alert-box-{{ $custom_alert->id }}" data-value="removed" data-auto_hide={{ $custom_alert->auto_hide }} style="box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                    <div class=" position-relative" style="background: {{ $custom_alert->background_color }};">
                        <a href="{{ $custom_alert->link }}" class="d-block h-100 w-100">
                            <div class="@if ($custom_alert->type == 'small') d-flex @endif">
                                <img class="@if ($custom_alert->type == 'small') h-140px w-120px img-fit @else w-100 @endif" src="{{ uploaded_asset($custom_alert->banner) }}" alt="custom_alert">
                                <div class="text-{{ $custom_alert->text_color }} p-2rem">
                                    {!! $custom_alert->description !!}
                                </div>
                            </div>
                        </a>
                        <button class="absolute-top-right bg-transparent btn btn-circle btn-icon d-flex align-items-center justify-content-center text-{{ $custom_alert->text_color }} hov-text-primary set-session" data-key="custom-alert-box-{{ $custom_alert->id }}" data-value="removed" data-parent=".custom-alert-box">
                            <i class="la la-close fs-20"></i>
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- website popup -->
    @php
        $dynamic_popups = \Illuminate\Support\Facades\Cache::remember('dynamic_popups', 3600, function () {
            return App\Models\DynamicPopup::where('status', 1)->orderBy('id', 'asc')->get();
        });
        $popup_count = 0;
        $hideWrapper = false;
        if(count($dynamic_popups) == 1 && ($dynamic_popups[0]->id == 100 && $hasUnreviewed == false)) {
            $hideWrapper = true;
        }
    @endphp

    @if(count($dynamic_popups) > 0)
    <div class="modal website-popup removable-session d-none "
        data-key="stack-popup-main"
        data-value="removed" id="stack-popup-main-wrapper" @if($hideWrapper) style="display: none;" @endif>


        <div class="absolute-full bg-black opacity-60"></div>

        <div class="stack-container mx-auto" id="stackParent">

            @foreach ($dynamic_popups as $key => $dynamic_popup)
                
                @php
                    
                    $showPopup = true;
                    if ($dynamic_popup->id == 100 ) {
                        if(auth()->user()){
                            $showPopup = $hasUnreviewed;
                        }else{
                            $showPopup = false;
                        }
                    }
                @endphp

                @if($showPopup)
                @php $popup_count++; @endphp

                <div class="card-wrapper card-pos-{{ $popup_count-1 }}"
                    id="w{{ $dynamic_popup->id }}"
                    data-key="stack-popup-{{ $dynamic_popup->id }}">

                    <button class="btn-close-stack d-none"
                            onclick="removeTopCard('w{{ $dynamic_popup->id }}')">
                        <i class="la la-close"></i>
                    </button>

                    <div class="mirror-card">

                        <img src="{{ uploaded_asset($dynamic_popup->banner) }}"
                            class="card-img">

                        <div class="p-4 text-center">
                            <h5 class="font-weight-bold">
                                {{ $dynamic_popup->title }}
                            </h5>

                            <p class="text-muted fs-16">
                                {{ $dynamic_popup->summary }}
                            </p>

                            @if($dynamic_popup->id == 1 && $dynamic_popup->show_subscribe_form == 'on')
                                <!-- Subscription form for ID 1 -->
                                <form class="mt-3" method="POST" action="{{ route('subscribers.store') }}">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <input type="email" class="form-control rounded-0" 
                                            placeholder="{{ translate('Your Email Address') }}" 
                                            name="email" required>
                                    </div>
                                    <button type="submit" class="vote-btn w-100 set-session"
                                            data-key="stack-popup-{{ $dynamic_popup->id }}"
                                            data-value="removed">
                                        {{ $dynamic_popup->btn_text }}
                                    </button>
                                </form>
                            @elseif($dynamic_popup->btn_link)
                                <!-- Regular button for other popups -->
                                <a href="{{ $dynamic_popup->btn_link }}"
                                class="vote-btn d-block set-session w-100"
                                data-key="stack-popup-{{ $dynamic_popup->id }}"
                                data-value="removed">
                                    {{ $dynamic_popup->btn_text }}
                                </a>
                            @endif
                        </div>
                        <div class="loader-container">
                            <div class="loader-fill" id="fill-w{{ $dynamic_popup->id }}"></div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif







    @include('frontend.partials.modal')

    @include('frontend.partials.account_delete_modal')

    <div class="modal fade" id="addToCart">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
            <div class="modal-content position-relative">
                <div class="c-preloader text-center p-3">
                    <i class="las la-spinner la-spin la-3x"></i>
                </div>
                <button type="button" class="close absolute-top-right btn-icon close z-1 btn-circle hov-text-blue bg-light hov-bg-gray has-transition mr-3 mt-3 d-flex justify-content-center align-items-center" data-dismiss="modal" aria-label="Close" style="background: #ededf2; width: calc(2rem + 2px); height: calc(2rem + 2px);">
                     <i class="la la-close fs-20 text-gray hov-text-blue has-transition"></i>
                </button>
                <div id="addToCart-modal-body">

                </div>
            </div>
        </div>
    </div>


    <!-- Offcanvas -->
    <div id="rightOffcanvas" class="right-offcanvas-md position-fixed top-0 fullscreen bg-white  py-20px z-1045">
        <!-- content will here -->
    </div>
    <!-- Overlay -->
    <div id="rightOffcanvasOverlay" class="position-fixed top-0 left-0 h-100 w-100"></div>


    @yield('modal')

    <div id="videoModal" class="video-modal">
        <div class="modal-video-wrapper">
            <video id="popupVideo" style="width: 100%; height: 100%" controls disablePictureInPicture></video>
            <span id="closeModalBtn" class="close-modal">✖</span>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        var AIZ = AIZ || {};
        AIZ.local = {
            nothing_selected: '{!! translate('Nothing selected', null, true) !!}',
            nothing_found: '{!! translate('Nothing found', null, true) !!}',
            choose_file: '{{ translate('Choose file') }}',
            file_selected: '{{ translate('File selected') }}',
            files_selected: '{{ translate('Files selected') }}',
            add_more_files: '{{ translate('Add more files') }}',
            adding_more_files: '{{ translate('Adding more files') }}',
            drop_files_here_paste_or: '{{ translate('Drop files here, paste or') }}',
            browse: '{{ translate('Browse') }}',
            upload_complete: '{{ translate('Upload complete') }}',
            upload_paused: '{{ translate('Upload paused') }}',
            resume_upload: '{{ translate('Resume upload') }}',
            pause_upload: '{{ translate('Pause upload') }}',
            retry_upload: '{{ translate('Retry upload') }}',
            cancel_upload: '{{ translate('Cancel upload') }}',
            uploading: '{{ translate('Uploading') }}',
            processing: '{{ translate('Processing') }}',
            complete: '{{ translate('Complete') }}',
            file: '{{ translate('File') }}',
            files: '{{ translate('Files') }}',
        }
    </script>
    <script src="{{ static_asset('assets/js/vendors.js?v=') }}{{ get_setting('current_version') }}"></script>
    <script src="{{ static_asset('assets/js/aiz-core.min.js?v=') }}{{ get_setting('current_version') }}"></script>

    {{-- WhatsApp Chat — deferred until user interaction or 3 s to avoid TBT penalty --}}
    @if (get_setting('whatsapp_chat') == 1)
    <script>
    (function () {
        var _loaded = false;
        function _loadWA() {
            if (_loaded) return; _loaded = true;
            var options = {
                whatsapp: "{{ env('WHATSAPP_NUMBER') }}",
                call_to_action: "{{ translate('Message us') }}",
                position: "right",
            };
            var proto = document.location.protocol, host = "getbutton.io", url = proto + "//static." + host;
            var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true;
            s.src = url + '/widget-send-button/js/init.js';
            s.onload = function () { WhWidgetSendButton.init(host, proto, options); };
            (document.getElementsByTagName('script')[0]).parentNode.insertBefore(s, document.getElementsByTagName('script')[0]);
        }
        // Load after 3 s idle or on first user gesture — whichever is sooner
        setTimeout(_loadWA, 3000);
        ['scroll','mousemove','touchstart','keydown','click'].forEach(function(e) {
            document.addEventListener(e, _loadWA, {once: true, passive: true});
        });
    })();
    </script>
    @endif

    <style>
        .sc-q8c6tt-3 {
            bottom: 54px !important;
        }

        a[aria-label="Go to GetButton.io website"] {
            display: none !important;
        }
        
    </style>
    @if(addon_is_activated('otp_system'))
        <script>
            document.querySelector('.otp-alert-link')?.addEventListener('click', function () {

                fetch("{{ route('otp.alert.seen') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({})
                });

            });
        </script>
    @endif

    <script>
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach
    </script>

    <script>
        window.POPUP_DURATION = {{ (int) get_setting('dynamic_popup_duration') ?? 5 }};
        let currentIdx = 0;
        let cardIds = [];
        let timer;

        $(document).ready(function () {

            $('.card-wrapper:visible').each(function () {
                cardIds.push($(this).attr('id'));
            });
            startTimer();
        });

    </script>

    <!-- Smooth Alert Transitions JavaScript -->
    <script>
        
        $(document).ready(function() {
            // Handle auto-hide alerts
            $('.aiz-custom-alert .custom-alert-box[data-auto_hide]').each(function() {
                const $alert = $(this);
                const seconds = parseInt($alert.attr('data-auto_hide'), 10) || 0;
                if (seconds > 0) {                    
                    setTimeout(() => {
                        smoothlyRemoveElement($alert);
                    }, seconds * 1000);
                }
            });

            // Handle cookie alert accept button
            $('.aiz-custom-alert').on('click', '.aiz-cookie-accept, .aiz-cookie-accept-club-point', function(e) {
                e.preventDefault();
                const $parent = $(this).closest('.aiz-cookie-alert');
                if ($parent.length) {
                    smoothlyRemoveElement($parent);
                }
            });

            // Handle custom alert box close button
            $('.aiz-custom-alert').on('click', '[data-parent=".custom-alert-box"]', function(e) {
                e.preventDefault();
                const $box = $(this).closest('.custom-alert-box');
                if ($box.length) {
                    smoothlyRemoveElement($box);
                }
            });
        });
    </script>

    <script>
        @if (Route::currentRouteName() == 'home' || Route::currentRouteName() == '/')
        // Guard: only fire each AJAX call when its target container actually exists in
        // the current theme. Eliminates 4-5 wasted server round-trips on thecore theme.
        function _homeSection(route, selector, extra) {
            if (!$(selector).length) return;
            $.post(route, {_token: '{{ csrf_token() }}'}, function(data) {
                $(selector).html(data);
                AIZ.plugins.slickCarousel();
                if (extra) extra();
            });
        }

        _homeSection('{{ route('home.section.featured') }}',        '#section_featured');
        _homeSection('{{ route('home.section.todays_deal') }}',     '#todays_deal');
        _homeSection('{{ route('home.section.best_selling') }}',    '#section_best_selling');
        _homeSection('{{ route('home.section.newest_products') }}', '#section_newest', function() {
            @if (get_setting('homepage_select') == 'thecore')
            toggleViewMoreButton();
            @endif
        });
        _homeSection('{{ route('home.section.auction_products') }}', '#auction_products');
        _homeSection('{{ route('home.section.home_categories') }}',  '#section_home_categories');

        if (@json(addon_is_activated('preorder'))) {
            _homeSection('{{ route('home.section.preorder_products') }}', '#section_featured_preorder_products');
        }

        @endif

        $(document).ready(function() {
            $('.category-nav-element').each(function(i, el) {

                $(el).on('mouseover', function(){
                    if(!$(el).find('.sub-cat-menu').hasClass('loaded')){
                        $.post('{{ route('category.elements') }}', {
                            _token: AIZ.data.csrf,
                            id:$(el).data('id'
                            )}, function(data){
                            $(el).find('.sub-cat-menu').addClass('loaded').html(data);
                        });
                    }
                });
            });

            if ($('#lang-change').length > 0) {
                $('#lang-change .dropdown-menu a').each(function() {
                    $(this).on('click', function(e){
                        e.preventDefault();
                        var $this = $(this);
                        var locale = $this.data('flag');
                        $.post('{{ route('language.change') }}',{_token: AIZ.data.csrf, locale:locale}, function(data){
                            location.reload();
                        });

                    });
                });
            }

            if ($('#currency-change').length > 0) {
                $('#currency-change .dropdown-menu a').each(function() {
                    $(this).on('click', function(e){
                        e.preventDefault();
                        var $this = $(this);
                        var currency_code = $this.data('currency');
                        $.post('{{ route('currency.change') }}',{_token: AIZ.data.csrf, currency_code:currency_code}, function(data){
                            location.reload();
                        });

                    });
                });
            }
        });

        $('#search').on('keyup', function(){
            search();
        });

        $('#search').on('focus', function(){
            search();
        });

        function search(){
            var searchKey = $('#search').val();
            if(searchKey.length > 0){
                $('body').addClass("typed-search-box-shown");

                $('.typed-search-box').removeClass('d-none');
                $('.search-preloader').removeClass('d-none');
                $.post('{{ route('search.ajax') }}', { _token: AIZ.data.csrf, search:searchKey}, function(data){
                    if(data == '0'){
                        // $('.typed-search-box').addClass('d-none');
                        $('#search-content').html(null);
                        $('.typed-search-box .search-nothing').removeClass('d-none').html('{{ translate('Sorry, nothing found for') }} <strong>"'+searchKey+'"</strong>');
                        $('.search-preloader').addClass('d-none');

                    }
                    else{
                        $('.typed-search-box .search-nothing').addClass('d-none').html(null);
                        $('#search-content').html(data);
                        $('.search-preloader').addClass('d-none');
                    }
                });
            }
            else {
                $('.typed-search-box').addClass('d-none');
                $('body').removeClass("typed-search-box-shown");
            }
        }

        $(".aiz-user-top-menu").on("mouseover", function (event) {
            $(".hover-user-top-menu").addClass('active');
        })
        .on("mouseout", function (event) {
            $(".hover-user-top-menu").removeClass('active');
        });

        $(document).on("click", function(event){
            var $trigger = $("#category-menu-bar");
            if($trigger !== event.target && !$trigger.has(event.target).length){
                $("#click-category-menu").slideUp("fast");;
                $("#category-menu-bar-icon").removeClass('show');
            }
        });

        function updateNavCart(view,count){
            $('.cart-count').html(count);
            $('#cart_items').html(view);
        }

        function removeFromCart(key){
            $.post('{{ route('cart.removeFromCart') }}', {
                _token  : AIZ.data.csrf,
                id      :  key
            }, function(data){
                updateNavCart(data.nav_cart_view,data.cart_count);
                $('#cart-details').html(data.cart_view);
                AIZ.plugins.notify('danger', "{{ translate('Item has been removed from cart') }}");
                $('#cart_items_sidenav').html(parseInt($('#cart_items_sidenav').html())-1);
            });
        }

        function showLoginModal() {
            $('#login_modal').modal();
        }

        function addToCompare(id){
            $.post('{{ route('compare.addToCompare') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                $('#compare').html(data);
                AIZ.plugins.notify('success', "{{ translate('Item has been added to compare list') }}");
                $('#compare_items_sidenav').html(parseInt($('#compare_items_sidenav').html())+1);
            });
        }

        function addToWishList(id){
            @if (Auth::check() && Auth::user()->user_type == 'customer')
                $.post('{{ route('wishlists.store') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                    if(data != 0){
                        $('#wishlist').html(data);
                        AIZ.plugins.notify('success', "{{ translate('Item has been added to wishlist') }}");
                    }
                    else{
                        AIZ.plugins.notify('warning', "{{ translate('Please login first') }}");
                    }
                });
            @elseif(Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the WishList.') }}");
            @else
                AIZ.plugins.notify('warning', "{{ translate('Please login first') }}");
            @endif
        }

        function showAddToCartModal(id){
            if(!$('#modal-size').hasClass('modal-lg')){
                $('#modal-size').addClass('modal-lg');
            }
            $('#addToCart-modal-body').html(null);
                $('#addToCart').modal();
            $('.c-preloader').show();
            $.post('{{ route('cart.showCartModal') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                $('.c-preloader').hide();
                $('#addToCart-modal-body').html(data);
                AIZ.plugins.slickCarousel();
                AIZ.plugins.zoom();
                AIZ.extra.plusMinus();
                getVariantPrice();
            });
        }

           // Right Offcanvas JS Start
        const rightOffcanvas = document.getElementById('rightOffcanvas');
        const overlay = document.getElementById('rightOffcanvasOverlay');

        // Open function
        function showAddToCartRightCanvas(id) {
            const $social_chat = $('.sc-q8c6tt-3');
            if ($social_chat.length) {
                $social_chat.addClass('d-none');
            }
            rightOffcanvas.classList.add('active');
            overlay.classList.add('active');
            document.body.classList.add('body-no-scroll');
            rightOffcanvas.innerHTML = '<div class="h-100 w-100 d-flex justify-content-center align-items-center"><div class="footable-loader" style="height: 100vh !important;"><span class="fooicon fooicon-loader"></span></div> </div>';

            $.ajax({
                url: '{{ route('cart.selectVariantCanvas') }}',
                type: 'POST',
                data: {
                    _token: AIZ.data.csrf,
                    id: id
                },
                success: function (data) {
                    rightOffcanvas.innerHTML = data;
                    AIZ.plugins.slickCarousel();
                    AIZ.plugins.zoom();
                    AIZ.extra.plusMinus();
                    getVariantPrice();
                },
                error: function () {
                    rightOffcanvas.innerHTML =
                        '<p class="text-danger">{{ translate("Failed to load stock data") }}</p>';
                }
            });
        }

            // Close function
            function closeRightcanvas() {
                rightOffcanvas.classList.remove('active');
                overlay.classList.remove('active');
                document.body.classList.remove('body-no-scroll');
                const $social_chat = $('.sc-q8c6tt-3');
                if ($social_chat.length) {
                    $social_chat.removeClass('d-none');
                }
            }
            function closeOffcanvas() {
                closeRightcanvas();
            }

            if (overlay) {
                overlay.addEventListener('click', closeRightcanvas);
            }
            // Optional: close with ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeRightcanvas();
            });
        // Right Offcanvas JS End

        function showReviewImageModal(imageUrl, imagesJson) {
            try {
                var images = JSON.parse(imagesJson);
                var currentIndex = images.indexOf(imageUrl);

                $('#modalReviewImage').attr('src', imageUrl);
                $('#reviewImageModal').modal('show');

                $('#prevImageBtn').off('click').on('click', function() {
                    currentIndex = (currentIndex - 1 + images.length) % images.length;
                    $('#modalReviewImage').attr('src', images[currentIndex]);
                });

                $('#nextImageBtn').off('click').on('click', function() {
                    currentIndex = (currentIndex + 1) % images.length;
                    $('#modalReviewImage').attr('src', images[currentIndex]);
                });
            } catch (error) {
                console.error("Error parsing JSON:", error);
            }
        }

        $('#option-choice-form input').on('change', function(){
            getVariantPrice();
        });

        $(document).on('change click', '#option-choice-form input', function () {
            getVariantPrice();
        });

        
        function getVariantPrice(){
            if($('#option-choice-form input[name=quantity]').val() > 0 && checkAddToCartValidity()){
                $.ajax({
                    type:"POST",
                    url: '{{ route('products.variant_price') }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function(data){
                        
                        
                        const responseImage = data.image || null;

                        if (responseImage) {
                         const thumbEl = document.querySelector('.thumb-slider');
                            const mainEl = document.querySelector('.main-slider');
                            const thumbSwiper = thumbEl && thumbEl.swiper ? thumbEl.swiper : null;
                            const mainSwiper = mainEl && mainEl.swiper ? mainEl.swiper : null;

                            let found = false;
                            $('.thumb-slider .swiper-slide').each(function (index) {
                                const slideImage = $(this).data('variation-image');
                                if (String(slideImage) === String(responseImage)) {
                                    found = true;
                                    if (thumbSwiper && typeof thumbSwiper.slideTo === 'function') {
                                        thumbSwiper.slideTo(index);
                                    }
                                    if (mainSwiper && typeof mainSwiper.slideTo === 'function') {
                                        mainSwiper.slideTo(index);
                                    }
                                    $(this).trigger('click');
                                    return false;
                                }
                            });

                            // if nothing matched, reset to first slide once
                            if (!found) {
                                if (thumbSwiper && typeof thumbSwiper.slideTo === 'function') thumbSwiper.slideTo(0);
                                if (mainSwiper && typeof mainSwiper.slideTo === 'function') mainSwiper.slideTo(0);
                            }

                        }

                        // Update main price display with the selected variant's unit price
                        if (data.unit_price) {
                            $('#chosen_price_display').html(data.unit_price);
                        }
                        $('#option-choice-form #chosen_price_div').removeClass('d-none');
                        $('#option-choice-form #chosen_price_div #chosen_price').html(data.price);
                        $('#variant_sku_section #variant_sku').html(data.sku);
                        $('#option-choice-form #selected_variant').html(data.variation);
                        $('#available-quantity').html(data.quantity);
                        $('.input-number').prop('max', data.max_limit);
                        if(parseInt(data.in_stock) == 0 && data.digital  == 0){
                           $('.buy-now').addClass('d-none');
                           $('.add-to-cart').addClass('d-none');
                           $('.out-of-stock').removeClass('d-none');
                        }
                        else{
                           $('.buy-now').removeClass('d-none');
                           $('.add-to-cart').removeClass('d-none');
                           $('.out-of-stock').addClass('d-none');
                        }

                        AIZ.extra.plusMinus();
                    }
                });
                $('#add_to_cart_count').text('(' + String($('#option-choice-form input[name=quantity]').val()).padStart(2, '0') + ')');
            }
        }

        function checkAddToCartValidity(){
            var names = {};
            $('#option-choice-form input:radio').each(function() { // find unique names
                names[$(this).attr('name')] = true;
            });
            var count = 0;
            $.each(names, function() { // then count them
                count++;
            });

            if($('#option-choice-form input:radio:checked').length == count){
                return true;
            }

            return false;
        }

        function addToCart(){
            @if (Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the Cart.') }}");
                return false;
            @endif

            if(checkAddToCartValidity()) {
                animateAddToCartButton('#added_to_cart_btn', 'loading');
                $('#addToCart').modal();
                $('.c-preloader').show();
                $.ajax({
                    type:"POST",
                    url: '{{ route('cart.addToCart') }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function(data){
                        animateAddToCartButton('#added_to_cart_btn', 'success');
                       $('#addToCart-modal-body').html(null);
                       $('.c-preloader').hide();
                       $('#modal-size').removeClass('modal-lg');
                       $('#addToCart-modal-body').html(data.modal_view);
                       AIZ.extra.plusMinus();
                       AIZ.plugins.slickCarousel();
                       updateNavCart(data.nav_cart_view,data.cart_count);
                    }
                });

                if ("{{ get_setting('facebook_pixel') }}" == 1 && typeof fbq === 'function'){
                    // Facebook Pixel AddToCart (deduplicated with CAPI via deterministic event_id)
                    var atcProductId = $('#option-choice-form input[name="id"]').val()
                                    || $('#option-choice-form [name="product_id"]').val() || '';
                    var atcEventId = 'addtocart_' + atcProductId + '_' + Date.now();
                    fbq('track', 'AddToCart', {
                        content_type: 'product',
                        content_ids: atcProductId ? [String(atcProductId)] : []
                    }, { eventID: atcEventId });
                }
            }
            else{
                animateAddToCartButton('#added_to_cart_btn', 'reset');
                AIZ.plugins.notify('warning', "{{ translate('Please choose all the options') }}");
            }
        }

        function addToCartSingleProduct(productId = null){
            @if (Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the Cart.') }}");
                return false;
            @endif

            if(!productId){
                AIZ.plugins.notify('warning', "{{ translate('Product not found') }}");
                return false;
            }

            var formData = {
                id: productId,
                quantity: 1,
                _token: '{{ csrf_token() }}'
            };

            if(checkAddToCartValidity()) {
                $('.c-preloader').show();
                $('#addToCart-modal-body').html('<div class="text-center p-5"><div class="c-preloader"></div></div>');
                $('#modal-size').removeClass('modal-lg');
                $('#addToCart').modal('show'); 

                $.ajax({
                    type: "POST",
                    url: '{{ route('cart.addToCart') }}',
                    data: formData,
                    success: function(data){
                        $('#addToCart .c-preloader').hide(); 

                        if (data && data.modal_view) {
                            $('#addToCart-modal-body').html(data.modal_view);

                            try {
                                AIZ.extra.plusMinus();
                                AIZ.plugins.slickCarousel();
                                if (typeof updateNavCart === 'function') {
                                    updateNavCart(data.nav_cart_view, data.cart_count);
                                }
                            } catch (e) {
                                console.warn("JS init error:", e);
                            }

                            $('#addToCart .modal-body').scrollTop(0);
                        } else {
                            $('#addToCart-modal-body').html('<div class="text-center p-5 text-danger">Product details not available.</div>');
                        }
                    },
                    error: function() {
                        AIZ.plugins.notify('danger', "{{ translate('Something went wrong') }}");
                        $('.c-preloader').hide();
                    }
                });

                if ("{{ get_setting('facebook_pixel') }}" == 1 && typeof fbq === 'function'){
                    fbq('track', 'AddToCart', {
                        content_type: 'product',
                        content_ids: productId ? [String(productId)] : []
                    }, { eventID: 'addtocart_' + productId + '_' + Date.now() });
                }
            }
            else{
                AIZ.plugins.notify('warning', "{{ translate('Please choose all the options') }}");
            }
        }

        function buyNow(){
            @if (Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the Cart.') }}");
                return false;
            @endif

            if(checkAddToCartValidity()) {
                $('#addToCart-modal-body').html(null);
                $('#addToCart').modal();
                $('.c-preloader').show();
                $.ajax({
                    type:"POST",
                    url: '{{ route('cart.addToCart') }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function(data){
                        if(data.status == 1){
                            $('#addToCart-modal-body').html(data.modal_view);
                            updateNavCart(data.nav_cart_view,data.cart_count);
                            window.location.replace("{{ route('cart') }}");
                        }
                        else{
                            $('#addToCart-modal-body').html(null);
                            $('.c-preloader').hide();
                            $('#modal-size').removeClass('modal-lg');
                            $('#addToCart-modal-body').html(data.modal_view);
                        }
                    }
               });
            }
            else{
                AIZ.plugins.notify('warning', "{{ translate('Please choose all the options') }}");
            }
        }

        function bid_single_modal(bid_product_id, min_bid_amount, gst_rate = null){
            @if (Auth::check() && (isCustomer() || isSeller()))
                var min_bid_amount_text = "({{ translate('Min Bid Amount: ') }}"+min_bid_amount+")";
                if (gst_rate !== null){
                $('#gst_applicable_alert').text("{{ translate('An') }} "+gst_rate+"%" + " {{ translate('GST will be applied if you win the bid and proceed with the purchase') }}");
                }
                $('#min_bid_amount').text(min_bid_amount_text);
                $('#bid_product_id').val(bid_product_id);
                $('#bid_amount').attr('min', min_bid_amount);
                $('#bid_for_product').modal('show');
            @elseif (Auth::check() && isAdmin())
                AIZ.plugins.notify('warning', '{{ translate('Sorry, Only customers & Sellers can Bid.') }}');
            @else
                $('#login_modal').modal('show');
            @endif
        }

        function clickToSlide(btn,id){
            $('#'+id+' .aiz-carousel').find('.'+btn).trigger('click');
            $('#'+id+' .slide-arrow').removeClass('link-disable');
            var arrow = btn=='slick-prev' ? 'arrow-prev' : 'arrow-next';
            if ($('#'+id+' .aiz-carousel').find('.'+btn).hasClass('slick-disabled')) {
                $('#'+id).find('.'+arrow).addClass('link-disable');
            }
        }

        function goToView(params) {
            document.getElementById(params).scrollIntoView({behavior: "smooth", block: "center"});
        }

        function copyCouponCode(code){
            navigator.clipboard.writeText(code);
            AIZ.plugins.notify('success', "{{ translate('Coupon Code Copied') }}");
        }

        $(document).ready(function(){
            $('.cart-animate').animate({margin : 0}, "slow");

            $({deg: 0}).animate({deg: 360}, {
                duration: 2000,
                step: function(now) {
                    $('.cart-rotate').css({
                        transform: 'rotate(' + now + 'deg)'
                    });
                }
            });

            setTimeout(function(){
                $('.cart-ok').css({ fill: '#d43533' });
            }, 2000);

        });

        function nonLinkableNotificationRead(){
            $.get('{{ route('non-linkable-notification-read') }}',function(data){
                $('.unread-notification-count').html(data);
            });
        }

        function animateAddToCartButton(btnSelector, state = 'loading') {
            const $btn = $(btnSelector);

            if (!$btn.length) return;

            // store original text once
            if (!$btn.data('original-html')) {
                $btn.data('original-html', $btn.html());
            }

            if (state === 'loading') {
                $btn
                    .addClass('adding')
                    .prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm mr-2"></span>');
            }

            if (state === 'success') {
                $btn
                    .removeClass('adding')
                    .addClass('added-success')
                    .html('<i class="las la-check"></i>');

                setTimeout(() => {
                    $btn
                        .removeClass('added-success')
                        .prop('disabled', false)
                        .html($btn.data('original-html'));
                }, 1500);
            }

            if (state === 'reset') {
                $btn
                    .removeClass('adding added-success')
                    .prop('disabled', false)
                    .html($btn.data('original-html'));
            }
        }

    </script>



    <script type="text/javascript">
        if ($('input[name=country_code]').length > 0){
            // Country Code
            var isPhoneShown = true,
                countryData = window.intlTelInputGlobals.getCountryData(),
                input = document.querySelector("#phone-code");

            for (var i = 0; i < countryData.length; i++) {
                var country = countryData[i];
                if (country.iso2 == 'bd') {
                    country.dialCode = '88';
                }
            }

            var iti = intlTelInput(input, {
                separateDialCode: true,
                utilsScript: "{{ static_asset('assets/js/intlTelutils.js') }}?1590403638580",
                onlyCountries: @php echo get_active_countries()->pluck('code') @endphp,
                customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                    if (selectedCountryData.iso2 == 'bd') {
                        return "01xxxxxxxxx";
                    }
                    return selectedCountryPlaceholder;
                }
            });

            var country = iti.getSelectedCountryData();
            $('input[name=country_code]').val(country.dialCode);

            input.addEventListener("countrychange", function(e) {
                // var currentMask = e.currentTarget.placeholder;
                var country = iti.getSelectedCountryData();
                $('input[name=country_code]').val(country.dialCode);

            });

            function toggleEmailPhone(el) {
                if (isPhoneShown) {
                    $('.phone-form-group').addClass('d-none');
                    $('.email-form-group').removeClass('d-none');
                    $('input[name=phone]').val(null);
                    isPhoneShown = false;
                    $(el).html('*{{ translate('Use Phone Number Instead') }}');
                } else {
                    $('.phone-form-group').removeClass('d-none');
                    $('.email-form-group').addClass('d-none');
                    $('input[name=email]').val(null);
                    isPhoneShown = true;
                    $(el).html('<i>*{{ translate('Use Email Instead') }}</i>');
                }
            }
        }
    </script>

    <script>
        var acc = document.getElementsByClassName("aiz-accordion-heading");
        var i;
        for (i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        }
    </script>

    <script>
        function showFloatingButtons() {
            document.querySelector('.floating-buttons-section').classList.toggle('show');;
        }
    </script>

    @if (env("DEMO_MODE") == "On")
        <script>
            var demoNav = document.querySelector('.aiz-demo-nav');
            var menuBtn = document.querySelector('.aiz-demo-nav-toggler');
            var lineOne = document.querySelector('.aiz-demo-nav-toggler .aiz-demo-nav-btn .line--1');
            var lineTwo = document.querySelector('.aiz-demo-nav-toggler .aiz-demo-nav-btn .line--2');
            var lineThree = document.querySelector('.aiz-demo-nav-toggler .aiz-demo-nav-btn .line--3');
            menuBtn.addEventListener('click', () => {
                toggleDemoNav();
            });

            function toggleDemoNav() {
                // demoNav.classList.toggle('show');
                demoNav.classList.toggle('shadow-none');
                lineOne.classList.toggle('line-cross');
                lineTwo.classList.toggle('line-fade-out');
                lineThree.classList.toggle('line-cross');
                if ($('.aiz-demo-nav-toggler').hasClass('show')) {
                    $('.aiz-demo-nav-toggler').removeClass('show');
                    demoHideOverlay();
                }else{
                    $('.aiz-demo-nav-toggler').addClass('show');
                    demoShowOverlay();
                }
            }

            $('.aiz-demos').click(function(e){
                if (!e.target.closest('.aiz-demos .aiz-demo-content')) {
                    toggleDemoNav();
                }
            });

            function demoShowOverlay(){
                $('.top-banner').removeClass('z-1035').addClass('z-1');
                $('.top-navbar').removeClass('z-1035').addClass('z-1');
                $('header').removeClass('z-1020').addClass('z-1');
                $('.aiz-demos').addClass('show');
            }

            function demoHideOverlay(cls=null){
                if($('.aiz-demos').hasClass('show')){
                    $('.aiz-demos').removeClass('show');
                    $('.top-banner').delay(800).removeClass('z-1').addClass('z-1035');
                    $('.top-navbar').delay(800).removeClass('z-1').addClass('z-1035');
                    $('header').delay(800).removeClass('z-1').addClass('z-1020');
                }
            }
        </script>
        
    @endif

    @if (get_setting('header_element') == 5 || get_setting('header_element') == 6)
        <script>
            // Language switcher
            function changeLanguage(code) {
                $.post('{{ route('language.change') }}', {
                    _token: '{{ csrf_token() }}',
                    locale: code
                }, function () {
                    location.reload();
                });
            }

            // Currency switcher
            function changeCurrency(code) {
                $.post('{{ route('currency.change') }}', {
                    _token: '{{ csrf_token() }}',
                    currency_code: code
                }, function () {
                    location.reload();
                });
            }
        </script>
    @endif

    <script>
    function fixSlickVisibility() {
        $('.slick-slide').css('visibility', 'visible');
        $('.slick-track').css('opacity', '1');
    }

    // Call after fullscreen exit
    $(window).on('resize', function() {
        setTimeout(function() {
            $('.product-gallery').slick('setPosition');
            $('.product-gallery-thumb').slick('setPosition');
            fixSlickVisibility();
        }, 300);
    });
    </script>
    @if(get_setting('show_custom_product_sale_alert')==1)
    <script>
    const saleAlertProducts = @json(get_all_sale_alert_products());

        function showSaleAlert() {
            if (!saleAlertProducts || saleAlertProducts.length === 0) return;
            const randomProduct = saleAlertProducts[Math.floor(Math.random() * saleAlertProducts.length)];
            const html = `
                <div class="alert  bg-white alert-dismissible rounded-2" role="alert" style="display: none; box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                    <div class="d-flex align-items-center">
                        <img src="${randomProduct.image}" class="h-50px w-50px img-fit mr-2 rounded" alt="${randomProduct.title}">
                        <div>
                            <span class="text-truncate-2">
                                <a href="${randomProduct.url}" class="text-dark font-weight-bold">${randomProduct.title}</a>
                            </span>
                             — {{ translate('ordered just now') }}!
                        </div>
                        <button type="button" class="close ml-auto hov-text-primary set-session" data-parent=".alert">
                            <i class="la la-close fs-20"></i>
                        </button>
                    </div>
                </div>
            `;
            const $container = $('#aiz-custom-sale-alert');
            @if ( get_setting('custom_alert_location')  == 'top-left' || get_setting('custom_alert_location') == 'top-right')
            const $alert = $(html).appendTo($container);
            @else
            const $alert = $(html).prependTo($container);
            @endif
            $alert.stop(true, true).fadeIn(600);

            const displayMs = 4000;
            const fadeOutTimeout = setTimeout(() => {
                smoothlyRemoveElement($alert);
            }, displayMs);

             $alert.find('.set-session').on('click', function() {
                clearTimeout(fadeOutTimeout);
                smoothlyRemoveElement($alert);
             });
        }


        function startRandomAlerts() {
            const min = parseInt(`{{ get_setting('sale_alert_min_time') }}`)  * 1000; 
            const max = parseInt(`{{ get_setting('sale_alert_max_time') }}`)  * 1000;
            const randomDelay = Math.random() * (max - min) + min;

            setTimeout(() => {
                showSaleAlert();
                startRandomAlerts();
            }, randomDelay);
        }
        if (Array.isArray(saleAlertProducts) && saleAlertProducts.length) {
            startRandomAlerts();
        }
    </script>
    @endif

   <script>
    $(document).ready(function() {
        $('.set-session').on('click', function(e) {
            const $target = $(e.currentTarget);
            const parent = $target.data('parent');
            if (parent && !$target.closest('.aiz-custom-alert').length) {
                e.preventDefault();
                $target.closest(parent).fadeOut(600);
            }
        });
    });
    </script>

    @yield('script')

    {{-- AI Chat Widget --}}
    @if(addon_is_activated('active_ecommerce_support_board') && get_setting('ai_chat_status') == 1)
        @php
            $chatVis = get_setting('ai_chat_visibility') ?? 'all';
            $showChat = ($chatVis === 'all') || ($chatVis === 'logged_in' && auth()->check());
        @endphp
        @if($showChat)
        <style>
        #aiz-chat-widget{position:fixed;bottom:24px;right:24px;z-index:9999;font-family:inherit}
        #aiz-chat-btn{width:56px;height:56px;border-radius:50%;background:var(--primary,#007bff);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25);display:flex;align-items:center;justify-content:center;font-size:24px;transition:.2s}
        #aiz-chat-btn:hover{transform:scale(1.08)}
        #aiz-chat-badge{position:absolute;top:-4px;right:-4px;background:#e53935;color:#fff;border-radius:50%;width:18px;height:18px;font-size:11px;display:none;align-items:center;justify-content:center}
        #aiz-chat-window{display:none;flex-direction:column;width:340px;max-width:calc(100vw - 48px);height:480px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.18);overflow:hidden;margin-bottom:12px}
        #aiz-chat-head{background:var(--primary,#007bff);color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
        #aiz-chat-head span{font-weight:600;font-size:15px}
        #aiz-chat-head button{background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1}
        #aiz-chat-msgs{flex:1;overflow-y:auto;padding:12px;background:#f5f7fa;display:flex;flex-direction:column;gap:10px}
        .aiz-msg-row{display:flex;gap:8px;align-items:flex-end}
        .aiz-msg-row.user{flex-direction:row-reverse}
        .aiz-msg-bubble{max-width:220px;padding:9px 13px;border-radius:14px;font-size:13.5px;line-height:1.45;word-break:break-word}
        .aiz-msg-bubble a{color:inherit;text-decoration:underline;font-weight:600}
        .aiz-msg-bubble ul{margin:4px 0 0 16px;padding:0}
        #aiz-chat-send:disabled{opacity:.5;cursor:not-allowed}
        .aiz-msg-row.ai .aiz-msg-bubble,.aiz-msg-row.admin .aiz-msg-bubble{background:#fff;color:#222;border-bottom-left-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
        .aiz-msg-row.user .aiz-msg-bubble{background:var(--primary,#007bff);color:#fff;border-bottom-right-radius:4px}
        .aiz-msg-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
        .aiz-msg-row.ai .aiz-msg-avatar,.aiz-msg-row.admin .aiz-msg-avatar{background:var(--primary,#007bff);color:#fff}
        .aiz-msg-row.user .aiz-msg-avatar{background:#ddd;color:#555}
        .aiz-msg-time{font-size:10px;color:#aaa;margin-top:2px;text-align:right}
        #aiz-chat-typing{padding:8px 12px;font-size:12px;color:#888;display:none}
        #aiz-chat-foot{padding:10px;border-top:1px solid #eee;display:flex;gap:8px}
        #aiz-chat-input{flex:1;border:1px solid #ddd;border-radius:20px;padding:8px 14px;font-size:13.5px;outline:none}
        #aiz-chat-input:focus{border-color:var(--primary,#007bff)}
        #aiz-chat-send{background:var(--primary,#007bff);color:#fff;border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        #aiz-mic-btn{background:none;border:1px solid #ddd;border-radius:50%;width:36px;height:36px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#666;transition:.2s;padding:0}
        #aiz-mic-btn:hover{background:#f0f0f0}
        #aiz-mic-btn.recording{background:#e53935;border-color:#e53935;color:#fff;animation:aizPulse 1s infinite}
        #aiz-tts-btn:hover{color:#fff !important}
        #aiz-voice-bar{display:none;align-items:center;gap:6px;padding:6px 12px;background:#fff3f3;border-top:1px solid #fdd;font-size:12px;color:#e53935}
        #aiz-voice-bar .aiz-dots span{display:inline-block;width:6px;height:6px;border-radius:50%;background:#e53935;margin:0 1px;animation:aizDot 1.2s infinite}
        #aiz-voice-bar .aiz-dots span:nth-child(2){animation-delay:.2s}
        #aiz-voice-bar .aiz-dots span:nth-child(3){animation-delay:.4s}
        @keyframes aizPulse{0%,100%{box-shadow:0 0 0 0 rgba(229,57,53,.4)}50%{box-shadow:0 0 0 8px rgba(229,57,53,0)}}
        @keyframes aizDot{0%,80%,100%{transform:scale(.6)}40%{transform:scale(1)}}
        </style>

        <div id="aiz-chat-widget">
            <div id="aiz-chat-window">
                <div id="aiz-chat-head">
                    <span>&#128172; {{ get_setting('site_name') ?? 'Support' }}</span>
                    <div style="display:flex;align-items:center;gap:8px">
                        <button id="aiz-tts-btn" onclick="aizToggleTTS()" title="Voice response OFF – click to enable" style="background:none;border:none;color:rgba(255,255,255,0.5);cursor:pointer;padding:0 4px;font-size:16px;line-height:1;transition:.2s">&#128264;</button>
                        <button onclick="aizNewChat()" title="Start new conversation" style="background:none;border:none;color:rgba(255,255,255,0.75);cursor:pointer;padding:0 4px;font-size:15px;line-height:1;transition:.2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.75)'">&#10010;</button>
                        <button onclick="aizToggleChat()" title="Close" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1">&times;</button>
                    </div>
                </div>
                <div id="aiz-chat-msgs"></div>
                <div id="aiz-chat-typing">AI is typing&#8230;</div>
                <div id="aiz-voice-bar">
                    <div class="aiz-dots"><span></span><span></span><span></span></div>
                    Listening… speak now
                </div>
                <div id="aiz-chat-foot">
                    <button id="aiz-mic-btn" onclick="aizToggleMic()" title="Voice input">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a4 4 0 0 1 4 4v6a4 4 0 0 1-8 0V5a4 4 0 0 1 4-4zm-1 17.93V21H9v2h6v-2h-2v-2.07A8 8 0 0 0 20 11h-2a6 6 0 0 1-12 0H4a8 8 0 0 0 7 7.93z"/></svg>
                    </button>
                    <input type="text" id="aiz-chat-input" placeholder="{{ translate('Type or speak a message…') }}"
                        onkeydown="if(event.key==='Enter')aizSendMsg()">
                    <button id="aiz-chat-send" onclick="aizSendMsg()" title="Send">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
                    </button>
                </div>
            </div>
            <div style="position:relative;display:inline-block">
                <button id="aiz-chat-btn" onclick="aizToggleChat()" title="{{ translate('Chat with us') }}">
                    &#128172;
                    <span id="aiz-chat-badge"></span>
                </button>
            </div>
        </div>

        <script>
        var aizConvId    = localStorage.getItem('aiz_conv_id');
        var aizLastMsgId = 0;
        var aizOpen      = false;
        var aizPollTimer = null;
        var aizTtsOn     = localStorage.getItem('aiz_tts') === '1'; // OFF by default
        var aizRecog     = null;
        var aizRecording = false;
        var aizPageCtx   = (document.title + ' | ' + window.location.pathname).substring(0, 300);

        // ── TTS toggle ────────────────────────────────────────────────────────
        function aizToggleTTS() {
            aizTtsOn = !aizTtsOn;
            localStorage.setItem('aiz_tts', aizTtsOn ? '1' : '0');
            var btn = document.getElementById('aiz-tts-btn');
            btn.style.color = aizTtsOn ? '#fff' : 'rgba(255,255,255,0.5)';
            btn.title = aizTtsOn ? 'Voice ON – click to mute' : 'Voice OFF – click to enable';
        }

        function aizSpeak(text) {
            if (!aizTtsOn || !window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            var plain = text.replace(/<[^>]+>/g, '');
            var utt = new SpeechSynthesisUtterance(plain);
            utt.lang  = 'en-US';
            utt.rate  = 1;
            utt.pitch = 1;
            window.speechSynthesis.speak(utt);
        }

        // ── STT mic ───────────────────────────────────────────────────────────
        function aizToggleMic() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert('Speech recognition is not supported in this browser. Please use Chrome or Edge.');
                return;
            }
            if (aizRecording) { aizStopMic(); return; }

            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            aizRecog = new SpeechRecognition();
            aizRecog.lang        = 'en-US';
            aizRecog.interimResults = true;
            aizRecog.maxAlternatives = 1;

            aizRecog.onstart = function() {
                aizRecording = true;
                document.getElementById('aiz-mic-btn').classList.add('recording');
                document.getElementById('aiz-voice-bar').style.display = 'flex';
            };

            aizRecog.onresult = function(e) {
                var transcript = '';
                for (var i = e.resultIndex; i < e.results.length; i++) {
                    transcript += e.results[i][0].transcript;
                }
                document.getElementById('aiz-chat-input').value = transcript;
                if (e.results[e.results.length - 1].isFinal) {
                    aizStopMic();
                    setTimeout(aizSendMsg, 300);
                }
            };

            aizRecog.onerror = function(e) { aizStopMic(); };
            aizRecog.onend   = function()  { aizStopMic(); };
            aizRecog.start();
        }

        function aizStopMic() {
            aizRecording = false;
            document.getElementById('aiz-mic-btn').classList.remove('recording');
            document.getElementById('aiz-voice-bar').style.display = 'none';
            if (aizRecog) { try { aizRecog.stop(); } catch(e){} aizRecog = null; }
        }

        // ── Chat core ─────────────────────────────────────────────────────────
        function aizToggleChat() {
            aizOpen = !aizOpen;
            document.getElementById('aiz-chat-window').style.display = aizOpen ? 'flex' : 'none';
            if (aizOpen) {
                document.getElementById('aiz-chat-badge').style.display = 'none';
                var ttsBtn = document.getElementById('aiz-tts-btn');
                ttsBtn.style.color = aizTtsOn ? '#fff' : 'rgba(255,255,255,0.5)';
                if (!aizConvId) { aizStartChat(); } else { aizScrollBottom(); }
                aizStartPolling();
            } else {
                aizStopPolling();
                aizStopMic();
                if (window.speechSynthesis) window.speechSynthesis.cancel();
            }
        }

        function aizStartChat() {
            fetch('{{ route("support_board.chat_start") }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify({})
            }).then(r => r.json()).then(data => {
                aizConvId = data.conversation_id;
                localStorage.setItem('aiz_conv_id', aizConvId);
                data.messages.forEach(m => {
                    aizAppendMsg(m.sender_type, m.message, m.time);
                    if (m.id > aizLastMsgId) aizLastMsgId = m.id;
                });
                aizScrollBottom();
            });
        }

        function aizSendMsg() {
            var input  = document.getElementById('aiz-chat-input');
            var sendBtn= document.getElementById('aiz-chat-send');
            var msg    = input.value.trim();
            if (!msg || !aizConvId) return;
            input.value = '';
            input.disabled = true;
            sendBtn.disabled = true;
            aizAppendMsg('user', msg, '');
            document.getElementById('aiz-chat-typing').style.display = 'block';
            aizScrollBottom();

            fetch('{{ route("support_board.chat_send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN':'{{ csrf_token() }}'
                },
                body: JSON.stringify({conversation_id: aizConvId, message: msg, page_context: aizPageCtx})
            }).then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function(data) {
                document.getElementById('aiz-chat-typing').style.display = 'none';
                input.disabled = false; sendBtn.disabled = false; input.focus();
                if (data.success) {
                    aizAppendMsg('ai', data.response, data.time);
                    aizSpeak(data.response);
                    aizScrollBottom();
                }
            }).catch(function(err) {
                document.getElementById('aiz-chat-typing').style.display = 'none';
                input.disabled = false; sendBtn.disabled = false;
                aizAppendMsg('ai', 'Sorry, I could not respond right now. Please try again or call +1 (647) 456-2244.', '');
                aizScrollBottom();
            });
        }

        function aizAppendMsg(type, text, time) {
            var msgs = document.getElementById('aiz-chat-msgs');
            var icon = type === 'user' ? '&#128100;' : (type === 'admin' ? '&#128737;' : '&#129302;');
            var row  = document.createElement('div');
            row.className = 'aiz-msg-row ' + type;
            var content = (type === 'ai' || type === 'admin') ? aizRenderMsg(text) : aizEsc(text);
            row.innerHTML = '<div class="aiz-msg-avatar">' + icon + '</div>' +
                '<div><div class="aiz-msg-bubble">' + content + '</div>' +
                (time ? '<div class="aiz-msg-time">' + time + '</div>' : '') + '</div>';
            msgs.appendChild(row);
        }

        function aizEsc(t) {
            return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        }

        function aizRenderMsg(t) {
            // Escape HTML
            var s = t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            // Markdown links [text](url) — only allow safe relative or https URLs
            s = s.replace(/\[([^\]]{1,120})\]\(((https?:\/\/|\/)[^\s)]{1,400})\)/g,
                '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
            // **bold**
            s = s.replace(/\*\*([^*\n]{1,120})\*\*/g, '<strong>$1</strong>');
            // Bullet lines starting with - or * or •
            s = s.replace(/(^|\n)[•\-\*] ([^\n]+)/g, '$1<li>$2</li>');
            // Wrap consecutive <li> blocks in <ul>
            s = s.replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>');
            // Newlines to br
            s = s.replace(/\n/g, '<br>');
            return s;
        }

        function aizNewChat() {
            if (!confirm('{{ translate("Start a new conversation? Current chat will be cleared.") }}')) return;
            localStorage.removeItem('aiz_conv_id');
            aizConvId    = null;
            aizLastMsgId = 0;
            document.getElementById('aiz-chat-msgs').innerHTML = '';
            aizStartChat();
        }

        function aizScrollBottom() {
            var m = document.getElementById('aiz-chat-msgs');
            m.scrollTop = m.scrollHeight;
        }

        function aizStartPolling() {
            aizPollTimer = setInterval(function() {
                if (!aizConvId) return;
                fetch('{{ route("support_board.chat_poll") }}?conversation_id=' + aizConvId + '&since=' + aizLastMsgId)
                    .then(r => r.json()).then(data => {
                        if (data.messages && data.messages.length) {
                            data.messages.forEach(m => {
                                aizAppendMsg('admin', m.message, m.time);
                                aizSpeak(m.message);
                                if (m.id > aizLastMsgId) aizLastMsgId = m.id;
                            });
                            aizScrollBottom();
                            if (!aizOpen) {
                                document.getElementById('aiz-chat-badge').style.display = 'flex';
                                document.getElementById('aiz-chat-badge').textContent = data.messages.length;
                            }
                        }
                    });
            }, 8000);
        }

        function aizStopPolling() { clearInterval(aizPollTimer); }
        </script>
        @endif
    @endif

    @php
        echo get_setting('footer_script');
        echo get_setting('footer_gtm_script');
    @endphp

</body>
</html>
