<?php

namespace App\Services\Seo\Advanced;

use App\Services\Seo\Providers\ResilientProviderHttp;
use App\Services\Seo\Providers\SeoProviderManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Autonomous multi-page SEO research agent (ReAct-style loop).
 *
 * Rather than a single prompt, this runs a real reason/act cycle: each turn
 * the model sees what it has gathered so far and decides which page to read
 * next, or that it has enough to write the report. That lets it follow its own
 * line of enquiry across several competitor pages instead of summarising one.
 *
 * SECURITY BOUNDARY — this is the important part:
 * The agent may ONLY fetch URLs from the operator-supplied seed list. It
 * cannot follow links it discovers in fetched pages. An agent that fetches
 * model-chosen arbitrary URLs is a server-side request forgery primitive: the
 * model could be steered by injected text in a fetched page into requesting
 * internal addresses (169.254.169.254, 127.0.0.1, intranet hosts) and echoing
 * the response back. Restricting fetches to a fixed, admin-provided allowlist
 * removes that class of attack entirely, and the seed URLs are additionally
 * validated to be public HTTP(S) addresses before any request is made.
 */
class SeoResearchAgentService
{
    use ResilientProviderHttp;

    public const MAX_TURNS = 10;
    public const MAX_FETCHES = 8;
    protected const PAGE_CHAR_LIMIT = 6000;

