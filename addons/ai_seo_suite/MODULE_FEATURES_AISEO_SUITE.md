# AI SEO Suite Module — Feature Documentation

This document describes the full feature set of the `AI SEO Suite` admin module installed at `/admin/seo-suite`.
It is intended as a feature reference for implementing the same module on another platform with identical tabs, workflow, and functionality.

## Overview

The AI SEO Suite is a unified SEO management console for ecommerce and CMS platforms. It combines AI-assisted content, on-page SEO tooling, search analytics, webmaster tools, indexation utilities, and site health monitoring into a single admin experience.

The suite actually spans **four parallel admin surfaces** that share the same top nav (`suite_nav` partial), settings, AI providers, and underlying services:
1. **SEO Suite** (`/admin/seo-suite`) — the main consolidated dashboard and most end-user-facing tool screens (documented in most of this file).
2. **On-Page SEO** (`/admin/seo/on-page`) — tile launcher for all `config('seo.features.on_page')` tools.
3. **Off-Page SEO** (`/admin/seo/off-page`) — tile launcher for all `config('seo.features.off_page')` (backlink/outreach/content marketing) tools.
4. **Technical Optimization** (`/admin/seo/optimization`) — a second, more technical automation-focused dashboard with its own readiness score, automation scope panel, and grouped tool tiles for `config('seo.features.optimization')`.

Key capabilities:
- Central SEO dashboard with site score, setup progress, automation readiness, and priority actions.
- AI SEO Board: sitewide entity scan, per-entity AI fix/preview/approve workflow, and batch/bulk AI fixing with budget guardrails.
- On-Page, Off-Page, and Technical Optimization tool launchers exposing 30+ individually-configurable AI SEO features (see "Feature Catalog" section).
- AI-powered assistant chat and content generation tools (writer, images, outreach emails).
- Per-entity (product/category/page) inline "Generate Meta" and "Generate Description" AI buttons embedded directly in admin edit forms.
- Sitemap, robots.txt, RSS, IndexNow, and LLMs.txt generation.
- Google Search Console integration, keyword rank tracking, and PageSpeed/Core Web Vitals sync.
- Advanced analytics: semantic gap analysis, content decay detection, internal link graph, predictive traffic forecasting.
- Advanced AI tooling: GEO/AI-search readiness scoring, real Chrome-user field data (CrUX), embedding-based keyword clustering, an autonomous research agent, and SSE token streaming.
- Internal link building assistance (with automated auto-linker engine) and SEO revision history.
- Webmaster verification management for Google, Bing, Yandex, Pinterest, Baidu.
- Redirect manager and automated SEO run queue control.
- SEO Monitoring dashboard for AI provider health, spend, batch history, and error tracking.
- A full hourly/daily/weekly cron automation layer (`seo:automation-run` and ~12 satellite commands) driving nearly every feature above without manual admin action.
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
- `/admin/seo-suite/ai-board`

Advanced AI tool pages:
- `/admin/seo-suite/geo-readiness`
- `/admin/seo-suite/field-data`
- `/admin/seo-suite/keyword-clusters`
- `/admin/seo-suite/research-agent`

Advanced analytics pages:
- `/admin/seo-suite/semantic-gap`
- `/admin/seo-suite/content-decay`
- `/admin/seo-suite/link-graph`
- `/admin/seo-suite/core-web-vitals`
- `/admin/seo-suite/predictive-traffic`

Support and utility pages:
- `/admin/seo-suite/keyword-manager`
- `/admin/seo-suite/index-status`
- `/admin/seo-suite/monitoring`
- `/admin/seo-suite/oauth/google/connect`
- `/admin/seo-suite/oauth/google/callback`

Parallel module dashboards (separate controllers, same nav/settings):
- `/admin/seo/on-page` — On-Page AI SEO Tools
- `/admin/seo/off-page` — Off-Page AI SEO Tools
- `/admin/seo/optimization` — Technical SEO Optimization

Per-entity AI generation endpoints (called from product/category/page edit forms, not standalone pages):
- `POST /admin/seo/optimization/generate-meta` — AI meta title/description for any entity
- `POST /admin/seo/optimization/generate-product-content` — AI short/long product description
- `POST /admin/seo/optimization/generate-category-content` — AI category top/bottom description

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
**14 supported AI providers.** All are defined once in `SeoProviderManager::PROVIDERS` / `::META`; the settings form, dropdowns, save/load maps, `.env` mirroring, and health panel all read from that registry, so adding a provider is a single entry rather than edits in five files.

