<?php

namespace Tests\Unit;

use App\Services\Seo\Providers\NullProvider;
use App\Services\Seo\Providers\OpenAiCompatibleProvider;
use App\Services\Seo\Providers\SeoAiProviderInterface;
use App\Services\Seo\Providers\SeoProviderManager;
use App\Services\Seo\Advanced\EmbeddingKeywordClusterService;
use App\Services\Seo\Advanced\StreamingAiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Guards the provider registry against the drift that previously required the
 * same provider to be added by hand in five separate places.
 */
class SeoProviderRegistryTest extends TestCase
{
    public function test_every_registered_provider_constructs_and_reports_its_own_name(): void
    {
        foreach (SeoProviderManager::available() as $name) {
            $provider = SeoProviderManager::makeDirect($name);

            $this->assertInstanceOf(SeoAiProviderInterface::class, $provider, "{$name} must implement the provider interface");
            $this->assertNotInstanceOf(NullProvider::class, $provider, "{$name} fell through to NullProvider");
            $this->assertSame($name, $provider->getName(), "{$name} reports a mismatched name");
        }
    }

    public function test_every_registered_provider_has_config_and_metadata(): void
    {
        foreach (SeoProviderManager::available() as $name) {
            $this->assertIsArray(config("seo.providers.{$name}"), "{$name} has no config/seo.php entry");
            $this->assertNotEmpty(config("seo.providers.{$name}.endpoint"), "{$name} has no endpoint");
            $this->assertNotEmpty(config("seo.providers.{$name}.model"), "{$name} has no default model");

            // Cost estimates drive the AI Board budget guard; a missing entry
            // would silently price that provider's requests at zero.
            $this->assertNotNull(
                config("seo.provider_failover.attempt_cost_usd.{$name}"),
                "{$name} has no attempt_cost_usd estimate"
            );

            $this->assertArrayHasKey($name, SeoProviderManager::meta(), "{$name} missing from META");
        }
    }

    public function test_metadata_covers_exactly_the_registered_providers(): void
    {
        $this->assertSame(
            SeoProviderManager::available(),
            array_keys(SeoProviderManager::meta()),
            'META and PROVIDERS have drifted apart'
        );
    }

    public function test_provider_field_and_setting_names_are_unique(): void
    {
        $meta = SeoProviderManager::meta();

        $fields = array_column($meta, 'field');
        $settings = array_column($meta, 'setting');

        // A duplicate would make two providers overwrite each other's key.
        $this->assertSame(count($fields), count(array_unique($fields)), 'Duplicate form field name');
        $this->assertSame(count($settings), count(array_unique($settings)), 'Duplicate setting key');
    }

    public function test_claude_keeps_its_legacy_anthropic_setting_key(): void
    {
        // Renaming this would orphan every existing install's stored key.
        $meta = SeoProviderManager::meta();

        $this->assertSame('anthropic_api_key', $meta['claude']['field']);
        $this->assertSame('seo_anthropic_api_key', $meta['claude']['setting']);
    }

    public function test_grok_and_groq_are_distinct_providers(): void
    {
        // One letter apart, completely different vendors. Aliasing either way
        // would silently route traffic and spend to the wrong company.
        $this->assertSame('grok', SeoProviderManager::normalizeName('grok'));
        $this->assertSame('groq', SeoProviderManager::normalizeName('groq'));

        $this->assertStringContainsString('x.ai', config('seo.providers.grok.endpoint'));
        $this->assertStringContainsString('groq.com', config('seo.providers.groq.endpoint'));
    }

    public function test_aliases_resolve_and_unknown_names_are_rejected(): void
    {
        $this->assertSame('claude', SeoProviderManager::normalizeName('anthropic'));
        $this->assertSame('grok', SeoProviderManager::normalizeName('xai'));
        $this->assertSame('moonshot', SeoProviderManager::normalizeName('kimi'));
        $this->assertSame('qwen', SeoProviderManager::normalizeName('DashScope'));
        $this->assertSame('openai', SeoProviderManager::normalizeName('  ChatGPT  '));

        $this->assertNull(SeoProviderManager::normalizeName('not-a-provider'));
        $this->assertNull(SeoProviderManager::normalizeName(''));
        $this->assertInstanceOf(NullProvider::class, SeoProviderManager::makeDirect('not-a-provider'));
    }

    public function test_openai_compatible_providers_share_one_implementation(): void
    {
        // The point of the base class: these must not drift back into copies.
        foreach (['grok', 'perplexity', 'mistral', 'deepseek', 'groq', 'openrouter', 'together', 'fireworks', 'qwen', 'moonshot', 'cohere'] as $name) {
            $this->assertInstanceOf(
                OpenAiCompatibleProvider::class,
                SeoProviderManager::makeDirect($name),
                "{$name} should extend the shared OpenAI-compatible base"
            );
        }
    }

    public function test_a_new_provider_sends_the_expected_openai_shaped_request(): void
    {
        config([
            'seo.providers.groq.api_key'  => 'gsk-test-key',
            'seo.providers.groq.endpoint' => 'https://groq.test/v1/chat/completions',
            'seo.providers.groq.model'    => 'test-model',
        ]);

        Http::fake([
            'https://groq.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'Groq response']]],
            ], 200),
        ]);

        $result = SeoProviderManager::makeDirect('groq')->generate('Write meta.', 'You are an SEO expert.');

        $this->assertSame('Groq response', $result);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $request->url() === 'https://groq.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer gsk-test-key')
                && $body['model'] === 'test-model'
                && $body['messages'][0]['role'] === 'system'
                && $body['messages'][1]['content'] === 'Write meta.';
        });
    }

    public function test_openrouter_sends_its_attribution_headers(): void
    {
        config([
            'seo.providers.openrouter.api_key'  => 'sk-or-test',
            'seo.providers.openrouter.endpoint' => 'https://openrouter.test/v1/chat/completions',
        ]);

        Http::fake(['https://openrouter.test/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200)]);

        SeoProviderManager::makeDirect('openrouter')->generate('Hi');

        Http::assertSent(fn($request) => $request->hasHeader('HTTP-Referer') && $request->hasHeader('X-Title'));
    }

    public function test_an_unconfigured_provider_returns_null_without_calling_out(): void
    {
        config(['seo.providers.fireworks.api_key' => null]);
        Http::fake();

        $this->assertNull(SeoProviderManager::makeDirect('fireworks')->generate('Hi'));
        Http::assertNothingSent();
    }

    public function test_streaming_and_embedding_lists_only_name_real_providers(): void
    {
        $registered = SeoProviderManager::available();

        foreach (StreamingAiService::STREAMING_PROVIDERS as $name) {
            $this->assertContains($name, $registered, "Streaming lists unknown provider {$name}");
        }

        foreach (array_keys(EmbeddingKeywordClusterService::SUPPORTED) as $name) {
            $this->assertContains($name, $registered, "Embeddings list unknown provider {$name}");
        }
    }
}
