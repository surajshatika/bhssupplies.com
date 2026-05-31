<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;
use App\Services\Seo\Providers\SeoProviderManager;

class AiAssistantService extends AbstractSeoService
{
    protected array $systemContext;

    public function __construct()
    {
        parent::__construct();
        $this->systemContext = [
            'site_name'   => get_setting('website_name', config('app.name')),
            'site_url'    => url('/'),
            'business'    => get_setting('seo_local_business_name', get_setting('website_name')),
            'niche'       => 'B2B e-commerce safety and health supplies',
        ];
    }

    public function chat(array $payload): array
    {
        $message      = $payload['message'] ?? '';
        $history      = $payload['history'] ?? [];
        $provider     = $payload['provider'] ?? get_setting('seo_suite_default_provider', 'openai');
        $context      = $payload['context'] ?? 'general'; // general, on_page, off_page, technical

        if (is_string($history)) {
            $history = json_decode($history, true);
        }
        if (!is_array($history)) {
            $history = [];
        }

        if (empty($message)) {
            return ['error' => 'Message is required.'];
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        $fullPrompt   = $this->buildFullPrompt($message, $history, $context);

        $ai       = SeoProviderManager::make($provider);
        $response = $ai->generate($fullPrompt, $systemPrompt);

        return [
            'message'   => $message,
            'response'  => $response,
            'provider'  => $ai->getName(),
            'context'   => $context,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function handle(array $payload): array
    {
        return $this->chat($payload);
    }

    public function quickAction(string $action, array $payload): array
    {
        $prompts = [
            'audit_checklist'   => "Create a comprehensive SEO audit checklist for " . $this->systemContext['site_url'],
            'content_ideas'     => "Generate 10 SEO content ideas for " . $this->systemContext['niche'],
            'competitor_strategy' => "What are the best SEO strategies to outrank competitors in " . $this->systemContext['niche'] . "?",
            'technical_tips'    => "List the top 10 technical SEO improvements for an e-commerce site",
            'local_seo_tips'    => "Best local SEO strategies for " . $this->systemContext['business'],
            'link_building'     => "Effective white-hat link building strategies for " . $this->systemContext['niche'],
        ];

        $prompt = $prompts[$action] ?? "Provide SEO advice for {$action}";
        $system = $this->buildSystemPrompt('general');
        $ai     = SeoProviderManager::make($payload['provider'] ?? null);

        return [
            'action'   => $action,
            'response' => $ai->generate($prompt, $system),
            'provider' => $ai->getName(),
        ];
    }

    protected function buildSystemPrompt(string $context): string
    {
        $siteName  = $this->systemContext['site_name'];
        $siteUrl   = $this->systemContext['site_url'];
        $niche     = $this->systemContext['niche'];

        $base = "You are an expert AI SEO assistant for {$siteName} ({$siteUrl}), a {$niche} website. "
            . "You have deep knowledge of SEO, content marketing, technical SEO, local SEO, and e-commerce SEO. "
            . "Provide actionable, specific, practical advice. Be concise but thorough.";

        $contextMap = [
            'on_page'   => " Focus on on-page SEO: meta tags, content optimization, keyword usage, schema markup.",
            'off_page'  => " Focus on off-page SEO: link building, brand mentions, social signals, outreach.",
            'technical' => " Focus on technical SEO: site speed, crawlability, indexation, Core Web Vitals.",
            'local'     => " Focus on local SEO: Google Business Profile, citations, local keywords, reviews.",
        ];

        return $base . ($contextMap[$context] ?? '');
    }

    protected function buildFullPrompt(string $message, array $history, string $context): string
    {
        if (empty($history)) {
            return $message;
        }

        $historyText = '';
        foreach (array_slice($history, -6) as $turn) {
            $role = $turn['role'] === 'user' ? 'User' : 'Assistant';
            $historyText .= "{$role}: " . ($turn['content'] ?? '') . "\n";
        }

        return "Previous conversation:\n{$historyText}\nUser: {$message}";
    }
}