| Provider | Best for |
|---|---|
| OpenAI (ChatGPT) | Content generation, images, TruSEO analysis |
| Claude (Anthropic) | Advanced content, schema markup, SEO strategy |
| Gemini (Google) | Multimodal content, structured data, embeddings |
| Grok (xAI) | Real-time insights with X/Twitter data |
| Perplexity | Live-web-grounded answers — best for gap/competitor research |
| Mistral | European-hosted; also provides embeddings |
| DeepSeek | Lowest cost per token — the value pick for bulk passes |
| Groq | LPU inference, highest tokens/sec — best for streaming |
| OpenRouter | One key fronting 300+ models; swap model via env alone |
| Together AI | Hosted open-weight models, cheap bulk generation |
| Fireworks AI | Fast hosted open-weight models |
| Qwen (Alibaba) | Strong multilingual coverage for non-English SEO |
| Moonshot (Kimi) | Long context — whole-page/multi-document analysis |
| Cohere | Chat plus embeddings and reranking |

Notes:
- **Grok (xAI) and Groq (LPU inference) are different vendors** whose names differ by one letter. They are deliberately kept distinct with no cross-aliasing — silently routing one to the other would send traffic and spend to the wrong company. A test enforces this.
- Claude's form field and setting key remain `anthropic_api_key` / `seo_anthropic_api_key`; renaming them would orphan every existing install's stored key. Also test-enforced.
- Accepted aliases: `anthropic`→claude, `google`→gemini, `chatgpt`→openai, `xai`/`x.ai`→grok, `kimi`→moonshot, `dashscope`/`alibaba`→qwen, `togetherai`→together, `open-router`→openrouter.
- All 14 keys are encrypted at rest via the generic `_api_key$` pattern in `BusinessSetting`.
- Eleven of the fourteen share one `OpenAiCompatibleProvider` base class (~24 lines each) instead of duplicated ~68-line drivers, so a behaviour fix lands everywhere at once.
- **Streaming:** 12 providers stream genuinely; Gemini and Perplexity return a single chunk and say so.
- **Embeddings** (for keyword clustering): OpenAI, Gemini, Mistral, Cohere, Together. Chat-only providers are rejected with a clear message rather than silently downgraded.

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
- "Draft Email" button per external link-opportunity row, opening an AI-generated cold outreach email (personalized subject line, angle adapted to guest post / broken-link-replacement / resource addition) in a modal with copy-to-clipboard.
- Auto-Linker Engine card with a "Run Auto-Linker Now" button that runs the internal auto-linking engine synchronously and reports the results.

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

## AI SEO Board

The flagship sitewide scan-and-fix dashboard, separate from the main Suite tab, at `/admin/seo-suite/ai-board`.

### Scan & Table
- Scans products, categories, pages, and blogs for SEO completeness/score via a scoring service.
- Type filter, text search, "missing field" filter, score range filter, sort options, and pagination.
- Sitewide summary block plus a scored table per entity with counts broken down by entity type.

### Per-Entity Actions
- **Fix** — immediately generates and writes an AI-produced SEO fix (meta title/description, focus keyword, etc.) for a single entity, with provider selection.
- **Preview** — generates the same suggested fields without saving, feeding an edit/approve drawer so the admin can review before committing.
- **Apply Approved** — persists admin-approved or manually edited values from the preview drawer (meta title ≤255 chars, meta description ≤500 chars, focus keyword ≤191 chars, secondary keywords ≤1000 chars, schema markup toggle).
- **Rescore** — recomputes and returns the live SEO score for an entity without any AI call.

### Bulk / Batch Processing
- **Bulk Estimate** — given a mode of `selected` (explicit ID list) or `filtered` (type + filters + limit, capped at a max manual batch size), returns projected target count, per-entity cost, total cost, active provider, and the daily AI-budget cap/spent/remaining. Warns if the queue driver is `sync` (jobs run inline/cron-chunked instead of queued).
- **Bulk Run** — re-validates the AI budget cap, creates a batch record, and dispatches it to the queue (or schedules cron-chunked processing).
- **Bulk Progress** — polls a JSON snapshot per batch: status, total/processed/succeeded/failed/skipped counts, percent complete, current item label, running cost, and recent errors — used to drive a live progress UI.
- **Bulk Cancel** — cancels an in-flight batch.

## Semantic Gap Analysis

Compares a page's on-page content against what top-ranking competitors likely cover for a target keyword.

### Features
- Keyword input and target URL input.
- Fetches the live page HTML, strips markup, and sends the cleaned text plus the keyword to the configured AI provider.
- Returns up to 10 missing LSI keywords/entities with a relevance rating (High / Medium / Low), rendered as an entity/relevance table.
- Falls back to generic suggested entities if the AI response can't be parsed, and shows an error state if the URL fetch fails.

## Content Decay Analysis

