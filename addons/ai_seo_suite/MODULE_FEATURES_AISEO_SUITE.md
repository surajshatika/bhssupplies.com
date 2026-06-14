# AI SEO Suite Module — Feature Documentation

This document describes the full feature set of the `AI SEO Suite` admin module installed at `/admin/seo-suite`.
It is intended as a feature reference for implementing the same module on another platform with identical tabs, workflow, and functionality.

## Overview

The AI SEO Suite is a unified SEO management console for ecommerce and CMS platforms. It combines AI-assisted content, on-page SEO tooling, search analytics, webmaster tools, indexation utilities, and site health monitoring into a single admin experience.

Key capabilities:
- Central SEO dashboard with site score, setup progress, automation readiness, and priority actions.
- AI-powered assistant chat and content generation tools.
- Sitemap, robots.txt, RSS, IndexNow, and LLMs.txt generation.
- Google Search Console integration and keyword rank tracking.
- Internal link building assistance and SEO revision history.
- Webmaster verification management for Google, Bing, Yandex, Pinterest, Baidu.
- Redirect manager and automated SEO run queue control.
- Full UI parity across all suite tabs and tool screens.

## Entry Points

Primary admin entry point:
- `/admin/seo-suite`

Related admin pages:
- `/admin/seo-suite/ai-assistant`
- `/admin/seo-suite/ai-writing`
- `/admin/seo-suite/ai-images`
- `/admin/seo-suite/keyword-tracker`
- `/admin/seo-suite/search-stats`
- `/admin/seo-suite/webmaster`
- `/admin/seo-suite/link-assistant`
- `/admin/seo-suite/revisions`
- `/admin/seo-suite/settings`

Support and utility pages:
- `/admin/seo-suite/keyword-manager`
- `/admin/seo-suite/index-status`
- `/admin/seo-suite/monitoring`
- `/admin/seo-suite/oauth/google/connect`
- `/admin/seo-suite/oauth/google/callback`

## Admin Navigation and UI

The module uses a top nav strip across SEO Suite screens for the main tabs:
- Suite
- AI SEO Assistant
- Writer
- Keywords
- Stats
- Webmaster
- Links
- Revisions
- Settings

The suite UI is built around a responsive dashboard layout with cards, progress meters, sections, and quick action buttons.

## Dashboard / Suite Tab

The main dashboard is the heart of the module and includes:

### Setup & Health
- Setup wizard with steps for AI provider config, API keys, sitemap generation, robots.txt, LLMs.txt, and webmaster verification.
- Progress bar and completion percentage.
- Quick CTA button to open global SEO settings.

### SEO Site Score
- Circular score ring showing current SEO score.
- Score grade interpretation: Excellent / Good / Needs Work / Poor.
- Summary cards for done URLs, pending URLs, critical issues, and completion coverage.

### Advanced SEO Command Center
- Automation Readiness metric.
- Run Success Rate.
- Score Momentum delta.
- Average Run Time.
- Priority action queue with action severity and direct buttons.
- AI provider readiness and failover status.
- Technical file health cards for sitemap, robots, LLMs, RSS, etc.
- Provider reliability table showing configured keys, health, attempts, success rate, failures, fallbacks, latency, and estimated cost.

### Keyword Intelligence
- Target keywords count.
- Tracked keywords count.
- Google Page 1 count.
- GSC query URLs count.
- Autopilot focus keyword count.
- Keyword group badges and lists.
- Saved Google ranking table for tracked keywords.
- AI ranking insights pane when available.

### Autopilot and Batch Controls
- Bulk optimize pending URLs button.
- Queue recovery controls: compact duplicates, process next SEO URL, restart batch, cancel batch.
- Active batch status and progress details.

### Tools Panel
- Smart XML Sitemap generation.
- Video sitemap generation.
- News sitemap generation.
- RSS feed generation.
- Robots.txt generation.
- LLMs.txt generation.
- Redirect manager create/delete.
- IndexNow URL submission.
- IndexNow key generation.

### Redirects
- Redirect creation form with source URL, target URL, HTTP status code, notes, and save button.
- Redirect list with delete controls.

### Technical File Health
- Health cards for sitemap, robots, llms, rss existence and freshness.

## Settings Tab

Admin settings page includes:

### AI Providers & API Keys
- Default AI provider selector.
- Automatic AI provider failover toggle.
- AI failover provider order.
- Maximum AI attempts per request.
- Cool down unhealthy AI providers toggle.
- Failures before cooldown.
- Cooldown duration.
- OpenAI API key.
- Claude API key.
- Google Gemini API key.
- Grok API key.

### Search & Tracking
- Google Search Console site URL.
- Google Custom Search API key.
- Google Custom Search Engine ID.

### Google Search Console OAuth
- One-click connect button for Google Search Console via OAuth.
- Disconnect button when connected.
- Setup instructions and OAuth callback URL.

### PageSpeed / Performance Settings
- Settings for performance queries and integration (found in settings page, not visible in specific suite view but implemented in backend).

### Additional SEO Configuration
- Global SEO automation toggles.
- IndexNow key storage.
- Webmaster verification keys.
- Search Console sync controls.
- Sitemap / robots / LLMs settings.

## AI SEO Assistant Tab

The AI Assistant works as an interactive chat interface.

### Core features
- Message input and chat history.
- Provider selection.
- Context selection: General SEO, On-Page SEO, Off-Page SEO, Technical SEO, Local SEO.
- Quick action buttons for:
  - SEO Audit Checklist
  - Content Ideas
  - Competitor Strategy
  - Technical SEO Tips
  - Local SEO Tips
  - Link Building Ideas

