<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportConversation extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'guest_name', 'guest_email', 'status',
    ];

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(SupportMessage::class, 'conversation_id')->latestOfMany();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->guest_name ?? 'Guest';
    }

    public function getDisplayEmailAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->email;
        }
        return $this->guest_email ?? 'N/A';
    }

    public function unreadCount(): int
    {
        return $this->messages()->where('sender_type', 'user')->where('is_read', 0)->count();
    }
}
