<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\BusinessSetting;
use App\Utility\AiChatUtility;
use Auth;
use Cache;
use DB;

class SupportChatController extends Controller
{
    // ─── Admin middleware ──────────────────────────────────────────────────────

    public function __construct()
    {
        $this->middleware(['auth', 'admin'])->only([
            'settings', 'updateSettings', 'updateActivation',
            'conversations', 'conversationDetail', 'adminReply', 'closeConversation',
        ]);
    }

    // ─── Admin: Settings ──────────────────────────────────────────────────────

    public function settings()
    {
        return view('backend.support_board.settings');
    }

    public function updateSettings(Request $request)
    {
        $fields = [
            'ai_chat_provider', 'openai_api_key', 'openai_model',
            'gemini_api_key', 'gemini_model',
            'anthropic_api_key', 'claude_model',
            'groq_api_key', 'groq_model',
            'ai_chat_system_prompt', 'ai_chat_welcome_msg',
            'ai_chat_visibility',
        ];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $setting = BusinessSetting::firstOrNew(['type' => $field]);
                $setting->value = $request->input($field);
                $setting->save();
            }
        }
        Cache::forget('business_settings');
        flash(translate('AI Chat settings updated successfully'))->success();
        return redirect()->route('support_board.settings');
    }

    public function updateActivation(Request $request)
    {
        $setting = BusinessSetting::firstOrNew(['type' => $request->type]);
        $setting->value = $request->value;
        $setting->save();
        Cache::forget('business_settings');
        return '1';
    }

    // ─── Admin: Conversations ─────────────────────────────────────────────────

    public function conversations()
    {
        $conversations = SupportConversation::with(['lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);
        return view('backend.support_board.conversations', compact('conversations'));
    }

    public function conversationDetail($id)
    {
        $conversation = SupportConversation::with('messages')->findOrFail($id);
        // Mark user messages as read
        $conversation->messages()->where('sender_type', 'user')->update(['is_read' => 1]);
        return view('backend.support_board.conversation_detail', compact('conversation'));
    }

    public function adminReply(Request $request)
    {
        $request->validate(['conversation_id' => 'required', 'message' => 'required|string|max:2000']);

        $conversation = SupportConversation::findOrFail($request->conversation_id);

        SupportMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'admin',
            'sender_name'     => Auth::user()->name,
            'message'         => $request->message,
            'is_read'         => 0,
        ]);

        $conversation->touch();

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back();
    }

    public function closeConversation($id)
    {
        SupportConversation::findOrFail($id)->update(['status' => 'closed']);
        flash(translate('Conversation closed'))->success();
        return redirect()->route('support_board.conversations');
    }

    // ─── Frontend: Start chat ─────────────────────────────────────────────────

    public function startChat(Request $request)
    {
        $sessionId = session()->getId();

        // Reuse existing open conversation for this session/user
        $query = SupportConversation::where('status', 'open');

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('session_id', $sessionId);
        }

        $conversation = $query->latest()->first();

        if (!$conversation) {
            $conversation = SupportConversation::create([
                'user_id'     => Auth::check() ? Auth::id() : null,
                'session_id'  => $sessionId,
                'guest_name'  => $request->input('name'),
                'guest_email' => $request->input('email'),
                'status'      => 'open',
            ]);

            // Welcome message from AI
            $welcome = get_setting('ai_chat_welcome_msg') ?? 'Hi! How can I help you today?';
            SupportMessage::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => 'ai',
                'sender_name'     => 'AI Support',
                'message'         => $welcome,
                'is_read'         => 1,
            ]);
        }

        $messages = $conversation->messages->map(fn($m) => [
            'id'          => $m->id,
            'sender_type' => $m->sender_type,
            'sender_name' => $m->sender_name,
            'message'     => $m->message,
            'time'        => $m->created_at->format('g:i A'),
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages'        => $messages,
        ]);
    }

    // ─── Frontend: Send message ───────────────────────────────────────────────

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'message'         => 'required|string|max:2000',
            'page_context'    => 'nullable|string|max:300',
        ]);

        $conversation = SupportConversation::findOrFail($request->conversation_id);

        // Save user message
        $displayName = Auth::check() ? Auth::user()->name : ($conversation->guest_name ?? 'Guest');

        SupportMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'user',
            'sender_name'     => $displayName,
            'message'         => $request->message,
            'is_read'         => 0,
        ]);

        // Build history (last 10 exchanges) for AI context
        $history = $conversation->messages()
            ->whereIn('sender_type', ['user', 'ai'])
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->map(fn($m) => ['sender_type' => $m->sender_type, 'message' => $m->message])
            ->values()
            ->toArray();

        // Get AI response
        $pageContext    = $request->input('page_context', '');
        $productContext = $this->buildProductContext($request->message);
        $aiText = AiChatUtility::chat($history, $request->message, $pageContext, $productContext);

        $aiMessage = SupportMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'ai',
            'sender_name'     => 'AI Support',
            'message'         => $aiText,
            'is_read'         => 1,
        ]);

        $conversation->touch();

        return response()->json([
            'success'  => true,
            'response' => $aiText,
            'time'     => $aiMessage->created_at->format('g:i A'),
        ]);
    }

    // ─── Product/Category context builder ────────────────────────────────────

    private function buildProductContext(string $message): string
    {
        // Extract meaningful keywords — min length 2 so codes like "95", "11" are kept
        $stop = ['the','and','for','are','you','with','this','that','have','need','want',
                 'can','how','what','where','price','buy','get','show','find','list','is','in','of','a','i'];
        $words = array_values(array_filter(
            preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $message))),
            fn($w) => strlen($w) >= 2 && !in_array($w, $stop)
        ));

        if (empty($words)) return '';

        $baseUrl    = rtrim(config('app.url'), '/');
        $cleanMsg   = trim(preg_replace('/\s+/', ' ', $message));
        $slugPhrase = str_replace(' ', '-', strtolower($cleanMsg));
        $lines      = [];

        // Step 1: Exact full-phrase match (SKU/code searches like "95 11 200 SBA")
        $exactProducts = DB::table('products')
            ->select('name', 'slug')
            ->where('published', 1)
            ->where(function ($q) use ($cleanMsg, $slugPhrase) {
                $q->where('name', 'like', '%' . $cleanMsg . '%')
                  ->orWhere('slug', 'like', '%' . $slugPhrase . '%');
            })
            ->limit(8)
            ->get();

        $foundSlugs = $exactProducts->pluck('slug')->toArray();

        // Step 2: Fill remaining slots with keyword matches (exclude already found)
        $remaining = 8 - $exactProducts->count();
        $keywordProducts = collect();
        if ($remaining > 0 && count($words) > 0) {
            $keywordProducts = DB::table('products')
                ->select('name', 'slug')
                ->where('published', 1)
                ->whereNotIn('slug', $foundSlugs)
                ->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->orWhere('name', 'like', '%' . $w . '%');
                    }
                })
                ->limit($remaining)
                ->get();
        }

        $productQuery = $exactProducts->concat($keywordProducts);

        if ($productQuery->isNotEmpty()) {
            $lines[] = 'REAL PRODUCTS IN DATABASE (use these exact URLs, do not invent others):';
            foreach ($productQuery as $p) {
                $lines[] = '- [' . $p->name . '](' . $baseUrl . '/product/' . $p->slug . ')';
            }
        }

        // Search categories — match any keyword
        $catQuery = DB::table('categories')
            ->select('name', 'slug')
            ->where(function ($q) use ($words, $cleanMsg) {
                $q->where('name', 'like', '%' . $cleanMsg . '%');
                foreach ($words as $w) {
                    $q->orWhere('name', 'like', '%' . $w . '%');
                }
            })
            ->limit(5)
            ->get();

        if ($catQuery->isNotEmpty()) {
            $lines[] = 'REAL CATEGORIES IN DATABASE:';
            foreach ($catQuery as $c) {
                $lines[] = '- [' . $c->name . '](' . $baseUrl . '/category/' . $c->slug . ')';
            }
        }

        return implode("\n", $lines);
    }

    // ─── Frontend: Poll new messages (admin replies) ──────────────────────────

    public function pollMessages(Request $request)
    {
        $conversation = SupportConversation::findOrFail($request->conversation_id);
        $since        = $request->input('since', 0);

        $messages = $conversation->messages()
            ->where('id', '>', $since)
            ->where('sender_type', 'admin')
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'message'     => $m->message,
                'time'        => $m->created_at->format('g:i A'),
            ]);

        return response()->json(['messages' => $messages]);
    }
}
