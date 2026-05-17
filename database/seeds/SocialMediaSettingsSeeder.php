<?php

use App\Models\SocialAutomationSetting;
use Illuminate\Database\Seeder;

class SocialMediaSettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // Global
            ['social_global_autopost_enabled', '0',  'boolean', 'general'],
            ['social_default_ai_provider',     'openai', 'string', 'general'],

            // Platform enables (only Facebook & Instagram active — confirmed accounts exist)
            ['social_facebook_enabled',   '1', 'boolean', 'facebook'],
            ['social_instagram_enabled',  '1', 'boolean', 'instagram'],
            ['social_twitter_enabled',    '0', 'boolean', 'twitter'],
            ['social_linkedin_enabled',   '0', 'boolean', 'linkedin'],
            ['social_youtube_enabled',    '0', 'boolean', 'youtube'],
            ['social_telegram_enabled',   '0', 'boolean', 'telegram'],
            ['social_whatsapp_enabled',   '0', 'boolean', 'whatsapp'],
            ['social_tiktok_enabled',     '0', 'boolean', 'tiktok'],
            ['social_pinterest_enabled',  '0', 'boolean', 'pinterest'],
            ['social_reddit_enabled',     '0', 'boolean', 'reddit'],
            ['social_snapchat_enabled',   '0', 'boolean', 'snapchat'],
            ['social_discord_enabled',    '0', 'boolean', 'discord'],

            // Facebook — Page ID is known from business_settings
            // Page Access Token must be obtained from Meta Developer Console
            ['facebook_page_id',           '100083261085061', 'string', 'facebook'],
            ['facebook_page_access_token', '', 'secret', 'facebook'],

            // Instagram — linked to same Meta app as Facebook
            // Business Account ID retrieved via Graph API after Meta app setup
            ['instagram_business_account_id', '', 'string', 'instagram'],
            ['instagram_graph_api_token',     '', 'secret', 'instagram'],

            // Twitter/X — no account exists yet; create at twitter.com then developer.twitter.com
            ['twitter_api_key',             '', 'secret', 'twitter'],
            ['twitter_api_secret',          '', 'secret', 'twitter'],
            ['twitter_access_token',        '', 'secret', 'twitter'],
            ['twitter_access_token_secret', '', 'secret', 'twitter'],

            // LinkedIn — no account exists yet; create company page then developer.linkedin.com
            ['linkedin_access_token',       '', 'secret', 'linkedin'],
            ['linkedin_organization_urn',   '', 'string', 'linkedin'],

            // YouTube — create channel via bhssupplies@gmail.com, then Google Cloud Console
            ['youtube_oauth_token',  '', 'secret', 'youtube'],
            ['youtube_channel_id',   '', 'string', 'youtube'],

            // Telegram — create bot via @BotFather, create channel
            ['telegram_bot_token', '', 'secret', 'telegram'],
            ['telegram_chat_id',   '', 'string', 'telegram'],

            // WhatsApp Business — requires Meta Business Suite + verified business number
            ['whatsapp_business_api_token', '', 'secret', 'whatsapp'],
            ['whatsapp_phone_number_id',    '', 'string', 'whatsapp'],
            ['whatsapp_channel_id',         '', 'string', 'whatsapp'],

            // TikTok — create account, then developer.tiktok.com
            ['tiktok_access_token', '', 'secret', 'tiktok'],
            ['tiktok_open_id',      '', 'string', 'tiktok'],

            // Pinterest — create business account, then developers.pinterest.com
            ['pinterest_access_token', '', 'secret', 'pinterest'],
            ['pinterest_board_id',     '', 'string', 'pinterest'],

            // Reddit — create account, then reddit.com/prefs/apps
            ['reddit_access_token', '', 'secret', 'reddit'],
            ['reddit_subreddit',    'r/hvac', 'string', 'reddit'],

            // Snapchat — create business account at ads.snapchat.com
            ['snapchat_access_token',  '', 'secret', 'snapchat'],
            ['snapchat_ad_account_id', '', 'string', 'snapchat'],

            // Discord — create server, then add webhook via channel settings
            ['discord_webhook_url', '', 'string', 'discord'],

            // AI Provider Keys (fill in your own API keys)
            ['social_ai_openai_key',   '', 'secret', 'ai'],
            ['social_ai_openai_model', 'gpt-4o', 'string', 'ai'],
            ['social_ai_claude_key',   '', 'secret', 'ai'],
            ['social_ai_claude_model', 'claude-sonnet-4-6', 'string', 'ai'],
            ['social_ai_grok_key',     '', 'secret', 'ai'],
            ['social_ai_grok_model',   'grok-beta', 'string', 'ai'],
        ];

        foreach ($settings as [$key, $value, $type, $group]) {
            // Only insert if not already set — don't overwrite existing credentials
            if (!SocialAutomationSetting::where('key', $key)->exists()) {
                SocialAutomationSetting::create([
                    'key'   => $key,
                    'value' => $value,
                    'type'  => $type,
                    'group' => $group,
                ]);
            }
        }

        $this->command->info('Social media settings seeded. Facebook Page ID pre-filled.');
        $this->command->warn('Add Page Access Token (Facebook), Graph API Token (Instagram), and other API keys via admin panel.');
    }
}
