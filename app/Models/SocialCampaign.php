<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SocialCampaign extends Model
{
    protected $fillable = [
        'name', 'type', 'status', 'ai_provider', 'tone',
        'topic', 'keywords', 'target_audience', 'platforms',
        'generated_content', 'auto_post', 'schedule_type',
        'cron_expression', 'scheduled_at', 'last_posted_at',
        'post_count', 'total_reach', 'created_by',
    ];

    protected $casts = [
        'platforms'         => 'array',
        'generated_content' => 'array',
        'auto_post'         => 'boolean',
        'scheduled_at'      => 'datetime',
        'last_posted_at'    => 'datetime',
    ];

    public function queuedPosts()
    {
        return $this->hasMany(SocialQueuedPost::class, 'campaign_id');
    }

    public function logs()
    {
        return $this->hasMany(SocialPostLog::class, 'campaign_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDueToPost(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('auto_post', true)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'badge-success',
            'draft'     => 'badge-secondary',
            'paused'    => 'badge-warning',
            'completed' => 'badge-info',
            'archived'  => 'badge-dark',
            default     => 'badge-light',
        };
    }
}
