<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\ScriptRule;
use App\Services\PerformanceOptimizer\ScriptManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceScriptManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $rules = ScriptRule::orderBy('priority')->orderByDesc('id')->get();

        return view('backend.performance_optimizer.index', [
            'tab'   => 'scripts',
            'rules' => $rules,
        ]);
    }

    public function store(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        // Master toggle
        if ($request->filled('perf_script_manager_status_only') && (int) $request->perf_script_manager_status_only === 1) {
            $setting = BusinessSetting::firstOrNew(['type' => 'perf_script_manager_status']);
            $setting->value = $request->boolean('perf_script_manager_status') ? '1' : '0';
            $setting->save();
            Cache::forget('business_settings');
            flash(translate('Script Manager master switch saved.'))->success();
            return back();
        }

        // Seed defaults
        if ($request->filled('seed_defaults')) {
            $created = 0;
            foreach (ScriptManagerService::defaultRules() as $d) {
                $exists = ScriptRule::where('script_pattern', $d['script_pattern'])
                    ->where('route_pattern',  $d['route_pattern'])
                    ->where('action',         $d['action'])
                    ->exists();
                if ($exists) continue;
                ScriptRule::create($d);
                $created++;
            }
            app(ScriptManagerService::class)->flushCache();
            flash(translate('Seeded') . " {$created} " . translate('default rules.'))->success();
            return back();
        }

        $data = $this->validated($request);
        ScriptRule::create($data);
        app(ScriptManagerService::class)->flushCache();
        flash(translate('Script rule added.'))->success();
        return back();
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->demoBlock()) return $r;

        $rule = ScriptRule::findOrFail($id);
        $rule->fill($this->validated($request))->save();
        app(ScriptManagerService::class)->flushCache();
        flash(translate('Script rule updated.'))->success();
        return back();
    }

    public function destroy($id)
    {
        if ($r = $this->demoBlock()) return $r;

        $rule = ScriptRule::findOrFail($id);
        $rule->delete();
        app(ScriptManagerService::class)->flushCache();
        flash(translate('Script rule deleted.'))->success();
        return back();
    }

    public function toggle($id)
    {
        if ($r = $this->demoBlock()) return $r;

        $rule = ScriptRule::findOrFail($id);
        $rule->enabled = !$rule->enabled;
        $rule->save();
        app(ScriptManagerService::class)->flushCache();
        flash(translate('Script rule') . ' ' . translate($rule->enabled ? 'enabled' : 'disabled') . '.')->success();
        return back();
    }

    // ── helpers ──────────────────────────────────────────────────────

    protected function validated(Request $request): array
    {
        $request->validate([
            'script_pattern' => 'required|string|max:255',
            'route_pattern'  => 'nullable|string|max:255',
            'device_filter'  => 'nullable|in:any,mobile,desktop,tablet',
            'action'         => 'required|in:allow,deny,defer,async,delay',
            'priority'       => 'nullable|integer|min:0|max:9999',
            'note'           => 'nullable|string|max:255',
        ]);

        return [
            'script_pattern' => trim((string) $request->input('script_pattern')),
            'route_pattern'  => trim((string) $request->input('route_pattern', '*')) ?: '*',
            'device_filter'  => $request->input('device_filter', 'any'),
            'action'         => $request->input('action', 'allow'),
            'priority'       => (int) $request->input('priority', 50),
            'enabled'        => $request->boolean('enabled', true) ? 1 : 0,
            'note'           => $request->input('note'),
        ];
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
