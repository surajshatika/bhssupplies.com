<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoRedirect;
use App\Observers\SeoEntitySlugObserver;
use App\Observers\SeoRedirectObserver;
use App\Services\Seo\SeoCacheManager;
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoMetaResolver::class);
        $this->app->alias(SeoMetaResolver::class, 'seo.resolver');

        $this->app->singleton(SeoCacheManager::class);
        $this->app->alias(SeoCacheManager::class, 'seo.cache');
    }

    public function boot(): void
    {
        $this->registerObservers();
        $this->registerBladeDirectives();
    }

    protected function registerObservers(): void
    {
        SeoRedirect::observe(SeoRedirectObserver::class);

        $slugObserver = SeoEntitySlugObserver::class;
        Product::observe($slugObserver);
        Category::observe($slugObserver);
        Page::observe($slugObserver);
        Blog::observe($slugObserver);
    }

    protected function registerBladeDirectives(): void
    {
        // @seoMeta — emits the canonical/OG/Twitter/JSON-LD partial for the
        // current request. For an explicit entity, templates can use:
        //   @include('seo.partials.meta-tags', ['entity' => $product, 'type' => 'product'])
        Blade::directive('seoMeta', function () {
            return "<?php echo \$__env->make('seo.partials.meta-tags', \\Illuminate\\Support\\Arr::except(get_defined_vars(), ['__data','__path']))->render(); ?>";
        });

        // @schema($json) — emits a JSON-LD script tag from an array or string.
        Blade::directive('schema', function ($expression) {
            return "<?php \$__schemaPayload = {$expression};
                if (is_array(\$__schemaPayload)) { \$__schemaPayload = json_encode(\$__schemaPayload, JSON_UNESCAPED_SLASHES); }
                if (is_string(\$__schemaPayload) && trim(\$__schemaPayload) !== '') {
                    echo '<script type=\"application/ld+json\">' . \$__schemaPayload . '</script>';
                } ?>";
        });
    }
}
