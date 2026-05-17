<?php

namespace App\Services\Marketing;

use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleReviewService
{
    protected string $storagePath = 'marketing/google_reviews.json';

    protected function absPath(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->storagePath));
    }

    public function isEnabled(): bool
    {
        return (int) get_setting('google_reviews_enabled') === 1
            && !empty($this->placeId())
            && !empty($this->apiKey());
    }

    public function placeId(): ?string
    {
        return env('GOOGLE_PLACE_ID') ?: null;
    }

    public function apiKey(): ?string
    {
        return env('GOOGLE_PLACES_API_KEY') ?: null;
    }

    public function fetchFromGoogle(?string $language = null): array
    {
        $placeId = $this->placeId();
        $apiKey  = $this->apiKey();

        if (!$placeId || !$apiKey) {
            return [
                'success' => false,
                'error'   => 'Missing Place ID or API Key. Please configure them first.',
            ];
        }

        $language = $language ?: (get_setting('google_reviews_language') ?: 'en');
        $sortOrder = get_setting('google_reviews_sort') ?: 'most_relevant';

        try {
            $response = Http::timeout(20)->retry(2, 500)->get(
                'https://maps.googleapis.com/maps/api/place/details/json',
                [
                    'place_id' => $placeId,
                    'fields'   => 'name,rating,user_ratings_total,reviews,url,formatted_address,formatted_phone_number,website,types',
                    'reviews_sort'    => $sortOrder,
                    'reviews_no_translations' => false,
                    'language' => $language,
                    'key'      => $apiKey,
                ]
            );

            if (!$response->successful()) {
                Log::warning('[GoogleReviewService] Non-200 from Places API', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error'   => 'Google API returned HTTP '.$response->status(),
                ];
            }

            $payload = $response->json();
            $status  = $payload['status'] ?? 'UNKNOWN';

            if ($status !== 'OK') {
                return [
                    'success' => false,
                    'error'   => 'Google API status: '.$status.' '.($payload['error_message'] ?? ''),
                ];
            }

            $result = $payload['result'] ?? [];
            return [
                'success' => true,
                'data'    => $this->normalize($result),
            ];
        } catch (\Throwable $e) {
            Log::error('[GoogleReviewService] fetch failed', [
                'message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function syncAndStore(?string $language = null): array
    {
        $result = $this->fetchFromGoogle($language);

        if (!$result['success']) {
            return $result;
        }

        $payload = [
            'fetched_at' => Carbon::now()->toIso8601String(),
            'language'   => $language ?: (get_setting('google_reviews_language') ?: 'en'),
            'place_id'   => $this->placeId(),
            'business'   => $result['data'],
        ];

        $path = $this->absPath();
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
        file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        BusinessSetting::updateOrCreate(
            ['type' => 'google_reviews_last_synced_at'],
            ['value' => Carbon::now()->toDateTimeString()]
        );
        BusinessSetting::updateOrCreate(
            ['type' => 'google_reviews_last_synced_count'],
            ['value' => count($result['data']['reviews'] ?? [])]
        );

        return [
            'success' => true,
            'data'    => $payload,
            'count'   => count($result['data']['reviews'] ?? []),
        ];
    }

    public function load(): ?array
    {
        $path = $this->absPath();
        if (!is_file($path)) return null;
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    public function clear(): void
    {
        $path = $this->absPath();
        if (is_file($path)) @unlink($path);
    }

    public function summary(): array
    {
        $stored = $this->load();
        if (!$stored) {
            return [
                'rating' => null,
                'total'  => 0,
                'reviews' => [],
                'fetched_at' => null,
                'place_url'  => null,
                'business_name' => null,
                'distribution'  => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            ];
        }

        $business = $stored['business'] ?? [];
        $reviews  = $business['reviews'] ?? [];

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($reviews as $r) {
            $rating = (int) ($r['rating'] ?? 0);
            if (isset($distribution[$rating])) {
                $distribution[$rating]++;
            }
        }

        return [
            'rating'        => $business['rating'] ?? null,
            'total'         => (int) ($business['user_ratings_total'] ?? 0),
            'reviews'       => $reviews,
            'fetched_at'    => $stored['fetched_at'] ?? null,
            'place_url'     => $business['url'] ?? null,
            'business_name' => $business['name'] ?? null,
            'address'       => $business['formatted_address'] ?? null,
            'phone'         => $business['formatted_phone_number'] ?? null,
            'website'       => $business['website'] ?? null,
            'distribution'  => $distribution,
        ];
    }

    protected function normalize(array $result): array
    {
        $reviews = [];
        foreach (($result['reviews'] ?? []) as $review) {
            $reviews[] = [
                'author_name'              => $review['author_name'] ?? 'Anonymous',
                'author_url'               => $review['author_url'] ?? null,
                'profile_photo_url'        => $review['profile_photo_url'] ?? null,
                'rating'                   => (int) ($review['rating'] ?? 0),
                'text'                     => $review['text'] ?? '',
                'relative_time_description'=> $review['relative_time_description'] ?? '',
                'time'                     => (int) ($review['time'] ?? 0),
                'language'                 => $review['language'] ?? null,
                'original_language'        => $review['original_language'] ?? null,
                'translated'               => (bool) ($review['translated'] ?? false),
            ];
        }

        return [
            'name'               => $result['name'] ?? null,
            'rating'             => isset($result['rating']) ? (float) $result['rating'] : null,
            'user_ratings_total' => (int) ($result['user_ratings_total'] ?? 0),
            'url'                => $result['url'] ?? null,
            'formatted_address'  => $result['formatted_address'] ?? null,
            'formatted_phone_number' => $result['formatted_phone_number'] ?? null,
            'website'            => $result['website'] ?? null,
            'types'              => $result['types'] ?? [],
            'reviews'            => $reviews,
        ];
    }
}
