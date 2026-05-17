<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoProject extends Model
{
    protected $guarded = [];

    public function runs()
    {
        return $this->hasMany(SeoRun::class, 'project_id');
    }

    public function scoreHistories()
    {
        return $this->hasMany(SeoScoreHistory::class, 'project_id');
    }
}
