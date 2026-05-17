<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonToken extends Model
{
    protected $fillable = ['account_id', 'refresh_token', 'access_token', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function account()
    {
        return $this->belongsTo(AmazonAccount::class, 'account_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }
}
