@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Store Page Settings') }}</h1>
            <p class="text-muted mb-0">{{ translate('Configure the public') }} <a href="{{ route('promotions') }}" target="_blank">/promotions</a> {{ translate('page heading, intro and SEO.') }}</p>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_promotions.index') }}" class="btn btn-soft-secondary"><i class="las la-th-large"></i> {{ translate('Manage Blocks') }}</a>
            <a href="{{ route('promotions') }}" target="_blank" class="btn btn-soft-primary"><i class="las la-external-link-alt"></i> {{ translate('View Page') }}</a>
        </div>
    </div>
</div>

<form action="{{ route('store_promotions.settings.save') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Page Content') }}</h5></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ translate('Hero Heading') }}</label>
                        <input type="text" name="heading" class="form-control" value="{{ get_setting('store_page_heading', 'Promotions & Deals') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Hero Subheading') }}</label>
                        <input type="text" name="subheading" class="form-control" value="{{ get_setting('store_page_subheading', 'Limited-time offers, liquidation sales and wholesale pricing.') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>{{ translate('Intro Content (optional, shown above the blocks)') }}</label>
                        <textarea name="intro" class="form-control aiz-text-editor" rows="4">{{ get_setting('store_page_intro', '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('SEO') }}</h5></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ translate('Meta Title') }}</label>
                        <input type="text" name="meta_title" class="form-control" maxlength="70"
                               value="{{ get_setting('store_page_meta_title', '') }}"
                               placeholder="{{ translate('Promotions & Deals') }} | {{ get_setting('website_name') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>{{ translate('Meta Description') }}</label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="320"
                                  placeholder="{{ translate('Current promotions and wholesale deals at') }} {{ get_setting('website_name') }}.">{{ get_setting('store_page_meta_description', '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Visibility') }}</h5></div>
                <div class="card-body">
                    <div class="form-group d-flex justify-content-between align-items-center">
                        <span>{{ translate('Enable store page') }}</span>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="enabled" value="1" @if((int) get_setting('store_page_enabled', 1) === 1) checked @endif>
                            <span></span>
                        </label>
                    </div>
                    <small class="text-muted d-block mb-3">{{ translate('When off, /promotions returns 404.') }}</small>

                    <div class="form-group d-flex justify-content-between align-items-center mb-0">
                        <span>{{ translate('Show breadcrumb') }}</span>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="show_breadcrumb" value="1" @if((int) get_setting('store_page_show_breadcrumb', 1) === 1) checked @endif>
                            <span></span>
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ translate('Save Settings') }}</button>
        </div>
    </div>
</form>
@endsection