    /**
     * @param string   $question  The research question
     * @param string[] $seedUrls  The ONLY URLs the agent is permitted to read
     */
    public function research(string $question, array $seedUrls, ?string $provider = null): array
    {
        [$allowed, $rejected] = $this->partitionSeeds($seedUrls);

        if (empty($allowed)) {
            return ['error' => 'No valid public http(s) URLs supplied. The agent can only read pages you explicitly list.'];
        }

        $providerName = $provider ?: get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));
        $driver = app(SeoProviderManager::class)->makeDirect($providerName);

        if (!$driver->isConfigured()) {
            return ['error' => 'AI provider "' . $providerName . '" is not configured. Add its API key in SEO Settings.'];
        }

        $gathered = [];   // url => extracted text
        $trace = [];      // the visible reasoning/action log
        $unread = $allowed;
        $report = null;

        for ($turn = 1; $turn <= self::MAX_TURNS; $turn++) {
            if (count($gathered) >= self::MAX_FETCHES) {
                $trace[] = ['turn' => $turn, 'type' => 'limit', 'detail' => 'Fetch budget of ' . self::MAX_FETCHES . ' pages reached — writing the report from what was gathered.'];
                break;
            }

            $decision = $this->decide($driver, $question, $gathered, $unread, $turn);

            if (isset($decision['error'])) {
                $trace[] = ['turn' => $turn, 'type' => 'error', 'detail' => $decision['error']];
                break;
            }

            $trace[] = [
                'turn'      => $turn,
                'type'      => 'thought',
                'detail'    => $decision['reasoning'] ?? '(no reasoning given)',
                'action'    => $decision['action'] ?? 'unknown',
            ];

            if (($decision['action'] ?? '') === 'report') {
                $report = $this->writeReport($driver, $question, $gathered);
                break;
            }

            $target = $decision['url'] ?? null;

            // Enforce the allowlist regardless of what the model asked for.
            if (!$target || !in_array($target, $allowed, true)) {
                $target = $unread[0] ?? null;
                if ($target === null) {
                    $report = $this->writeReport($driver, $question, $gathered);
                    break;
                }
                $trace[] = ['turn' => $turn, 'type' => 'guard', 'detail' => 'Requested URL was not on the allowlist; falling back to the next unread seed.'];
            }

            $fetch = $this->fetchPage($target);
            $unread = array_values(array_diff($unread, [$target]));

            if ($fetch['ok']) {
                $gathered[$target] = $fetch['text'];
                $trace[] = ['turn' => $turn, 'type' => 'observation', 'url' => $target, 'detail' => 'Read ' . str_word_count($fetch['text']) . ' words.'];
            } else {
                // Report the failure honestly instead of inventing content.
                $trace[] = ['turn' => $turn, 'type' => 'observation', 'url' => $target, 'detail' => 'Could not read: ' . $fetch['error']];
            }

            if (empty($unread) && !empty($gathered)) {
                $report = $this->writeReport($driver, $question, $gathered);
                $trace[] = ['turn' => $turn + 1, 'type' => 'thought', 'detail' => 'All permitted sources have been read.', 'action' => 'report'];
                break;
            }
        }

        if ($report === null) {
            $report = empty($gathered)
                ? 'No sources could be read, so no grounded report can be produced.'
                : $this->writeReport($driver, $question, $gathered);
        }

        return [
            'question'       => $question,
            'provider'       => $providerName,
            'report'         => $report,
            'trace'          => $trace,
            'sources_read'   => array_keys($gathered),
            'sources_failed' => array_values(array_diff($allowed, array_keys($gathered))),
            'rejected_seeds' => $rejected,
        ];
    }

    /** One ReAct turn: the model picks the next action given what it has. */
    protected function decide($driver, string $question, array $gathered, array $unread, int $turn): array
    {
        $readList = empty($gathered)
            ? '(nothing yet)'
            : implode("\n", array_map(fn($u) => '- ' . $u . ' — ' . Str::limit(str_replace("\n", ' ', $gathered[$u]), 200), array_keys($gathered)));

        $unreadList = empty($unread) ? '(none left)' : implode("\n", array_map(fn($u) => '- ' . $u, $unread));

        $prompt = <<<PROMPT
You are an SEO research agent working through a question step by step.

RESEARCH QUESTION:
{$question}

PAGES YOU HAVE ALREADY READ (with a snippet of each):
{$readList}

PAGES YOU MAY STILL READ (you may ONLY choose from this list):
{$unreadList}

This is turn {$turn}. Decide the single next step.
- If you still need evidence and there are unread pages, choose "fetch" and name exactly one URL from the permitted list.
- If you have enough to answer the question well, choose "report".

Respond with ONLY a JSON object, no other text:
{"reasoning": "one sentence on why this step", "action": "fetch" or "report", "url": "the URL if fetching, otherwise null"}
PROMPT;

        try {
            $raw = $driver->generate($prompt, 'You are a precise research agent. You reply with JSON only.');
            if (!$raw) {
                return ['error' => 'The AI provider returned no response.'];
            }

            preg_match('/\{.*\}/s', $raw, $matches);
            $decoded = isset($matches[0]) ? json_decode($matches[0], true) : null;

            if (!is_array($decoded) || !isset($decoded['action'])) {
                // Malformed plan — degrade to reading the next unread source
                // rather than aborting the whole run.
                return ['reasoning' => 'Model response could not be parsed; continuing with the next source.', 'action' => 'fetch', 'url' => $unread[0] ?? null];
            }

            return $decoded;
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /** Final synthesis, explicitly grounded in the fetched text only. */
    protected function writeReport($driver, string $question, array $gathered): string
    {
        if (empty($gathered)) {
            return 'No sources could be read, so no grounded report can be produced.';
        }

        $sources = '';
        foreach ($gathered as $url => $text) {
            $sources .= "\n\n=== SOURCE: {$url} ===\n" . Str::limit($text, self::PAGE_CHAR_LIMIT);
        }

        $prompt = <<<PROMPT
Write an SEO research report answering this question:
{$question}

Use ONLY the source material below. Cite the source URL inline for each claim.
If the sources do not answer part of the question, say so explicitly rather
than filling the gap with general knowledge.

Structure the report as:
1. Direct answer (2-3 sentences)
2. Key findings, each with its source URL
3. Recommended actions
4. What the sources did not cover

SOURCE MATERIAL:{$sources}
PROMPT;

        try {
            return trim((string) $driver->generate($prompt, 'You are a senior SEO strategist. Ground every claim in the supplied sources.'))
                ?: 'The AI provider returned an empty report.';
        } catch (Throwable $e) {
            return 'Report generation failed: ' . $e->getMessage();
        }
    }

    protected function fetchPage(string $url): array
    {
        try {
            $response = $this->providerHttp()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; SeoSuite-Research/1.0)'])
                ->get($url);

            if (!$response->successful()) {
                return ['ok' => false, 'error' => 'HTTP ' . $response->status() . ($response->status() === 402 ? ' (paywall)' : '')];
            }

            $html = $response->body();
            $html = preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1>#is', ' ', $html);
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

            if (str_word_count($text) < 30) {
                return ['ok' => false, 'error' => 'page returned almost no readable text (likely JS-rendered or blocked)'];
            }

            return ['ok' => true, 'text' => $text];
        } catch (Throwable $e) {
            Log::warning('[SEO][Agent] fetch failed', ['url' => $url, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate seeds up front: public http(s) only. Blocks localhost, private
     * ranges, and link-local metadata addresses so a seed list can never be
     * used to probe internal infrastructure.
     *
     * @return array{0: string[], 1: array<int, array{url: string, reason: string}>}
     */
    protected function partitionSeeds(array $urls): array
    {
        $allowed = [];
        $rejected = [];

        foreach ($urls as $raw) {
            $url = trim((string) $raw);
            if ($url === '') {
                continue;
            }

            $parts = parse_url($url);
            $scheme = strtolower($parts['scheme'] ?? '');
            $host = $parts['host'] ?? '';

            if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
                $rejected[] = ['url' => $url, 'reason' => 'not a valid http(s) URL'];
                continue;
            }

            if ($this->isPrivateHost($host)) {
                $rejected[] = ['url' => $url, 'reason' => 'private, local, or metadata address — not permitted'];
                continue;
            }

            if (!in_array($url, $allowed, true)) {
                $allowed[] = $url;
            }
        }

        return [$allowed, $rejected];
    }

    protected function isPrivateHost(string $host): bool
    {
        $host = strtolower(rtrim($host, '.'));

        if (in_array($host, ['localhost', 'metadata.google.internal'], true)) {
            return true;
        }
        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.internal') || str_ends_with($host, '.local')) {
            return true;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            // Reject anything not in public routable space.
            return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // Unresolvable host — refuse rather than let the HTTP client decide.
        return true;
    }
}
