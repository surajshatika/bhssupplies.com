<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use PreventDemoModeChanges;
    use SoftDeletes;

    protected $fillable = ['category_name', 'slug'];

    public function posts()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }
}
