<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class CompetitorGapService extends AbstractSeoService
{
    public function analyze(array $payload): array
    {
        $ourKeywords   = $this->csvList($payload['our_keywords']   ?? '');
        $comp1Keywords = $this->csvList($payload['comp1_keywords'] ?? '');
        $comp2Keywords = $this->csvList($payload['comp2_keywords'] ?? '');
        $url           = $payload['url'] ?? '';

        $prompt = <<<PROMPT
Perform a competitor keyword gap analysis.

Our Website: {$url}
Our Keywords: {$ourKeywords}
Competitor 1 Keywords: {$comp1Keywords}
Competitor 2 Keywords: {$comp2Keywords}

Identify:
1. Keywords competitors rank for that we don't (gaps)
2. Keywords we rank for that competitors don't (advantages)
3. Keywords all three target (battleground)
4. Quick-win opportunities

Return JSON:
{
  "gap_keywords": [
    {"keyword": "", "competitor": "", "estimated_volume": "", "difficulty": "low|medium|high", "opportunity_score": 0}
  ],
  "our_advantages": [],
  "battleground_keywords": [],
  "quick_wins": [],
  "content_ideas": [],
  "priority_targets": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are a competitive SEO and keyword strategy expert.', [
            'gap_keywords'          => [],
            'our_advantages'        => [],
            'battleground_keywords' => [],
            'quick_wins'            => [],
            'content_ideas'         => [],
            'priority_targets'      => [],
        ]);
    }
}
