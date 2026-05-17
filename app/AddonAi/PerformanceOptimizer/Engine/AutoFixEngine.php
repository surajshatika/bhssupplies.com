<?php

namespace App\AddonAi\PerformanceOptimizer\Engine;

use App\Models\BusinessSetting;
use App\Models\PerformanceOptimizer\AiRecommendation;
use App\Models\PerformanceOptimizer\AutoFixHistory;
use App\Services\PerformanceOptimizer\CssJsMinifierService;
use App\Services\PerformanceOptimizer\DatabaseCleanerService;
use App\Services\PerformanceOptimizer\ImageOptimizerService;
use App\Services\PerformanceOptimizer\PageCacheService;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class AutoFixEngine
{
    public function apply(AiRecommendation $rec, ?int $userId = null): array
    {
        if (!$rec->auto_fixable || !$rec->auto_fix_action) {
            return ['ok' => false, 'error' => 'Not auto-fixable'];
        }

        $before = $this->snapshotBefore($rec);

        try {
            $result = $this->executeAction($rec->auto_fix_action, $rec->auto_fix_payload ?? []);
            $after  = ['result' => $result];

            AutoFixHistory::create([
                'recommendation_id' => $rec->id,
                'action'            => $rec->auto_fix_action,
                'before_state'      => $before,
                'after_state'       => $after,
                'applied_at'        => now(),
                'user_id'           => $userId,
            ]);

            $rec->update(['applied_at' => now()]);
            return ['ok' => true, 'result' => $result];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function rollback(AutoFixHistory $h): array
    {
        if ($h->rolled_back_at) {
            return ['ok' => false, 'error' => 'Already rolled back'];
        }

        try {
            switch ($h->action) {
                case 'setSettings':
                    foreach (($h->before_state['settings'] ?? []) as $k => $v) {
                        $s = BusinessSetting::firstOrNew(['type' => $k]);
                        $s->value = (string) $v;
                        $s->save();
                    }
                    Cache::forget('business_settings');
                    $h->update(['rolled_back_at' => now()]);
                    // Also unmark applied_at on the recommendation so it can be re-evaluated
                    if ($h->recommendation) {
                        $h->recommendation->update(['applied_at' => null]);
                    }
                    return ['ok' => true];

                case 'convertImagesWebp':
                    return ['ok' => false, 'error' => 'Image conversions cannot be rolled back here — use Images → Restore All Originals.'];

                case 'autoFixImageDimensions':
                    return ['ok' => false, 'error' => 'Dimension fixes write into images — no automated rollback. Re-scan to verify.'];

                case 'warmCache':
                    try { app(PageCacheService::class)->clearAll(); } catch (Throwable $e) {}
                    $h->update(['rolled_back_at' => now()]);
                    return ['ok' => true];

                case 'cleanDatabase':
                    return ['ok' => false, 'error' => 'Deleted rows cannot be restored from history. Restore from a database backup if needed.'];

                default:
                    return ['ok' => false, 'error' => 'No rollback handler for action: ' . $h->action];
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // ── internals ────────────────────────────────────────────────────

    protected function executeAction(string $action, array $payload): array
    {
        return match ($action) {
            'convertImagesWebp'      => app(ImageOptimizerService::class)->convertAllToWebp(null, (int) ($payload['limit'] ?? 100)),
            'autoFixImageDimensions' => app(CssJsMinifierService::class)->autoFixDimensions(),
            'warmCache'              => app(PageCacheService::class)->warmFromSitemap((int) ($payload['max'] ?? 100)),
            'cleanDatabase'          => app(DatabaseCleanerService::class)->clean($payload['items'] ?? []),
            'setSettings'            => $this->setSettings($payload['settings'] ?? []),
            default                  => throw new RuntimeException('Unknown auto-fix action: ' . $action),
        };
    }

    protected function setSettings(array $settings): array
    {
        $changed = [];
        foreach ($settings as $k => $v) {
            $s = BusinessSetting::firstOrNew(['type' => $k]);
            $s->value = (string) $v;
            $s->save();
            $changed[$k] = (string) $v;
        }
        Cache::forget('business_settings');
        return ['settings_updated' => $changed];
    }

    /**
     * Capture the current value of settings touched by this recommendation
     * so rollback can restore them.
     */
    protected function snapshotBefore(AiRecommendation $rec): array
    {
        if ($rec->auto_fix_action === 'setSettings') {
            $keys = array_keys(($rec->auto_fix_payload['settings'] ?? []));
            $current = [];
            foreach ($keys as $k) {
                $current[$k] = (string) (BusinessSetting::where('type', $k)->value('value') ?? '');
            }
            return ['settings' => $current];
        }
        return [];
    }
}
