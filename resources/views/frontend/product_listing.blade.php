@extends('frontend.layouts.app')

@if (isset($category_id))
    @php
        $catObj          = isset($category) && is_object($category) ? $category : \App\Models\Category::find($category_id);
        $meta_title      = $catObj->meta_title;
        $meta_description= $catObj->meta_description;
    @endphp
@elseif (isset($brand_id))
    @php
        $brandObj        = isset($brand) && is_object($brand) ? $brand : \App\Models\Brand::find($brand_id);
        $meta_title      = $brandObj->meta_title;
        $meta_description= $brandObj->meta_description;
    @endphp
@else
    @php
        $meta_title      = get_setting('meta_title');
        $meta_description= get_setting('meta_description');
    @endphp
@endif

@section('meta_title'){{ $meta_title }}@stop
@section('meta_description'){{ $meta_description }}@stop

@section('canonical'){{ url()->current() }}@endsection

@section('meta')
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta_title }}">
    <meta name="twitter:description" content="{{ $meta_description }}">
    <!-- Open Graph -->
    <meta property="og:locale" content="{{ str_replace('-', '_', str_replace('_', '-', app()->getLocale())) }}">
    <meta property="og:title" content="{{ $meta_title }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:description" content="{{ $meta_description }}">
    <meta property="og:site_name" content="{{ get_setting('website_name') ?: env('APP_NAME') }}">

    {{-- BreadcrumbList Schema --}}
    @php
        $breadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'All Categories', 'item' => route('search')],
        ];
        if (isset($category_id) && ($catBc = (isset($category) && is_object($category) ? $category : \App\Models\Category::find($category_id)))) {
            $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $catBc->getTranslation('name'), 'item' => url()->current()];
        } elseif (isset($brand_id) && ($brandBc = (isset($brand) && is_object($brand) ? $brand : \App\Models\Brand::find($brand_id)))) {
            $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $brandBc->getTranslation('name'), 'item' => url()->current()];
        } elseif (isset($query) && $query) {
            $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => 'Search: ' . $query, 'item' => url()->current()];
        }
    @endphp
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": @json($breadcrumbItems)
    }
    </script>

    {{-- CollectionPage + ItemList Schema --}}
    @php
        $_clPageTitle = $meta_title ?: 'HVAC & Plumbing Supplies — BHS Supplies';
        $_clPageDesc  = $meta_description ?: 'Wholesale HVAC, plumbing, and hardware supplies for contractors across Mississauga and GTA.';
        $_clItems     = [];
        $_clPos       = 1;
        foreach (($products ?? collect())->take(12) as $_clProd) {
            $_clImg = uploaded_asset($_clProd->thumbnail_img);
            $_clItems[] = [
                '@type'    => 'ListItem',
                'position' => $_clPos++,
                'url'      => route('product', $_clProd->slug),
                'name'     => $_clProd->getTranslation('name'),
                'image'    => $_clImg,
            ];
        }
    @endphp
    @if(!empty($_clItems))
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CollectionPage",
      "name": "{{ addslashes($_clPageTitle) }}",
      "description": "{{ addslashes($_clPageDesc) }}",
      "url": "{{ url()->current() }}",
      "mainEntity": {
        "@type": "ItemList",
        "name": "{{ addslashes($_clPageTitle) }}",
        "numberOfItems": {{ count($_clItems) }},
        "itemListElement": @json($_clItems)
      }
    }
    </script>
    @endif
@endsection

@section('content')
<style>
/* ═══════════════════════════════════════════
   BHS Supplies – Category / Listing Page
═══════════════════════════════════════════ */

