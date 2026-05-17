@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{translate('African Payment Gateway Configurations')}}</h1>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <p class="text-muted">{{translate('African Payment Gateway addon is not installed.')}}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
