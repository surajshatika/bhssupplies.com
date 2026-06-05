<?php

namespace Tests\Unit;

use App\Console\Commands\Seo\ProcessAiSeoBatchesCommand;
use App\Jobs\Seo\AiAutoFixSeoJob;
use App\Models\Page;
use App\Models\SeoFixBatch;
use App\Services\Seo\SeoMetaResolver;
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

    public function test_cron_batch_processor_uses_container_injection_for_job_dependencies(): void
    {
        $source = file_get_contents(app_path('Console/Commands/Seo/ProcessAiSeoBatchesCommand.php'));

        $this->assertStringContainsString("app()->call([\$job, 'handle'])", $source);
        $this->assertStringNotContainsString('$job->handle(', $source);
        $this->assertStringContainsString("Cache::lock('seo:process-ai-batches:lock'", $source);
        $this->assertStringContainsString('MAX_AUTO_BATCH_TARGETS', $source);
        $this->assertStringContainsString("get_setting('seo_auto_seo_batch_size', 10)", $source);
    }

    public function test_zero_progress_failed_batch_does_not_consume_url_retry_allowance(): void
    {
        $board = new AiSeoBoardService();
        $method = new ReflectionMethod($board, 'attemptedTargetsForBatch');
        $method->setAccessible(true);
        $targets = [
            ['type' => 'page', 'id' => 10],
            ['type' => 'category', 'id' => 20],
            ['type' => 'product', 'id' => 30],
        ];

        $this->assertSame([], $method->invoke($board, new SeoFixBatch([
            'status' => SeoFixBatch::STATUS_FAILED,
            'processed' => 0,
            'target_ids' => $targets,
        ])));

        $this->assertSame([$targets[0]], $method->invoke($board, new SeoFixBatch([
            'status' => SeoFixBatch::STATUS_FAILED,
            'processed' => 1,
            'target_ids' => $targets,
        ])));
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

    public function test_on_page_advanced_patch_fills_safe_metadata_without_overwriting_curated_values(): void
    {
        $page = new Page();
        $page->id = 42;
        $page->slug = 'support-policy';
        $page->title = 'Support Policy';

        $board = new AiSeoBoardService();
        $method = new ReflectionMethod($board, 'advancedMetaPatch');
        $method->setAccessible(true);

        $patch = $method->invoke($board, $page, 'page', [
            'meta_title' => 'Support Policy Canada Guide 2026',
            'meta_description' => 'Support policy details for Canadian buyers with clear purchasing, service, and local assistance information from BHS Supplies.',
            'focus_keyword' => 'support policy Canada',
            'og_title' => 'Curated social title',
        ]);

        $this->assertStringEndsWith('/support-policy', $patch['canonical_url']);
        $this->assertArrayNotHasKey('og_title', $patch);
        $this->assertSame('website', $patch['og_type']);
        $this->assertSame('summary_large_image', $patch['twitter_card']);
        $this->assertSame('Support Policy Canada Guide 2026', $patch['twitter_title']);
        $this->assertSame('BreadcrumbList', $patch['breadcrumbs_json']['@type']);
    }

    public function test_contextual_internal_link_block_connects_core_conversion_and_local_pages_once(): void
    {
        $page = new Page();
        $page->id = 42;
        $page->slug = 'support-policy';
        $page->title = 'Support Policy';

        $board = new AiSeoBoardService();
        $method = new ReflectionMethod($board, 'seoLinkParagraph');
        $method->setAccessible(true);

        $html = $method->invoke($board, ['focus_keyword' => 'support policy Canada'], $page, 'page');

        $this->assertStringContainsString('data-seo-context-links="1"', $html);
        $this->assertStringContainsString('/shop', $html);
        $this->assertStringContainsString('/contractor-trade-account', $html);
        $this->assertStringContainsString('/review', $html);
        $this->assertStringContainsString('/hvac-supplies-', $html);
        $this->assertStringContainsString('canada.ca/en/services/business.html', $html);
    }

    public function test_resolver_can_detect_an_existing_breadcrumb_schema_node(): void
    {
        $resolver = new SeoMetaResolver();
        $method = new ReflectionMethod($resolver, 'schemasContainType');
        $method->setAccessible(true);

        $schemas = [
            ['@type' => 'WebPage'],
            ['@type' => 'BreadcrumbList'],
        ];

        $this->assertTrue($method->invoke($resolver, $schemas, 'BreadcrumbList'));
        $this->assertFalse($method->invoke($resolver, $schemas, 'FAQPage'));
    }

    public function test_unattended_on_page_quality_gate_rolls_back_only_score_regressions(): void
    {
        $board = new AiSeoBoardService();
        $method = new ReflectionMethod($board, 'shouldRollbackSeoMutation');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($board, ['score' => 52], ['score' => 51]));
        $this->assertFalse($method->invoke($board, ['score' => 52], ['score' => 52]));
        $this->assertFalse($method->invoke($board, ['score' => 52], ['score' => 73]));
    }

    public function test_manual_bulk_and_automated_queue_limits_are_intentionally_separate(): void
    {
        $board = new AiSeoBoardService();
        $controller = file_get_contents(app_path('Http/Controllers/Seo/AiSeoBoardController.php'));
        $automation = file_get_contents(app_path('Console/Commands/Seo/AutoOptimizePendingSeoCommand.php'));
        $restart = file_get_contents(app_path('Console/Commands/Seo/RestartAiSeoQueueCommand.php'));
        $kernel = file_get_contents(app_path('Console/Kernel.php'));

        $this->assertSame(10, $board::MAX_MANUAL_BATCH_TARGETS);
        $this->assertSame(100, $board::MAX_AUTO_BATCH_TARGETS);
        $this->assertSame(5, $board::MAX_AUTOPILOT_ATTEMPTS);
        $this->assertStringContainsString('MAX_MANUAL_BATCH_TARGETS', $controller);
        $this->assertStringContainsString('MAX_AUTO_BATCH_TARGETS', $automation);
        $this->assertStringContainsString("get_setting('seo_auto_seo_batch_size', 10)", $restart);
        $this->assertStringContainsString('seo:process-ai-batches --max-batches=1', $kernel);
        $this->assertStringNotContainsString('seo:process-ai-batches --limit=10', $kernel);
    }
}
