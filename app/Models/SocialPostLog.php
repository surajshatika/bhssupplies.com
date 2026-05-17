<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SocialPostLog extends Model
{
    protected $fillable = [
        'platform', 'trigger', 'campaign_id', 'ai_provider',
        'content_sent', 'status', 'response', 'post_id',
        'post_url', 'retry_count', 'posted_at',
    ];

    protected $casts = ['posted_at' => 'datetime'];

    public function scopePlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success'      => 'badge-success',
            'failed'       => 'badge-danger',
            'skipped'      => 'badge-warning',
            'queued'       => 'badge-info',
            'rate_limited' => 'badge-secondary',
            default        => 'badge-light',
        };
    }

    public static function stats(): array
    {
        return [
            'total'   => static::count(),
            'success' => static::where('status', 'success')->count(),
            'failed'  => static::where('status', 'failed')->count(),
            'today'   => static::whereDate('created_at', today())->count(),
        ];
    }
}