Detects pages losing organic traffic and queries where the site cannibalizes its own rankings, using Search Console history.

### Features
- **Decayed Pages** — compares the most recent 28 days of clicks against the prior 28 days per URL; flags pages with meaningful historical traffic (>10 past clicks) and a drop of 15% or more, sorted by biggest decline. Shows URL, past clicks, recent clicks, and drop percentage.
- **Cannibalized Queries** — identifies queries where more than one page on the site currently earns clicks, listing each competing page's share of clicks and total query clicks, sorted by total volume.

## Internal Link Graph

Visualizes the site's internal linking structure to surface orphaned and highly-linked pages.

### Features
- Builds a graph across all SEO-tracked entities (products, categories, pages, blogs) and their resolved live URLs.
- **Orphaned Pages** — pages with zero internal inlinks.
- **Powerful Pages** — pages with 3+ inlinks, top 20 shown by inlink count, with URL, entity type, title, inlink/outlink counts, and a computed authority score.
- Note: outlink counts are currently simulated pending a full DOM/content crawler; treat as directional rather than exact.

## Core Web Vitals

Surfaces PageSpeed Insights data synced from Google for mobile and desktop.

### Features
- Groups synced PageSpeed results by URL and strategy (mobile/desktop).
- Displays performance, SEO, accessibility, and best-practices scores per page, sorted by most recent sync date.
- Empty state shown until PageSpeed data has been synced at least once.

## Predictive Traffic

Forecasts potential traffic gains and flags underperforming queries using Search Console data.

### Features
- **Forecasts** — models the click gain from moving a query up 3 ranking positions using a standard position-based CTR curve, estimating new clicks, click gain, and an estimated dollar ROI per query; sorted by potential gain.
- **Anomalies** — flags top-10 ranking queries whose actual CTR is less than half of what's expected for that position, reporting estimated missed clicks; sorted by missed-click volume.

## SEO Monitoring

An operations/health dashboard at `/admin/seo-suite/monitoring`, separate from the Suite dashboard, for tracking AI usage and system health over a selectable time window (7–90 days, default 30).

### Features
- AI provider usage and spend summary for the selected window.
- SEO runs completed vs. failed.
- Average SEO score trend chart and score distribution chart.
- Recent AI fix batches table (links back to AI Board bulk runs).
- Features with the most failures (error hotspot table).
- Top Search Console queries (28 days).
- Biggest keyword rank changes table.

## On-Page SEO Module (`/admin/seo/on-page`)

A dedicated tile-launcher dashboard, distinct from the AI SEO Board, for running the on-page feature set defined in `config('seo.features.on_page')`.

### Features
- "Local On-Page AI Strategy" banner card: primary target cities (e.g. Mississauga, Brampton, Toronto), secondary target cities, and conversion-intent badges (Trade Account, Leave a Review) — drives locally-flavored AI prompts.
- Grid of clickable tool tiles, one per on-page feature (Local On-Page Blueprint, Meta Title & Description Generator, Focus Keyword Density Analyzer, SEO Content/Article Writer, Heading Structure H1–H6, Image Alt Text Bulk Generator, Internal Linking Suggestions, Schema Markup JSON-LD, Readability Score, Full On-Page SEO Audit (20+ factors), Open Graph/Twitter Card Generator, TruSEO On-Page Analysis Score, Breadcrumb Schema Generator, Image SEO Optimizer).
- Clicking a tile opens a shared "Run Tool" modal: AI provider selector, dynamic input fields (URL, keyword, content paste, etc. shown/hidden per selected tool), and a submit button that posts to a single generic run endpoint.
- "Recent On-Page Runs" history table: feature, status (queued/completed/failed badge), target URL or topic, created date.
- Setup-required warning banner if the SEO database migrations haven't been run yet.

### Backend
- `App\Http\Controllers\Seo\OnPageSeoController@index` / `@run`.
- Each run is persisted as a `SeoRun` (module=`on_page`) and processed via `GenerateOnPageSeoJob`, dispatching to the matching `App\Services\Seo\OnPage\Features\*Service` class.

## Off-Page SEO Module (`/admin/seo/off-page`)

Tile-launcher dashboard for backlink/content-marketing style AI generation, explicitly scoped to *white-hat, admin-reviewed* output — it does not auto-publish anything externally.

