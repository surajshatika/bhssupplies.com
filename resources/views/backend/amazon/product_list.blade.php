@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Amazon Listed Products') }}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('amazon.index') }}" class="btn btn-light btn-sm">
                <i class="las la-arrow-left"></i> {{ translate('Back') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-0 h6">{{ translate('Amazon Products') }}</h5>
        </div>
        <div class="col-auto">
            <form action="" method="GET" class="form-inline">
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">{{ translate('All Status') }}</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                    <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>{{ translate('Error') }}</option>
                </select>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Product') }}</th>
                    <th>{{ translate('Amazon SKU') }}</th>
                    <th>{{ translate('ASIN') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Last Synced') }}</th>
                    <th>{{ translate('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($amazonProducts as $ap)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if($ap->product)
                                <a href="{{ route('products.admin.edit', $ap->product_id) }}" target="_blank">
                                    {{ $ap->product->name }}
                                </a>
                            @else
                                <span class="text-muted">{{ translate('Deleted') }}</span>
                            @endif
                        </td>
                        <td><code>{{ $ap->amazon_sku }}</code></td>
                        <td>
                            @if($ap->asin)
                                <a href="https://www.amazon.ca/dp/{{ $ap->asin }}" target="_blank">{{ $ap->asin }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($ap->status == 'active')
                                <span class="badge badge-success">{{ translate('Active') }}</span>
                            @elseif($ap->status == 'pending')
                                <span class="badge badge-warning">{{ translate('Pending') }}</span>
                            @elseif($ap->status == 'error')
                                <span class="badge badge-danger" title="{{ $ap->error_message }}">{{ translate('Error') }}</span>
                            @else
                                <span class="badge badge-secondary">{{ translate('Inactive') }}</span>
                            @endif
                        </td>
                        <td>{{ $ap->last_synced_at ? $ap->last_synced_at->diffForHumans() : '—' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <form action="{{ route('amazon.sync.inventory') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="amazon_product_id" value="{{ $ap->id }}">
                                    <button class="btn btn-outline-primary" title="{{ translate('Sync Stock') }}">
                                        <i class="las la-boxes"></i>
                                    </button>
                                </form>
                                <form action="{{ route('amazon.sync.price') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="amazon_product_id" value="{{ $ap->id }}">
                                    <button class="btn btn-outline-info" title="{{ translate('Sync Price') }}">
                                        <i class="las la-dollar-sign"></i>
                                    </button>
                                </form>
                                <form action="{{ route('amazon.deactivate', $ap->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ translate('Deactivate this listing on Amazon?') }}')">
                                    @csrf
                                    <button class="btn btn-outline-danger" title="{{ translate('Deactivate') }}">
                                        <i class="las la-times-circle"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            {{ translate('No products listed on Amazon yet.') }}
                            <br>
                            <a href="{{ route('products.admin') }}">{{ translate('Go to products') }}</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($amazonProducts->hasPages())
        <div class="card-footer">
            {{ $amazonProducts->links() }}
        </div>
    @endif
</div>
@endsection
