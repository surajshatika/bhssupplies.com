<?php

namespace App\Console\Commands\PerformanceOptimizer;

use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use Illuminate\Console\Command;

class PerfAutoCleanCommand extends Command
{
    protected $signature   = 'perf:auto-clean {--days= : Override keep-days from settings}';
    protected $description = 'Performance Optimizer: scheduled cleanup of old DB rows, expired tokens, old image backups';

    public function handle(DatabaseCleanerService $db, ImageOptimizerService $img): int
    {
        if (!function_exists('get_setting')) {
            $this->warn('Helpers not loaded — aborting.');
            return self::FAILURE;
        }
        if ((int) get_setting('perf_db_auto_clean_status', 0) !== 1) {
            $this->line('perf_db_auto_clean_status is OFF — skipping.');
            return self::SUCCESS;
        }

        $days = $this->option('days') !== null
            ? max(1, (int) $this->option('days'))
            : max(1, (int) get_setting('perf_db_auto_clean_keep_days', 30));

        $items = ['sessions', 'failed_jobs', 'password_resets', 'personal_tokens',
                  'old_notifications', 'old_carts', 'expired_otps'];
        $r = $db->clean($items);
        $totalRows = array_sum($r['deleted'] ?? []);
        $this->info("DB cleanup: deleted {$totalRows} rows.");

        $deletedBackups = $img->cleanOldBackups($days);
        $this->info("Image backups older than {$days}d: deleted {$deletedBackups} files.");

        return self::SUCCESS;
    }
}
