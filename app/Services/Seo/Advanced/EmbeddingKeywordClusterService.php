<?php

namespace App\Services\Seo\Advanced;

use App\Services\Seo\Providers\ResilientProviderHttp;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vector-embedding keyword clustering.
 *
 * Groups keywords by semantic meaning so each cluster maps to one page — the
 * standard fix for keyword cannibalisation and the basis for topical-authority
 * content planning.
 *
 * Design principle: the AI supplies EMBEDDINGS ONLY — it never decides the
 * groups. Clustering is done here with deterministic cosine-similarity
 * union-find. That matters because asking an LLM to "group these keywords"
 * yields different answers run to run, silently drops inputs, and cannot be
 * verified. Here the vectors are the only model output, and the grouping is
 * reproducible, inspectable, and unit-testable against known vectors.
 */
class EmbeddingKeywordClusterService
{
    use ResilientProviderHttp;

    /**
     * Providers that expose a real embeddings endpoint. A chat-only provider
     * cannot embed, so those are rejected rather than silently downgraded.
     */
    public const SUPPORTED = [
        'openai'   => ['label' => 'OpenAI',     'model' => 'text-embedding-3-small'],
        'gemini'   => ['label' => 'Gemini',     'model' => 'text-embedding-004'],
        'mistral'  => ['label' => 'Mistral',    'model' => 'mistral-embed'],
        'cohere'   => ['label' => 'Cohere',     'model' => 'embed-v4.0'],
        'together' => ['label' => 'Together AI','model' => 'BAAI/bge-large-en-v1.5'],
    ];

    public const MAX_KEYWORDS = 300;

    /**
     * Cluster keywords by semantic similarity.
     *
     * @param string[] $keywords
     * @param float    $threshold Cosine similarity above which two keywords join a cluster (0..1)
     */
    public function cluster(array $keywords, string $provider = 'openai', float $threshold = 0.82): array
    {
        $keywords = $this->normalizeKeywords($keywords);

        if (count($keywords) < 2) {
            return ['error' => 'Provide at least 2 keywords to cluster.'];
        }
        if (!isset(self::SUPPORTED[$provider])) {
            return ['error' => 'Provider "' . $provider . '" has no embeddings API. Use OpenAI, Gemini, or Mistral.'];
        }

        $truncated = count($keywords) > self::MAX_KEYWORDS;
        if ($truncated) {
            $keywords = array_slice($keywords, 0, self::MAX_KEYWORDS);
        }

        $embeddings = $this->embed($keywords, $provider);
        if (isset($embeddings['error'])) {
            return $embeddings;
        }

        $clusters = $this->buildClusters($keywords, $embeddings['vectors'], $threshold);

        return [
            'provider'      => self::SUPPORTED[$provider]['label'],
            'model'         => self::SUPPORTED[$provider]['model'],
            'threshold'     => $threshold,
            'keyword_count' => count($keywords),
            'cluster_count' => count($clusters),
            'clusters'      => $clusters,
            'truncated'     => $truncated,
            'dimensions'    => count($embeddings['vectors'][0] ?? []),
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    // Clustering — pure deterministic math, no AI involvement.
    // ────────────────────────────────────────────────────────────────────

    /**
     * Single-link agglomerative clustering via union-find: any two keywords
     * whose cosine similarity exceeds the threshold end up in the same group.
     *
     * @param string[]  $keywords
     * @param float[][] $vectors
     */
    public function buildClusters(array $keywords, array $vectors, float $threshold): array
    {
        $count = count($keywords);
        $parent = range(0, $count - 1);

        $find = function (int $i) use (&$parent, &$find): int {
            while ($parent[$i] !== $i) {
                $parent[$i] = $parent[$parent[$i]]; // path compression
                $i = $parent[$i];
            }
            return $i;
        };

        // Pre-normalise once so similarity is a plain dot product.
        $unit = array_map(fn($v) => $this->normalizeVector($v), $vectors);

        $edges = [];
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $similarity = $this->dot($unit[$i], $unit[$j]);
                if ($similarity >= $threshold) {
                    $a = $find($i);
                    $b = $find($j);
                    if ($a !== $b) {
                        $parent[$a] = $b;
                    }
                    $edges[] = ['i' => $i, 'j' => $j, 'similarity' => $similarity];
                }
            }
        }

        $groups = [];
        for ($i = 0; $i < $count; $i++) {
            $groups[$find($i)][] = $i;
        }

        $clusters = [];
        foreach ($groups as $members) {
            $memberKeywords = array_map(fn($idx) => $keywords[$idx], $members);

            $clusters[] = [
                'head'      => $this->pickHead($members, $unit, $keywords),
                'keywords'  => array_values($memberKeywords),
                'size'      => count($members),
                'cohesion'  => $this->cohesion($members, $unit),
                'is_single' => count($members) === 1,
            ];
        }

        // Biggest, tightest clusters first — these are the strongest page candidates.
        usort($clusters, fn($a, $b) => [$b['size'], $b['cohesion']] <=> [$a['size'], $a['cohesion']]);

        return $clusters;
    }

