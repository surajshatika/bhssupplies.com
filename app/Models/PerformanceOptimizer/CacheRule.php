<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class CacheRule extends Model
{
    protected $table = 'perf_cache_rules';

    protected $fillable = [
        'pattern',
        'action',
        'ttl_minutes',
        'priority',
        'enabled',
        'note',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        'priority'    => 'integer',
        'ttl_minutes' => 'integer',
    ];

    public const ACTIONS = ['cache', 'bypass', 'vary_device', 'vary_locale'];
}
