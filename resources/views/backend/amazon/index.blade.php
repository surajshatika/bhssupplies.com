@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="mm-hero mm-hero--amazon">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div>
                <h2>{{ translate('Amazon Integration') }}</h2>
                <p>{{ translate('Sync your catalog to Amazon SP-API — inventory, pricing, orders all automated.') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><span class="mm-dot {{ $account ? 'ok' : 'warn' }}"></span>
                        @if($account) {{ translate('Connected') }}: {{ $account->name }} @else {{ translate('Not connected') }} @endif
                    </span>
                    <span class="mm-chip"><i class="las la-cube"></i> {{ $stats['active'] }} {{ translate('active listings') }}</span>
                    <span class="mm-chip"><i class="las la-shopping-bag"></i> {{ $stats['orders'] }} {{ translate('orders') }}</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
            <a href="{{ route('amazon.products') }}" class="mm-btn mm-btn-light">
                <i class="las la-boxes"></i> {{ translate('Products') }}
            </a>
            <a href="{{ route('amazon.orders') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-shopping-bag"></i> {{ translate('Orders') }}
            </a>
            <a href="{{ route('amazon.logs') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-history"></i> {{ translate('Logs') }}
            </a>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="row mb-4">
    <div class="col-6 col-lg mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['total']) }}</h3>
            <div class="mm-stat-label">{{ translate('Total Listed') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['active']) }}</h3>
            <div class="mm-stat-label">{{ translate('Active') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['pending']) }}</h3>
            <div class="mm-stat-label">{{ translate('Pending') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['error']) }}</h3>
            <div class="mm-stat-label">{{ translate('Errors') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['orders']) }}</h3>
            <div class="mm-stat-label">{{ translate('Orders') }}</div>
        </div>
    </div>
</div>

{{-- Quick Links --}}
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <a href="{{ route('amazon.products') }}" class="mm-tile">
            <div class="mm-tile-icon mm-tint-orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div>
                <h6>{{ translate('Manage Products') }}</h6>
                <p>{{ translate('Push listings to Amazon SP-API') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('amazon.category-mapping') }}" class="mm-tile">
            <div class="mm-tile-icon mm-tint-yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div>
                <h6>{{ translate('Category Mapping') }}</h6>
                <p>{{ translate('Match local categories to Amazon nodes') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('amazon.orders') }}" class="mm-tile">
            <div class="mm-tile-icon mm-tint-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <div>
                <h6>{{ translate('Amazon Orders') }}</h6>
                <p>{{ translate('Auto-imported every 30 min') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('amazon.logs') }}" class="mm-tile">
            <div class="mm-tile-icon mm-tint-cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
                <h6>{{ translate('Sync Logs') }}</h6>
                <p>{{ translate('Inventory + listing operations') }}</p>
            </div>
        </a>
    </div>
</div>

{{-- Account Settings --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Amazon Account Settings') }}</h5>
    </div>
    <div class="card-body">
        @if(!$account)
            <div class="alert alert-warning">
                <i class="las la-exclamation-triangle"></i>
                {{ translate('No Amazon account configured. Add your credentials below.') }}
            </div>
        @else
            <div class="alert alert-success">
                <i class="las la-check-circle"></i>
                {{ translate('Account:') }} <strong>{{ $account->name }}</strong> — Seller ID: {{ $account->seller_id }}
            </div>
        @endif

        <form action="{{ route('amazon.settings.save') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('Account Name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $account->name ?? 'BHS Supplies CA' }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('Seller ID') }}</label>
                        <input type="text" name="seller_id" class="form-control" value="{{ $account->seller_id ?? '' }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('Marketplace ID') }}</label>
                        <input type="text" name="marketplace_id" class="form-control" value="{{ $account->marketplace_id ?? 'A2EUQ1WTGCTBG2' }}" required>
                        <small class="text-muted">{{ translate('Canada = A2EUQ1WTGCTBG2') }}</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('LWA Client ID') }}</label>
                        <input type="text" name="lwa_client_id" class="form-control" value="{{ $account->lwa_client_id ?? '' }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('LWA Client Secret') }}</label>
                        <input type="password" name="lwa_client_secret" class="form-control" value="{{ $account->lwa_client_secret ?? '' }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('Refresh Token') }}</label>
                        <input type="password" name="refresh_token" class="form-control" value="">
                        <small class="text-muted">{{ translate('Leave blank to keep existing token') }}</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('AWS Access Key') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                        <input type="text" name="aws_access_key" class="form-control" value="{{ $account->aws_access_key ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ translate('AWS Secret Key') }} <span class="text-muted">({{ translate('optional') }})</span></label>
                        <input type="password" name="aws_secret_key" class="form-control" value="">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="las la-save"></i> {{ translate('Save Settings') }}
            </button>
        </form>
    </div>
</div>
@endsection
