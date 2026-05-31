<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

/**
 * AI writing assistant — AIOSEO "AI Assistant" + "AI Content Generator" parity.
 *
 * One entry point ({@see handle()}) drives many task modes: generate / improve /
 * rewrite modes (expand, shorten, simplify, formal, persuasive, change tone,
 * fix grammar), plus structured generators (key points / TL;DR, comparison
 * table, FAQ, email copy, translation, meta + title variants).
 *
 * Output is Canada/B2B-aware to match the storefront. Each mode advertises
 * whether it returns HTML (`format`) so the UI can render it correctly.
 */
class AiWritingAssistantService extends AbstractSeoService
{
    /** Modes whose output should be treated as raw HTML, not plain text. */
    protected array $htmlModes = ['table', 'generate', 'expand', 'improve', 'key_points'];

    public function handle(array $payload): array
    {
        $task           = $payload['task'] ?? 'improve';
        $content        = trim((string) ($payload['content'] ?? ''));
        $keyword        = trim((string) ($payload['keyword'] ?? ''));
        $tone           = $payload['tone'] ?? 'professional';
        $length         = $payload['length'] ?? 'medium';
        $type           = $payload['content_type'] ?? 'product_description';
        $targetLanguage = $payload['target_language'] ?? 'French';
        $emailType      = $payload['email_type'] ?? 'newsletter';

        $systemPrompt = "You are an expert SEO copywriter for a Canadian B2B e-commerce supplier "
            . "(safety, HVAC, plumbing and industrial supplies serving Mississauga, Brampton, Toronto and the GTA). "
            . "You write clear, engaging, SEO-optimized copy that ranks and converts. "
            . "Tone: {$tone}. Length: {$length}. Output clean content with no markdown code fences.";

        $ai = !empty($payload['provider'])
            ? \App\Services\Seo\Providers\SeoProviderManager::make($payload['provider'])
            : $this->ai();

        $prompt = $this->buildTaskPrompt($task, compact('content', 'keyword', 'type', 'tone', 'length', 'targetLanguage', 'emailType'));
        $result = (string) $ai->generate($prompt, $systemPrompt);
        $result = $this->stripFences($result);

        return [
            'task'         => $task,
            'original'     => $content,
            'result'       => $result,
            'keyword'      => $keyword,
            'content_type' => $type,
            'format'       => in_array($task, $this->htmlModes, true) ? 'html' : 'text',
            'word_count'   => str_word_count(strip_tags($result)),
            'provider'     => $ai->getName(),
            'provider_attempts' => method_exists($ai, 'getAttempts') ? $ai->getAttempts() : [],
            'failover_used' => method_exists($ai, 'usedFallback') ? $ai->usedFallback() : false,
        ];
    }

    /** Human-readable mode list for the UI mode picker. */
    public static function modes(): array
    {
        return [
            'generate'        => 'Generate new content',
            'improve'         => 'Improve & optimize',
            'expand'          => 'Expand (add detail)',
            'shorten'         => 'Shorten (condense)',
            'simplify'        => 'Simplify (Grade 8)',
            'formal'          => 'Make formal (B2B)',
            'persuasive'      => 'Make persuasive (sales)',
            'change_tone'     => 'Change tone',
            'paraphrase'      => 'Paraphrase (unique)',
            'fix_grammar'     => 'Fix grammar & spelling',
            'key_points'      => 'Key points / TL;DR',
            'table'           => 'Comparison table',
            'faq'             => 'FAQ + answers',
            'email'           => 'Email copy',
            'translate'       => 'Translate',
            'summarize'       => 'Summarize',
            'meta_description'=> 'Meta description',
            'title_variants'  => 'Title variants',
        ];
    }

