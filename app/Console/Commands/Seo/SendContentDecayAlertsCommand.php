<?php

namespace App\Console\Commands\Seo;

use Illuminate\Console\Command;
use App\Models\SeoAnalytic;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendContentDecayAlertsCommand extends Command
{
    protected $signature = 'seo:content-decay-alerts {--email= : Email address to send the alert to (defaults to system settings)}';
    protected $description = 'Scan for content decay (pages losing >15% traffic) and send an email alert.';

    public function handle()
    {
        $this->info('Starting Content Decay Analysis...');

        if (!Schema::hasTable('seo_analytics')) {
            $this->error('seo_analytics table is missing. Cannot run decay analysis.');
            return self::FAILURE;
        }

        $allRows = SeoAnalytic::query()
            ->where('source', 'gsc')
            ->where('dimension', 'query_page')
            ->where('date', '>=', now()->subDays(56)->toDateString())
            ->get();

        $pagesTraffic = [];

        foreach ($allRows as $row) {
            $pair = json_decode((string) $row->value, true);
            if (empty($pair['query']) || empty($pair['page'])) continue;
            
            $date = Carbon::parse($row->date);
            $isRecent = $date->gte(now()->subDays(28));
            
            $pageUrl = $pair['page'];
            $clicks = (int) $row->clicks;

            if (!isset($pagesTraffic[$pageUrl])) {
                $pagesTraffic[$pageUrl] = ['recent_clicks' => 0, 'past_clicks' => 0];
            }
            if ($isRecent) {
                $pagesTraffic[$pageUrl]['recent_clicks'] += $clicks;
            } else {
                $pagesTraffic[$pageUrl]['past_clicks'] += $clicks;
            }
        }

        $decayedPages = [];
        foreach ($pagesTraffic as $url => $traffic) {
            if ($traffic['past_clicks'] > 10 && $traffic['recent_clicks'] < $traffic['past_clicks']) {
                $drop = (($traffic['past_clicks'] - $traffic['recent_clicks']) / $traffic['past_clicks']) * 100;
                if ($drop >= 15) {
                    $decayedPages[] = [
                        'url' => $url,
                        'past_clicks' => $traffic['past_clicks'],
                        'recent_clicks' => $traffic['recent_clicks'],
                        'drop_percentage' => round($drop, 1)
                    ];
                }
            }
        }

        if (empty($decayedPages)) {
            $this->info('No decaying pages found. Great job!');
            return self::SUCCESS;
        }

        usort($decayedPages, fn($a, $b) => $b['drop_percentage'] <=> $a['drop_percentage']);

        $email = $this->option('email') ?: get_setting('system_email');
        if (!$email) {
            $this->warn('No email configured to send alerts to.');
            return self::FAILURE;
        }

        $this->info(count($decayedPages) . ' decaying pages found. Sending email to ' . $email);

        try {
            Mail::raw($this->buildEmailBody($decayedPages), function ($message) use ($email) {
                $message->to($email)
                        ->subject('SEO Alert: Content Decay Detected on ' . count($decayedPages) . ' Pages');
            });
            $this->info('Alert email sent successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function buildEmailBody(array $decayedPages): string
    {
        $body = "Hi there,\n\nOur AI SEO Suite detected that the following pages have lost more than 15% of their Google search traffic over the last 28 days compared to the previous 28 days.\n\nWe recommend refreshing the content or running the AI SEO Optimizer on them:\n\n";

        foreach (array_slice($decayedPages, 0, 20) as $page) {
            $body .= "- URL: {$page['url']}\n";
            $body .= "  Drop: {$page['drop_percentage']}% (from {$page['past_clicks']} to {$page['recent_clicks']} clicks)\n\n";
        }

        if (count($decayedPages) > 20) {
            $body .= "\n... and " . (count($decayedPages) - 20) . " more pages. Please check the AI SEO Suite Dashboard for the full list.";
        }

        $body .= "\n\nBest regards,\nYour AI SEO Suite";

        return $body;
    }
}
