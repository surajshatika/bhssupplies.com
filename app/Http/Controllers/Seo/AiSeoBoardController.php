<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\SeoFixBatch;
use App\Services\Seo\Board\AiSeoBoardService;
use App\Services\Seo\Budget\SeoBudgetGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiSeoBoardController extends Controller
{
    public function __construct(protected AiSeoBoardService $board)
    {
    }

    public function index(Request $request)
    {
        $type    = $request->input('type', 'product');
        $allowed = AiSeoBoardService::SUPPORTED_TYPES;
        if (!in_array($type, $allowed, true)) {
            $type = 'product';
        }

        $filters = [
            'search'    => $request->input('search'),
            'missing'   => $request->input('missing'),
            'min_score' => $request->filled('min_score') ? (int) $request->input('min_score') : null,
            'max_score' => $request->filled('max_score') ? (int) $request->input('max_score') : null,
            'sort'      => $request->input('sort', 'recent'),
        ];

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        $summary   = $this->board->siteSummary();
        $paginator = $this->board->scanAndPaginate($type, $filters, $perPage, (int) $request->input('page', 1));

        $typeCounts = $this->typeCounts();

        return view('backend.seo.ai_board.index', compact(
            'summary', 'paginator', 'filters', 'type', 'typeCounts', 'perPage'
        ));
    }

    public function fix(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:product,category,page,blog',
            'id'   => 'required|integer|min:1',
        ]);

        try {
            $result = $this->board->applyAiFix(
                $request->input('type'),
                (int) $request->input('id'),
                $request->input('provider')
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (Throwable $e) {
            logger()->error('AI SEO Board fix failed', [
                'type'  => $request->input('type'),
                'id'    => $request->input('id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function rescore(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:product,category,page,blog',
            'id'   => 'required|integer|min:1',
        ]);

        $type  = $request->input('type');
        $id    = (int) $request->input('id');
        $class = match ($type) {
            'product'  => \App\Models\Product::class,
            'category' => \App\Models\Category::class,
            'page'     => \App\Models\Page::class,
            'blog'     => \App\Models\Blog::class,
        };

        $entity = $class::find($id);
        if (!$entity) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'row'     => $this->board->buildRow($entity, $type),
        ]);
    }

    protected function typeCounts(): array
    {
        return [
            'product'  => \App\Models\Product::query()->count(),
            'category' => \App\Models\Category::query()->count(),
            'page'     => \App\Models\Page::query()->count(),
            'blog'     => \App\Models\Blog::query()->count(),
        ];
    }

    /**
     * Pre-flight: collect targets + return a cost estimate. UI shows this in
     * the confirmation modal before the admin actually clicks "Run".
     */
    public function bulkEstimate(Request $request): JsonResponse
    {
        $payload  = $this->bulkPayload($request);
        $targets  = $this->board->collectTargets($payload);
        $estimate = $this->board->estimateBatchCost($targets, $request->input('provider'));

        $guard      = app(SeoBudgetGuard::class);
        $cap        = $guard->dailyCapUsd();
        $spent      = $guard->spendToday();
        $remaining  = $guard->remainingUsd();
        $withinCap  = $guard->allowsAdditional($estimate['usd']);

        return response()->json([
            'success'         => true,
            'count'           => $estimate['count'],
            'estimated_usd'   => $estimate['usd'],
            'per_entity_usd'  => $estimate['per_entity_usd'],
            'provider'        => $estimate['provider'],
            'ai_call'         => $estimate['ai_call'],
            'sync_warning'    => $this->queueWillRunInline() && $estimate['count'] > 25,
            'queue_driver'    => config('queue.default'),
            'budget' => [
                'daily_cap_usd' => $cap,
                'spent_today'   => round($spent, 4),
                'remaining'     => $cap > 0 ? round($remaining, 4) : null,
                'within_cap'    => $withinCap,
            ],
        ]);
    }

    public function bulkRun(Request $request): JsonResponse
    {
        $payload = $this->bulkPayload($request);
        $targets = $this->board->collectTargets($payload);

        if (empty($targets)) {
            return response()->json(['success' => false, 'error' => 'No matching entities to fix.'], 422);
        }

        $estimate = $this->board->estimateBatchCost($targets, $request->input('provider'));
        $guard    = app(SeoBudgetGuard::class);
        if (!$guard->allowsAdditional($estimate['usd'])) {
            return response()->json([
                'success' => false,
                'error'   => sprintf(
                    'Daily SEO AI budget cap would be exceeded. Cap: $%.2f, spent today: $%.4f, this batch: $%.4f.',
                    $guard->dailyCapUsd(),
                    $guard->spendToday(),
                    $estimate['usd']
                ),
            ], 429);
        }

        $batch = $this->board->createBatch(
            $targets,
            $request->input('provider'),
            optional(auth()->user())->id,
            $request->input('label')
        );

        $guard->bustCache();

        return response()->json([
            'success'  => true,
            'batch_id' => $batch->id,
            'snapshot' => $this->batchSnapshot($batch),
        ]);
    }

    public function bulkProgress(int $batchId): JsonResponse
    {
        $batch = SeoFixBatch::find($batchId);
        if (!$batch) {
            return response()->json(['success' => false, 'error' => 'Batch not found'], 404);
        }
        return response()->json([
            'success'  => true,
            'snapshot' => $this->batchSnapshot($batch),
        ]);
    }

    public function bulkCancel(int $batchId): JsonResponse
    {
        $batch = SeoFixBatch::find($batchId);
        if (!$batch) {
            return response()->json(['success' => false, 'error' => 'Batch not found'], 404);
        }
        $batch = $this->board->cancelBatch($batch);
        return response()->json([
            'success'  => true,
            'snapshot' => $this->batchSnapshot($batch),
        ]);
    }

    protected function bulkPayload(Request $request): array
    {
        $request->validate([
            'mode'      => 'required|in:selected,filtered',
            'type'      => 'nullable|in:product,category,page,blog',
            'targets'   => 'array',
            'targets.*' => 'array',
            'limit'     => 'nullable|integer|min:1|max:5000',
        ]);

        if ($request->input('mode') === 'selected') {
            return ['targets' => $request->input('targets', [])];
        }

        return [
            'type'    => $request->input('type', 'product'),
            'filters' => [
                'search'    => $request->input('search'),
                'missing'   => $request->input('missing'),
                'min_score' => $request->filled('min_score') ? (int) $request->input('min_score') : null,
                'max_score' => $request->filled('max_score') ? (int) $request->input('max_score') : null,
                'sort'      => $request->input('sort', 'recent'),
            ],
            'limit'   => (int) $request->input('limit', 500),
        ];
    }

    protected function batchSnapshot(SeoFixBatch $batch): array
    {
        return [
            'id'                 => $batch->id,
            'status'             => $batch->status,
            'label'              => $batch->label,
            'total'              => $batch->total,
            'processed'          => $batch->processed,
            'succeeded'          => $batch->succeeded,
            'failed'             => $batch->failed,
            'skipped'            => $batch->skipped,
            'percent'            => $batch->progressPercent(),
            'current_label'      => $batch->current_label,
            'provider'           => $batch->provider,
            'estimated_cost_usd' => (float) $batch->estimated_cost_usd,
            'actual_cost_usd'    => (float) $batch->actual_cost_usd,
            'started_at'         => optional($batch->started_at)->toDateTimeString(),
            'completed_at'       => optional($batch->completed_at)->toDateTimeString(),
            'is_terminal'        => $batch->isTerminal(),
            'recent_errors'      => array_slice($batch->error_log ?? [], -3),
        ];
    }

    protected function queueWillRunInline(): bool
    {
        return config('queue.default') === 'sync';
    }
}
