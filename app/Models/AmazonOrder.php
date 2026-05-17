<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonOrder extends Model
{
    protected $fillable = [
        'account_id', 'amazon_order_id', 'status',
        'buyer_email', 'buyer_name', 'total_amount',
        'currency', 'order_items', 'shipped_at', 'raw_data',
    ];

    protected $casts = [
        'order_items' => 'array',
        'raw_data'    => 'array',
        'shipped_at'  => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(AmazonAccount::class, 'account_id');
    }
}
