<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\Http;

class BrokenLinkService extends AbstractSeoService
{
    public function detect(array $payload): array
    {
        $links = $payload['links'] ?? [];
        if (empty($links)) {
            return [
                'broken'        => [],
                'working'       => [],
                'total_checked' => 0,
                'message'       => 'No links provided for checking.',
            ];
        }

        $broken  = [];
        $working = [];

        foreach (array_slice($links, 0, 50) as $link) {
            $status = $this->checkLink($link);
            if ($status['broken']) {
                $broken[] = $status;
            } else {
                $working[] = $link;
            }
        }

        $brokenJson = json_encode(array_slice($broken, 0, 10));

        $prompt = <<<PROMPT
Analyze the following broken links and provide fix recommendations.

Broken Links Found:
{$brokenJson}

For each broken link, suggest:
1. Whether to fix the URL (typo, moved content)
2. Whether to replace with a relevant working page
3. Whether to remove the link entirely

Return JSON:
{
  "broken_links": [],
  "fix_plan": [
    {"url": "", "status_code": 0, "recommendation": "fix|replace|remove", "suggested_replacement": ""}
  ],
  "seo_impact": "",
  "priority_fixes": []
}
PROMPT;

        $aiResult = $this->askForJson($prompt, 'You are a technical SEO specialist focused on link health.', [
            'broken_links' => $broken,
            'fix_plan'     => [],
            'seo_impact'   => '',
            'priority_fixes' => [],
        ]);

        return array_merge($aiResult, [
            'broken'        => $broken,
            'working'       => $working,
            'total_checked' => count($links),
            'broken_count'  => count($broken),
        ]);
    }

    private function checkLink(string $url): array
    {
        try {
            $response = Http::timeout(8)->head($url);
            $code = $response->status();
            return [
                'url'        => $url,
                'status_code' => $code,
                'broken'     => $code >= 400,
            ];
        } catch (\Throwable $e) {
            return [
                'url'        => $url,
                'status_code' => 0,
                'broken'     => true,
                'error'      => $e->getMessage(),
            ];
        }
    }
}
