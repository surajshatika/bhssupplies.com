<?php

namespace App\Models\PerformanceOptimizer;

use Illuminate\Database\Eloquent\Model;

class WebVital extends Model
{
    protected $table = 'performance_web_vitals';

    protected $fillable = [
        'url', 'metric', 'value', 'rating', 'device', 'connection', 'user_agent',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    public static function thresholds(): array
    {
        return [
            'LCP' => ['good' => 2500,  'needs' => 4000],
            'FID' => ['good' => 100,   'needs' => 300],
            'CLS' => ['good' => 0.1,   'needs' => 0.25],
            'INP' => ['good' => 200,   'needs' => 500],
            'FCP' => ['good' => 1800,  'needs' => 3000],
            'TTFB'=> ['good' => 800,   'needs' => 1800],
        ];
    }

    public static function ratingFor(string $metric, float $value): string
    {
        $t = self::thresholds()[$metric] ?? null;
        if (!$t) return 'unknown';
        if ($value <= $t['good'])  return 'good';
        if ($value <= $t['needs']) return 'needs-improvement';
        return 'poor';
    }
}
