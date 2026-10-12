<?php

namespace App\Console\Commands\Seo;

use App\Services\Seo\NotFoundResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Bulk-fix the URLs in a Search Console "Not found (404)" export.
 *   php artisan seo:fix-404s storage/app/gsc-404.csv
 * Each URL that still 404s is mapped to the closest live page (301).
 */
class Fix404sCommand extends Command
{
    protected $signature = 'seo:fix-404s {file : CSV/TXT with one URL per line (GSC export)} {--no-check : Skip the live HTTP status check}';
    protected $description = 'Create 301 redirects for 404 URLs reported by Google Search Console';

    public function handle(NotFoundResolver $resolver): int
    {
        $file = $this->argument('file');
        if (!is_file($file)) { $this->error("File not found: $file"); return 1; }

        preg_match_all('#https?://[^\s,"]+#', file_get_contents($file), $m);
        $urls = array_unique($m[0]);
        $fixed = $live = $unmatched = 0;
        $left = [];

        foreach ($urls as $url) {
            if (!$this->option('no-check')) {
                try {
                    $status = Http::withOptions(['allow_redirects' => false, 'verify' => config('seo.ssl_verify', true)])
                        ->timeout(15)->get($url)->status();
                    if ($status !== 404 && $status !== 410) { $live++; continue; }
                } catch (\Throwable $e) {}
            }
            if ($target = $resolver->resolve($url)) {
                $fixed++;
                $this->line("301  $url  →  $target");
            } else {
                $unmatched++;
                $left[] = $url;
            }
        }

        $this->info("Redirected: $fixed | Already OK: $live | No close match (left 404): $unmatched");
        foreach (array_slice($left, 0, 50) as $u) $this->line("  404 $u");
        return 0;
    }
}
