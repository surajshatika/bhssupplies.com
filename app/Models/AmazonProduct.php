<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonProduct extends Model
{
    protected $fillable = [
        'product_id', 'account_id', 'amazon_sku',
        'asin', 'status', 'last_synced_at', 'error_message',
    ];

    protected $casts = ['last_synced_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(AmazonAccount::class, 'account_id');
    }

    public function logs()
    {
        return $this->hasMany(AmazonSyncLog::class, 'amazon_product_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
