<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class AiRecommendation extends Model
{
    protected $table = 'perf_ai_recommendations';

    protected $fillable = [
        'category', 'severity', 'title', 'body',
        'evidence', 'confidence',
        'auto_fixable', 'auto_fix_action', 'auto_fix_payload',
        'source', 'applied_at', 'dismissed_at',
    ];

    protected $casts = [
        'evidence'         => 'array',
        'auto_fix_payload' => 'array',
        'auto_fixable'     => 'boolean',
        'confidence'       => 'integer',
        'applied_at'       => 'datetime',
        'dismissed_at'     => 'datetime',
    ];

    public const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    public function scopePending($q) { return $q->whereNull('applied_at')->whereNull('dismissed_at'); }
}
