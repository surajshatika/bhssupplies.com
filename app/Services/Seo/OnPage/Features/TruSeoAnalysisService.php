<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

class TruSeoAnalysisService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $keyword  = $payload['keyword'] ?? '';
        $content  = $payload['content'] ?? '';
        $title    = $payload['title'] ?? '';
        $url      = $payload['url'] ?? '';
        $description = $payload['description'] ?? '';

        $checks = $this->runChecks($keyword, $title, $description, $content, $url);
        $score  = $this->calcScore($checks);

        $prompt = "You are a TruSEO analysis expert. Given the following on-page data:\n"
            . "- Title: {$title}\n"
            . "- Focus Keyword: {$keyword}\n"
            . "- Meta Description: {$description}\n"
            . "- Content Length: " . str_word_count($content) . " words\n"
            . "- URL: {$url}\n"
            . "- Automated check score: {$score}/100\n\n"
            . "Provide a detailed TruSEO on-page analysis with:\n"
            . "1. Overall score justification\n"
            . "2. Critical issues to fix\n"
            . "3. Good practices detected\n"
            . "4. Specific recommendations to reach 90+ score\n"
            . "Format as structured bullet points.";

        $aiAnalysis = $this->ai()->generate($prompt, 'You are an expert SEO analyst. Be concise and actionable.');

        return [
            'score'      => $score,
            'grade'      => $this->scoreGrade($score),
            'checks'     => $checks,
            'ai_analysis'=> $aiAnalysis,
            'keyword'    => $keyword,
        ];
    }

    public function runChecks($keyword, $title, $description, $content, $url): array
    {
        $kw = mb_strtolower($keyword);
        $checks = [];

        // Title checks
        $checks['title_has_keyword'] = [
            'label' => 'Keyword in Title',
            'pass'  => $kw && stripos($title, $kw) !== false,
            'weight'=> 15,
        ];
        $checks['title_length'] = [
            'label' => 'Title Length (30-60 chars)',
            'pass'  => strlen($title) >= 30 && strlen($title) <= 60,
            'weight'=> 10,
        ];

        // Description checks
        $checks['desc_has_keyword'] = [
            'label' => 'Keyword in Meta Description',
            'pass'  => $kw && stripos($description, $kw) !== false,
            'weight'=> 8,
        ];
        $checks['desc_length'] = [
            'label' => 'Meta Description Length (120-160 chars)',
            'pass'  => strlen($description) >= 120 && strlen($description) <= 160,
            'weight'=> 7,
        ];

        // Content checks
        $wordCount = str_word_count($content);
        $checks['content_length'] = [
            'label' => 'Content Length (>300 words)',
            'pass'  => $wordCount >= 300,
            'weight'=> 12,
        ];
        $density = ($kw && $wordCount > 0) ? (substr_count(mb_strtolower($content), $kw) / $wordCount) * 100 : 0;
        $checks['keyword_density'] = [
            'label' => 'Keyword Density (0.5%-2.5%)',
            'pass'  => $density >= 0.5 && $density <= 2.5,
            'weight'=> 10,
        ];
        $checks['keyword_in_first100'] = [
            'label' => 'Keyword in First 100 Words',
            'pass'  => $kw && stripos(implode(' ', array_slice(explode(' ', $content), 0, 100)), $kw) !== false,
            'weight'=> 8,
        ];

        // URL check
        $slug = basename(rtrim($url, '/'));
        $checks['keyword_in_url'] = [
            'label' => 'Keyword in URL',
            'pass'  => $kw && stripos($slug, str_replace(' ', '-', $kw)) !== false,
            'weight'=> 8,
        ];
        $checks['url_length'] = [
            'label' => 'URL Length (<75 chars)',
            'pass'  => strlen($url) < 75,
            'weight'=> 5,
        ];

        // Heading check (H1 presence)
        $checks['has_h1'] = [
            'label' => 'H1 Tag Present',
            'pass'  => preg_match('/<h1/i', $content) || strlen($title) > 0,
            'weight'=> 10,
        ];

        // Internal links
        $linkCount = preg_match_all('/<a\s[^>]*href/i', $content);
        $checks['has_internal_links'] = [
            'label' => 'Has Internal Links',
            'pass'  => $linkCount > 0,
            'weight'=> 7,
        ];

        return $checks;
    }

    protected function calcScore(array $checks): int
    {
        $totalWeight = array_sum(array_column($checks, 'weight'));
        $earnedWeight = array_sum(array_map(fn($c) => $c['pass'] ? $c['weight'] : 0, $checks));
        return $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;
    }

    protected function scoreGrade(int $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
}
