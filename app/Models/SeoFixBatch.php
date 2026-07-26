<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoFixBatch extends Model
{
    protected $table = 'seo_fix_batches';

    protected $guarded = [];

    protected $casts = [
        'target_ids'         => 'array',
        'options'            => 'array',
        'error_log'          => 'array',
        'started_at'         => 'datetime',
        'completed_at'       => 'datetime',
        'estimated_cost_usd' => 'float',
        'actual_cost_usd'    => 'float',
    ];

    public const STATUS_QUEUED     = 'queued';
    public const STATUS_RUNNING    = 'running';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_FAILED     = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
        ], true);
    }

    public function progressPercent(): int
    {
        if ($this->total <= 0) {
            return 0;
        }
        return min(100, (int) round(($this->processed / $this->total) * 100));
    }

    public function remainingCount(): int
    {
        return max(0, (int) $this->total - (int) $this->processed);
    }

    public function isStalled(int $minutes = 20): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $heartbeat = $this->updated_at ?: $this->created_at;

        return $heartbeat && $heartbeat->lt(now()->subMinutes(max(5, $minutes)));
    }

    public function appendError(string $message): void
    {
        $log = $this->error_log ?? [];
        $log[] = [
            'at'  => now()->toDateTimeString(),
            'msg' => $message,
        ];
        $this->error_log = array_slice($log, -50);    // keep last 50
        $this->save();
    }
}
