@php
    $cart_added = [];
    $carts = get_user_cart();
    if (count($carts) > 0) { $cart_added = $carts->pluck('product_id')->toArray(); }
    $product_url = route('product', $product->slug);
    if ($product->auction_product == 1) { $product_url = route('auction-product', $product->slug); }
    $stockQty = 0;
    foreach ($product->stocks as $_s) { $stockQty += $_s->qty; }
    $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
    $attributes = is_string($product->attributes) ? json_decode($product->attributes, true) : $product->attributes;
    $hasOptions = (is_array($colors) && count($colors) > 0) || (is_array($attributes) && count($attributes) > 0);
    $firstSku = optional($product->stocks->first())->sku ?: $product->sku ?? null;
@endphp

<div class="pc-card h-100 d-flex flex-column">

    {{-- Image Area --}}
    <div class="pc-img-wrap position-relative">
        {{-- Badges --}}
        @if (discount_in_percentage($product) > 0)
            <span class="bhs-badge-discount">-{{ discount_in_percentage($product) }}%</span>
        @endif
        @if ($product->wholesale_product)
            <span class="bhs-badge-wholesale">{{ translate('Wholesale') }}</span>
        @endif
        @php $customLabels = get_custom_labels($product->custom_label_id); @endphp
        @if ($customLabels)
            @foreach ($customLabels as $customLabel)
                <span class="bhs-badge-custom" style="background:{{ $customLabel->background_color }};color:{{ $customLabel->text_color }};">{{ $customLabel->text }}</span>
            @endforeach
        @endif

        <a href="{{ $product_url }}" class="d-block pc-img-link">
            <img class="lazyload pc-img"
                src="{{ get_image($product->thumbnail) }}"
                alt="{{ $product->getTranslation('name') }}"
                loading="lazy"
                width="300" height="300"
                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
            <img class="lazyload pc-img pc-img-hover position-absolute"
                src="{{ get_first_product_image($product->thumbnail, $product->photos) }}"
                alt="{{ $product->getTranslation('name') }}"
                loading="lazy"
                width="300" height="300"
                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
        </a>

        @if ($product->auction_product == 0)
        <div class="pc-actions">
            <button class="pc-action-btn" type="button" onclick="addToWishList({{ $product->id }})" title="{{ translate('Add to wishlist') }}">
                <i class="las la-heart"></i>
            </button>
            <button class="pc-action-btn" type="button" onclick="addToCompare({{ $product->id }})" title="{{ translate('Add to compare') }}">
                <i class="las la-exchange-alt"></i>
            </button>
        </div>
        @endif
    </div>

    {{-- Card Body --}}
    <div class="pc-body flex-grow-1 d-flex flex-column">

        {{-- Brand --}}
        @if ($product->brand)
            <div class="pc-brand">
                <a href="{{ route('products.brand', $product->brand->slug) }}" class="pc-brand-link">
                    {{ strtoupper($product->brand->getTranslation('name')) }}
                </a>
            </div>
        @endif

        {{-- Name --}}
        <h3 class="pc-name flex-grow-1">
            <a href="{{ $product_url }}" class="pc-name-link" title="{{ $product->getTranslation('name') }}">
                {{ $product->getTranslation('name') }}
            </a>
        </h3>

        {{-- SKU --}}
        @if ($firstSku)
            <div class="pc-sku">SKU: {{ $firstSku }}</div>
        @endif

        {{-- Rating --}}
        <div class="pc-rating">
            {{ renderStarRating($product->rating) }}
        </div>

        {{-- Price --}}
        <div class="pc-price-row">
            @if ($product->auction_product == 0)
                @if (home_base_price($product) != home_discounted_base_price($product))
                    <del class="pc-price-orig">{{ home_base_price($product) }}</del>
                @endif
                <span class="pc-price-final">{{ home_discounted_base_price($product) }}</span>
            @else
                <span class="pc-price-final">{{ single_price($product->starting_bid) }}</span>
            @endif
        </div>

        {{-- Stock --}}
        @if ($product->auction_product == 0)
            <div class="pc-stock {{ $stockQty > 0 ? 'in-stock' : 'out-stock' }}">
                <i class="las {{ $stockQty > 0 ? 'la-check-circle' : 'la-times-circle' }}"></i>
                {{ $stockQty > 0 ? translate('In stock') : translate('Out of Stock') }}
            </div>
        @endif

        {{-- Cart Button --}}
        @if ($product->auction_product == 0)
            @if ($hasOptions)
                <button class="pc-cart-btn" type="button" onclick="showAddToCartRightCanvas({{ $product->id }})">
                    <i class="las la-sliders-h"></i> {{ translate('Select Option') }}
                </button>
            @else
                <button class="pc-cart-btn {{ in_array($product->id, $cart_added) ? 'added' : '' }}" type="button"
                    @if (Auth::check() || get_setting('guest_checkout_activation') == 1)
                        onclick="addToCartSingleProduct({{ $product->id }})"
                    @else
                        onclick="showLoginModal()"
                    @endif>
                    <i class="las la-shopping-cart"></i> {{ translate('Add to cart') }}
                </button>
            @endif
        @else
            @php
                $highest_bid = $product->bids->max('amount');
                $min_bid_amount = $highest_bid != null ? $highest_bid + 1 : $product->starting_bid;
                $gst_rate = gst_applicable_product_rate($product->id);
            @endphp
            @if ($product->auction_start_date <= strtotime('now') && $product->auction_end_date >= strtotime('now'))
                <button class="pc-cart-btn" type="button" onclick="bid_single_modal({{ $product->id }}, {{ $min_bid_amount }}, {{ $gst_rate }})">
                    <i class="las la-gavel"></i> {{ translate('Place Bid') }}
                </button>
            @endif
        @endif

    </div>
</div>
