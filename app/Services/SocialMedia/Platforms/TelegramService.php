<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class TelegramService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('telegram_bot_token'))
            && !empty(SocialAutomationSetting::get('telegram_chat_id'));
    }

    public function getSlug(): string { return 'telegram'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token  = SocialAutomationSetting::get('telegram_bot_token');
        $chatId = SocialAutomationSetting::get('telegram_chat_id');

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $content,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
            ]);

            $data = $response->json();
            if ($response->successful() && ($data['ok'] ?? false)) {
                $msgId = (string) ($data['result']['message_id'] ?? '');
                return ['success' => true, 'post_id' => $msgId, 'post_url' => null, 'response' => json_encode($data)];
            }
            return $this->fail(json_encode($data));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'post_id' => null, 'post_url' => null, 'response' => $msg];
    }
}
