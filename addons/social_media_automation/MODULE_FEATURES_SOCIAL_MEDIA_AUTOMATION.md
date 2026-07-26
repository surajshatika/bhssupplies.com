# Social Media Automation Module — Feature Documentation

This document describes the full feature set of the **Social Media Automation** module as it
actually exists in this codebase today, at `/admin/social-media`. Unlike `ai_seo_suite` and
`active_ecommerce_performance_optimizer`, this module is **not packaged as an installable addon**
— it lives directly in the core app (`app/Http/Controllers/Admin`, `app/Services/SocialMedia`,
`app/Models`, `app/Jobs`, `app/Console/Commands`, `config/social_media.php`,
`resources/views/backend/social_media`). This file is placed in `addons/` as a feature reference /
porting spec, mirroring the documentation style of the other addon modules.

## Overview

The Social Media Automation module is an AI-assisted console for generating and auto-publishing
content across 12 social platforms, running scheduled/recurring campaigns, queuing posts, and
tracking delivery success — all from the admin panel, with optional AI content/hashtag generation
via OpenAI, Claude (Anthropic), or Grok.

Key capabilities:
- Central dashboard with per-platform connection status, global auto-post toggle, campaign counts,
  queue backlog, and recent post logs.
- 12 supported platforms, each behind a common `SocialPlatformInterface`: X/Twitter, Facebook Page,
  Instagram Business, LinkedIn, YouTube Community, Telegram Channel, WhatsApp Business, TikTok
  Business, Pinterest, Reddit, Snapchat Business, Discord.
- AI content generation (per-platform and multi-platform) with 8 selectable tones and AI-generated
  hashtags, via a pluggable AI provider manager (OpenAI, Claude, Grok).
- Campaigns: named, typed content plans (product launch, sale/promo, brand awareness, seasonal,
  educational, testimonial, news, custom) with AI-generated per-platform content, one-off or
  recurring (cron expression) scheduling, and auto-post toggle.
- Manual Compose screen: pick platforms, write or AI-generate content, attach image/video/link,
  publish immediately or schedule for later (goes to the queue if scheduled in the future).
- Background queue: `SocialQueuedPost` records processed by `ProcessSocialQueueJob` (priority +
  pending, 50 at a time) which dispatches `PostToSocialMediaJob` per platform.
- Post logs (`SocialPostLog`) recording every publish attempt — platform, trigger, AI provider,
  content sent, status (success/failed/skipped/queued/rate_limited), response, remote post
  id/url, retry count — with dashboard stats (total, success, failed, today) and a filterable log
  list (by platform, status, date range).
- `social:autopost` Artisan command for CLI/cron-driven posting: post fixed content or generate via
  AI per topic/tone/provider, restricted to a platform subset, with a `--dry-run` mode; gated by a
  global auto-post setting and per-platform enabled flags.
- Settings screen storing all platform credentials and AI keys as key/value rows via
  `SocialAutomationSetting` (boolean flags stored distinctly from secret values).
- Test-post action per platform to validate credentials end-to-end without a real campaign.

## Entry Points

Primary admin entry point (route prefix `social-media`, route name group `admin.social.`):
- `/admin/social-media` — Dashboard (`index`)
- `/admin/social-media/settings` — Settings (GET `settings`, POST `settings.save`)
- `/admin/social-media/logs` — Post Logs (`logs`)
- `/admin/social-media/campaigns` — Campaigns list (GET `campaigns`)
  - POST `/admin/social-media/campaigns` — create campaign (`campaigns.create`)
  - POST `/admin/social-media/campaigns/{campaign}/generate` — AI-generate content (`campaigns.generate`)
  - POST `/admin/social-media/campaigns/{campaign}/status` — update status (`campaigns.status`)
  - DELETE `/admin/social-media/campaigns/{campaign}` — delete (`campaigns.delete`)
