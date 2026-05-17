@extends('frontend.layouts.app')

@section('content')

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        <i class="las la-comments fs-48 text-primary mb-3"></i>
                        <h4 class="fw-700 mb-2">{{ translate('Customer Support Chat') }}</h4>
                        <p class="text-muted mb-0">
                            {{ translate('Use the chat widget at the bottom-right to start a conversation with our support team.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
