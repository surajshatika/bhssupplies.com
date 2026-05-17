<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_name', 'message', 'is_read',
    ];

    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }
}
