@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')
@php
    $providers = ['openai' => 'OpenAI (ChatGPT)', 'claude' => 'Claude (Anthropic)', 'gemini' => 'Gemini (Google)', 'grok' => 'Grok (xAI)'];
@endphp

<div class="mm-hero mm-hero--seo">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <div>
                <h2>{{ translate('AI SEO Global Settings') }}</h2>
                <p>{{ translate('Configure AI providers, API keys, webmaster verifications, and automation') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-robot"></i> {{ count($providers) }} AI {{ translate('providers') }}</span>
                    <span class="mm-chip"><i class="las la-key"></i> {{ translate('API Keys') }}</span>
                    <span class="mm-chip"><i class="las la-shield-alt"></i> {{ translate('Webmaster Tools') }}</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
            <a href="{{ route('admin.seo-suite.index') }}" class="mm-btn mm-btn-light">
                <i class="las la-arrow-left"></i> {{ translate('Dashboard') }}
            </a>
            <a href="{{ route('admin.seo-suite.ai_assistant') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-robot"></i> {{ translate('AI Assistant') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

<form action="{{ route('admin.seo-suite.settings') }}" method="POST">
@csrf

<div class="row">
    {{-- Left Column --}}
    <div class="col-lg-6">

        {{-- AI Providers --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-robot mr-1 text-primary"></i>{{ translate('AI Providers & API Keys') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('Default AI Provider') }}</label>
                    <select name="default_provider" class="form-control">
                        @foreach($providers as $val => $label)
                            <option value="{{ $val }}" @if(($settings['default_provider'] ?? 'openai') === $val) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <span class="badge badge-success mr-1">OpenAI</span>
                        {{ translate('OpenAI API Key (ChatGPT / DALL-E)') }}
                    </label>
                    <input type="password" class="form-control" name="openai_api_key"
                        value="{{ $settings['openai_api_key'] ?? '' }}" placeholder="sk-...">
                    <small class="text-muted">{{ translate('Used for: Content generation, image generation, TruSEO analysis') }}</small>
                </div>

                <div class="form-group">
                    <label>
                        <span class="badge badge-primary mr-1">Claude</span>
                        {{ translate('Anthropic API Key (Claude)') }}
                    </label>
                    <input type="password" class="form-control" name="anthropic_api_key"
                        value="{{ $settings['anthropic_api_key'] ?? '' }}" placeholder="sk-ant-...">
                    <small class="text-muted">{{ translate('Used for: Advanced content, schema markup, SEO strategies') }}</small>
                </div>

                <div class="form-group">
                    <label>
                        <span class="badge badge-info mr-1">Gemini</span>
                        {{ translate('Google Gemini API Key') }}
                    </label>
                    <input type="password" class="form-control" name="gemini_api_key"
                        value="{{ $settings['gemini_api_key'] ?? '' }}" placeholder="AIza...">
                    <small class="text-muted">{{ translate('Used for: Multimodal content, structured data') }}</small>
                </div>

                <div class="form-group mb-0">
                    <label>
                        <span class="badge badge-warning mr-1">Grok</span>
                        {{ translate('Grok API Key (xAI)') }}
                    </label>
                    <input type="password" class="form-control" name="grok_api_key"
                        value="{{ $settings['grok_api_key'] ?? '' }}" placeholder="xai-...">
                    <small class="text-muted">{{ translate('Used for: Real-time SEO insights, competitive analysis') }}</small>
                </div>
            </div>
        </div>

        {{-- Search & Tracking --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-search mr-1 text-success"></i>{{ translate('Search & Tracking') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('Google Search Console Site URL') }}</label>
                    <input type="text" class="form-control" name="search_console_site"
                        value="{{ $settings['search_console_site'] ?? '' }}" placeholder="https://yourdomain.com">
                </div>
                <div class="form-group">
                    <label>
                        {{ translate('Google Custom Search API Key') }}
                        <small class="text-muted ml-1">{{ translate('(for Rank Tracker & Index Status)') }}</small>
                    </label>
                    <input type="password" class="form-control" name="google_search_api_key"
                        value="{{ $settings['google_search_api_key'] ?? '' }}" placeholder="AIza...">
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('Google Custom Search Engine ID (CX)') }}</label>
                    <input type="text" class="form-control" name="google_search_cx"
                        value="{{ $settings['google_search_cx'] ?? '' }}" placeholder="012345678901234567890:abc">
                    <small class="text-muted"><a href="https://programmablesearchengine.google.com" target="_blank">{{ translate('Get your CX from Programmable Search Engine') }}</a></small>
                </div>
            </div>
        </div>

        {{-- Search Console (one-click Google OAuth) --}}
        @php
            $googleConnected     = !empty($settings['gsc_connected_email']);
            $hasGoogleClient     = !empty($settings['gsc_client_id']) && !empty($settings['gsc_client_secret']);
            $googleCallbackUrl   = rtrim(url('/'), '/') . '/admin/seo-suite/oauth/google/callback';
        @endphp
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="lab la-google mr-1 text-info"></i>{{ translate('Google Search Console') }}</h6>
                @if ($googleConnected)
                    <span class="badge badge-success">{{ translate('Connected') }}</span>
                @elseif ($hasGoogleClient)
                    <span class="badge badge-warning">{{ translate('OAuth ready — click Connect') }}</span>
                @else
                    <span class="badge badge-secondary">{{ translate('Setup required') }}</span>
                @endif
            </div>
            <div class="card-body">
                @if ($googleConnected)
                    <div class="alert alert-success py-2 mb-3">
                        <i class="las la-check-circle mr-1"></i>
                        {{ translate('Connected as') }} <strong>{{ $settings['gsc_connected_email'] }}</strong>
                        — {{ translate('clicks/impressions/CTR/position sync every day at 04:00.') }}
                    </div>
                    <form action="{{ route('admin.seo.oauth.google.disconnect') }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-soft-danger btn-sm" onclick="return confirm('{{ translate('Disconnect Google Search Console?') }}');">
                            <i class="las la-unlink mr-1"></i> {{ translate('Disconnect Google') }}
                        </button>
                    </form>
                @elseif ($hasGoogleClient)
                    @if (!empty($settings['gsc_expected_email']))
                        <div class="alert alert-info py-2 mb-3 small">
                            <i class="las la-user-circle mr-1"></i>
                            {{ translate('Sign in with') }} <strong>{{ $settings['gsc_expected_email'] }}</strong>
                            {{ translate('when Google prompts you. This account should already own Search Console for this site.') }}
                        </div>
                    @endif
                    <p class="text-muted small mb-3">
                        {{ translate('One click and Google will ask you to authorise this site to read your Search Console data. No token paste needed.') }}
                    </p>
                    <a href="{{ route('admin.seo.oauth.google.connect') }}" class="btn btn-primary btn-sm">
                        <i class="lab la-google mr-1"></i> {{ translate('Connect Google Search Console') }}
                    </a>
                @else
                    @if (!empty($settings['gsc_expected_email']))
                        <div class="alert alert-info py-2 mb-2 small">
                            <i class="las la-user-circle mr-1"></i>
                            {{ translate('Once configured, sign in with') }} <strong>{{ $settings['gsc_expected_email'] }}</strong>.
                        </div>
                    @endif
                    <details class="mb-3">
                        <summary class="text-muted small" style="cursor:pointer;">
                            <i class="las la-info-circle mr-1"></i> {{ translate('How to get OAuth credentials (5 minutes, free)') }}
                        </summary>
                        <ol class="small text-muted mt-2 pl-3 mb-0">
                            <li>{{ translate('Open') }} <a href="https://console.cloud.google.com/apis/credentials" target="_blank">console.cloud.google.com/apis/credentials</a></li>
                            <li>{{ translate('Create a project (or pick one), then') }} → <strong>{{ translate('Create credentials') }}</strong> → <strong>OAuth client ID</strong></li>
                            <li>{{ translate('Application type:') }} <strong>{{ translate('Web application') }}</strong></li>
                            <li>{{ translate('Add this as an Authorized redirect URI:') }}
                                <code class="user-select-all d-block mt-1 bg-light p-1 small">{{ $googleCallbackUrl }}</code>
                            </li>
                            <li>{{ translate('Save → paste the Client ID & Secret below → click Save All → then come back and click Connect.') }}</li>
                        </ol>
                    </details>
                @endif

                <div class="form-group mt-3">
                    <label>{{ translate('OAuth Client ID') }}</label>
                    <input type="text" class="form-control" name="gsc_client_id"
                        value="{{ $settings['gsc_client_id'] ?? '' }}" placeholder="xxxxxxx.apps.googleusercontent.com">
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('OAuth Client Secret') }}</label>
                    <input type="password" class="form-control" name="gsc_client_secret"
                        value="{{ $settings['gsc_client_secret'] ? '••••••••••••••' : '' }}" placeholder="GOCSPX-...">
                    <small class="text-muted">{{ translate('Encrypted at rest. Leave blank to keep the existing value.') }}</small>
                </div>
            </div>
        </div>

        {{-- PageSpeed Insights --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-tachometer-alt mr-1 text-warning"></i>{{ translate('Google PageSpeed Insights') }}</h6></div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    {{ translate('Lighthouse audits run twice daily against your homepage + top categories/products. Anonymous mode works with a low quota — paste a free PSI API key for 25k requests/day.') }}
                </p>
                <div class="form-group mb-0">
                    <label>{{ translate('PageSpeed Insights API Key') }}</label>
                    <input type="password" class="form-control" name="pagespeed_api_key"
                        value="{{ $settings['pagespeed_api_key'] ? '••••••••••••••' : '' }}" placeholder="AIza...">
                    <small class="text-muted">
                        <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">{{ translate('Get a free PSI API key') }}</a>
                        — {{ translate('use the same Google Cloud project as Search Console.') }}
                    </small>
                </div>
            </div>
        </div>

        {{-- SERP rank tracking --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-chart-line mr-1 text-success"></i>{{ translate('Keyword Rank Tracking (SerpAPI)') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('SERP Provider') }}</label>
                    <select name="rank_provider" class="form-control">
                        @foreach(['serpapi' => 'SerpAPI'] as $val => $label)
                            <option value="{{ $val }}" @if(($settings['rank_provider'] ?? 'serpapi') === $val) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('SerpAPI Key') }}</label>
                    <input type="password" class="form-control" name="serpapi_key"
                        value="{{ $settings['serpapi_key'] ?? '' }}" placeholder="serpapi-...">
                    <small class="text-muted"><a href="https://serpapi.com/manage-api-key" target="_blank">{{ translate('Get your key from serpapi.com') }}</a> — {{ translate('used by seo:check-keyword-ranks every 6 hours.') }}</small>
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="col-lg-6">

        {{-- Business & Local SEO --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-store mr-1 text-warning"></i>{{ translate('Local Business Settings') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('Default Robots Rule') }}</label>
                    <input type="text" class="form-control" name="default_robots"
                        value="{{ $settings['default_robots'] ?? 'index, follow' }}">
                </div>
                <div class="form-group">
                    <label>{{ translate('Business Name') }}</label>
                    <input type="text" class="form-control" name="local_business_name"
                        value="{{ $settings['local_business_name'] ?? '' }}">
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('Business Type') }}</label>
                    <select name="local_business_type" class="form-control">
                        @foreach(['Store','LocalBusiness','Restaurant','MedicalBusiness','HomeAndConstructionBusiness','FinancialService','ProfessionalService','SportsActivityLocation','EntertainmentBusiness','FoodEstablishment'] as $type)
                            <option value="{{ $type }}" @if(($settings['local_business_type'] ?? 'Store') === $type) selected @endif>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- IndexNow --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-bolt mr-1 text-warning"></i>{{ translate('IndexNow') }}</h6></div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label>{{ translate('IndexNow API Key') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="indexnow_key"
                            value="{{ $settings['indexnow_key'] ?? '' }}" placeholder="{{ translate('32-char hex key') }}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-soft-primary" onclick="generateIndexNowKey()">{{ translate('Generate') }}</button>
                        </div>
                    </div>
                    <small class="text-muted">{{ translate('Required to submit URLs to Bing/Yandex instantly via IndexNow protocol.') }}</small>
                </div>
            </div>
        </div>

        {{-- Webmaster Verification --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0"><i class="las la-tools mr-1 text-danger"></i>{{ translate('Webmaster Verification') }}</h6>
                <a href="{{ route('admin.seo-suite.webmaster') }}" class="btn btn-xs btn-soft-primary">{{ translate('Full Manager') }}</a>
            </div>
            <div class="card-body">
                @foreach([
                    ['google',    'Google Search Console',  'seo_google_verification'],
                    ['bing',      'Bing Webmaster Tools',   'seo_bing_verification'],
                    ['yandex',    'Yandex.Webmaster',       'seo_yandex_verification'],
                    ['pinterest', 'Pinterest Verification', 'seo_pinterest_verification'],
                    ['baidu',     'Baidu Zhanzhang',        'seo_baidu_verification'],
                ] as $wm)
                <div class="form-group @if($loop->last) mb-0 @endif">
                    <label class="small">{{ $wm[1] }}</label>
                    <input type="text" class="form-control form-control-sm" name="{{ $wm[0] }}_verification"
                        value="{{ $settings[$wm[0] . '_verification'] ?? '' }}" placeholder="{{ translate('Verification code') }}">
                </div>
                @endforeach
            </div>
        </div>

        {{-- Performance --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-tachometer-alt mr-1 text-info"></i>{{ translate('Performance Optimization') }}</h6></div>
            <div class="card-body">
                <div class="form-group row mb-2">
                    <label class="col-9 col-form-label small">{{ translate('Enable HTML Minification') }}</label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="enable_minify" value="1" @if(!empty($settings['enable_minify'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <label class="col-9 col-form-label small">{{ translate('Enable Image Lazy Loading') }}</label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="enable_lazyload" value="1" @if(!empty($settings['enable_lazyload'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cloudflare --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-cloud mr-1 text-info"></i>{{ translate('Cloudflare CDN') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('API Token (Zone:Cache Purge scope)') }}</label>
                    <input type="password" class="form-control" name="cloudflare_api_token"
                        value="{{ $settings['cloudflare_api_token'] ?? '' }}" placeholder="cf-token...">
                    <small class="text-muted"><a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">{{ translate('Create a scoped token here') }}</a></small>
                </div>
                <div class="form-group">
                    <label>{{ translate('Zone ID') }}</label>
                    <input type="text" class="form-control" name="cloudflare_zone_id"
                        value="{{ $settings['cloudflare_zone_id'] ?? '' }}" placeholder="32-char hex">
                </div>
                <div class="form-group row mb-0">
                    <label class="col-9 col-form-label small">{{ translate('Auto-purge entity URL on save') }}</label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="auto_cloudflare_purge" value="1" @if(!empty($settings['auto_cloudflare_purge'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auto-actions + Budget --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-shield-alt mr-1 text-danger"></i>{{ translate('Auto Actions & Safety Caps') }}</h6></div>
            <div class="card-body">
                <div class="form-group row mb-2">
                    <label class="col-9 col-form-label small">
                        {{ translate('Master hourly SEO automation command') }}
                        <span class="d-block text-muted">{{ translate('Runs pending on-page SEO every hour and interval-gates heavier technical, rank, PageSpeed, and link checks.') }}</span>
                    </label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="master_automation_enabled" value="1" @if(!empty($settings['master_automation_enabled'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="form-group row mb-2">
                    <label class="col-9 col-form-label small">{{ translate('Auto-ping IndexNow on entity save') }}</label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="auto_indexnow" value="1" @if(!empty($settings['auto_indexnow'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="form-group row mb-2">
                    <label class="col-9 col-form-label small">
                        {{ translate('Automated technical optimization refresh') }}
                        <span class="d-block text-muted">{{ translate('Refreshes sitemap, robots.txt, llms.txt, RSS, SEO scores, and optional IndexNow pings.') }}</span>
                    </label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="auto_optimization_enabled" value="1" @if(!empty($settings['auto_optimization_enabled'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="form-group row mb-2">
                    <label class="col-9 col-form-label small">
                        {{ translate('Fully automated Canada SEO for pending URLs') }}
                        <span class="d-block text-muted">{{ translate('Runs nightly and skips already-done Product, Category, and Page SEO.') }}</span>
                    </label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="auto_seo_enabled" value="1" @if(!empty($settings['auto_seo_enabled'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ translate('Auto SEO URLs Per Run') }}</label>
                    <input type="number" min="1" max="10" class="form-control" name="auto_seo_batch_size"
                        value="{{ $settings['auto_seo_batch_size'] ?? '10' }}">
                    <small class="text-muted">{{ translate('Recommended: 5-10. The system only selects pending/non-SEO URLs unless you manually run another tool.') }}</small>
                </div>
                <div class="form-group row mb-2">
                    <label class="col-9 col-form-label small">
                        {{ translate('Fully automated AI off-page backlink campaigns') }}
                        <span class="d-block text-muted">{{ translate('Creates white-hat backlink prospect, citation, guest post, outreach, and anchor text plans.') }}</span>
                    </label>
                    <div class="col-3 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="auto_offpage_enabled" value="1" @if(!empty($settings['auto_offpage_enabled'])) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ translate('Off-Page Campaigns Per Run') }}</label>
                    <input type="number" min="1" max="10" class="form-control" name="auto_offpage_batch_size"
                        value="{{ $settings['auto_offpage_batch_size'] ?? '3' }}">
                    <small class="text-muted">{{ translate('Recommended: 2-3. These are AI campaign plans and outreach templates, not spam auto-posted links.') }}</small>
                </div>
                <div class="form-group">
                    <label>{{ translate('Competitor Websites to Outrank') }}</label>
                    <textarea class="form-control" name="competitor_urls" rows="4"
                        placeholder="https://competitor1.ca&#10;https://competitor2.ca">{{ $settings['competitor_urls'] ?? '' }}</textarea>
                    <small class="text-muted">{{ translate('One URL per line or comma-separated. Autopilot uses these only for keyword gaps, content angles, and positioning. It will not copy competitor content or mention competitor brand names.') }}</small>
                </div>
                <div class="form-group">
                    <label>{{ translate('Daily AI Budget Cap (USD)') }}</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="daily_budget_usd"
                        value="{{ $settings['daily_budget_usd'] ?? '5' }}">
                    <small class="text-muted">{{ translate('Bulk fixes that would exceed this cap are blocked. Set to 0 to disable.') }}</small>
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('AI Endpoint Rate Limit (calls/min)') }}</label>
                    <input type="number" min="1" max="300" class="form-control" name="ai_rate_per_min"
                        value="{{ $settings['ai_rate_per_min'] ?? '30' }}">
                </div>
            </div>
        </div>

        {{-- Public Endpoints Info --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="las la-globe mr-1 text-secondary"></i>{{ translate('Public SEO Endpoints') }}</h6></div>
            <div class="card-body p-2">
                @foreach(['/sitemap.xml','/video-sitemap.xml','/news-sitemap.xml','/robots.txt','/rss.xml','/llms.txt'] as $ep)
                <div class="mb-1 d-flex align-items-center">
                    <a href="{{ url($ep) }}" target="_blank" class="small text-truncate flex-grow-1">{{ url($ep) }}</a>
                    <a href="{{ url($ep) }}" target="_blank" class="btn btn-xs btn-soft-info ml-1 flex-shrink-0">View</a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <button type="submit" class="btn btn-primary px-5">
                    <i class="las la-save mr-1"></i>{{ translate('Save All SEO Settings') }}
                </button>
                <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary ml-2">{{ translate('Cancel') }}</a>
            </div>
            <div class="text-muted small">
                <i class="las la-info-circle mr-1"></i>{{ translate('API keys are encrypted and stored securely.') }}
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@section('script')
<script>
function generateIndexNowKey() {
    fetch('{{ route('admin.seo-suite.indexnow.generate_key') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    }).then(function() {
        window.location.reload();
    }).catch(function() {
        window.location.href = '{{ route('admin.seo-suite.indexnow.generate_key') }}';
    });
}
</script>
@endsection
