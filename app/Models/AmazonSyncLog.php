<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonSyncLog extends Model
{
    protected $fillable = [
        'product_id', 'amazon_product_id', 'action',
        'status', 'request_payload', 'response_payload', 'error_message',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function amazonProduct()
    {
        return $this->belongsTo(AmazonProduct::class, 'amazon_product_id');
    }
}
