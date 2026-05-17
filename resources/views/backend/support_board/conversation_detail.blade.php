@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3 d-flex align-items-center justify-content-between">
    <div>
        <a href="{{ route('support_board.conversations') }}" class="text-muted mr-2">
            <i class="las la-arrow-left"></i> {{ translate('Back') }}
        </a>
        <span class="h3">
            {{ translate('Conversation with') }} {{ $conversation->display_name }}
        </span>
    </div>
    @if($conversation->status == 'open')
        <a href="{{ route('support_board.close_conversation', $conversation->id) }}"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('{{ translate('Close this conversation?') }}')">
            {{ translate('Close Conversation') }}
        </a>
    @endif
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <div>
            <strong>{{ $conversation->display_name }}</strong>
            <span class="text-muted ml-2">{{ $conversation->display_email }}</span>
        </div>
        <span class="badge badge-{{ $conversation->status=='open' ? 'success':'secondary' }}">
            {{ ucfirst($conversation->status) }}
        </span>
    </div>
    <div class="card-body" id="chat-log" style="height:450px;overflow-y:auto;background:#f8f9fa">
        @foreach($conversation->messages as $msg)
            @if($msg->sender_type == 'user')
                <div class="d-flex mb-3">
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mr-2 flex-shrink-0"
                         style="width:36px;height:36px;font-size:14px">
                        <i class="las la-user"></i>
                    </div>
                    <div>
                        <div class="small text-muted mb-1">{{ $msg->sender_name }} &bull; {{ $msg->created_at->format('g:i A') }}</div>
                        <div class="bg-white rounded p-2 shadow-sm" style="max-width:500px">{{ $msg->message }}</div>
                    </div>
                </div>
            @elseif($msg->sender_type == 'ai')
                <div class="d-flex mb-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2 flex-shrink-0"
                         style="width:36px;height:36px;font-size:14px">
                        <i class="las la-robot"></i>
                    </div>
                    <div>
                        <div class="small text-muted mb-1">AI Support &bull; {{ $msg->created_at->format('g:i A') }}</div>
                        <div class="bg-primary text-white rounded p-2" style="max-width:500px">{{ $msg->message }}</div>
                    </div>
                </div>
            @else
                <div class="d-flex mb-3 justify-content-end">
                    <div class="text-right">
                        <div class="small text-muted mb-1">{{ $msg->sender_name }} (Admin) &bull; {{ $msg->created_at->format('g:i A') }}</div>
                        <div class="bg-success text-white rounded p-2" style="max-width:500px">{{ $msg->message }}</div>
                    </div>
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center ml-2 flex-shrink-0"
                         style="width:36px;height:36px;font-size:14px">
                        <i class="las la-user-shield"></i>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @if($conversation->status == 'open')
        <div class="card-footer">
            <form id="admin-reply-form">
                @csrf
                <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                <div class="input-group">
                    <input type="text" name="message" id="admin-message" class="form-control"
                        placeholder="{{ translate('Type your reply...') }}" autocomplete="off">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            <i class="las la-paper-plane"></i> {{ translate('Send') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
@section('script')
<script>
var chatLog = document.getElementById('chat-log');
chatLog.scrollTop = chatLog.scrollHeight;

document.getElementById('admin-reply-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var msg = document.getElementById('admin-message').value.trim();
    if (!msg) return;
    $.post('{{ route("support_board.admin_reply") }}', $(form).serialize(), function(data) {
        if (data.success) {
            document.getElementById('admin-message').value = '';
            var html = '<div class="d-flex mb-3 justify-content-end">' +
                '<div class="text-right">' +
                '<div class="small text-muted mb-1">You (Admin)</div>' +
                '<div class="bg-success text-white rounded p-2" style="max-width:500px">' + msg + '</div>' +
                '</div>' +
                '<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center ml-2 flex-shrink-0" style="width:36px;height:36px">' +
                '<i class="las la-user-shield"></i></div></div>';
            chatLog.insertAdjacentHTML('beforeend', html);
            chatLog.scrollTop = chatLog.scrollHeight;
        }
    });
});
</script>
@endsection