### Features
- Disclaimer banner: "AI backlink automation creates white-hat prospect lists, outreach emails, citation targets, guest post angles, and anchor plans. It does not auto-post spam links on third-party websites."
- Tool tiles for each off-page feature: AI Backlink Campaign Generator, Backlink Outreach Email Generator, Guest Post Topic Generator, Guest Post Full Article Writer, Social Media Signal Posts, Press Release Generator, Anchor Text Profile Builder.
- Same shared "Run Tool" modal pattern as On-Page: AI provider selector plus dynamic fields — Topic/Subject (for campaign/guest-post/social/press-release tools), Target URL to promote (for campaign/outreach/social/guest-post-article tools), and tool-specific extra fields.
- "Recent Off-Page Content Generation" history table: feature, status, topic/keyword, created date.

### Backend
- `App\Http\Controllers\Seo\OffPageSeoController@index` / `@run`.
- Persists `SeoRun` (module=`off_page`), processed via `GenerateSeoContentJob` against services like `AnchorTextService`, `BacklinkOutreachService`, `GuestPostService`, `PressReleaseService`, `SocialSignalService`.

## Technical Optimization Module (`/admin/seo/optimization`)

The most automation-centric dashboard in the suite — a second, deeper "control room" beyond the main Suite tab, focused on technical health and the cron automation layer.

### Optimization Automation Center
- Technical Readiness percentage (files, APIs, automation configured).
- Run Success Rate and count of failed recent runs.
- Active Redirects count.
- AI Providers configured count.
- "Hourly Cron Setup" panel showing the exact crontab line (`0 * * * * php artisan seo:automation-run`) and a dry-run command variant.
- "Automation Scope" panel with ON/OFF badges for: on-page pending SEO, off-page backlink campaigns, technical refresh, IndexNow auto-submission.
- "Canada Targeting" panel repeating the primary/secondary city and conversion-intent badges.
- Master ON/OFF badge tied to the `seo_master_automation_enabled` setting.

### Technical File Health
- Table of tracked files (sitemap, robots.txt, LLMs.txt, RSS, etc.) with status, last-updated time, file size, and a regenerate action button per row.
- Footnote that files are auto-refreshed by the `seo:automation-run` cron job.

### Grouped Tool Tiles
Tools are organized into labeled groups (each with its own icon/color), all launched from this one dashboard:
- **Sitemaps** — Smart XML Sitemap, Video SEO Sitemap, Blog/News Sitemap, RSS Content Feed.
- **Technical SEO** — Page Speed Analyzer (Core Web Vitals), Technical SEO Audit (50+ checks), Robots.txt Optimizer, Canonical URL Manager, Broken Link Checker, Competitor Gap Analyzer.
- **Content & Schema** — FAQ Schema Generator, E-commerce Product SEO, Local SEO Optimizer, Small Business SEO.
- **URL & Redirects** — Redirection Manager, Post Index Status, IndexNow Submission, Webmaster Tools.
- **AI Tools** — AI Writing Assistant, AI Image Generator, AI SEO Chat Assistant, Link Assistant, Keyword Rank Tracker, Search Statistics, SEO Revisions, LLMs.txt Generator, SEO Score Dashboard.
- Some tiles are "direct action" buttons that immediately POST to their existing seo-suite endpoint (sitemap/robots/rss/indexnow/etc.); others that don't have a dedicated screen open the shared Run Tool modal, same pattern as On-Page/Off-Page.

### Backend
- `App\Http\Controllers\Seo\OptimizationController` — `index`, `run`, `generateSitemap`, `generateRobots`, `storeRedirect`, plus the three per-entity AI generation endpoints (`generateMetaTags`, `generateProductContent`, `generateCategoryContent`).

## Feature Catalog (All Configured On-Page / Off-Page / Optimization Tools)

Every entry below is a real, implemented feature (backed by its own service class under `app/Services/Seo/{OnPage,OffPage,Optimization}/Features/`), reachable either via a dedicated suite screen or via the generic On-Page/Off-Page/Optimization "Run Tool" modal + run-history table.

**On-Page:** Local On-Page Blueprint, Meta Title & Description Generator, Focus Keyword Density Analyzer, SEO Content/Article Writer, Heading Structure (H1–H6), Image Alt Text Bulk Generator, Internal Linking Suggestions, Schema Markup (JSON-LD), Readability Score & Improvement, Full On-Page SEO Audit (20+ factors), Open Graph & Twitter Card Generator, TruSEO On-Page Analysis Score, Breadcrumb Schema Generator, Image SEO Optimizer.

**Off-Page:** AI Backlink Campaign Generator, Backlink Outreach Email Generator, Guest Post Topic Generator, Guest Post Full Article Writer, Social Media Signal Posts, Press Release Generator, Anchor Text Profile Builder.

