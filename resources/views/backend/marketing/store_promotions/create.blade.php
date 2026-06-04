@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Add Promotion Block') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_promotions.index') }}" class="btn btn-soft-secondary"><i class="las la-arrow-left"></i> {{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('New Block') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('store_promotions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('backend.marketing.store_promotions._form', ['promotion' => null])
                    <div class="text-right mt-3">
                        <button type="submit" class="btn btn-primary">{{ translate('Save Block') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
