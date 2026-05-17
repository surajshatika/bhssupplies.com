<?php

namespace App\Services\Seo;

use App\Services\Seo\Optimization\OptimizationService;
use Illuminate\Http\Request;

class SeoRenderService
{
    public function canonicalUrl(Request $request = null)
    {
        $request = $request ?: request();
        $query = $request->query();
        $allowed = array_intersect_key($query, array_flip(['page', 'sort_by']));
        $url = $request->url();

        if (!empty($allowed)) {
            $url .= '?'.http_build_query($allowed);
        }

        return $url;
    }

    public function metaRobots(Request $request = null)
    {
        $request = $request ?: request();
        if ($request->has('q') || $request->has('search')) {
            return 'noindex, follow';
        }

        return get_setting('seo_default_robots', 'index, follow');
    }

    public function renderSchema(array $context = [])
    {
        $schemas = [];
        $schemas[] = $this->organizationSchema();

        if (isset($context['product']) && $context['product']) {
            $schemas[] = app(OptimizationService::class)->optimizeEcommerceSeo([
                'product_id' => $context['product']->id,
            ])['product_schema'];
        } elseif (isset($context['blog']) && $context['blog']) {
            $schemas[] = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $context['blog']->title,
                'description' => $context['blog']->meta_description,
                'url' => route('blog.details', $context['blog']->slug),
            ], JSON_UNESCAPED_SLASHES);
        } elseif (isset($context['page']) && $context['page']) {
            $schemas[] = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $context['page']->title,
                'url' => url($context['page']->slug),
            ], JSON_UNESCAPED_SLASHES);
        }

        return collect($schemas)->filter()->map(function ($schema) {
            return '<script type="application/ld+json">'.$schema.'</script>';
        })->implode("\n");
    }

    protected function organizationSchema()
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_setting('website_name', config('app.name')),
            'url' => url('/'),
            'email' => get_setting('contact_email'),
            'telephone' => get_setting('contact_phone'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => get_setting('contact_address'),
            ],
        ], JSON_UNESCAPED_SLASHES);
    }
}
