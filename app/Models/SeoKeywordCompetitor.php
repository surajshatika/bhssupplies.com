<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoKeywordCompetitor extends Model
{
    protected $table = 'seo_keyword_competitors';

    protected $guarded = [];

    protected $casts = [
        'history'       => 'array',
        'rank_current'  => 'integer',
        'rank_previous' => 'integer',
    ];

    public function keyword()
    {
        return $this->belongsTo(SeoKeyword::class, 'seo_keyword_id');
    }

    public function recordRank(int $rank): void
    {
        $history = $this->history ?? [];
        $history[] = ['date' => now()->toDateString(), 'rank' => $rank];
        if (count($history) > 120) {
            $history = array_slice($history, -120);
        }

        $this->rank_previous = $this->rank_current;
        $this->rank_current  = $rank;
        $this->history       = $history;
        $this->save();
    }
}
