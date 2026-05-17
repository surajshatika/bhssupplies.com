<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SocialQueuedPost extends Model
{
    protected $fillable = [
        'platform', 'campaign_id', 'content', 'options',
        'status', 'trigger', 'ai_provider', 'priority',
        'attempts', 'last_error', 'scheduled_at', 'processed_at',
    ];

    protected $casts = [
        'options'      => 'array',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(SocialCampaign::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    public function scopePriority(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('scheduled_at')->orderBy('created_at');
    }
}
