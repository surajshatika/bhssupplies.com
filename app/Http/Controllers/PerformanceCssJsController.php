<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\CssJsMinifierService;

class PerformanceCssJsController extends Controller
{
    protected CssJsMinifierService $service;

    public function __construct(CssJsMinifierService $service)
    {
        $this->middleware(['auth', 'admin']);
        $this->service = $service;
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'              => 'cssjs',
            'missing_dimensions' => $this->service->scanMissingDimensions(),
        ]);
    }

    public function minifyCss()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        $r = $this->service->minifyAllCss();
        flash(translate('CSS minified.') . " {$r['minified']} / {$r['files']} files. " . translate('Saved') . ": {$r['saved_human']}")->success();
        return back();
    }

    public function minifyJs()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        if (!$this->service->jsMinifierAvailable()) {
            flash(translate('JS minifier not installed. Run on your server: composer require tedivm/jshrink'))->error();
            return back();
        }
        $r = $this->service->minifyAllJs();
        flash(translate('JS minified.') . " {$r['minified']} / {$r['files']} files. " . translate('Saved') . ": {$r['saved_human']}")->success();
        return back();
    }

    public function scanDimensions()
    {
        $missing = $this->service->scanMissingDimensions();
        return response()->json(['success' => true, 'count' => count($missing), 'items' => $missing]);
    }

    public function fixDimensions()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        $r = $this->service->autoFixDimensions();
        flash(translate('Fixed') . " {$r['fixed']} <img> " . translate('tags. Skipped') . " {$r['skipped']}.")->success();
        return back();
    }
}
