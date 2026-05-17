@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{translate('Send Bulk SMS')}}</h1>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Send SMS') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('sms.send') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('Phone Number') }}</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="phone" placeholder="{{ translate('Phone Number') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('Message') }}</label>
                            </div>
                            <div class="col-md-9">
                                <textarea class="form-control" name="message" rows="4" placeholder="{{ translate('Message') }}" required></textarea>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Send') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
