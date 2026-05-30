<?php

namespace App\Services\Seo\Support;

use App\Services\Seo\Providers\SeoProviderManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class AbstractSeoService
{
    protected $provider;

    protected $providerName;

    public function __construct($providerName = null)
    {
        $this->provider = SeoProviderManager::make($providerName);
        $this->providerName = $this->provider->getName();
    }

    /** Return the underlying AI provider instance (shorthand for new services). */
    protected function ai()
    {
        return $this->provider;
    }

    protected function askForJson($prompt, $systemPrompt, array $fallback)
    {
        $response = $this->provider->generate($prompt, $systemPrompt);
        $parsed = $this->parseJson($response);

        if (!is_array($parsed) || empty($parsed)) {
            return $fallback;
        }

        return array_replace_recursive($fallback, $parsed);
    }

    protected function askForText($prompt, $systemPrompt, $fallback)
    {
        $response = $this->provider->generate($prompt, $systemPrompt);

        if (!is_string($response) || trim($response) === '') {
            return $fallback;
        }

        return trim($response);
    }

    protected function parseJson($content)
    {
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $content = trim($content);
        $content = preg_replace('/^```json/i', '', $content);
        $content = preg_replace('/^```/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (preg_match('/(\{.*\}|\[.*\])/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    protected function plainText($content)
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $content)));
    }

    protected function wordCount($content)
    {
        $plain = $this->plainText($content);
        return $plain === '' ? 0 : count(preg_split('/\s+/', $plain));
    }

    protected function slugWords($value)
    {
        return array_values(array_filter(preg_split('/[\s,\-]+/', strtolower((string) $value))));
    }

    protected function csvList($value)
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $value))));
    }

    protected function scoreFromChecks(array $checks)
    {
        if (empty($checks)) {
            return 0;
        }

        $score = 0;
        foreach ($checks as $check) {
            $status = Arr::get($check, 'status');
            if ($status === 'pass') {
                $score += 5;
            } elseif ($status === 'warning') {
                $score += 2;
            }
        }

        return max(0, min(100, $score));
    }

    protected function gradeFromScore($score): string
    {
        return match (true) {
            (int) $score >= 90 => 'A+',
            (int) $score >= 80 => 'A',
            (int) $score >= 70 => 'B',
            (int) $score >= 60 => 'C',
            (int) $score >= 50 => 'D',
            default            => 'F',
        };
    }

    protected function normalizeUrl($url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return url('/');
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url(ltrim($url, '/'));
    }
}
