@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Amazon Sync Logs') }}</h1>
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
            <h5 class="mb-0 h6">{{ translate('Sync Activity Log') }}</h5>
        </div>
        <div class="col-auto">
            <form action="" method="GET" class="form-inline">
                <select name="action" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">{{ translate('All Actions') }}</option>
                    <option value="upload" {{ request('action') == 'upload' ? 'selected' : '' }}>{{ translate('Upload') }}</option>
                    <option value="price_sync" {{ request('action') == 'price_sync' ? 'selected' : '' }}>{{ translate('Price Sync') }}</option>
                    <option value="inventory_sync" {{ request('action') == 'inventory_sync' ? 'selected' : '' }}>{{ translate('Inventory Sync') }}</option>
                    <option value="order_import" {{ request('action') == 'order_import' ? 'selected' : '' }}>{{ translate('Order Import') }}</option>
                </select>
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">{{ translate('All Status') }}</option>
                    <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>{{ translate('Success') }}</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ translate('Failed') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                </select>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('Product') }}</th>
                    <th>{{ translate('Action') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Error') }}</th>
                    <th>{{ translate('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>
                            @if($log->product)
                                <a href="{{ route('products.admin.edit', $log->product_id) }}" target="_blank" class="text-truncate d-block" style="max-width:200px">
                                    {{ $log->product->name }}
                                </a>
                            @else
                                <span class="text-muted">{{ translate('N/A') }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $labels = ['upload' => 'primary', 'price_sync' => 'info', 'inventory_sync' => 'warning', 'order_import' => 'success', 'deactivate' => 'secondary'];
                            @endphp
                            <span class="badge badge-{{ $labels[$log->action] ?? 'secondary' }}">{{ $log->action }}</span>
                        </td>
                        <td>
                            @if($log->status == 'success')
                                <span class="badge badge-success">{{ translate('Success') }}</span>
                            @elseif($log->status == 'failed')
                                <span class="badge badge-danger">{{ translate('Failed') }}</span>
                            @else
                                <span class="badge badge-warning">{{ translate('Pending') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($log->error_message)
                                <span class="text-danger small text-truncate d-block" style="max-width:250px" title="{{ $log->error_message }}">
                                    {{ $log->error_message }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ translate('No logs yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
