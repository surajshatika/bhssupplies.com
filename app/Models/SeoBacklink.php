<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoBacklink extends Model
{
    public const STATUSES = ['prospect', 'contacted', 'submitted', 'live', 'lost', 'rejected'];
    public const TYPES = ['citation', 'directory', 'association', 'supplier', 'press', 'blog', 'other'];

    protected $guarded = ['id'];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'first_seen_live_at' => 'datetime',
        'lost_at' => 'datetime',
    ];

    public static function domainOf(?string $url): string
    {
        $host = parse_url(str_contains((string) $url, '://') ? $url : 'https://' . $url, PHP_URL_HOST) ?: (string) $url;
        return preg_replace('/^www\./i', '', strtolower(trim($host)));
    }
}
