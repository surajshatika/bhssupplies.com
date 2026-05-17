{{--
    SEO meta-tags partial.

    Renders canonical / OG / Twitter / JSON-LD for the current entity using
    the SeoMetaResolver. Intentionally does NOT render <title> or
    <meta name="description"> here — the parent layout (frontend.layouts.app)
    already owns those via @yield('meta_title') and @yield('meta_description'),
    and entity views may override them via @section. The HasSeoMeta accessors
    on Product/Category/Page/Blog feed AI-Board-fixed values into those slots
    transparently.

    Usage (in any layout's <head>):
        @include('seo.partials.meta-tags')

    Or with an explicit entity:
        @include('seo.partials.meta-tags', ['entity' => $product, 'type' => 'product'])
--}}

@php
    /** @var \App\Services\Seo\SeoMetaResolver $__seoResolver */
    $__seoResolver = app('seo.resolver');

    if (!empty($entity) && !empty($type)) {
        $__seo = $__seoResolver->resolveForEntity($entity, $type);
    } else {
        $__seo = $__seoResolver->resolveForRequest();
    }
@endphp

<link rel="canonical" href="{{ $__seo['canonical'] }}">

@if(!empty($__seo['robots']))
<meta name="robots" content="{{ $__seo['robots'] }}">
@endif

{{-- Open Graph --}}
<meta property="og:locale" content="{{ $__seo['locale'] }}">
<meta property="og:type" content="{{ $__seo['og_type'] }}">
<meta property="og:title" content="{{ $__seo['og_title'] }}">
<meta property="og:description" content="{{ $__seo['og_description'] }}">
<meta property="og:url" content="{{ $__seo['canonical'] }}">
<meta property="og:site_name" content="{{ $__seo['site_name'] }}">
@if(!empty($__seo['og_image']))
<meta property="og:image" content="{{ $__seo['og_image'] }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $__seo['twitter_card'] }}">
<meta name="twitter:title" content="{{ $__seo['twitter_title'] }}">
<meta name="twitter:description" content="{{ $__seo['twitter_description'] }}">
@if(!empty($__seo['twitter_image']))
<meta name="twitter:image" content="{{ $__seo['twitter_image'] }}">
@endif

{{-- JSON-LD --}}
@foreach($__seo['schemas'] ?? [] as $__schema)
    @if(is_array($__schema) && !empty($__schema))
        <script type="application/ld+json">{!! json_encode($__schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endforeach
