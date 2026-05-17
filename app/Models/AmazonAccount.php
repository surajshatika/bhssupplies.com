<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonAccount extends Model
{
    protected $fillable = [
        'name', 'seller_id', 'marketplace_id',
        'lwa_client_id', 'lwa_client_secret',
        'aws_access_key', 'aws_secret_key', 'is_active',
    ];

    protected $hidden = ['lwa_client_id', 'lwa_client_secret', 'aws_access_key', 'aws_secret_key'];

    public function token()
    {
        return $this->hasOne(AmazonToken::class, 'account_id');
    }

    public function products()
    {
        return $this->hasMany(AmazonProduct::class, 'account_id');
    }

    public function orders()
    {
        return $this->hasMany(AmazonOrder::class, 'account_id');
    }
}
