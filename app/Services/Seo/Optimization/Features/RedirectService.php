<?php

namespace App\Services\Seo\Optimization\Features;

use App\Models\SeoRedirect;
use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class RedirectService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $urls = $payload['links'] ?? [];
        if (empty($urls)) {
            $urls = [($payload['url'] ?? url('/'))];
        }

        $chains = [];
        foreach (array_slice($urls, 0, 20) as $url) {
            $chain = $this->traceRedirectChain($url);
            if (count($chain) > 1) {
                $chains[] = [
                    'original_url'  => $url,
                    'chain'         => $chain,
                    'hops'          => count($chain) - 1,
                    'final_url'     => end($chain),
                    'is_problematic' => count($chain) > 2,
                ];
            }
        }

        $chainSummary = !empty($chains)
            ? json_encode(array_slice($chains, 0, 5))
            : 'No redirect chains detected from provided URLs.';

        $prompt = <<<PROMPT
Analyze the following redirect chains and provide an optimization plan.

Redirect Chains Found:
{$chainSummary}

Return JSON:
{
  "total_chains": 0,
  "problematic_chains": 0,
  "chains": [],
  "fix_recommendations": [
    {"original_url": "", "direct_target": "", "action": ""}
  ],
  "impact_summary": "",
  "estimated_link_equity_loss_percent": 0
}
PROMPT;

        $result = $this->askForJson($prompt, 'You are a technical SEO redirect chain specialist.', [
            'total_chains'                     => count($chains),
            'problematic_chains'               => count(array_filter($chains, fn($c) => $c['is_problematic'])),
            'chains'                           => $chains,
            'fix_recommendations'              => [],
            'impact_summary'                   => '',
            'estimated_link_equity_loss_percent' => 0,
        ]);

        $result['chains'] = $result['chains'] ?: $chains;
        return $result;
    }

    private function traceRedirectChain(string $url): array
    {
        $chain = [$url];
        $current = $url;
        $maxHops = 5;

        for ($i = 0; $i < $maxHops; $i++) {
            try {
                $response = Http::timeout(5)
                    ->withOptions(['allow_redirects' => false])
                    ->get($current);

                if (in_array($response->status(), [301, 302, 307, 308])) {
                    $location = $response->header('Location');
                    if (!$location || $location === $current) {
                        break;
                    }
                    $chain[]  = $location;
                    $current  = $location;
                } else {
                    break;
                }
            } catch (\Throwable $e) {
                break;
            }
        }

        return $chain;
    }
}
