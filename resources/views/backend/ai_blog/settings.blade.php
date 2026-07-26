@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">{{ translate('AI Blog — Settings') }}</h1>
            <p class="text-muted mb-0">{{ translate('Configure automation, AI provider, competitor keywords, and social posting') }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.ai-blog.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back') }}
            </a>
        </div>
    </div>
</div>

<div class="alert alert-info d-flex align-items-start">
    <i class="las la-info-circle la-2x mr-2"></i>
    <div>
        <strong>{{ translate('Full Blog Blueprint') }}</strong>
        <div class="small">{{ translate('AI blogs now generate full blog records: title, category, slug, 1300x650 banner, short description, full HTML description, and SEO metadata.') }}</div>
    </div>
</div>

<form action="{{ route('admin.ai-blog.settings.save') }}" method="POST">
    @csrf

    {{-- Automation Settings --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="las la-robot mr-1"></i>{{ translate('Automation') }}</h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <strong>{{ translate('Enable Daily AI Blog Generation') }}</strong>
                            <div class="text-muted small">{{ translate('Auto-generates blog posts on schedule (runs via cron/scheduler)') }}</div>
                        </div>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="ai_blog_enabled" value="1"
                                {{ !empty($settings['ai_blog_enabled']) ? 'checked' : '' }}>
                            <span></span>
                        </label>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <strong>{{ translate('Auto-Publish Generated Posts') }}</strong>
                            <div class="text-muted small">{{ translate('Publish immediately without review') }}</div>
                        </div>
                        <label class="aiz-switch aiz-switch-warning mb-0">
                            <input type="checkbox" name="ai_blog_auto_publish" value="1"
                                {{ !empty($settings['ai_blog_auto_publish']) ? 'checked' : '' }}>
                            <span></span>
                        </label>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ translate('Auto-Post to Social Media') }}</strong>
                            <div class="text-muted small">{{ translate('Share each new blog on enabled platforms') }}</div>
                        </div>
                        <label class="aiz-switch aiz-switch-primary mb-0">
                            <input type="checkbox" name="ai_blog_post_to_social" value="1"
                                {{ !empty($settings['ai_blog_post_to_social']) ? 'checked' : '' }}>
                            <span></span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('Posts Per Day') }}</label>
                        <select name="ai_blog_posts_per_day" class="form-control aiz-selectpicker">
                            @for($i=1;$i<=5;$i++)
                                <option value="{{ $i }}" {{ ($settings['ai_blog_posts_per_day'] ?? 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Schedule Time (daily)') }}</label>
                        <input type="time" name="ai_blog_schedule_time" class="form-control"
                            value="{{ $settings['ai_blog_schedule_time'] ?: '08:00' }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>{{ translate('Target Country') }}</label>
                        <input type="text" name="ai_blog_target_country" class="form-control"
                            value="{{ $settings['ai_blog_target_country'] ?: 'Canada' }}"
                            placeholder="Canada">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Full Blog Blueprint --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="las la-layer-group mr-1"></i>{{ translate('Full Blog SEO Blueprint') }}</h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Primary Locations') }}</label>
                        <input type="text" name="ai_blog_primary_locations" class="form-control"
                            value="{{ $settings['ai_blog_primary_locations'] ?: 'Mississauga, Brampton, Toronto' }}">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>{{ translate('Secondary Locations') }}</label>
                        <input type="text" name="ai_blog_secondary_locations" class="form-control"
                            value="{{ $settings['ai_blog_secondary_locations'] ?: 'Etobicoke, Vaughan, Oakville, Scarborough, Markham, North York, Burlington' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Conversion Intents') }}</label>
                        <input type="text" name="ai_blog_conversion_intents" class="form-control"
                            value="{{ $settings['ai_blog_conversion_intents'] ?: 'Trade Account, Leave a Review' }}">
                    </div>
                </div>
            </div>
            <div class="row gutters-16">
                @foreach(['Blog Title','Category/Create New','Slug','Banner 1300x650','Short Description','Description','Meta Title','Meta Image','Meta Description','Meta Keywords'] as $item)
                    <div class="col-sm-6 col-md-3 mb-2">
                        <div class="border rounded px-3 py-2 small bg-light">
                            <i class="las la-check-circle text-success mr-1"></i>{{ translate($item) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- AI Provider --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="las la-brain mr-1"></i>{{ translate('AI Provider & Content Settings') }}</h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('AI Provider') }}</label>
                        <select name="ai_blog_provider" class="form-control aiz-selectpicker">
                            @foreach($aiProviders as $k => $lbl)
                                <option value="{{ $k }}" {{ ($settings['ai_blog_provider'] ?? '') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ translate('API keys managed in Social Media → API Settings') }}</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Content Tone') }}</label>
                        <select name="ai_blog_tone" class="form-control aiz-selectpicker">
                            @foreach($tones as $k => $lbl)
                                <option value="{{ $k }}" {{ ($settings['ai_blog_tone'] ?? '') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Target Word Count') }}</label>
                        <select name="ai_blog_word_count" class="form-control aiz-selectpicker">
                            @foreach([800,1000,1200,1500,2000,2500] as $wc)
                                <option value="{{ $wc }}" {{ ($settings['ai_blog_word_count'] ?? 1200) == $wc ? 'selected' : '' }}>~{{ $wc }} words</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Pexels API Key') }} <small class="text-muted">({{ translate('optional, for stock photos') }})</small></label>
                        <input type="password" name="ai_blog_pexels_api_key" class="form-control"
                            value="{{ $settings['ai_blog_pexels_api_key'] }}"
                            placeholder="{{ translate('Leave blank to use product images') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Competitor Keywords --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="las la-search mr-1"></i>{{ translate('Competitor Keyword Research') }}</h6></div>
        <div class="card-body">
            <div class="form-group">
                <label>{{ translate('Competitor Website URLs') }}</label>
                <textarea name="ai_blog_competitor_urls" class="form-control" rows="4"
                    placeholder="https://competitor1.com&#10;https://competitor2.com&#10;https://competitor3.com">{{ $settings['ai_blog_competitor_urls'] ?? '' }}</textarea>
                <small class="text-muted">
                    {{ translate('One URL per line or comma-separated. AI will research these domains to find keywords your competitors rank for, then target the same keywords in your blogs.') }}
                </small>
            </div>
        </div>
    </div>

    {{-- Social Platforms for Blog Posts --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="las la-share-alt mr-1"></i>{{ translate('Social Platforms for Blog Posts') }}</h6></div>
        <div class="card-body">
            <p class="text-muted small mb-3">{{ translate('Select which platforms to post new blog announcements to. Platform must also be enabled in Social Media → Settings.') }}</p>
            @php
                $raw = $settings['ai_blog_social_platforms'] ?? [];
                $savedPlatforms = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
            @endphp
            <div class="row">
                @foreach($platforms as $slug => $info)
                <div class="col-6 col-md-3 col-lg-2 mb-2">
                    <label class="d-flex align-items-center" style="cursor:pointer;">
                        <input type="checkbox" name="ai_blog_social_platforms[]" value="{{ $slug }}" class="mr-2"
                            {{ in_array($slug, $savedPlatforms) ? 'checked' : '' }}>
                        <i class="{{ $info['icon'] }} mr-1"></i>
                        <small>{{ $info['label'] }}</small>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="las la-save mr-1"></i>{{ translate('Save Settings') }}
        </button>
    </div>
</form>
@endsection
