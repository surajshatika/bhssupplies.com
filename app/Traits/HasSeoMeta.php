<?php

namespace App\Traits;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds a polymorphic seo_meta relationship to a model.
 *
 * Provides:
 *   - $model->seoMeta              (default-locale row, auto-created on access)
 *   - $model->allSeoMeta           (all locales)
 *   - $model->seoMetaFor($lang)    (specific locale)
 *
 * The trait never writes to seo_meta itself; callers control persistence.
 */
trait HasSeoMeta
{
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'model')
            ->where('lang', $this->seoMetaDefaultLang());
    }

    public function allSeoMeta(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'model');
    }

    public function seoMetaFor(string $lang): ?SeoMeta
    {
        return $this->allSeoMeta()->where('lang', $lang)->first();
    }

    public function ensureSeoMeta(string $lang = null): SeoMeta
    {
        $lang = $lang ?: $this->seoMetaDefaultLang();
        return $this->allSeoMeta()->firstOrCreate(['lang' => $lang]);
    }

    protected function seoMetaDefaultLang(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Accessor — prefers the seo_meta row's meta_title over the inline column.
     * Returning the inline value untouched when seo_meta is empty keeps every
     * existing template compatible.
     */
    public function getMetaTitleAttribute($value)
    {
        $override = $this->resolveSeoMetaField('meta_title');
        return $override !== null ? $override : $value;
    }

    public function getMetaDescriptionAttribute($value)
    {
        $override = $this->resolveSeoMetaField('meta_description');
        return $override !== null ? $override : $value;
    }

    /**
     * Single source for fetching a seo_meta field with relation caching.
     * The default-locale row is loaded once per model instance.
     */
    protected function resolveSeoMetaField(string $field): ?string
    {
        if (!isset($this->relations['seoMeta'])) {
            try {
                $this->load('seoMeta');
            } catch (\Throwable $e) {
                return null;
            }
        }
        $meta = $this->getRelation('seoMeta');
        if (!$meta) {
            return null;
        }
        $val = $meta->{$field} ?? null;
        return (is_string($val) && trim($val) !== '') ? $val : null;
    }
}
