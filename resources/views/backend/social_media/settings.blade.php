@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">{{ translate('Social Media — API Settings') }}</h1>
            <p class="text-muted mb-0">{{ translate('Configure API credentials, AI providers, and platform toggles') }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.social.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Dashboard') }}
            </a>
        </div>
    </div>
</div>

<form action="{{ route('admin.social.settings.save') }}" method="POST">
    @csrf

    {{-- Global Toggle --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">{{ translate('Global Settings') }}</h6></div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <strong>{{ translate('Enable Global Auto-Post') }}</strong>
                    <div class="text-muted small">{{ translate('Master switch — disabling this stops all automation regardless of platform toggles') }}</div>
                </div>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" name="social_global_autopost_enabled" value="1"
                        {{ $settings['social_global_autopost_enabled'] ? 'checked' : '' }}>
                    <span></span>
                </label>
            </div>
            <div class="form-group mb-0">
                <label>{{ translate('Default AI Provider') }}</label>
                <select name="social_default_ai_provider" class="form-control aiz-selectpicker">
                    @foreach($aiProviders as $key => $label)
                        <option value="{{ $key }}" {{ ($settings['social_default_ai_provider'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- AI Provider Keys --}}
    <div class="card mb-4" id="ai-settings">
        <div class="card-header"><h6 class="mb-0"><i class="las la-brain mr-1"></i>{{ translate('AI Provider API Keys') }}</h6></div>
        <div class="card-body">
            <div class="row">
                {{-- ChatGPT --}}
                <div class="col-md-4 mb-3">
                    <div class="card border h-100">
                        <div class="card-body">
                            <h6 class="font-weight-700"><i class="las la-robot text-success mr-1"></i> ChatGPT (OpenAI)</h6>
                            <div class="form-group">
                                <label class="small">{{ translate('API Key') }}</label>
                                <input type="password" name="social_ai_openai_key" class="form-control form-control-sm"
                                    value="{{ $settings['social_ai_openai_key'] }}" placeholder="sk-...">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small">{{ translate('Model') }}</label>
                                <input type="text" name="social_ai_openai_model" class="form-control form-control-sm"
                                    value="{{ $settings['social_ai_openai_model'] ?: 'gpt-4o' }}" placeholder="gpt-4o">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Claude --}}
                <div class="col-md-4 mb-3">
                    <div class="card border h-100">
                        <div class="card-body">
                            <h6 class="font-weight-700"><i class="las la-cloud text-primary mr-1"></i> Claude (Anthropic)</h6>
                            <div class="form-group">
                                <label class="small">{{ translate('API Key') }}</label>
                                <input type="password" name="social_ai_claude_key" class="form-control form-control-sm"
                                    value="{{ $settings['social_ai_claude_key'] }}" placeholder="sk-ant-...">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small">{{ translate('Model') }}</label>
                                <input type="text" name="social_ai_claude_model" class="form-control form-control-sm"
                                    value="{{ $settings['social_ai_claude_model'] ?: 'claude-sonnet-4-6' }}" placeholder="claude-sonnet-4-6">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Grok --}}
                <div class="col-md-4 mb-3">
                    <div class="card border h-100">
                        <div class="card-body">
                            <h6 class="font-weight-700"><i class="lab la-twitter text-dark mr-1"></i> Grok (xAI)</h6>
                            <div class="form-group">
                                <label class="small">{{ translate('API Key') }}</label>
                                <input type="password" name="social_ai_grok_key" class="form-control form-control-sm"
                                    value="{{ $settings['social_ai_grok_key'] }}" placeholder="xai-...">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small">{{ translate('Model') }}</label>
                                <input type="text" name="social_ai_grok_model" class="form-control form-control-sm"
                                    value="{{ $settings['social_ai_grok_model'] ?: 'grok-beta' }}" placeholder="grok-beta">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Platform Credentials --}}
    @php
    $platformGroups = [
        'global' => [
            'twitter'   => ['Twitter / X', 'lab la-twitter', [['twitter_api_key','API Key'],['twitter_api_secret','API Secret'],['twitter_access_token','Access Token'],['twitter_access_token_secret','Access Token Secret']]],
            'facebook'  => ['Facebook Page', 'lab la-facebook-f', [['facebook_page_access_token','Page Access Token'],['facebook_page_id','Page ID']]],
            'instagram' => ['Instagram Business', 'lab la-instagram', [['instagram_graph_api_token','Graph API Token'],['instagram_business_account_id','Business Account ID']]],
            'linkedin'  => ['LinkedIn', 'lab la-linkedin-in', [['linkedin_access_token','Access Token'],['linkedin_organization_urn','Organization URN (urn:li:organization:...)']]],
            'youtube'   => ['YouTube Community', 'lab la-youtube', [['youtube_oauth_token','OAuth Token'],['youtube_channel_id','Channel ID']]],
            'telegram'  => ['Telegram Channel', 'lab la-telegram', [['telegram_bot_token','Bot Token'],['telegram_chat_id','Channel/Chat ID (@channel)']]],
            'whatsapp'  => ['WhatsApp Business', 'lab la-whatsapp', [['whatsapp_business_api_token','Business API Token'],['whatsapp_phone_number_id','Phone Number ID'],['whatsapp_channel_id','Channel ID']]],
        ],
        'canada' => [
            'tiktok'    => ['TikTok Business', 'lab la-tiktok', [['tiktok_access_token','Access Token'],['tiktok_open_id','Open ID']]],
            'pinterest' => ['Pinterest', 'lab la-pinterest', [['pinterest_access_token','Access Token'],['pinterest_board_id','Board ID']]],
            'reddit'    => ['Reddit', 'lab la-reddit', [['reddit_access_token','Access Token'],['reddit_subreddit','Subreddit (e.g. r/canada)']]],
            'snapchat'  => ['Snapchat Business', 'lab la-snapchat', [['snapchat_access_token','Access Token'],['snapchat_ad_account_id','Ad Account ID']]],
            'discord'   => ['Discord', 'lab la-discord', [['discord_webhook_url','Webhook URL']]],
        ],
    ];
    @endphp

    @foreach($platformGroups as $groupLabel => $groupPlatforms)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                @if($groupLabel === 'global')
                    <span class="badge badge-success mr-2">{{ translate('Global') }}</span>
                @else
                    <span class="badge badge-info mr-2">{{ translate('Canada + Global') }}</span>
                @endif
                {{ translate('Platform Credentials') }}
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($groupPlatforms as $slug => [$label, $icon, $fields])
                <div class="col-md-6 col-lg-4 mb-4" id="{{ $slug }}">
                    <div class="card border h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 font-weight-700">
                                    <i class="{{ $icon }} mr-1" style="font-size:16px;"></i>{{ $label }}
                                </h6>
                                <label class="aiz-switch aiz-switch-primary mb-0">
                                    <input type="checkbox" name="social_{{ $slug }}_enabled" value="1"
                                        {{ !empty($settings['social_'.$slug.'_enabled']) ? 'checked' : '' }}>
                                    <span></span>
                                </label>
                            </div>
                            @foreach($fields as [$key, $fieldLabel])
                            <div class="form-group mb-2">
                                <label class="small text-muted">{{ translate($fieldLabel) }}</label>
                                <input type="{{ in_array($key, ['telegram_chat_id','facebook_page_id','youtube_channel_id','instagram_business_account_id','linkedin_organization_urn','whatsapp_channel_id','reddit_subreddit','tiktok_open_id','pinterest_board_id']) ? 'text' : 'password' }}"
                                    name="{{ $key }}" class="form-control form-control-sm"
                                    value="{{ $settings[$key] ?? '' }}" autocomplete="off">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach

    <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="las la-save mr-1"></i>{{ translate('Save All Settings') }}
        </button>
    </div>
</form>
@endsection