/* ── Category Hero ── */
.cat-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    color: #fff; padding: 22px 0 16px; margin-bottom: 20px;
}
.cat-hero-breadcrumb { font-size: 12px; opacity: .65; margin-bottom: 6px; }
.cat-hero-breadcrumb a { color: #fff; text-decoration: none; }
.cat-hero-breadcrumb a:hover { text-decoration: underline; }
.cat-hero-breadcrumb .sep { margin: 0 6px; }
.cat-hero-title {
    font-size: 1.6rem; font-weight: 800; margin: 0 0 4px;
    display: flex; align-items: center; gap: 10px;
}
.cat-hero-title .cat-count-chip {
    background: rgba(255,107,0,.85); border-radius: 20px;
    padding: 2px 12px; font-size: 13px; font-weight: 600;
}
.cat-hero-desc { font-size: 13px; opacity: .7; max-width: 640px; margin-top: 4px; }

/* ── Filter Sidebar ── */
.filter-panel { border-radius: 10px; overflow: hidden; border: 1px solid #e9ecef; background: #fff; margin-bottom: 16px; }
.filter-panel-head {
    background: #1a1a2e; color: #fff; padding: 12px 16px;
    font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px;
}
.filter-panel-head i { color: #ff6b00; }
.fs-section { border-bottom: 1px solid #f0f2f5; }
.fs-section:last-child { border-bottom: none; }
.fs-toggle {
    width: 100%; background: #fff; border: none; padding: 11px 16px;
    font-size: 13px; font-weight: 700; color: #1a1a2e; cursor: pointer;
    display: flex; justify-content: space-between; align-items: center;
    transition: background .15s; text-align: left;
}
.fs-toggle:hover { background: #f8f9fa; }
.fs-toggle .fs-arrow { font-size: 11px; transition: transform .2s; color: #999; }
.fs-toggle.collapsed .fs-arrow { transform: rotate(-90deg); }
.fs-body { padding: 4px 16px 14px; }
.fs-cat-link {
    display: flex; align-items: center; padding: 5px 2px; font-size: 13px;
    color: #444; text-decoration: none; border-radius: 4px; transition: color .15s;
}
.fs-cat-link:hover { color: #ff6b00; text-decoration: none; }
.fs-cat-link.is-active { color: #ff6b00; font-weight: 700; }
.fs-cat-link i { font-size: 9px; margin-right: 6px; opacity: .5; }
.fs-cat-back { color: #ff6b00 !important; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 4px; padding: 5px 2px; text-decoration: none; }

/* ── Listing Toolbar ── */
.listing-toolbar {
    background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
    padding: 10px 16px; margin-bottom: 14px;
    display: flex; align-items: center; flex-wrap: wrap; gap: 10px;
}
.listing-toolbar .prod-count { font-size: 13px; color: #888; flex: 1; }
.listing-toolbar .prod-count b { color: #1a1a2e; }
.view-btns { display: flex; gap: 4px; }
.view-btn {
    width: 32px; height: 32px; border: 1.5px solid #dee2e6; border-radius: 6px;
    background: #fff; cursor: pointer; display: flex; align-items: center;
    justify-content: center; color: #999; transition: all .15s; font-size: 15px;
}
.view-btn:hover, .view-btn.vb-active { background: #ff6b00; border-color: #ff6b00; color: #fff; }
.toolbar-select {
    font-size: 13px; border: 1.5px solid #dee2e6; border-radius: 6px;
    padding: 5px 10px; color: #444; background: #fff;
}
.filter-mob-btn {
    background: #1a1a2e; color: #fff; border: none; border-radius: 6px;
    padding: 6px 14px; font-size: 13px; font-weight: 600; display: none;
    align-items: center; gap: 6px;
}
@media(max-width:1199px){ .filter-mob-btn { display: flex; } }

/* ── Product Grid / List layout ── */
#product-listing-row { transition: all .2s; }
#product-listing-row.grid-view .pc-list-inner { display: none !important; }

#product-listing-row.list-view {
    display: flex !important; flex-direction: column; gap: 10px;
}
#product-listing-row.list-view > .col {
    max-width: 100% !important; flex: 0 0 100% !important;
    padding: 0 !important;
}
#product-listing-row.list-view .pc-card {
    flex-direction: row !important; border-radius: 10px !important; max-height: 160px;
}
#product-listing-row.list-view .pc-img-wrap { width: 160px; min-width: 160px; height: 160px; border-radius: 10px 0 0 10px; }
#product-listing-row.list-view .pc-body { display: flex; flex-wrap: wrap; align-items: center; gap: 0 16px; padding: 12px 16px; }
#product-listing-row.list-view .pc-name { flex: 0 0 100%; height: auto; margin-bottom: 4px; }
#product-listing-row.list-view .pc-brand, #product-listing-row.list-view .pc-sku,
#product-listing-row.list-view .pc-rating { display: inline-flex; margin-right: 12px; }
#product-listing-row.list-view .pc-price-row { margin-left: auto; }
#product-listing-row.list-view .pc-cart-btn { white-space: nowrap; }
#product-listing-row.list-view .pc-actions { display: none; }
#product-listing-row.list-view .pc-stock { display: none; }
#product-listing-row.list-view .pc-list-inner { display: flex !important; align-items: center; justify-content: flex-end; gap: 10px; flex: 0 0 100%; }

/* ── Product Card ── */
.pc-card {
    background: #fff; border-radius: 10px; border: 1px solid #e9ecef;
    display: flex; flex-direction: column; position: relative;
    transition: transform .2s, box-shadow .2s, border-color .2s;
    overflow: hidden; height: 100%;
}
.pc-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.1); border-color: #ffd0a8; }

.pc-discount-badge {
    position: absolute; top: 10px; left: 10px; z-index: 2;
    background: #e65100; color: #fff; font-size: 11px; font-weight: 700;
    padding: 2px 8px; border-radius: 4px;
}
.pc-wholesale-badge {
    position: absolute; top: 10px; right: 10px; z-index: 2;
    background: #37474f; color: #fff; font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 4px; letter-spacing: .3px;
}

/* image */
.pc-img-wrap {
    display: block; overflow: hidden;
    background: #f8f9fa; padding: 12px;
    height: 180px; display: flex; align-items: center; justify-content: center;
}
.pc-img {
    max-height: 156px; max-width: 100%; object-fit: contain;
    transition: transform .3s;
}
.pc-card:hover .pc-img { transform: scale(1.05); }

/* quick actions */
.pc-actions {
    position: absolute; top: 8px; right: 8px; z-index: 3;
    display: flex; flex-direction: column; gap: 4px;
    opacity: 0; transform: translateX(6px); transition: all .2s;
}
.pc-card:hover .pc-actions { opacity: 1; transform: translateX(0); }
.pc-action-btn {
    width: 30px; height: 30px; border-radius: 50%; border: none;
    background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.12);
    display: flex; align-items: center; justify-content: center;
    color: #555; cursor: pointer; transition: all .15s; font-size: 14px;
}
.pc-action-btn:hover { background: #ff6b00; color: #fff; }

/* body */
.pc-body { padding: 10px 12px 12px; display: flex; flex-direction: column; flex: 1; }
.pc-brand { font-size: 11px; font-weight: 700; color: #ff6b00; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.pc-name { font-size: 13px; font-weight: 600; color: #1a1a2e; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin: 0 0 5px; }
.pc-name-link { color: #1a1a2e; text-decoration: none; }
.pc-name-link:hover { color: #ff6b00; }
.pc-sku { font-size: 11px; color: #999; margin-bottom: 4px; }
.pc-rating { font-size: 12px; margin-bottom: 6px; line-height: 1; }

.pc-price-row { display: flex; align-items: baseline; gap: 5px; margin-bottom: 6px; flex-wrap: wrap; }
.pc-price-orig { font-size: 12px; color: #bbb; text-decoration: line-through; }
.pc-price-final { font-size: 17px; font-weight: 800; color: #e65100; }

.pc-stock { font-size: 11px; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 3px; }
.pc-stock.in  { color: #2e7d32; }
.pc-stock.out { color: #c62828; }
.pc-stock i   { font-size: 13px; }

.pc-cart-btn {
    width: 100%; margin-top: auto;
    background: #fff; border: 1.5px solid #ff6b00; color: #ff6b00;
    font-size: 13px; font-weight: 700; padding: 7px 10px; border-radius: 7px;
    cursor: pointer; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 5px;
}
.pc-cart-btn:hover { background: #ff6b00; color: #fff; }

/* Load more spinner */
#load-more .spinner-wrap {
    display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 20px;
    color: #888; font-size: 13px;
}

/* Pagination */
.aiz-pagination .page-link { border-radius: 6px !important; margin: 0 2px; border-color: #dee2e6; color: #444; }
.aiz-pagination .page-item.active .page-link { background: #ff6b00; border-color: #ff6b00; }

/* ── Category Descriptions ── */
.category-description-box {
    background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
    padding: 20px 24px; color: #444; font-size: 14px; line-height: 1.7;
}
.category-description-box h1,.category-description-box h2,.category-description-box h3 { color: #1a1a2e; }
.category-top-description { border-left: 4px solid #ff6b00; }
.category-bottom-description { border-left: 4px solid #0f3460; }
</style>

{{-- ── Category Hero Banner ── --}}
<div class="cat-hero">
    <div class="container">
        <div class="cat-hero-breadcrumb">
            <a href="{{ route('home') }}">{{ translate('Home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('search') }}">{{ translate('All Categories') }}</a>
            @if(isset($category_id))
                <span class="sep">/</span>
                <span>{{ \App\Models\Category::find($category_id)->getTranslation('name') }}</span>
            @elseif(isset($brand_id))
                <span class="sep">/</span>
                <span>{{ \App\Models\Brand::find($brand_id)->getTranslation('name') }}</span>
            @elseif(isset($query))
                <span class="sep">/</span>
                <span>{{ translate('Search') }}</span>
            @endif
        </div>
        <h1 class="cat-hero-title">
            @if(isset($category_id))
                <i class="las la-th-large" style="font-size:1.2rem; opacity:.6;"></i>
                {{ \App\Models\Category::find($category_id)->getTranslation('name') }}
                <span class="cat-count-chip">{{ $products->total() }} {{ translate('Products') }}</span>
            @elseif(isset($brand_id))
                <i class="las la-tag" style="font-size:1.2rem; opacity:.6;"></i>
                {{ \App\Models\Brand::find($brand_id)->getTranslation('name') }}
                <span class="cat-count-chip">{{ $products->total() }} {{ translate('Products') }}</span>
            @elseif(isset($query) && $query)
                <i class="las la-search" style="font-size:1.2rem; opacity:.6;"></i>
                {{ translate('Results for') }}: "{{ $query }}"
                <span class="cat-count-chip">{{ $products->total() }} {{ translate('found') }}</span>
            @else
                <i class="las la-boxes" style="font-size:1.2rem; opacity:.6;"></i>
                {{ translate('All Products') }}
                <span class="cat-count-chip">{{ $products->total() }} {{ translate('Products') }}</span>
            @endif
        </h1>
        @if(isset($category_id) && \App\Models\Category::find($category_id)->meta_description)
            <p class="cat-hero-desc">{{ \App\Models\Category::find($category_id)->meta_description }}</p>
        @endif
    </div>
</div>

{{-- ── Category Top Description ── --}}
@if(isset($category_id) && \App\Models\Category::find($category_id)->top_description)
<section class="mb-3">
    <div class="container">
        <div class="category-description-box category-top-description">
            {!! \App\Models\Category::find($category_id)->top_description !!}
        </div>
    </div>
</section>
@endif

<section class="mb-4">
    <div class="container sm-px-0">
        <form id="search-form" action="" method="GET">
            <input type="hidden" name="keyword" value="{{ $query ?? '' }}">
            <input type="hidden" name="min_price" value="">
            <input type="hidden" name="max_price" value="">

            <div class="row gutters-10">

                {{-- ── Filter Sidebar ── --}}
                <div class="col-xl-3">
                    <div class="aiz-filter-sidebar collapse-sidebar-wrap sidebar-xl sidebar-right z-1035">
                        <div class="overlay overlay-fixed dark c-pointer" data-toggle="class-toggle" data-target=".aiz-filter-sidebar" data-same=".filter-sidebar-thumb"></div>
                        <div class="collapse-sidebar c-scrollbar-light text-left">

                            {{-- Mobile close --}}
                            <div class="d-flex d-xl-none justify-content-between align-items-center px-3 py-2 border-bottom">
                                <h3 class="h6 mb-0 fw-700">{{ translate('Filters') }}</h3>
                                <button type="button" class="btn btn-sm p-2 filter-sidebar-thumb" data-toggle="class-toggle" data-target=".aiz-filter-sidebar">
                                    <i class="las la-times la-2x"></i>
                                </button>
                            </div>

                            {{-- Categories panel --}}
                            <div class="filter-panel">
                                <div class="filter-panel-head">
                                    <i class="las la-th-large"></i>{{ translate('Categories') }}
                                </div>
                                <div class="fs-body">
                                    @if (!isset($category_id))
                                        @foreach (\App\Models\Category::where('level', 0)->get() as $category)
                                            <a class="fs-cat-link" href="{{ route('products.category', $category->slug) }}">
                                                <i class="las la-angle-right"></i>{{ $category->getTranslation('name') }}
                                            </a>
                                        @endforeach
                                    @else
                                        <a class="fs-cat-back" href="{{ route('search') }}">
                                            <i class="las la-arrow-left"></i>{{ translate('All Categories') }}
                                        </a>
                                        @if (\App\Models\Category::find($category_id)->parent_id != 0)
                                            @php $parentCat = \App\Models\Category::find(\App\Models\Category::find($category_id)->parent_id); @endphp
                                            <a class="fs-cat-back" href="{{ route('products.category', $parentCat->slug) }}">
                                                <i class="las la-arrow-left"></i>{{ $parentCat->getTranslation('name') }}
                                            </a>
                                        @endif
                                        <a class="fs-cat-link is-active" href="{{ route('products.category', \App\Models\Category::find($category_id)->slug) }}">
                                            <i class="las la-folder-open"></i>{{ \App\Models\Category::find($category_id)->getTranslation('name') }}
                                        </a>
                                        @foreach (\App\Utility\CategoryUtility::get_immediate_children_ids($category_id) as $id)
                                            <a class="fs-cat-link" style="padding-left:14px;" href="{{ route('products.category', \App\Models\Category::find($id)->slug) }}">
                                                <i class="las la-angle-right"></i>{{ \App\Models\Category::find($id)->getTranslation('name') }}
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            {{-- Price Range panel --}}
                            <div class="filter-panel">
                                <div class="filter-panel-head">
                                    <i class="las la-dollar-sign"></i>{{ translate('Price Range') }}
                                </div>
                                <div class="fs-body pt-2">
                                    <div class="aiz-range-slider">
                                        <div id="input-slider-range"
                                            data-range-value-min="@if(\App\Models\Product::count() < 1) 0 @else{{ \App\Models\Product::min('unit_price') }}@endif"
                                            data-range-value-max="@if(\App\Models\Product::count() < 1) 0 @else{{ \App\Models\Product::max('unit_price') }}@endif">
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <span class="range-slider-value value-low fs-14 fw-600 opacity-70"
                                                    @if(isset($min_price)) data-range-value-low="{{ $min_price }}"
                                                    @elseif($products->min('unit_price') > 0) data-range-value-low="{{ $products->min('unit_price') }}"
                                                    @else data-range-value-low="0" @endif
                                                    id="input-slider-range-value-low"></span>
                                            </div>
                                            <div class="col-6 text-right">
                                                <span class="range-slider-value value-high fs-14 fw-600 opacity-70"
                                                    @if(isset($max_price)) data-range-value-high="{{ $max_price }}"
                                                    @elseif($products->max('unit_price') > 0) data-range-value-high="{{ $products->max('unit_price') }}"
                                                    @else data-range-value-high="0" @endif
                                                    id="input-slider-range-value-high"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Attribute filters --}}
                            @foreach ($attributes as $attribute)
                                <div class="filter-panel">
                                    <button type="button" class="fs-toggle" onclick="this.classList.toggle('collapsed'); this.nextElementSibling.classList.toggle('d-none')">
                                        {{ translate('Filter by') }} {{ $attribute->getTranslation('name') }}
                                        <i class="las la-angle-down fs-arrow"></i>
                                    </button>
                                    <div class="fs-body">
                                        <div class="aiz-checkbox-list">
                                            @foreach ($attribute->attribute_values as $av)
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" name="selected_attribute_values[]" value="{{ $av->value }}"
                                                        @if(in_array($av->value, $selected_attribute_values)) checked @endif
                                                        onchange="filter()">
                                                    <span class="aiz-square-check"></span>
                                                    <span>{{ $av->value }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Color filter --}}
                            @if(get_setting('color_filter_activation'))
                                <div class="filter-panel">
                                    <button type="button" class="fs-toggle" onclick="this.classList.toggle('collapsed'); this.nextElementSibling.classList.toggle('d-none')">
                                        {{ translate('Filter by Color') }}
                                        <i class="las la-angle-down fs-arrow"></i>
                                    </button>
                                    <div class="fs-body">
                                        <div class="aiz-radio-inline">
                                            @foreach ($colors as $color)
                                                <label class="aiz-megabox pl-0 mr-2" data-toggle="tooltip" data-title="{{ $color->name }}">
                                                    <input type="radio" name="color" value="{{ $color->code }}" onchange="filter()"
                                                        @if(isset($selected_color) && $selected_color == $color->code) checked @endif>
                                                    <span class="aiz-megabox-elem rounded d-flex align-items-center justify-content-center p-1 mb-2">
                                                        <span class="size-30px d-inline-block rounded" style="background:{{ $color->code }};"></span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div>{{-- /collapse-sidebar --}}
                    </div>
                </div>

                {{-- ── Products Column ── --}}
                <div class="col-xl-9">

                    {{-- Toolbar --}}
                    <div class="listing-toolbar">
                        {{-- Mobile filter button --}}
                        <button type="button" class="filter-mob-btn filter-sidebar-thumb" data-toggle="class-toggle" data-target=".aiz-filter-sidebar">
                            <i class="las la-filter"></i>{{ translate('Filters') }}
                        </button>

                        <span class="prod-count">
                            {{ translate('Showing') }} <b>{{ $products->firstItem() }}–{{ $products->lastItem() }}</b>
                            {{ translate('of') }} <b>{{ $products->total() }}</b> {{ translate('products') }}
                        </span>

                        {{-- Brand selector --}}
                        @if(Route::currentRouteName() != 'products.brand')
                            <select class="toolbar-select aiz-selectpicker" data-live-search="true" name="brand" onchange="filter()" style="max-width:160px;">
                                <option value="">{{ translate('All Brands') }}</option>
                                @foreach(\App\Models\Brand::all() as $brand)
                                    <option value="{{ $brand->slug }}" @isset($brand_id) @if($brand_id == $brand->id) selected @endif @endisset>{{ $brand->getTranslation('name') }}</option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Sort --}}
                        <select class="toolbar-select aiz-selectpicker" name="sort_by" onchange="filter()" style="max-width:175px;">
                            <option value="newest"     @isset($sort_by) @if($sort_by=='newest')     selected @endif @endisset>{{ translate('Newest') }}</option>
                            <option value="oldest"     @isset($sort_by) @if($sort_by=='oldest')     selected @endif @endisset>{{ translate('Oldest') }}</option>
                            <option value="price-asc"  @isset($sort_by) @if($sort_by=='price-asc')  selected @endif @endisset>{{ translate('Price: Low → High') }}</option>
                            <option value="price-desc" @isset($sort_by) @if($sort_by=='price-desc') selected @endif @endisset>{{ translate('Price: High → Low') }}</option>
                        </select>

                        {{-- View toggle --}}
                        <div class="view-btns ml-auto">
                            <button type="button" class="view-btn vb-active" id="btn-grid" onclick="setView('grid')" title="{{ translate('Grid View') }}">
                                <i class="las la-th"></i>
                            </button>
                            <button type="button" class="view-btn" id="btn-list" onclick="setView('list')" title="{{ translate('List View') }}">
                                <i class="las la-list"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Product Grid --}}
                    <div id="product-listing-row" class="row gutters-5 row-cols-xxl-4 row-cols-xl-3 row-cols-lg-4 row-cols-md-3 row-cols-2 grid-view">
                        @include('frontend.product_listing_products', ['products' => $products])
                    </div>

                    {{-- Load More Spinner --}}
                    <div id="load-more" class="text-center mt-4" style="display:none;">
                        <div class="spinner-wrap">
                            <div class="spinner-border text-warning" role="status" style="width:2rem;height:2rem;"></div>
                            <span>{{ translate('Loading more products…') }}</span>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    <div class="aiz-pagination aiz-pagination-center mt-4 d-none">
                        {{ $products->appends(request()->input())->links() }}
                    </div>

                </div>{{-- /col-xl-9 --}}
            </div>{{-- /row --}}
        </form>
    </div>
</section>

{{-- ── Category Bottom Description ── --}}
@if(isset($category_id) && \App\Models\Category::find($category_id)->bottom_description)
<section class="mb-4">
    <div class="container">
        <div class="category-description-box category-bottom-description">
            {!! \App\Models\Category::find($category_id)->bottom_description !!}
        </div>
    </div>
</section>
@endif

@endsection

@section('script')
<script>
    function filter(){ $('#search-form').submit(); }
    function rangefilter(arg){
        $('input[name=min_price]').val(arg[0]);
        $('input[name=max_price]').val(arg[1]);
        filter();
    }

    /* Grid / List toggle */
    function setView(mode) {
        var grid = document.getElementById('product-listing-row');
        var btnGrid = document.getElementById('btn-grid');
        var btnList = document.getElementById('btn-list');
        if (mode === 'list') {
            grid.classList.remove('grid-view');
            grid.classList.add('list-view');
            btnGrid.classList.remove('vb-active');
            btnList.classList.add('vb-active');
            localStorage.setItem('bhs_listing_view', 'list');
        } else {
            grid.classList.remove('list-view');
            grid.classList.add('grid-view');
            btnList.classList.remove('vb-active');
            btnGrid.classList.add('vb-active');
            localStorage.setItem('bhs_listing_view', 'grid');
        }
    }
    /* Restore saved view */
    $(function() {
        var saved = localStorage.getItem('bhs_listing_view');
        if (saved === 'list') setView('list');
    });

    /* Infinite scroll */
    var page = {{ request()->get('page', 1) }}, loading = false, noMoreProducts = {{ $products->hasMorePages() ? 'false' : 'true' }};
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 600) {
            if (!loading && !noMoreProducts) loadMoreProducts();
        }
    });

    function loadMoreProducts() {
        page++; loading = true;
        $('#load-more').show();
        var url = window.location.href;
        url += (url.indexOf('?') > -1 ? '&' : '?') + 'page=' + page;
        $.ajax({ url: url, type: 'get' })
        .done(function(data) {
            $('#load-more').hide();
            if (!data.trim()) { noMoreProducts = true; return; }
            $('#product-listing-row').append(data);
            loading = false;
        })
        .fail(function() { $('#load-more').hide(); loading = false; });
    }
</script>
@endsection
