<?php

namespace App\Console\Commands\Seo;

use App\Services\Seo\Budget\SeoBudgetGuard;
use App\Services\Seo\Providers\SeoProviderManager;
use App\Services\Seo\Ranking\RankerManager;
use App\Services\Seo\SearchConsole\GoogleSearchConsoleService;
use App\Services\Seo\Speed\CloudflareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * One-shot health check for the SEO suite. Prints a green/red status line for
 * every dependency and feature so admins can see at a glance what's wired up
 * and what still needs configuration.
 *
 *   php artisan seo:doctor
 */
class DoctorCommand extends Command
{
    protected $signature = 'seo:doctor {--json : Output results as JSON}';

    protected $description = 'Diagnose the AI SEO Suite — verify migrations, providers, queue, integrations and observers.';

    protected array $results = [];

    public function handle(): int
    {
        $this->checkMigrations();
        $this->checkAiProviders();
        $this->checkQueue();
        $this->checkSitemap();
        $this->checkRobots();
        $this->checkRedirectMiddleware();
        $this->checkObservers();
        $this->checkSearchConsole();
        $this->checkSerpRanker();
        $this->checkCloudflare();
        $this->checkIndexNow();
        $this->checkBudgetGuard();
        $this->checkCacheDriver();

        if ($this->option('json')) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->printSummary();
        }

        $failed = collect($this->results)->where('status', 'fail')->count();
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Checks ────────────────────────────────────────────────────────────────

    protected function checkMigrations(): void
    {
        $tables = [
            'seo_projects', 'seo_runs', 'seo_redirects', 'seo_score_histories',
            'seo_meta', 'seo_fix_batches', 'seo_keywords', 'seo_analytics',
            'seo_broken_links', 'on_page_seo_pages', 'on_page_seo_tasks',
        ];
        $missing = array_values(array_filter($tables, fn($t) => !Schema::hasTable($t)));

        $this->result('migrations',
            empty($missing) ? 'ok' : 'fail',
            empty($missing) ? count($tables) . ' SEO tables present' : 'Missing tables: ' . implode(', ', $missing)
        );
    }

    protected function checkAiProviders(): void
    {
        $providers = ['openai', 'claude', 'gemini', 'grok'];
        $configured = [];
        foreach ($providers as $name) {
            try {
                $p = SeoProviderManager::make($name);
                if (method_exists($p, 'isConfigured') && $p->isConfigured()) {
                    $configured[] = $name;
                }
            } catch (Throwable $e) {}
        }

        $this->result('ai_providers',
            empty($configured) ? 'warn' : 'ok',
            empty($configured) ? 'No AI provider keys configured — Board will use template fallback'
                : 'Configured: ' . implode(', ', $configured)
        );
    }

    protected function checkQueue(): void
    {
        $driver = config('queue.default');
        $isSync = $driver === 'sync';

        $this->result('queue',
            $isSync ? 'warn' : 'ok',
            'Driver=' . $driver . ($isSync ? ' (bulk fixes block the browser — set up queue worker for async)' : '')
        );
    }

    protected function checkSitemap(): void
    {
        $path = base_path('sitemap.xml');
        if (file_exists($path)) {
            $age = round((time() - filemtime($path)) / 3600, 1);
            $this->result('sitemap', $age > 168 ? 'warn' : 'ok', "sitemap.xml present ({$age}h old)");
        } else {
            $this->result('sitemap', 'fail', 'sitemap.xml missing — run php artisan seo:generate-sitemap');
        }
    }

    protected function checkRobots(): void
    {
        $path = public_path('robots.txt');
        $this->result('robots',
            file_exists($path) ? 'ok' : 'warn',
            file_exists($path) ? 'robots.txt present' : 'robots.txt missing — run Robots.txt tool in admin'
        );
    }

