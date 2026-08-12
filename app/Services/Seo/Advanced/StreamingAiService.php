<?php

namespace App\Services\Seo\Advanced;

use App\Services\Seo\Providers\SeoProviderManager;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-sent-events streaming for AI SEO content generation.
 *
 * Long generations (a 900-word article) otherwise sit behind a spinner for
 * 30+ seconds with no signal that anything is happening. Streaming shows
 * tokens as they arrive.
 *
 * Honesty note on provider support: OpenAI, DeepSeek, Mistral, Grok and
 * Claude expose genuine incremental token streams and are streamed for real.
 * Gemini and Perplexity are handled through the normal non-streaming call and
 * emitted as a single chunk. We do NOT simulate a typewriter effect for those
 * — a fake character-by-character reveal of an already-complete response looks
 * identical to real streaming while giving the user false information about
 * how fast the model is actually responding.
 */
class StreamingAiService
{
    /** Providers with a real incremental SSE token stream. */
    public const STREAMING_PROVIDERS = ['openai', 'deepseek', 'mistral', 'grok', 'claude'];

    /**
     * Stream a completion, invoking $onDelta for each text fragment.
     *
     * @param callable(string): void $onDelta
     * @return array{streamed: bool, chars: int, error: ?string}
     */
    public function stream(string $prompt, ?string $systemPrompt, string $provider, callable $onDelta): array
    {
        $provider = SeoProviderManager::normalizeName($provider) ?: 'openai';

        if (!in_array($provider, self::STREAMING_PROVIDERS, true)) {
            return $this->singleChunk($prompt, $systemPrompt, $provider, $onDelta);
        }

        try {
            return $provider === 'claude'
                ? $this->streamAnthropic($prompt, $systemPrompt, $onDelta)
                : $this->streamOpenAiCompatible($prompt, $systemPrompt, $provider, $onDelta);
        } catch (Throwable $e) {
            Log::warning('[SEO][Stream] failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return ['streamed' => false, 'chars' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * OpenAI-compatible SSE (OpenAI, DeepSeek, Mistral, Grok all share this
     * wire format: `data: {...}` lines terminated by `data: [DONE]`).
     */
    protected function streamOpenAiCompatible(string $prompt, ?string $systemPrompt, string $provider, callable $onDelta): array
    {
        $endpoint = config("seo.providers.{$provider}.endpoint");
        $model = config("seo.providers.{$provider}.model");
        $apiKey = $this->apiKey($provider);

        if (!$apiKey) {
            return ['streamed' => false, 'chars' => 0, 'error' => 'No API key configured for ' . $provider . '.'];
        }

        $payload = json_encode([
            'model'    => $model,
            'stream'   => true,
            'messages' => array_values(array_filter([
                $systemPrompt ? ['role' => 'system', 'content' => $systemPrompt] : null,
                ['role' => 'user', 'content' => $prompt],
            ])),
        ]);

        $chars = 0;
        $error = null;
        $buffer = '';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'Accept: text/event-stream',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => (bool) config('seo.ssl_verify', true),
            CURLOPT_CONNECTTIMEOUT => (int) config('seo.provider_failover.connect_timeout', 5),
            CURLOPT_TIMEOUT        => (int) config('seo.provider_failover.request_timeout', 45) * 3,
            CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$buffer, &$chars, $onDelta) {
                $buffer .= $chunk;

                // SSE events are newline-delimited; keep any partial trailing line.
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '' || !str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, 5));
                    if ($data === '[DONE]') {
                        continue;
                    }

                    $decoded = json_decode($data, true);
                    $delta = data_get($decoded, 'choices.0.delta.content');
                    if (is_string($delta) && $delta !== '') {
                        $chars += strlen($delta);
                        $onDelta($delta);
                    }
                }

                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($chars === 0 && $error === null) {
            $error = 'Provider returned no content (HTTP ' . $status . ').';
        }

        return ['streamed' => true, 'chars' => $chars, 'error' => $error];
    }

    /** Anthropic uses its own event names and a different delta shape. */
    protected function streamAnthropic(string $prompt, ?string $systemPrompt, callable $onDelta): array
    {
        $apiKey = $this->apiKey('claude');
        if (!$apiKey) {
            return ['streamed' => false, 'chars' => 0, 'error' => 'No Claude API key configured.'];
        }

        $payload = array_filter([
            'model'      => config('seo.providers.claude.model', 'claude-sonnet-4-6'),
            'max_tokens' => 4096,
            'stream'     => true,
            'system'     => $systemPrompt,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ], fn($v) => $v !== null);

        $chars = 0;
        $error = null;
        $buffer = '';

        $ch = curl_init(config('seo.providers.claude.endpoint', 'https://api.anthropic.com/v1/messages'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'Accept: text/event-stream',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => (bool) config('seo.ssl_verify', true),
            CURLOPT_CONNECTTIMEOUT => (int) config('seo.provider_failover.connect_timeout', 5),
            CURLOPT_TIMEOUT        => (int) config('seo.provider_failover.request_timeout', 45) * 3,
            CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$buffer, &$chars, $onDelta) {
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '' || !str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $decoded = json_decode(trim(substr($line, 5)), true);
                    if (data_get($decoded, 'type') === 'content_block_delta') {
                        $delta = data_get($decoded, 'delta.text');
                        if (is_string($delta) && $delta !== '') {
                            $chars += strlen($delta);
                            $onDelta($delta);
                        }
                    }
                }

                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }
        curl_close($ch);

        return ['streamed' => true, 'chars' => $chars, 'error' => $error];
    }

    /**
     * Non-streaming providers: do the ordinary call and emit the whole result
     * as one chunk, flagged so the UI can say so rather than implying a stream.
     */
    protected function singleChunk(string $prompt, ?string $systemPrompt, string $provider, callable $onDelta): array
    {
        try {
            $driver = app(SeoProviderManager::class)->makeDirect($provider);
            $text = (string) $driver->generate($prompt, $systemPrompt);

            if ($text === '') {
                return ['streamed' => false, 'chars' => 0, 'error' => 'Provider returned an empty response.'];
            }

            $onDelta($text);

            return ['streamed' => false, 'chars' => strlen($text), 'error' => null];
        } catch (Throwable $e) {
            return ['streamed' => false, 'chars' => 0, 'error' => $e->getMessage()];
        }
    }

    protected function apiKey(string $provider): ?string
    {
        $configured = config("seo.providers.{$provider}.api_key");
        if ($configured) {
            return $configured;
        }

        $map = [
            'openai'   => 'seo_openai_api_key',
            'claude'   => 'seo_anthropic_api_key',
            'grok'     => 'seo_grok_api_key',
            'mistral'  => 'seo_mistral_api_key',
            'deepseek' => 'seo_deepseek_api_key',
        ];

        if (function_exists('get_setting') && isset($map[$provider])) {
            return get_setting($map[$provider]) ?: null;
        }

        return null;
    }
}
