<?php

namespace App\Services\Seo\Providers;

use Illuminate\Support\Str;
use Throwable;

class FailoverSeoProvider implements SeoAiProviderInterface
{
    protected ?string $selectedProvider = null;

    protected array $attempts = [];

    public function __construct(protected ?string $preferredProvider = null)
    {
        $this->preferredProvider = SeoProviderManager::normalizeName($preferredProvider);
    }

    public function generate($prompt, $systemPrompt = null, array $options = [])
    {
        $this->selectedProvider = null;
        $this->attempts = [];
        $reliability = app(SeoProviderReliability::class);

        foreach (SeoProviderManager::fallbackOrder($this->preferredProvider) as $providerName) {
            if ($reliability->shouldSkip($providerName)) {
                $this->attempts[] = $this->attemptRecord($providerName, 'cooldown', 0, 0.0);
                continue;
            }

            $provider = SeoProviderManager::makeDirect($providerName);
            if (!$provider->isConfigured()) {
                $this->attempts[] = $this->attemptRecord($providerName, 'not_configured', 0, 0.0);
                continue;
            }

            $startedAt = microtime(true);
            try {
                $response = $provider->generate($prompt, $systemPrompt, $options);
            } catch (Throwable $e) {
                $durationMs = $this->durationMs($startedAt);
                $reliability->recordAttempt($providerName, 'exception', $e->getMessage(), $durationMs);
                $this->attempts[] = $this->attemptRecord(
                    $providerName,
                    'exception',
                    $durationMs,
                    $reliability->estimateAttemptCost($providerName)
                );
                logger()->warning('SEO AI provider failed; trying fallback', [
                    'provider' => $providerName,
                    'error' => Str::limit($e->getMessage(), 300),
                ]);
                continue;
            }

            $status = $this->responseStatus($response, $options);
            $durationMs = $this->durationMs($startedAt);
            $reliability->recordAttempt($providerName, $status, null, $durationMs);
            $this->attempts[] = $this->attemptRecord(
                $providerName,
                $status,
                $durationMs,
                $reliability->estimateAttemptCost($providerName)
            );

            if ($status !== 'success') {
                logger()->warning('SEO AI provider returned unusable output; trying fallback', [
                    'provider' => $providerName,
                    'status' => $status,
                ]);
                continue;
            }

            $this->selectedProvider = $provider->getName();
            if ($this->selectedProvider !== $this->preferredProvider) {
                $reliability->recordFallbackSelection($this->selectedProvider);
                logger()->info('SEO AI provider failover selected', [
                    'preferred' => $this->preferredProvider,
                    'selected' => $this->selectedProvider,
                    'attempts' => $this->attempts,
                ]);
            }

            return $response;
        }

        logger()->warning('All configured SEO AI providers returned unusable output', [
            'preferred' => $this->preferredProvider,
            'attempts' => $this->attempts,
        ]);

        return null;
    }

    public function isConfigured()
    {
        foreach (SeoProviderManager::fallbackOrder($this->preferredProvider) as $providerName) {
            if (SeoProviderManager::makeDirect($providerName)->isConfigured()) {
                return true;
            }
        }

        return false;
    }

    public function getName()
    {
        return $this->selectedProvider ?: $this->preferredProvider ?: 'fallback';
    }

    public function getAttempts(): array
    {
        return $this->attempts;
    }

    public function usedFallback(): bool
    {
        return $this->selectedProvider !== null
            && $this->preferredProvider !== null
            && $this->selectedProvider !== $this->preferredProvider;
    }

    protected function responseStatus($response, array $options): string
    {
        if (!is_string($response) || trim($response) === '') {
            return 'empty';
        }

        if (!empty($options['expect_json']) && !$this->containsValidJson($response)) {
            return 'invalid_json';
        }

        return 'success';
    }

    protected function containsValidJson(string $response): bool
    {
        $content = trim(preg_replace('/```(?:json)?|```/i', '', $response));
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return true;
        }

        if (!preg_match('/(\{.*\}|\[.*\])/s', $content, $matches)) {
            return false;
        }

        $decoded = json_decode($matches[1], true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    protected function attemptRecord(string $provider, string $status, int $durationMs, float $estimatedCostUsd): array
    {
        return [
            'provider' => $provider,
            'status' => $status,
            'duration_ms' => $durationMs,
            'estimated_cost_usd' => round($estimatedCostUsd, 6),
        ];
    }

    protected function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }
}
