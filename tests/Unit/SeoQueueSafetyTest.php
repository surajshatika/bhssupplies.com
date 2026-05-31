<?php

namespace Tests\Unit;

use App\Console\Commands\Seo\ProcessAiSeoBatchesCommand;
use App\Jobs\Seo\AiAutoFixSeoJob;
use App\Models\SeoFixBatch;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use Tests\TestCase;

class SeoQueueSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'seo.provider_failover.database_settings' => false,
            'seo.provider_failover.enabled' => true,
            'seo.provider_failover.order' => ['openai', 'claude', 'gemini', 'grok'],
            'seo.provider_failover.max_attempts' => 4,
            'seo.provider_failover.cooldown_enabled' => true,
            'seo.provider_failover.attempt_cost_usd.openai' => 0.0009,
            'seo.provider_failover.attempt_cost_usd.claude' => 0.0207,
            'seo.provider_failover.attempt_cost_usd.gemini' => 0.0004,
            'seo.provider_failover.attempt_cost_usd.grok' => 0.0023,
            'seo.providers.openai.api_key' => 'openai-test-key',
            'seo.providers.claude.api_key' => 'claude-test-key',
            'seo.providers.gemini.api_key' => 'gemini-test-key',
            'seo.providers.grok.api_key' => 'grok-test-key',
        ]);

        Cache::flush();
    }

    public function test_completed_seo_row_is_protected_at_the_done_threshold(): void
    {
        $board = new AiSeoBoardService();
        $method = new ReflectionMethod($board, 'isSeoDoneRow');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($board, [
            'has_meta' => true,
            'has_focus_kw' => true,
            'has_schema' => true,
            'score' => AiSeoBoardService::SEO_DONE_SCORE,
        ]));

        $this->assertFalse($method->invoke($board, [
            'has_meta' => true,
            'has_focus_kw' => true,
            'has_schema' => true,
            'score' => AiSeoBoardService::SEO_DONE_SCORE - 1,
        ]));
    }

    public function test_queue_compaction_removes_duplicate_pending_targets_from_newer_batches(): void
    {
        $older = new SeoFixBatch([
            'processed' => 1,
            'total' => 3,
            'target_ids' => [
                ['type' => 'page', 'id' => 10],
                ['type' => 'category', 'id' => 20],
                ['type' => 'product', 'id' => 30],
            ],
        ]);
        $newer = new SeoFixBatch([
            'processed' => 0,
            'total' => 3,
            'target_ids' => [
                ['type' => 'category', 'id' => 20],
                ['type' => 'product', 'id' => 30],
                ['type' => 'product', 'id' => 40],
            ],
        ]);

        $command = new ProcessAiSeoBatchesCommand();
        $method = new ReflectionMethod($command, 'compactDuplicatePendingTargets');
        $method->setAccessible(true);

        $removed = $method->invoke($command, new Collection([$older, $newer]), false);

        $this->assertSame(2, $removed);
        $this->assertSame(2, $older->remainingCount());
        $this->assertSame(3, $newer->remainingCount());
    }

    public function test_fallback_attempts_are_charged_individually(): void
    {
        $job = new AiAutoFixSeoJob(1);
        $method = new ReflectionMethod($job, 'estimatedAttemptCost');
        $method->setAccessible(true);

        $cost = $method->invoke($job, [
            'ai_attempt_details' => [
                ['provider' => 'openai', 'status' => 'empty', 'estimated_cost_usd' => 0.0009],
                ['provider' => 'claude', 'status' => 'success', 'estimated_cost_usd' => 0.0207],
                ['provider' => 'gemini', 'status' => 'cooldown', 'estimated_cost_usd' => 0.0004],
            ],
        ], 0.001);

        $this->assertSame(0.0216, $cost);
    }

    public function test_next_entity_budget_reserve_covers_the_available_fallback_chain(): void
    {
        $job = new AiAutoFixSeoJob(1);
        $method = new ReflectionMethod($job, 'reservedCostForNextEntity');
        $method->setAccessible(true);

        $cost = $method->invoke($job, new SeoFixBatch(['provider' => 'openai']));

        $this->assertSame(0.0243, $cost);
    }
}
