<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class AutoFixHistory extends Model
{
    protected $table = 'perf_auto_fix_history';

    public $timestamps = false;

    protected $fillable = [
        'recommendation_id',
        'action',
        'before_state',
        'after_state',
        'applied_at',
        'rolled_back_at',
        'user_id',
    ];

    protected $casts = [
        'before_state'   => 'array',
        'after_state'    => 'array',
        'applied_at'     => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    public function recommendation()
    {
        return $this->belongsTo(AiRecommendation::class, 'recommendation_id');
    }
}
