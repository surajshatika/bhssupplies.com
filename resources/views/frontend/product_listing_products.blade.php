@php
    if (!isset($cart_added)) {
        $cart_added = [];
        $carts = get_user_cart();
        if (count($carts) > 0) {
            $cart_added = $carts->pluck('product_id')->toArray();
        }
    }
@endphp

@foreach ($products as $key => $product)
    <div class="col border-right border-bottom has-transition hov-shadow-out z-1 ">
        @if (isset($product_type) && $product_type == 'preorder_product')
            @include('preorder.frontend.product_box3', [
                'product' => $product,
            ])
        @else
            @include(
                'frontend.product_box_for_listing_page',
                ['product' => $product, 'listingIndex' => $key, 'cart_added' => $cart_added]
            )
        @endif
    </div>
@endforeach
