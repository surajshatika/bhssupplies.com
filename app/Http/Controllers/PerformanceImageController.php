<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\ImageOptimizerService;
use App\Jobs\PerformanceOptimizer\ConvertImagesJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class PerformanceImageController extends Controller
{
    protected ImageOptimizerService $service;

    public function __construct(ImageOptimizerService $service)
    {
        $this->middleware(['auth', 'admin']);
        $this->service = $service;
    }

    protected function demoBlock()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        return null;
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'       => 'images',
            'stats'     => $this->service->getStats(),
            'images'    => $this->service->scanUnoptimized(200),
            'oversized' => $this->service->scanOversized(500000, 2000, 30),
        ]);
    }

    public function scan()
    {
        return response()->json([
            'success' => true,
            'stats'   => $this->service->getStats(),
            'images'  => $this->service->scanUnoptimized(500),
        ]);
    }

    public function convertAllWebp(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $quality = $request->input('quality') ? (int) $request->input('quality') : null;
        $limit   = (int) $request->input('limit', 200);
        $async   = (bool) $request->input('async', false);

        if ($async && config('queue.default') !== 'sync') {
            Bus::dispatch(new ConvertImagesJob('webp', $quality, $limit));
            flash(translate('Queued WebP conversion job (up to') . " {$limit} " . translate('images).'))->success();
            return back();
        }

        $r = $this->service->convertAllToWebp($quality, $limit);
        flash(translate('WebP conversion done.') . " {$r['converted']} converted, {$r['skipped']} skipped, {$r['failed']} failed. " . translate('Saved') . ': ' . $r['saved_human'])->success();
        return back();
    }

    public function convertAllAvif(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $quality = $request->input('quality') ? (int) $request->input('quality') : null;
        $r = $this->service->convertAllToAvif($quality, (int) $request->input('limit', 50));
        if (isset($r['converted']) && $r['converted'] > 0) {
            flash(translate('AVIF conversion done.') . " {$r['converted']} converted. " . translate('Saved') . ': ' . $r['saved_human'])->success();
        } else {
            flash(translate('AVIF conversion: 0 files processed (server may not support AVIF)'))->warning();
        }
        return back();
    }

    public function convertSingle(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            return response()->json(['success' => false, 'error' => 'Demo mode'], 403);
        }

        $rel = (string) $request->input('relative', '');
        if ($rel === '' || str_contains($rel, '..') || str_contains($rel, "\0")) {
            return response()->json(['success' => false, 'error' => 'Invalid path'], 422);
        }
        $abs = public_path($rel);
        if (!file_exists($abs) || !$this->service->isPathAllowed($abs)) {
            return response()->json(['success' => false, 'error' => 'File not found or not allowed'], 404);
        }

        $format = $request->input('format', 'webp');
        $r = $format === 'avif'
            ? $this->service->convertSingleToAvif($abs)
            : $this->service->convertSingleToWebp($abs);

        return response()->json($r);
    }

    public function restoreAll()
    {
        if ($r = $this->demoBlock()) return $r;

        $r = $this->service->restoreAll();
        flash(translate('Originals restored.') . " {$r['restored']} restored, {$r['failed']} failed.")->success();
        return back();
    }

    public function cleanBackups(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        $days = max(1, (int) $request->input('days', 30));
        $n = $this->service->cleanOldBackups($days);
        flash(translate('Deleted') . " {$n} " . translate('backup file(s) older than') . " {$days} " . translate('days.'))->success();
        return back();
    }
}
