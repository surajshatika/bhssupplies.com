<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AutomationRunCommand extends Command
{
    protected $signature = 'seo:automation-run
                            {--onpage-limit= : Pending URLs to optimize this run}
                            {--provider= : AI provider override}
                            {--force-all : Run interval-gated automation immediately}
                            {--dry-run : Show what would run without changing data}';

    protected $description = 'Master hourly SEO automation runner for pending SEO, off-page campaigns, technical refresh, rankings, GSC, PageSpeed, and broken links.';

    public function handle(): int
    {
        if ((int) get_setting('seo_master_automation_enabled', 1) !== 1 && !$this->option('dry-run')) {
            $this->info('Master SEO automation is disabled in SEO settings.');
            return self::SUCCESS;
        }

        $provider = $this->option('provider');
        $onpageLimit = $this->option('onpage-limit') ?: get_setting('seo_auto_seo_batch_size', 10);
        $forceAll = (bool) $this->option('force-all');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('SEO automation run started' . ($dryRun ? ' (dry run)' : '') . '.');

        $this->callSeoCommand('seo:auto-optimize-pending', array_filter([
            '--limit' => $onpageLimit,
            '--provider' => $provider,
            '--dry-run' => $dryRun ?: null,
        ]));

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
                } catch (\Throwable $e) {
                    $this->warn('Sitemap regeneration failed: ' . $e->getMessage());
                }
                $this->markRan('sitemap', false);
            }
        }

        if ($this->isDue('technical_refresh', 6, $forceAll, $dryRun)) {
            $this->callSeoCommand('seo:auto-technical-refresh', [
                '--sample' => 80,
                '--dry-run' => $dryRun ?: null,
            ]);
            $this->markRan('technical_refresh', $dryRun);
        }

        if ($this->isDue('offpage_campaign', (int) get_setting('seo_auto_offpage_interval_hours', 6), $forceAll, $dryRun)) {
            $this->callSeoCommand('seo:auto-offpage-campaign', array_filter([
                '--limit' => get_setting('seo_auto_offpage_batch_size', 3),
                '--provider' => $provider,
                '--dry-run' => $dryRun ?: null,
            ]));
            $this->markRan('offpage_campaign', $dryRun);
        }

        if ($this->isDue('search_console', 6, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:sync-search-console (due, skipped in dry run)');
            } else {
                $this->callSeoCommand('seo:sync-search-console', ['--days' => 7]);
                $this->markRan('search_console', false);
            }
        }

        if ($this->isDue('keyword_ranks', 6, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:check-keyword-ranks (due, skipped in dry run)');
            } else {
                $this->callSeoCommand('seo:check-keyword-ranks', ['--limit' => 50]);
                $this->markRan('keyword_ranks', false);
            }
        }

        if ($this->isDue('pagespeed', 12, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:pagespeed (due, skipped in dry run)');
            } else {
                $this->callSeoCommand('seo:pagespeed', ['--strategy' => 'mobile']);
                $this->markRan('pagespeed', false);
            }
        }

        if ($this->isDue('broken_links', 24, $forceAll, $dryRun)) {
            if ($dryRun) {
                $this->line('> php artisan seo:check-broken-links (due, skipped in dry run)');
            } else {
                $this->callSeoCommand('seo:check-broken-links', ['--limit' => 400, '--per-entity' => 10]);
                $this->markRan('broken_links', false);
            }
        }

        $this->info('SEO automation run finished.');
        return self::SUCCESS;
    }

    protected function callSeoCommand(string $command, array $arguments = []): void
    {
        $arguments = array_filter($arguments, fn($value) => $value !== null && $value !== false && $value !== '');
        $this->line('> php artisan ' . $command);
        $this->call($command, $arguments);
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
