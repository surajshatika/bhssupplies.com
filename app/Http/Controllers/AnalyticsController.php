<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Services\Marketing\AnalyticsAiService;
use App\Services\Marketing\AttributionService;
use App\Services\Marketing\CampaignService;
use App\Services\Marketing\CohortService;
use App\Services\Marketing\EventStore;
use App\Services\Marketing\ExperimentService;
use App\Services\Marketing\FunnelService;
use App\Services\Marketing\GoogleReviewService;
use App\Services\Marketing\MarketingEventDispatcher;
use CoreComponentRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Str;
use DB;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use ZipArchive;

class AnalyticsController extends Controller
{
    /** Allowed quick-range buckets in days (custom range falls outside this list). */
    protected array $allowedDays = [1, 7, 30, 90, 180, 365];

    public function __construct()
    {
        // Staff Permission Check — protect every analytics endpoint, not just one.
        $this->middleware(['permission:analytics_tools_configuration'])
            ->only(['google_analytics_config', 'google_tag_manager', 'pixel_analytics', 'pixel_conversation_api']);
    }

    public function google_analytics_report(Request $request)
    {
        if (!get_setting('google_analytics')) {
            flash(translate("Google Analytics is not enabled."))->error();
            return back();
        }

        try {
            // Resolve period: custom date range OR quick-pick days
            $from = $request->date('from');
            $to   = $request->date('to');
            if ($from && $to && $from <= $to) {
                $period = Period::create($from, $to);
                $days   = $from->diffInDays($to) + 1;
                $rangeLabel = $from->format('M d') . ' – ' . $to->format('M d, Y');
            } else {
                $days = (int) $request->input('days', 7);
                if (!in_array($days, $this->allowedDays, true)) {
                    $days = 7;
                }
                $period = Period::days($days);
                $rangeLabel = 'Last ' . $days . ' days';
            }

            // 5-minute cache keyed by date range — avoids burning GA quota on refresh
            $cacheKey = 'analytics.ga4.report.' . md5($days . '|' . ($from?->toDateString()) . '|' . ($to?->toDateString()));
            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($period) {
                $totalStats = Analytics::fetchTotalVisitorsAndPageViews($period);
                return [
                    'totals'        => $totalStats->first() ?? [],
                    'visitorsTrend' => Analytics::fetchVisitorsAndPageViews($period, 30)->toArray(),
                    'topPages'      => Analytics::fetchMostVisitedPages($period, 50)->toArray(),
                    'topCountries'  => Analytics::fetchTopCountries($period, 12)->toArray(),
                    'topReferrers'  => Analytics::fetchTopReferrers($period, 12)->toArray(),
                    'topBrowsers'   => Analytics::fetchTopBrowsers($period, 10)->toArray(),
                ];
            });

            // REAL realtime via GA4 runRealtimeReport — not arithmetic guesses
            $realtime = $this->fetchRealtimeBlock();

            return view('backend.reports.google_analytics', [
                'topPages'      => $data['topPages'],
                'visitorsTrend' => $data['visitorsTrend'],
                'topCountries'  => $data['topCountries'],
                'topReferrers'  => $data['topReferrers'],
                'topBrowsers'   => $data['topBrowsers'],
                'totals'        => $data['totals'],
                'days'          => $days,
                'rangeLabel'    => $rangeLabel,
                'fromDate'      => $from?->toDateString(),
                'toDate'        => $to?->toDateString(),
                'realtimeUsers'     => $realtime['users_30min'],
                'realtimePerMinute' => $realtime['per_minute_avg'],
                'realtimeLast5Min'  => $realtime['users_5min'],
                'realtimeByCountry' => $realtime['by_country'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[AnalyticsController] GA4 report failed', [
                'message' => $e->getMessage(),
                'trace'   => Str::limit($e->getTraceAsString(), 800),
            ]);
            flash(translate('Google Analytics error: ') . $e->getMessage())->error();
            return back();
        }
    }

    /**
     * Fetch true realtime numbers via GA4 Realtime Reporting API.
     * Cached for 30 seconds to keep quota low while feeling "live".
     */
    protected function fetchRealtimeBlock(): array
    {
        return Cache::remember('analytics.ga4.realtime', now()->addSeconds(30), function () {
            $default = [
                'users_30min'    => 0,
                'users_5min'     => 0,
                'per_minute_avg' => 0,
                'by_country'     => [],
            ];

            try {
                $rt = Analytics::getRealtime(
                    Period::create(now()->subMinutes(30), now()),
                    ['activeUsers'],
                    ['country'],
                    10
                );

                $byCountry = $rt->map(fn ($row) => [
                    'country'      => $row['country'] ?? 'Unknown',
                    'active_users' => (int) ($row['activeUsers'] ?? 0),
                ])->values()->toArray();

                $total30 = array_sum(array_column($byCountry, 'active_users'));

                // Last-5-min users via 'minutesAgo' dimension
                $last5 = Analytics::getRealtime(
                    Period::create(now()->subMinutes(5), now()),
                    ['activeUsers']
                );
                $users5 = (int) ($last5->first()['activeUsers'] ?? 0);

                return [
                    'users_30min'    => $total30,
                    'users_5min'     => $users5,
                    'per_minute_avg' => $total30 > 0 ? max(1, (int) round($total30 / 30)) : 0,
                    'by_country'     => $byCountry,
                ];
            } catch (\Throwable $e) {
                Log::warning('[AnalyticsController] GA4 realtime failed', ['message' => $e->getMessage()]);
                return $default;
            }
        });
    }

