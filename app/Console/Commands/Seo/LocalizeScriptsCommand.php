<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use App\Services\PerformanceOptimizer\ThirdPartyLocalizerService;

class LocalizeScriptsCommand extends Command
{
    protected $signature = 'perf:localize-scripts';
    protected $description = 'Download external 3rd-party scripts (GA, FB, GTM) to local server for faster caching';

    public function handle(ThirdPartyLocalizerService $localizer)
    {
        $this->info('Downloading external scripts...');
        $results = $localizer->localizeAll();

        $this->info("Success: {$results['success']}, Failed: {$results['failed']}");
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                $this->error($error);
            }
        }
        
        return 0;
    }
}
