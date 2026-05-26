@extends('frontend.layouts.app')

@section('meta_title', 'HVAC & Plumbing Contractor Guides | BHS Supplies Mississauga Blog')
@section('meta_description', 'Contractor guides, product tips, and wholesale buying advice from BHS Supplies — Mississauga\'s HVAC & plumbing wholesale supplier. PEX pipe, duct fittings, refrigerants, gas valves & more.')
@section('meta_keywords', 'HVAC contractor guides Mississauga, plumbing tips wholesale, sheet metal duct fittings guide, PEX pipe installation, gas valve supplier tips, refrigerant R410A R32 contractor, BHS Supplies blog')

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "Contractor Guides & HVAC Tips", "item": "{{ url('/blogs') }}"}
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "name": "BHS Supplies — HVAC & Plumbing Contractor Guides",
  "description": "Wholesale buying guides, product tips, and contractor resources from BHS Supplies in Mississauga, Ontario.",
  "url": "{{ url('/blogs') }}",
  "publisher": {
    "@type": "Organization",
    "name": "BHS Supplies",
    "url": "{{ url('/') }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ static_asset('assets/img/logo.png') }}"
    }
  }
}
</script>
@endsection

@section('content')
    {{-- Page Hero / Header --}}
    <section class="bg-light border-bottom py-3">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Contractor Guides & HVAC Tips</li>
                </ol>
            </nav>
        </div>
    </section>

    <section class="pb-4 pt-4">
        <div class="container">

            {{-- Page Title --}}
            <div class="mb-4">
                <h1 class="fw-700 fs-22 fs-md-28 text-dark mb-2">HVAC & Plumbing Contractor Guides</h1>
                <p class="fs-15 text-gray mb-0">Wholesale buying tips, product guides, and supply advice for GTA contractors — from BHS Supplies, Mississauga.</p>
            </div>

            <div class="row gutters-16">
                <!-- Blog Posts -->
                <div class="col-xl-9 order-xl-1">
                    <div class="blog card-columns">
                        @foreach($blogs as $blog)
                            <div class="card mb-4 overflow-hidden shadow-none border rounded-0 hov-scale-img p-3">
                                <a href="{{ url("blog").'/'. $blog->slug }}" class="text-reset d-block overflow-hidden h-180px">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($blog->banner) }}"
                                        alt="{{ $blog->title }}"
                                        class="img-fit lazyload h-100 has-transition">
                                </a>
                                <div class="py-3">
                                    <h2 class="fs-16 fw-700 mb-3 h-35px text-truncate-2">
                                        <a href="{{ url("blog").'/'. $blog->slug }}" class="text-reset hov-text-primary" title="{{ $blog->title }}">
                                            {{ $blog->title }}
                                        </a>
                                    </h2>
                                    <p class="opacity-70 mb-3 h-60px text-truncate-3" title="{{ $blog->short_description }}">
                                        {{ $blog->short_description }}
                                    </p>
                                    <div>
                                        <small class="fs-12 fw-400 opacity-60">{{ date('M d, Y',strtotime($blog->created_at)) }}</small>
                                    </div>
                                    @if($blog->category != null)
                                        <div>
                                            <small class="fs-12 fw-400 text-blue">{{ $blog->category->category_name }}</small>
                                        </div>
                                    @endif
                                    <div class="mt-3 text-primary">
                                        <a href="{{ url("blog").'/'. $blog->slug }}" class="fs-14 fw-700 text-primary has-transition d-flex align-items-center hov-column-gap-1">
                                            {{ translate('Read Full Blog') }}
                                            <i class="las las-2x la-arrow-right fs-24 ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- Pagination -->
                    <div class="aiz-pagination mt-4">
                        {{ $blogs->links() }}
                    </div>

                    {{-- SEO Content Section --}}
                    <div class="mt-5 pt-4 border-top">
                        <h2 class="h5 fw-700 text-dark mb-3">Wholesale HVAC & Plumbing Guides for GTA Contractors</h2>
                        <p class="fs-14 text-dark mb-3" style="line-height:1.8;">
                            BHS Supplies publishes practical guides for licensed <strong>HVAC contractors, plumbers, and gas fitters</strong> across the GTA — covering everything from <strong>where to buy PEX pipe wholesale in Mississauga</strong> to choosing the right <strong>sheet metal duct fittings</strong> for residential and commercial installs. Our guides are written for working contractors, not homeowners — pricing, product specs, and sourcing tips you can use on the job.
                        </p>
                        <p class="fs-14 text-dark mb-4" style="line-height:1.8;">
                            We stock 2,000+ SKUs at our <strong>Mississauga warehouse (7040 Torbram Rd #8)</strong>, open 7 days a week. Same-day walk-in pickup. No minimum order. Trade accounts with volume pricing available — call <strong>(647) 456-2244</strong> or visit <a href="{{ route('trade-account') }}" class="text-primary fw-600">bhssupplies.com/contractor-trade-account</a>.
                        </p>

                        <h3 class="h6 fw-700 text-dark mb-3">We Serve Contractors Across the GTA</h3>
                        <div class="row gutters-8 mb-4">
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.mississauga') }}" class="btn btn-sm btn-outline-primary btn-block">Mississauga</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.brampton') }}" class="btn btn-sm btn-outline-primary btn-block">Brampton</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.toronto') }}" class="btn btn-sm btn-outline-primary btn-block">Toronto</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.etobicoke') }}" class="btn btn-sm btn-outline-primary btn-block">Etobicoke</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.vaughan') }}" class="btn btn-sm btn-outline-primary btn-block">Vaughan</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.oakville') }}" class="btn btn-sm btn-outline-primary btn-block">Oakville</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('locations.scarborough') }}" class="btn btn-sm btn-outline-primary btn-block">Scarborough</a></div>
                            <div class="col-6 col-md-3 mb-2"><a href="{{ route('trade-account') }}" class="btn btn-sm btn-primary btn-block">Trade Account</a></div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-xl-3">

                    {{-- Trade Account CTA --}}
                    <div class="card border-0 bg-primary text-white mb-4 p-3">
                        <h3 class="h6 fw-700 mb-2">Open a Trade Account</h3>
                        <p class="fs-13 mb-3 opacity-90">Volume pricing, priority stock access, and no minimum order — for licensed GTA contractors.</p>
                        <a href="{{ route('trade-account') }}" class="btn btn-light btn-sm fw-600 text-primary">Apply Now</a>
                    </div>

                    <!-- Search -->
                    <form class="mb-4" id="search-form" action="" method="GET">
                        <div class="aiz-filter-sidebar collapse-sidebar-wrap sidebar-xl sidebar-right z-1035">
                            <div class="overlay overlay-fixed dark c-pointer" data-toggle="class-toggle" data-target=".aiz-filter-sidebar" data-same=".filter-sidebar-thumb"></div>
                            <div class="collapse-sidebar c-scrollbar-light text-left" style="overflow-y: auto;">
                                <div class="d-flex d-xl-none justify-content-between align-items-center pl-3 border-bottom">
                                    <h3 class="h6 mb-0 fw-600">{{ translate('Filters') }}</h3>
                                    <button type="button" class="btn btn-sm p-2 filter-sidebar-thumb" data-toggle="class-toggle" data-target=".aiz-filter-sidebar">
                                        <i class="las la-times la-2x"></i>
                                    </button>
                                </div>
                                <!-- Search -->
                                <div class="mb-4 mt-3 px-3 mt-xl-0 px-xl-0">
                                    <div class="input-group w-100">
                                        <input type="text" class="border border-right-0 rounded-0 fs-14 flex-grow-1" name="search" value="{{ $search }}" placeholder="{{translate('Search...')}}" autocomplete="off" style="padding: 14px;">
                                        <div class="input-group-append">
                                            <button class="btn bg-transparent hov-bg-light rounded-0 border border-left-0" type="submit">
                                                <i class="la la-search la-flip-horizontal fs-18 text-gray"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Categories -->
                                <div class="bg-white border mb-3 mx-3 mx-xl-0">
                                    <div class="fs-16 fw-700 p-3">{{ translate('Categories')}}</div>
                                    <div class="p-3 aiz-checkbox-list">
                                        @foreach (get_all_blog_categories() as $category)
                                        <label class="aiz-checkbox mb-3">
                                            <input
                                                type="checkbox"
                                                name="selected_categories[]"
                                                value="{{ $category->slug }}" @if (in_array($category->slug, $selected_categories)) checked @endif
                                                onchange="filter()"
                                            >
                                            <span class="aiz-square-check"></span>
                                            <span class="fs-14 fw-400 text-dark has-transition hov-text-primary">{{ $category->category_name }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Recent Posts -->
                    <div class="p-3 border mb-4">
                        <h3 class="fs-16 fw-700 text-dark mb-3">{{ translate('Recent Posts') }}</h3>
                        <div class="row">
                            @foreach($recent_blogs as $recent_blog)
                            <div class="col-xl-12 col-lg-4 col-sm-6 mb-4 hov-scale-img">
                                <div class="d-flex">
                                    <div>
                                        <a href="{{ url("blog").'/'. $recent_blog->slug }}" class="text-reset d-block overflow-hidden size-80px size-xl-90px mr-2">
                                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                                data-src="{{ uploaded_asset($recent_blog->banner) }}"
                                                alt="{{ $recent_blog->title }}"
                                                class="img-fit lazyload h-100 has-transition">
                                        </a>
                                    </div>
                                    <div>
                                        <h2 class="fs-14 fw-700 mb-2 mb-xl-3 h-35px text-truncate-2">
                                            <a href="{{ url("blog").'/'. $recent_blog->slug }}" class="text-reset hov-text-primary" title="{{ $recent_blog->title }}">
                                                {{ $recent_blog->title }}
                                            </a>
                                        </h2>
                                        <div>
                                            <small class="fs-12 fw-400 opacity-60">{{ date('M d, Y',strtotime($recent_blog->created_at)) }}</small>
                                        </div>
                                        @if($recent_blog->category != null)
                                            <div>
                                                <small class="fs-12 fw-400 text-blue">{{ $recent_blog->category->category_name }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Store Info Card --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white fw-700 fs-14 py-2">
                            <i class="las la-store mr-1"></i> BHS Supplies — Mississauga
                        </div>
                        <div class="card-body p-3 fs-13">
                            <p class="mb-1"><i class="las la-map-marker text-primary mr-1"></i> <strong>7040 Torbram Rd #8</strong>, Mississauga ON</p>
                            <p class="mb-1"><i class="las la-phone text-primary mr-1"></i> <a href="tel:+16474562244" class="text-dark fw-600">(647) 456-2244</a></p>
                            <p class="mb-0"><i class="las la-clock text-primary mr-1"></i> Mon–Sat 10am–6pm · Sun 10am–2pm</p>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>
@endsection

@section('script')
    <script type="text/javascript">
        function filter(){
            $('#search-form').submit();
        }
    </script>
@endsection
