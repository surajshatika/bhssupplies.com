<?php

namespace App\Services\Seo;

use App\Services\Marketing\GoogleReviewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Compares the business Name / Address / Phone / Website shown on the site and
 * in its LocalBusiness schema with the live Google Business Profile listing.
 * Mismatches weaken local rankings, so each field gets a match verdict.
 */
class NapConsistencyService
{
    public function check(bool $refresh = false): array
    {
        $google = $refresh ? null : Cache::get('seo:nap-google');
        if (!$google) {
            $google = $this->fetchGoogle();
            if (!empty($google['ok'])) {
                Cache::put('seo:nap-google', $google, now()->addHours(12));
            }
        }

        $lang = app()->getLocale();
        $site = [
            'name' => (string) (get_setting('seo_local_business_name') ?: get_setting('site_name')),
            'address' => (string) (get_setting('contact_address', null, $lang) ?: get_setting('contact_address')),
            'phone' => (string) get_setting('contact_phone'),
            'website' => (string) (config('seo.site_url') ?: config('app.url')),
        ];

        if (empty($google['ok'])) {
            return ['ok' => false, 'error' => $google['error'] ?? 'Google listing unavailable', 'site' => $site, 'fields' => []];
        }

        $fields = [
            'name' => $this->nameMatch($site['name'], $google['name']),
            'address' => $this->addressTokens($site['address']) !== [] && $this->addressTokens($site['address']) === $this->addressTokens($google['address']),
            'phone' => $this->digits($site['phone']) !== '' && substr($this->digits($site['phone']), -10) === substr($this->digits($google['phone']), -10),
            'website' => $this->host($site['website']) !== '' && $this->host($site['website']) === $this->host($google['website']),
        ];

        $rows = [];
        foreach ($fields as $key => $match) {
            $rows[$key] = ['site' => $site[$key], 'google' => $google[$key] ?? '', 'match' => $match];
        }

        return ['ok' => true, 'fields' => $rows, 'maps_url' => $google['url'] ?? null,
            'rating' => $google['rating'] ?? null, 'reviews' => $google['reviews'] ?? null,
            'mismatches' => count(array_filter($fields, fn($m) => !$m))];
    }

    protected function fetchGoogle(): array
    {
        $svc = app(GoogleReviewService::class);
        if (!$svc->placeId() || !$svc->apiKey()) {
            return ['ok' => false, 'error' => 'GOOGLE_PLACE_ID / GOOGLE_PLACES_API_KEY not configured'];
        }
        try {
            $res = Http::timeout(15)->withOptions(['verify' => config('seo.ssl_verify', true)])
                ->get('https://maps.googleapis.com/maps/api/place/details/json', [
                    'place_id' => $svc->placeId(),
                    'fields' => 'name,formatted_address,international_phone_number,formatted_phone_number,website,url,rating,user_ratings_total',
                    'key' => $svc->apiKey(),
                ]);
            $r = $res->json('result');
            if (!$res->successful() || !$r) {
                return ['ok' => false, 'error' => 'Places API: ' . ($res->json('status') ?: 'HTTP ' . $res->status())];
            }
            return ['ok' => true, 'name' => $r['name'] ?? '', 'address' => $r['formatted_address'] ?? '',
                'phone' => $r['international_phone_number'] ?? ($r['formatted_phone_number'] ?? ''),
                'website' => $r['website'] ?? '', 'url' => $r['url'] ?? null,
                'rating' => $r['rating'] ?? null, 'reviews' => $r['user_ratings_total'] ?? null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Google's name should appear in (or equal) the site's business name, ignoring suffix text. */
    protected function nameMatch(string $site, string $google): bool
    {
        $s = $this->norm($site); $g = $this->norm($google);
        return $s !== '' && $g !== '' && ($s === $g || $s === $this->norm(preg_split('/\s*[|–—-]\s*/u', $site)[0] ?? ''));
    }

    protected function norm(string $v, string $mode = ''): string
    {
        $v = mb_strtolower(html_entity_decode($v));
        if ($mode === 'address') {
            $v = preg_replace('/,?\s*(canada|ca)\s*$/', '', $v);
            $map = ['/\broad\b/' => 'rd', '/\bstreet\b/' => 'st', '/\bavenue\b/' => 'ave', '/\bdrive\b/' => 'dr',
                '/\bboulevard\b/' => 'blvd', '/\b(unit|suite|ste)\b\.?/' => '#', '/\bontario\b/' => 'on'];
            $v = preg_replace(array_keys($map), array_values($map), $v);
        }
        return trim(preg_replace('/[^a-z0-9#]+/', ' ', $v));
    }

    protected function addressTokens(string $v): array
    {
        $t = array_values(array_unique(array_filter(explode(' ', str_replace('#', ' ', $this->norm($v, 'address'))))));
        sort($t);
        return $t;
    }

    protected function digits(string $v): string
    {
        return preg_replace('/\D/', '', $v);
    }

    protected function host(string $v): string
    {
        return preg_replace('/^www\./', '', strtolower((string) parse_url(str_contains($v, '://') ? $v : "https://$v", PHP_URL_HOST)));
    }
}
