<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\WebVital;
use Exception;

class WebVitalsService
{
    public function record(array $payload): bool
    {
        try {
            $metric = strtoupper((string) ($payload['metric'] ?? ''));
            if (!in_array($metric, ['LCP', 'FID', 'CLS', 'INP', 'FCP', 'TTFB'])) return false;

            $value = (float) ($payload['value'] ?? 0);
            $url = substr((string) ($payload['url'] ?? ''), 0, 500);
            if (!$this->shouldRecord($payload, $url, $metric, $value)) return false;

            WebVital::create([
                'url'        => $url,
                'metric'     => $metric,
                'value'      => $value,
                'rating'     => WebVital::ratingFor($metric, $value),
                'device'     => substr((string) ($payload['device']    ?? ''), 0, 20),
                'connection' => substr((string) ($payload['connection']?? ''), 0, 20),
                'user_agent' => substr((string) ($payload['user_agent']?? ''), 0, 255),
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function summary(int $days = 7): array
    {
        $since   = now()->subDays($days);
        $metrics = ['LCP', 'FID', 'CLS', 'INP', 'FCP', 'TTFB'];
        $out     = [];

        foreach ($metrics as $m) {
            $rows = WebVital::where('metric', $m)
                ->where('created_at', '>=', $since)
                ->where('url', 'not like', '/admin%')
                ->orderBy('value')
                ->pluck('value')
                ->filter(fn($value) => $this->isReasonableValue($m, (float) $value))
                ->values()
                ->all();
            $count = count($rows);
            if ($count === 0) {
                $out[$m] = ['samples' => 0, 'p50' => null, 'p75' => null, 'p95' => null, 'rating' => 'no-data'];
                continue;
            }
            $p50    = $rows[(int) floor(($count - 1) * 0.50)];
            $p75    = $rows[(int) floor(($count - 1) * 0.75)];
            $p95    = $rows[(int) floor(($count - 1) * 0.95)];
            $rating = WebVital::ratingFor($m, (float) $p75);
            $out[$m] = compact('p50', 'p75', 'p95', 'rating') + ['samples' => $count];
        }
        return $out;
    }

    public function clear(): int
    {
        return WebVital::truncate() ? 1 : 0;
    }

    /**
     * P75 per metric per day for chart rendering. Returns:
     *   [
     *     'labels' => ['2026-05-09', ..., '2026-05-15'],
     *     'series' => ['LCP' => [1234, 1300, ...], 'CLS' => [...], ...],
     *   ]
     */
    public function dailyTrend(int $days = 7): array
    {
        $metrics = ['LCP', 'CLS', 'INP', 'FCP', 'TTFB'];
        $labels  = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('Y-m-d');
        }

        $series = [];
        foreach ($metrics as $m) {
            $vals = [];
            foreach ($labels as $d) {
                $rows = WebVital::where('metric', $m)
                    ->whereDate('created_at', $d)
                    ->where('url', 'not like', '/admin%')
                    ->orderBy('value')
                    ->pluck('value')
                    ->filter(fn($value) => $this->isReasonableValue($m, (float) $value))
                    ->values()
                    ->all();
                if (empty($rows)) {
                    $vals[] = null;
                    continue;
                }
                $vals[] = round((float) $rows[(int) floor((count($rows) - 1) * 0.75)], $m === 'CLS' ? 3 : 0);
            }
            $series[$m] = $vals;
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function trackerScript(string $endpoint): string
    {
        $rate = max(1, min(100, (int) get_setting('perf_vitals_sample_rate', 10)));
        return <<<JS
<script data-perfopt="vitals-tracker">
(function(){
    if (Math.random() * 100 > {$rate}) return;
    var endpoint = "{$endpoint}";
    var token    = document.querySelector('meta[name="csrf-token"]');
    var csrf     = token ? token.getAttribute('content') : '';
    var conn     = (navigator.connection && navigator.connection.effectiveType) || '';
    var device   = /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop';

    function send(metric, value) {
        try {
            var data = new Blob([JSON.stringify({
                metric: metric, value: value, url: location.pathname,
                host: location.hostname,
                device: device, connection: conn,
                user_agent: (navigator.userAgent || '').substring(0, 240),
                _token: csrf
            })], {type: 'application/json'});
            if (navigator.sendBeacon) navigator.sendBeacon(endpoint, data);
            else fetch(endpoint, { method:'POST', keepalive:true, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf}, body: data });
        } catch(e) {}
    }

    // LCP
    try {
        new PerformanceObserver(function(list){
            var entries = list.getEntries();
            var last = entries[entries.length - 1];
            if (last) send('LCP', last.renderTime || last.loadTime || last.startTime);
        }).observe({type:'largest-contentful-paint', buffered:true});
    } catch(e){}

    // CLS
    try {
        var cls = 0;
        new PerformanceObserver(function(list){
            list.getEntries().forEach(function(e){ if (!e.hadRecentInput) cls += e.value; });
        }).observe({type:'layout-shift', buffered:true});
        addEventListener('visibilitychange', function(){
            if (document.visibilityState === 'hidden') send('CLS', cls);
        });
    } catch(e){}

    // FCP
    try {
        new PerformanceObserver(function(list){
            list.getEntries().forEach(function(e){
                if (e.name === 'first-contentful-paint') send('FCP', e.startTime);
            });
        }).observe({type:'paint', buffered:true});
    } catch(e){}

    // TTFB
    try {
        var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
        if (nav) send('TTFB', nav.responseStart);
    } catch(e){}

    // INP (event timing)
    try {
        var maxInp = 0;
        new PerformanceObserver(function(list){
            list.getEntries().forEach(function(e){
                if (e.duration > maxInp) maxInp = e.duration;
            });
        }).observe({type:'event', buffered:true, durationThreshold:40});
        addEventListener('visibilitychange', function(){
            if (document.visibilityState === 'hidden' && maxInp > 0) send('INP', maxInp);
        });
    } catch(e){}
})();
</script>
JS;
    }

    protected function shouldRecord(array $payload, string $url, string $metric, float $value): bool
    {
        $path = ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
        if ($path === 'admin' || str_starts_with($path, 'admin/')) return false;

        $host = strtolower((string) ($payload['host'] ?? ''));
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) return false;
        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) return false;

        return $this->isReasonableValue($metric, $value);
    }

    protected function isReasonableValue(string $metric, float $value): bool
    {
        if ($value < 0) return false;
        $max = [
            'LCP' => 60000,
            'FID' => 5000,
            'CLS' => 5,
            'INP' => 10000,
            'FCP' => 60000,
            'TTFB' => 30000,
        ];
        return $value <= ($max[$metric] ?? 60000);
    }
}
