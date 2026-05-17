<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\CacheRule;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PerformanceCacheRulesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $rules = CacheRule::orderBy('priority')->orderByDesc('id')->get();
        return view('backend.performance_optimizer.index', [
            'tab'   => 'cache_rules',
            'rules' => $rules,
        ]);
    }

    public function store(Request $request)
    {
        if ($r = $this->demoBlock()) return $r;

        if ($request->filled('seed_defaults')) {
            $created = 0;
            foreach (self::defaultRules() as $d) {
                $exists = CacheRule::where('pattern', $d['pattern'])
                    ->where('action', $d['action'])
                    ->exists();
                if ($exists) continue;
                CacheRule::create($d);
                $created++;
            }
            app(PageCacheService::class)->flushRuleCache();
            flash(translate('Seeded') . " {$created} " . translate('default cache rules.'))->success();
            return back();
        }

        CacheRule::create($this->validated($request));
        app(PageCacheService::class)->flushRuleCache();
        flash(translate('Cache rule added.'))->success();
        return back();
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->demoBlock()) return $r;

        $rule = CacheRule::findOrFail($id);
        $rule->fill($this->validated($request))->save();
        app(PageCacheService::class)->flushRuleCache();
        flash(translate('Cache rule updated.'))->success();
        return back();
    }

    public function destroy($id)
    {
        if ($r = $this->demoBlock()) return $r;

        CacheRule::findOrFail($id)->delete();
        app(PageCacheService::class)->flushRuleCache();
        flash(translate('Cache rule deleted.'))->success();
        return back();
    }

    public function toggle($id)
    {
        if ($r = $this->demoBlock()) return $r;

        $rule = CacheRule::findOrFail($id);
        $rule->enabled = !$rule->enabled;
        $rule->save();
        app(PageCacheService::class)->flushRuleCache();
        flash(translate('Cache rule') . ' ' . translate($rule->enabled ? 'enabled' : 'disabled') . '.')->success();
        return back();
    }

    // ── helpers ──────────────────────────────────────────────────────

    protected function validated(Request $request): array
    {
        $request->validate([
            'pattern'     => 'required|string|max:500',
            'action'      => 'required|in:cache,bypass,vary_device,vary_locale',
            'ttl_minutes' => 'nullable|integer|min:0|max:43200',
            'priority'    => 'nullable|integer|min:0|max:9999',
            'note'        => 'nullable|string|max:255',
        ]);

        return [
            'pattern'     => trim((string) $request->input('pattern')),
            'action'      => $request->input('action', 'cache'),
            'ttl_minutes' => $request->input('ttl_minutes') === null || $request->input('ttl_minutes') === '' ? null : (int) $request->input('ttl_minutes'),
            'priority'    => (int) $request->input('priority', 50),
            'enabled'     => $request->boolean('enabled', true) ? 1 : 0,
            'note'        => $request->input('note'),
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

    public static function defaultRules(): array
    {
        return [
            ['pattern' => 'admin/*',      'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 1,   'enabled' => 1, 'note' => 'Never cache admin'],
            ['pattern' => 'cart',         'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 5,   'enabled' => 1, 'note' => 'Cart page is per-user'],
            ['pattern' => 'cart/*',       'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 5,   'enabled' => 1, 'note' => null],
            ['pattern' => 'checkout/*',   'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 5,   'enabled' => 1, 'note' => null],
            ['pattern' => 'my-account/*', 'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 5,   'enabled' => 1, 'note' => null],
            ['pattern' => 'user/*',       'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 5,   'enabled' => 1, 'note' => null],
            ['pattern' => 'seller/*',     'action' => 'bypass', 'ttl_minutes' => null, 'priority' => 5,   'enabled' => 1, 'note' => null],
            ['pattern' => '/',            'action' => 'cache',  'ttl_minutes' => 1440, 'priority' => 100, 'enabled' => 1, 'note' => 'Homepage — 24h'],
            ['pattern' => 'product/*',    'action' => 'cache',  'ttl_minutes' => 720,  'priority' => 100, 'enabled' => 1, 'note' => 'Products — 12h'],
            ['pattern' => 'category/*',   'action' => 'cache',  'ttl_minutes' => 720,  'priority' => 100, 'enabled' => 1, 'note' => 'Categories — 12h'],
            ['pattern' => 'shop/*',       'action' => 'cache',  'ttl_minutes' => 720,  'priority' => 100, 'enabled' => 1, 'note' => 'Shop pages — 12h'],
        ];
    }
}
