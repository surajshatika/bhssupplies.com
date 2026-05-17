<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Exception;
use Illuminate\Support\Facades\DB;

class SecurityAuditService
{
    public function run(): array
    {
        $checks = [];

        // 1. APP_DEBUG should be false in production
        $checks[] = $this->check(
            'APP_DEBUG disabled',
            !(bool) env('APP_DEBUG', false),
            'APP_DEBUG=true leaks stack traces and env data. Set APP_DEBUG=false in .env on production.'
        );

        // 2. APP_ENV production
        $checks[] = $this->check(
            'APP_ENV is production',
            env('APP_ENV') === 'production',
            'Set APP_ENV=production on live servers.'
        );

        // 3. HTTPS
        $checks[] = $this->check(
            'HTTPS enforced',
            request()->isSecure() || str_starts_with((string) env('APP_URL'), 'https://'),
            'Site should be served over HTTPS. Configure SSL on your server and set APP_URL to https://...'
        );

        // 4. .env not in public/
        $checks[] = $this->check(
            '.env not exposed in /public',
            !file_exists(public_path('.env')),
            'A .env file inside /public is a critical leak. Remove it immediately.'
        );

        // 5. Storage symlink
        $checks[] = $this->check(
            'Storage symlink exists',
            is_link(public_path('storage')) || is_dir(public_path('storage')),
            'Run "php artisan storage:link" so uploads are served from /public/storage.'
        );

        // 6. Default Laravel routes hidden
        $checks[] = $this->check(
            'Telescope/Horizon not publicly exposed',
            !\Illuminate\Support\Facades\Route::has('telescope'),
            'If you use Telescope, ensure its routes are gated behind admin-only middleware.'
        );

        // 7. Weak password column (very rough)
        try {
            $weakAdmins = DB::table('users')->where('user_type', 'admin')->whereRaw('LENGTH(password) < 50')->count();
            $checks[] = $this->check(
                'Admin passwords hashed',
                $weakAdmins === 0,
                $weakAdmins . ' admin user(s) appear to have unhashed/short password hashes. Force a password reset.'
            );
        } catch (Exception $e) { /* skip if column shape differs */ }

        // 8. Default admin email check
        try {
            $defAdmin = DB::table('users')->where('email', 'admin@example.com')->exists();
            $checks[] = $this->check(
                'No default admin email (admin@example.com)',
                !$defAdmin,
                'Default seed admin email still present. Change it.'
            );
        } catch (Exception $e) {}

        // 9. File permissions on storage/
        $checks[] = $this->check(
            'Storage directory writable',
            is_writable(storage_path()),
            'storage/ is not writable. Laravel cannot write cache/logs.'
        );

        // 10. XML-RPC blocked (toggle)
        $checks[] = $this->check(
            'XML-RPC blocked (if not used)',
            (int) get_setting('perf_security_block_xmlrpc', 0) === 1 || !file_exists(public_path('xmlrpc.php')),
            'Enable "Block XML-RPC" in Security settings if you do not use it.'
        );

        // 11. Force HTTPS toggle
        $checks[] = $this->check(
            'Force HTTPS redirect enabled',
            (int) get_setting('perf_security_force_https', 0) === 1,
            'Turn on "Force HTTPS" in Performance Optimizer → Settings for stronger TLS enforcement.'
        );

        // 12. PHP version exposure
        $checks[] = $this->check(
            'PHP version not exposed via X-Powered-By',
            !function_exists('ini_get') || ini_get('expose_php') == false,
            'Set expose_php=Off in php.ini so PHP version is not advertised.'
        );

        // 13. Debugbar disabled
        $checks[] = $this->check(
            'Laravel Debugbar disabled in production',
            !(bool) env('DEBUGBAR_ENABLED', false),
            'Set DEBUGBAR_ENABLED=false on production.'
        );

        // 14. Session secure cookie
        $checks[] = $this->check(
            'Session secure cookie enabled',
            (bool) config('session.secure'),
            'Set SESSION_SECURE_COOKIE=true so cookies are only sent over HTTPS.'
        );

        $passed = count(array_filter($checks, fn ($c) => $c['pass']));
        $score  = count($checks) > 0 ? round(($passed / count($checks)) * 100) : 0;

        OptimizationLog::create([
            'type' => 'security_audit', 'action' => 'run', 'status' => 'success',
            'meta' => ['score' => $score, 'passed' => $passed, 'total' => count($checks)],
        ]);

        return [
            'score'  => $score,
            'passed' => $passed,
            'total'  => count($checks),
            'checks' => $checks,
        ];
    }

    protected function check(string $title, bool $pass, string $fix = ''): array
    {
        return ['title' => $title, 'pass' => $pass, 'fix' => $fix];
    }
}
