<?php

namespace App\Services\Seo\OnPage\Features;

use App\Services\Seo\Support\AbstractSeoService;

/**
 * TruSEO on-page scoring engine.
 *
 * Modelled on the AIOSEO "TruSEO" approach: a weighted checklist split into
 * three groups — Basic SEO, Title, and Readability — each returning a pass/fail
 * with an actionable hint the UI surfaces as a suggestion. Heavy structural
 * checks (headings, links, images) use the raw HTML when callers pass it via
 * the $context array; everything else works off the plain-text content so the
 * engine still produces a sensible score when only text is available.
 *
 * Check shape:
 *   ['label' => string, 'pass' => bool, 'weight' => int, 'group' => string, 'hint' => string]
 */
class TruSeoAnalysisService extends AbstractSeoService
{
    public const GROUP_BASIC = 'basic';
    public const GROUP_TITLE = 'title';
    public const GROUP_READABILITY = 'readability';

    /** Power words that lift SERP click-through when present in a title. */
    protected array $powerWords = [
        'best', 'top', 'ultimate', 'guide', 'proven', 'essential', 'complete',
        'expert', 'professional', 'premium', 'quality', 'trusted', 'reliable',
        'affordable', 'official', 'genuine', 'wholesale', 'bulk', 'free',
        'fast', 'guaranteed', 'certified', 'authorized', 'leading',
    ];

    /** Positive-sentiment words used for the title sentiment signal. */
    protected array $positiveWords = [
        'best', 'top', 'great', 'quality', 'trusted', 'reliable', 'premium',
        'leading', 'professional', 'expert', 'affordable', 'durable', 'safe',
        'certified', 'genuine', 'authorized', 'official', 'guaranteed',
    ];

    /** Transition words that improve readability flow. */
    protected array $transitionWords = [
        'however', 'therefore', 'additionally', 'moreover', 'furthermore',
        'consequently', 'meanwhile', 'similarly', 'because', 'although',
        'since', 'thus', 'hence', 'also', 'finally', 'first', 'second',
        'next', 'then', 'for example', 'in addition', 'as a result',
    ];

