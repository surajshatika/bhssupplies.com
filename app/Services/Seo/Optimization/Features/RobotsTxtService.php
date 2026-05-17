<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Facades\File;

class RobotsTxtService extends AbstractSeoService
{
    public function optimize(array $payload): array
    {
        $url     = $this->normalizeUrl($payload['url'] ?? url('/'));
        $content = $payload['content'] ?? $this->fetchCurrentRobots($url);

        $prompt = <<<PROMPT
Analyze and optimize the robots.txt file for the following website.

Website URL: {$url}
Current robots.txt content:
{$content}

Optimize it for best SEO performance:
- Block admin/checkout/cart pages from crawling
- Allow CSS/JS assets for Google rendering
- Include sitemap URL
- Avoid blocking product/category pages

Return JSON:
{
  "current_issues": [],
  "optimized_robots_txt": "",
  "changes_made": [],
  "blocked_paths": [],
  "allowed_paths": [],
  "sitemap_line": "",
  "recommendations": []
}
PROMPT;

        $result = $this->askForJson($prompt, 'You are a technical SEO specialist focused on crawl optimization.', [
            'current_issues'      => [],
            'optimized_robots_txt' => '',
            'changes_made'        => [],
            'blocked_paths'       => [],
            'allowed_paths'       => [],
            'sitemap_line'        => '',
            'recommendations'     => [],
        ]);

        // Save optimized robots.txt if generated
        if (!empty($result['optimized_robots_txt'])) {
            try {
                File::put(public_path('robots.txt'), $result['optimized_robots_txt']);
                $result['saved'] = true;
            } catch (\Throwable $e) {
                $result['saved'] = false;
            }
        }

        return $result;
    }

    private function fetchCurrentRobots(string $url): string
    {
        $robotsUrl = rtrim($url, '/').'/robots.txt';
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($robotsUrl);
            if ($response->successful()) {
                return substr($response->body(), 0, 3000);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 'Could not fetch current robots.txt';
    }
}
