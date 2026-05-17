<?php

namespace App\Utility;

use Illuminate\Support\Facades\Http;

class AiChatUtility
{
    public static function getProvider(): string
    {
        return get_setting('ai_chat_provider') ?? 'claude';
    }

    public static function getSystemPrompt(string $pageContext = '', string $productContext = ''): string
    {
        $custom   = get_setting('ai_chat_system_prompt');
        $siteName = get_setting('site_name') ?? 'our store';

        $base = $custom ?: "You are a fast, direct customer support assistant for {$siteName} — a wholesale HVAC, plumbing, and hardware supplies store. "
            . "RULES: (1) Give the answer or solution FIRST — do NOT start with clarifying questions. "
            . "(2) If the customer's intent is clear, solve it immediately. "
            . "(3) Ask at most ONE follow-up question per reply, only if absolutely required. "
            . "(4) Keep replies short and actionable — 2 to 4 sentences max. "
            . "(5) For product questions, give specs, alternatives, or pricing guidance directly. "
            . "(6) For orders or shipping, guide them to their account or give the support contact. "
            . "(7) LINKS: Only link products/categories that appear in the REAL DATABASE LIST provided below. "
            . "Copy the markdown link exactly as given — never invent or guess URLs. "
            . "Support contact: +1 (647) 456-2244. Be professional, warm, and solution-focused.";

        if ($pageContext) {
            $base .= "\n\nCURRENT PAGE: The customer is viewing: {$pageContext}.";
        }

        if ($productContext) {
            $base .= "\n\n" . $productContext;
            $base .= "\n\nIMPORTANT: Only use the URLs listed above. Do NOT invent product or category URLs.";
        }

        return $base;
    }

    public static function chat(array $history, string $userMessage, string $pageContext = '', string $productContext = ''): string
    {
        $provider = self::getProvider();
        if ($provider === 'gemini')  return self::geminiChat($history, $userMessage, $pageContext, $productContext);
        if ($provider === 'openai')  return self::openaiChat($history, $userMessage, $pageContext, $productContext);
        if ($provider === 'groq')    return self::groqChat($history, $userMessage, $pageContext, $productContext);
        return self::claudeChat($history, $userMessage, $pageContext, $productContext);
    }

    // ─── Anthropic Claude ─────────────────────────────────────────────────────

    private static function claudeChat(array $history, string $userMessage, string $pageContext = '', string $productContext = ''): string
    {
        $apiKey = get_setting('anthropic_api_key');
        if (!$apiKey) {
            return 'AI support is not configured yet. Please contact us directly.';
        }

        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['sender_type'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['message'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => get_setting('claude_model') ?? 'claude-haiku-4-5-20251001',
                'system'     => self::getSystemPrompt($pageContext, $productContext),
                'messages'   => $messages,
                'max_tokens' => 600,
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                return 'AI error: ' . ($data['error']['message'] ?? 'Unknown error.');
            }

            return trim($data['content'][0]['text'] ?? 'Sorry, I could not generate a response.');
        } catch (\Exception $e) {
            return 'I am unable to respond right now. Please try again in a moment.';
        }
    }

    // ─── OpenAI ───────────────────────────────────────────────────────────────

    private static function openaiChat(array $history, string $userMessage, string $pageContext = '', string $productContext = ''): string
    {
        $apiKey = get_setting('openai_api_key');
        if (!$apiKey) {
            return 'AI support is not configured yet. Please contact us directly.';
        }

        $messages = [['role' => 'system', 'content' => self::getSystemPrompt($pageContext, $productContext)]];

        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['sender_type'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['message'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => get_setting('openai_model') ?? 'gpt-4o-mini',
                'messages'    => $messages,
                'max_tokens'  => 600,
                'temperature' => 0.7,
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                return 'AI error: ' . ($data['error']['message'] ?? 'Unknown error.');
            }

            return trim($data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.');
        } catch (\Exception $e) {
            return 'I am unable to respond right now. Please try again in a moment.';
        }
    }

    // ─── Groq (Free) ─────────────────────────────────────────────────────────

    private static function groqChat(array $history, string $userMessage, string $pageContext = '', string $productContext = ''): string
    {
        $apiKey = get_setting('groq_api_key');
        if (!$apiKey) {
            return 'AI support is not configured yet. Please contact us directly.';
        }

        $messages = [['role' => 'system', 'content' => self::getSystemPrompt($pageContext, $productContext)]];
        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['sender_type'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['message'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => get_setting('groq_model') ?? 'llama-3.1-8b-instant',
                'messages'    => $messages,
                'max_tokens'  => 600,
                'temperature' => 0.7,
            ]);

            $data = $response->json();
            if (isset($data['error'])) {
                return 'AI error: ' . ($data['error']['message'] ?? 'Unknown error.');
            }
            return trim($data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.');
        } catch (\Exception $e) {
            return 'I am unable to respond right now. Please try again in a moment.';
        }
    }

    // ─── Google Gemini ────────────────────────────────────────────────────────

    private static function geminiChat(array $history, string $userMessage, string $pageContext = '', string $productContext = ''): string
    {
        $apiKey = get_setting('gemini_api_key');
        if (!$apiKey) {
            return 'AI support is not configured yet. Please contact us directly.';
        }

        $contents = [
            ['role' => 'user',  'parts' => [['text' => self::getSystemPrompt($pageContext, $productContext)]]],
            ['role' => 'model', 'parts' => [['text' => 'Understood! I am ready to assist customers.']]],
        ];

        foreach ($history as $msg) {
            $role       = $msg['sender_type'] === 'user' ? 'user' : 'model';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['message']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $model = get_setting('gemini_model') ?? 'gemini-pro';
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(30)->post($url, [
                'contents'         => $contents,
                'generationConfig' => [
                    'maxOutputTokens' => 600,
                    'temperature'     => 0.7,
                ],
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                return 'AI error: ' . ($data['error']['message'] ?? 'Unknown error.');
            }

            return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response.');
        } catch (\Exception $e) {
            return 'I am unable to respond right now. Please try again in a moment.';
        }
    }
}
