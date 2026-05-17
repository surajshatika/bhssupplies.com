@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{translate('OTP Login Configuration')}}</h1>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 text-center">{{ translate('Login With OTP') }}</h5>
                </div>
                <div class="card-body text-center">
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="checkbox" onchange="updateOtpSettings(this, 'login_with_otp')"
                            {{ get_setting('login_with_otp') == 1 ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 text-center">{{ translate('OTP For Registration') }}</h5>
                </div>
                <div class="card-body text-center">
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="checkbox" onchange="updateOtpSettings(this, 'otp_for_register')"
                            {{ get_setting('otp_for_register') == 1 ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 text-center">{{ translate('Phone Number Verification') }}</h5>
                </div>
                <div class="card-body text-center">
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="checkbox" onchange="updateOtpSettings(this, 'customer_phone_verification')"
                            {{ get_setting('customer_phone_verification') == 1 ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function updateOtpSettings(el, type) {
            $.post('{{ route('otp_configurations.update.activation') }}', {
                _token: '{{ csrf_token() }}',
                type: type,
                value: el.checked ? 1 : 0
            }, function(data) {
                if (data == 1) {
                    toastr.success('{{ translate('Settings updated successfully') }}');
                }
            });
        }
    </script>
@endsection
