<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use PreventDemoModeChanges;
    use SoftDeletes;
    use HasSeoMeta;

    protected $fillable = [
        'category_id', 'title', 'slug', 'short_description', 'description',
        'banner', 'meta_title', 'meta_img', 'meta_description', 'meta_keywords',
        'status', 'news', 'event', 'going_on',
    ];

    public function category() {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

}
