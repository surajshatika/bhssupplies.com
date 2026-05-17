@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Amazon Category Mapping') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('amazon.index') }}" class="btn btn-light btn-sm">
                <i class="las la-arrow-left"></i> {{ translate('Back') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Map your categories to Amazon product types') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="alert alert-info m-3">
            <strong>{{ translate('How it works:') }}</strong>
            {{ translate('For each category, enter the Amazon Browse Node ID, a human-readable name, and the Amazon Product Type (e.g. HVAC_PART, PLUMBING_SUPPLY). These are required for Amazon listings.') }}
        </div>

        <form action="{{ route('amazon.category-mapping.save') }}" method="POST">
            @csrf
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Website Category') }}</th>
                        <th>{{ translate('Amazon Category ID') }}</th>
                        <th>{{ translate('Amazon Category Name') }}</th>
                        <th>{{ translate('Amazon Product Type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        @include('backend.amazon.partials.category_row', ['category' => $category, 'mappings' => $mappings, 'depth' => 0])
                        @foreach($category->categories as $sub)
                            @include('backend.amazon.partials.category_row', ['category' => $sub, 'mappings' => $mappings, 'depth' => 1])
                            @foreach($sub->categories as $subsub)
                                @include('backend.amazon.partials.category_row', ['category' => $subsub, 'mappings' => $mappings, 'depth' => 2])
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div class="p-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <i class="las la-save"></i> {{ translate('Save Mappings') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
