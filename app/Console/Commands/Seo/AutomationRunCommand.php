<?php

namespace App\Console\Commands\Seo;

use App\Services\Seo\Automation\SeoAutomationCoverage;
use App\Services\Seo\Board\AiSeoBoardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AutomationRunCommand extends Command
{
    protected $signature = 'seo:automation-run
                            {--onpage-limit= : Pending URLs to optimize this run}
                            {--batch-limit= : Active AI SEO URLs to process; defaults to backend Auto SEO URLs Per Run}
                            {--max-batches=1 : Active AI SEO batches to process}
                            {--provider= : AI provider override}
                            {--force-all : Run interval-gated automation immediately}
                            {--dry-run : Show what would run without changing data}';

    protected $description = 'Master hourly SEO automation runner for protected on-page SEO, white-hat off-page planning, technical refresh, index coverage, rankings, GSC, PageSpeed, and broken links.';

    public function handle(): int
    {
        if ((int) get_setting('seo_master_automation_enabled', 1) !== 1 && !$this->option('dry-run')) {
            $this->info('Master SEO automation is disabled in SEO settings.');
            return self::SUCCESS;
        }

        $provider = $this->option('provider');
        $onpageLimit = max(1, min(AiSeoBoardService::MAX_AUTO_BATCH_TARGETS, (int) ($this->option('onpage-limit') ?: get_setting('seo_auto_seo_batch_size', 10))));
        $batchLimit = max(1, min(AiSeoBoardService::MAX_AUTO_BATCH_TARGETS, (int) ($this->option('batch-limit') ?: get_setting('seo_auto_seo_batch_size', 10))));
        $maxBatches = 1;
        $forceAll = (bool) $this->option('force-all');
        $dryRun = (bool) $this->option('dry-run');
        $hasFailures = false;

        $this->info('SEO automation run started' . ($dryRun ? ' (dry run)' : '') . '.');
        $coverage = app(SeoAutomationCoverage::class)->summary();
        $this->line('Coverage: ' . $coverage['automatic_count'] . ' automatic controls; ' . $coverage['approval_count'] . ' external or routing actions remain approval-gated.');

        $hasFailures = $this->callSeoCommand('seo:auto-optimize-pending', array_filter([
            '--limit' => $onpageLimit,
            '--provider' => $provider,
            '--dry-run' => $dryRun ?: null,
        ])) !== self::SUCCESS || $hasFailures;

        $hasFailures = $this->callSeoCommand('seo:process-ai-batches', [
            '--limit' => $batchLimit,
            '--max-batches' => $maxBatches,
            '--dry-run' => $dryRun ?: null,
        ]) !== self::SUCCESS || $hasFailures;

        // Keep the on-disk sitemaps fresh (new products, image tags, dynamic
        // priorities) without waiting for the weekly snapshot.
        if ($this->isDue('sitemap', (int) get_setting('seo_auto_sitemap_interval_hours', 3), $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> regenerate XML sitemaps (due, skipped in dry run)');
            } else {
                try {
                    $result = app(\App\Services\Seo\Optimization\Features\SmartSitemapService::class)
                        ->handle(['persist' => true, 'split' => true, 'base_url' => url('/')]);
                    $this->info('Sitemaps regenerated: ' . ($result['url_count'] ?? 0) . ' URLs.');
                    $this->markRan('sitemap', false);
                } catch (\Throwable $e) {
                    $this->warn('Sitemap regeneration failed: ' . $e->getMessage());
                    $hasFailures = true;
                }
            }
        }

        if ($this->isDue('technical_refresh', 6, $forceAll, $dryRun)) {
            $exitCode = $this->callSeoCommand('seo:auto-technical-refresh', [
                '--sample' => 80,
                '--dry-run' => $dryRun ?: null,
            ]);
            if ($exitCode === self::SUCCESS) {
                $this->markRan('technical_refresh', $dryRun);
            } else {
                $hasFailures = true;
            }
        }

        if ($this->isDue('offpage_campaign', (int) get_setting('seo_auto_offpage_interval_hours', 6), $forceAll, $dryRun)) {
            $exitCode = $this->callSeoCommand('seo:auto-offpage-campaign', array_filter([
                '--limit' => get_setting('seo_auto_offpage_batch_size', 3),
                '--provider' => $provider,
                '--dry-run' => $dryRun ?: null,
            ]));
            if ($exitCode === self::SUCCESS) {
                $this->markRan('offpage_campaign', $dryRun);
            } else {
                $hasFailures = true;
            }
        }

        if ($this->isDue('search_console', 6, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:sync-search-console (due, skipped in dry run)');
            } else {
                $exitCode = $this->callSeoCommand('seo:sync-search-console', ['--days' => 7]);
                if ($exitCode === self::SUCCESS) {
                    $this->markRan('search_console', false);
                } else {
                    $hasFailures = true;
                }
            }
        }

        if ($this->isDue('keyword_ranks', 6, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:check-keyword-ranks (due, skipped in dry run)');
            } else {
                $exitCode = $this->callSeoCommand('seo:check-keyword-ranks', ['--limit' => 50]);
                if ($exitCode === self::SUCCESS) {
                    $this->markRan('keyword_ranks', false);
                } else {
                    $hasFailures = true;
                }
            }
        }

        if ($this->isDue('index_coverage', (int) get_setting('seo_auto_index_coverage_interval_hours', 24), $forceAll, $dryRun)) {
            $exitCode = $this->callSeoCommand('seo:auto-index-coverage', [
                '--limit' => get_setting('seo_auto_index_coverage_limit', 20),
                '--dry-run' => $dryRun ?: null,
            ]);
            if ($exitCode === self::SUCCESS) {
                $this->markRan('index_coverage', $dryRun);
            } else {
                $hasFailures = true;
            }
        }

        if ($this->isDue('pagespeed', 12, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:pagespeed (due, skipped in dry run)');
            } else {
                $exitCode = $this->callSeoCommand('seo:pagespeed', ['--strategy' => 'mobile']);
                if ($exitCode === self::SUCCESS) {
                    $this->markRan('pagespeed', false);
                } else {
                    $hasFailures = true;
                }
            }
        }

        if ($this->isDue('broken_links', 24, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:check-broken-links (due, skipped in dry run)');
            } else {
                $exitCode = $this->callSeoCommand('seo:check-broken-links', ['--limit' => 400, '--per-entity' => 10]);
                if ($exitCode === self::SUCCESS) {
                    $this->markRan('broken_links', false);
                } else {
                    $hasFailures = true;
                }
            }
        }

        if ($hasFailures) {
            $this->warn('SEO automation run finished with failures.');
            return self::FAILURE;
        }

        $this->info('SEO automation run finished successfully.');
        return self::SUCCESS;
    }

    protected function callSeoCommand(string $command, array $arguments = []): int
    {
        $arguments = array_filter($arguments, fn($value) => $value !== null && $value !== false && $value !== '');
        $printedArgs = collect($arguments)
            ->map(function ($value, $key) {
                return is_bool($value) ? (string) $key : $key . '=' . $value;
            })
            ->implode(' ');

        $this->line('> php artisan ' . $command . ($printedArgs ? ' ' . $printedArgs : ''));
        try {
            $exitCode = $this->call($command, $arguments);
        } catch (\Throwable $e) {
            $this->error($command . ' failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($exitCode !== self::SUCCESS) {
            $this->warn($command . ' exited with code ' . $exitCode . '.');
        }

        return $exitCode;
    }

    protected function isDue(string $key, int $intervalHours, bool $forceAll, bool $dryRun): bool
    {
        if ($forceAll || $dryRun) {
            return true;
        }

        $lastRun = Cache::get($this->cacheKey($key));
        if (!$lastRun) {
            return true;
        }

        return now()->diffInHours($lastRun) >= max(1, $intervalHours);
    }

    protected function markRan(string $key, bool $dryRun): void
    {
        if (!$dryRun) {
            Cache::put($this->cacheKey($key), now(), now()->addDays(14));
        }
    }

    protected function cacheKey(string $key): string
    {
        return 'seo:automation:last_run:' . $key;
    }
}