### Backend
- Chat endpoint for AI provider conversation.
- Quick action endpoint for preconfigured AI prompts.
- History serialization on each request.

## Writer Tab

AI Writing Assistant tab supports content generation tasks.

### Core features
- Task selection for a variety of content types.
- Content input or page content paste.
- Keyword input.
- Tone selection.
- Length selection.
- Content type choices such as product descriptions, emails, ads, blog posts.
- Target language selection.
- Provider selection.
- AJAX generation endpoint.

### Backend
- AI writing assistant service that returns generated content.
- Validation for task presence.

## Image Tab

AI Image Generator tab supports SEO image creation.

### Core features
- Keyword / subject input.
- Purpose selector: product photo, blog/article, social media, banner, infographic.
- Size selector: 1024x1024, 1792x1024, 1024x1792.
- Quality selector: Standard, HD.
- Style selector.
- Custom prompt input.
- Save to media library toggle.
- Generate button.
- Generated image gallery.
- Suggested alt text, filename, and revised prompt.
- Apply generated image as OG/social image for page/entity.

### Backend
- Image generation endpoint.
- Apply-og endpoint to assign generated image to content entities.
- Generation history listing.

## Keyword Tracker Tab

Tracks keyword performance and Google ranking.

### Features
- Keyword list input.
- Search engine selector (Google Canada).
- Check & save rankings button.
- Keyword ranking summary counts.
- Latest ranking results table.
- AI insights panel for ranking analysis.
- Saved Google rank tracker table with movement indicators.
- Query-to-page mapping from Google Search Console.

### Backend
- Rank check endpoint.
- Data persistence for tracked keywords.
- Cron or sync integration with Search Console.

## Keyword Manager Page

Manages SEO keyword sets separate from the dashboard.

### Features
- Add related keywords and competitor keywords.
- Inline edit and delete controls.
- Search filter.
- Counts for each keyword group.
- Status alerts for keyword refresh requirements.

### Behavior
- Saved keywords are automatically applied to AI autopilot runs and content generation.
- Pages already scored highly can be reprocessed when keywords change.

## Search Statistics Tab

SEO performance analytics and score trends.

### Features
- Summary cards for pages tracked, average score, best score, Search Console connection.
- SEO score history chart.
- Top performing pages table.
- Google Search Console top queries table.
- AI analysis sidebar when available.

### Metrics
- Local SEO score history from tracked pages.
- Top page scores and grades.
- GSC impressions, clicks, CTR, and position data.

## Post Index Status Page

Checks whether pages are indexed in Google.

### Features
- URL list input (one per line).
- Check index status button.
- Results table with URL, status, method, and action.
- Indexed vs not indexed badges.
- AI recommendations panel.
- Quick actions: regenerate sitemap, submit homepage via IndexNow.

### Backend
- Index status check endpoint using Google Custom Search or Google API.
- Error handling for API failures.

## Webmaster Tools Tab

Manages verification codes for major webmaster consoles.

### Features
- Verification code input for:
  - Google Search Console
  - Bing Webmaster Tools
  - Yandex.Webmaster
  - Pinterest Verification
  - Baidu Zhanzhang
- Generated HTML meta tags.
- Clipboard copy for meta tags.
- Quick external links to webmaster consoles.
- Implementation guidance for adding meta tags to page head.

## Link Assistant Tab

Helps with internal and external linking opportunities.

### Features
- Page URL input.
- Focus keyword input.
- Optional page content input.
- Link type selector: both, internal only, external only.
- Find opportunities button.
- Summary metrics for total, internal, external suggestions.
- Internal link table with anchor text and copy action.
- External authority links table with domain authority.
- AI link building strategy suggestions.

## SEO Revisions Tab

Tracks historical SEO score changes.

### Features
- Total revisions count.
- Latest score.
- Average score.
- Delta vs previous revision.
- Revision history table with URL, score, grade, trend, and date.
- Score trend chart.
- Run SEO audit button.

## Supporting Documents for Porting

When implementing this module on another platform, ensure the following:
- Preserve the navigation tabs and suite structure.
- Keep the same user flow for dashboard actions and tool pages.
- Support the same AI provider controls and fallback behavior.
- Include the same key endpoint actions for sitemaps, robots, redirects, IndexNow, and LLMs.
- Implement keyword management and tracker persistence.
- Maintain Search Console OAuth and GSC data sync.
- Provide AI chat, writer, and image generation capabilities with provider selection.
- Add webmaster verification management and meta tag generation.
- Add a SEO revisions history view.

## Recommended Implementation Notes

- Use a modular service layer for AI provider routing and SEO features.
- Store settings centrally and allow admin override by provider type.
- Use a dashboard data builder for site score, provider health, and automation readiness.
- Keep the command center actions clear: run, recover, restart, submit, generate.
- Use consistent UI cards, badges, and small text sections to match the current experience.

## UI Tab Summary

1. Suite
2. AI SEO Assistant
3. Writer
4. AI Images
5. Keywords
6. Stats
7. Webmaster
8. Links
9. Revisions
10. Settings

## Notes

This document mirrors the existing SEO Suite module implementation details discovered in the Laravel codebase at `resources/views/backend/seo_suite/` and related routes in `routes/admin.php`. It is intentionally structured for porting the same experience to another platform.
