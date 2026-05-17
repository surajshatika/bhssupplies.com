<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoScoreHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metrics'     => 'array',
        'recorded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(SeoProject::class, 'project_id');
    }
}
