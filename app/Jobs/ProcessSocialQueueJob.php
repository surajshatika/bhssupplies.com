<?php

namespace App\Jobs;

use App\Models\SocialQueuedPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSocialQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(config('social_media.queue', 'social'));
    }

    public function handle(): void
    {
        $posts = SocialQueuedPost::pending()->priority()->limit(50)->get();

        foreach ($posts as $post) {
            $post->update(['status' => 'processing', 'attempts' => $post->attempts + 1]);

            PostToSocialMediaJob::dispatch(
                $post->platform,
                $post->content,
                $post->trigger ?? 'scheduled',
                $post->options ?? [],
                $post->campaign_id ? (string) $post->campaign_id : null,
                $post->ai_provider,
                $post->id,
            );
        }
    }
}
