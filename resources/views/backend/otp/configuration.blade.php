@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{translate('OTP Configurations')}}</h1>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($otp_configurations as $otp)
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6 text-center">{{ translate(str_replace('_', ' ', ucwords($otp->type, '_'))) }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" onchange="updateActivation(this, '{{ $otp->type }}')"
                                {{ $otp->value == 1 ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Nexmo / Vonage SMS Credentials') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('update_credentials') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('API Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="NEXMO_KEY" value="{{ env('NEXMO_KEY') }}" placeholder="{{ translate('Nexmo API Key') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('API Secret') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="NEXMO_SECRET" value="{{ env('NEXMO_SECRET') }}" placeholder="{{ translate('Nexmo API Secret') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Sender ID') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="NEXMO_SENDER_ID" value="{{ env('NEXMO_SENDER_ID') }}" placeholder="{{ translate('Sender ID') }}">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Fast2SMS Credentials') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('update_credentials') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Auth Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="AUTH_KEY" value="{{ env('AUTH_KEY') }}" placeholder="{{ translate('Auth Key') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Sender ID') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="SENDER_ID" value="{{ env('SENDER_ID') }}" placeholder="{{ translate('Sender ID') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Route') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="ROUTE" value="{{ env('ROUTE') }}" placeholder="{{ translate('Route') }}">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function updateActivation(el, type) {
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
