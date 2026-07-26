@extends('frontend.layouts.app')

@section('content')
    <section class="py-5 gry-bg">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <h1 class="h5 mb-0">{{ translate('Verify OTP') }}</h1>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('validate-otp-code') }}">
                                @csrf
                                <input type="hidden" name="phone" value="{{ $phone }}">
                                <div class="form-group">
                                    <label>{{ translate('Verification Code') }}</label>
                                    <input type="text" name="verification_code" class="form-control" inputmode="numeric" autocomplete="one-time-code" required autofocus>
                                </div>
                                <button class="btn btn-primary btn-block" type="submit">{{ translate('Verify & Login') }}</button>
                            </form>
                            <div class="text-center mt-3">
                                <a href="{{ route('resend-otp', $phone) }}" class="text-reset">{{ translate('Resend OTP') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
