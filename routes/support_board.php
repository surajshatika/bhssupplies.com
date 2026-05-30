<?php

/*
|--------------------------------------------------------------------------
| AI Chat & Support Board Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\SupportBoardController;

// ── Admin ────────────────────────────────────────────────────────────────────
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::controller(SupportChatController::class)->group(function () {
        Route::get('/support-board/settings',            'settings')->name('support_board.settings');
        Route::post('/support-board/settings/update',    'updateSettings')->name('support_board.settings.update');
        Route::post('/support-board/activation',         'updateActivation')->name('support_board.activation');
        Route::get('/support-board/conversations',       'conversations')->name('support_board.conversations');
        Route::get('/support-board/conversation/{id}',   'conversationDetail')->name('support_board.conversation');
        Route::post('/support-board/admin-reply',        'adminReply')->name('support_board.admin_reply');
        Route::get('/support-board/close/{id}',         'closeConversation')->name('support_board.close_conversation');
    });
});

// ── Debug (remove after fixing) ───────────────────────────────────────────────
Route::get('/admin/support-board/gemini-test', function () {
    $apiKey  = get_setting('gemini_api_key');
    $models  = ['gemini-2.0-flash','gemini-2.0-flash-lite','gemini-1.5-flash','gemini-1.5-flash-latest','gemini-1.5-pro'];
    $results = [];

    foreach ($models as $model) {
        $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $resp = \Illuminate\Support\Facades\Http::timeout(10)->post($url, [
            'contents' => [['role' => 'user', 'parts' => [['text' => 'Hi']]]],
        ]);
        $body = $resp->json();
        $results[$model] = [
            'status' => $resp->status(),
            'ok'     => $resp->successful() && isset($body['candidates']),
            'error'  => $body['error']['message'] ?? null,
        ];
        if ($resp->successful() && isset($body['candidates'])) break;
    }

    return response()->json($results);
})->middleware(['auth', 'admin']);

// ── Frontend AJAX ────────────────────────────────────────────────────────────
Route::middleware('web')->group(function () {
    Route::get('/support-board/tickets', [SupportBoardController::class, 'tickets'])->name('support_board.tickets');
    Route::post('/support/chat/start',  [SupportChatController::class, 'startChat'])->name('support_board.chat_start');
    Route::post('/support/chat/send',   [SupportChatController::class, 'sendMessage'])->name('support_board.chat_send');
    Route::get('/support/chat/poll',    [SupportChatController::class, 'pollMessages'])->name('support_board.chat_poll');
});