    public function google_analytics_config(Request $request)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();
        return view('backend.setup_configurations.google_configuration.google_analytics');
    }

    public function google_tag_manager(Request $request)
    {
        return view('backend.setup_configurations.google_configuration.google_tag_manager');
    }
    public function pixel_analytics(Request $request)
    {
        return view('backend.setup_configurations.facebook_configuration.pixel_analytics');
    }

    public function pixel_conversation_api(Request $request)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();
        return view('backend.setup_configurations.facebook_configuration.pixel_capi');
    }

    /**
     * A/B Experiments — list + results page.
     */
    public function experiments_index(Request $request, ExperimentService $svc)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();

        $window = (int) $request->input('window', 30);
        $experiments = collect($svc->all())->map(function ($e) use ($svc, $window) {
            $e['results'] = $svc->results($e['id'], $window);
            return $e;
        })->all();

        return view('backend.marketing_analytics.experiments_index', compact('experiments', 'window'));
    }

    public function experiments_save(Request $request, ExperimentService $svc)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'key'    => 'required|string|max:60|regex:/^[a-z0-9_]+$/',
            'status' => 'required|in:running,paused,completed',
        ]);

        // Parse variants from arrays like variant_keys[]=A&variant_weights[]=50
        $variants = [];
        $keys     = (array) $request->input('variant_keys', []);
        $weights  = (array) $request->input('variant_weights', []);
        foreach ($keys as $i => $k) {
            if ($k === '' || $k === null) continue;
            $variants[] = ['key' => $k, 'weight' => (int) ($weights[$i] ?? 1)];
        }

        $svc->save([
            'id'         => $request->input('id'),
            'name'       => $request->input('name'),
            'key'        => $request->input('key'),
            'status'     => $request->input('status'),
            'goal_event' => $request->input('goal_event', 'Purchase'),
            'variants'   => $variants,
        ]);
        flash(translate('Experiment saved'))->success();
        return back();
    }

    public function experiments_delete(Request $request, ExperimentService $svc, string $id)
    {
        $svc->delete($id);
        flash(translate('Experiment deleted'))->success();
        return back();
    }

    /**
     * UTM Campaign Manager — list, create, performance, delete.
     */
    public function campaigns_index(Request $request, CampaignService $svc)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();

        $window    = (int) $request->input('window', 30);
        $campaigns = collect($svc->all())->map(function ($c) use ($svc, $window) {
            $c['performance'] = $svc->performance($c, $window);
            return $c;
        })->all();

        return view('backend.marketing_analytics.campaigns_index', compact('campaigns', 'window'));
    }

    public function campaigns_save(Request $request, CampaignService $svc)
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'destination_url' => 'required|url',
            'utm_source'      => 'nullable|string|max:60',
            'utm_medium'      => 'nullable|string|max:60',
            'utm_campaign'    => 'nullable|string|max:80',
        ]);
        $saved = $svc->save($request->only([
            'id','name','destination_url','utm_source','utm_medium','utm_campaign','utm_term','utm_content','ad_spend'
        ]));
        flash(translate('Campaign saved. Short URL: ') . url('/c/' . $saved['short_code']))->success();
        return back();
    }

    public function campaigns_delete(Request $request, CampaignService $svc, string $id)
    {
        $svc->delete($id);
        flash(translate('Campaign deleted'))->success();
        return back();
    }

    /**
     * Attribution + Funnels + Cohorts — single 3-tab dashboard.
     */
    public function attribution_dashboard(
        Request $request,
        AttributionService $attribution,
        FunnelService $funnels,
        CohortService $cohorts
    ) {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();

        $model        = $request->input('model', 'last_touch');
        $window       = (int) $request->input('window', 30);
        $funnelId     = $request->input('funnel', 'ecommerce_default');
        $cohortMonths = (int) $request->input('cohort_months', 6);

        $attr     = $attribution->compute($model, $window);
        $compare  = $attribution->compareModels($window);
        $funnel   = $funnels->compute($funnelId, $window);
        $cohort   = $cohorts->compute($cohortMonths);
        $available = collect($funnels->all())->map(fn ($f) => ['id' => $f['id'], 'name' => $f['name']])->all();

        return view('backend.marketing_analytics.attribution_dashboard', compact(
            'attr', 'compare', 'funnel', 'cohort',
            'model', 'window', 'funnelId', 'cohortMonths', 'available'
        ));
    }

    /**
     * AI-powered Marketing Insights dashboard. Reads first-party warehouse aggregates +
     * AI summary + anomaly detection. Includes NLP "ask the data" box.
     */
    public function insights_dashboard(Request $request, EventStore $store, AnalyticsAiService $ai)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();

        $aggregates = $store->loadRecentAggregates(30);
        $latest     = end($aggregates) ?: [];
        $summary    = $ai->dailySummary();
        $anomalies  = $ai->detectAnomalies();

        $trendDates    = array_keys($aggregates);
        $trendRevenue  = array_map(fn ($r) => round((float) ($r['revenue']  ?? 0), 2), $aggregates);
        $trendVisitors = array_map(fn ($r) => (int) ($r['unique_visitors'] ?? 0), $aggregates);
        $trendOrders   = array_map(fn ($r) => (int) ($r['purchases'] ?? 0), $aggregates);

        return view('backend.marketing_analytics.insights_dashboard', compact(
            'aggregates', 'latest', 'summary', 'anomalies',
            'trendDates', 'trendRevenue', 'trendVisitors', 'trendOrders'
        ));
    }

    /** AJAX endpoint: ask a natural-language question against the analytics warehouse. */
    public function insights_ask(Request $request, AnalyticsAiService $ai)
    {
        $q = trim((string) $request->input('question'));
        if ($q === '') {
            return response()->json(['success' => false, 'message' => 'Question is empty.'], 422);
        }

        $result = $ai->answerQuestion($q);
        return response()->json(['success' => true] + $result);
    }

    /** AJAX endpoint: regenerate the AI summary on demand. */
    public function insights_regenerate(Request $request, AnalyticsAiService $ai)
    {
        \Illuminate\Support\Facades\Cache::forget('marketing.ai.daily.' . \Carbon\Carbon::yesterday()->toDateString());
        $payload = $ai->dailySummary();
        return response()->json(['success' => true, 'payload' => $payload]);
    }

    /** AJAX endpoint: 7-day forecast. */
    public function insights_forecast(Request $request, AnalyticsAiService $ai)
    {
        return response()->json($ai->forecast((int) $request->input('horizon', 7)));
    }

    /**
     * Multi-channel Pixels & CAPI — single page lists all 7 channels with toggle + token fields.
     */
    public function pixels_capi_hub(Request $request, MarketingEventDispatcher $dispatcher)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();
        $channels = $dispatcher->allChannels();
        return view('backend.marketing_analytics.pixels_capi_hub', compact('channels'));
    }

    /**
     * Google Reviews — Configuration page (Place ID + API Key).
     */
    public function google_reviews_config(Request $request, GoogleReviewService $service)
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();

        $summary  = $service->summary();
        $isEnabled = $service->isEnabled();
        $lastSyncedAt = get_setting('google_reviews_last_synced_at');

        return view('backend.setup_configurations.google_configuration.google_reviews', compact(
            'summary',
            'isEnabled',
            'lastSyncedAt'
        ));
    }

    /**
     * Google Reviews — Public display dashboard for admin.
     */
    public function google_reviews_dashboard(Request $request, GoogleReviewService $service)
    {
        if (!$service->isEnabled()) {
            flash(translate('Please configure Google Reviews first (Place ID + API Key).'))->warning();
            return redirect()->route('google-reviews-config');
        }

        $summary = $service->summary();
        $lastSyncedAt = get_setting('google_reviews_last_synced_at');

        return view('backend.marketing_analytics.google_reviews', compact(
            'summary',
            'lastSyncedAt'
        ));
    }

    /**
     * Google Reviews — Manual on-demand sync (AJAX or full-page).
     */
    public function google_reviews_sync_now(Request $request, GoogleReviewService $service)
    {
        if (!$service->isEnabled()) {
            $msg = translate('Google Reviews is disabled or missing API key / Place ID.');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            flash($msg)->error();
            return back();
        }

        $result = $service->syncAndStore();

        if ($request->ajax() || $request->wantsJson()) {
            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => translate('Synced successfully.'),
                    'count'   => $result['count'] ?? 0,
                    'summary' => $service->summary(),
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? translate('Unknown error.'),
            ], 422);
        }

        if ($result['success'] ?? false) {
            flash(translate('Google Reviews synced successfully.').' ('.($result['count'] ?? 0).' '.translate('reviews').')')->success();
        } else {
            flash(translate('Sync failed: ').($result['error'] ?? 'unknown'))->error();
        }
        return back();
    }
}
