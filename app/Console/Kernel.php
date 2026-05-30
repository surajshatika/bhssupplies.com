<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Social Media — process queued posts every 5 minutes
        $schedule->command('social:process-queue')
            ->everyFiveMinutes()
            ->withoutOverlapping(3)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/social-queue.log'));

        // Social Media — auto-post AI content every 4 hours (when enabled)
        $schedule->command('social:autopost --trigger=scheduled')
            ->cron('0 */4 * * *')
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/social-autopost.log'));

        // AI Blog — generate 3 blogs daily: morning, afternoon, evening
        $schedule->command('blog:ai-generate --count=1 --publish')
            ->dailyAt('08:00')
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/blog-ai-generate.log'));

        $schedule->command('blog:ai-generate --count=1 --publish')
            ->dailyAt('13:00')
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/blog-ai-generate.log'));

        $schedule->command('blog:ai-generate --count=1 --publish')
            ->dailyAt('18:00')
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/blog-ai-generate.log'));

        // SEO — daily sitemap regeneration at 02:00
        // SEO master automation - hourly pending SEO and interval-gated technical tasks.
        $schedule->command('seo:automation-run')
            ->hourly()
            ->withoutOverlapping(55)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-automation.log'));

        $schedule->command('seo:process-ai-batches --limit=10 --max-batches=3')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-ai-batches.log'));

        // SEO — daily score snapshot at 02:30 (records per-entity scores)
        // SEO — weekly broken-link sweep, Monday 03:30
        $schedule->command('seo:auto-optimize-pending')
            ->dailyAt('02:45')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-auto-optimize.log'));

        $schedule->command('seo:auto-offpage-campaign')
            ->dailyAt('03:10')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-auto-offpage.log'));

        $schedule->command('seo:check-broken-links --limit=400 --per-entity=10')
            ->weeklyOn(1, '03:30')
            ->withoutOverlapping(20)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-broken-links.log'));

        // SEO — daily GSC sync at 04:00
        $schedule->command('seo:sync-search-console --days=7')
            ->dailyAt('04:00')
            ->withoutOverlapping(15)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-gsc.log'));

        // SEO — keyword rank check every 6 hours (capped to 50 keywords/run)
        $schedule->command('seo:check-keyword-ranks --limit=50')
            ->cron('15 */6 * * *')
            ->withoutOverlapping(20)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-ranks.log'));

        // SEO — PageSpeed Insights audit, twice daily (06:00 + 18:00)
        $schedule->command('seo:pagespeed --strategy=mobile')
            ->twiceDaily(6, 18)
            ->withoutOverlapping(30)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/seo-pagespeed.log'));

        // SEO weekly snapshot (legacy URL-keyed history + robots regeneration)
        $schedule->command('seo:weekly-snapshot')
            ->weekly()
            ->withoutOverlapping(10)
            ->runInBackground();

        // Cache warm-up — runs daily at 3am to pre-heat after nightly cache expiry
        $schedule->command('cache:warm')
            ->dailyAt('03:00')
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/cache-warm.log'));

        // Amazon — sync inventory to all active listings hourly
        $schedule->command('amazon:sync-inventory')
            ->hourly()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/amazon-sync.log'));

        // Amazon — import new orders from Amazon Canada every 30 minutes
        $schedule->command('amazon:import-orders')
            ->everyThirtyMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/amazon-orders.log'));

        // Marketing Analytics — Google Reviews weekly sync (Monday 06:30)
        $schedule->command('marketing:sync-google-reviews')
            ->weeklyOn(1, '06:30')
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/google-reviews-sync.log'));

        // Marketing Analytics — aggregate yesterday's raw events into daily summary (01:15)
        $schedule->command('marketing:aggregate-daily --purge')
            ->dailyAt('01:15')
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/marketing-aggregate.log'));

        // Marketing Analytics — generate AI insights for yesterday (01:45 — after aggregation)
        $schedule->command('marketing:daily-ai-insights')
            ->dailyAt('01:45')
            ->withoutOverlapping(15)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/marketing-ai-insights.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
