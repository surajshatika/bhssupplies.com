<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\OtpConfiguration;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsService
{
    public function sendSMS($to, $from, $text, $template_id = null, array $options = []): bool
    {
        $phone = $this->normalizePhone((string) $to, $options['country_code'] ?? null);
        $context = (string) ($options['context'] ?? 'general');
        $providers = $this->activeProviders();

        if ($phone === '') {
            $this->writeLog($phone, null, $template_id, $context, 'failed', 1, $text, null, 'Invalid phone number', 0);
            return false;
        }

        if (empty($providers)) {
            $this->writeLog($phone, null, $template_id, $context, 'failed', 1, $text, null, 'No active SMS provider configured', 0);
            return false;
        }

        foreach ($providers as $attempt => $provider) {
            $provider = (string) $provider;
            $otpClass = __NAMESPACE__ . '\\OTP\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $provider)));
            $attemptNo = $attempt + 1;
            $start = microtime(true);

            if (!class_exists($otpClass)) {
                $this->writeLog($phone, $provider, $template_id, $context, 'failed', $attemptNo, $text, null, 'Provider class missing', 0);
                continue;
            }

            try {
                $response = (new $otpClass)->send($phone, $from, $text, $template_id);
                $durationMs = (int) round((microtime(true) - $start) * 1000);

                if ($this->isSuccessfulResponse($response)) {
                    $this->writeLog($phone, $provider, $template_id, $context, 'sent', $attemptNo, $text, $response, null, $durationMs);
                    return true;
                }

                $this->writeLog($phone, $provider, $template_id, $context, 'failed', $attemptNo, $text, $response, 'Provider returned a failed response', $durationMs);
            } catch (Throwable $e) {
                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $this->writeLog($phone, $provider, $template_id, $context, 'failed', $attemptNo, $text, null, $e->getMessage(), $durationMs);
                Log::warning('[SMS] provider failed', [
                    'provider' => $provider,
                    'phone' => $this->maskPhone($phone),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }

    public function normalizePhone(string $phone, ?string $countryCode = null): string
    {
        $phone = trim($phone);
        if ($phone === '') return '';

        $phone = preg_replace('/[^\d+]/', '', $phone) ?: '';
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (!str_starts_with($phone, '+')) {
            $prefix = $countryCode ?: (string) env('SMS_DEFAULT_COUNTRY_CODE', env('DEFAULT_COUNTRY_CODE', '+1'));
            $prefix = '+' . ltrim(preg_replace('/[^\d+]/', '', $prefix) ?: '+1', '+');
            $phone = $prefix . ltrim($phone, '0');
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $phone) ? $phone : '';
    }

    protected function activeProviders(): array
    {
        return OtpConfiguration::where('value', 1)
            ->pluck('type')
            ->filter()
            ->values()
            ->all();
    }

    protected function isSuccessfulResponse($response): bool
    {
        if ($response === true || $response === 1 || $response === '1') {
            return true;
        }

        if ($response === false || $response === null || $response === '') {
            return false;
        }

        $text = is_scalar($response) ? (string) $response : json_encode($response);
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            $flat = strtolower(json_encode($decoded));
            if (str_contains($flat, 'error') || str_contains($flat, 'failed') || str_contains($flat, 'invalid')) {
                return false;
            }
            if (array_key_exists('success', $decoded)) {
                return (bool) $decoded['success'];
            }
            if (array_key_exists('status', $decoded) && in_array(strtolower((string) $decoded['status']), ['0', 'false', 'failed', 'error'], true)) {
                return false;
            }
            return true;
        }

        $lower = strtolower($text);
        return !str_contains($lower, 'error')
            && !str_contains($lower, 'failed')
            && !str_contains($lower, 'invalid');
    }

    protected function writeLog(string $phone, ?string $provider, $templateId, string $context, string $status, int $attempt, string $message, $response, ?string $error, int $durationMs): void
    {
        try {
            SmsLog::create([
                'phone' => $this->maskPhone($phone),
                'provider' => $provider,
                'template_id' => $templateId,
                'context' => $context,
                'status' => $status,
                'attempt' => $attempt,
                'message_preview' => $this->maskMessage($message),
                'response' => $this->shorten($response),
                'error' => $this->shorten($error),
                'duration_ms' => $durationMs,
                'sent_at' => $status === 'sent' ? now() : null,
            ]);
        } catch (Throwable $e) {
            Log::debug('[SMS] log write failed: ' . $e->getMessage());
        }
    }

    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 6) return $phone;
        return substr($phone, 0, 4) . str_repeat('*', max(0, strlen($phone) - 7)) . substr($phone, -3);
    }

    protected function maskMessage(string $message): string
    {
        $message = preg_replace('/\b\d{4,8}\b/', '******', $message) ?: $message;
        return mb_substr($message, 0, 500);
    }

    protected function shorten($value): ?string
    {
        if ($value === null) return null;
        $value = is_scalar($value) ? (string) $value : json_encode($value);
        return mb_substr((string) $value, 0, 2000);
    }
}
