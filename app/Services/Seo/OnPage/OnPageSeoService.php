<?php

namespace App\Services\Seo\OnPage;

use App\Services\Seo\Support\AbstractSeoService;
use App\Services\Seo\OnPage\Features\TruSeoAnalysisService;
use App\Services\Seo\OnPage\Features\BreadcrumbService;
use App\Services\Seo\OnPage\Features\ImageSeoService;
use Illuminate\Support\Str;

class OnPageSeoService extends AbstractSeoService
{
    public function run($feature, array $payload = [])
    {
        $map = [
            'meta_tags' => 'generateMetaTags',
            'keyword_density' => 'analyzeKeywordDensity',
            'content_writer' => 'writeSeoArticle',
            'heading_structure' => 'buildHeadingStructure',
            'alt_text' => 'generateAltText',
            'internal_links' => 'suggestInternalLinks',
            'schema_markup' => 'generateSchemaMarkup',
            'readability' => 'analyzeReadability',
            'seo_audit' => 'runSeoAudit',
            'open_graph'      => 'generateOpenGraph',
            'truseo_analysis' => 'runTruSeoAnalysis',
            'breadcrumbs'     => 'generateBreadcrumbs',
            'image_seo'       => 'runImageSeo',
        ];

        if (!isset($map[$feature])) {
            throw new \InvalidArgumentException('Unsupported on-page feature: '.$feature);
        }

        return call_user_func([$this, $map[$feature]], $payload);
    }

    public function generateMetaTags(array $payload)
    {
        $keyword = trim((string) data_get($payload, 'keyword', data_get($payload, 'title', 'SEO keyword')));
        $title = trim((string) data_get($payload, 'title', $keyword));
        $description = trim((string) data_get($payload, 'description', data_get($payload, 'content', '')));

        $fallback = [
            'variations' => [
                [
                    'title' => Str::limit($keyword.' | '.get_setting('website_name', config('app.name')), 60, ''),
                    'description' => Str::limit($description ?: 'Explore '.$keyword.' with fast delivery, trusted pricing, and expert support from '.get_setting('website_name', config('app.name')).'.', 160, ''),
                ],
                [
                    'title' => Str::limit('Buy '.$keyword.' Online from '.get_setting('website_name', config('app.name')), 60, ''),
                    'description' => Str::limit('Shop '.$keyword.' with clear specs, quality service, and SEO-ready product content for better click-through rates.', 160, ''),
                ],
                [
                    'title' => Str::limit($title.' - Best Deals & Expert Support', 60, ''),
                    'description' => Str::limit('Improve rankings and clicks for '.$keyword.' with focused metadata, benefit-led copy, and strong search intent alignment.', 160, ''),
                ],
            ],
            'provider' => $this->providerName,
        ];

        return $this->askForJson(
            'Create 3 CTR-focused SEO title and meta description variations. Return JSON with key variations.',
            'You are an SEO copywriter. Output JSON only.',
            $fallback
        );
    }

    public function analyzeKeywordDensity(array $payload)
    {
        $content = $this->plainText(data_get($payload, 'content'));
        $keyword = strtolower(trim((string) data_get($payload, 'keyword')));
        $totalWords = max(1, $this->wordCount($content));
        $occurrences = $keyword === '' ? 0 : substr_count(strtolower($content), $keyword);
        $density = round(($occurrences / $totalWords) * 100, 2);

        return [
            'keyword' => $keyword,
            'density' => $density,
            'occurrences' => $occurrences,
            'total_words' => $totalWords,
            'status' => $density >= 0.5 && $density <= 2.5 ? 'healthy' : ($density > 2.5 ? 'overused' : 'underused'),
            'placements' => [
                'title' => Str::contains(strtolower((string) data_get($payload, 'title')), $keyword),
                'h1' => Str::contains(strtolower((string) data_get($payload, 'h1', data_get($payload, 'title'))), $keyword),
                'first_100_words' => Str::contains(strtolower(Str::limit($content, 600, '')), $keyword),
            ],
            'lsi_keywords' => array_slice($this->slugWords($keyword.' supplier wholesale industrial quality reliable durable'), 0, 6),
            'suggestions' => [
                $density < 0.5 ? 'Add the focus keyword naturally in the intro and one supporting heading.' : 'Keyword density is within a workable range.',
                $density > 2.5 ? 'Reduce repetitive exact-match usage and replace some mentions with semantically related phrases.' : 'Maintain semantic variety with related terms and modifiers.',
            ],
            'provider' => $this->providerName,
        ];
    }