    /**
     * The head term is the cluster member closest to the cluster centroid —
     * i.e. the keyword that best represents the whole group. That is the one
     * the target page should be built around.
     */
    protected function pickHead(array $members, array $unit, array $keywords): string
    {
        if (count($members) === 1) {
            return $keywords[$members[0]];
        }

        $dimensions = count($unit[$members[0]]);
        $centroid = array_fill(0, $dimensions, 0.0);
        foreach ($members as $idx) {
            foreach ($unit[$idx] as $d => $value) {
                $centroid[$d] += $value;
            }
        }
        $centroid = $this->normalizeVector($centroid);

        $best = $members[0];
        $bestScore = -INF;
        foreach ($members as $idx) {
            $score = $this->dot($unit[$idx], $centroid);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $idx;
            }
        }

        return $keywords[$best];
    }

    /** Mean pairwise similarity within a cluster — how tight the grouping is. */
    protected function cohesion(array $members, array $unit): float
    {
        $count = count($members);
        if ($count < 2) {
            return 1.0;
        }

        $sum = 0.0;
        $pairs = 0;
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $sum += $this->dot($unit[$members[$i]], $unit[$members[$j]]);
                $pairs++;
            }
        }

        return $pairs ? round($sum / $pairs, 4) : 1.0;
    }

    /** Cosine similarity between two raw (un-normalised) vectors. */
    public function cosine(array $a, array $b): float
    {
        return $this->dot($this->normalizeVector($a), $this->normalizeVector($b));
    }

    protected function dot(array $a, array $b): float
    {
        $sum = 0.0;
        $length = min(count($a), count($b));
        for ($i = 0; $i < $length; $i++) {
            $sum += $a[$i] * $b[$i];
        }

        return $sum;
    }

    protected function normalizeVector(array $v): array
    {
        $magnitude = 0.0;
        foreach ($v as $value) {
            $magnitude += $value * $value;
        }
        $magnitude = sqrt($magnitude);

        if ($magnitude <= 0.0) {
            return array_fill(0, count($v), 0.0);
        }

        return array_map(fn($value) => $value / $magnitude, $v);
    }

    // ────────────────────────────────────────────────────────────────────
    // Embeddings — the only part that calls an AI provider.
    // ────────────────────────────────────────────────────────────────────

    protected function embed(array $keywords, string $provider): array
    {
        try {
            return match ($provider) {
                // Together exposes an OpenAI-shaped /v1/embeddings endpoint.
                'openai'   => $this->embedOpenAiShaped($keywords, 'openai', 'https://api.openai.com/v1/embeddings'),
                'mistral'  => $this->embedOpenAiShaped($keywords, 'mistral', 'https://api.mistral.ai/v1/embeddings'),
                'together' => $this->embedOpenAiShaped($keywords, 'together', 'https://api.together.xyz/v1/embeddings'),
                'gemini'   => $this->embedGemini($keywords),
                'cohere'   => $this->embedCohere($keywords),
            };
        } catch (Throwable $e) {
            Log::warning('[SEO][Embeddings] failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return ['error' => 'Embedding request failed: ' . $e->getMessage()];
        }
    }

    /**
     * OpenAI, Mistral and Together all expose the same `/v1/embeddings`
     * contract: `{model, input[]}` in, `data[].embedding` out.
     */
    protected function embedOpenAiShaped(array $keywords, string $provider, string $endpoint): array
    {
        $key = $this->key($provider);
        if (!$key) {
            return ['error' => 'No ' . self::SUPPORTED[$provider]['label'] . ' API key configured.'];
        }

        $response = $this->providerHttp()->withToken($key)->post($endpoint, [
            'model' => self::SUPPORTED[$provider]['model'],
            'input' => $keywords,
        ]);

        if (!$response->successful()) {
            return ['error' => self::SUPPORTED[$provider]['label'] . ' embeddings error: '
                . data_get($response->json(), 'error.message', 'HTTP ' . $response->status())];
        }

        $vectors = array_map(fn($row) => $row['embedding'] ?? [], $response->json('data', []));

        return $this->validateVectors($vectors, $keywords);
    }

    /** Cohere's native embed API — needs input_type and returns float arrays. */
    protected function embedCohere(array $keywords): array
    {
        $key = $this->key('cohere');
        if (!$key) {
            return ['error' => 'No Cohere API key configured.'];
        }

        $response = $this->providerHttp()->withToken($key)->post('https://api.cohere.ai/v2/embed', [
            'model'           => self::SUPPORTED['cohere']['model'],
            'texts'           => $keywords,
            'input_type'      => 'clustering',
            'embedding_types' => ['float'],
        ]);

        if (!$response->successful()) {
            return ['error' => 'Cohere embeddings error: ' . data_get($response->json(), 'message', 'HTTP ' . $response->status())];
        }

        $vectors = $response->json('embeddings.float', []);

        return $this->validateVectors($vectors, $keywords);
    }

    /** Gemini batches through a dedicated endpoint with one request per text. */
    protected function embedGemini(array $keywords): array
    {
        $key = $this->key('gemini');
        if (!$key) {
            return ['error' => 'No Gemini API key configured.'];
        }

        $model = self::SUPPORTED['gemini']['model'];
        $requests = array_map(fn($keyword) => [
            'model'   => 'models/' . $model,
            'content' => ['parts' => [['text' => $keyword]]],
        ], $keywords);

        $response = $this->providerHttp()->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:batchEmbedContents?key=" . urlencode($key),
            ['requests' => $requests]
        );

        if (!$response->successful()) {
            return ['error' => 'Gemini embeddings error: ' . data_get($response->json(), 'error.message', 'HTTP ' . $response->status())];
        }

        $vectors = array_map(fn($row) => $row['values'] ?? [], $response->json('embeddings', []));

        return $this->validateVectors($vectors, $keywords);
    }

    /**
     * Refuse to cluster on a malformed or partial embedding response — a short
     * vector list would silently misalign keywords to the wrong vectors.
     */
    protected function validateVectors(array $vectors, array $keywords): array
    {
        if (count($vectors) !== count($keywords)) {
            return ['error' => 'Embedding response did not cover every keyword (' . count($vectors) . ' vectors for ' . count($keywords) . ' keywords).'];
        }

        foreach ($vectors as $vector) {
            if (!is_array($vector) || count($vector) < 2) {
                return ['error' => 'Embedding response contained an invalid vector.'];
            }
        }

        return ['vectors' => $vectors];
    }

    protected function key(string $provider): ?string
    {
        $configured = config("seo.providers.{$provider}.api_key");
        if ($configured) {
            return $configured;
        }

        if (!function_exists('get_setting')) {
            return null;
        }

        return get_setting("seo_{$provider}_api_key") ?: null;
    }

    /** @return string[] */
    protected function normalizeKeywords(array $keywords): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($k) => trim(preg_replace('/\s+/', ' ', (string) $k)),
            $keywords
        ), fn($k) => $k !== '')));
    }
}
