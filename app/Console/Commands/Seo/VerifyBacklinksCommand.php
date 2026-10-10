<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoBacklink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Fetches each tracked linking page and checks it really links to this site,
 * recording anchor text and rel. Live links that disappear are marked lost.
 */
class VerifyBacklinksCommand extends Command
{
    protected $signature = 'seo:verify-backlinks {--limit=100 : Max pages to check this run}';

    protected $description = 'Check tracked backlink pages still link to this site (anchor, rel, live/lost)';

    public function handle(): int
    {
        $ourDomains = collect([url('/'), config('seo.site_url'), config('app.url'), 'https://bhssupplies.com'])
            ->filter()->map(fn($u) => SeoBacklink::domainOf($u))->unique()->values()->all();

        $rows = SeoBacklink::whereNotNull('source_url')
            ->whereIn('status', ['submitted', 'live', 'lost', 'contacted'])
            ->orderByRaw('last_checked_at IS NOT NULL')->orderBy('last_checked_at')
            ->limit((int) $this->option('limit'))->get();

        $counts = ['live' => 0, 'lost' => 0, 'unreachable' => 0];
        foreach ($rows as $row) {
            try {
                $res = Http::timeout(15)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; BHSLinkCheck/1.0)'])
                    ->withOptions(['verify' => config('seo.ssl_verify', true)])->get($row->source_url);
                $row->last_http_status = $res->status();
                $link = $res->successful() ? $this->findLink($res->body(), $ourDomains) : null;
            } catch (\Throwable $e) {
                $row->last_http_status = 0;
                $link = null;
            }
            $row->last_checked_at = now();

            if ($link) {
                $row->status = 'live';
                $row->anchor_text = $link['anchor'];
                $row->rel = $link['rel'];
                $row->target_url = $row->target_url ?: $link['href'];
                $row->first_seen_live_at = $row->first_seen_live_at ?: now();
                $row->lost_at = null;
                $counts['live']++;
            } elseif ($row->status === 'live' && $row->last_http_status >= 200 && $row->last_http_status < 500) {
                // Page reachable but link gone → lost. Network errors don't flip it.
                $row->status = 'lost';
                $row->lost_at = now();
                $counts['lost']++;
            } else {
                $counts['unreachable'] += $row->last_http_status === 0 ? 1 : 0;
            }
            $row->save();
        }

        $this->info('Checked ' . $rows->count() . ': ' . json_encode($counts));
        return self::SUCCESS;
    }

    /** @return array{href:string,anchor:string,rel:string}|null */
    protected function findLink(string $html, array $ourDomains): ?array
    {
        if (!preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/is', $html, $m, PREG_SET_ORDER)) {
            return null;
        }
        foreach ($m as $a) {
            if (!preg_match('/href\s*=\s*["\']([^"\']+)/i', $a[1], $h)) continue;
            if (!in_array(SeoBacklink::domainOf(html_entity_decode($h[1])), $ourDomains, true)) continue;
            preg_match('/rel\s*=\s*["\']([^"\']+)/i', $a[1], $r);
            $rel = strtolower($r[1] ?? '');
            $anchor = trim(preg_replace('/\s+/', ' ', strip_tags($a[2])));
            if ($anchor === '' && preg_match('/alt\s*=\s*["\']([^"\']*)/i', $a[2], $alt)) $anchor = '[img] ' . $alt[1];
            return [
                'href' => html_entity_decode($h[1]),
                'anchor' => mb_substr($anchor, 0, 250),
                'rel' => str_contains($rel, 'sponsored') ? 'sponsored' : (str_contains($rel, 'ugc') ? 'ugc' : (str_contains($rel, 'nofollow') ? 'nofollow' : 'follow')),
            ];
        }
        return null;
    }
}