**Optimization/Technical:** Page Speed Analyzer & Fixer, Automated Technical Refresh, Technical SEO Audit (live-measured), Competitor Keyword Gap Analyzer, Smart XML Sitemap Generator, Video SEO Sitemap, Blog/News Sitemap, RSS Content Feed, Robots.txt AI Optimizer, Canonical URL Manager, Redirection Manager, Broken Link Checker, FAQ Schema Generator, Local SEO Optimizer, E-commerce Product SEO, Small Business SEO, SEO Score Dashboard, IndexNow Submission, Link Assistant, Webmaster Tools, Search Statistics, Post Index Status, Keyword Rank Tracker, SEO Revisions, LLMs.txt Generator, AI Writing Assistant, AI Image Generator, AI SEO Assistant.

## Per-Entity SEO Editing (Product / Category / Page Forms)

Beyond the suite's own dashboards, AI SEO generation is embedded directly inside the standard admin content-editing screens:

- **Product create/edit** (`resources/views/backend/product/products/create.blade.php`, `edit.blade.php`) — a "Generate Meta" button calls the AI meta-tag endpoint inline; a separate AJAX call generates short/long product descriptions.
- **Category edit** (`resources/views/backend/product/categories/edit.blade.php`) — the same AI meta generator plus a category-specific top/bottom description generator.
- **Static page edit** (`resources/views/backend/website_settings/pages/edit.blade.php`) — uses the same optimization-module routes for AI meta generation on static pages.
- Blog posts do **not** currently have this inline AI meta/content generation wired up — a known gap rather than a hidden feature.

## Automation & Scheduling

A full cron-driven automation layer ties almost every feature above together, orchestrated primarily by one master command plus satellite jobs (see `app/Console/Kernel.php`):

| Schedule | Command | Purpose |
|---|---|---|
| Hourly | `seo:automation-run` | Master orchestrator — on-page runs every hour; heavier tasks are interval-gated unless `--force-all` |
| Every 5 min | `seo:process-ai-batches --max-batches=1` | Drains AI SEO Board bulk-fix batches |
| Daily 02:45 | `seo:auto-optimize-pending` | Auto-runs on-page optimization for pending URLs (batch size from settings) |
| Daily 03:10 | `seo:auto-offpage-campaign` | Automated off-page/backlink content generation |
| Weekly Mon 03:30 | `seo:check-broken-links --limit=400 --per-entity=10` | Site-wide broken link sweep |
| Daily 04:00 | `seo:sync-search-console --days=7` | Pulls latest Google Search Console data |
| Every 6 hours | `seo:check-keyword-ranks --limit=50` | Refreshes tracked keyword rankings |
| Daily 05:00 | `seo:evergreen-loop` | Evergreen Auto-Improver — remediates decaying content |
| Weekly Sun 05:30 | `seo:resolve-cannibalization` | Auto-resolves keyword cannibalization between competing pages |
| Twice daily 06:00/18:00 | `seo:pagespeed --strategy=mobile` | PageSpeed Insights sync feeding Core Web Vitals |
| Weekly | `seo:weekly-snapshot` | Legacy URL-keyed score history + robots regeneration |

Each job logs to its own file under `storage/logs/` (e.g. `seo-automation.log`, `seo-ai-batches.log`) and uses `withoutOverlapping()` + `runInBackground()` to stay safe under concurrent execution. The Technical Optimization dashboard surfaces ON/OFF state for on-page, off-page, technical-refresh, and auto-IndexNow automation scopes, all controlled from Settings.

## Artisan / CLI Tools for Admins & Ops

Beyond the scheduled jobs above, these commands are available for manual operation and troubleshooting:
- `seo:doctor [--json]` — diagnostics check: validates config, required DB tables, AI provider keys, and cron wiring.
- `seo:content-decay-alerts [--email=]` — sends an email digest of decaying content (the CLI counterpart to the Content Decay Analysis screen).
- `seo:migrate-inline-meta` — migrates legacy inline meta tags into the suite's structured meta fields.
- `seo:cleanup-injected-content [--dry-run]` — removes AI-injected content that needs to be rolled back.
- `seo:restart-ai-queue` — restarts/recovers stuck AI SEO Board batch queue workers.
- `seo:auto-linker` — CLI equivalent of the Link Assistant's "Run Auto-Linker Now" button.

## Advanced AI Tools

Five newer capabilities built around what actually moves rankings in AI-mediated search. Services live in `app/Services/Seo/Advanced/`, wired through `App\Http\Controllers\Seo\AdvancedSeoController`. All AI-spending endpoints are rate-limited via `seo.rate`.

### AI Search (GEO) Readiness — `/admin/seo-suite/geo-readiness`
Scores how citable a page is by ChatGPT Search, Perplexity, and Google AI Overviews — a different problem from ranking ten blue links, since answer engines extract and attribute discrete passages.

