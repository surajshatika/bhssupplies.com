<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\Seo\SearchConsole\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(protected GoogleOAuthService $oauth) {}

    public function connect(): RedirectResponse
    {
        if (!$this->oauth->isConfigured()) {
            flash(translate('Set OAuth Client ID & Secret in SEO settings before connecting Google.'))->warning();
            return redirect()->route('admin.seo-suite.settings.view');
        }

        return redirect()->away($this->oauth->authUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            flash(translate('Google sign-in was cancelled: ') . $request->input('error'))->warning();
            return redirect()->route('admin.seo-suite.settings.view');
        }

        if (!$this->oauth->verifyState($request->input('state'))) {
            flash(translate('Google OAuth state mismatch — please retry.'))->error();
            return redirect()->route('admin.seo-suite.settings.view');
        }

        $code = $request->input('code');
        if (!$code) {
            flash(translate('Google OAuth callback received no code.'))->error();
            return redirect()->route('admin.seo-suite.settings.view');
        }

        $result = $this->oauth->exchangeCodeForTokens($code);
        if (!$result['success']) {
            flash(translate('Google connection failed: ') . ($result['error'] ?? 'unknown error'))->error();
            return redirect()->route('admin.seo-suite.settings.view');
        }

        // Persist the refresh_token + connected email (refresh_token gets
        // auto-encrypted by BusinessSetting mutator).
        $this->saveSetting('seo_gsc_refresh_token', $result['refresh_token']);
        if ($result['email']) {
            $this->saveSetting('seo_gsc_connected_email', $result['email']);
        }

        // Auto-discover the first verified site if site_url isn't set yet.
        if (empty(get_setting('seo_search_console_site')) && $result['access_token']) {
            $sites = $this->oauth->listSites($result['access_token']);
            if (!empty($sites)) {
                $this->saveSetting('seo_search_console_site', $sites[0]);
            }
        }

        Cache::forget('business_settings');
        Cache::forget('seo:gsc:access_token');

        flash(translate('Google Search Console connected as ') . ($result['email'] ?: translate('your account')))->success();
        return redirect()->route('admin.seo-suite.settings.view');
    }

    public function disconnect(): RedirectResponse
    {
        try {
            BusinessSetting::whereIn('type', [
                'seo_gsc_refresh_token',
                'seo_gsc_connected_email',
            ])->delete();
            Cache::forget('business_settings');
            Cache::forget('seo:gsc:access_token');
            flash(translate('Disconnected from Google.'))->success();
        } catch (Throwable $e) {
            flash(translate('Disconnect failed: ') . $e->getMessage())->error();
        }
        return redirect()->route('admin.seo-suite.settings.view');
    }

    protected function saveSetting(string $type, $value): void
    {
        $row = BusinessSetting::where('type', $type)->first();
        if (!$row) {
            $row = new BusinessSetting();
            $row->type = $type;
        }
        $row->value = $value;
        $row->save();
    }
}
