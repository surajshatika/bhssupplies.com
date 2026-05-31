<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use App\Models\SeoScoreHistory;

class SeoRevisionsService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $action  = $payload['action'] ?? 'list';
        $url     = $payload['url'] ?? null;

        switch ($action) {
            case 'save':
                return $this->saveRevision($payload);
            case 'compare':
                return $this->compareRevisions($payload);
            case 'history':
                return $this->getHistory($url);
            default:
                return $this->listRevisions($payload);
        }
    }

    protected function saveRevision(array $payload): array
    {
        $data = [
            'url'         => $payload['url'] ?? '',
            'title'       => $payload['title'] ?? '',
            'description' => $payload['description'] ?? '',
            'keyword'     => $payload['keyword'] ?? '',
            'score'       => $payload['score'] ?? 0,
            'grade'       => $payload['grade'] ?? 'N/A',
            'notes'       => $payload['notes'] ?? '',
            'metrics'     => $payload,
        ];

        try {
            SeoScoreHistory::create([
                'url'         => $data['url'],
                'score'       => (int) $data['score'],
                'grade'       => $data['grade'],
                'recorded_at' => now(),
                'metrics'     => $data['metrics'],
            ]);
        } catch (\Throwable $e) {
            logger()->warning('SEO revision save failed', ['error' => $e->getMessage()]);

            return ['saved' => false, 'data' => $data, 'error' => 'SEO revision could not be saved.'];
        }

        return ['saved' => true, 'data' => $data];
    }

    protected function compareRevisions(array $payload): array
    {
        $url    = $payload['url'] ?? '';
        $limit  = (int) ($payload['limit'] ?? 5);

        try {
            $revisions = SeoScoreHistory::where('url', $url)
                ->orderByDesc('recorded_at')
                ->limit($limit)
                ->get(['url', 'score', 'grade', 'recorded_at', 'metrics'])
                ->toArray();
        } catch (\Throwable $e) {
            $revisions = [];
        }

        if (count($revisions) < 2) {
            return ['revisions' => $revisions, 'comparison' => null];
        }

        $latest   = $revisions[0];
        $previous = $revisions[1];
        $change   = ($latest['score'] ?? 0) - ($previous['score'] ?? 0);

        $prompt = "Compare these two SEO revisions for URL: {$url}\n"
            . "Previous ({$previous['recorded_at']}): Score {$previous['score']} ({$previous['grade']})\n"
            . "Latest ({$latest['recorded_at']}): Score {$latest['score']} ({$latest['grade']})\n"
            . "Score change: " . ($change >= 0 ? "+{$change}" : $change) . "\n\n"
            . "Analyze what likely changed, why the score changed, and what to do next.";

        $aiAnalysis = $this->ai()->generate($prompt, 'You are an SEO revision analysis expert.');

        return [
            'revisions'   => $revisions,
            'score_change'=> $change,
            'trend'       => $change > 0 ? 'improving' : ($change < 0 ? 'declining' : 'stable'),
            'ai_analysis' => $aiAnalysis,
        ];
    }

    protected function getHistory(?string $url): array
    {
        try {
            $query = SeoScoreHistory::query()->orderByDesc('recorded_at')->limit(50);
            if ($url) $query->where('url', $url);
            return $query->get(['url', 'score', 'grade', 'recorded_at'])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function listRevisions(array $payload): array
    {
        try {
            return SeoScoreHistory::query()
                ->orderByDesc('recorded_at')
                ->limit(30)
                ->get(['url', 'score', 'grade', 'recorded_at'])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