- `/admin/social-media/compose` — Compose / manual post + pending queue view (`compose`)
  - POST `/admin/social-media/post` — send or schedule a post (`post`)
  - POST `/admin/social-media/ai/generate` — AI content generation (`ai.generate`)
  - POST `/admin/social-media/ai/hashtags` — AI hashtag generation (`ai.hashtags`)
  - POST `/admin/social-media/test-platform` — send a test post (`test`)
  - DELETE `/admin/social-media/queue/{post}` — remove a queued post (`queue.delete`)

Defined in `routes/admin.php` under the "Social Media Automation (AI Agent)" section, controller
`App\Http\Controllers\Admin\SocialMediaController`.

Note: this is separate from the unrelated **Social Login** feature (`admin.social_login.index`,
`/social-login/{provider}/redirect|callback` in `routes/web.php` and `routes/api.php`, backed by
`laravel/socialite`) — that is customer-facing OAuth sign-in (Google/Facebook/Twitter), not content
automation, and shares no models or controllers with this module.

## Dashboard Tab (`index`)

- Platform status grid: for each of the 12 platforms, shows configured/not-configured
  (`SocialPlatformInterface::isConfigured()`), enabled/disabled (per-platform
  `SocialAutomationSetting`), label, icon, and region tag (`global` / `canada` / `both`).
- Global auto-post enabled/disabled toggle indicator.
- Post stats cards: total, successful, failed, and today's post count (`SocialPostLog::stats()`).
- Recent post logs (latest 10).
- Active campaign count and pending queue count.
- Available AI providers list (only providers with a configured API key surface as usable).

## Settings Tab

Stores all configuration as rows via `SocialAutomationSetting::get/set/getMultiple`, boolean flags
saved with type `boolean`, everything else saved as `secret`:

### Global
- `social_global_autopost_enabled` — master on/off switch gating the `social:autopost` command and
  automated campaign posting.
- `social_default_ai_provider`.

### Per-platform enable flags
- `social_{platform}_enabled` for each of: twitter, facebook, instagram, linkedin, youtube,
  telegram, whatsapp, tiktok, pinterest, reddit, snapchat, discord.

### AI provider keys
- OpenAI: `social_ai_openai_key`, `social_ai_openai_model` (default `gpt-4o`).
- Claude/Anthropic: `social_ai_claude_key`, `social_ai_claude_model` (default `claude-sonnet-4-6`).
- Grok/xAI: `social_ai_grok_key`, `social_ai_grok_model` (default `grok-beta`).

### Per-platform credentials
- Twitter: API key, API secret, access token, access token secret.
- Telegram: bot token, chat id.
- Facebook: page access token, page id.
- YouTube: OAuth token, channel id.
- Instagram: Graph API token, business account id.
- LinkedIn: access token, organization URN.
- WhatsApp: business API token, phone number id, channel id.
- TikTok: access token, open id.
- Pinterest: access token, board id.
- Reddit: access token, subreddit.
- Snapchat: access token, ad account id.
- Discord: webhook URL.

Unchecked boolean checkboxes are explicitly persisted as `'0'` on save so disabling a platform
sticks even though unchecked checkboxes aren't submitted by the browser.

## Campaigns Tab

CRUD list of `SocialCampaign` records (paginated, 20/page).

### Fields
- Name, type (`config('social_media.campaign_types')`: product_launch, sale_promotion,
  brand_awareness, seasonal, educational, testimonial, news_update, custom).
- Status: draft, active, paused, completed, archived (color-coded badge).
- AI provider (openai/claude/grok), tone (`config('social_media.tones')`: professional, casual,
  promotional, educational, urgent, storytelling, humorous, inspirational), topic, keywords,
  target audience.
- Target platforms (array).
- `auto_post` toggle, `schedule_type` (once/recurring), `cron_expression` for recurring campaigns,
  `scheduled_at`.
- Tracks `post_count` and `total_reach` (reach field present in schema; not yet populated by any
  analytics sync in current code) and `last_posted_at`.

