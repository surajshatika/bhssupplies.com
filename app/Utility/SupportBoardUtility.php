<?php

namespace App\Utility;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class SupportBoardUtility
{
    public static function isActivated(): bool
    {
        return addon_is_activated('active_ecommerce_support_board')
            && get_setting('support_board_status') == 1;
    }

    public static function getUrl(): string
    {
        return rtrim(get_setting('support_board_url') ?? '', '/');
    }

    public static function getSecret(): string
    {
        return get_setting('support_board_secret') ?? '';
    }

    /**
     * Call Support Board REST API.
     * Support Board uses a flat POST endpoint: {url}/api.php
     * with `function` + `data[]` parameters and `token` auth.
     */
    public static function callApi(string $function, array $data = []): array
    {
        $url    = self::getUrl();
        $secret = self::getSecret();

        if (!$url || !$secret) {
            return ['success' => false, 'message' => 'Support Board URL or Secret Key is not configured.'];
        }

        $payload = array_merge(['function' => $function, 'token' => $secret], $data);

        try {
            $response = Http::timeout(15)->asForm()->post($url . '/api.php', $payload);
            $body     = $response->json();
            return $body ?? ['success' => false, 'message' => 'Empty response from Support Board.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Register or update a user in Support Board.
     * Called on every chat open for logged-in users.
     */
    public static function syncUser(User $user): array
    {
        $userType = $user->user_type; // customer | admin | staff | seller

        $sbUserType = match ($userType) {
            'admin'  => 'admin',
            'staff'  => 'agent',
            'seller' => 'agent',
            default  => 'user',
        };

        return self::callApi('add-user', [
            'first_name' => $user->name,
            'email'      => $user->email,
            'user_type'  => $sbUserType,
            'extra'      => json_encode([
                'active_ecommerce_id'        => (string) $user->id,
                'active_ecommerce_user_type' => $userType,
            ]),
        ]);
    }

    /**
     * Import all admin and staff users into Support Board as agents/admins.
     */
    public static function importAdmins(): array
    {
        $users = User::whereIn('user_type', ['admin', 'staff'])->get();

        if ($users->isEmpty()) {
            return ['success' => false, 'message' => 'No admin or staff users found.'];
        }

        $imported = 0;
        foreach ($users as $user) {
            $result = self::syncUser($user);
            if (!isset($result['error'])) {
                $imported++;
            }
        }

        return [
            'success' => true,
            'message' => $imported . ' admin/staff user(s) imported into Support Board successfully.',
        ];
    }

    /**
     * Sync all active sellers into Support Board as agents.
     */
    public static function syncSellers(): array
    {
        $sellers = User::where('user_type', 'seller')
            ->where('email_verified_at', '!=', null)
            ->get();

        if ($sellers->isEmpty()) {
            return ['success' => false, 'message' => 'No active sellers found.'];
        }

        $synced = 0;
        foreach ($sellers as $seller) {
            $result = self::callApi('add-user', [
                'first_name' => $seller->name,
                'email'      => $seller->email,
                'user_type'  => 'agent',
                'extra'      => json_encode([
                    'active_ecommerce_id'        => (string) $seller->id,
                    'active_ecommerce_user_type' => 'seller',
                ]),
            ]);
            if (!isset($result['error'])) {
                $synced++;
            }
        }

        return [
            'success' => true,
            'message' => $synced . ' seller(s) synced to Support Board successfully.',
        ];
    }

    /**
     * Generate the JavaScript identity token for the current logged-in user.
     * Passed to the SB chat widget so Support Board recognises the visitor.
     */
    public static function getChatUserPayload(): array
    {
        if (!auth()->check()) {
            return [];
        }

        $user = auth()->user();

        return [
            'user_id'    => (string) $user->id,
            'first_name' => $user->name,
            'email'      => $user->email,
            'user_type'  => $user->user_type === 'customer' ? 'user' : 'agent',
        ];
    }

    /**
     * Determine if the chat widget should be visible for the current request.
     */
    public static function chatIsVisible(): bool
    {
        if (!self::isActivated()) {
            return false;
        }

        $visibility = get_setting('support_board_chat_visibility') ?? 'all';

        if ($visibility === 'logged_in') {
            return auth()->check();
        }

        return true;
    }
}
