<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'input_payload'  => 'array',
        'result_payload' => 'array',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(SeoProject::class, 'project_id');
    }
}
