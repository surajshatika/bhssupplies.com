@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Amazon Orders') }}</h1>
        </div>
        <div class="col text-right">
            <form action="{{ route('amazon.orders.import') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-primary btn-sm">
                    <i class="las la-sync"></i> {{ translate('Import New Orders') }}
                </button>
            </form>
            <a href="{{ route('amazon.index') }}" class="btn btn-light btn-sm ml-2">
                <i class="las la-arrow-left"></i> {{ translate('Back') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col">
            <h5 class="mb-0 h6">{{ translate('Amazon Canada Orders') }}</h5>
        </div>
        <div class="col-auto">
            <form action="" method="GET" class="form-inline">
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">{{ translate('All Status') }}</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                    <option value="Unshipped" {{ request('status') == 'Unshipped' ? 'selected' : '' }}>{{ translate('Unshipped') }}</option>
                    <option value="Shipped" {{ request('status') == 'Shipped' ? 'selected' : '' }}>{{ translate('Shipped') }}</option>
                    <option value="Canceled" {{ request('status') == 'Canceled' ? 'selected' : '' }}>{{ translate('Canceled') }}</option>
                </select>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('Amazon Order ID') }}</th>
                    <th>{{ translate('Buyer') }}</th>
                    <th>{{ translate('Amount') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Items') }}</th>
                    <th>{{ translate('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><code>{{ $order->amazon_order_id }}</code></td>
                        <td>{{ $order->buyer_name ?: '—' }}</td>
                        <td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            @php
                                $badgeMap = ['Shipped' => 'success', 'Unshipped' => 'warning', 'Canceled' => 'danger', 'Pending' => 'secondary'];
                                $badge = $badgeMap[$order->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $badge }}">{{ $order->status }}</span>
                        </td>
                        <td>{{ is_array($order->order_items) ? count($order->order_items) : '—' }}</td>
                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            {{ translate('No Amazon orders imported yet.') }}
                            <br>
                            <small>{{ translate('Click "Import New Orders" to fetch from Amazon.') }}</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
