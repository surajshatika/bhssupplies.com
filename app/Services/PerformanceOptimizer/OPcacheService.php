<?php

namespace App\Services\PerformanceOptimizer;

class OPcacheService
{
    public function isAvailable(): bool
    {
        return function_exists('opcache_get_status') && function_exists('opcache_reset');
    }

    public function isEnabled(): bool
    {
        if (!$this->isAvailable()) return false;
        $status = @opcache_get_status(false);
        return is_array($status) && ($status['opcache_enabled'] ?? false);
    }

    /**
     * Returns a flat stats array safe for the view.
     */
    public function getStats(): array
    {
        if (!$this->isEnabled()) {
            return [
                'available'       => $this->isAvailable(),
                'enabled'         => false,
                'hit_rate'        => 0,
                'memory_used_mb'  => 0,
                'memory_free_mb'  => 0,
                'memory_total_mb' => 0,
                'memory_pct'      => 0,
                'cached_scripts'  => 0,
                'cached_keys'     => 0,
                'hits'            => 0,
                'misses'          => 0,
                'restarts'        => 0,
                'jit_enabled'     => false,
                'jit_buffer_mb'   => 0,
            ];
        }

        $s = @opcache_get_status(false);

        $memUsed  = $s['memory_usage']['used_memory']      ?? 0;
        $memFree  = $s['memory_usage']['free_memory']      ?? 0;
        $memWaste = $s['memory_usage']['wasted_memory']    ?? 0;
        $memTotal = $memUsed + $memFree + $memWaste;

        $hits   = $s['opcache_statistics']['hits']   ?? 0;
        $misses = $s['opcache_statistics']['misses'] ?? 0;
        $total  = $hits + $misses;
        $hitRate = $total > 0 ? round($hits / $total * 100, 1) : 0;

        $jitStatus  = $s['jit'] ?? null;
        $jitEnabled = isset($jitStatus['enabled']) && $jitStatus['enabled'];

        $cfg = @opcache_get_configuration();
        $jitBufMb = 0;
        if ($jitEnabled && isset($cfg['directives']['opcache.jit_buffer_size'])) {
            $jitBufMb = round($cfg['directives']['opcache.jit_buffer_size'] / 1024 / 1024, 1);
        }

        return [
            'available'       => true,
            'enabled'         => true,
            'hit_rate'        => $hitRate,
            'memory_used_mb'  => round($memUsed  / 1024 / 1024, 1),
            'memory_free_mb'  => round($memFree  / 1024 / 1024, 1),
            'memory_total_mb' => round($memTotal / 1024 / 1024, 1),
            'memory_pct'      => $memTotal > 0 ? round($memUsed / $memTotal * 100) : 0,
            'cached_scripts'  => $s['opcache_statistics']['num_cached_scripts'] ?? 0,
            'cached_keys'     => $s['opcache_statistics']['num_cached_keys']    ?? 0,
            'hits'            => $hits,
            'misses'          => $misses,
            'restarts'        => ($s['opcache_statistics']['oom_restarts'] ?? 0) + ($s['opcache_statistics']['manual_restarts'] ?? 0),
            'jit_enabled'     => $jitEnabled,
            'jit_buffer_mb'   => $jitBufMb,
        ];
    }

    /**
     * Flush the entire OPcache. Returns true on success.
     */
    public function flush(): bool
    {
        if (!$this->isEnabled()) return false;
        return (bool) @opcache_reset();
    }

    /**
     * Invalidate a single file (useful after a deployment).
     */
    public function invalidateFile(string $path): bool
    {
        if (!function_exists('opcache_invalidate')) return false;
        return (bool) @opcache_invalidate($path, true);
    }
}
