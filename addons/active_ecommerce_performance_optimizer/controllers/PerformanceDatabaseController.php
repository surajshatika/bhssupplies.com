<?php

namespace App\Http\Controllers;

use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use Illuminate\Http\Request;

class PerformanceDatabaseController extends Controller
{
    protected DatabaseCleanerService $service;

    public function __construct(DatabaseCleanerService $service)
    {
        $this->middleware(['auth', 'admin']);
        $this->service = $service;
    }

    public function index()
    {
        return view('backend.performance_optimizer.index', [
            'tab'   => 'database',
            'stats' => $this->service->getStats(),
        ]);
    }

    public function clean(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        $items = (array) $request->input('items', []);
        if (empty($items)) {
            flash(translate('Select at least one item to clean.'))->warning();
            return back();
        }
        $r = $this->service->clean($items);
        $total = array_sum($r['deleted'] ?? []);
        flash(translate('Cleanup complete. Deleted rows') . ": {$total}")->success();
        return back();
    }

    public function optimizeTables()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        $r = $this->service->optimizeTables();
        flash(translate('OPTIMIZE TABLE ran on') . " {$r['optimized']} " . translate('tables.'))->success();
        return back();
    }
}
