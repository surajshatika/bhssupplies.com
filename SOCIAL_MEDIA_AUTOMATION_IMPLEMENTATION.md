# Social Media Automation (AI Agent) — Laravel Implementation Guide

Full implementation guide for the Social Media Automation admin panel feature shown in StockAI.
Covers: Twitter/X, Telegram, Facebook, YouTube, Instagram, LinkedIn, WhatsApp.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Database Migrations](#2-database-migrations)
3. [Models](#3-models)
4. [Services (Per Platform)](#4-services)
5. [Queue Jobs](#5-queue-jobs)
6. [Console Commands & Scheduler](#6-console-commands--scheduler)
7. [Admin Controller](#7-admin-controller)
8. [Routes](#8-routes)
9. [Environment & Config](#9-environment--config)
10. [Frontend Admin Panel (Blade/Vue)](#10-frontend-admin-panel)
11. [Testing Each Platform](#11-testing-each-platform)

---

## 1. Architecture Overview

```
Admin Panel
    ↓  saves API credentials  →  site_settings table (key/value)
    ↓  toggles auto-post      →  site_settings (booleans)

Scheduler (every 3-4 hrs or on news fetch)
    ↓  fires Console Command  →  AutoPostSocialMedia
    ↓  reads enabled channels →  site_settings
    ↓  dispatches per channel →  PostToSocialMediaJob (queued)

PostToSocialMediaJob
    ↓  resolves platform service (Twitter/Telegram/Facebook/...)
    ↓  formats & posts content
    ↓  logs result            →  social_post_logs table
```

**Key decisions:**
- Credentials stored in `site_settings` (key-value), NOT `.env`, so admin can change them at runtime.
- One queued job per platform dispatch (isolated failure).
- Posting is opt-in per platform via boolean settings.

---

## 2. Database Migrations

### 2a. `site_settings` table (if not already present)

```php
// database/migrations/xxxx_create_site_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->enum('type', ['string', 'boolean', 'json', 'integer'])->default('string');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
```

### 2b. `social_post_logs` table (audit trail)

```php
// database/migrations/xxxx_create_social_post_logs_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_post_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform');            // twitter, telegram, facebook, etc.
            $table->string('trigger');             // manual, auto_news, auto_picks
            $table->longText('content_sent');
            $table->string('status');              // success, failed, skipped
            $table->text('response')->nullable();  // raw API response or error
            $table->string('post_id')->nullable(); // platform post/tweet/message ID
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_logs');
    }
};
```

Run: `php artisan migrate`

---

## 3. Models

### SiteSetting Model

```php
// app/Models/SiteSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;
        static::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type]);
    }
}
```

### SocialPostLog Model

```php
// app/Models/SocialPostLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPostLog extends Model
{
    protected $fillable = [
        'platform', 'trigger', 'content_sent',
        'status', 'response', 'post_id', 'posted_at',
    ];

    protected $casts = ['posted_at' => 'datetime'];
}
```

---

## 4. Services

Create a base contract then one service per platform.

### 4a. Base Interface

```php
// app/Services/SocialMedia/SocialMediaInterface.php
namespace App\Services\SocialMedia;

interface SocialMediaInterface
{
    /** Returns ['success' => bool, 'post_id' => string|null, 'response' => string] */
    public function post(string $content, array $options = []): array;
    public function isConfigured(): bool;
}
```

### 4b. Twitter / X Service

Requires `composer require abraham/twitteroauth` or use the v2 API directly via Guzzle.

```php
// app/Services/SocialMedia/TwitterService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class TwitterService implements SocialMediaInterface
{
    private string $bearerToken;

    public function __construct()
    {
        $this->bearerToken = (string) SiteSetting::get('twitter_bearer_token', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->bearerToken);
    }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        // Twitter v2 needs OAuth 1.0a user context for posting (Bearer is read-only).
        // Store api_key + api_secret + access_token + access_token_secret.
        $apiKey            = SiteSetting::get('twitter_api_key');
        $apiSecret         = SiteSetting::get('twitter_api_secret');
        $accessToken       = SiteSetting::get('twitter_access_token');
        $accessTokenSecret = SiteSetting::get('twitter_access_token_secret');

        $url    = 'https://api.twitter.com/2/tweets';
        $body   = json_encode(['text' => mb_substr($content, 0, 280)]);
        $oauth  = $this->buildOAuthHeader('POST', $url, $apiKey, $apiSecret, $accessToken, $accessTokenSecret);

        $response = Http::withHeaders([
            'Authorization' => $oauth,
            'Content-Type'  => 'application/json',
        ])->post($url, ['text' => mb_substr($content, 0, 280)]);

        $data = $response->json();

        if ($response->successful() && isset($data['data']['id'])) {
            return ['success' => true, 'post_id' => $data['data']['id'], 'response' => json_encode($data)];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($data)];
    }

    private function buildOAuthHeader(string $method, string $url, string $apiKey, string $apiSecret, string $accessToken, string $accessSecret): string
    {
        $nonce     = bin2hex(random_bytes(16));
        $timestamp = time();
        $params    = [
            'oauth_consumer_key'     => $apiKey,
            'oauth_nonce'            => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => $timestamp,
            'oauth_token'            => $accessToken,
            'oauth_version'          => '1.0',
        ];
        ksort($params);
        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($params));
        $signingKey = rawurlencode($apiSecret) . '&' . rawurlencode($accessSecret);
        $signature  = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        $params['oauth_signature'] = $signature;

        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = rawurlencode($k) . '="' . rawurlencode($v) . '"';
        }

        return 'OAuth ' . implode(', ', $parts);
    }
}
```

### 4c. Telegram Service

```php
// app/Services/SocialMedia/TelegramService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class TelegramService implements SocialMediaInterface
{
    private string $botToken;
    private string $chatId;

    public function __construct()
    {
        $this->botToken = (string) SiteSetting::get('telegram_bot_token', '');
        $this->chatId   = (string) SiteSetting::get('telegram_chat_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id'    => $this->chatId,
            'text'       => $content,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['ok'] ?? false)) {
            return [
                'success' => true,
                'post_id' => (string) ($data['result']['message_id'] ?? ''),
                'response' => json_encode($data),
            ];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($data)];
    }
}
```

### 4d. Facebook Page Service

```php
// app/Services/SocialMedia/FacebookService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class FacebookService implements SocialMediaInterface
{
    private string $pageAccessToken;
    private string $pageId;

    public function __construct()
    {
        $this->pageAccessToken = (string) SiteSetting::get('facebook_page_access_token', '');
        $this->pageId          = (string) SiteSetting::get('facebook_page_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->pageAccessToken) && !empty($this->pageId);
    }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        $response = Http::post(
            "https://graph.facebook.com/v19.0/{$this->pageId}/feed",
            [
                'message'      => $content,
                'access_token' => $this->pageAccessToken,
                'link'         => $options['link'] ?? null,
            ]
        );

        $data = $response->json();

        if ($response->successful() && isset($data['id'])) {
            return ['success' => true, 'post_id' => $data['id'], 'response' => json_encode($data)];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($data)];
    }
}
```

### 4e. YouTube Community Post Service

```php
// app/Services/SocialMedia/YouTubeService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class YouTubeService implements SocialMediaInterface
{
    private string $oauthToken;
    private string $channelId;

    public function __construct()
    {
        $this->oauthToken = (string) SiteSetting::get('youtube_oauth_token', '');
        $this->channelId  = (string) SiteSetting::get('youtube_channel_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->oauthToken) && !empty($this->channelId);
    }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        // YouTube Data API v3: Community posts via Activities endpoint
        $response = Http::withToken($this->oauthToken)
            ->post('https://www.googleapis.com/youtube/v3/activities?part=snippet,contentDetails', [
                'snippet' => [
                    'description' => $content,
                ],
                'contentDetails' => [
                    'bulletin' => ['resourceId' => []],
                ],
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['id'])) {
            return ['success' => true, 'post_id' => $data['id'], 'response' => json_encode($data)];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($data)];
    }
}
```

### 4f. Instagram (Business) Service

```php
// app/Services/SocialMedia/InstagramService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class InstagramService implements SocialMediaInterface
{
    private string $graphApiToken;
    private string $businessAccountId;

    public function __construct()
    {
        $this->graphApiToken      = (string) SiteSetting::get('instagram_graph_api_token', '');
        $this->businessAccountId  = (string) SiteSetting::get('instagram_business_account_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->graphApiToken) && !empty($this->businessAccountId);
    }

    // Instagram requires an image URL for feed posts. Text-only = caption on a container.
    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        $imageUrl = $options['image_url'] ?? null;
        if (!$imageUrl) {
            return ['success' => false, 'post_id' => null, 'response' => 'Instagram requires image_url'];
        }

        // Step 1: Create container
        $container = Http::post(
            "https://graph.facebook.com/v19.0/{$this->businessAccountId}/media",
            [
                'image_url'    => $imageUrl,
                'caption'      => $content,
                'access_token' => $this->graphApiToken,
            ]
        )->json();

        if (!isset($container['id'])) {
            return ['success' => false, 'post_id' => null, 'response' => json_encode($container)];
        }

        // Step 2: Publish container
        $publish = Http::post(
            "https://graph.facebook.com/v19.0/{$this->businessAccountId}/media_publish",
            [
                'creation_id'  => $container['id'],
                'access_token' => $this->graphApiToken,
            ]
        )->json();

        if (isset($publish['id'])) {
            return ['success' => true, 'post_id' => $publish['id'], 'response' => json_encode($publish)];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($publish)];
    }
}
```

### 4g. LinkedIn Service

```php
// app/Services/SocialMedia/LinkedInService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class LinkedInService implements SocialMediaInterface
{
    private string $accessToken;
    private string $organizationUrn; // urn:li:organization:XXXXXXX  OR  urn:li:person:XXXXXXX

    public function __construct()
    {
        $this->accessToken     = (string) SiteSetting::get('linkedin_access_token', '');
        $this->organizationUrn = (string) SiteSetting::get('linkedin_organization_urn', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->organizationUrn);
    }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        $body = [
            'author'         => $this->organizationUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $content],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::withToken($this->accessToken)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->post('https://api.linkedin.com/v2/ugcPosts', $body);

        $data = $response->json();

        if ($response->successful()) {
            $postId = $response->header('x-restli-id') ?? ($data['id'] ?? null);
            return ['success' => true, 'post_id' => $postId, 'response' => json_encode($data)];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($data)];
    }
}
```

### 4h. WhatsApp Business Service

```php
// app/Services/SocialMedia/WhatsAppService.php
namespace App\Services\SocialMedia;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;

class WhatsAppService implements SocialMediaInterface
{
    private string $businessApiToken;
    private string $phoneNumberId;

    public function __construct()
    {
        $this->businessApiToken = (string) SiteSetting::get('whatsapp_business_api_token', '');
        $this->phoneNumberId    = (string) SiteSetting::get('whatsapp_phone_number_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->businessApiToken) && !empty($this->phoneNumberId);
    }

    // WhatsApp Business API broadcasts to opted-in subscribers via channel updates.
    // For broadcast/channel messages use the channel ID instead of a recipient number.
    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'post_id' => null, 'response' => 'Not configured'];
        }

        $to = $options['to'] ?? SiteSetting::get('whatsapp_channel_id', '');

        $response = Http::withToken($this->businessApiToken)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $content],
            ]);

        $data = $response->json();

        if ($response->successful() && isset($data['messages'][0]['id'])) {
            return ['success' => true, 'post_id' => $data['messages'][0]['id'], 'response' => json_encode($data)];
        }

        return ['success' => false, 'post_id' => null, 'response' => json_encode($data)];
    }
}
```

### 4i. Service Registry (Factory)

```php
// app/Services/SocialMedia/SocialMediaManager.php
namespace App\Services\SocialMedia;

class SocialMediaManager
{
    private static array $map = [
        'twitter'   => TwitterService::class,
        'telegram'  => TelegramService::class,
        'facebook'  => FacebookService::class,
        'youtube'   => YouTubeService::class,
        'instagram' => InstagramService::class,
        'linkedin'  => LinkedInService::class,
        'whatsapp'  => WhatsAppService::class,
    ];

    public static function make(string $platform): SocialMediaInterface
    {
        $class = self::$map[$platform] ?? null;
        if (!$class) throw new \InvalidArgumentException("Unknown platform: {$platform}");
        return new $class();
    }

    public static function platforms(): array
    {
        return array_keys(self::$map);
    }
}
```

---

## 5. Queue Jobs

```php
// app/Jobs/PostToSocialMediaJob.php
namespace App\Jobs;

use App\Models\SiteSetting;
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
    public int $backoff = 60; // seconds between retries

    public function __construct(
        private string $platform,
        private string $content,
        private string $trigger = 'auto',
        private array  $options = []
    ) {}

    public function handle(): void
    {
        $service = SocialMediaManager::make($this->platform);

        if (!$service->isConfigured()) {
            SocialPostLog::create([
                'platform'     => $this->platform,
                'trigger'      => $this->trigger,
                'content_sent' => $this->content,
                'status'       => 'skipped',
                'response'     => 'Not configured',
                'posted_at'    => now(),
            ]);
            return;
        }

        try {
            $result = $service->post($this->content, $this->options);

            SocialPostLog::create([
                'platform'     => $this->platform,
                'trigger'      => $this->trigger,
                'content_sent' => $this->content,
                'status'       => $result['success'] ? 'success' : 'failed',
                'response'     => $result['response'],
                'post_id'      => $result['post_id'],
                'posted_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("SocialMedia [{$this->platform}] error: " . $e->getMessage());

            SocialPostLog::create([
                'platform'     => $this->platform,
                'trigger'      => $this->trigger,
                'content_sent' => $this->content,
                'status'       => 'failed',
                'response'     => $e->getMessage(),
                'posted_at'    => now(),
            ]);

            throw $e; // allow retry
        }
    }
}
```

---

## 6. Console Commands & Scheduler

### 6a. Command

```php
// app/Console/Commands/AutoPostSocialMedia.php
namespace App\Console\Commands;

use App\Jobs\PostToSocialMediaJob;
use App\Models\SiteSetting;
use App\Services\SocialMedia\SocialMediaManager;
use Illuminate\Console\Command;

class AutoPostSocialMedia extends Command
{
    protected $signature   = 'social:autopost {--trigger=scheduled} {--content=} {--type=picks}';
    protected $description = 'Auto-post AI stock picks or market news to social channels';

    public function handle(): int
    {
        $trigger = $this->option('trigger');
        $type    = $this->option('type'); // 'picks' or 'news'
        $content = $this->option('content');

        // If no content passed, generate or fetch from DB
        if (!$content) {
            $content = $this->generateContent($type);
        }

        if (!$content) {
            $this->error('No content to post.');
            return self::FAILURE;
        }

        // Check global toggle
        $globalKey = $type === 'picks' ? 'social_autopost_picks_enabled' : 'social_autopost_news_enabled';
        if (!SiteSetting::get($globalKey, false)) {
            $this->info("Auto-post [{$type}] is disabled. Skipping.");
            return self::SUCCESS;
        }

        foreach (SocialMediaManager::platforms() as $platform) {
            $enabledKey = "social_{$platform}_enabled";
            if (!SiteSetting::get($enabledKey, false)) {
                $this->line("  Skipping {$platform} (disabled)");
                continue;
            }

            PostToSocialMediaJob::dispatch($platform, $content, $trigger);
            $this->info("  Dispatched job for {$platform}");
        }

        return self::SUCCESS;
    }

    private function generateContent(string $type): ?string
    {
        // Hook into your existing AI news / signals generation logic here.
        // Example: pull latest from ai_news table or generate via your AI service.
        // Return a formatted string.
        return null;
    }
}
```

### 6b. Scheduler Registration

```php
// app/Console/Kernel.php  (inside schedule() method)

// Auto-post AI stock picks every 3-4 hours (when enabled)
$schedule->command('social:autopost --type=picks --trigger=scheduled')
    ->cron('0 */4 * * *')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping(5)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/social-autopost.log'))
    ->name('social-autopost-picks');

// Auto-post market news (trigger after news fetch)
// Call this from your news-fetch command: Artisan::call('social:autopost --type=news --trigger=after_news_fetch')
```

**Calling it from another command after news fetch:**

```php
// Inside your existing AutoPostMarketNews command handle():
use Illuminate\Support\Facades\Artisan;

Artisan::call('social:autopost', [
    '--type'    => 'news',
    '--trigger' => 'auto_news',
    '--content' => $formattedNewsText, // your generated content
]);
```

---

## 7. Admin Controller

```php
// app/Http/Controllers/Admin/AdminSocialMediaController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\SocialPostLog;
use App\Jobs\PostToSocialMediaJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSocialMediaController extends Controller
{
    // GET /api/admin/social/settings
    public function getSettings(): JsonResponse
    {
        $keys = [
            // Toggles
            'social_autopost_news_enabled',
            'social_autopost_picks_enabled',
            // Per-platform toggles
            'social_twitter_enabled', 'social_telegram_enabled', 'social_facebook_enabled',
            'social_youtube_enabled', 'social_instagram_enabled', 'social_linkedin_enabled',
            'social_whatsapp_enabled',
            // Twitter/X
            'twitter_bearer_token', 'twitter_api_key', 'twitter_api_secret',
            'twitter_access_token', 'twitter_access_token_secret',
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
            'whatsapp_business_api_token', 'whatsapp_phone_number_id',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = SiteSetting::get($key, '');
        }

        return response()->json(['settings' => $settings]);
    }

    // POST /api/admin/social/settings
    public function saveSettings(Request $request): JsonResponse
    {
        $booleanKeys = [
            'social_autopost_news_enabled', 'social_autopost_picks_enabled',
            'social_twitter_enabled', 'social_telegram_enabled', 'social_facebook_enabled',
            'social_youtube_enabled', 'social_instagram_enabled', 'social_linkedin_enabled',
            'social_whatsapp_enabled',
        ];

        foreach ($request->all() as $key => $value) {
            $type = in_array($key, $booleanKeys) ? 'boolean' : 'string';
            SiteSetting::set($key, $value, $type);
        }

        return response()->json(['message' => 'Settings saved successfully.']);
    }

    // POST /api/admin/social/test-post  (manual trigger from admin panel)
    public function testPost(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'required|in:twitter,telegram,facebook,youtube,instagram,linkedin,whatsapp',
            'content'  => 'required|string|max:5000',
        ]);

        PostToSocialMediaJob::dispatch(
            $request->platform,
            $request->content,
            'manual_test'
        );

        return response()->json(['message' => "Post queued for {$request->platform}."]);
    }

    // GET /api/admin/social/logs
    public function getLogs(Request $request): JsonResponse
    {
        $logs = SocialPostLog::query()
            ->when($request->platform, fn($q) => $q->where('platform', $request->platform))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
```

---

## 8. Routes

```php
// routes/api.php  (inside your admin middleware group)

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Social Media Automation
    Route::prefix('social')->group(function () {
        Route::get('/settings',    [AdminSocialMediaController::class, 'getSettings']);
        Route::post('/settings',   [AdminSocialMediaController::class, 'saveSettings']);
        Route::post('/test-post',  [AdminSocialMediaController::class, 'testPost']);
        Route::get('/logs',        [AdminSocialMediaController::class, 'getLogs']);
    });

});
```

---

## 9. Environment & Config

### `.env` (optional — credentials stored in DB at runtime, but you can seed from env)

```dotenv
# Social Media (optional — admin panel overrides these at runtime)
TWITTER_BEARER_TOKEN=
TWITTER_API_KEY=
TWITTER_API_SECRET=
TWITTER_ACCESS_TOKEN=
TWITTER_ACCESS_TOKEN_SECRET=

TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

FACEBOOK_PAGE_ACCESS_TOKEN=
FACEBOOK_PAGE_ID=

YOUTUBE_OAUTH_TOKEN=
YOUTUBE_CHANNEL_ID=

INSTAGRAM_GRAPH_API_TOKEN=
INSTAGRAM_BUSINESS_ACCOUNT_ID=

LINKEDIN_ACCESS_TOKEN=
LINKEDIN_ORGANIZATION_URN=

WHATSAPP_BUSINESS_API_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
```

### `config/services.php` addition (optional fallback)

```php
'social_media' => [
    'twitter' => [
        'bearer_token'        => env('TWITTER_BEARER_TOKEN'),
        'api_key'             => env('TWITTER_API_KEY'),
        'api_secret'          => env('TWITTER_API_SECRET'),
        'access_token'        => env('TWITTER_ACCESS_TOKEN'),
        'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id'   => env('TELEGRAM_CHAT_ID'),
    ],
    'facebook' => [
        'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
        'page_id'           => env('FACEBOOK_PAGE_ID'),
    ],
    'youtube' => [
        'oauth_token' => env('YOUTUBE_OAUTH_TOKEN'),
        'channel_id'  => env('YOUTUBE_CHANNEL_ID'),
    ],
    'instagram' => [
        'graph_api_token'      => env('INSTAGRAM_GRAPH_API_TOKEN'),
        'business_account_id'  => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
    ],
    'linkedin' => [
        'access_token'    => env('LINKEDIN_ACCESS_TOKEN'),
        'organization_urn'=> env('LINKEDIN_ORGANIZATION_URN'),
    ],
    'whatsapp' => [
        'business_api_token' => env('WHATSAPP_BUSINESS_API_TOKEN'),
        'phone_number_id'    => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],
],
```

### Queue Worker (required for jobs)

```bash
# Start queue worker
php artisan queue:work --queue=default --tries=3 --backoff=60

# Or with Supervisor in production (recommended)
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
command=php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
```

---

## 10. Frontend Admin Panel

### Blade template approach (minimal, drop into existing admin)

```blade
{{-- resources/views/admin/social-media.blade.php --}}

<div class="card">
    <div class="card-header">
        <h5>Social Media Automation (AI Agent) <span class="badge bg-warning">BETA</span></h5>
        <p class="text-muted">Automate posting fetched news and AI stock suggestions directly to your social channels.</p>
    </div>
    <div class="card-body">

        {{-- Global Toggles --}}
        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
            <div>
                <strong>Auto-Post Market News &amp; Ideas (When fetched)</strong>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="autopost_news"
                    {{ $settings['social_autopost_news_enabled'] ? 'checked' : '' }}>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
            <div>
                <strong>Auto-Post AI Stock Picks (Every 3-4 hours)</strong>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="autopost_picks"
                    {{ $settings['social_autopost_picks_enabled'] ? 'checked' : '' }}>
            </div>
        </div>

        <h6 class="mt-4">Social Media Credentials (API Keys / Tokens):</h6>
        <div class="row g-3">

            {{-- Twitter/X --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>𝕏 Twitter / X</h6>
                        <label>API Key (Bearer Token)</label>
                        <input type="password" class="form-control mb-2" name="twitter_bearer_token"
                            value="{{ $settings['twitter_bearer_token'] }}" placeholder="AAAA...">
                        <label>API Secret</label>
                        <input type="password" class="form-control mb-2" name="twitter_api_secret"
                            value="{{ $settings['twitter_api_secret'] }}" placeholder="...">
                        <label>Access Token</label>
                        <input type="password" class="form-control mb-2" name="twitter_access_token"
                            value="{{ $settings['twitter_access_token'] }}" placeholder="...">
                        <label>Access Token Secret</label>
                        <input type="password" class="form-control" name="twitter_access_token_secret"
                            value="{{ $settings['twitter_access_token_secret'] }}" placeholder="...">
                    </div>
                </div>
            </div>

            {{-- Telegram --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>✈️ Telegram</h6>
                        <label>Bot Token</label>
                        <input type="password" class="form-control mb-2" name="telegram_bot_token"
                            value="{{ $settings['telegram_bot_token'] }}" placeholder="123456789:ABCDEF...">
                        <label>Channel/Chat ID</label>
                        <input type="text" class="form-control" name="telegram_chat_id"
                            value="{{ $settings['telegram_chat_id'] }}" placeholder="@YourChannel">
                    </div>
                </div>
            </div>

            {{-- Facebook --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>Facebook Page</h6>
                        <label>Page Access Token</label>
                        <input type="password" class="form-control mb-2" name="facebook_page_access_token"
                            value="{{ $settings['facebook_page_access_token'] }}" placeholder="EAABw...">
                        <label>Page ID</label>
                        <input type="text" class="form-control" name="facebook_page_id"
                            value="{{ $settings['facebook_page_id'] }}" placeholder="1023456789">
                    </div>
                </div>
            </div>

            {{-- YouTube --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>YouTube (Community Post)</h6>
                        <label>OAuth Token / API Key</label>
                        <input type="password" class="form-control mb-2" name="youtube_oauth_token"
                            value="{{ $settings['youtube_oauth_token'] }}" placeholder="AIzaSy...">
                        <label>Channel ID</label>
                        <input type="text" class="form-control" name="youtube_channel_id"
                            value="{{ $settings['youtube_channel_id'] }}" placeholder="UC_x5XG1OV2P...">
                    </div>
                </div>
            </div>

            {{-- Instagram --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>Instagram (Business)</h6>
                        <label>Graph API Token</label>
                        <input type="password" class="form-control mb-2" name="instagram_graph_api_token"
                            value="{{ $settings['instagram_graph_api_token'] }}" placeholder="IGQQA...">
                        <label>Business Account ID</label>
                        <input type="text" class="form-control" name="instagram_business_account_id"
                            value="{{ $settings['instagram_business_account_id'] }}" placeholder="178414...">
                    </div>
                </div>
            </div>

            {{-- LinkedIn --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>LinkedIn</h6>
                        <label>Access Token</label>
                        <input type="password" class="form-control mb-2" name="linkedin_access_token"
                            value="{{ $settings['linkedin_access_token'] }}" placeholder="AQU...">
                        <label>Organization URN</label>
                        <input type="text" class="form-control" name="linkedin_organization_urn"
                            value="{{ $settings['linkedin_organization_urn'] }}" placeholder="urn:li:organization:...">
                    </div>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6>WhatsApp Channel/API</h6>
                        <label>Business API Token</label>
                        <input type="password" class="form-control mb-2" name="whatsapp_business_api_token"
                            value="{{ $settings['whatsapp_business_api_token'] }}" placeholder="EAAG...">
                        <label>Phone Number ID</label>
                        <input type="text" class="form-control" name="whatsapp_phone_number_id"
                            value="{{ $settings['whatsapp_phone_number_id'] }}" placeholder="102938...">
                    </div>
                </div>
            </div>

        </div>{{-- .row --}}

        <div class="mt-4">
            <button class="btn btn-primary" id="save-social-settings">Save Settings</button>
            <button class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#testPostModal">
                Test Post
            </button>
        </div>

    </div>{{-- .card-body --}}
</div>

<script>
document.getElementById('save-social-settings').addEventListener('click', function () {
    const fields = document.querySelectorAll('[name]');
    const payload = {};
    fields.forEach(f => {
        if (f.type === 'checkbox') {
            payload[f.name] = f.checked ? '1' : '0';
        } else {
            payload[f.name] = f.value;
        }
    });

    // map toggle IDs to keys
    ['autopost_news', 'autopost_picks'].forEach(id => {
        const el = document.getElementById(id);
        if (el) payload['social_' + id + '_enabled'] = el.checked ? '1' : '0';
    });

    fetch('/api/admin/social/settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => alert(d.message ?? 'Saved!'))
    .catch(() => alert('Error saving settings'));
});
</script>
```

---

## 11. Testing Each Platform

### Telegram (easiest to start)

1. Create a bot via [@BotFather](https://t.me/BotFather) → get `BOT_TOKEN`
2. Create a channel, add your bot as admin
3. Get Chat ID: `https://api.telegram.org/bot<BOT_TOKEN>/getUpdates`
4. Test via admin panel "Test Post" or:

```bash
php artisan social:autopost --type=news --trigger=manual --content="Test message from Laravel"
```

### Twitter/X

1. Apply for developer account at developer.twitter.com
2. Create an App → get API Key, API Secret
3. Generate Access Token + Secret (Read/Write permission required)
4. Use v2 API endpoint `/2/tweets`

### Facebook Page

1. Facebook Developer Console → create App → add "Pages" product
2. Get Page Access Token (long-lived) via Graph API Explorer
3. Permissions needed: `pages_manage_posts`, `pages_read_engagement`

### WhatsApp Business

1. Meta Developer Console → WhatsApp product
2. Get Phone Number ID and temporary access token
3. For production: generate permanent system user token

---

## Quick Checklist

- [ ] Run `php artisan migrate` for `site_settings` and `social_post_logs`
- [ ] Add `AutoPostSocialMedia` command to `Kernel.php` schedule
- [ ] Create `PostToSocialMediaJob` and queue it
- [ ] Register `AdminSocialMediaController` routes
- [ ] Start queue worker: `php artisan queue:work`
- [ ] Set up platform credentials from admin panel `/admin/social`
- [ ] Test with `php artisan social:autopost --trigger=test --content="Hello"`
- [ ] Monitor `social_post_logs` table for results
