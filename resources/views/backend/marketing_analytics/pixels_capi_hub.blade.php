@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

@php
    // Per-channel config descriptors — defines which env keys go in which channel card.
    $channelDefs = [
        'tiktok' => [
            'name'     => 'TikTok',
            'gradient' => 'linear-gradient(135deg,#000 0%,#FF0050 50%,#00F2EA 100%)',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#fff" width="24" height="24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.79 20.1a6.34 6.34 0 0 0 10.86-4.43V8.45a8.18 8.18 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.83-.88z"/></svg>',
            'desc'     => 'TikTok Pixel + Events API for Purchase, AddToCart, ViewContent, Search.',
            'fields'   => [
                ['env' => 'TIKTOK_PIXEL_ID',     'label' => 'Pixel ID',      'placeholder' => 'C1XXXXXXXXXXXXXXXXXX'],
                ['env' => 'TIKTOK_ACCESS_TOKEN', 'label' => 'Access Token',  'placeholder' => '••••••••', 'secret' => true],
            ],
            'docs' => 'https://business-api.tiktok.com/portal/docs?id=1741601162187777',
        ],
        'pinterest' => [
            'name'     => 'Pinterest',
            'gradient' => 'linear-gradient(135deg,#E60023 0%,#BD081C 100%)',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#fff" width="24" height="24"><path d="M12 0a12 12 0 0 0-4.37 23.17c-.1-.94-.2-2.43.04-3.47.22-.93 1.4-5.94 1.4-5.94s-.36-.72-.36-1.78c0-1.66.97-2.9 2.17-2.9 1.02 0 1.51.77 1.51 1.69 0 1.03-.66 2.57-1 4-.28 1.2.6 2.17 1.78 2.17 2.13 0 3.77-2.25 3.77-5.49 0-2.87-2.06-4.88-5.01-4.88-3.41 0-5.42 2.56-5.42 5.21 0 1.03.4 2.13.89 2.74.1.12.11.22.08.34-.09.36-.29 1.2-.33 1.36-.05.22-.17.27-.4.16-1.5-.7-2.43-2.88-2.43-4.65 0-3.78 2.75-7.27 7.92-7.27 4.16 0 7.39 2.97 7.39 6.93 0 4.13-2.6 7.46-6.22 7.46-1.21 0-2.36-.63-2.75-1.38l-.75 2.85c-.27 1.04-1 2.36-1.49 3.16A12 12 0 1 0 12 0z"/></svg>',
            'desc'     => 'Pinterest Tag + Conversions API. Most relevant for visual / craft / décor inventory.',
            'fields'   => [
                ['env' => 'PINTEREST_AD_ACCOUNT_ID', 'label' => 'Ad Account ID', 'placeholder' => '549764100000'],
                ['env' => 'PINTEREST_TAG_ID',        'label' => 'Pinterest Tag ID', 'placeholder' => '2612345678910'],
                ['env' => 'PINTEREST_ACCESS_TOKEN',  'label' => 'Access Token',  'placeholder' => '••••••••', 'secret' => true],
            ],
            'docs' => 'https://developers.pinterest.com/docs/conversions/conversion-management/',
        ],
        'snapchat' => [
            'name'     => 'Snapchat',
            'gradient' => 'linear-gradient(135deg,#FFFC00 0%,#000 100%)',
            'textColor'=> '#000',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#000" width="24" height="24"><path d="M12.166 2.234c.49 0 1.025.11 1.59.328 1.726.665 2.83 2.234 3.027 4.31.087.957.13 1.92.022 2.872 0 .076-.01.153-.01.218 0 .197.022.295.087.305.219.022.547-.218.83-.39.197-.12.448-.218.74-.218.142 0 .295.022.448.054.546.131.945.602.945 1.082v.022c-.01.349-.197.83-.92 1.116-.142.054-.273.087-.426.142-.197.054-.546.142-.633.36-.054.108-.043.273.011.491.011.022.328 1.05 1.34 1.945.305.262.622.49 1.027.665l.043.022c.142.054.295.131.4.218.339.262.382.6.218.99-.197.502-.74.84-1.59 1.05-.087.022-.207.054-.339.131-.218.142-.207.371-.392.84-.087.218-.219.448-.426.633-.218.207-.524.317-.83.317-.197 0-.426-.054-.622-.108-.262-.087-.546-.131-.83-.131-.142 0-.295.022-.426.054-.633.142-1.116.546-1.726 1.038-.622.502-1.276 1.027-2.36 1.027-.043 0-.087 0-.142-.011-.054.011-.087.011-.142.011-1.082 0-1.737-.524-2.36-1.027-.6-.49-1.082-.896-1.726-1.038-.131-.032-.273-.054-.426-.054-.317 0-.6.054-.83.131-.197.054-.426.108-.622.108-.339 0-.622-.131-.83-.317-.207-.197-.339-.426-.426-.644-.197-.49-.197-.7-.392-.84-.131-.077-.262-.108-.339-.131-.85-.219-1.394-.546-1.59-1.05-.164-.39-.131-.74.218-.99.108-.087.262-.164.4-.218l.043-.022c.4-.175.722-.4 1.027-.665 1.005-.896 1.34-1.92 1.34-1.945.054-.218.065-.382.011-.49-.087-.219-.426-.306-.633-.36-.142-.054-.273-.087-.426-.142-.85-.339-.92-.83-.92-1.094v-.022c.011-.49.4-.96.945-1.094.142-.032.295-.054.448-.054.273 0 .524.087.74.207.295.175.622.4.83.39.054 0 .087-.087.087-.295 0-.087-.011-.153-.011-.218-.108-.96-.054-1.92.022-2.872.197-2.087 1.301-3.645 3.038-4.31.546-.218 1.082-.328 1.59-.328l.5-.011z"/></svg>',
            'desc'     => 'Snap Pixel + Conversions API. Strong for younger demographic + ads spend.',
            'fields'   => [
                ['env' => 'SNAPCHAT_PIXEL_ID',     'label' => 'Pixel ID',     'placeholder' => 'XXXXXXXX-YYYY-ZZZZ-AAAA-BBBBBBBBBBBB'],
                ['env' => 'SNAPCHAT_ACCESS_TOKEN', 'label' => 'Access Token', 'placeholder' => '••••••••', 'secret' => true],
            ],
            'docs' => 'https://marketingapi.snapchat.com/docs/conversion.html',
        ],
        'linkedin' => [
            'name'     => 'LinkedIn',
            'gradient' => 'linear-gradient(135deg,#0A66C2 0%,#004182 100%)',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#fff" width="24" height="24"><path d="M19 0h-14C2.24 0 0 2.24 0 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5V5c0-2.76-2.24-5-5-5zM7.12 20.45H3.56V9h3.56v11.45zM5.34 7.5a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zm15.11 12.95H16.9v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29z"/></svg>',
            'desc'     => 'LinkedIn Insight Tag + Conversions API. Best for B2B / contractor + trade audiences.',
            'fields'   => [
                ['env' => 'LINKEDIN_PARTNER_ID',           'label' => 'Partner ID',         'placeholder' => '1234567'],
                ['env' => 'LINKEDIN_ACCESS_TOKEN',         'label' => 'OAuth Access Token', 'placeholder' => '••••••••', 'secret' => true],
                ['env' => 'LINKEDIN_CONVERSION_RULE_URN', 'label' => 'Default Conversion URN', 'placeholder' => 'urn:lla:llaPartnerConversion:123456789'],
            ],
            'docs' => 'https://learn.microsoft.com/en-us/linkedin/marketing/integrations/ads-reporting/conversions-api',
        ],
        'twitter' => [
            'name'     => 'X (Twitter)',
            'gradient' => 'linear-gradient(135deg,#000 0%,#1DA1F2 100%)',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#fff" width="24" height="24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>',
            'desc'     => 'X Pixel + CAPI. Mostly used by tech / professional services advertisers.',
            'fields'   => [
                ['env' => 'TWITTER_PIXEL_ID',     'label' => 'Pixel ID',      'placeholder' => 'oXXXX'],
                ['env' => 'TWITTER_BEARER_TOKEN', 'label' => 'Bearer Token',  'placeholder' => '••••••••', 'secret' => true],
            ],
            'docs' => 'https://developer.twitter.com/en/docs/twitter-ads-api/measurement/api-reference/conversions',
        ],
        'google_ads' => [
            'name'     => 'Google Ads',
            'gradient' => 'linear-gradient(135deg,#4285F4 0%,#FBBC04 50%,#EA4335 100%)',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#fff" width="24" height="24"><path d="M14.05 2.6l-2.92 5.05 5.7 9.87 2.92-5.05L14.05 2.6zM21 17l-5.4-9.35L13 12.4l5.4 9.35L21 17zM2.7 17.95L8.1 8.6l2.92 5.05L5.62 23 2.7 17.95z"/></svg>',
            'desc'     => 'Google Ads Enhanced Conversions (server-side gclid upload).',
            'fields'   => [
                ['env' => 'GOOGLE_ADS_CUSTOMER_ID',     'label' => 'Customer ID (10 digits)', 'placeholder' => '1234567890'],
                ['env' => 'GOOGLE_ADS_DEVELOPER_TOKEN', 'label' => 'Developer Token',         'placeholder' => '••••••••', 'secret' => true],
                ['env' => 'GOOGLE_ADS_OAUTH_TOKEN',     'label' => 'OAuth Access Token',      'placeholder' => '••••••••', 'secret' => true],
                ['env' => 'GOOGLE_ADS_CONV_PURCHASE',          'label' => 'Conv. Action ID — Purchase',          'placeholder' => '7890123456'],
                ['env' => 'GOOGLE_ADS_CONV_ADD_TO_CART',       'label' => 'Conv. Action ID — Add to Cart',       'placeholder' => '7890123457'],
                ['env' => 'GOOGLE_ADS_CONV_BEGIN_CHECKOUT',    'label' => 'Conv. Action ID — Begin Checkout',    'placeholder' => '7890123458'],
                ['env' => 'GOOGLE_ADS_CONV_LEAD',              'label' => 'Conv. Action ID — Lead',              'placeholder' => '7890123459'],
                ['env' => 'GOOGLE_ADS_CONV_SIGN_UP',           'label' => 'Conv. Action ID — Sign Up',           'placeholder' => '7890123460'],
                ['env' => 'GOOGLE_ADS_CONV_SUBSCRIBE',         'label' => 'Conv. Action ID — Subscribe',         'placeholder' => '7890123461'],
            ],
            'docs' => 'https://developers.google.com/google-ads/api/docs/conversions/upload-clicks',
        ],
        'clarity' => [
            'name'     => 'Microsoft Clarity',
            'gradient' => 'linear-gradient(135deg,#FF8800 0%,#FF4F00 100%)',
            'icon'     => '<svg viewBox="0 0 24 24" fill="#fff" width="24" height="24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4" fill="#FF4F00"/></svg>',
            'desc'     => 'Heatmaps + session recordings (frontend script tag only — no CAPI needed).',
            'fields'   => [
                ['env' => 'CLARITY_PROJECT_ID', 'label' => 'Project ID', 'placeholder' => 'abcdefgh12'],
            ],
            'docs' => 'https://learn.microsoft.com/en-us/clarity/setup-and-installation/',
        ],
    ];
