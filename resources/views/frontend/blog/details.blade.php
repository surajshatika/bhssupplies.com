@extends('frontend.layouts.app')

@section('meta_title'){{ $blog->meta_title }}@stop

@section('meta_description'){{ $blog->meta_description }}@stop

@section('meta_keywords'){{ $blog->meta_keywords }}@stop

@section('meta')
    <!-- Open Graph — article type for blog posts -->
    <meta property="og:title" content="{{ $blog->meta_title }}" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{ route('blog.details', $blog->slug) }}" />
    <meta property="og:image" content="{{ uploaded_asset($blog->meta_img) }}" />
    <meta property="og:description" content="{{ $blog->meta_description }}" />
    <meta property="og:site_name" content="{{ env('APP_NAME') }}" />
    <meta property="article:published_time" content="{{ $blog->created_at->toIso8601String() }}" />
    <meta property="article:modified_time" content="{{ $blog->updated_at->toIso8601String() }}" />
    <meta property="article:section" content="{{ $blog->category?->category_name ?? 'HVAC & Plumbing Tips' }}" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $blog->meta_title }}">
    <meta name="twitter:description" content="{{ $blog->meta_description }}">
    <meta name="twitter:image" content="{{ uploaded_asset($blog->meta_img) }}">
@endsection

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "{{ addslashes($blog->title) }}",
  "description": "{{ addslashes($blog->meta_description) }}",
  "image": "{{ uploaded_asset($blog->meta_img) }}",
  "url": "{{ route('blog.details', $blog->slug) }}",
  "datePublished": "{{ $blog->created_at->toIso8601String() }}",
  "dateModified": "{{ $blog->updated_at->toIso8601String() }}",
  "author": {
    "@type": "Organization",
    "name": "BHS Supplies",
    "url": "{{ url('/') }}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "BHS Supplies",
    "url": "{{ url('/') }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ uploaded_asset(get_setting('site_icon')) }}"
    }
  },
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ route('blog.details', $blog->slug) }}"
  },
  "keywords": "{{ $blog->meta_keywords }}",
  "articleSection": "{{ $blog->category?->category_name ?? 'HVAC & Plumbing Tips' }}"
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "{{ url('/') }}"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Blog",
      "item": "{{ route('blog') }}"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "{{ addslashes($blog->title) }}",
      "item": "{{ route('blog.details', $blog->slug) }}"
    }
  ]
}
</script>
@endsection

@section('content')

