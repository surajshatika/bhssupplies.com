<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\Advanced\CruxFieldDataService;
use App\Services\Seo\Advanced\EmbeddingKeywordClusterService;
use App\Services\Seo\Advanced\GeoReadinessService;
use App\Services\Seo\Advanced\SeoResearchAgentService;
use App\Services\Seo\Advanced\StreamingAiService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Advanced AI SEO tools: GEO readiness, real CrUX field data, embedding-based
 * keyword clustering, the autonomous research agent, and SSE streaming.
 */
class AdvancedSeoController extends Controller
{
    // ── GEO / AI Search Readiness ───────────────────────────────────────

    public function geoReadiness()
    {
        return view('backend.seo_suite.geo_readiness', [
            'factors' => GeoReadinessService::FACTORS,
        ]);
    }

    public function runGeoReadiness(Request $request, GeoReadinessService $service)
    {
        $request->validate(['url' => 'required|url']);

        $report = $service->analyze($request->input('url'));

        if (isset($report['error'])) {
            return back()->with('error', $report['error'])->withInput();
        }

        return back()
            ->with('success', 'GEO readiness analysis complete.')
            ->with('geo_report', $report);
    }

    // ── Core Web Vitals — real field data ───────────────────────────────

    public function fieldData(Request $request, CruxFieldDataService $crux)
    {
        $url = $request->input('url');
        $formFactor = in_array($request->input('form_factor'), ['PHONE', 'DESKTOP'], true)
            ? $request->input('form_factor')
            : 'PHONE';

        $result = null;
        if ($url) {
            $request->validate(['url' => 'url']);
            $result = $crux->fetchWithOriginFallback($url, $formFactor);
        }

        return view('backend.seo_suite.field_data', [
            'result'     => $result,
            'url'        => $url,
            'formFactor' => $formFactor,
            'siteUrl'    => config('seo.site_url', config('app.url')),
        ]);
    }

    // ── Embedding keyword clustering ────────────────────────────────────

    public function keywordClusters()
    {
        return view('backend.seo_suite.keyword_clusters', [
            'providers'      => EmbeddingKeywordClusterService::SUPPORTED,
            'targetKeywords' => $this->savedKeywords(),
        ]);
    }

    public function runKeywordClusters(Request $request, EmbeddingKeywordClusterService $service)
    {
        $request->validate([
            'keywords'  => 'required|string',
            'provider'  => 'required|string',
            'threshold' => 'nullable|numeric|min:0.5|max:0.99',
        ]);

        $keywords = preg_split('/[\r\n,]+/', $request->input('keywords'));
        $threshold = (float) ($request->input('threshold') ?: 0.82);

        $result = $service->cluster($keywords, $request->input('provider'), $threshold);

        if (isset($result['error'])) {
            return back()->with('error', $result['error'])->withInput();
        }

        return back()
            ->with('success', 'Clustered ' . $result['keyword_count'] . ' keywords into ' . $result['cluster_count'] . ' groups.')
            ->with('cluster_result', $result)
            ->withInput();
    }

    // ── Autonomous research agent ───────────────────────────────────────

    public function researchAgent()
    {
        return view('backend.seo_suite.research_agent', [
            'maxFetches' => SeoResearchAgentService::MAX_FETCHES,
            'maxTurns'   => SeoResearchAgentService::MAX_TURNS,
        ]);
    }

    public function runResearchAgent(Request $request, SeoResearchAgentService $agent)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'urls'     => 'required|string',
        ]);

        $urls = array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->input('urls'))));

        $result = $agent->research($request->input('question'), $urls, $request->input('provider') ?: null);

        if (isset($result['error'])) {
            return back()->with('error', $result['error'])->withInput();
        }

        return back()
            ->with('success', 'Research complete — read ' . count($result['sources_read']) . ' source(s).')
            ->with('agent_result', $result)
            ->withInput();
    }

    // ── Streaming generation (SSE) ──────────────────────────────────────

    /**
     * Streams tokens to the browser as they arrive. Output buffering is fully
     * disabled and each event is flushed immediately, otherwise PHP/Apache
     * would hold the whole response and defeat the point of streaming.
     */
    public function streamGenerate(Request $request, StreamingAiService $streamer): StreamedResponse
    {
        $validated = $request->validate([
            'prompt'   => 'required|string|max:8000',
            'system'   => 'nullable|string|max:2000',
            'provider' => 'nullable|string',
        ]);

        $provider = $validated['provider']
            ?? get_setting('seo_suite_default_provider', config('seo.default_provider', 'openai'));

        $response = new StreamedResponse(function () use ($validated, $provider, $streamer) {
            // Tear down every buffering layer we control before the first write.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ignore_user_abort(false);

            $send = function (string $event, array $data) {
                echo 'event: ' . $event . "\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $send('start', ['provider' => $provider]);

            $result = $streamer->stream(
                $validated['prompt'],
                $validated['system'] ?? null,
                $provider,
                fn(string $delta) => $send('delta', ['text' => $delta])
            );

            if ($result['error']) {
                $send('error', ['message' => $result['error']]);
            }

            $send('done', [
                'streamed' => $result['streamed'],
                'chars'    => $result['chars'],
                // Told plainly so the UI never implies token streaming it didn't get.
                'note'     => $result['streamed']
                    ? null
                    : 'This provider has no incremental streaming API — the response arrived complete, in one piece.',
            ]);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('X-Accel-Buffering', 'no'); // disable nginx proxy buffering
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    /** @return string[] */
    protected function savedKeywords(): array
    {
        $raw = function_exists('get_setting') ? (string) get_setting('seo_target_keywords', '') : '';

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw))));
    }
}
