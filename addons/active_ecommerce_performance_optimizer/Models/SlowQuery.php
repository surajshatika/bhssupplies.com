<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class SlowQuery extends Model
{
    protected $table = 'perf_slow_queries';

    public $timestamps = false;

    protected $fillable = [
        'query_hash',
        'query_text',
        'avg_time_ms',
        'max_time_ms',
        'occurrences',
        'explain_result',
        'suggested_index',
        'first_seen',
        'last_seen',
    ];

    protected $casts = [
        'explain_result' => 'array',
        'avg_time_ms'    => 'integer',
        'max_time_ms'    => 'integer',
        'occurrences'    => 'integer',
        'first_seen'     => 'datetime',
        'last_seen'      => 'datetime',
    ];
}
