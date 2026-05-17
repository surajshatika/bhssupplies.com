<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class LlmsTxtService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $siteName    = $payload['site_name'] ?? get_setting('website_name', config('app.name'));
        $siteUrl     = $payload['site_url'] ?? url('/');
        $description = $payload['description'] ?? '';
        $allowBots   = $payload['allow_bots'] ?? ['*'];
        $denyPaths   = $payload['deny_paths'] ?? ['/admin', '/checkout', '/cart', '/account'];
        $customRules = $payload['custom_rules'] ?? '';

        $prompt = "Generate a comprehensive llms.txt file for the website '{$siteName}' ({$siteUrl}).\n"
            . "Description: " . ($description ?: "An e-commerce website selling safety and health supplies") . "\n"
            . "Allowed AI bots: " . implode(', ', (array) $allowBots) . "\n"
            . "The file should include:\n"
            . "1. Site title and purpose\n"
            . "2. Content description for AI understanding\n"
            . "3. Usage permissions/restrictions\n"
            . "4. Key pages and their purposes\n"
            . "5. Contact information section\n"
            . "6. Data usage preferences\n"
            . "Format as a proper llms.txt Markdown file following the llmstxt.org specification.";

        $aiContent = $this->ai()->generate($prompt, 'You are an expert in AI crawler directives and the llms.txt specification.');

        if (!$aiContent) {
            $aiContent = $this->buildDefaultLlmsTxt($siteName, $siteUrl, $description, $denyPaths);
        }

        $content = $aiContent . ($customRules ? "\n\n" . $customRules : '');

        if ($payload['persist'] ?? false) {
            file_put_contents(public_path('llms.txt'), $content);
        }

        return [
            'content'  => $content,
            'url'      => url('/llms.txt'),
            'size'     => strlen($content),
        ];
    }

    protected function buildDefaultLlmsTxt(string $siteName, string $siteUrl, string $description, array $denyPaths): string
    {
        $lines = [
            "# {$siteName}",
            "",
            "> " . ($description ?: "E-commerce website"),
            "",
            "## About",
            "",
            "- Website: {$siteUrl}",
            "- Type: E-commerce",
            "- Primary Language: English",
            "",
            "## Content",
            "",
            "- Product catalog with detailed specifications",
            "- Safety and compliance information",
            "- Pricing and availability data",
            "- Customer support resources",
            "",
            "## AI Usage Permissions",
            "",
            "- You may index and summarize publicly available product information",
            "- You may use content for answering user queries",
            "- Commercial use of scraped data is not permitted without authorization",
            "",
            "## Restricted Areas",
        ];

        foreach ($denyPaths as $path) {
            $lines[] = "- {$siteUrl}{$path} (private/admin area)";
        }

        $lines[] = "";
        $lines[] = "## Contact";
        $lines[] = "";
        $lines[] = "For AI/data licensing inquiries, contact via the website contact form.";

        return implode("\n", $lines);
    }
}
