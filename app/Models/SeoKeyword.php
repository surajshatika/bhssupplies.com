<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoKeyword extends Model
{
    protected $table = 'seo_keywords';

    protected $guarded = [];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_checked_at' => 'datetime',
        'history'         => 'array',
        'cpc_usd'         => 'float',
        'difficulty'      => 'float',
    ];

    public function recordRank(int $rank): void
    {
        $history = $this->history ?? [];
        $history[] = ['date' => now()->toDateString(), 'rank' => $rank];
        if (count($history) > 120) {
            $history = array_slice($history, -120);
        }

        $this->rank_previous   = $this->rank_current;
        $this->rank_current    = $rank;
        $this->rank_best       = $this->rank_best  === null ? $rank : min($this->rank_best,  $rank);
        $this->rank_worst      = $this->rank_worst === null ? $rank : max($this->rank_worst, $rank);
        $this->history         = $history;
        $this->last_checked_at = now();
        $this->save();
    }
}
