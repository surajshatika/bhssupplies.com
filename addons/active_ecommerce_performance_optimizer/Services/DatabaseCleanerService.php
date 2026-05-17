<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseCleanerService
{
    public function getStats(): array
    {
        return [
            'sessions'         => $this->safeCount('sessions'),
            'failed_jobs'      => $this->safeCount('failed_jobs'),
            'jobs'             => $this->safeCount('jobs'),
            'password_resets'  => $this->safeCount('password_resets'),
            'personal_tokens'  => $this->safeCount('personal_access_tokens'),
            'old_notifications'=> $this->safeCount('notifications', 30),
            'old_carts'        => $this->safeCount('carts', 30, 'created_at'),
            'expired_otps'     => $this->expiredOtps(),
        ];
    }

    public function clean(array $items): array
    {
        $result = ['deleted' => [], 'failed' => []];

        foreach ($items as $item) {
            try {
                $deleted = 0;
                switch ($item) {
                    case 'sessions':
                        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'last_activity')) {
                            $deleted = DB::table('sessions')
                                ->where('last_activity', '<', now()->subDays(30)->timestamp)
                                ->delete();
                        }
                        break;
                    case 'failed_jobs':
                        if (Schema::hasTable('failed_jobs')) {
                            $deleted = DB::table('failed_jobs')->delete();
                        }
                        break;
                    case 'password_resets':
                        if (Schema::hasTable('password_resets')) {
                            $deleted = DB::table('password_resets')
                                ->where('created_at', '<', now()->subDays(7))
                                ->delete();
                        }
                        break;
                    case 'old_notifications':
                        if (Schema::hasTable('notifications')) {
                            $deleted = DB::table('notifications')
                                ->whereNotNull('read_at')
                                ->where('created_at', '<', now()->subDays(30))
                                ->delete();
                        }
                        break;
                    case 'old_carts':
                        if (Schema::hasTable('carts')) {
                            $deleted = DB::table('carts')
                                ->where('created_at', '<', now()->subDays(30))
                                ->delete();
                        }
                        break;
                    case 'expired_otps':
                        if (Schema::hasTable('otp_email')) {
                            $deleted = DB::table('otp_email')
                                ->where('created_at', '<', now()->subHours(24))
                                ->delete();
                        }
                        break;
                    case 'personal_tokens':
                        if (Schema::hasTable('personal_access_tokens')) {
                            $deleted = DB::table('personal_access_tokens')
                                ->whereNotNull('expires_at')
                                ->where('expires_at', '<', now())
                                ->delete();
                        }
                        break;
                }
                $result['deleted'][$item] = $deleted;
            } catch (Exception $e) {
                $result['failed'][$item] = $e->getMessage();
                Log::error('[PerfOptimizer] DB clean failed: ' . $item . ' — ' . $e->getMessage());
            }
        }

        OptimizationLog::create([
            'type' => 'db_clean', 'action' => 'clean', 'status' => 'success', 'meta' => $result,
        ]);
        return $result;
    }

    public function optimizeTables(): array
    {
        $result = ['optimized' => 0, 'failed' => 0, 'tables' => []];
        try {
            $tables = DB::select('SHOW TABLES');
            $key    = 'Tables_in_' . DB::connection()->getDatabaseName();
            foreach ($tables as $t) {
                $name = $t->{$key} ?? null;
                if (!$name) continue;
                try {
                    DB::statement('OPTIMIZE TABLE `' . $name . '`');
                    $result['optimized']++;
                    $result['tables'][] = $name;
                } catch (Exception $e) {
                    $result['failed']++;
                }
            }
        } catch (Exception $e) {
            Log::error('[PerfOptimizer] OPTIMIZE TABLE failed: ' . $e->getMessage());
            $result['error'] = $e->getMessage();
        }

        OptimizationLog::create([
            'type' => 'db_clean', 'action' => 'optimize_tables', 'status' => 'success',
            'meta' => ['optimized' => $result['optimized'], 'failed' => $result['failed']],
        ]);
        return $result;
    }

    // ── Helpers ──────────────────────────────────────────────────────

    protected function safeCount(string $table, ?int $days = null, string $col = 'created_at'): int
    {
        try {
            if (!Schema::hasTable($table)) return 0;
            $q = DB::table($table);
            if ($days !== null && Schema::hasColumn($table, $col)) {
                $q->where($col, '<', now()->subDays($days));
            }
            return (int) $q->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    protected function expiredOtps(): int
    {
        try {
            if (!Schema::hasTable('otp_email')) return 0;
            return (int) DB::table('otp_email')->where('created_at', '<', now()->subHours(24))->count();
        } catch (Exception $e) {
            return 0;
        }
    }
}