    public function handle(array $payload): array
    {
        $keyword     = $payload['keyword'] ?? '';
        $content     = $payload['content'] ?? '';
        $title       = $payload['title'] ?? '';
        $url         = $payload['url'] ?? '';
        $description = $payload['description'] ?? '';

        // When the caller passes HTML content directly, feed it to the
        // structural checks too.
        $context = $payload['context'] ?? [];
        if (empty($context['raw_html']) && $content !== strip_tags($content)) {
            $context['raw_html'] = $content;
        }

        $checks = $this->runChecks($keyword, $title, $description, $content, $url, $context);
        $score  = $this->calcScore($checks);

        $prompt = "You are a TruSEO analysis expert. Given the following on-page data:\n"
            . "- Title: {$title}\n"
            . "- Focus Keyword: {$keyword}\n"
            . "- Meta Description: {$description}\n"
            . "- Content Length: " . str_word_count(strip_tags($content)) . " words\n"
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
            'score'       => $score,
            'grade'       => $this->scoreGrade($score),
            'checks'      => $checks,
            'groups'      => $this->groupSummary($checks),
            'ai_analysis' => $aiAnalysis,
            'keyword'     => $keyword,
        ];
    }

    /**
     * Run the full weighted checklist.
     *
     * @param array $context Optional structural data:
     *   - raw_html (string)            unstripped HTML for heading/link/image checks
     *   - secondary_keywords (array)   additional keywords expected in the body
     *   - image_alts (string|array)    alt text(s) to scan for the focus keyword
     *   - has_schema (bool)            JSON-LD present for this URL
     *   - has_og (bool)                Open Graph image/title present
     *   - has_canonical (bool)         canonical tag resolvable for this URL
     */
    public function runChecks($keyword, $title, $description, $content, $url, array $context = []): array
    {
        $kw        = trim(mb_strtolower((string) $keyword));
        $rawHtml   = (string) ($context['raw_html'] ?? '');
        $plain     = $this->toPlainText($rawHtml !== '' ? $rawHtml : (string) $content);
        $wordCount = $plain === '' ? 0 : str_word_count($plain);
        $secondary = $this->normalizeSecondary($context['secondary_keywords'] ?? []);

        $checks = [];

        // ── Basic SEO ──────────────────────────────────────────────────────
        $checks['title_has_keyword'] = $this->check(
            'Focus keyword in title', $kw !== '' && mb_stripos($title, $kw) !== false, 15, self::GROUP_BASIC,
            'Add the focus keyword to the meta title.'
        );

        $checks['desc_has_keyword'] = $this->check(
            'Focus keyword in meta description', $kw !== '' && mb_stripos($description, $kw) !== false, 8, self::GROUP_BASIC,
            'Work the focus keyword naturally into the meta description.'
        );

        $checks['content_length_300'] = $this->check(
            'Content length over 300 words', $wordCount >= 300, 10, self::GROUP_BASIC,
            'Aim for at least 300 words of unique content.'
        );

        $checks['content_length_600'] = $this->check(
            'In-depth content over 600 words', $wordCount >= 600, 8, self::GROUP_BASIC,
            'Long-form pages (600+ words) tend to rank higher — expand coverage.'
        );

        $density = ($kw !== '' && $wordCount > 0)
            ? (substr_count(mb_strtolower($plain), $kw) / max(1, $wordCount)) * 100
            : 0;
        $checks['keyword_density'] = $this->check(
            'Keyword density 0.5%–2.5%', $density >= 0.5 && $density <= 2.5, 10, self::GROUP_BASIC,
            sprintf('Current density %.2f%%. Target 0.5%%–2.5%%.', $density)
        );
        $checks['keyword_not_stuffed'] = $this->check(
            'No keyword stuffing (density under 3%)', $density < 3.0, 6, self::GROUP_BASIC,
            'Density above 3% reads as spam — reduce repetition.'
        );

        $first100 = implode(' ', array_slice(explode(' ', $plain), 0, 100));
        $checks['keyword_in_first_100'] = $this->check(
            'Keyword in first 100 words', $kw !== '' && mb_stripos($first100, $kw) !== false, 8, self::GROUP_BASIC,
            'Mention the keyword within the opening paragraph.'
        );

        $slug = basename(rtrim((string) $url, '/'));
        $checks['keyword_in_url'] = $this->check(
            'Keyword in URL slug', $kw !== '' && mb_stripos($slug, str_replace(' ', '-', $kw)) !== false, 8, self::GROUP_BASIC,
            'Include the keyword (hyphenated) in the URL slug.'
        );

        $checks['keyword_in_subheading'] = $this->check(
            'Keyword in an H2/H3 subheading', $kw !== '' && $this->keywordInHeadings($rawHtml, $kw), 10, self::GROUP_BASIC,
            'Use the keyword in at least one H2 or H3 subheading.'
        );

        $checks['has_h1'] = $this->check(
            'H1 heading present', $this->hasHeading($rawHtml, 1) || mb_strlen((string) $title) > 0, 8, self::GROUP_BASIC,
            'Ensure the page renders a single descriptive H1.'
        );

        $checks['internal_links'] = $this->check(
            'Has internal links', $this->countLinks($rawHtml, $url, 'internal') > 0, 7, self::GROUP_BASIC,
            'Link out to related products, categories, or guides on this site.'
        );

        $checks['external_links'] = $this->check(
            'Has an external authority link', $this->countLinks($rawHtml, $url, 'external') > 0, 5, self::GROUP_BASIC,
            'Cite at least one authoritative external source.'
        );

        $checks['image_alt_keyword'] = $this->check(
            'Keyword in an image alt text', $kw !== '' && $this->keywordInAlts($rawHtml, $context['image_alts'] ?? null, $kw), 8, self::GROUP_BASIC,
            'Add descriptive alt text containing the keyword to an image.'
        );

        $checks['secondary_keywords_used'] = $this->check(
            'Secondary keywords used in content',
            !empty($secondary) && $this->anyKeywordPresent($plain, $secondary),
            6, self::GROUP_BASIC,
            'Reference your secondary/LSI keywords in the body copy.'
        );

        $checks['has_schema'] = $this->check(
            'Structured data (schema) present', !empty($context['has_schema']), 12, self::GROUP_BASIC,
            'Add JSON-LD schema (Product, Article, FAQ, etc.) for rich results.'
        );

        $checks['has_open_graph'] = $this->check(
            'Open Graph / social tags set', !empty($context['has_og']), 6, self::GROUP_BASIC,
            'Set an OG image and title so shares render a rich card.'
        );

        $checks['has_canonical'] = $this->check(
            'Canonical tag set', !empty($context['has_canonical']), 7, self::GROUP_BASIC,
            'Declare a canonical URL to consolidate duplicate signals.'
        );

        // ── Title ──────────────────────────────────────────────────────────
        $titleLen = mb_strlen((string) $title);
        $checks['title_length'] = $this->check(
            'Title length 30–60 characters', $titleLen >= 30 && $titleLen <= 60, 10, self::GROUP_TITLE,
            sprintf('Title is %d chars. Keep it 30–60 so it is not truncated.', $titleLen)
        );

        $titleStart = mb_strtolower(implode(' ', array_slice(explode(' ', (string) $title), 0, 4)));
        $checks['keyword_at_title_start'] = $this->check(
            'Keyword near the start of the title', $kw !== '' && mb_stripos($titleStart, $kw) !== false, 8, self::GROUP_TITLE,
            'Move the focus keyword into the first few words of the title.'
        );

        $checks['title_power_word'] = $this->check(
            'Title uses a power word', $this->containsAny($title, $this->powerWords), 5, self::GROUP_TITLE,
            'Add a power word (e.g. Best, Trusted, Wholesale) to boost CTR.'
        );

        $checks['title_has_number'] = $this->check(
            'Title contains a number', (bool) preg_match('/\d/', (string) $title), 4, self::GROUP_TITLE,
            'Numbers (years, counts, prices) raise click-through.'
        );

        $checks['title_sentiment'] = $this->check(
            'Title has positive sentiment', $this->containsAny($title, $this->positiveWords), 4, self::GROUP_TITLE,
            'Lead with a positive, benefit-driven word.'
        );

        // ── Readability ────────────────────────────────────────────────────
        $descLen = mb_strlen((string) $description);
        $checks['desc_length'] = $this->check(
            'Meta description length 120–160 characters', $descLen >= 120 && $descLen <= 160, 7, self::GROUP_READABILITY,
            sprintf('Description is %d chars. Target 120–160.', $descLen)
        );

        $avgSentence = $this->averageSentenceLength($plain);
        $checks['sentence_length'] = $this->check(
            'Average sentence under 20 words', $avgSentence > 0 && $avgSentence < 20, 6, self::GROUP_READABILITY,
            sprintf('Average sentence is %.0f words. Shorten to under 20.', $avgSentence)
        );

        $checks['has_subheadings'] = $this->check(
            'Content broken up with subheadings', $this->hasHeading($rawHtml, 2) || $this->hasHeading($rawHtml, 3), 5, self::GROUP_READABILITY,
            'Add H2/H3 subheadings roughly every 300 words.'
        );

        $passiveRatio = $this->passiveVoiceRatio($plain);
        $checks['passive_voice'] = $this->check(
            'Low passive voice (under 15%)', $passiveRatio < 0.15, 5, self::GROUP_READABILITY,
            'Rewrite passive sentences in an active voice.'
        );

        $checks['transition_words'] = $this->check(
            'Uses transition words', $this->containsAny($plain, $this->transitionWords), 5, self::GROUP_READABILITY,
            'Add transition words (however, because, additionally) for flow.'
        );

        return $checks;
    }

    /** Per-group earned/total/score breakdown for the UI tabs. */
    public function groupSummary(array $checks): array
    {
        $groups = [];
        foreach ($checks as $c) {
            $g = $c['group'] ?? self::GROUP_BASIC;
            $groups[$g] ??= ['earned' => 0, 'total' => 0, 'passed' => 0, 'count' => 0];
            $groups[$g]['total']  += $c['weight'];
            $groups[$g]['count']  += 1;
            if ($c['pass']) {
                $groups[$g]['earned'] += $c['weight'];
                $groups[$g]['passed'] += 1;
            }
        }
        foreach ($groups as $g => &$data) {
            $data['score'] = $data['total'] > 0 ? (int) round(($data['earned'] / $data['total']) * 100) : 0;
        }
        return $groups;
    }

    protected function calcScore(array $checks): int
    {
        $totalWeight  = array_sum(array_column($checks, 'weight'));
        $earnedWeight = array_sum(array_map(fn($c) => $c['pass'] ? $c['weight'] : 0, $checks));
        return $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;
    }

    protected function scoreGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default      => 'F',
        };
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    protected function check(string $label, bool $pass, int $weight, string $group, string $hint): array
    {
        return [
            'label'  => $label,
            'pass'   => $pass,
            'weight' => $weight,
            'group'  => $group,
            'hint'   => $pass ? '' : $hint,
        ];
    }

    protected function toPlainText(string $value): string
    {
        $text = strip_tags($value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    protected function normalizeSecondary($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value);
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter(array_map(fn($v) => trim(mb_strtolower((string) $v)), $value)));
    }

    protected function anyKeywordPresent(string $haystack, array $keywords): bool
    {
        $haystack = mb_strtolower($haystack);
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($haystack, $kw) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        $haystack = mb_strtolower($haystack);
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, mb_strtolower($needle)) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function hasHeading(string $html, int $level): bool
    {
        return $html !== '' && (bool) preg_match('/<h' . $level . '[\s>]/i', $html);
    }

    protected function keywordInHeadings(string $html, string $kw): bool
    {
        if ($html === '' || $kw === '') {
            return false;
        }
        if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $html, $m)) {
            foreach ($m[1] as $headingHtml) {
                if (mb_stripos($this->toPlainText($headingHtml), $kw) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function countLinks(string $html, string $url, string $which): int
    {
        if ($html === '') {
            return 0;
        }
        if (!preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $m)) {
            return 0;
        }

        $host = parse_url($url ?: url('/'), PHP_URL_HOST) ?: parse_url(url('/'), PHP_URL_HOST);
        $count = 0;
        foreach ($m[1] as $href) {
            $href = trim($href);
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $isAbsolute = (bool) preg_match('#^https?://#i', $href);
            $linkHost   = $isAbsolute ? parse_url($href, PHP_URL_HOST) : $host;
            $isInternal = !$isAbsolute || ($host && $linkHost && stripos((string) $linkHost, (string) $host) !== false);

            if ($which === 'internal' && $isInternal) {
                $count++;
            } elseif ($which === 'external' && !$isInternal) {
                $count++;
            }
        }
        return $count;
    }

    protected function keywordInAlts(string $html, $altsContext, string $kw): bool
    {
        if ($kw === '') {
            return false;
        }
        $alts = [];
        if (is_array($altsContext)) {
            $alts = $altsContext;
        } elseif (is_string($altsContext) && $altsContext !== '') {
            $alts[] = $altsContext;
        }
        if ($html !== '' && preg_match_all('/<img\s[^>]*alt=["\']([^"\']*)["\']/i', $html, $m)) {
            $alts = array_merge($alts, $m[1]);
        }
        foreach ($alts as $alt) {
            if (mb_stripos((string) $alt, $kw) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function averageSentenceLength(string $text): float
    {
        $sentences = array_filter(array_map('trim', preg_split('/[.!?]+/', $text)));
        if (empty($sentences)) {
            return 0.0;
        }
        $words = 0;
        foreach ($sentences as $s) {
            $words += str_word_count($s);
        }
        return $words / count($sentences);
    }

    protected function passiveVoiceRatio(string $text): float
    {
        $sentences = array_filter(array_map('trim', preg_split('/[.!?]+/', $text)));
        if (empty($sentences)) {
            return 0.0;
        }
        $passive = 0;
        foreach ($sentences as $s) {
            if (preg_match('/\b(is|are|was|were|be|been|being)\b\s+(\w+ed|\w+en)\b/i', $s)) {
                $passive++;
            }
        }
        return $passive / count($sentences);
    }
}
