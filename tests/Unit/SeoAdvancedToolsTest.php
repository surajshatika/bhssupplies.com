<?php

namespace Tests\Unit;

use App\Services\Seo\Advanced\EmbeddingKeywordClusterService;
use App\Services\Seo\Advanced\GeoReadinessService;
use App\Services\Seo\Advanced\SeoResearchAgentService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Covers the deterministic parts of the advanced SEO tools — the scoring,
 * the clustering math, and the agent's URL allowlist. None of these should
 * ever need a network call or an AI response to verify.
 */
class SeoAdvancedToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ── GEO readiness scoring ───────────────────────────────────────────

    public function test_geo_scoring_is_deterministic_for_identical_html(): void
    {
        $service = new GeoReadinessService();
        $html = $this->wellOptimizedPage();

        $first = $service->analyzeHtml($html, 'https://example.com/a');
        $second = $service->analyzeHtml($html, 'https://example.com/a');

        $this->assertSame($first['score'], $second['score'], 'Same HTML must always produce the same score.');
    }

    public function test_geo_score_is_higher_for_a_well_structured_page(): void
    {
        $service = new GeoReadinessService();

        $good = $service->analyzeHtml($this->wellOptimizedPage());
        $poor = $service->analyzeHtml('<html><body><div>Buy now. Great deals.</div></body></html>');

        $this->assertGreaterThan($poor['score'], $good['score']);
        $this->assertGreaterThan(60, $good['score']);
        $this->assertLessThan(35, $poor['score']);
    }

    public function test_geo_detects_duplicate_h1_tags(): void
    {
        $service = new GeoReadinessService();

        $report = $service->analyzeHtml('<html><body><h1>First</h1><h1>Second</h1><h2>Section</h2></body></html>');

        $this->assertStringContainsString('2 H1 tags', $report['factors']['heading_clarity']['detail']);
        $this->assertLessThan(1.0, $report['factors']['heading_clarity']['ratio']);
    }

    public function test_geo_penalises_noindex_pages(): void
    {
        $service = new GeoReadinessService();

        $indexable = $service->analyzeHtml($this->wellOptimizedPage());
        $blocked = $service->analyzeHtml(str_replace(
            '<head>',
            '<head><meta name="robots" content="noindex">',
            $this->wellOptimizedPage()
        ));

        $this->assertLessThan(
            $indexable['factors']['ai_crawlability']['ratio'],
            $blocked['factors']['ai_crawlability']['ratio']
        );
    }

    public function test_geo_reads_json_ld_schema_types_including_graph(): void
    {
        $service = new GeoReadinessService();

        $html = '<html><body><h1>T</h1><script type="application/ld+json">
            {"@graph":[{"@type":"FAQPage"},{"@type":"Organization"}]}
        </script></body></html>';

        $report = $service->analyzeHtml($html);

        $this->assertStringContainsString('FAQPage', $report['factors']['structured_data']['detail']);
        $this->assertStringContainsString('Organization', $report['factors']['structured_data']['detail']);
    }

    public function test_geo_priorities_are_ordered_by_points_lost(): void
    {
        $service = new GeoReadinessService();

        $report = $service->analyzeHtml('<html><body><p>Nothing much here at all.</p></body></html>');
        $priorities = $report['priorities'];

        $this->assertNotEmpty($priorities);
        for ($i = 1; $i < count($priorities); $i++) {
            $this->assertGreaterThanOrEqual(
                $priorities[$i]['points_lost'],
                $priorities[$i - 1]['points_lost'],
                'Priorities must be sorted by points lost, descending.'
            );
        }
    }

    // ── Embedding clustering math ───────────────────────────────────────

    public function test_cosine_similarity_matches_known_values(): void
    {
        $service = new EmbeddingKeywordClusterService();

        // Identical direction → 1.0
        $this->assertEqualsWithDelta(1.0, $service->cosine([1, 0, 0], [1, 0, 0]), 0.0001);
        // Orthogonal → 0.0
        $this->assertEqualsWithDelta(0.0, $service->cosine([1, 0, 0], [0, 1, 0]), 0.0001);
        // Opposite → -1.0
        $this->assertEqualsWithDelta(-1.0, $service->cosine([1, 0, 0], [-1, 0, 0]), 0.0001);
        // Magnitude must not matter, only direction.
        $this->assertEqualsWithDelta(1.0, $service->cosine([2, 2, 0], [8, 8, 0]), 0.0001);
        // 45 degrees → cos(45°) ≈ 0.7071
        $this->assertEqualsWithDelta(0.7071, $service->cosine([1, 0], [1, 1]), 0.0001);
    }

    public function test_clustering_groups_similar_vectors_and_separates_dissimilar_ones(): void
    {
        $service = new EmbeddingKeywordClusterService();

        $keywords = ['safety helmet', 'hard hat', 'work gloves'];
        $vectors = [
            [1.0, 0.0, 0.0],    // helmet
            [0.99, 0.14, 0.0],  // hard hat — nearly identical direction
            [0.0, 0.0, 1.0],    // gloves — orthogonal
        ];

        $clusters = $service->buildClusters($keywords, $vectors, 0.82);

        $this->assertCount(2, $clusters, 'Helmet+hard hat should merge; gloves should stand alone.');
        $this->assertSame(2, $clusters[0]['size']);
        $this->assertContains('safety helmet', $clusters[0]['keywords']);
        $this->assertContains('hard hat', $clusters[0]['keywords']);
        $this->assertSame(['work gloves'], $clusters[1]['keywords']);
        $this->assertTrue($clusters[1]['is_single']);
    }

    public function test_clustering_is_transitive_through_union_find(): void
    {
        $service = new EmbeddingKeywordClusterService();

        // A~B and B~C, but A and C are below threshold to each other.
        // Single-link clustering must still place all three together.
        $keywords = ['a', 'b', 'c'];
        $vectors = [
            [1.0, 0.0],
            [0.94, 0.34],
            [0.77, 0.64],
        ];

        $clusters = $service->buildClusters($keywords, $vectors, 0.90);

        $this->assertCount(1, $clusters);
        $this->assertSame(3, $clusters[0]['size']);
    }

    public function test_every_keyword_survives_clustering(): void
    {
        $service = new EmbeddingKeywordClusterService();

        $keywords = ['k1', 'k2', 'k3', 'k4', 'k5'];
        $vectors = [[1, 0], [0, 1], [1, 1], [-1, 0], [0.5, 0.5]];

        $clusters = $service->buildClusters($keywords, $vectors, 0.85);

        $recovered = collect($clusters)->pluck('keywords')->flatten()->sort()->values()->all();
        $this->assertSame($keywords, $recovered, 'Clustering must never drop or duplicate a keyword.');
    }

    public function test_head_term_is_the_most_central_member(): void
    {
        $service = new EmbeddingKeywordClusterService();

        // 'middle' sits between the two outliers, so it best represents the group.
        $keywords = ['left', 'middle', 'right'];
        $vectors = [
            [1.0, 0.0],
            [0.92, 0.38],
            [0.71, 0.71],
        ];

        $clusters = $service->buildClusters($keywords, $vectors, 0.5);

        $this->assertCount(1, $clusters);
        $this->assertSame('middle', $clusters[0]['head']);
    }

    public function test_clustering_rejects_unsupported_provider(): void
    {
        $service = new EmbeddingKeywordClusterService();

        $result = $service->cluster(['one', 'two'], 'claude');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('no embeddings API', $result['error']);
    }

    // ── Research agent SSRF boundary ────────────────────────────────────

    public function test_agent_rejects_private_and_local_addresses(): void
    {
        $agent = new SeoResearchAgentService();

        $blocked = [
            'http://localhost/admin',
            'http://127.0.0.1/',
            'http://169.254.169.254/latest/meta-data/',
            'http://192.168.1.1/',
            'http://10.0.0.5/internal',
            'http://intranet.local/',
            'file:///etc/passwd',
            'ftp://example.com/x',
        ];

        $result = $agent->research('Anything', $blocked);

        // With every seed rejected there is nothing to read, so the run must
        // refuse rather than proceed.
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('No valid public', $result['error']);
    }

    /** A realistic page that should score well on most GEO factors. */
    protected function wellOptimizedPage(): string
    {
        $year = date('Y');

        return <<<HTML
<html><head>
<link rel="canonical" href="https://example.com/guide">
<meta property="og:site_name" content="Example Supply Co">
<meta name="author" content="Jane Smith">
</head><body>
<h1>How Long Does a Safety Helmet Last?</h1>
<p>A safety helmet should be replaced every five years from its date of manufacture,
or immediately after any significant impact. Most manufacturers stamp the production
date inside the shell, and daily outdoor use can shorten that window considerably.</p>
<h2>What affects helmet lifespan?</h2>
<p>UV exposure degrades the shell over time. Helmets used outdoors in direct sun
typically need replacement 30 percent sooner than those used indoors.</p>
<ul><li>Impact damage — replace immediately</li><li>UV exposure — inspect every 6 months</li><li>Chemical contact — replace</li></ul>
<h2>How do I check the manufacture date?</h2>
<p>Look for the moulded date wheel inside the shell near the harness attachment points.</p>
<h2>When should you inspect a helmet?</h2>
<table><tr><th>Use case</th><th>Inspection interval</th></tr>
<tr><td>Daily site work</td><td>Every 30 days</td></tr>
<tr><td>Occasional use</td><td>Every 6 months</td></tr></table>
<p>Updated for {$year}. Reviewed by our safety team.</p>
<time datetime="{$year}-01-15">January 15, {$year}</time>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","datePublished":"{$year}-01-15",
"author":{"@type":"Person","name":"Jane Smith"},
"publisher":{"@type":"Organization","name":"Example Supply Co"}}
</script>
</body></html>
HTML;
    }
}
