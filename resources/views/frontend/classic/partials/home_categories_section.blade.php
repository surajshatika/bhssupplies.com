@if (get_setting('home_categories') != null)
@php
    $home_categories = json_decode(get_setting('home_categories'));
    $categories = get_category($home_categories);
    $cart_added = [];
    $carts = get_user_cart();
    if (count($carts) > 0) { $cart_added = $carts->pluck('product_id')->toArray(); }
@endphp

@foreach ($categories as $category_key => $category)
@php $category_name = $category->getTranslation('name'); @endphp

<section class="mt-4">
    <div class="container">
        <div class="bhs-section-card">

            {{-- Section Header --}}
            <div class="bhs-section-header">
                <h2 class="bhs-section-title">{{ $category_name }}</h2>
                <a href="{{ route('products.category', $category->slug) }}"
                    class="bhs-view-more-btn">
                    {{ translate('View More') }}
                </a>
            </div>

            {{-- Product Carousel --}}
            <div class="aiz-carousel bhs-home-carousel arrow-inactive-none home-category px-3 pb-3"
                data-items="6" data-xxl-items="6" data-xl-items="6"
                data-lg-items="4" data-md-items="3" data-sm-items="2"
                data-xs-items="2" data-arrows="true" data-infinite="false"
                data-arrow-position-middle="true">

                @foreach (get_cached_products($category->id) as $product_key => $product)
                <div class="carousel-box px-2 py-2">
                    @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1', ['product' => $product, 'cart_added' => $cart_added])
                </div>
                @endforeach

            </div>

        </div>
    </div>
</section>

@endforeach
@endif
