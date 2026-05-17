<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\DB;

class PerformanceMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $monitor = $this->collect();
        $health  = $this->environmentHealth($monitor);

        return view('backend.performance_optimizer.index', [
            'tab'     => 'monitor',
            'monitor' => $monitor,
            'health'  => $health,
        ]);
    }

    public function refresh()
    {
        return response()->json(['success' => true, 'data' => $this->collect()]);
    }

    protected function collect(): array
    {
        $memLimit       = ini_get('memory_limit');
        $memLimitBytes  = $this->parseSize($memLimit);
        $memUsed        = memory_get_usage(true);
        $memUsedPercent = $memLimitBytes > 0 ? min(100, round(($memUsed / $memLimitBytes) * 100)) : 0;

        $diskFree  = (int) @disk_free_space(base_path());
        $diskTotal = (int) @disk_total_space(base_path());
        $diskUsedPercent = $diskTotal > 0 ? min(100, round((($diskTotal - $diskFree) / $diskTotal) * 100)) : 0;

        $data = [
            'php_version'      => PHP_VERSION,
            'memory_limit'     => $memLimit,
            'memory_limit_bytes' => $memLimitBytes,
            'memory_used'      => $this->humanSize($memUsed),
            'memory_used_bytes'=> $memUsed,
            'memory_used_percent' => $memUsedPercent,
            'memory_peak'      => $this->humanSize(memory_get_peak_usage(true)),
            'max_execution'    => ini_get('max_execution_time'),
            'upload_max'       => ini_get('upload_max_filesize'),
            'post_max'         => ini_get('post_max_size'),
            'sapi'             => PHP_SAPI,
            'os'               => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
            'os_short'         => PHP_OS,
            'server_software'  => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'server_admin'     => $_SERVER['SERVER_ADMIN'] ?? '–',
            'server_ip'        => $_SERVER['SERVER_ADDR'] ?? '–',
            'server_port'      => $_SERVER['SERVER_PORT'] ?? '–',
            'server_protocol'  => $_SERVER['SERVER_PROTOCOL'] ?? '–',
            'hostname'         => php_uname('n'),
            'document_root'    => $_SERVER['DOCUMENT_ROOT'] ?? base_path(),
            'extensions'       => $this->extensionStatus(),
            'disk_total'       => $this->humanSize($diskTotal),
            'disk_free'        => $this->humanSize($diskFree),
            'disk_used_percent'=> $diskUsedPercent,
            'opcache'          => $this->opcacheInfo(),
            'mysql'            => $this->mysqlInfo(),
            'laravel_version'  => app()->version(),
            'env'              => app()->environment(),
            'debug'            => (bool) config('app.debug'),
            'timezone'         => config('app.timezone'),
            'app_url'          => config('app.url'),
            'cpu_cores'        => $this->cpuCores(),
            'now'              => now()->format('Y-m-d H:i:s'),
        ];

        if (function_exists('sys_getloadavg')) {
            $load = @sys_getloadavg();
            $data['load_avg']     = is_array($load) ? implode(' / ', array_map(fn ($v) => round($v, 2), $load)) : 'n/a';
            $data['load_avg_1m']  = is_array($load) ? round($load[0], 2) : null;
        } else {
            $data['load_avg']    = 'n/a (Windows)';
            $data['load_avg_1m'] = null;
        }

        return $data;
    }

    protected function environmentHealth(array $m): array
    {
        return [
            ['label' => '.env file',                'pass' => file_exists(base_path('.env')),                  'detail' => file_exists(base_path('.env')) ? 'Present' : 'Missing!'],
            ['label' => 'APP_DEBUG',                'pass' => !$m['debug'],                                    'detail' => $m['debug'] ? 'ON (turn OFF in production)' : 'OFF'],
            ['label' => 'APP_KEY',                  'pass' => !empty(config('app.key')),                       'detail' => !empty(config('app.key')) ? 'Set' : 'Run php artisan key:generate'],
            ['label' => 'Storage writable',         'pass' => is_writable(storage_path()),                     'detail' => is_writable(storage_path()) ? 'Writable' : 'Not writable!'],
            ['label' => 'Bootstrap cache writable', 'pass' => is_writable(base_path('bootstrap/cache')),       'detail' => is_writable(base_path('bootstrap/cache')) ? 'Writable' : 'Not writable!'],
            ['label' => 'public/storage symlink',   'pass' => is_link(public_path('storage')) || is_dir(public_path('storage')), 'detail' => is_link(public_path('storage')) ? 'Symlink' : (is_dir(public_path('storage')) ? 'Directory (not symlink)' : 'Missing')],
            ['label' => 'OpCache',                  'pass' => $m['opcache']['enabled'] ?? false,               'detail' => ($m['opcache']['enabled'] ?? false) ? 'Enabled' : 'Disabled'],
            ['label' => 'gd / imagick',             'pass' => $m['extensions']['gd'] || $m['extensions']['imagick'], 'detail' => $m['extensions']['gd'] ? 'gd' : ($m['extensions']['imagick'] ? 'imagick' : 'Missing!')],
            ['label' => 'pdo_mysql',                'pass' => $m['extensions']['pdo_mysql'] ?? false,          'detail' => ($m['extensions']['pdo_mysql'] ?? false) ? 'Loaded' : 'Missing!'],
        ];
    }

    protected function extensionStatus(): array
    {
        $required = ['gd', 'imagick', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml', 'curl', 'zip', 'fileinfo', 'openssl', 'bcmath', 'redis', 'opcache', 'intl', 'ctype', 'json'];
        $out = [];
        foreach ($required as $ext) {
            $out[$ext] = extension_loaded($ext);
        }
        return $out;
    }

    protected function opcacheInfo(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['enabled' => false];
        }
        $s = @opcache_get_status(false);
        if (!$s) return ['enabled' => false];
        return [
            'enabled'      => $s['opcache_enabled'] ?? false,
            'memory_used'  => $this->humanSize((int) ($s['memory_usage']['used_memory']  ?? 0)),
            'memory_free'  => $this->humanSize((int) ($s['memory_usage']['free_memory']  ?? 0)),
            'memory_used_percent' => isset($s['memory_usage']['used_memory'], $s['memory_usage']['free_memory'])
                ? round(($s['memory_usage']['used_memory'] / ($s['memory_usage']['used_memory'] + $s['memory_usage']['free_memory'])) * 100)
                : 0,
            'hits'         => $s['opcache_statistics']['hits'] ?? 0,
            'misses'       => $s['opcache_statistics']['misses'] ?? 0,
            'hit_rate'     => isset($s['opcache_statistics']['opcache_hit_rate']) ? round((float) $s['opcache_statistics']['opcache_hit_rate'], 2) : 0,
            'cached_scripts'=> $s['opcache_statistics']['num_cached_scripts'] ?? 0,
        ];
    }

    protected function mysqlInfo(): array
    {
        try {
            $version = DB::selectOne('SELECT VERSION() AS v');
            $size = DB::selectOne(
                "SELECT SUM(data_length + index_length) AS s FROM information_schema.tables WHERE table_schema = ?",
                [DB::connection()->getDatabaseName()]
            );
            return [
                'version' => $version->v ?? 'n/a',
                'db'      => DB::connection()->getDatabaseName(),
                'size'    => $this->humanSize((int) ($size->s ?? 0)),
            ];
        } catch (Exception $e) {
            return ['version' => 'error', 'db' => '', 'size' => '0 B'];
        }
    }

    protected function cpuCores(): int
    {
        // Try /proc/cpuinfo (Linux)
        if (is_readable('/proc/cpuinfo')) {
            return substr_count(@file_get_contents('/proc/cpuinfo'), 'processor');
        }
        // Windows fallback
        $envCores = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 0);
        return $envCores ?: 1;
    }

    protected function parseSize(string $size): int
    {
        $size  = trim($size);
        $unit  = strtolower(substr($size, -1));
        $value = (int) $size;
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
