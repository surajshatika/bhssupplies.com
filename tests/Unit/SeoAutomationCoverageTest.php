<?php

namespace Tests\Unit;

use App\Services\Seo\Automation\SeoAutomationCoverage;
use Tests\TestCase;

class SeoAutomationCoverageTest extends TestCase
{
    public function test_coverage_distinguishes_automatic_controls_from_approval_gated_actions(): void
    {
        $coverage = (new SeoAutomationCoverage())->summary();
        $groups = collect($coverage['groups'])->keyBy('key');

        $this->assertGreaterThanOrEqual(20, $coverage['automatic_count']);
        $this->assertGreaterThanOrEqual(5, $coverage['approval_count']);
        $this->assertSame('automatic', collect($groups['on_page']['items'])->firstWhere('label', 'Meta title and description')['mode']);
        $this->assertSame('approval', collect($groups['approval']['items'])->firstWhere('label', 'Third-party backlink placement')['mode']);
    }

    public function test_technical_refresh_includes_specialized_sitemaps_and_safe_audits(): void
    {
        $source = file_get_contents(app_path('Console/Commands/Seo/AutoTechnicalRefreshCommand.php'));

        $this->assertStringContainsString('Video sitemap', $source);
        $this->assertStringContainsString('Google News sitemap', $source);
        $this->assertStringContainsString("'canonical_home'", $source);
        $this->assertStringContainsString("'redirect_audit'", $source);
        $this->assertStringContainsString("'local_seo'", $source);
        $this->assertStringContainsString("'webmaster_tools'", $source);
    }

    public function test_master_automation_includes_api_only_index_coverage_verification(): void
    {
        $runner = file_get_contents(app_path('Console/Commands/Seo/AutomationRunCommand.php'));
        $command = file_get_contents(app_path('Console/Commands/Seo/AutoCheckIndexCoverageCommand.php'));
        $service = file_get_contents(app_path('Services/Seo/Optimization/Features/PostIndexStatusService.php'));

        $this->assertStringContainsString("seo:auto-index-coverage", $runner);
        $this->assertStringContainsString("'require_api' => true", $command);
        $this->assertStringContainsString("'generate_advice' => false", $command);
        $this->assertStringNotContainsString('webcache.googleusercontent.com', $service);
    }
}
