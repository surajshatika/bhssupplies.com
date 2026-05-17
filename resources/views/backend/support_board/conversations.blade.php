@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3 d-flex align-items-center justify-content-between">
    <h1 class="h3">{{ translate('Support Conversations') }}</h1>
    <a href="{{ route('support_board.settings') }}" class="btn btn-sm btn-outline-primary">
        <i class="las la-cog"></i> {{ translate('Settings') }}
    </a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Customer') }}</th>
                    <th>{{ translate('Last Message') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Date') }}</th>
                    <th>{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conv)
                    <tr>
                        <td>{{ $conv->id }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $conv->display_name }}</div>
                            <small class="text-muted">{{ $conv->display_email }}</small>
                        </td>
                        <td>
                            @if($conv->lastMessage)
                                <span class="text-truncate d-inline-block" style="max-width:250px">
                                    @if($conv->lastMessage->sender_type == 'ai')
                                        <i class="las la-robot text-primary"></i>
                                    @elseif($conv->lastMessage->sender_type == 'admin')
                                        <i class="las la-user-shield text-success"></i>
                                    @else
                                        <i class="las la-user text-secondary"></i>
                                    @endif
                                    {{ $conv->lastMessage->message }}
                                </span>
                                @if($conv->unreadCount() > 0)
                                    <span class="badge badge-danger ml-1">{{ $conv->unreadCount() }} new</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $conv->status == 'open' ? 'success' : 'secondary' }}">
                                {{ ucfirst($conv->status) }}
                            </span>
                        </td>
                        <td>{{ $conv->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('support_board.conversation', $conv->id) }}" class="btn btn-sm btn-primary">
                                <i class="las la-eye"></i> {{ translate('View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="las la-comments fs-48 text-muted d-block mb-2"></i>
                            {{ translate('No conversations yet') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($conversations->hasPages())
        <div class="card-footer">
            {{ $conversations->links() }}
        </div>
    @endif
</div>
@endsection