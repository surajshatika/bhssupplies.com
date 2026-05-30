<?php

namespace App\Utility;

use App\Models\OtpConfiguration;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\SendSmsService;
use Illuminate\Support\Facades\Cache;

class SmsUtility
{
    public static function phone_number_verification($user = '')
    {
        return self::sendTemplate('phone_number_verification', $user->phone, [
            '[[code]]' => $user->verification_code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'phone_verification');
    }

    public static function password_reset($user = '')
    {
        return self::sendTemplate('password_reset', $user->phone, [
            '[[code]]' => $user->verification_code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'password_reset');
    }

    public static function order_placement($phone = '', $order = '')
    {
        return self::sendTemplate('order_placement', $phone, [
            '[[order_code]]' => $order->code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'order_placement');
    }

    public static function delivery_status_change($phone, $order)
    {
        $delivery_status = translate(ucfirst(str_replace('_', ' ', $order->delivery_status)));
        return self::sendTemplate('delivery_status_change', $phone, [
            '[[delivery_status]]' => $delivery_status,
            '[[order_code]]' => $order->code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'delivery_status');
    }

    public static function payment_status_change($phone = '', $order = '')
    {
        return self::sendTemplate('payment_status_change', $phone, [
            '[[payment_status]]' => translate(ucfirst($order->payment_status)),
            '[[order_code]]' => $order->code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'payment_status');
    }

    public static function assign_delivery_boy($phone = '', $code = '')
    {
        return self::sendTemplate('assign_delivery_boy', $phone, [
            '[[order_code]]' => $code,
            '[[site_name]]' => env('APP_NAME'),
        ], 'assign_delivery_boy');
    }

    public static function sendTemplate(string $identifier, string $phone, array $replacements = [], string $context = 'general', ?string $fallbackBody = null)
    {
        $smsTemplate = self::template($identifier);
        if ($smsTemplate && (int) $smsTemplate->status !== 1) {
            return false;
        }

        $body = $smsTemplate ? (string) $smsTemplate->sms_body : $fallbackBody;
        if (!$body) {
            return false;
        }

        $body = str_replace(array_keys($replacements), array_values($replacements), $body);

        return (new SendSmsService())->sendSMS($phone, env('APP_NAME'), $body, $smsTemplate->template_id ?? null, [
            'context' => $context,
        ]);
    }

    protected static function template(string $identifier): ?SmsTemplate
    {
        return Cache::remember('sms_template_' . $identifier, 300, function () use ($identifier) {
            return SmsTemplate::where('identifier', $identifier)->first();
        });
    }
}
