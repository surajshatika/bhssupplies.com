<?php

namespace Tests\Unit;

use App\Services\Seo\Providers\FailoverSeoProvider;
use App\Services\Seo\Providers\OpenAIProvider;
use App\Services\Seo\Providers\SeoProviderManager;
use App\Services\Seo\Providers\SeoProviderReliability;
use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoProviderFailoverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'seo.provider_failover.database_settings' => false,
            'seo.provider_failover.enabled' => true,
            'seo.provider_failover.order' => ['claude', 'openai', 'gemini', 'grok'],
            'seo.provider_failover.max_attempts' => 4,
            'seo.provider_failover.cooldown_enabled' => true,
            'seo.provider_failover.failure_threshold' => 3,
            'seo.provider_failover.cooldown_minutes' => 15,
            // These tests assert exact HTTP call counts to prove failover
            // behaviour. Transport-level retries are a separate concern and
            // would otherwise multiply those counts, so pin them off here.
            'seo.provider_failover.http_retries' => 0,
            'seo.provider_failover.attempt_cost_usd.openai' => 0.0009,
            'seo.provider_failover.attempt_cost_usd.claude' => 0.0207,
            'seo.providers.openai.api_key' => 'openai-test-key',
            'seo.providers.openai.endpoint' => 'https://openai.test/v1/chat/completions',
            'seo.providers.claude.api_key' => 'claude-test-key',
            'seo.providers.claude.endpoint' => 'https://claude.test/v1/messages',
            'seo.providers.gemini.api_key' => 'gemini-test-key',
            'seo.providers.grok.api_key' => 'grok-test-key',
        ]);

        Cache::flush();
    }

    public function test_it_switches_to_the_next_configured_provider_when_primary_fails(): void
    {
        Http::fake([
            'https://openai.test/*' => Http::response(['error' => 'temporary failure'], 500),
            'https://claude.test/*' => Http::response(['content' => [['text' => 'Claude rescue response']]], 200),
        ]);

        $provider = SeoProviderManager::make('openai');
        $response = $provider->generate('Write SEO content.');

        $this->assertInstanceOf(FailoverSeoProvider::class, $provider);
        $this->assertSame('Claude rescue response', $response);
        $this->assertSame('claude', $provider->getName());
        $this->assertTrue($provider->usedFallback());
        $this->assertSame(['empty', 'success'], array_column($provider->getAttempts(), 'status'));
    }

    public function test_it_switches_provider_when_json_tool_receives_invalid_json(): void
    {
        Http::fake([
            'https://openai.test/*' => Http::response([
                'choices' => [['message' => ['content' => '<html>Unexpected response</html>']]],
            ], 200),
            'https://claude.test/*' => Http::response([
                'content' => [['text' => '{"title":"Valid SEO response"}']],
            ], 200),
        ]);

        $provider = SeoProviderManager::make('openai');
        $response = $provider->generate('Return JSON.', null, ['expect_json' => true]);

        $this->assertSame('{"title":"Valid SEO response"}', $response);
        $this->assertSame('claude', $provider->getName());
        $this->assertSame(['invalid_json', 'success'], array_column($provider->getAttempts(), 'status'));
    }

    public function test_it_uses_only_the_selected_provider_when_failover_is_disabled(): void
    {
        config(['seo.provider_failover.enabled' => false]);

        Http::fake([
            'https://openai.test/*' => Http::response(['error' => 'temporary failure'], 500),
            'https://claude.test/*' => Http::response(['content' => [['text' => 'Should not run']]], 200),
        ]);

        $provider = SeoProviderManager::make('openai');

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
        $this->assertNull($provider->generate('Write SEO content.'));
        Http::assertSentCount(1);
    }

    public function test_direct_provider_factory_never_wraps_health_checks(): void
    {
        $this->assertInstanceOf(OpenAIProvider::class, SeoProviderManager::makeDirect('chatgpt'));
    }

    public function test_json_services_report_the_provider_that_returned_the_usable_result(): void
    {
        Http::fake([
            'https://openai.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'invalid']]],
            ], 200),
            'https://claude.test/*' => Http::response([
                'content' => [['text' => '{"title":"Claude title"}']],
            ], 200),
        ]);

        $service = new class('openai') extends AbstractSeoService {
            public function run(): array
            {
                return $this->askForJson('Return JSON.', 'Output JSON only.', [
                    'title' => 'Template title',
                    'provider' => $this->providerName,
                ]);
            }
        };

        $result = $service->run();

        $this->assertSame('Claude title', $result['title']);
        $this->assertSame('claude', $result['provider']);
    }

    public function test_repeated_failures_cool_down_the_unhealthy_provider_and_keep_using_the_fallback(): void
    {
        config(['seo.provider_failover.failure_threshold' => 2]);

        Http::fake([
            'https://openai.test/*' => Http::response(['error' => 'temporary failure'], 500),
            'https://claude.test/*' => Http::response(['content' => [['text' => 'Claude rescue response']]], 200),
        ]);

        SeoProviderManager::make('openai')->generate('First SEO request.');
        SeoProviderManager::make('openai')->generate('Second SEO request.');
        $provider = SeoProviderManager::make('openai');
        $response = $provider->generate('Third SEO request.');

        $this->assertSame('Claude rescue response', $response);
        $this->assertSame(['cooldown', 'success'], array_column($provider->getAttempts(), 'status'));

        $health = app(SeoProviderReliability::class)->dashboard();
        $this->assertTrue($health['openai']['cooling_down']);
        $this->assertSame(2, $health['openai']['failures']);
        $this->assertSame(3, $health['claude']['fallback_selections']);
        Http::assertSentCount(5);
    }
}
