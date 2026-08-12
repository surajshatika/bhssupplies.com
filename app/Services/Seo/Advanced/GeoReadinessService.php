<?php

namespace App\Services\Seo\Advanced;

use App\Services\Seo\Providers\ResilientProviderHttp;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GEO (Generative Engine Optimization) readiness checker.
 *
 * Scores a page on how citable it is by AI answer engines — ChatGPT Search,
 * Perplexity, Google AI Overviews, Claude with search. These systems extract
 * and attribute discrete factual passages rather than ranking ten blue links,
 * so the signals that matter are different from classic on-page SEO.
 *
 * Deliberately uses ZERO AI calls: every factor below is measured directly
 * from the fetched HTML so the score is deterministic, free, instant, and
 * reproducible. Two runs against unchanged HTML always produce the same score.
 *
 * All 8 factors are disclosed to the admin in the UI along with the exact
 * evidence that produced each score — no black-box numbers.
 */
class GeoReadinessService
{
    use ResilientProviderHttp;

    /**
     * Factor weights (sum = 100). Ordered by how strongly each signal
     * correlates with being quoted by an answer engine.
     */
    public const FACTORS = [
        'answer_first'    => ['weight' => 18, 'label' => 'Answer-First Content'],
        'extractable'     => ['weight' => 16, 'label' => 'Extractable Facts'],
        'structured_data' => ['weight' => 15, 'label' => 'Structured Data (JSON-LD)'],
        'heading_clarity' => ['weight' => 13, 'label' => 'Heading Structure'],
        'qa_blocks'       => ['weight' => 12, 'label' => 'Question-Shaped Sections'],
        'attribution'     => ['weight' => 10, 'label' => 'Author & Entity Attribution'],
        'freshness'       => ['weight' => 8,  'label' => 'Freshness Signals'],
        'ai_crawlability' => ['weight' => 8,  'label' => 'AI Crawler Access'],
    ];

    /**
     * Fetch a URL and score it. Returns a full report array, or an array with
     * an 'error' key when the page could not be retrieved.
     */
    public function analyze(string $url): array
    {
        $html = $this->fetch($url);

        if ($html === null) {
            return [
                'url'   => $url,
                'error' => 'Could not fetch the page. Check that the URL is publicly reachable and returns HTML.',
            ];
        }

        return $this->analyzeHtml($html, $url);
    }

