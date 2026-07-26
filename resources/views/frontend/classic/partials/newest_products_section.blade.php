@if (count($newest_products) > 0)
@php
    $cart_added = [];
    $carts = get_user_cart();
    if (count($carts) > 0) { $cart_added = $carts->pluck('product_id')->toArray(); }
@endphp
<section class="mt-4">
    <div class="container">
        <div class="bhs-section-card">
            <div class="bhs-section-header">
                <h2 class="bhs-section-title">{{ translate('New Products') }}</h2>
                <a href="{{ route('search', ['sort_by' => 'newest']) }}" class="bhs-view-more-btn">{{ translate('View More') }}</a>
            </div>
            <div class="bhs-home-carousel aiz-carousel arrow-inactive-none px-4 pb-3"
                data-items="6" data-xxl-items="6" data-xl-items="6"
                data-lg-items="4" data-md-items="3" data-sm-items="2" data-xs-items="2"
                data-arrows="true" data-infinite="false">
                @foreach ($newest_products as $new_product)
                <div class="carousel-box px-2 py-2">
                    @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1', ['product' => $new_product, 'cart_added' => $cart_added])
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
