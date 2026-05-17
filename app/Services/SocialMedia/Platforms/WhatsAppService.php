<?php

namespace App\Services\SocialMedia\Platforms;

use App\Models\SocialAutomationSetting;
use Illuminate\Support\Facades\Http;

class WhatsAppService implements SocialPlatformInterface
{
    public function isConfigured(): bool
    {
        return !empty(SocialAutomationSetting::get('whatsapp_business_api_token'))
            && !empty(SocialAutomationSetting::get('whatsapp_phone_number_id'));
    }

    public function getSlug(): string { return 'whatsapp'; }

    public function post(string $content, array $options = []): array
    {
        if (!$this->isConfigured()) return $this->fail('Not configured');

        $token   = SocialAutomationSetting::get('whatsapp_business_api_token');
        $phoneId = SocialAutomationSetting::get('whatsapp_phone_number_id');
        $to      = $options['to'] ?? SocialAutomationSetting::get('whatsapp_channel_id', '');

        if (!$to) return $this->fail('WhatsApp channel ID not configured');

        try {
            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $content],
                ]);

            $data = $response->json();
            if ($response->successful() && isset($data['messages'][0]['id'])) {
                return ['success' => true, 'post_id' => $data['messages'][0]['id'], 'post_url' => null, 'response' => json_encode($data)];
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