- **Uses zero AI calls.** All 8 factors are measured directly from the fetched HTML, so the score is deterministic, free, instant, and reproducible — the same page always scores the same. This is verified by a test.
- Weighted factors (100 pts total): Answer-First Content (18), Extractable Facts (16), Structured Data/JSON-LD (15), Heading Structure (13), Question-Shaped Sections (12), Author & Entity Attribution (10), Freshness Signals (8), AI Crawler Access (8).
- Every factor reports the **evidence** behind its score plus a concrete fix — no black-box numbers.
- "Fix These First" panel ranks gaps by points recoverable.
- Detects real problems: duplicate H1s, `noindex`/`nosnippet` blocking answer-engine excerpting, JS-only content invisible to non-executing crawlers, missing machine-readable dates.

### Real User Field Data (CrUX) — `/admin/seo-suite/field-data`
Reads the Chrome UX Report API: the 28-day rolling distribution of measurements from real Chrome users.

- **This is the data Google actually ranks on.** The existing PageSpeed/Lighthouse page shows *lab* data — a simulated load on synthetic hardware. Both are now available and clearly labelled as distinct.
- Reports LCP, INP, CLS, FCP, and TTFB at the 75th percentile, with the full good/needs-improvement/poor distribution bar rather than a single number.
- Core Web Vitals pass/fail assessment (LCP + INP + CLS must all be "good"); returns "incomplete" rather than claiming a pass when data is missing.
- **Honest about missing data:** low-traffic URLs get an explicit "not enough real Chrome traffic yet" state, never substituted lab numbers. Falls back to origin-level data only with a visible notice saying so.
- Requires the Chrome UX Report API to be enabled on the Google Cloud project that owns the PageSpeed key.

### Semantic Keyword Clustering — `/admin/seo-suite/keyword-clusters`
Groups keywords by meaning so each cluster maps to one page — the fix for cannibalisation and the basis for topical authority.

- **The AI supplies embeddings only; it never picks the groups.** Clustering is deterministic cosine-similarity union-find. Asking a chat model to "group these keywords" gives a different answer each run, silently drops inputs, and can't be verified.
- Supports OpenAI (`text-embedding-3-small`), Gemini (`text-embedding-004`), and Mistral (`mistral-embed`) — the providers with real embeddings endpoints. Chat-only providers are rejected with a clear message.
- Adjustable similarity threshold; single-link clustering so transitive relationships group correctly.
- Each cluster names a **head term** (the member closest to the cluster centroid) as the page target, plus a cohesion score.
- Refuses to cluster on a partial embedding response rather than silently misaligning keywords to wrong vectors.

### Autonomous Research Agent — `/admin/seo-suite/research-agent`
A ReAct-style loop: each turn the model reviews what it has gathered and decides which page to read next, or that it has enough to write up.

- **Hard security boundary — the agent may only fetch URLs from the admin-supplied seed list, and cannot follow links it discovers.** An agent that fetches model-chosen URLs is an SSRF primitive: text injected into a fetched page could steer it into requesting internal addresses (`169.254.169.254`, `127.0.0.1`, intranet hosts) and echoing the response back. Seeds are validated as public HTTP(S) before any request; localhost, private ranges, and metadata addresses are rejected up front. Covered by a test.
- Capped at 8 fetches / 10 turns.
- Full **reasoning trace** is shown: each turn's thought, chosen action, observation, and any guard interventions.
- Reports fetch failures (paywalls, blocks, JS-only pages) honestly instead of inventing content, and the final report is instructed to state what the sources did not cover.

### Streaming AI Output (SSE)
Token-by-token streaming for the AI Writing Assistant, via `POST /admin/seo-suite/stream`.

- Genuine incremental streaming for OpenAI, DeepSeek, Mistral, Grok (OpenAI-compatible SSE) and Claude (Anthropic event format).
- **Gemini and Perplexity degrade honestly** — they return the whole response in one chunk and the UI says "returned in one piece". No simulated typewriter effect, which would look identical to real streaming while misrepresenting how fast the model actually responded.
- Server-side output buffering is torn down and each frame flushed explicitly, with `X-Accel-Buffering: no` so nginx doesn't buffer the stream.

### Navigation
The nav strip is grouped into five workflow stages — Overview, Create, Optimize, Research, Links & History — rather than one flat 23-item row, which read as undifferentiated noise at that size. The AI-keys health chip now counts all seven configured providers.

## Frontend / Public-Facing SEO Layer

Everything documented above is the admin control surface. This section covers what actually renders on the live storefront and public URLs — previously undocumented but essential to the suite's actual SEO effect.