    /**
     * Score already-fetched HTML. Split out from analyze() so it is directly
     * unit-testable without any network access.
     */
    public function analyzeHtml(string $html, string $url = ''): array
    {
        $dom = $this->parse($html);
        $xpath = new DOMXPath($dom);
        $text = $this->visibleText($dom);

        $factors = [
            'answer_first'    => $this->scoreAnswerFirst($xpath, $text),
            'extractable'     => $this->scoreExtractable($xpath, $text),
            'structured_data' => $this->scoreStructuredData($xpath),
            'heading_clarity' => $this->scoreHeadingClarity($xpath),
            'qa_blocks'       => $this->scoreQaBlocks($xpath),
            'attribution'     => $this->scoreAttribution($xpath, $html),
            'freshness'       => $this->scoreFreshness($xpath, $html),
            'ai_crawlability' => $this->scoreAiCrawlability($xpath, $text, $html),
        ];

        $total = 0;
        foreach ($factors as $key => $factor) {
            // Each factor returns a 0..1 ratio; weight converts it to points.
            $total += $factor['ratio'] * self::FACTORS[$key]['weight'];
        }
        $score = (int) round($total);

        return [
            'url'        => $url,
            'score'      => $score,
            'grade'      => $this->grade($score),
            'factors'    => $factors,
            'word_count' => str_word_count($text),
            'priorities' => $this->priorities($factors),
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    // Factor scorers. Each returns:
    //   ratio  — 0.0..1.0 achievement of this factor
    //   detail — human-readable evidence shown in the UI
    //   fix    — the concrete next action when the ratio is below 1
    // ────────────────────────────────────────────────────────────────────

    /**
     * Answer engines quote passages that answer the question immediately.
     * A page that opens with 400 words of preamble rarely gets cited.
     */
    protected function scoreAnswerFirst(DOMXPath $xpath, string $text): array
    {
        $h1 = $this->firstText($xpath, '//h1');
        if ($h1 === '') {
            return $this->factor(0, 'No H1 found, so there is no stated question or topic to answer.', 'Add a single descriptive H1 that states the page topic as a question or clear claim.');
        }

        // Measure how much prose sits between the H1 and the first substantive
        // paragraph — the "time to answer" for an extraction model.
        $firstPara = '';
        foreach ($xpath->query('//h1/following::p') as $node) {
            $candidate = trim($node->textContent);
            if (str_word_count($candidate) >= 15) {
                $firstPara = $candidate;
                break;
            }
        }

        if ($firstPara === '') {
            return $this->factor(0.15, 'No substantive paragraph (15+ words) follows the H1.', 'Place a 40-60 word direct answer immediately below the H1.');
        }

        $words = str_word_count($firstPara);
        // 40-80 words is the sweet spot for a quotable standalone answer.
        if ($words >= 40 && $words <= 80) {
            $ratio = 1.0;
            $detail = "Opening paragraph is {$words} words — an ideal quotable length.";
            $fix = '';
        } elseif ($words >= 25 && $words <= 120) {
            $ratio = 0.7;
            $detail = "Opening paragraph is {$words} words — usable but not optimal.";
            $fix = 'Tighten the opening answer to roughly 40-60 words so it can be quoted whole.';
        } else {
            $ratio = 0.35;
            $detail = "Opening paragraph is {$words} words — too " . ($words < 25 ? 'thin' : 'long') . ' to quote cleanly.';
            $fix = 'Lead with a self-contained 40-60 word answer before expanding into detail.';
        }

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * Discrete facts — numbers, lists, tables — are what actually gets lifted
     * into an AI answer. Flowing prose with no hard data rarely survives.
     */
    protected function scoreExtractable(DOMXPath $xpath, string $text): array
    {
        $lists  = $xpath->query('//ul|//ol')->length;
        $tables = $xpath->query('//table')->length;
        // Count statements carrying a concrete figure (number, %, currency).
        preg_match_all('/\b\d[\d,.]*\s*(%|percent|kg|lbs?|mm|cm|hours?|days?|years?|\$|USD|CAD)\b/i', $text, $m);
        $stats = count($m[0]);

        $signals = ($lists > 0 ? 1 : 0) + ($tables > 0 ? 1 : 0) + ($stats >= 3 ? 1 : 0);
        $ratio = min(1.0, ($lists >= 2 ? 0.4 : $lists * 0.2) + ($tables > 0 ? 0.3 : 0) + min(0.3, $stats * 0.06));

        $detail = "{$lists} list(s), {$tables} table(s), {$stats} statistic(s) with units detected.";
        $fix = $ratio >= 1.0 ? '' : 'Add a comparison table, a bulleted spec list, or concrete figures with units — these are what answer engines lift verbatim.';

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * JSON-LD is how you tell an answer engine what the page *is* rather than
     * making it infer from prose. Missing schema is the single most common
     * reason a factually strong page never gets cited.
     */
    protected function scoreStructuredData(DOMXPath $xpath): array
    {
        $types = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $decoded = json_decode(trim($node->textContent), true);
            if (!is_array($decoded)) {
                continue;
            }
            foreach ($this->collectSchemaTypes($decoded) as $type) {
                $types[$type] = true;
            }
        }
        $types = array_keys($types);

        if (empty($types)) {
            return $this->factor(0, 'No JSON-LD structured data found on the page.', 'Add JSON-LD describing the page (Article, Product, FAQPage, or LocalBusiness as appropriate).');
        }

        // Types that specifically help answer engines extract and attribute.
        $highValue = ['FAQPage', 'HowTo', 'QAPage', 'Article', 'NewsArticle', 'BlogPosting', 'Product', 'LocalBusiness', 'Organization', 'BreadcrumbList'];
        $matched = array_values(array_intersect($types, $highValue));

        $ratio = min(1.0, 0.45 + (count($matched) * 0.2));
        $detail = 'Schema found: ' . implode(', ', array_slice($types, 0, 6)) . '.';
        $fix = count($matched) >= 2 ? '' : 'Add a high-value schema type (FAQPage, HowTo, or Article) — these map directly onto how answer engines structure citations.';

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * Extraction models use headings as passage boundaries. Duplicate H1s or
     * skipped levels make the document structurally ambiguous to chunk.
     */
    protected function scoreHeadingClarity(DOMXPath $xpath): array
    {
        $h1 = $xpath->query('//h1')->length;
        $h2 = $xpath->query('//h2')->length;
        $h3 = $xpath->query('//h3')->length;

        $problems = [];
        $ratio = 1.0;

        if ($h1 === 0) {
            $problems[] = 'no H1';
            $ratio -= 0.5;
        } elseif ($h1 > 1) {
            $problems[] = "{$h1} H1 tags (should be exactly 1)";
            $ratio -= 0.4;
        }

        if ($h2 === 0) {
            $problems[] = 'no H2 sections to chunk the page into passages';
            $ratio -= 0.35;
        } elseif ($h2 < 3) {
            $problems[] = "only {$h2} H2 section(s)";
            $ratio -= 0.15;
        }

        if ($h3 > 0 && $h2 === 0) {
            $problems[] = 'H3 used without any H2 (skipped level)';
            $ratio -= 0.15;
        }

        $ratio = max(0.0, min(1.0, $ratio));
        $detail = "H1: {$h1}, H2: {$h2}, H3: {$h3}." . ($problems ? ' Issues: ' . implode('; ', $problems) . '.' : ' Structure is clean.');
        $fix = $problems ? 'Use exactly one H1 and at least three descriptive H2 sections so each passage stands alone.' : '';

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * Headings phrased as real user questions match the retrieval query far
     * more directly than noun-phrase headings.
     */
    protected function scoreQaBlocks(DOMXPath $xpath): array
    {
        $questionWords = ['what', 'why', 'how', 'when', 'where', 'which', 'who', 'can', 'do', 'does', 'is', 'are', 'should'];
        $total = 0;
        $questions = 0;

        foreach ($xpath->query('//h2|//h3|//h4') as $node) {
            $heading = trim($node->textContent);
            if ($heading === '') {
                continue;
            }
            $total++;
            $firstWord = strtolower(strtok($heading, " \t\n"));
            if (str_contains($heading, '?') || in_array($firstWord, $questionWords, true)) {
                $questions++;
            }
        }

        if ($total === 0) {
            return $this->factor(0, 'No subheadings found to evaluate.', 'Add H2/H3 sections, phrasing several of them as the questions users actually ask.');
        }

        // Roughly a third of headings being question-shaped is a healthy target.
        $share = $questions / $total;
        $ratio = min(1.0, $share / 0.34);
        $detail = "{$questions} of {$total} subheadings are question-shaped (" . round($share * 100) . '%).';
        $fix = $ratio >= 1.0 ? '' : 'Rewrite some section headings as natural questions ("How long does X last?") to match retrieval queries.';

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * Answer engines attribute to identifiable entities. Anonymous pages are
     * systematically down-weighted for citation.
     */
    protected function scoreAttribution(DOMXPath $xpath, string $html): array
    {
        $signals = [];

        if ($xpath->query('//*[@rel="author"]|//*[contains(@class,"author")]|//meta[@name="author"]')->length > 0) {
            $signals[] = 'author markup';
        }
        if (preg_match('/"@type"\s*:\s*"(Person|Organization)"/i', $html)) {
            $signals[] = 'Person/Organization schema';
        }
        if ($xpath->query('//meta[@property="og:site_name"]')->length > 0) {
            $signals[] = 'og:site_name';
        }
        if (preg_match('/\b(about us|our team|contact us)\b/i', $html)) {
            $signals[] = 'about/contact reference';
        }

        $ratio = min(1.0, count($signals) * 0.34);
        $detail = $signals ? 'Attribution signals: ' . implode(', ', $signals) . '.' : 'No author or publisher attribution detected.';
        $fix = $ratio >= 1.0 ? '' : 'Add a named author with Person schema and a clear publisher Organization block.';

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * Answer engines strongly prefer content they can date, and will often
     * state the date alongside a citation.
     */
    protected function scoreFreshness(DOMXPath $xpath, string $html): array
    {
        $signals = [];

        if ($xpath->query('//time[@datetime]')->length > 0) {
            $signals[] = '<time datetime>';
        }
        if (preg_match('/"(datePublished|dateModified)"\s*:/i', $html)) {
            $signals[] = 'schema date fields';
        }
        if ($xpath->query('//meta[@property="article:modified_time"]|//meta[@property="article:published_time"]')->length > 0) {
            $signals[] = 'article meta dates';
        }

        // A visible year in the current or previous year reads as current.
        $year = (int) date('Y');
        if (preg_match('/\b(' . $year . '|' . ($year - 1) . ')\b/', strip_tags($html))) {
            $signals[] = 'recent year mentioned in copy';
        }

        $ratio = min(1.0, count($signals) * 0.34);
        $detail = $signals ? 'Freshness signals: ' . implode(', ', $signals) . '.' : 'No machine-readable publish or update date found.';
        $fix = $ratio >= 1.0 ? '' : 'Expose datePublished/dateModified in JSON-LD and render a visible "Last updated" date.';

        return $this->factor($ratio, $detail, $fix);
    }

    /**
     * A page can be perfect and still be invisible if the AI crawlers are
     * blocked or the content only exists after JavaScript execution.
     */
    protected function scoreAiCrawlability(DOMXPath $xpath, string $text, string $html): array
    {
        $problems = [];
        $ratio = 1.0;

        foreach ($xpath->query('//meta[@name="robots"]|//meta[@name="googlebot"]') as $node) {
            $content = strtolower($node->getAttribute('content'));
            if (str_contains($content, 'noindex')) {
                $problems[] = 'page is set to noindex';
                $ratio -= 0.6;
            }
            if (str_contains($content, 'nosnippet')) {
                $problems[] = 'nosnippet blocks answer-engine excerpting';
                $ratio -= 0.3;
            }
            if (str_contains($content, 'max-snippet:0')) {
                $problems[] = 'max-snippet:0 blocks excerpting';
                $ratio -= 0.3;
            }
        }

        // Server-rendered text is what a non-JS-executing crawler actually sees.
        $words = str_word_count($text);
        if ($words < 100) {
            $problems[] = "only {$words} words present in server-rendered HTML (content may be JS-only)";
            $ratio -= 0.4;
        }

        if ($xpath->query('//link[@rel="canonical"]')->length === 0) {
            $problems[] = 'no canonical tag';
            $ratio -= 0.1;
        }

        $ratio = max(0.0, min(1.0, $ratio));
        $detail = $problems ? 'Issues: ' . implode('; ', $problems) . '.' : "Page is crawlable, with {$words} words server-rendered.";
        $fix = $problems ? 'Allow indexing and snippets, and ensure the main content is present in server-rendered HTML.' : '';

        return $this->factor($ratio, $detail, $fix);
    }

    // ────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────

    protected function fetch(string $url): ?string
    {
        try {
            $response = $this->providerHttp()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; SeoSuite-GEO-Checker/1.0)'])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('[SEO][GEO] fetch failed', ['url' => $url, 'status' => $response->status()]);
                return null;
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::warning('[SEO][GEO] fetch exception', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function parse(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // Force UTF-8 interpretation; real-world pages are inconsistently declared.
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    /**
     * Text a reader actually sees — script/style removed.
     *
     * Operates on a CLONE: stripping these nodes from the shared document
     * would delete the JSON-LD blocks before scoreStructuredData() can read
     * them, silently zeroing the structured-data factor.
     */
    protected function visibleText(DOMDocument $dom): string
    {
        $clone = clone $dom;
        $xpath = new DOMXPath($clone);

        foreach (iterator_to_array($xpath->query('//script|//style|//noscript')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        return trim(preg_replace('/\s+/', ' ', $clone->textContent ?? ''));
    }

    protected function firstText(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return $node ? trim($node->textContent) : '';
    }

    /** Walk a decoded JSON-LD blob collecting every @type, including @graph. */
    protected function collectSchemaTypes(array $data): array
    {
        $types = [];

        if (isset($data['@type'])) {
            foreach ((array) $data['@type'] as $type) {
                if (is_string($type)) {
                    $types[] = $type;
                }
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $types = array_merge($types, $this->collectSchemaTypes($value));
            }
        }

        return $types;
    }

    protected function factor(float $ratio, string $detail, string $fix): array
    {
        $ratio = max(0.0, min(1.0, $ratio));

        return [
            'ratio'   => $ratio,
            'percent' => (int) round($ratio * 100),
            'detail'  => $detail,
            'fix'     => $fix,
        ];
    }

    protected function grade(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Needs Work',
            default      => 'Poor',
        };
    }

    /** The lowest-scoring factors, weighted by how much they cost — what to fix first. */
    protected function priorities(array $factors): array
    {
        $gaps = [];
        foreach ($factors as $key => $factor) {
            if ($factor['ratio'] >= 1.0 || $factor['fix'] === '') {
                continue;
            }
            $gaps[] = [
                'key'          => $key,
                'label'        => self::FACTORS[$key]['label'],
                'fix'          => $factor['fix'],
                'points_lost'  => round((1 - $factor['ratio']) * self::FACTORS[$key]['weight'], 1),
            ];
        }

        usort($gaps, fn($a, $b) => $b['points_lost'] <=> $a['points_lost']);

        return array_slice($gaps, 0, 5);
    }
}
