<?php

namespace App\Services\SocialMedia;

use App\Services\SocialMedia\AI\SocialAiProviderManager;

class SocialAiContentGenerator
{
    public function generateForPlatform(
        string $platform,
        string $topic,
        string $tone = 'professional',
        string $provider = 'openai',
        array  $context = []
    ): ?string {
        $ai         = SocialAiProviderManager::make($provider);
        $config     = config("social_media.platforms.{$platform}", []);
        $charLimit  = $config['char_limit'] ?? 500;
        $label      = $config['label'] ?? ucfirst($platform);
        $toneDesc   = config("social_media.tones.{$tone}", $tone);
        $keywords   = !empty($context['keywords']) ? 'Keywords: '.$context['keywords'] : '';
        $audience   = !empty($context['target_audience']) ? 'Target audience: '.$context['target_audience'] : '';
        $brandName  = get_setting('website_name', 'our brand');
        $websiteUrl = url('/');

        $systemPrompt = "You are a social media expert for {$brandName} ({$websiteUrl}). "
            . "Write compelling social media content. Be concise, engaging, and on-brand. "
            . "Always include relevant hashtags. Never use placeholder text.";

        $prompt = "Write a {$label} post about: {$topic}\n"
            . "Tone: {$toneDesc}\n"
            . ($keywords ? "{$keywords}\n" : '')
            . ($audience ? "{$audience}\n" : '')
            . "Max length: {$charLimit} characters.\n"
            . "Include 3-5 relevant hashtags.\n"
            . "Output only the post content, nothing else.";

        return $ai->generate($prompt, $systemPrompt, ['max_tokens' => 600]);
    }

    public function generateMultiPlatform(
        array  $platforms,
        string $topic,
        string $tone = 'professional',
        string $provider = 'openai',
        array  $context = []
    ): array {
        $results = [];
        foreach ($platforms as $platform) {
            $results[$platform] = $this->generateForPlatform($platform, $topic, $tone, $provider, $context);
        }
        return $results;
    }

    public function refineContent(string $content, string $instruction, string $provider = 'openai'): ?string
    {
        $ai = SocialAiProviderManager::make($provider);

        $prompt = "Refine this social media post according to the instruction.\n\n"
            . "Original post:\n{$content}\n\n"
            . "Instruction: {$instruction}\n\n"
            . "Output only the refined post, nothing else.";

        return $ai->generate($prompt, null, ['max_tokens' => 600]);
    }

    public function generateHashtags(string $topic, int $count = 10, string $provider = 'openai'): array
    {
        $ai     = SocialAiProviderManager::make($provider);
        $prompt = "Generate {$count} relevant hashtags for this topic: {$topic}\n"
            . "Output only the hashtags, one per line, with # prefix. No extra text.";

        $result = $ai->generate($prompt, null, ['max_tokens' => 200]);
        if (!$result) return [];

        preg_match_all('/#\w+/', $result, $matches);
        return array_slice($matches[0] ?? [], 0, $count);
    }

    public function generateContentVariants(
        string $content,
        int    $count = 3,
        string $provider = 'openai'
    ): array {
        $ai = SocialAiProviderManager::make($provider);

        $prompt = "Generate {$count} different variations of this social media post. "
            . "Keep the same message but vary the phrasing, tone, and structure.\n\n"
            . "Original:\n{$content}\n\n"
            . "Output each variant numbered 1., 2., 3. with a blank line between them.";

        $result = $ai->generate($prompt, null, ['max_tokens' => 1200]);
        if (!$result) return [$content];

        // Split by numbered list
        $variants = preg_split('/\n\d+\.\s+/', "\n".$result);
        $variants = array_filter(array_map('trim', $variants));
        return array_values(array_slice($variants, 0, $count)) ?: [$content];
    }
}
