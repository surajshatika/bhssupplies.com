<?php

namespace App\Jobs;

use App\Models\SocialPostLog;
use App\Services\SocialMedia\SocialMediaManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostToSocialMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;

    public function __construct(
        private string  $platform,
        private string  $content,
        private string  $trigger     = 'manual',
        private array   $options     = [],
        private ?string $campaignId  = null,
        private ?string $aiProvider  = null,
        private ?int    $queuedPostId = null,
    ) {
        $this->onQueue(config('social_media.queue', 'social'));
    }

    public function handle(): void
    {
        $service = SocialMediaManager::make($this->platform);

        if (!$service->isConfigured()) {
            $this->createLog('skipped', null, null, 'Not configured');
            $this->updateQueuedPost('failed', 'Not configured');
            return;
        }

        try {
            $result = $service->post($this->content, $this->options);

            $status = $result['success'] ? 'success' : 'failed';
            $this->createLog($status, $result['post_id'] ?? null, $result['post_url'] ?? null, $result['response'] ?? '');
            $this->updateQueuedPost($status, $result['response'] ?? '');

            if (!$result['success']) {
                Log::warning("SocialMedia [{$this->platform}] post failed: " . ($result['response'] ?? ''));
            }
        } catch (\Throwable $e) {
            Log::error("SocialMedia [{$this->platform}] exception: " . $e->getMessage());
            $this->createLog('failed', null, null, $e->getMessage());
            $this->updateQueuedPost('failed', $e->getMessage());
            throw $e;
        }
    }

    private function createLog(string $status, ?string $postId, ?string $postUrl, string $response): void
    {
        SocialPostLog::create([
            'platform'     => $this->platform,
            'trigger'      => $this->trigger,
            'campaign_id'  => $this->campaignId,
            'ai_provider'  => $this->aiProvider,
            'content_sent' => $this->content,
            'status'       => $status,
            'response'     => $response,
            'post_id'      => $postId,
            'post_url'     => $postUrl,
            'posted_at'    => now(),
        ]);
    }

    private function updateQueuedPost(string $status, string $error = ''): void
    {
        if (!$this->queuedPostId) return;

        $update = [
            'status'       => $status === 'success' ? 'sent' : 'failed',
            'processed_at' => now(),
        ];

        if ($status !== 'success') {
            $update['last_error'] = $error;
        }

        \App\Models\SocialQueuedPost::where('id', $this->queuedPostId)->update($update);
    }
}