### Actions
- **Create** — validates required name/type/ai_provider/tone/topic/platforms, saved as `draft`.
- **Generate Content** — `SocialAiContentGenerator::generateMultiPlatform()` produces per-platform
  copy from the campaign's topic/tone/keywords/audience and stores it on `generated_content`
  (keyed by platform).
- **Update Status** — draft/active/paused/completed/archived transitions.
- **Delete**.
- `SocialCampaign::scopeDueToPost()` selects active + auto_post campaigns with no `scheduled_at` or
  one that has passed; `AutoPostSocialMedia::runActiveCampaigns()` (private helper, not currently
  wired into the command's `handle()` flow) would dispatch `PostToSocialMediaJob` per platform for
  each due campaign's generated content and bump `post_count`/`last_posted_at`.

## Compose Tab

Manual/ad-hoc posting screen.

### Features
- Platform multi-select, free-text content box.
- Optional image URL, video URL, link (passed through as `options` to the platform service).
- Optional AI content generation (`ai.generate`) by platform/topic/tone/provider before sending.
- Optional AI hashtag generation (`ai.hashtags`) — up to 10 hashtags for a topic via a chosen
  provider.
- **Send/Schedule** — if a future `schedule` datetime is given, creates a `SocialQueuedPost` per
  platform (status `pending`, trigger `manual_scheduled`); otherwise dispatches
  `PostToSocialMediaJob` immediately per platform.
- Pending/processing queued posts list (paginated 20, ordered by `scheduled_at`), each deletable.
- **Test Platform** action — dispatches a `manual_test`-triggered job for a single platform/content
  pair to verify credentials without going through a campaign or queue.

## Logs Tab

Filterable history of every publish attempt (`SocialPostLog`).

### Features
- Filters: platform, status, date range (`date_from`/`date_to`).
- Paginated (30/page), newest first.
- Stats header (total/success/failed/today).
- Per-row: platform, trigger source, AI provider used, content sent, status badge (success /
  failed / skipped / queued / rate_limited), raw response, remote post id/url, retry count.

## Background Processing

### `PostToSocialMediaJob`
Per-platform job that performs the actual publish via `SocialMediaManager::make($platform)->post()`,
called from Compose (immediate send), the queue processor, the autopost command, and campaign
auto-posting. Writes the outcome to `SocialPostLog`.

### `ProcessSocialQueueJob`
Runs on the `social` queue (configurable via `SOCIAL_QUEUE_NAME`). Pulls up to 50 pending queued
posts (`SocialQueuedPost::pending()->priority()`), marks each `processing`, increments `attempts`,
and dispatches `PostToSocialMediaJob` for each with its stored platform/content/trigger/options/
campaign id/AI provider/queue id.

### `social:autopost` Artisan command
- Refuses to run unless `social_global_autopost_enabled` is on.
- `--content=` posts fixed text; `--topic=` triggers AI generation per platform via
  `SocialAiContentGenerator`; `--provider=`, `--tone=`, `--platforms=` (comma list, default: all),
  `--trigger=` (label stored in logs), `--dry-run` (prints without dispatching).
- Filters the target platform list down to only those with `social_{platform}_enabled` true.
- Intended to be scheduled via the Laravel scheduler / cron for recurring auto-posting (no
  `$schedule->command(...)` entry was found wired up in the current codebase — this would need to
  be added, along with wiring `runActiveCampaigns()` into `handle()`, for campaigns to actually
  auto-fire on their `cron_expression`/`scheduled_at`).

## AI Content Generation

`App\Services\SocialMedia\SocialAiContentGenerator` + `App\Services\SocialMedia\AI\SocialAiProviderManager`:
- `generateForPlatform($platform, $topic, $tone, $provider, $context)` — single-platform copy,
  respecting each platform's `char_limit` from `config/social_media.php`.
- `generateMultiPlatform($platforms, $topic, $tone, $provider, $context)` — batch variant used by
  campaigns, returns an array keyed by platform.
- `generateHashtags($topic, $count, $provider)` — returns up to N suggested hashtags.
- Supported providers: OpenAI (`gpt-4o` default), Claude/Anthropic (`claude-sonnet-4-6` default),
  Grok/xAI (`grok-beta` default) — each configured via its own endpoint/model/API key in
  `config/social_media.php`, keys settable per-provider from the Settings screen.
- `SocialAiProviderManager::availableProviders()` filters to providers with a non-empty API key so
  the UI only offers usable providers.

## Platform Services

`App\Services\SocialMedia\Platforms\*Service` — one class per platform, each implementing
`SocialPlatformInterface` (`post(content, options): array{success, post_id, post_url, response}`,
`isConfigured(): bool`, `getSlug(): string`). Resolved via `SocialMediaManager::make($slug)`.
Platforms: Twitter, Facebook, Instagram, LinkedIn, YouTube, Telegram, WhatsApp, TikTok, Pinterest,
Reddit, Snapchat, Discord.

`SocialMediaManager::configuredPlatforms()` returns only platforms whose service reports
`isConfigured()` true (i.e., required credentials are present in settings/config).

## Data Model Summary

| Model | Purpose | Key fields |
|---|---|---|
| `SocialAutomationSetting` | Key/value settings store (typed: boolean/secret) | key, value, type |
| `SocialCampaign` | Planned content campaign | name, type, status, ai_provider, tone, topic, keywords, target_audience, platforms[], generated_content[], auto_post, schedule_type, cron_expression, scheduled_at, last_posted_at, post_count, total_reach |
| `SocialQueuedPost` | Pending/processing scheduled post | platform, content, options, status, trigger, ai_provider, scheduled_at, campaign_id, attempts |
| `SocialPostLog` | Immutable record of every publish attempt | platform, trigger, campaign_id, ai_provider, content_sent, status, response, post_id, post_url, retry_count, posted_at |

Migrations (dated 2026-05-03): `create_social_automation_settings_table`,
`create_social_campaigns_table`, `create_social_post_logs_table`, `create_social_queued_posts_table`.

## Related but Separate: SEO Off-Page Social Signal Service

`app/Services/Seo/OffPage/Features/SocialSignalService.php` exists under the AI SEO Suite's
off-page analysis feature set — it appears to assess/report on social signals for SEO scoring
purposes, not to publish posts. It is unrelated to the publishing pipeline described above and
should not be conflated with it when porting this module.

## Composer Dependencies

- `laravel/socialite: ^5.6` and `genealabs/laravel-socialiter` are installed but power the
  unrelated customer-facing **Social Login** feature, not this automation module.
- No platform-specific SDKs (e.g., `abraham/twitteroauth`, official Instagram/Pinterest/TikTok/
  LinkedIn SDKs) or third-party scheduling SDKs (Buffer/Hootsuite) are installed — all platform
  services call each platform's HTTP API directly.

## Known Gaps / Notes for Porting or Hardening

- `AutoPostSocialMedia::runActiveCampaigns()` is defined but not called from `handle()` — recurring
  campaign auto-posting via cron expression is not currently wired end-to-end.
- No scheduler entry (`app/Console/Kernel.php`) currently runs `social:autopost` or a queue-draining
  job automatically — both would need a `$schedule->command(...)` / `$schedule->job(...)` entry to
  operate without manual CLI invocation.
- `SocialCampaign.total_reach` and any engagement/analytics rollup (impressions, clicks, follower
  growth) are not populated anywhere in the current code — there is no analytics-sync feature yet,
  unlike the AI SEO Suite's Search Console/PageSpeed sync pattern.
- No content calendar UI, no per-post approval workflow, no comment/DM moderation, and no link
  shortening/UTM/click-tracking exist in the current implementation — the module covers
  generation + publishing + logging only, not the fuller "social suite" feature set found in
  dedicated third-party social schedulers.
- Unlike `ai_seo_suite` and `active_ecommerce_performance_optimizer`, this feature has no
  `config.json`/installer, so it cannot be packaged and installed as a toggleable addon zip through
  `/admin/addons` in its current form — it ships as part of core.
