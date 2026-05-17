<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoAnalytic;
use App\Services\Seo\Speed\PageSpeedInsightsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PageSpeedAuditCommand extends Command
{
    protected $signature = 'seo:pagespeed
                            {url?* : URLs to audit (defaults to homepage + top 3 categories + top 5 products)}
                            {--strategy=both : mobile | desktop | both}';

    protected $description = 'Run Google PageSpeed Insights against key site URLs and persist Lighthouse scores.';

    public function handle(PageSpeedInsightsService $psi): int
    {
        $urls = $this->argument('url');
        if (empty($urls)) {
            $urls = $this->defaultUrls();
        }

        $strategies = match ($this->option('strategy')) {
            'mobile'  => ['mobile'],
            'desktop' => ['desktop'],
            default   => ['mobile', 'desktop'],
        };

        $persistable = Schema::hasTable('seo_analytics');
        $ok = 0;
        $fail = 0;

        foreach ($urls as $url) {
            foreach ($strategies as $strategy) {
                $this->info("→ {$strategy} {$url}");
                $result = $psi->audit($url, $strategy);

                if (!$result['success']) {
                    $this->error('  ' . $result['error']);
                    $fail++;
                    continue;
                }

                $perf = $result['scores']['performance'] ?? null;
                $seo  = $result['scores']['seo']         ?? null;
                $this->line("  perf={$perf} seo={$seo} LCP=" . round($result['vitals']['lcp_ms']/1000, 2) . 's CLS=' . $result['vitals']['cls']);

                if ($persistable) {
                    foreach (['performance', 'seo', 'accessibility', 'best-practices'] as $cat) {
                        if (!isset($result['scores'][$cat])) continue;
                        SeoAnalytic::create([
                            'date'        => now()->toDateString(),
                            'source'      => 'psi',
                            'dimension'   => 'lighthouse',
                            'value'       => mb_substr($cat . '|' . $strategy . '|' . $url, 0, 500),
                            'clicks'      => 0,
                            'impressions' => 0,
                            'ctr'         => null,
                            'position'    => (float) $result['scores'][$cat],
                        ]);
                    }
                }
                $ok++;
            }
        }

        $this->info("PageSpeed audit complete — ok={$ok} fail={$fail}");
        return self::SUCCESS;
    }

    protected function defaultUrls(): array
    {
        $urls = [url('/')];

        if (Schema::hasTable('categories')) {
            foreach (\App\Models\Category::query()->whereNotNull('slug')->take(3)->get() as $c) {
                $urls[] = url('/category/' . $c->slug);
            }
        }
        if (Schema::hasTable('products')) {
            foreach (\App\Models\Product::query()->whereNotNull('slug')->latest()->take(5)->get() as $p) {
                $urls[] = url('/product/' . $p->slug);
            }
        }

        return array_values(array_unique($urls));
    }
}
