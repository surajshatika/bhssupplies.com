<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class OptimizationLog extends Model
{
    protected $table = 'performance_optimization_logs';

    protected $fillable = [
        'type', 'action', 'status', 'target', 'meta', 'error_message', 'user_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
