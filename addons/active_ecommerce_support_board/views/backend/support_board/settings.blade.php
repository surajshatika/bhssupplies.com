@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="align-items-center">
        <h1 class="h3">{{ translate('Support Board Settings') }}</h1>
    </div>
</div>

<div class="row">

    {{-- Status & Visibility --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Chat Status') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group row align-items-center">
                    <label class="col-md-6 col-form-label">{{ translate('Enable Support Board Chat') }}</label>
                    <div class="col-md-6 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox"
                                onchange="updateActivation(this, 'support_board_status')"
                                @if(get_setting('support_board_status') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
                <div class="form-group row align-items-center mb-0">
                    <label class="col-md-6 col-form-label">{{ translate('Enable Tickets Area') }}</label>
                    <div class="col-md-6 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox"
                                onchange="updateActivation(this, 'support_board_tickets_status')"
                                @if(get_setting('support_board_tickets_status') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Chat Visibility') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('support_board.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label>{{ translate('Show Chat To') }}</label>
                        <select name="support_board_chat_visibility" class="form-control">
                            <option value="all" @if(get_setting('support_board_chat_visibility') == 'all') selected @endif>
                                {{ translate('All Visitors (guests + logged-in)') }}
                            </option>
                            <option value="logged_in" @if(get_setting('support_board_chat_visibility') == 'logged_in') selected @endif>
                                {{ translate('Logged-in Users Only') }}
                            </option>
                        </select>
                        <small class="text-muted">{{ translate('Tickets are always restricted to logged-in users regardless of this setting.') }}</small>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Connection --}}
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Support Board Connection') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('support_board.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
                            {{ translate('Support Board URL') }}
                            <small class="d-block text-muted">{{ translate('Full URL where Support Board is installed') }}</small>
                        </label>
                        <div class="col-md-9">
                            <input type="url" name="support_board_url"
                                class="form-control"
                                placeholder="https://yourdomain.com/supportboard/"
                                value="{{ get_setting('support_board_url') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">
                            {{ translate('Secret Key') }}
                            <small class="d-block text-muted">{{ translate('Found in Support Board → Settings → Miscellaneous → Secret Key') }}</small>
                        </label>
                        <div class="col-md-9">
                            <input type="text" name="support_board_secret"
                                class="form-control"
                                placeholder="{{ translate('Paste your Support Board secret key') }}"
                                value="{{ get_setting('support_board_secret') }}">
                        </div>
                    </div>
                    <div class="form-group row mb-0">
                        <div class="col-md-9 offset-md-3">
                            <button type="submit" class="btn btn-primary">{{ translate('Save Connection Settings') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Import Admins --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Import Admin & Staff') }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    {{ translate('One-click import of all admin and staff users into Support Board. Admin accounts become Support Board admins; staff accounts become agents.') }}
                </p>
                <a href="{{ route('support_board.import_admins') }}"
                   class="btn btn-primary"
                   onclick="return confirm('{{ translate('Import all admin and staff users to Support Board?') }}')">
                    <i class="las la-upload mr-1"></i> {{ translate('Import Admins & Staff') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Sync Sellers --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Sync Sellers') }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    {{ translate('Register all active sellers in Support Board as agents. Sellers will only see conversations from their own product pages.') }}
                </p>
                <a href="{{ route('support_board.sync_sellers') }}"
                   class="btn btn-primary"
                   onclick="return confirm('{{ translate('Sync all active sellers to Support Board?') }}')">
                    <i class="las la-sync mr-1"></i> {{ translate('Sync Sellers') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Frontend Links --}}
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Frontend URLs') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-group row align-items-center">
                    <label class="col-md-3">{{ translate('Chat Page') }}</label>
                    <div class="col-md-9">
                        <a href="{{ route('support_board.chat') }}" target="_blank" class="text-primary">
                            {{ route('support_board.chat') }}
                        </a>
                    </div>
                </div>
                <div class="form-group row align-items-center mb-0">
                    <label class="col-md-3">{{ translate('Tickets Page') }}</label>
                    <div class="col-md-9">
                        <a href="{{ route('support_board.tickets') }}" target="_blank" class="text-primary">
                            {{ route('support_board.tickets') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@section('script')
<script>
    function updateActivation(el, type) {
        var value = $(el).is(':checked') ? 1 : 0;
        $.post('{{ route('support_board.activation') }}', {
            _token: '{{ csrf_token() }}',
            type: type,
            value: value
        }, function (data) {
            if (data == '1') {
                AIZ.plugins.notify('success', '{{ translate('Settings updated successfully') }}');
            } else {
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        });
    }
</script>
@endsection