### Public SEO files (served outside `/admin`)
Routed in `routes/web.php` and handled by `SeoSuiteController`'s public methods:
- `GET /sitemap.xml` → `publicSitemap()`
- `GET /robots.txt` → `publicRobots()`
- `GET /video-sitemap.xml` → `publicVideoSitemap()`
- `GET /news-sitemap.xml` → `publicNewsSitemap()`
- `GET /llms.txt` → `publicLlmsTxt()`

### Live meta tag & structured data rendering
- `App\Services\Seo\SeoMetaResolver` and `SeoCacheManager` — singleton services (registered in `App\Providers\SeoServiceProvider`) that resolve canonical URL, robots directive, Open Graph/Twitter Card tags, and JSON-LD schema per entity or per request.
- Two custom Blade directives registered by `SeoServiceProvider::registerBladeDirectives()`:
  - `@seoMeta` — renders `resources/views/seo/partials/meta-tags.blade.php` (canonical link, OG/Twitter tags, JSON-LD) for the current request or an explicit entity.
  - `@schema($json)` — emits a raw `<script type="application/ld+json">` block from an array or JSON string.
- Both directives are wrapped in try/catch so a missing partial or bad payload renders nothing rather than breaking the page — the whole SEO layer is designed to fail silently, never take down the storefront.
- Consumed across `frontend/layouts/app.blade.php` (base canonical tag), product detail/listing pages, blog detail/listing pages, static location landing pages, and store-promotion pages.

### Cache-busting observers (also registered in `SeoServiceProvider`)
- `SeoMetaObserver` — busts the full-page cache the instant SEO meta changes, so AI SEO Board / autopilot edits appear live immediately instead of waiting for cache TTL.
- `EntityCachePurgeObserver` — attached to Product, Category, Page, and Blog models; busts cache when the entity's own SEO-relevant content changes.
- `SeoEntitySlugObserver` — attached to the same four models; tracks slug changes so redirects/canonical URLs stay correct.
- `SeoRedirectObserver` — attached to the `SeoRedirect` model; invalidates the redirect map cache on save/delete.

### Live redirect enforcement
`App\Http\Middleware\SeoRedirectMiddleware` runs on every public GET/HEAD request (admin/api/storage/assets/livewire paths are bypassed):
- Loads the `seo_redirects` table into a 1-hour cached map (source URL → target + status code), capped at 5,000 rules.
- Matches the request path (with/without trailing slash) or full URL, issues the redirect, and increments `hit_count`/`last_hit_at` per rule for the Redirect Manager's analytics.
- Wrapped in a top-level try/catch — any failure logs a warning and the request continues unmodified rather than 500ing.

### AI rate limiting
`App\Http\Middleware\SeoRateLimitMiddleware` throttles AI-spending SEO endpoints (AI Board fix/preview/bulk, writer, image generator, chat, etc.) per authenticated admin user — default 30 requests/minute, configurable via the `seo_ai_rate_per_min` setting (1–300 range). Returns a JSON 429 with `retry_after` seconds for AJAX/AI Board calls, or a plain 429 response otherwise.

## Infrastructure Notes (Provider Failover, Cost Estimation, Local Business Schema)

Cross-referenced from a full re-read of `config/seo.php`:
- **`ssl_verify`** — toggles TLS certificate verification for AI/Google/Bing/PageSpeed HTTP calls; defaults to verifying in production but can be disabled for local WAMP development.
- **Provider failover block** (`provider_failover.*`) — beyond the failover order already documented: `max_attempts` (default 4), `cooldown_enabled`/`failure_threshold`/`cooldown_minutes` (auto-cools a provider after repeated failures), `request_timeout`/`connect_timeout`/`http_retries` for the shared HTTP client, and a per-provider `attempt_cost_usd` table (e.g. OpenAI ~$0.0009, Claude ~$0.0207, Gemini ~$0.0004, Grok ~$0.0023, Perplexity ~$0.0030, Mistral ~$0.0006, DeepSeek ~$0.0003) that feeds the AI Board's bulk-cost estimator and the Monitoring dashboard's spend tracking.
- **`local_business`** config (name, type, city, region, country, phone) — backs LocalBusiness JSON-LD schema output for Local SEO Optimizer / Small Business SEO features.
- **`webmaster`** verification keys — confirmed to include Pinterest and Baidu in addition to Google/Bing/Yandex (already documented in the Webmaster Tools tab, cross-referenced here for completeness).
- **`indexnow`** key/host config is backed by a real `IndexNowService` implementation (`app/Services/Seo/Optimization/Features/IndexNowService.php`), invoked from `SeoSuiteController::indexNow()`.
- **`queue`** config maps on-page, off-page, and optimization runs to independently configurable queue names/connections.

## Database Schema (Supporting Tables)

