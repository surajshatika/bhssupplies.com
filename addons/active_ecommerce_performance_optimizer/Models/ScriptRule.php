<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class ScriptRule extends Model
{
    protected $table = 'perf_script_rules';

    protected $fillable = [
        'script_pattern',
        'route_pattern',
        'device_filter',
        'action',
        'priority',
        'enabled',
        'note',
    ];

    protected $casts = [
        'enabled'  => 'boolean',
        'priority' => 'integer',
    ];

    public const ACTIONS = ['allow', 'deny', 'defer', 'async', 'delay'];
    public const DEVICES = ['any', 'mobile', 'desktop', 'tablet'];
}
