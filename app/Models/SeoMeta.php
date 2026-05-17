<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $guarded = [];

    protected $casts = [
        'secondary_keywords' => 'array',
        'schema_json'        => 'array',
        'breadcrumbs_json'   => 'array',
        'analysis_checks'    => 'array',
        'seo_score'          => 'float',
        'last_analyzed_at'   => 'datetime',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForEntity($query, string $type, $id, string $lang = 'en')
    {
        return $query->where('model_type', $type)
            ->where('model_id', $id)
            ->where('lang', $lang);
    }

    public function hasMeta(): bool
    {
        return filled($this->meta_title) && filled($this->meta_description);
    }

    public function hasOpenGraph(): bool
    {
        return filled($this->og_image);
    }

    public function hasSchema(): bool
    {
        return filled($this->schema_json);
    }

    public function hasFocusKeyword(): bool
    {
        return filled($this->focus_keyword);
    }
}
