<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * SEO suite bootstrap. Every operation here is wrapped in a try/catch so that
 * a missing class file, missing model, or any other partial-deploy hazard
 * cannot prevent the rest of the application from booting. Failures are
 * logged once at boot and the site keeps running.
 */
class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->safe('register', function () {
            if (class_exists(\App\Services\Seo\SeoMetaResolver::class)) {
                $this->app->singleton(\App\Services\Seo\SeoMetaResolver::class);
                $this->app->alias(\App\Services\Seo\SeoMetaResolver::class, 'seo.resolver');
            }
            if (class_exists(\App\Services\Seo\SeoCacheManager::class)) {
                $this->app->singleton(\App\Services\Seo\SeoCacheManager::class);
                $this->app->alias(\App\Services\Seo\SeoCacheManager::class, 'seo.cache');
            }
        });
    }

    public function boot(): void
    {
        $this->safe('observers', fn() => $this->registerObservers());
        $this->safe('blade',     fn() => $this->registerBladeDirectives());
    }

    protected function registerObservers(): void
    {
        if (class_exists(\App\Models\SeoRedirect::class)
            && class_exists(\App\Observers\SeoRedirectObserver::class)) {
            \App\Models\SeoRedirect::observe(\App\Observers\SeoRedirectObserver::class);
        }

        if (!class_exists(\App\Observers\SeoEntitySlugObserver::class)) {
            return;
        }
        $slugObserver = \App\Observers\SeoEntitySlugObserver::class;

        foreach ([\App\Models\Product::class, \App\Models\Category::class, \App\Models\Page::class, \App\Models\Blog::class] as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::observe($slugObserver);
            }
        }
    }

    protected function registerBladeDirectives(): void
    {
        // @seoMeta — emits the canonical/OG/Twitter/JSON-LD partial for the
        // current request. For an explicit entity, templates can use:
        //   @include('seo.partials.meta-tags', ['entity' => $product, 'type' => 'product'])
        Blade::directive('seoMeta', function () {
            return "<?php try { echo \$__env->make('seo.partials.meta-tags', \\Illuminate\\Support\\Arr::except(get_defined_vars(), ['__data','__path']))->render(); } catch (\\Throwable \$e) { /* SEO meta-tags partial missing on this deploy — render nothing rather than crash */ } ?>";
        });

        // @schema($json) — emits a JSON-LD script tag from an array or string.
        Blade::directive('schema', function ($expression) {
            return "<?php try { \$__schemaPayload = {$expression};
                if (is_array(\$__schemaPayload)) { \$__schemaPayload = json_encode(\$__schemaPayload, JSON_UNESCAPED_SLASHES); }
                if (is_string(\$__schemaPayload) && trim(\$__schemaPayload) !== '') {
                    echo '<script type=\"application/ld+json\">' . \$__schemaPayload . '</script>';
                } } catch (\\Throwable \$e) {} ?>";
        });
    }

    /** Run a closure, swallowing any throwable so SEO boot never breaks the app. */
    protected function safe(string $phase, callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            // Best-effort logging — guarded in case logger is not yet ready.
            try {
                logger()->warning("SeoServiceProvider {$phase} failed; SEO features disabled this request.", [
                    'error' => $e->getMessage(),
                ]);
            } catch (Throwable $ignored) {
                // last-ditch — never bubble.
            }
        }
    }
}
