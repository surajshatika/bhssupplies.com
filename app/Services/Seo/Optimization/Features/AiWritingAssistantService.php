<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class AiWritingAssistantService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $task     = $payload['task'] ?? 'improve';
        $content  = $payload['content'] ?? '';
        $keyword  = $payload['keyword'] ?? '';
        $tone     = $payload['tone'] ?? 'professional';
        $length   = $payload['length'] ?? 'medium';
        $type     = $payload['content_type'] ?? 'product_description';

        $systemPrompt = "You are an expert SEO content writer. You write clear, engaging, "
            . "SEO-optimized content that ranks well and converts readers into customers. "
            . "Tone: {$tone}. Length: {$length}.";

        $prompt = $this->buildTaskPrompt($task, $content, $keyword, $type, $tone, $length);
        $result = $this->ai()->generate($prompt, $systemPrompt);

        return [
            'task'          => $task,
            'original'      => $content,
            'result'        => $result,
            'keyword'       => $keyword,
            'content_type'  => $type,
            'word_count'    => str_word_count($result ?? ''),
        ];
    }

    protected function buildTaskPrompt(string $task, string $content, string $keyword, string $type, string $tone, string $length): string
    {
        $lengthMap = ['short' => '100-150 words', 'medium' => '200-300 words', 'long' => '400-600 words'];
        $wordTarget = $lengthMap[$length] ?? '200-300 words';

        switch ($task) {
            case 'improve':
                return "Improve the following content for SEO. Focus keyword: '{$keyword}'. "
                    . "Target length: {$wordTarget}. Content type: {$type}.\n\n"
                    . "Original:\n{$content}\n\n"
                    . "Provide an improved version that is more engaging, SEO-optimized, and conversion-focused.";
            case 'generate':
                return "Write a {$wordTarget} {$type} about '{$keyword}' in {$tone} tone. "
                    . "Include the keyword naturally 2-3 times. Make it SEO-optimized and compelling. "
                    . ($content ? "Context: {$content}" : '');
            case 'paraphrase':
                return "Paraphrase the following content to make it unique and SEO-friendly. "
                    . "Maintain the meaning but improve clarity and keyword usage (focus: '{$keyword}').\n\n{$content}";
            case 'expand':
                return "Expand the following content to {$wordTarget}. "
                    . "Add relevant details, SEO keywords related to '{$keyword}', and make it more comprehensive.\n\n{$content}";
            case 'summarize':
                return "Create a concise SEO-optimized summary (2-3 sentences) of this content, "
                    . "including the keyword '{$keyword}':\n\n{$content}";
            case 'meta_description':
                return "Write an SEO meta description (120-155 characters) for content about '{$keyword}'.\n\n"
                    . "Content context:\n{$content}\n\n"
                    . "Requirements: Include keyword naturally, compelling call-to-action, under 155 chars.";
            case 'title_variants':
                return "Generate 5 SEO-optimized title tag variants (50-60 chars each) for content about '{$keyword}'.\n\n"
                    . "Context:\n{$content}\n\n"
                    . "Mix emotional triggers, numbers, and power words. Format as numbered list.";
            case 'faq':
                return "Generate 5 FAQ items (question and answer) for content about '{$keyword}'.\n\n"
                    . "Context:\n{$content}\n\n"
                    . "Format: Q: [question]\nA: [answer]\n\n";
            default:
                return "Process the following content for SEO improvement (keyword: '{$keyword}'):\n\n{$content}";
        }
    }
}
