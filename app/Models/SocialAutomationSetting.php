<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SocialAutomationSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    protected static int $cacheTtl       = 300; // 5 min for non-sensitive
    protected static int $cacheTtlSecret = 60;  // 1 min for API keys

    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = Cache::remember("social_setting_{$key}", static::$cacheTtl, function () use ($key) {
            $row = static::where('key', $key)->first();
            // Re-cache secrets with shorter TTL immediately
            if ($row && $row->type === 'secret') {
                Cache::put("social_setting_{$key}", $row, static::$cacheTtlSecret);
            }
            return $row;
        });

        if (!$raw) return $default;

        return match ($raw->type) {
            'boolean' => filter_var($raw->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw->value,
            'json'    => json_decode($raw->value, true),
            'secret'  => $raw->value, // stored as-is
            default   => $raw->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;
        $ttl    = $type === 'secret' ? static::$cacheTtlSecret : static::$cacheTtl;
        Cache::forget("social_setting_{$key}");
        static::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type, 'group' => $group]);
        // Re-prime cache immediately after write so next ::get() hits cache
        $record = static::where('key', $key)->first();
        Cache::put("social_setting_{$key}", $record, $ttl);
    }

    public static function getMultiple(array $keys, mixed $default = ''): array
    {
        $rows    = static::whereIn('key', $keys)->get()->keyBy('key');
        $results = [];
        foreach ($keys as $key) {
            $row = $rows->get($key);
            if (!$row) {
                $results[$key] = $default;
                continue;
            }
            $results[$key] = match ($row->type) {
                'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $row->value,
                'json'    => json_decode($row->value, true),
                default   => $row->value,
            };
        }
        return $results;
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public static function bulkSet(array $data, string $group = 'general'): void
    {
        foreach ($data as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
            static::set($key, $value, $type, $group);
        }
    }
}
