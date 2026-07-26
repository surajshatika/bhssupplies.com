# AI Chat & Support Settings

Native, multi-provider AI chatbot widget for storefront customer support, with voice I/O and human admin takeover. Not a bolt-on addon module — it's built directly into the core app (`app/Http/Controllers/SupportChatController.php`, `app/Utility/AiChatUtility.php`, `resources/views/frontend/layouts/app.blade.php`), but reuses the `support_board.*` route/setting namespace and the activation flag (`active_ecommerce_support_board`) left over from the separate ticketing addon.

## Overview

- **Admin settings page:** `/admin/support-board/settings` (route `support_board.settings`)
- **Providers supported:** Groq (default, free), Anthropic Claude, OpenAI, Google Gemini — one active provider at a time, switchable per store
- **Storage:** `BusinessSetting` key/value table (`get_setting()`, cached under `business_settings`) — no dedicated config file or migration; `support_conversations` / `support_messages` tables are used directly by the Eloquent models with no migration file present in the repo (likely created ad hoc / via raw SQL)
- **Data model:** `SupportConversation` (one per visitor session or logged-in user) has many `SupportMessage` (`sender_type`: `user` \| `ai` \| `admin`)
- **Widget gate:** rendered in `resources/views/frontend/layouts/app.blade.php:2028` only if `addon_is_activated('active_ecommerce_support_board')` AND `ai_chat_status == 1`

## Settings Stored (`BusinessSetting`)

| Setting key | Description |
|---|---|
| `ai_chat_status` | Enables/disables the chat widget (instant AJAX toggle) |
| `ai_chat_visibility` | `all` (guests + logged-in) or `logged_in` only |
| `ai_chat_provider` | `groq` (default) \| `claude` \| `openai` \| `gemini` |
| `ai_chat_welcome_msg` | First AI message shown when a conversation starts (default: "Hi! How can I help you today?") |
| `ai_chat_system_prompt` | Custom system prompt; blank = built-in default (see below) |
| `groq_api_key` / `groq_model` | Groq key + model (default `llama-3.1-8b-instant`; also `llama-3.3-70b-versatile`, `gemma2-9b-it`) |
| `anthropic_api_key` / `claude_model` | Claude key + model (default `claude-haiku-4-5-20251001`; also `claude-sonnet-4-6`, `claude-opus-4-7`) |
| `openai_api_key` / `openai_model` | OpenAI key + model (`gpt-4o`, `gpt-4o-mini`, `gpt-4-turbo`, `gpt-3.5-turbo`) |
| `gemini_api_key` / `gemini_model` | Gemini key + model (`gemini-pro`, `gemini-1.5-flash`, `gemini-1.5-pro`, `gemini-2.0-flash`) |

## Admin UI

### Settings page (`resources/views/backend/support_board/settings.blade.php`)
1. **Chat Status** — AJAX switch (`ai_chat_status`) posted via `support_board.activation`, no page reload
2. **Chat Visibility** — `all` vs `logged_in` dropdown, saved via `support_board.settings.update`
3. **AI Provider** — provider dropdown (Groq/Claude/OpenAI/Gemini) + per-provider API key/model fields; only the selected provider's field group is shown, toggled client-side by `toggleProviderFields()`
4. **Chat Customization** — welcome message input + system prompt textarea (with placeholder example text)
5. **Conversations** — link out to the conversation inbox (`support_board.conversations`)