    protected function checkRedirectMiddleware(): void
    {
        $kernel = file_get_contents(app_path('Http/Kernel.php'));
        $registered = $kernel && str_contains($kernel, 'SeoRedirectMiddleware::class');

        $this->result('redirect_middleware',
            $registered ? 'ok' : 'fail',
            $registered ? 'Registered in web middleware group' : 'NOT in web middleware — 301s won\'t fire'
        );
    }

    protected function checkObservers(): void
    {
        $provider = file_get_contents(app_path('Providers/SeoServiceProvider.php'));
        $hasObservers = $provider && str_contains($provider, 'SeoEntitySlugObserver');

        $this->result('observers',
            $hasObservers ? 'ok' : 'fail',
            $hasObservers ? 'Slug-change observer wired' : 'Observers missing in SeoServiceProvider'
        );
    }

    protected function checkSearchConsole(): void
    {
        $gsc = app(GoogleSearchConsoleService::class);
        $this->result('search_console',
            $gsc->isConfigured() ? 'ok' : 'warn',
            $gsc->isConfigured() ? 'Configured for ' . $gsc->siteUrl() : 'Not configured — paste GSC refresh_token in settings'
        );
    }

    protected function checkSerpRanker(): void
    {
        $r = RankerManager::make();
        $this->result('serp_ranker',
            $r->isConfigured() ? 'ok' : 'warn',
            $r->isConfigured() ? "Ranker={$r->name()} configured" : "Ranker={$r->name()} key missing — keyword tracking inactive"
        );
    }

    protected function checkCloudflare(): void
    {
        $cf = app(CloudflareService::class);
        $this->result('cloudflare',
            $cf->isConfigured() ? 'ok' : 'warn',
            $cf->isConfigured() ? 'API token + zone configured' : 'Not configured — page-level edge cache purge disabled'
        );
    }

    protected function checkIndexNow(): void
    {
        $key = get_setting('seo_indexnow_key');
        $auto = (int) get_setting('seo_auto_indexnow', 0);

        $this->result('indexnow',
            $key ? 'ok' : 'warn',
            ($key ? 'Key set' : 'No key') . ($auto ? ' • auto-ping ON' : ' • auto-ping OFF')
        );
    }

    protected function checkBudgetGuard(): void
    {
        $guard = app(SeoBudgetGuard::class);
        $cap   = $guard->dailyCapUsd();
        $spent = $guard->spendToday();

        $this->result('budget',
            $cap > 0 ? 'ok' : 'warn',
            $cap > 0
                ? sprintf('Cap $%.2f/day — spent today $%.4f', $cap, $spent)
                : 'No daily cap set — AI spend is unbounded. Set seo_daily_budget_usd.'
        );
    }

    protected function checkCacheDriver(): void
    {
        $driver = config('cache.default');
        $tagSupport = in_array($driver, ['redis', 'memcached'], true);

        $this->result('cache_driver',
            'ok',
            "Driver={$driver}" . ($tagSupport ? ' (tag support: native)' : ' (tag support: emulated via index — works but slower)')
        );
    }

    // ── Output ────────────────────────────────────────────────────────────────

    protected function result(string $check, string $status, string $message): void
    {
        $this->results[$check] = ['status' => $status, 'message' => $message];
    }

    protected function printSummary(): void
    {
        $this->newLine();
        $this->line('  AI SEO Suite — Doctor Report');
        $this->line('  ' . str_repeat('─', 60));

        $icons = [
            'ok'   => "  <fg=green>✓</> ",
            'warn' => "  <fg=yellow>!</> ",
            'fail' => "  <fg=red>✗</> ",
        ];

        foreach ($this->results as $check => $info) {
            $icon = $icons[$info['status']] ?? '  ? ';
            $name = str_pad($check, 22);
            $this->line($icon . "<options=bold>{$name}</> {$info['message']}");
        }

        $this->newLine();
        $counts = collect($this->results)->groupBy('status')->map->count();
        $this->line(sprintf(
            '  ok=%d  warn=%d  fail=%d',
            $counts['ok']   ?? 0,
            $counts['warn'] ?? 0,
            $counts['fail'] ?? 0,
        ));
        $this->newLine();
    }
}
