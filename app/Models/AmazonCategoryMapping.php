<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonCategoryMapping extends Model
{
    protected $fillable = [
        'website_category_id',
        'amazon_category_id',
        'amazon_category_name',
        'amazon_product_type',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'website_category_id');
    }
}
