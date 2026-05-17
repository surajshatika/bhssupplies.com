<?php

namespace App\Jobs;

use App\Models\SeoRun;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Optimization\OptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GenerateOptimizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $runId;

    public function __construct($runId)
    {
        $this->runId = $runId;
        $this->onQueue(config('seo.queue.optimization', 'default'));
    }

    public function handle()
    {
        $run = SeoRun::find($this->runId);
        if (!$run) {
            return;
        }

        $run->update([
            'status' => 'processing',
            'started_at' => Carbon::now(),
        ]);

        try {
            $service = new OptimizationService($run->provider);
            $result = $service->run($run->feature, $run->input_payload ?: []);
            $run->update([
                'status' => 'completed',
                'result_payload' => $result,
                'completed_at' => Carbon::now(),
            ]);

            if (isset($result['score'])) {
                SeoScoreHistory::create([
                    'project_id' => $run->project_id,
                    'seo_run_id' => $run->id,
                    'url' => $run->url,
                    'score' => $result['score'],
                    'grade' => $result['grade'],
                    'metrics' => $result,
                    'recorded_at' => Carbon::now(),
                ]);
            }
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => Carbon::now(),
            ]);
        }
    }
}
