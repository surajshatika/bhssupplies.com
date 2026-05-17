<?php

namespace App\Console\Commands\Amazon;

use App\Models\AmazonAccount;
use App\Services\Amazon\AmazonInventoryService;
use Illuminate\Console\Command;

class SyncAmazonInventoryCommand extends Command
{
    protected $signature   = 'amazon:sync-inventory';
    protected $description = 'Sync inventory/stock for all active Amazon listings';

    public function handle(AmazonInventoryService $service): int
    {
        if (!config('amazon.enabled')) {
            $this->info('Amazon integration is disabled. Set AMAZON_ENABLED=true to enable.');
            return 0;
        }

        $account = AmazonAccount::where('is_active', 1)->first();
        if (!$account) {
            $this->error('No active Amazon account configured.');
            return 1;
        }

        $this->info('Starting Amazon inventory sync...');
        $service->syncAllActive();
        $this->info('Amazon inventory sync dispatched.');

        return 0;
    }
}
