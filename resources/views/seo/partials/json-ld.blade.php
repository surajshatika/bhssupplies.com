{{--
    JSON-LD only partial.

    Emits the SEO Suite's generated schema_json / breadcrumbs_json for the current
    entity (or an explicit one), WITHOUT the OG/Twitter/canonical tags the page may
    already render itself. Use on entity pages that define their own @section('meta')
    but would otherwise drop the AI-generated structured data.

    Usage:
        @include('seo.partials.json-ld', ['entity' => $page, 'type' => 'page'])
    or (route-detected):
        @include('seo.partials.json-ld')
--}}
@php
    try {
        $__jsonLdResolver = app('seo.resolver');
        $__jsonLd = (!empty($entity) && !empty($type))
            ? $__jsonLdResolver->resolveForEntity($entity, $type)
            : $__jsonLdResolver->resolveForRequest();
        $__jsonLdSchemas = $__jsonLd['schemas'] ?? [];
    } catch (\Throwable $e) {
        $__jsonLdSchemas = [];
    }
@endphp
@foreach($__jsonLdSchemas as $__schema)
    @if(is_array($__schema) && !empty($__schema))
        <script type="application/ld+json">{!! json_encode($__schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endforeach
