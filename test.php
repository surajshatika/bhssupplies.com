<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $board = app(App\Services\Seo\Board\AiSeoBoardService::class);
    print_r($board->applyAiFix('page', 1, null));
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine();
}
