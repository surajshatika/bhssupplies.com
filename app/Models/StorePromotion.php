<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed promotional tiles (sale flyers, "80% OFF" banners, etc.)
 * rendered on the public /promotions store page.
 */
class StorePromotion extends Model
{
    protected $table = 'store_promotions';

    protected $guarded = [];

    protected $casts = [
        'published'  => 'boolean',
        'featured'   => 'boolean',
        'sort_order' => 'integer',
        'starts_at'  => 'date',
        'ends_at'    => 'date',
    ];

    /** Only promotions that are published and inside their (optional) date window. */
    public function scopeActiveNow(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('published', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            });
    }
}