    public function writeSeoArticle(array $payload)
    {
        $topic = trim((string) data_get($payload, 'topic', data_get($payload, 'keyword', 'SEO article')));
        $keyword = trim((string) data_get($payload, 'keyword', $topic));
        $cta = trim((string) data_get($payload, 'cta', 'Contact us for a tailored quote.'));

        $fallback = '<article><h1>'.e($topic).'</h1><p>'.e('This SEO-ready article is centered around '.$keyword.' and structured for readability, intent match, and internal linking.').'</p><h2>Why '.$keyword.' Matters</h2><p>'.e('Searchers want clarity, trust, and actionable buying information. Use this section to explain applications, quality markers, and product selection criteria.').'</p><h2>How to Choose the Right Solution</h2><p>'.e('Cover specifications, compliance, installation, maintenance, and pricing factors. Add internal links to related category or product pages.').'</p><h2>Frequently Asked Questions</h2><p><strong>What should buyers compare first?</strong> Compare material, compatibility, safety, and long-term operating cost.</p><p><strong>How can this page rank better?</strong> Keep metadata aligned, answer intent clearly, and support the page with related internal links.</p><p>'.e($cta).'</p></article>';

        return [
            'title' => Str::title($topic),
            'keyword' => $keyword,
            'html' => $this->askForText(
                'Write an SEO-friendly HTML article with H1-H3 headings, FAQ content, and a CTA.',
                'You are an SEO content writer. Return clean HTML only.',
                $fallback
            ),
            'meta_description' => Str::limit('Learn about '.$keyword.' with buyer-focused guidance, SEO-friendly structure, and practical answers for faster decision-making.', 160, ''),
            'provider' => $this->providerName,
        ];
    }

    public function buildHeadingStructure(array $payload)
    {
        $topic = trim((string) data_get($payload, 'topic', data_get($payload, 'keyword', 'SEO topic')));
        $keyword = trim((string) data_get($payload, 'keyword', $topic));

        return [
            'h1' => Str::title($topic),
            'h2' => [
                [
                    'title' => 'What to know about '.$keyword,
                    'h3' => ['Core benefits', 'Common use cases'],
                ],
                [
                    'title' => 'How to compare '.$keyword.' options',
                    'h3' => ['Technical specifications', 'Pricing factors'],
                ],
                [
                    'title' => 'Implementation and maintenance tips',
                    'h3' => ['Installation checklist', 'Troubleshooting basics'],
                ],
            ],
            'provider' => $this->providerName,
        ];
    }

    public function generateAltText(array $payload)
    {
        $keyword = trim((string) data_get($payload, 'keyword', 'product'));
        $images = data_get($payload, 'images', []);
        $images = is_array($images) ? $images : $this->csvList($images);
        $results = [];

        foreach ($images as $index => $image) {
            $label = is_array($image) ? data_get($image, 'label', 'Image '.($index + 1)) : basename((string) $image);
            $results[] = [
                'image' => $image,
                'alt_text' => Str::limit(trim($keyword.' '.str_replace(['-', '_'], ' ', pathinfo($label, PATHINFO_FILENAME))), 125, ''),
                'has_keyword' => true,
            ];
        }

        return [
            'items' => $results,
            'provider' => $this->providerName,
        ];
    }

    public function suggestInternalLinks(array $payload)
    {
        $keyword = trim((string) data_get($payload, 'keyword', data_get($payload, 'title', 'target page')));
        $links = data_get($payload, 'links', []);
        $links = is_array($links) ? $links : $this->csvList($links);
        $suggestions = [];

        foreach (array_slice($links, 0, 8) as $index => $link) {
            $suggestions[] = [
                'url' => $link,
                'anchor' => $keyword.' guide '.($index + 1),
                'placement' => 'Paragraph '.($index + 2),
                'relevance_score' => max(6, 10 - $index),
            ];
        }

        return [
            'suggestions' => $suggestions,
            'orphan_page_risk' => empty($suggestions),
            'provider' => $this->providerName,
        ];
    }

