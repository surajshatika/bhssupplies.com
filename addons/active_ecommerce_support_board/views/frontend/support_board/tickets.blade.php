@extends('frontend.layouts.app')

@section('content')

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h4 class="fw-700 mb-4">{{ translate('Support Tickets') }}</h4>

                @php
                    $sb_url = rtrim(get_setting('support_board_url'), '/');
                @endphp

                @if($sb_url)
                    {{-- Support Board Tickets Area embed --}}
                    <div id="sb-tickets-area">
                        <script>
                            var SB_TICKETS = true;
                        </script>
                        <script src="{{ $sb_url }}/js/min/jquery.min.js"></script>
                        <script id="sbinit" src="{{ $sb_url }}/js/main.js"></script>
                    </div>
                @else
                    <div class="alert alert-warning">
                        {{ translate('Tickets area is not configured yet. Please set the Support Board URL in the admin settings.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection
