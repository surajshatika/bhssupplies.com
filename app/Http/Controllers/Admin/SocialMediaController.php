<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PostToSocialMediaJob;
use App\Models\SocialAutomationSetting;
use App\Models\SocialCampaign;
use App\Models\SocialPostLog;
use App\Models\SocialQueuedPost;
use App\Services\SocialMedia\AI\SocialAiProviderManager;
use App\Services\SocialMedia\SocialAiContentGenerator;
use App\Services\SocialMedia\SocialMediaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialMediaController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function index(): View
    {
        $platforms     = config('social_media.platforms', []);
        $stats         = SocialPostLog::stats();
        $recentLogs    = SocialPostLog::latest()->limit(10)->get();
        $activeCampaigns = SocialCampaign::active()->count();
        $pendingQueue  = SocialQueuedPost::where('status', 'pending')->count();
        $aiProviders   = SocialAiProviderManager::availableProviders();
        $tones         = config('social_media.tones', []);

        $platformStatus = [];
        foreach ($platforms as $slug => $info) {
            $service = SocialMediaManager::make($slug);
            $platformStatus[$slug] = [
                'configured' => $service->isConfigured(),
                'enabled'    => (bool) SocialAutomationSetting::get("social_{$slug}_enabled", false),
                'label'      => $info['label'],
                'icon'       => $info['icon'],
                'region'     => $info['region'],
            ];
        }

        $globalEnabled = SocialAutomationSetting::get('social_global_autopost_enabled', false);

        return view('backend.social_media.index', compact(
            'platforms', 'stats', 'recentLogs', 'activeCampaigns',
            'pendingQueue', 'aiProviders', 'platformStatus', 'globalEnabled', 'tones'
        ));
    }

    // ─── Settings ────────────────────────────────────────────────────────────

    public function settings(): View
    {
        $platforms   = config('social_media.platforms', []);
        $aiProviders = SocialAiProviderManager::availableProviders();
        $settings    = [];

        $allKeys = [
            'social_global_autopost_enabled',
            'social_default_ai_provider',
            // Platform enables
            'social_twitter_enabled', 'social_facebook_enabled', 'social_instagram_enabled',
            'social_linkedin_enabled', 'social_youtube_enabled', 'social_telegram_enabled',
            'social_whatsapp_enabled', 'social_tiktok_enabled', 'social_pinterest_enabled',
            'social_reddit_enabled', 'social_snapchat_enabled', 'social_discord_enabled',
            // AI Keys
            'social_ai_openai_key', 'social_ai_openai_model',
            'social_ai_claude_key', 'social_ai_claude_model',
            'social_ai_grok_key', 'social_ai_grok_model',
            // Twitter
            'twitter_api_key', 'twitter_api_secret', 'twitter_access_token', 'twitter_access_token_secret',
            // Telegram
            'telegram_bot_token', 'telegram_chat_id',
            // Facebook
            'facebook_page_access_token', 'facebook_page_id',
            // YouTube
            'youtube_oauth_token', 'youtube_channel_id',
            // Instagram
            'instagram_graph_api_token', 'instagram_business_account_id',
            // LinkedIn
            'linkedin_access_token', 'linkedin_organization_urn',
            // WhatsApp
            'whatsapp_business_api_token', 'whatsapp_phone_number_id', 'whatsapp_channel_id',
            // TikTok
            'tiktok_access_token', 'tiktok_open_id',
            // Pinterest
            'pinterest_access_token', 'pinterest_board_id',
            // Reddit
            'reddit_access_token', 'reddit_subreddit',
            // Snapchat
            'snapchat_access_token', 'snapchat_ad_account_id',
            // Discord
            'discord_webhook_url',
        ];

        $settings = SocialAutomationSetting::getMultiple($allKeys);

        return view('backend.social_media.settings', compact('platforms', 'aiProviders', 'settings'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $boolKeys = [
            'social_global_autopost_enabled',
            'social_twitter_enabled', 'social_facebook_enabled', 'social_instagram_enabled',
            'social_linkedin_enabled', 'social_youtube_enabled', 'social_telegram_enabled',
            'social_whatsapp_enabled', 'social_tiktok_enabled', 'social_pinterest_enabled',
            'social_reddit_enabled', 'social_snapchat_enabled', 'social_discord_enabled',
        ];

        foreach ($request->except('_token') as $key => $value) {
            $type = in_array($key, $boolKeys) ? 'boolean' : 'secret';
            SocialAutomationSetting::set($key, $value ?? '', $type);
        }

        // Ensure unchecked booleans are saved as false
        foreach ($boolKeys as $key) {
            if (!$request->has($key)) {
                SocialAutomationSetting::set($key, '0', 'boolean');
            }
        }

        flash(translate('Social Media settings saved successfully.'))->success();
        return back();
    }

    // ─── Logs ─────────────────────────────────────────────────────────────────

    public function logs(Request $request): View
    {
        $query = SocialPostLog::query()
            ->when($request->platform, fn($q) => $q->where('platform', $request->platform))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();

        $logs     = $query->paginate(30)->withQueryString();
        $platforms = config('social_media.platforms', []);
        $stats    = SocialPostLog::stats();

        return view('backend.social_media.logs', compact('logs', 'platforms', 'stats'));
    }

    // ─── Campaigns ────────────────────────────────────────────────────────────

    public function campaigns(): View
    {
        $campaigns   = SocialCampaign::latest()->paginate(20);
        $platforms   = config('social_media.platforms', []);
        $aiProviders = SocialAiProviderManager::availableProviders();
        $tones       = config('social_media.tones', []);
        $types       = config('social_media.campaign_types', []);

        return view('backend.social_media.campaigns', compact('campaigns', 'platforms', 'aiProviders', 'tones', 'types'));
    }

    public function createCampaign(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|string',
            'ai_provider' => 'required|in:openai,claude,grok',
            'tone'        => 'required|string',
            'topic'       => 'required|string',
            'platforms'   => 'required|array|min:1',
        ]);

        $campaign = SocialCampaign::create([
            'name'           => $request->name,
            'type'           => $request->type,
            'status'         => 'draft',
            'ai_provider'    => $request->ai_provider,
            'tone'           => $request->tone,
            'topic'          => $request->topic,
            'keywords'       => $request->keywords,
            'target_audience'=> $request->target_audience,
            'platforms'      => $request->platforms,
            'auto_post'      => (bool) $request->auto_post,
            'schedule_type'  => $request->schedule_type ?? 'once',
            'cron_expression'=> $request->cron_expression,
            'scheduled_at'   => $request->scheduled_at,
            'created_by'     => auth()->id(),
        ]);

        flash(translate('Campaign created. Generate content to activate it.'))->success();
        return redirect()->route('admin.social.campaigns');
    }

    public function generateCampaignContent(Request $request, SocialCampaign $campaign): JsonResponse
    {
        $generator = new SocialAiContentGenerator();

        $content = $generator->generateMultiPlatform(
            $campaign->platforms ?? [],
            $campaign->topic,
            $campaign->tone,
            $campaign->ai_provider,
            [
                'keywords'        => $campaign->keywords,
                'target_audience' => $campaign->target_audience,
            ]
        );

        $campaign->update(['generated_content' => $content]);

        return response()->json([
            'success' => true,
            'content' => $content,
            'message' => translate('Content generated successfully.'),
        ]);
    }

    public function updateCampaignStatus(Request $request, SocialCampaign $campaign): JsonResponse
    {
        $request->validate(['status' => 'required|in:draft,active,paused,completed,archived']);
        $campaign->update(['status' => $request->status]);
        return response()->json(['success' => true, 'status' => $request->status]);
    }

    public function deleteCampaign(SocialCampaign $campaign): RedirectResponse
    {
        $campaign->delete();
        flash(translate('Campaign deleted.'))->success();
        return back();
    }

    // ─── Compose / Manual Post ────────────────────────────────────────────────

    public function compose(): View
    {
        $platforms   = config('social_media.platforms', []);
        $aiProviders = SocialAiProviderManager::availableProviders();
        $tones       = config('social_media.tones', []);
        $queuedPosts = SocialQueuedPost::with('campaign')
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('backend.social_media.compose', compact('platforms', 'aiProviders', 'tones', 'queuedPosts'));
    }

    public function sendPost(Request $request): JsonResponse
    {
        $request->validate([
            'platforms' => 'required|array|min:1',
            'content'   => 'required|string',
            'schedule'  => 'nullable|date',
        ]);

        $options = array_filter([
            'image_url'  => $request->image_url,
            'link'       => $request->link,
            'video_url'  => $request->video_url,
        ]);

        $scheduled = $request->schedule ? \Carbon\Carbon::parse($request->schedule) : null;

        foreach ($request->platforms as $platform) {
            if ($scheduled && $scheduled->isFuture()) {
                SocialQueuedPost::create([
                    'platform'     => $platform,
                    'content'      => $request->content,
                    'options'      => $options ?: null,
                    'status'       => 'pending',
                    'trigger'      => 'manual_scheduled',
                    'ai_provider'  => $request->ai_provider,
                    'scheduled_at' => $scheduled,
                ]);
            } else {
                PostToSocialMediaJob::dispatch(
                    $platform,
                    $request->content,
                    'manual',
                    $options,
                    null,
                    $request->ai_provider,
                );
            }
        }

        $msg = $scheduled ? translate('Post scheduled successfully.') : translate('Post dispatched to queue.');
        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function generateAiContent(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'required|string',
            'topic'    => 'required|string',
            'tone'     => 'nullable|string',
            'provider' => 'nullable|in:openai,claude,grok',
        ]);

        $generator = new SocialAiContentGenerator();
        $content   = $generator->generateForPlatform(
            $request->platform,
            $request->topic,
            $request->tone ?? 'professional',
            $request->provider ?? 'openai',
            [
                'keywords'        => $request->keywords,
                'target_audience' => $request->target_audience,
            ]
        );

        if (!$content) {
            return response()->json(['success' => false, 'message' => translate('AI content generation failed. Check your API key.')], 422);
        }

        return response()->json(['success' => true, 'content' => $content]);
    }

    public function generateHashtags(Request $request): JsonResponse
    {
        $request->validate(['topic' => 'required|string', 'provider' => 'nullable|string']);

        $generator = new SocialAiContentGenerator();
        $hashtags  = $generator->generateHashtags($request->topic, 10, $request->provider ?? 'openai');

        return response()->json(['success' => true, 'hashtags' => $hashtags]);
    }

    public function testPlatform(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'required|string',
            'content'  => 'required|string|max:5000',
        ]);

        PostToSocialMediaJob::dispatch(
            $request->platform,
            $request->content,
            'manual_test',
            [],
            null,
            null,
        );

        return response()->json([
            'success' => true,
            'message' => translate('Test post dispatched for :platform', ['platform' => $request->platform]),
        ]);
    }

    public function deleteQueuedPost(SocialQueuedPost $post): JsonResponse
    {
        $post->delete();
        return response()->json(['success' => true]);
    }
}