<section class="py-4">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blog') }}">Blog</a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width:300px;">{{ $blog->title }}</li>
            </ol>
        </nav>

        <div class="row gutters-16 justify-content-center">

            <!-- Blog Details -->
            <div class="col-xxl-7 col-lg-8">
                <div class="mb-4">
                    <!-- Title -->
                    <h1 class="fs-20 fs-md-24 fw-700 mb-3">{{ $blog->title }}</h1>
                    <div class="row mb-1">
                        <div class="col-6">
                            <small class="fs-12 fw-400 opacity-60">{{ date('M d, Y', strtotime($blog->created_at)) }}</small>
                            @if($blog->category != null)
                                &nbsp;·&nbsp;<small class="fs-12 fw-400 text-blue">{{ $blog->category->category_name }}</small>
                            @endif
                        </div>
                        <div class="col-6 text-right">
                            <div class="aiz-share"></div>
                        </div>
                    </div>
                    <!-- Image -->
                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                        data-src="{{ uploaded_asset($blog->banner) }}"
                        alt="{{ $blog->title }}"
                        class="img-fluid lazyload w-100 mt-3 mb-4 rounded">
                    <!-- Description -->
                    <div class="mb-4 overflow-hidden fs-15 text-dark" style="line-height:1.8;">
                        {!! $blog->description !!}
                    </div>

                    <!-- CTA strip -->
                    <div class="bg-primary text-white rounded p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                        <div>
                            <p class="fw-700 mb-1 fs-15">Same-day pickup — 7040 Torbram Rd #8, Mississauga</p>
                            <p class="mb-0 fs-13 opacity-90">Open Mon–Sat 10am–6pm · Sunday 10am–2pm</p>
                        </div>
                        <div class="d-flex" style="gap:8px;">
                            <a href="tel:+16474562244" class="btn btn-light btn-sm fw-700">
                                <i class="las la-phone mr-1"></i>(647) 456-2244
                            </a>
                            <a href="{{ route('search') }}" class="btn btn-outline-light btn-sm fw-600">Shop Now</a>
                        </div>
                    </div>

                    <!-- Serving cities -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <p class="fs-13 fw-700 text-gray-dark mb-2 text-uppercase" style="letter-spacing:0.5px;">
                            <i class="las la-map-marker text-primary mr-1"></i> Serving HVAC & Plumbing Contractors
                        </p>
                        <div class="d-flex flex-wrap" style="gap:8px;">
                            <a href="{{ route('locations.mississauga') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Mississauga</a>
                            <a href="{{ route('locations.brampton') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Brampton</a>
                            <a href="{{ route('locations.toronto') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Toronto</a>
                            <a href="{{ route('locations.etobicoke') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Etobicoke</a>
                            <a href="{{ route('locations.vaughan') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Vaughan</a>
                            <a href="{{ route('locations.oakville') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Oakville</a>
                            <a href="{{ route('locations.scarborough') }}" class="badge badge-light border text-dark fs-12 py-1 px-2">Scarborough</a>
                            <a href="{{ route('trade-account') }}" class="badge badge-primary fs-12 py-1 px-2">Trade Account</a>
                        </div>
                    </div>

                    <!-- Facebook Comment -->
                    @if (get_setting('facebook_comment') == 1)
                    <div class="mb-4">
                        <div class="fb-comments" data-href="{{ route('blog.details', $blog->slug) }}" data-width="" data-numposts="5"></div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xxl-3 col-lg-4">

                <!-- Trade account CTA -->
                <div class="card border-0 shadow-sm mb-4" style="border-top:3px solid #d43533 !important;">
                    <div class="card-body p-3">
                        <h3 class="h6 fw-700 text-dark mb-2">Contractor Trade Account</h3>
                        <p class="fs-13 text-gray mb-3">Wholesale pricing on all HVAC & plumbing supplies. No minimum order. Open 7 days.</p>
                        <a href="tel:+16474562244" class="btn btn-primary btn-block btn-sm fw-700 mb-2">
                            <i class="las la-phone mr-1"></i>(647) 456-2244
                        </a>
                        <a href="{{ route('trade-account') }}" class="btn btn-outline-primary btn-block btn-sm fw-600">Set Up Trade Account</a>
                    </div>
                </div>

                <!-- Recent posts -->
                <div class="p-3 border rounded mb-4">
                    <h3 class="fs-15 fw-700 text-dark mb-3">More Contractor Guides</h3>
                    <div class="row">
                        @foreach($recent_blogs as $recent_blog)
                        <div class="col-lg-12 col-sm-6 mb-3 hov-scale-img">
                            <div class="d-flex">
                                <a href="{{ route('blog.details', $recent_blog->slug) }}" class="d-block overflow-hidden flex-shrink-0 mr-2" style="width:70px;height:60px;border-radius:4px;">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($recent_blog->banner) }}"
                                        alt="{{ $recent_blog->title }}"
                                        class="img-fit lazyload w-100 h-100 has-transition">
                                </a>
                                <div>
                                    <h2 class="fs-13 fw-700 mb-1 lh-1-4">
                                        <a href="{{ route('blog.details', $recent_blog->slug) }}" class="text-reset hov-text-primary" title="{{ $recent_blog->title }}">
                                            {{ $recent_blog->title }}
                                        </a>
                                    </h2>
                                    <small class="fs-11 opacity-60">{{ date('M d, Y', strtotime($recent_blog->created_at)) }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route('blog') }}" class="btn btn-outline-dark btn-sm btn-block fw-600 mt-1">All Articles →</a>
                </div>

                <!-- Store info -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-store text-primary mr-1"></i> BHS Supplies</h3>
                        <p class="fs-13 text-gray mb-1">7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</p>
                        <p class="fs-13 text-gray mb-2">Mon–Sat: 10am–6pm · Sun: 10am–2pm</p>
                        <a href="{{ route('search') }}" class="btn btn-primary btn-block btn-sm fw-600">Browse All Products</a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

@endsection


@section('script')
    @if (get_setting('facebook_comment') == 1)
        <div id="fb-root"></div>
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v9.0&appId={{ env('FACEBOOK_APP_ID') }}&autoLogAppEvents=1" nonce="ji6tXwgZ"></script>
    @endif
@endsection