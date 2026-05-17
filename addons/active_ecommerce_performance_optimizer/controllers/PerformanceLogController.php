<?php

namespace App\Http\Controllers;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Illuminate\Http\Request;

class PerformanceLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $type = $request->input('type');
        $q = OptimizationLog::query();
        if ($type) $q->where('type', $type);

        $optimization_logs = $q->orderByDesc('id')->paginate(30);

        // Build the list of available log sources
        $sources = $this->discoverLogSources();

        // Pick the source to show (default: laravel.log)
        $sourceKey = $request->input('src', 'laravel');
        $active    = collect($sources)->flatten(1)->firstWhere('key', $sourceKey)
                  ?? collect($sources)->flatten(1)->first(fn ($s) => $s['available'] ?? false);

        $contentLines = [];
        $contentPath  = null;
        $contentSize  = '0 B';
        if ($active && ($active['available'] ?? false)) {
            $contentPath  = $active['path'];
            $contentSize  = $this->humanSize(@filesize($contentPath) ?: 0);
            $contentLines = $this->tailFile($contentPath, (int) $request->input('lines', 500));
        }

        return view('backend.performance_optimizer.index', [
            'tab'              => 'logs',
            'optimization_logs'=> $optimization_logs,
            'log_sources'      => $sources,
            'active_source'    => $active,
            'content_lines'    => $contentLines,
            'content_size'     => $contentSize,
            'log_type_filter'  => $type,
        ]);
    }

    public function clearError()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        $path = storage_path('logs/laravel.log');
        if (file_exists($path)) {
            @file_put_contents($path, '');
            flash(translate('Error log cleared.'))->success();
        }
        return back();
    }

    public function clearOptimization(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        OptimizationLog::query()->delete();
        flash(translate('Optimization activity log cleared.'))->success();
        return back();
    }

    // ────────────────────────────────────────────────────────────────

    protected function discoverLogSources(): array
    {
        $sources = [
            'Application' => [
                $this->mk('laravel',           'Laravel Application Log',        storage_path('logs/laravel.log')),
                $this->mk('optimizer',         'Optimizer Activity Log',         null, OptimizationLog::count()),
            ],
            'PHP Runtime' => [
                $this->mk('php_error',         'PHP Errors',                     ini_get('error_log') ?: null),
                $this->mk('php_deprecation',   'PHP Deprecations / Warnings',    storage_path('logs/php-deprecation.log')),
            ],
            'Server / OS' => [
                $this->mk('apache_error',      'Apache Error Log',               $this->guessPath(['/var/log/apache2/error.log', 'D:\\wamp\\logs\\apache_error.log'])),
                $this->mk('apache_access',     'Apache Access Log',              $this->guessPath(['/var/log/apache2/access.log', 'D:\\wamp\\logs\\access.log'])),
                $this->mk('nginx_error',       'Nginx Error Log',                $this->guessPath(['/var/log/nginx/error.log'])),
            ],
        ];
        return $sources;
    }

    protected function mk(string $key, string $name, ?string $path, ?int $fallbackSize = null): array
    {
        $exists = $path && file_exists($path);
        $size   = $exists ? (@filesize($path) ?: 0) : 0;
        return [
            'key'       => $key,
            'name'      => $name,
            'path'      => $path,
            'available' => $exists || $fallbackSize !== null,
            'size'      => $fallbackSize !== null ? $fallbackSize . ' rows' : $this->humanSize($size),
        ];
    }

    protected function guessPath(array $candidates): ?string
    {
        foreach ($candidates as $p) {
            if (file_exists($p) && is_readable($p)) return $p;
        }
        return null;
    }

    protected function tailFile(string $path, int $lines = 500): array
    {
        $f = @fopen($path, 'rb');
        if (!$f) return [];

        fseek($f, 0, SEEK_END);
        $filesize = ftell($f);
        $buffer   = '';
        $chunk    = 4096;
        $cursor   = $filesize;
        $found    = 0;

        while ($cursor > 0 && $found < $lines) {
            $read   = min($chunk, $cursor);
            $cursor -= $read;
            fseek($f, $cursor);
            $buffer = fread($f, $read) . $buffer;
            $found  = substr_count($buffer, "\n");
        }
        fclose($f);

        $arr = explode("\n", $buffer);
        return array_slice($arr, -$lines);
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
