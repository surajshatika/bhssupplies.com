@php
    $amazonProduct = \App\Models\AmazonProduct::where('product_id', $product->id)->first();
    $hasAccount = \App\Models\AmazonAccount::where('is_active', 1)->exists();
@endphp

@if($hasAccount)
    <div class="d-inline-block ml-2">
        @if($amazonProduct)
            <div class="btn-group btn-group-sm">
                <span class="btn btn-success btn-sm disabled">
                    <i class="las la-check"></i>
                    Amazon:
                    @if($amazonProduct->status == 'active')
                        {{ translate('Active') }}
                    @elseif($amazonProduct->status == 'pending')
                        {{ translate('Pending') }}
                    @elseif($amazonProduct->status == 'error')
                        {{ translate('Error') }}
                    @else
                        {{ translate('Inactive') }}
                    @endif
                </span>
                <form action="{{ route('amazon.upload', $product->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm" title="{{ translate('Re-upload to Amazon') }}">
                        <i class="las la-sync"></i>
                    </button>
                </form>
            </div>
        @else
            <form action="{{ route('amazon.upload', $product->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="las la-amazon"></i> {{ translate('Upload to Amazon') }}
                </button>
            </form>
        @endif
    </div>
@endif
