<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Traits\PreventDemoModeChanges;
use Throwable;

class BusinessSetting extends Model
{
    use PreventDemoModeChanges;

    protected $guarded = [];

    /**
     * Setting `type` patterns whose `value` should be encrypted at rest.
     * Matched case-insensitively against the full type name.
     */
    protected static array $encryptedTypePatterns = [
        '/_api_key$/i',
        '/_secret$/i',
        '/_refresh_token$/i',
        '/^seo_openai_api_key$/i',
        '/^seo_anthropic_api_key$/i',
        '/^seo_gemini_api_key$/i',
        '/^seo_grok_api_key$/i',
        '/^seo_serpapi_key$/i',
        '/^seo_dataforseo_key$/i',
        '/^seo_gsc_refresh_token$/i',
        '/^seo_gsc_client_secret$/i',
        '/^seo_cloudflare_api_token$/i',
    ];

    protected const ENC_PREFIX = 'enc::v1::';

    public function getValueAttribute($value)
    {
        if (!is_string($value) || $value === '' || !Str::startsWith($value, self::ENC_PREFIX)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::ENC_PREFIX)));
        } catch (Throwable $e) {
            // Decryption failed — log once, return raw so the app keeps running.
            logger()->warning('BusinessSetting decrypt failed', [
                'type'  => $this->type ?? '?',
                'error' => $e->getMessage(),
            ]);
            return $value;
        }
    }

    public function setValueAttribute($value): void
    {
        // Hard safety wrapper — under no circumstance should setting the value
        // attribute on this model break the saving flow. Outermost catch logs
        // and falls back to a plain assignment.
        try {
            if (!$this->shouldEncrypt($this->type ?? '', $value)) {
                $this->attributes['value'] = $value;
                return;
            }

            if (is_string($value) && Str::startsWith($value, self::ENC_PREFIX)) {
                $this->attributes['value'] = $value;
                return;
            }

            try {
                $this->attributes['value'] = self::ENC_PREFIX . Crypt::encryptString((string) $value);
            } catch (Throwable $e) {
                logger()->warning('BusinessSetting encrypt failed; storing raw', [
                    'type'  => $this->type ?? '?',
                    'error' => $e->getMessage(),
                ]);
                $this->attributes['value'] = $value;
            }
        } catch (Throwable $e) {
            logger()->warning('BusinessSetting mutator outer fault — storing raw', [
                'type'  => $this->type ?? '?',
                'error' => $e->getMessage(),
            ]);
            $this->attributes['value'] = is_scalar($value) ? (string) $value : (is_array($value) ? json_encode($value) : '');
        }
    }

    protected function shouldEncrypt(string $type, $value): bool
    {
        if ($type === '' || $value === null || $value === '') {
            return false;
        }
        foreach (self::$encryptedTypePatterns as $pattern) {
            if (preg_match($pattern, $type)) {
                return true;
            }
        }
        return false;
    }
}
