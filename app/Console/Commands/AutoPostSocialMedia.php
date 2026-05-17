<?php

namespace App\Console\Commands;

use App\Jobs\PostToSocialMediaJob;
use App\Models\SocialAutomationSetting;
use App\Models\SocialCampaign;
use App\Services\SocialMedia\SocialMediaManager;
use App\Services\SocialMedia\SocialAiContentGenerator;
use Illuminate\Console\Command;

class AutoPostSocialMedia extends Command
{
    protected $signature = 'social:autopost
                            {--trigger=scheduled : Trigger label stored in logs}
                            {--content= : Content to post (skip AI generation)}
                            {--topic= : Topic for AI content generation}
                            {--provider=openai : AI provider (openai|claude|grok)}
                            {--tone=professional : Content tone}
                            {--platforms= : Comma-separated platform list (default: all enabled)}
                            {--dry-run : Show what would be posted without actually posting}';

    protected $description = 'Auto-post AI-generated content to configured social media channels';

    public function handle(): int
    {
        $globalEnabled = SocialAutomationSetting::get('social_global_autopost_enabled', false);

        if (!$globalEnabled) {
            $this->warn('Global auto-post is disabled. Enable it from Admin → Social Media → Settings.');
            return self::SUCCESS;
        }

        $trigger  = $this->option('trigger');
        $content  = $this->option('content');
        $topic    = $this->option('topic');
        $provider = $this->option('provider');
        $tone     = $this->option('tone');
        $dryRun   = $this->option('dry-run');

        // Resolve platforms
        $platformsOption = $this->option('platforms');
        $platforms = $platformsOption
            ? explode(',', $platformsOption)
            : SocialMediaManager::platforms();

        // Filter to enabled ones
        $platforms = array_filter($platforms, function ($platform) {
            return (bool) SocialAutomationSetting::get("social_{$platform}_enabled", false);
        });

        if (empty($platforms)) {
            $this->warn('No platforms enabled. Enable platforms from Admin → Social Media → Settings.');
            return self::SUCCESS;
        }

        $this->info('Platforms: ' . implode(', ', $platforms));

        // Generate or use provided content
        if (!$content && $topic) {
            $this->info("Generating content via {$provider} for topic: {$topic}");
            $generator = new SocialAiContentGenerator();

            foreach ($platforms as $platform) {
                $generated = $generator->generateForPlatform($platform, $topic, $tone, $provider);

                if (!$generated) {
                    $this->error("  [{$platform}] AI generation failed — skipping");
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [{$platform}] DRY RUN: " . mb_substr($generated, 0, 120) . '...');
                    continue;
                }

                PostToSocialMediaJob::dispatch($platform, $generated, $trigger, [], null, $provider);
                $this->info("  [{$platform}] Dispatched job");
            }

            return self::SUCCESS;
        }

        if (!$content) {
            $this->error('Provide --content= or --topic= for AI generation.');
            return self::FAILURE;
        }

        foreach ($platforms as $platform) {
            if ($dryRun) {
                $this->line("  [{$platform}] DRY RUN: " . mb_substr($content, 0, 120));
                continue;
            }

            PostToSocialMediaJob::dispatch($platform, $content, $trigger);
            $this->info("  [{$platform}] Dispatched job");
        }

        return self::SUCCESS;
    }

    private function runActiveCampaigns(): void
    {
        $campaigns = SocialCampaign::dueToPost()->get();

        foreach ($campaigns as $campaign) {
            $platforms = $campaign->platforms ?? [];
            $content   = $campaign->generated_content ?? [];

            foreach ($platforms as $platform) {
                $platformContent = $content[$platform] ?? null;
                if (!$platformContent) continue;

                PostToSocialMediaJob::dispatch(
                    $platform,
                    $platformContent,
                    'campaign',
                    [],
                    (string) $campaign->id,
                    $campaign->ai_provider,
                );
            }

            $campaign->update([
                'post_count'     => $campaign->post_count + count($platforms),
                'last_posted_at' => now(),
            ]);
        }
    }
}