@endphp

<div class="mm-hero" style="background: linear-gradient(135deg,#7B61FF 0%,#0EA5E9 50%,#34A853 100%);">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <div>
                <h2>{{ translate('Pixels & Conversions API Hub') }}</h2>
                <p>{{ translate('One panel — all 7 ad channels. Server-side CAPI fans out via queue, deduplicated with browser pixels.') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    @php $enabledCount = count(array_filter($channels, fn($c) => $c->isEnabled())); @endphp
                    <span class="mm-chip"><i class="las la-broadcast-tower"></i> {{ $enabledCount }}/{{ count($channels) }} {{ translate('active') }}</span>
                    <span class="mm-chip"><i class="las la-shield-alt"></i> {{ translate('Server-side') }}</span>
                    <span class="mm-chip"><i class="las la-bolt"></i> {{ translate('Queue async') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('pixels_capi.update') }}" method="POST">
    @csrf

    <div class="row">
    @foreach($channels as $channel)
        @php
            $slug = $channel->slug();
            $def  = $channelDefs[$slug] ?? null;
            if (!$def) continue;
            $textColor = $def['textColor'] ?? '#fff';
            $isOn      = $channel->isEnabled();
        @endphp

        <div class="col-md-6 mb-4">
            <div class="mm-card h-100">
                {{-- Header band --}}
                <div style="background: {{ $def['gradient'] }}; color: {{ $textColor }}; padding: 1rem 1.25rem; display:flex; align-items:center; justify-content:space-between;">
                    <div class="d-flex align-items-center" style="gap:.7rem;">
                        <div style="width:42px;height:42px;background:rgba(255,255,255,.18);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            {!! $def['icon'] !!}
                        </div>
                        <div>
                            <h6 class="mb-0" style="color:{{ $textColor }}; font-weight:700;">{{ $def['name'] }}</h6>
                            <small style="color:{{ $textColor }}; opacity:.85;">{{ $channel->name() }}</small>
                        </div>
                    </div>
                    <label class="aiz-switch aiz-switch-success mb-0" title="{{ translate('Enable channel') }}">
                        <input type="checkbox" name="{{ $slug }}_capi_enabled" value="1" {{ $isOn ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>

                {{-- Body --}}
                <div class="mm-card-body">
                    <p class="text-muted small mb-3">{{ $def['desc'] }}</p>

                    @foreach($def['fields'] as $field)
                        <div class="form-group mb-2">
                            <input type="hidden" name="types[]" value="{{ $field['env'] }}">
                            <label class="small text-muted mb-1">{{ $field['label'] }}</label>
                            <input
                                type="{{ !empty($field['secret']) ? 'password' : 'text' }}"
                                class="form-control form-control-sm"
                                name="{{ $field['env'] }}"
                                value="{{ env($field['env']) }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                autocomplete="off">
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span style="font-size:12px;">
                            @if($isOn)
                                <span class="mm-dot ok"></span> <strong class="text-success">{{ translate('Active') }}</strong>
                            @else
                                <span class="mm-dot warn"></span> <span class="text-muted">{{ translate('Disabled') }}</span>
                            @endif
                        </span>
                        @if(!empty($def['docs']))
                            <a href="{{ $def['docs'] }}" target="_blank" class="small text-primary">
                                <i class="las la-external-link-alt"></i> {{ translate('Docs') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>

    {{-- GDPR Consent Banner toggle --}}
    <div class="mm-card mb-4" style="background: linear-gradient(135deg,#1e293b,#0f172a); color:#fff; border:none;">
        <div class="mm-card-body d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h6 class="mb-1" style="color:#fff;"><i class="las la-shield-alt"></i> {{ translate('GDPR / CCPA Consent Banner') }}</h6>
                <small style="color:rgba(255,255,255,.7);">
                    {{ translate('Show a cookie consent banner with Google Consent Mode v2. When OFF, all tracking runs unconditionally.') }}
                </small>
            </div>
            <label class="aiz-switch aiz-switch-success mb-0 ml-3" title="{{ translate('Enable consent banner') }}">
                <input type="checkbox" name="marketing_consent_enabled" value="1"
                       {{ (int) get_setting('marketing_consent_enabled') === 1 ? 'checked' : '' }}>
                <span class="slider round"></span>
            </label>
        </div>
    </div>

    <div class="text-right mb-5">
        <button type="submit" class="btn btn-primary px-4">
            <i class="las la-save"></i> {{ translate('Save All Channels') }}
        </button>
    </div>
</form>
@endsection
