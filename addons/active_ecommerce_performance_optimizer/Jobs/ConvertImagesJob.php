<?php

namespace App\Jobs\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;  // 10 minutes
    public int $tries   = 1;

    protected string $format;
    protected ?int   $quality;
    protected int    $limit;

    public function __construct(string $format = 'webp', ?int $quality = null, int $limit = 500)
    {
        $this->format  = $format === 'avif' ? 'avif' : 'webp';
        $this->quality = $quality;
        $this->limit   = $limit;

        $defaultQueue = config('queue.default');
        if ($defaultQueue !== 'sync') {
            $this->onQueue('perf-optimizer');
        }
    }

    public function handle(ImageOptimizerService $service): void
    {
        $r = $this->format === 'avif'
            ? $service->convertAllToAvif($this->quality, $this->limit)
            : $service->convertAllToWebp($this->quality, $this->limit);

        OptimizationLog::create([
            'type'   => 'image_convert',
            'action' => 'job_' . $this->format . '_batch',
            'status' => 'success',
            'meta'   => $r + ['format' => $this->format, 'limit' => $this->limit],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        OptimizationLog::create([
            'type'          => 'image_convert',
            'action'        => 'job_' . $this->format . '_batch',
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
