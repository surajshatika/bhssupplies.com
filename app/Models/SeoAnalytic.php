<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAnalytic extends Model
{
    protected $table = 'seo_analytics';

    protected $guarded = [];

    protected $casts = [
        'date'        => 'date',
        'clicks'      => 'int',
        'impressions' => 'int',
        'ctr'         => 'float',
        'position'    => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $date  = $row->date instanceof \Carbon\Carbon ? $row->date->toDateString() : (string) $row->date;
            $row->row_hash = sha1($date . '|' . $row->source . '|' . $row->dimension . '|' . $row->value);
        });
    }
}
