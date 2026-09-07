@php
    if (!isset($cart_added)) {
        $cart_added = [];
        $carts = get_user_cart();
        if (count($carts) > 0) { $cart_added = $carts->pluck('product_id')->toArray(); }
    }
    $product_url = route('product', $product->slug);
    if ($product->auction_product == 1) { $product_url = route('auction-product', $product->slug); }
    $stockQty = 0;
    foreach ($product->stocks as $_s) { $stockQty += $_s->qty; }
    $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
    $attributes = is_string($product->attributes) ? json_decode($product->attributes, true) : $product->attributes;
    $hasOptions = (is_array($colors) && count($colors) > 0) || (is_array($attributes) && count($attributes) > 0);
    $firstSku = optional($product->stocks->first())->sku ?: $product->sku ?? null;
    $basePriceRaw = home_base_price($product, false);
    $discountedBasePriceRaw = home_discounted_base_price($product, false);
    $discountPercent = $basePriceRaw > 0 ? round((($basePriceRaw - $discountedBasePriceRaw) * 100) / $basePriceRaw) : 0;
    $basePrice = format_price($basePriceRaw);
    $discountedBasePrice = format_price($discountedBasePriceRaw);
    $listingIndex = isset($listingIndex) ? (int) $listingIndex : 99;
    $isEagerImage = $listingIndex < 4 && !request()->ajax();
    $isPriorityImage = $listingIndex === 0 && !request()->ajax();
    $mainImage = get_image($product->thumbnail);
    $hoverImage = get_first_product_image($product->photos, $product->thumbnail_img);
    $showHoverImage = (int) get_setting('perf_product_hover_images', 0) === 1 && $hoverImage !== $mainImage;
    $placeholderImage = static_asset('assets/img/placeholder.jpg');
@endphp

<div class="pc-card h-100 d-flex flex-column">

    {{-- Image Area --}}
    <div class="pc-img-wrap position-relative">
        {{-- Badges --}}
        @if ($discountPercent > 0)
            <span class="bhs-badge-discount">-{{ $discountPercent }}%</span>
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

        <a href="{{ $product_url }}" class="d-block pc-img-link {{ $showHoverImage ? 'has-hover-image' : '' }}">
            <img class="{{ $isEagerImage ? '' : 'lazyload' }} pc-img"
                src="{{ $isEagerImage ? $mainImage : $placeholderImage }}"
                @if(!$isEagerImage) data-src="{{ $mainImage }}" @endif
                alt="{{ $product->getTranslation('name') }}"
                loading="{{ $isEagerImage ? 'eager' : 'lazy' }}"
                decoding="async"
                @if($isPriorityImage) fetchpriority="high" @endif
                sizes="(max-width: 575px) 50vw, (max-width: 991px) 33vw, 300px"
                width="300" height="300"
                onerror="this.onerror=null;this.src='{{ $placeholderImage }}';">
            @if($showHoverImage)
            {{-- Not "lazyload" here: this image is already conditionally
                 rendered only when a real second photo exists, so there's
                 nothing to save by deferring it further — and doing so via
                 the custom lazyload class raced against native `loading=lazy`
                 (both trying to manage the same src), which was why the
                 overlay sometimes never received its real image and just
                 sat blank on top of the main photo. --}}
            <img class="pc-img pc-img-hover position-absolute"
                src="{{ $hoverImage }}"
                alt="{{ $product->getTranslation('name') }}"
                loading="lazy"
                decoding="async"
                sizes="(max-width: 575px) 50vw, (max-width: 991px) 33vw, 300px"
                width="300" height="300"
                onerror="this.onerror=null;this.src='{{ $mainImage }}';">
            @endif
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
                @if ($basePrice != $discountedBasePrice)
                    <del class="pc-price-orig">{{ $basePrice }}</del>
                @endif
                <span class="pc-price-final">{{ $discountedBasePrice }}</span>
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
