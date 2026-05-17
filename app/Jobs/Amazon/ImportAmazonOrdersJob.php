<?php

namespace App\Jobs\Amazon;

use App\Models\AmazonAccount;
use App\Services\Amazon\AmazonOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportAmazonOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public array $backoff = [120, 300, 600];

    public function __construct(public AmazonAccount $account) {}

    public function handle(AmazonOrderService $service): void
    {
        $count = $service->importAllNew($this->account);
        \Log::info("Amazon order import: {$count} orders imported for account {$this->account->id}");
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Amazon order import job failed', [
            'account_id' => $this->account->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
