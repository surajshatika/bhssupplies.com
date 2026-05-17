<?php

namespace App\Services\Marketing;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * File-based first-party event warehouse. Appends one JSON line per event to
 * storage/app/marketing/events/YYYY-MM-DD.jsonl — no DB migration required.
 *
 * Aggregation command compresses these into BusinessSetting daily-summary JSON
 * for fast dashboard reads. Raw files older than retention window are deleted.
 */
class EventStore
{
    protected string $eventsDir = 'marketing/events';
    protected int    $retentionDays = 90;

    public function record(string $eventName, array $payload, ?string $eventId = null): void
    {
        try {
            $row = [
                'ts'         => Carbon::now()->toIso8601String(),
                'event'      => $eventName,
                'event_id'   => $eventId,
                'anon_id'    => request()->cookie('mm_anon_id'),
                'session_id' => request()->cookie('mm_session_id') ?: request()->session()->getId(),
                'user_id'    => auth()->id(),
                'utm' => array_filter([
                    'source'   => request()->cookie('mm_utm_source')   ?: request()->query('utm_source'),
                    'medium'   => request()->cookie('mm_utm_medium')   ?: request()->query('utm_medium'),
                    'campaign' => request()->cookie('mm_utm_campaign') ?: request()->query('utm_campaign'),
                    'term'     => request()->cookie('mm_utm_term')     ?: request()->query('utm_term'),
                    'content'  => request()->cookie('mm_utm_content')  ?: request()->query('utm_content'),
                ]),
                'referrer'   => request()->headers->get('referer'),
                'url'        => request()->fullUrl(),
                'ip'         => request()->ip(),
                'ua'         => substr((string) request()->userAgent(), 0, 200),
                'value'      => isset($payload['value']) ? (float) $payload['value'] : null,
                'currency'   => $payload['currency'] ?? null,
                'product_ids'=> $payload['content_ids'] ?? null,
                'order_id'   => $payload['order_id'] ?? null,
            ];

            $path = $this->path($this->eventsDir . '/' . Carbon::now()->toDateString() . '.jsonl');
            $this->ensureDir(dirname($path));
            $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

            // O_APPEND on Linux/Windows is atomic for writes under PIPE_BUF (4096) — single event lines.
            file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            Log::warning('[EventStore] failed to record event', [
                'event'   => $eventName,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** Read raw events for a single date — returns generator to avoid OOM. */
    public function readDate(string $date): \Generator
    {
        $path = $this->path($this->eventsDir . '/' . $date . '.jsonl');
        if (!is_file($path)) return;

        $fh = fopen($path, 'r');
        if (!$fh) return;
        try {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line === '') continue;
                $row = json_decode($line, true);
                if (is_array($row)) yield $row;
            }
        } finally {
            fclose($fh);
        }
    }

    /** Aggregate one day's raw events into a compact summary array. */
    public function aggregateDate(string $date): array
    {
        $summary = [
            'date'           => $date,
            'event_counts'   => [],
            'unique_visitors'=> 0,
            'unique_sessions'=> 0,
            'page_views'     => 0,
            'add_to_cart'    => 0,
            'purchases'      => 0,
            'revenue'        => 0.0,
            'top_products'   => [],
            'top_utm_source' => [],
            'top_referrers'  => [],
            'currency'       => null,
        ];

        $anon     = [];
        $sessions = [];
        $products = [];
        $utm      = [];
        $referrers= [];

        foreach ($this->readDate($date) as $row) {
            $event = $row['event'] ?? 'Unknown';
            $summary['event_counts'][$event] = ($summary['event_counts'][$event] ?? 0) + 1;

            if (!empty($row['anon_id']))    $anon[$row['anon_id']]       = true;
            if (!empty($row['session_id'])) $sessions[$row['session_id']]= true;

            switch ($event) {
                case 'ViewContent': $summary['page_views']++;  break;
                case 'AddToCart':   $summary['add_to_cart']++; break;
                case 'Purchase':
                    $summary['purchases']++;
                    $summary['revenue'] += (float) ($row['value'] ?? 0);
                    if (!$summary['currency'] && !empty($row['currency'])) $summary['currency'] = $row['currency'];
                    break;
            }

            foreach ((array) ($row['product_ids'] ?? []) as $pid) {
                $products[$pid] = ($products[$pid] ?? 0) + 1;
            }
            if (!empty($row['utm']['source'])) {
                $utm[$row['utm']['source']] = ($utm[$row['utm']['source']] ?? 0) + 1;
            }
            if (!empty($row['referrer'])) {
                $host = parse_url($row['referrer'], PHP_URL_HOST) ?: 'direct';
                $referrers[$host] = ($referrers[$host] ?? 0) + 1;
            }
        }

        $summary['unique_visitors'] = count($anon);
        $summary['unique_sessions'] = count($sessions);
        arsort($products); $summary['top_products']  = array_slice($products,  0, 10, true);
        arsort($utm);      $summary['top_utm_source']= array_slice($utm,       0, 10, true);
        arsort($referrers);$summary['top_referrers'] = array_slice($referrers, 0, 10, true);

        return $summary;
    }

    /** Persist daily aggregate JSON for fast dashboard loads. */
    public function storeAggregate(array $summary): void
    {
        $date = $summary['date'] ?? Carbon::now()->toDateString();
        $path = $this->path('marketing/aggregates/' . $date . '.json');
        $this->ensureDir(dirname($path));
        file_put_contents($path, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /** Load N most recent daily aggregates, oldest first. */
    public function loadRecentAggregates(int $days = 30): array
    {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $path = $this->path('marketing/aggregates/' . $d . '.json');
            if (is_file($path)) {
                $data = json_decode(file_get_contents($path), true);
                if (is_array($data)) $out[$d] = $data;
            }
        }
        return $out;
    }

    public function purgeOldRawEvents(): int
    {
        $deleted = 0;
        $cutoff  = Carbon::now()->subDays($this->retentionDays);
        $dir     = $this->path($this->eventsDir);
        if (!is_dir($dir)) return 0;

        foreach (glob($dir . '/*.jsonl') as $f) {
            $name = basename($f, '.jsonl');
            try {
                if (Carbon::parse($name)->lt($cutoff)) {
                    @unlink($f);
                    $deleted++;
                }
            } catch (\Throwable $e) {
                // bad filename — skip
            }
        }
        return $deleted;
    }

    /** Resolve absolute path inside storage/app (NOT public_path). */
    protected function path(string $rel): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
    }

    protected function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
    }
}