Confirms the persistence layer behind every feature above; no undocumented admin-facing capability was found beyond what's already described, but for completeness:
`seo_meta`, `seo_runs`, `seo_analytics`, `seo_fix_batches`, `seo_redirects`, `seo_score_histories`, `seo_projects`, `seo_campaigns`, `seo_keywords`, `seo_keyword_competitors`, `seo_generated_images`, `seo_broken_links`, `on_page_seo_pages`, `on_page_seo_tasks`. Each has a corresponding Eloquent model (`SeoMeta`, `SeoRun`, `SeoAnalytic`, `SeoFixBatch`, `SeoRedirect`, `SeoScoreHistory`, `SeoProject`, `SeoKeyword`, `SeoKeywordCompetitor`, `SeoGeneratedImage`, `SeoBrokenLink`) mapping 1:1 to the features already documented.

## Explicitly Out of Scope / Confirmed Gaps

To avoid over-claiming, these were checked and found **not implemented** — worth knowing when porting, so they aren't assumed to exist:
- No SEO-specific REST/headless API (`routes/api.php` has no SEO routes) — admin and public web routes only.
- No dedicated SEO Notification classes — score-drop alerts, budget-exceeded warnings, and failed-run alerts surface only as in-app flash messages / dashboard badges, not queued notifications (email/Slack/etc.), aside from the one `seo:content-decay-alerts` CLI command which does send email digests.
- No hreflang / multi-locale SEO tagging — the frontend layout sets `<html lang>`/`dir` per active locale, but there are no hreflang `<link rel="alternate">` tags or per-locale duplicate meta handling anywhere in the resolver or views.
- Blog posts still lack the inline AI meta/content generation buttons that products, categories, and static pages have.

## Supporting Documents for Porting

When implementing this module on another platform, ensure the following:
- Preserve the navigation tabs and suite structure.
- Keep the same user flow for dashboard actions and tool pages.
- Support the same AI provider controls and fallback behavior.
- Include the same key endpoint actions for sitemaps, robots, redirects, IndexNow, and LLMs.
- Implement keyword management and tracker persistence.
- Maintain Search Console OAuth and GSC data sync, plus PageSpeed Insights sync for Core Web Vitals.
- Provide AI chat, writer, and image generation capabilities with provider selection.
- Add webmaster verification management and meta tag generation.
- Add a SEO revisions history view.
- Implement the AI Board scan/fix/preview/approve workflow with budgeted bulk batch processing.
- Implement semantic gap analysis, content decay analysis, internal link graph, and predictive traffic analytics.
- Implement the auto-linker engine and AI-drafted outreach emails.
- Add a SEO Monitoring dashboard for AI spend, run health, and error tracking.
- Build the three parallel On-Page / Off-Page / Technical Optimization tile-launcher dashboards, sharing one "Run Tool" modal pattern and run-history table per module.
- Wire the full feature catalog (13 on-page, 7 off-page, 22+ optimization/technical features) to real backend services, not just labels.
- Embed inline AI "Generate Meta"/"Generate Description" actions directly into product, category, and static page edit forms.
- Replicate the cron automation layer (hourly master run + satellite jobs for batches, broken links, GSC sync, rank checks, evergreen loop, cannibalization resolution, PageSpeed sync, weekly snapshot).
- Provide equivalent CLI diagnostics/maintenance tooling (health check, content-decay alerts, queue restart, content cleanup).
- Implement the public-facing layer: serve sitemap.xml/robots.txt/video-sitemap.xml/news-sitemap.xml/llms.txt outside the admin area, render live canonical/OG/Twitter/JSON-LD tags via a resolver + template partials, and fail silently (never 500) if SEO data is missing.
- Add cache-busting observers so SEO edits appear on the live site immediately, not after a cache TTL.
- Add a redirect-enforcing middleware (cached rule map, hit tracking, safe bypass on failure) rather than relying only on the admin-side redirect list being descriptive.
- Add per-user rate limiting on AI-spending endpoints to prevent runaway AI cost from a single admin session.

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

Additional standalone screens reachable from dashboard cards/quick actions (not top-nav tabs): AI Board, Semantic Gap Analysis, Content Decay Analysis, Internal Link Graph, Core Web Vitals, Predictive Traffic, SEO Monitoring, Keyword Manager, Post Index Status.

Parallel module dashboards (separate top nav context, same suite_nav partial): On-Page SEO, Off-Page SEO, Technical Optimization.

## Notes

This document mirrors the existing SEO Suite module implementation details discovered in the Laravel codebase at `resources/views/backend/seo_suite/` and related routes in `routes/admin.php`. It is intentionally structured for porting the same experience to another platform.
