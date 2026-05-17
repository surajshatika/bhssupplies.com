<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSocialQueueJob;
use App\Models\SocialQueuedPost;
use Illuminate\Console\Command;

class ProcessSocialMediaQueue extends Command
{
    protected $signature = 'social:process-queue
                            {--limit=50 : Max posts to process per run}
                            {--dry-run : List pending posts without dispatching}';

    protected $description = 'Process pending social media posts from the queue';

    public function handle(): int
    {
        $limit  = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $pending = SocialQueuedPost::pending()->priority()->limit($limit)->get();

        if ($pending->isEmpty()) {
            $this->info('No pending posts in the queue.');
            return self::SUCCESS;
        }

        $this->info("Found {$pending->count()} pending post(s).");

        if ($dryRun) {
            foreach ($pending as $post) {
                $this->line("  [{$post->platform}] scheduled: {$post->scheduled_at} — " . mb_substr($post->content, 0, 80));
            }
            return self::SUCCESS;
        }

        ProcessSocialQueueJob::dispatch();
        $this->info('Queue processing job dispatched.');

        return self::SUCCESS;
    }
}