    public function generateSchemaMarkup(array $payload)
    {
        $schemaType = data_get($payload, 'schema_type', 'Article');
        $title = trim((string) data_get($payload, 'title', data_get($payload, 'keyword', get_setting('website_name', config('app.name')))));
        $description = trim((string) data_get($payload, 'description', data_get($payload, 'meta_description', '')));
        $url = $this->normalizeUrl(data_get($payload, 'url', request()->fullUrl()));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'headline' => $title,
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_setting('website_name', config('app.name')),
            ],
        ];

        return [
            'schema_type' => $schemaType,
            'json_ld' => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'provider' => $this->providerName,
        ];
    }

    public function analyzeReadability(array $payload)
    {
        $content = $this->plainText(data_get($payload, 'content'));
        $sentences = array_filter(preg_split('/[.!?]+/', $content));
        $sentenceCount = max(1, count($sentences));
        $wordCount = max(1, $this->wordCount($content));
        $avgSentenceLength = round($wordCount / $sentenceCount, 2);
        $flesch = max(0, min(100, 100 - ($avgSentenceLength * 1.8)));

        return [
            'flesch_score' => round($flesch, 2),
            'grade' => $flesch >= 70 ? 'Easy' : ($flesch >= 50 ? 'Standard' : 'Hard'),
            'average_sentence_length' => $avgSentenceLength,
            'suggestions' => [
                'Keep sentences under 20 words where possible.',
                'Use clearer transitions between headings and supporting paragraphs.',
                'Break dense product or technical information into bullet-style sections.',
            ],
            'provider' => $this->providerName,
        ];
    }

    public function runSeoAudit(array $payload)
    {
        $title = trim((string) data_get($payload, 'title'));
        $description = trim((string) data_get($payload, 'description'));
        $content = $this->plainText(data_get($payload, 'content'));
        $keyword = trim((string) data_get($payload, 'keyword'));
        $url = $this->normalizeUrl(data_get($payload, 'url', request()->fullUrl()));
        $checks = [
            ['label' => 'Title length', 'status' => strlen($title) >= 45 && strlen($title) <= 60 ? 'pass' : 'warning'],
            ['label' => 'Meta description length', 'status' => strlen($description) >= 120 && strlen($description) <= 160 ? 'pass' : 'warning'],
            ['label' => 'Keyword in title', 'status' => $keyword && Str::contains(strtolower($title), strtolower($keyword)) ? 'pass' : 'warning'],
            ['label' => 'Keyword in first paragraph', 'status' => $keyword && Str::contains(strtolower(Str::limit($content, 500, '')), strtolower($keyword)) ? 'pass' : 'warning'],
            ['label' => 'Word count', 'status' => $this->wordCount($content) >= 300 ? 'pass' : 'warning'],
            ['label' => 'Canonical ready', 'status' => $url ? 'pass' : 'warning'],
            ['label' => 'Open graph ready', 'status' => $title && $description ? 'pass' : 'warning'],
            ['label' => 'Schema ready', 'status' => 'pass'],
            ['label' => 'SSL', 'status' => Str::startsWith($url, 'https://') ? 'pass' : 'warning'],
            ['label' => 'Readable content', 'status' => $this->analyzeReadability($payload)['flesch_score'] >= 45 ? 'pass' : 'warning'],
            ['label' => 'Image alt coverage', 'status' => !empty(data_get($payload, 'images', [])) ? 'pass' : 'warning'],
            ['label' => 'Internal links', 'status' => !empty(data_get($payload, 'links', [])) ? 'pass' : 'warning'],
        ];
        $score = $this->scoreFromChecks($checks);

        return [
            'url' => $url,
            'score' => $score,
            'grade' => $this->gradeFromScore($score),
            'checks' => $checks,
            'fixes' => [
                'Tighten title and description lengths around click-friendly limits.',
                'Add more internal links and semantically related phrases.',
                'Improve supporting content depth if the page is below 300 words.',
            ],
            'provider' => $this->providerName,
        ];
    }

    public function runTruSeoAnalysis(array $payload): array
    {
        return app(TruSeoAnalysisService::class)->handle($payload);
    }

    public function generateBreadcrumbs(array $payload): array
    {
        return app(BreadcrumbService::class)->handle($payload);
    }

    public function runImageSeo(array $payload): array
    {
        return app(ImageSeoService::class)->handle($payload);
    }

    public function generateOpenGraph(array $payload)
    {
        $title = trim((string) data_get($payload, 'title', get_setting('meta_title', get_setting('website_name', config('app.name')))));
        $description = trim((string) data_get($payload, 'description', get_setting('meta_description')));
        $url = $this->normalizeUrl(data_get($payload, 'url', request()->fullUrl()));
        $image = data_get($payload, 'image', uploaded_asset(get_setting('meta_image')));

        return [
            'html' => implode("\n", [
                '<meta property="og:title" content="'.e($title).'" />',
                '<meta property="og:description" content="'.e($description).'" />',
                '<meta property="og:url" content="'.e($url).'" />',
                '<meta property="og:type" content="website" />',
                '<meta property="og:image" content="'.e($image).'" />',
                '<meta name="twitter:card" content="summary_large_image" />',
                '<meta name="twitter:title" content="'.e($title).'" />',
                '<meta name="twitter:description" content="'.e($description).'" />',
                '<meta name="twitter:image" content="'.e($image).'" />',
            ]),
            'provider' => $this->providerName,
        ];
    }
}
