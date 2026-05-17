@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">{{ translate('Social Media Post Logs') }}</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.social.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Dashboard') }}
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="row gutters-16 mb-4">
    <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center"><div class="h3 mb-0">{{ $stats['total'] }}</div><small class="text-muted">{{ translate('Total') }}</small></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center"><div class="h3 mb-0 text-success">{{ $stats['success'] }}</div><small class="text-muted">{{ translate('Success') }}</small></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center"><div class="h3 mb-0 text-danger">{{ $stats['failed'] }}</div><small class="text-muted">{{ translate('Failed') }}</small></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center"><div class="h3 mb-0 text-info">{{ $stats['today'] }}</div><small class="text-muted">{{ translate('Today') }}</small></div></div></div>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="platform" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{ translate('All Platforms') }}</option>
                    @foreach($platforms as $slug => $info)
                        <option value="{{ $slug }}" {{ request('platform') === $slug ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-control aiz-selectpicker">
                    <option value="">{{ translate('All Status') }}</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary mr-2">{{ translate('Filter') }}</button>
                <a href="{{ route('admin.social.logs') }}" class="btn btn-soft-secondary">{{ translate('Reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Platform') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Trigger') }}</th>
                        <th>{{ translate('AI Provider') }}</th>
                        <th>{{ translate('Content Preview') }}</th>
                        <th>{{ translate('Posted At') }}</th>
                        <th>{{ translate('Post URL') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>
                            <i class="{{ config('social_media.platforms.'.$log->platform.'.icon', 'las la-share-alt') }} mr-1"></i>
                            {{ ucfirst($log->platform) }}
                        </td>
                        <td><span class="badge {{ $log->status_badge }}">{{ $log->status }}</span></td>
                        <td><span class="badge badge-light">{{ $log->trigger }}</span></td>
                        <td>{{ $log->ai_provider ?: '—' }}</td>
                        <td>
                            <span class="d-inline-block text-truncate" style="max-width:200px;" title="{{ $log->content_sent }}">
                                {{ $log->content_sent }}
                            </span>
                            @if($log->response && $log->status === 'failed')
                                <a href="#" class="text-danger ml-1" data-toggle="tooltip" title="{{ $log->response }}">
                                    <i class="las la-exclamation-circle"></i>
                                </a>
                            @endif
                        </td>
                        <td>{{ $log->posted_at ? $log->posted_at->format('M d, H:i') : '—' }}</td>
                        <td>
                            @if($log->post_url)
                                <a href="{{ $log->post_url }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                    <i class="las la-external-link-alt"></i>
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="las la-inbox" style="font-size:40px;"></i>
                            <br>{{ translate('No logs found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
