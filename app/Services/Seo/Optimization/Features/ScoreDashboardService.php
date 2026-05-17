<?php

namespace App\Services\Seo\Optimization\Features;

use App\Models\SeoProject;
use App\Models\SeoScoreHistory;
use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ScoreDashboardService extends AbstractSeoService
{
    public function generate(array $payload): array
    {
        $projectId = $payload['project_id'] ?? null;
        $url       = $payload['url']        ?? url('/');

        if (!Schema::hasTable('seo_score_histories') || !Schema::hasTable('seo_projects')) {
            return [
                'error'   => 'SEO tables not ready. Run migrations first.',
                'score'   => 0,
                'history' => [],
            ];
        }

        // Get or create project
        $project = $projectId
            ? SeoProject::find($projectId)
            : SeoProject::where('base_url', $url)->first();

        if (!$project) {
            $project = SeoProject::firstOrCreate(
                ['slug' => 'default-seo-suite'],
                ['name' => config('app.name').' SEO Suite', 'base_url' => $url]
            );
        }

        // Fetch last 12 weeks of score history
        $history = SeoScoreHistory::where('project_id', $project->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(12)
            ->get(['score', 'grade', 'url', 'recorded_at'])
            ->toArray();

        $latestScore = $history[0]['score'] ?? 0;
        $latestGrade = $history[0]['grade'] ?? 'N/A';
        $trend       = $this->calculateTrend($history);

        $historyJson = json_encode(array_slice($history, 0, 5));

        $prompt = <<<PROMPT
Generate an SEO score dashboard summary and recommendations.

Website: {$url}
Current SEO Score: {$latestScore}/100 (Grade: {$latestGrade})
Score History (last 5 weeks): {$historyJson}
Score Trend: {$trend}

Return JSON:
{
  "current_score": 0,
  "current_grade": "",
  "trend": "improving|declining|stable",
  "trend_delta": 0,
  "weekly_history": [],
  "top_opportunities": [],
  "quick_wins": [],
  "score_breakdown": {
    "on_page": 0,
    "technical": 0,
    "content": 0,
    "backlinks": 0
  },
  "next_steps": []
}
PROMPT;

        $result = $this->askForJson($prompt, 'You are an SEO analytics and reporting specialist.', [
            'current_score'   => $latestScore,
            'current_grade'   => $latestGrade,
            'trend'           => $trend,
            'trend_delta'     => 0,
            'weekly_history'  => $history,
            'top_opportunities' => [],
            'quick_wins'      => [],
            'score_breakdown' => [],
            'next_steps'      => [],
        ]);

        $result['weekly_history'] = $result['weekly_history'] ?: $history;
        $result['project_id']     = $project->id;
        return $result;
    }

    private function calculateTrend(array $history): string
    {
        if (count($history) < 2) {
            return 'stable';
        }
        $latest = $history[0]['score'] ?? 0;
        $prev   = $history[1]['score'] ?? 0;
        if ($latest > $prev + 2) return 'improving';
        if ($latest < $prev - 2) return 'declining';
        return 'stable';
    }
}