    protected function buildTaskPrompt(string $task, array $p): string
    {
        $content   = $p['content'];
        $keyword   = $p['keyword'];
        $type      = $p['type'];
        $tone      = $p['tone'];
        $length    = $p['length'];
        $kwLine    = $keyword !== '' ? "Focus keyword: '{$keyword}'. Use it naturally, do not stuff. " : '';

        $lengthMap  = ['short' => '100-150 words', 'medium' => '200-300 words', 'long' => '400-600 words'];
        $wordTarget = $lengthMap[$length] ?? '200-300 words';

        switch ($task) {
            case 'generate':
                return "Write a {$wordTarget} {$type} about '{$keyword}' in a {$tone} tone for Canadian buyers. "
                    . "{$kwLine}Use clean HTML with an <h2> and 2-3 short paragraphs/bullets. "
                    . ($content ? "Context: {$content}" : '');

            case 'improve':
                return "Improve the following {$type} for SEO and conversions. {$kwLine}Target length: {$wordTarget}. "
                    . "Return clean HTML (h2/h3/p/ul). Keep facts; sharpen clarity, benefits, and Canada/GTA relevance.\n\n{$content}";

            case 'expand':
                return "Expand the following content to {$wordTarget}, adding relevant detail, specs, applications, "
                    . "and Canada/GTA buying guidance. {$kwLine}Return clean HTML.\n\n{$content}";

            case 'shorten':
                return "Condense the following content by ~50% while keeping every key fact and the keyword. "
                    . "{$kwLine}Return tight, scannable copy.\n\n{$content}";

            case 'simplify':
                return "Rewrite the following at a Grade 8 reading level: short sentences, plain words, active voice. "
                    . "Keep the meaning and the keyword. {$kwLine}\n\n{$content}";

            case 'formal':
                return "Rewrite the following in a formal, professional B2B tone suitable for trade and procurement buyers. "
                    . "{$kwLine}Keep it precise and credible.\n\n{$content}";

            case 'persuasive':
                return "Rewrite the following with stronger sales language: clear benefits, urgency, and a confident "
                    . "call-to-action (e.g. request trade pricing, order online). {$kwLine}Avoid hype/false claims.\n\n{$content}";

            case 'change_tone':
                return "Rewrite the following content in a {$tone} tone while preserving meaning and the keyword. "
                    . "{$kwLine}\n\n{$content}";

            case 'paraphrase':
                return "Paraphrase the following to make it unique and SEO-friendly. Keep the meaning, improve clarity "
                    . "and keyword usage. {$kwLine}\n\n{$content}";

            case 'fix_grammar':
                return "Fix only grammar, spelling, and punctuation in the following. Do not change meaning, tone, or "
                    . "wording beyond corrections. Return the corrected text.\n\n{$content}";

            case 'key_points':
                return "Extract 5-7 key takeaways from the following as a TL;DR. Return a clean HTML <ul> of concise "
                    . "bullet points (no intro text). {$kwLine}\n\n{$content}";

            case 'table':
                return "Create a comparison/specs table from the following. Return clean HTML wrapped in "
                    . "<div class=\"table-responsive\"><table class=\"table table-bordered\">…</table></div> with a <thead> "
                    . "and <tbody>. {$kwLine}\n\nSource:\n{$content}";

            case 'faq':
                return "Generate 5 genuine FAQ items (question + answer) for content about '{$keyword}' aimed at Canadian "
                    . "buyers.\nContext:\n{$content}\n\nFormat each as:\nQ: [question]\nA: [answer]\n";

            case 'email':
                return $this->emailPrompt($p['emailType'], $keyword, $content, $tone);

            case 'translate':
                return "Translate the following content into {$p['targetLanguage']}. Preserve meaning, tone, HTML tags, "
                    . "and brand/product names. Localize naturally for native speakers.\n\n{$content}";

            case 'summarize':
                return "Write a concise SEO-optimized summary (2-3 sentences) of this content"
                    . ($keyword !== '' ? ", including the keyword '{$keyword}'" : '') . ":\n\n{$content}";

            case 'meta_description':
                return "Write an SEO meta description (150-160 characters, never under 150) for content about '{$keyword}'.\n\n"
                    . "Context:\n{$content}\n\nInclude the keyword, a benefit, and a call-to-action.";

            case 'title_variants':
                return "Generate 5 SEO title tag variants (50-60 chars each) for content about '{$keyword}'.\n\n"
                    . "Context:\n{$content}\n\nMix power words, numbers, and Canada/GTA intent. Format as a numbered list.";

            default:
                return "Process the following content for SEO improvement {$kwLine}:\n\n{$content}";
        }
    }

    protected function emailPrompt(string $emailType, string $keyword, string $content, string $tone): string
    {
        $brief = match ($emailType) {
            'product_announcement' => 'a new-product announcement that drives clicks to the product page',
            'promotion'            => 'a promotional offer email with urgency and a clear discount CTA',
            'trade_account'        => 'an email inviting B2B buyers to open a trade account for bulk pricing',
            're_engagement'        => 'a re-engagement email to win back inactive customers',
            default                => 'a value-driven newsletter update for existing customers',
        };

        return "Write {$brief} in a {$tone} tone for a Canadian B2B supplier. "
            . ($keyword !== '' ? "Theme/keyword: '{$keyword}'. " : '')
            . "Return: a Subject line, a Preview line, and a short body with one clear CTA.\n\n"
            . ($content ? "Context:\n{$content}" : '');
    }

    protected function stripFences(string $text): string
    {
        $text = preg_replace('/```(?:html|json|markdown)?/i', '', $text);
        return trim(str_replace('```', '', $text));
    }
}
