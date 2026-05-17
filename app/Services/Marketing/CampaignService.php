<?php

namespace App\Services\Marketing;

use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * UTM Campaign Manager + short link generator + ROAS calculator.
 *
 * Campaigns are stored as JSON in business_setting key `marketing_campaigns`
 * (no migration needed). Each campaign has:
 *   - id, name, utm_source, utm_medium, utm_campaign, utm_term, utm_content
 *   - destination_url
 *   - short_code (auto)
 *   - ad_spend (manual entry for ROAS)
 *   - created_at, updated_at
 */
class CampaignService
{
    public function __construct(protected EventStore $store) {}

    public function all(): array
    {
        $raw = BusinessSetting::where('type', 'marketing_campaigns')->value('value');
        $arr = $raw ? json_decode($raw, true) : [];
        return is_array($arr) ? $arr : [];
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $c) {
            if ($c['id'] === $id) return $c;
        }
        return null;
    }

    public function findByCode(string $code): ?array
    {
        foreach ($this->all() as $c) {
            if (($c['short_code'] ?? '') === $code) return $c;
        }
        return null;
    }

    public function save(array $data): array
    {
        $campaigns = $this->all();

        $now = Carbon::now()->toIso8601String();
        $isNew = empty($data['id']);

        if ($isNew) {
            $data['id']         = (string) Str::uuid();
            $data['short_code'] = $this->generateShortCode($campaigns);
            $data['created_at'] = $now;
        }
        $data['updated_at']     = $now;
        $data['destination_url']= $this->buildTaggedUrl(
            $data['destination_url'] ?? url('/'),
            [
                'utm_source'   => $data['utm_source']   ?? null,
                'utm_medium'   => $data['utm_medium']   ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_term'     => $data['utm_term']     ?? null,
                'utm_content'  => $data['utm_content']  ?? null,
            ]
        );

        // Upsert
        $found = false;
        foreach ($campaigns as $i => $c) {
            if ($c['id'] === $data['id']) {
                $campaigns[$i] = $data;
                $found = true;
                break;
            }
        }
        if (!$found) $campaigns[] = $data;

        $this->persist($campaigns);
        return $data;
    }

    public function delete(string $id): bool
    {
        $campaigns = $this->all();
        $filtered  = array_values(array_filter($campaigns, fn ($c) => $c['id'] !== $id));
        if (count($filtered) === count($campaigns)) return false;
        $this->persist($filtered);
        return true;
    }

    /**
     * Compute attributed revenue + ROAS for one campaign by matching its UTM
     * triple against EventStore Purchase events.
     */
    public function performance(array $campaign, int $windowDays = 30): array
    {
        $clicks = 0;
        $orders = 0;
        $revenue= 0.0;

        $src  = strtolower((string) ($campaign['utm_source']   ?? ''));
        $med  = strtolower((string) ($campaign['utm_medium']   ?? ''));
        $name = strtolower((string) ($campaign['utm_campaign'] ?? ''));

        $journeysSeen = [];

        for ($i = $windowDays - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            foreach ($this->store->readDate($date) as $row) {
                $utm = $row['utm'] ?? [];
                $matchesSource   = $src  ? strtolower($utm['source']   ?? '') === $src  : true;
                $matchesMedium   = $med  ? strtolower($utm['medium']   ?? '') === $med  : true;
                $matchesCampaign = $name ? strtolower($utm['campaign'] ?? '') === $name : true;
                if (!$matchesSource || !$matchesMedium || !$matchesCampaign) continue;

                if (($row['event'] ?? '') === 'PageView' || ($row['event'] ?? '') === 'ViewContent') {
                    $clicks++;
                }
                if (($row['event'] ?? '') === 'Purchase') {
                    $anon = $row['anon_id'] ?? null;
                    if ($anon && !isset($journeysSeen[$anon])) {
                        $orders++;
                        $revenue += (float) ($row['value'] ?? 0);
                        $journeysSeen[$anon] = true;
                    }
                }
            }
        }

        $spend  = (float) ($campaign['ad_spend'] ?? 0);
        $roas   = $spend > 0 ? round($revenue / $spend, 2) : null;
        $cpa    = $orders > 0 && $spend > 0 ? round($spend / $orders, 2) : null;
        $cpc    = $clicks > 0 && $spend > 0 ? round($spend / $clicks, 2) : null;
        $cvr    = $clicks > 0 ? round(($orders / $clicks) * 100, 2) : 0;

        return [
            'clicks'  => $clicks,
            'orders'  => $orders,
            'revenue' => round($revenue, 2),
            'spend'   => $spend,
            'roas'    => $roas,
            'cpa'     => $cpa,
            'cpc'     => $cpc,
            'cvr_pct' => $cvr,
        ];
    }

    public function buildTaggedUrl(string $url, array $utm): string
    {
        $utm = array_filter($utm, fn ($v) => !is_null($v) && $v !== '');
        if (empty($utm)) return $url;

        $parts = parse_url($url);
        $query = [];
        if (!empty($parts['query'])) parse_str($parts['query'], $query);

        foreach ($utm as $k => $v) {
            $query[$k] = $v;
        }

        $rebuilt = ($parts['scheme'] ?? 'https') . '://'
                 . ($parts['host'] ?? '')
                 . ($parts['path'] ?? '/');
        $rebuilt .= '?' . http_build_query($query);
        if (!empty($parts['fragment'])) $rebuilt .= '#' . $parts['fragment'];
        return $rebuilt;
    }

    protected function generateShortCode(array $existing): string
    {
        $used = array_column($existing, 'short_code');
        do {
            $code = strtolower(Str::random(6));
        } while (in_array($code, $used, true));
        return $code;
    }

    protected function persist(array $campaigns): void
    {
        BusinessSetting::updateOrCreate(
            ['type' => 'marketing_campaigns'],
            ['value' => json_encode(array_values($campaigns))]
        );
    }
}
