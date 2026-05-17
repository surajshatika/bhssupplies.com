<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoBrokenLink extends Model
{
    protected $table = 'seo_broken_links';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at'   => 'datetime',
        'last_checked_at' => 'datetime',
        'resolved_at'     => 'datetime',
    ];
}
