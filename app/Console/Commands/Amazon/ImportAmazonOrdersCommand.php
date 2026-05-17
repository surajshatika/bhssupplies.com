<?php

namespace App\Console\Commands\Amazon;

use App\Models\AmazonAccount;
use App\Services\Amazon\AmazonOrderService;
use Illuminate\Console\Command;

class ImportAmazonOrdersCommand extends Command
{
    protected $signature   = 'amazon:import-orders';
    protected $description = 'Import new orders from Amazon Canada into Laravel';

    public function handle(AmazonOrderService $service): int
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

        $this->info('Importing Amazon orders...');
        $count = $service->importAllNew($account);
        $this->info("Done. {$count} orders imported.");

        return 0;
    }
}