### Conversations inbox (`resources/views/backend/support_board/conversations.blade.php`)
- Paginated table (20/page) of all `SupportConversation`s, newest-updated first
- Columns: ID, customer (`display_name`/`display_email` — resolves to the logged-in user's name/email or the guest name/email captured at chat start), last message preview (icon per sender type: robot/shield/person), open/closed status badge, created date
- Unread badge shows `unreadCount()` — count of unread `user`-sent messages per conversation
- "View" link opens the conversation detail

### Conversation detail (`resources/views/backend/support_board/conversation_detail.blade.php`)
- Full scrollable message log, styled per sender (`user` grey left bubble, `ai` blue bubble, `admin` green right-aligned bubble with shield icon)
- Marks all unread `user` messages as read on load (`conversationDetail()` controller action)
- "Close Conversation" button (only shown while `status == open`) — sets status to `closed`
- Reply box (only shown while open) posts to `support_board.admin_reply` via AJAX and appends the reply to the log without a full reload — this is the human-agent takeover path; once an admin replies, the frontend widget's poller (`aizStartPolling`, every 8s) picks it up and injects it into the customer's open widget

## Default System Prompt (`AiChatUtility::getSystemPrompt()`)

Hardcoded fallback used when `ai_chat_system_prompt` is empty: positions the bot as a fast, direct, solution-first support assistant for a wholesale HVAC/plumbing/hardware store. Rules baked in: answer first (no leading clarifying questions), solve immediately when intent is clear, at most one follow-up question per reply, 2–4 sentence replies, product questions get specs/alternatives/pricing directly, order/shipping questions redirect to account or support contact, and — critically — only link products/categories that appear in the real DB-backed context block (never invent URLs). Support phone number `+1 (647) 456-2244` is hardcoded into the prompt. Page context (current page title + path) and product context are appended dynamically per message.

## Request Flow

1. **`startChat`** (`POST /support/chat/start`) — finds or reuses an existing `open` conversation keyed by session ID (guest) or user ID (logged-in); if none exists, creates one and seeds it with the AI welcome message (`sender_type = ai`)
2. **`sendMessage`** (`POST /support/chat/send`) — validates input (message ≤2000 chars, page_context ≤300 chars), saves the user message, builds the last 20 messages (`user`/`ai` only) as conversation history, calls `buildProductContext()`, then `AiChatUtility::chat()`, saves the AI reply, and returns it
3. **`pollMessages`** (`GET /support/chat/poll`) — frontend polls every 8s for new `admin`-authored messages since the last seen message ID (human takeover delivery)
4. **Admin side** — `conversations()` / `conversationDetail()` list/view threads; `adminReply()` posts a message as `sender_type = admin`; `closeConversation()` sets status to `closed`

## Product/Category Context Injection (`buildProductContext()`)

Keyword-extracts the user's message (strips punctuation, lowercases, drops a stopword list, keeps tokens ≥2 chars — so numeric codes like "95"/"11" survive for SKU-style queries) then:
1. **Exact phrase match** — searches `products` (published only) for `name`/`slug` LIKE the full cleaned message or its slugified form (up to 8 rows) — catches SKU/code searches like "95 11 200 SBA"
2. **Keyword fallback** — fills any remaining slots (of 8) with `name LIKE %word%` matches, excluding products already found
3. **Categories** — separately searches `categories` by full phrase or any keyword (up to 5 rows)
4. Both result sets are rendered as markdown link lists (`[Name](https://.../product/slug)` / `.../category/slug`) and appended to the system prompt with an explicit "only use these URLs" instruction, preventing the AI from hallucinating product links

## Provider Dispatch (`AiChatUtility::chat()`)

Routes to `claudeChat()` (default/fallback), `openaiChat()`, `groqChat()`, or `geminiChat()` based on `ai_chat_provider`. Each:
- Returns `"AI support is not configured yet. Please contact us directly."` if its API key setting is empty (fails soft, never throws to the user)
- Calls the provider's native API directly via `Illuminate\Support\Facades\Http` — Anthropic Messages API (`x-api-key` header, `anthropic-version: 2023-06-01`), OpenAI/Groq OpenAI-compatible `chat/completions` (Bearer auth), Gemini `generateContent` (API key as query param) — 30s timeout, 600 max output tokens, temperature 0.7 (OpenAI/Groq/Gemini only; Claude call has no temperature param)
- Maps stored `sender_type` (`user`/`ai`) to each provider's expected role names (`user`/`assistant` for Claude & OpenAI-style; `user`/`model` for Gemini, which also requires a priming turn pair since it has no separate system-role slot)
- Surfaces provider error payloads as `"AI error: {message}"`; catches request exceptions and returns a generic retry message

## Frontend Widget (`resources/views/frontend/layouts/app.blade.php:2027-2325`)

Self-contained floating widget, ~300 lines of inline CSS/JS, no external dependencies:
- **Launcher button** — fixed bottom-right circular button with unread-count badge
- **Chat window** — header (site name, TTS toggle, "new chat", close), scrollable message list, typing indicator, footer with mic button + text input + send button
- **Conversation persistence** — `aiz_conv_id` stored in `localStorage` so the conversation survives page navigation/reload; "new chat" clears it and starts fresh (with confirm prompt)
- **Voice input (STT)** — uses the browser `SpeechRecognition`/`webkitSpeechRecognition` API (Chrome/Edge only); mic button toggles listening, shows a "Listening… speak now" bar with animated dots, auto-sends the transcript on final result
- **Voice output (TTS)** — uses `window.speechSynthesis`; toggle button persisted in `localStorage` (`aiz_tts`, off by default); speaks both AI replies and polled admin replies when enabled
- **Message rendering** (`aizRenderMsg`) — escapes HTML first, then re-enables a constrained markdown subset: `[text](url)` links (https/relative only, length-capped), `**bold**`, `- `/`* `/`• ` bullet lines wrapped into `<ul><li>`, and `\n` → `<br>`; user's own messages are plain-escaped only (no markdown)
- **Polling** — every 8s while the window is open, fetches admin replies since the last seen message ID; shows an unread badge on the launcher button if the window is closed when a reply arrives
- **Page context** — captures `document.title + pathname` (truncated to 300 chars) and sends it with every message so the AI knows what page the customer is viewing
- **CSRF** — uses `window.perfCurrentCsrf()` if present (perf/cache-optimizer integration), else falls back to the static Blade `csrf_token()`

## Known Cleanup Item

`routes/support_board.php` still contains a debug route `GET /admin/support-board/gemini-test` (probes several Gemini model IDs in sequence to find one that responds successfully) — flagged in-code as "remove after fixing" but not yet removed.
