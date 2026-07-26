<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use Illuminate\Support\Facades\Schema;

class Country extends Model
{
    use PreventDemoModeChanges;

    /**
     * Get the Zone that owns the Country
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeIsEnabled($query)
    {
        return $query->where('status', '1');
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function cities()
    {
        if (!Schema::hasColumn('cities', 'country_id')) {
            return $this->hasManyThrough(City::class, State::class);
        }

        return $this->hasMany(City::class);
    }
        
}
