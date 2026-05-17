<?php

namespace App\Http\Controllers;

use App\AddonAi\PerformanceOptimizer\Engine\AutoFixEngine;
use App\AddonAi\PerformanceOptimizer\Engine\RecommendationEngine;
use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\AiRecommendation;
use App\Models\PerformanceOptimizer\AutoFixHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PerformanceAiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $pending   = AiRecommendation::pending()->orderByRaw("FIELD(severity, 'critical','high','medium','low')")->orderByDesc('confidence')->get();
        $applied   = AiRecommendation::whereNotNull('applied_at')->orderByDesc('applied_at')->limit(20)->get();
        $dismissed = AiRecommendation::whereNotNull('dismissed_at')->orderByDesc('dismissed_at')->limit(10)->get();
        $history   = AutoFixHistory::orderByDesc('applied_at')->with('recommendation')->limit(20)->get();

        $stats = [
            'pending'    => $pending->count(),
            'applied'    => AiRecommendation::whereNotNull('applied_at')->where('applied_at', '>=', now()->startOfMonth())->count(),
            'autofixed'  => AutoFixHistory::where('applied_at', '>=', now()->subDays(30))->count(),
            'dismissed'  => AiRecommendation::whereNotNull('dismissed_at')->count(),
        ];

        return view('backend.performance_optimizer.index', [
            'tab'       => 'ai',
            'pending'   => $pending,
            'applied'   => $applied,
            'dismissed' => $dismissed,
            'history'   => $history,
            'stats'     => $stats,
        ]);
    }

    public function run(RecommendationEngine $engine)
    {
        if ($r = $this->demoBlock()) return $r;
        $count = $engine->run();
        flash(translate('Analysis complete —') . " {$count} " . translate('new recommendations.'))->success();
        return back();
    }

    public function apply($id, AutoFixEngine $fixer)
    {
        if ($r = $this->demoBlock()) return $r;
        $rec = AiRecommendation::findOrFail($id);
        $r = $fixer->apply($rec, Auth::id());
        if (!empty($r['ok'])) {
            flash(translate('Auto-fix applied.'))->success();
        } else {
            flash(translate('Auto-fix failed') . ': ' . ($r['error'] ?? 'unknown'))->error();
        }
        return back();
    }

    public function dismiss($id)
    {
        if ($r = $this->demoBlock()) return $r;
        $rec = AiRecommendation::findOrFail($id);
        $rec->update(['dismissed_at' => now()]);
        flash(translate('Recommendation dismissed.'))->success();
        return back();
    }

    public function rollback($id, AutoFixEngine $fixer)
    {
        if ($r = $this->demoBlock()) return $r;
        $h = AutoFixHistory::findOrFail($id);
        $r = $fixer->rollback($h);
        if (!empty($r['ok'])) {
            flash(translate('Auto-fix rolled back.'))->success();
        } else {
            flash(translate('Rollback failed') . ': ' . ($r['error'] ?? 'unknown'))->error();
        }
        return back();
    }

    public function save(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;
        foreach (['perf_ai_recs_status', 'perf_ai_recs_auto_apply', 'perf_ai_recs_auto_apply_threshold', 'perf_ai_recs_categories'] as $k) {
            $s = BusinessSetting::firstOrNew(['type' => $k]);
            $s->value = (string) $request->input($k, '');
            $s->save();
        }
        Cache::forget('business_settings');
        flash(translate('AI Recommendations settings saved.'))->success();
        return back();
    }

    protected function demoBlock()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }
        return null;
    }
}
