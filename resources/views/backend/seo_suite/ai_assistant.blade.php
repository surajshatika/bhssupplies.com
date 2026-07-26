@extends('backend.layouts.app')

@section('content')
@php
    $providers = ['openai' => 'OpenAI (ChatGPT)', 'claude' => 'Claude (Anthropic)', 'gemini' => 'Gemini (Google)', 'grok' => 'Grok (xAI)'];
    $contexts  = ['general' => 'General SEO', 'on_page' => 'On-Page SEO', 'off_page' => 'Off-Page SEO', 'technical' => 'Technical SEO', 'local' => 'Local SEO'];
    $quickActions = [
        'audit_checklist'     => ['icon' => 'la-clipboard-check', 'label' => 'SEO Audit Checklist',   'color' => 'primary'],
        'content_ideas'       => ['icon' => 'la-lightbulb',       'label' => 'Content Ideas',          'color' => 'success'],
        'competitor_strategy' => ['icon' => 'la-chess',           'label' => 'Competitor Strategy',    'color' => 'info'],
        'technical_tips'      => ['icon' => 'la-code',            'label' => 'Technical SEO Tips',     'color' => 'warning'],
        'local_seo_tips'      => ['icon' => 'la-map-marker',      'label' => 'Local SEO Tips',         'color' => 'danger'],
        'link_building'       => ['icon' => 'la-link',            'label' => 'Link Building Ideas',    'color' => 'secondary'],
    ];
@endphp
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-robot mr-2 text-primary"></i>{{ translate('AI SEO Assistant') }}</h1>
            <p class="text-muted mb-0">{{ translate('Chat with AI to get expert SEO advice, strategies, and recommendations.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

<div class="row">
    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Settings') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="small">{{ translate('AI Provider') }}</label>
                    <select id="ai-provider" class="form-control form-control-sm">
                        @foreach($providers as $val => $label)
                            <option value="{{ $val }}" @if(($settings['default_provider'] ?? 'openai') === $val) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="small">{{ translate('Context') }}</label>
                    <select id="ai-context" class="form-control form-control-sm">
                        @foreach($contexts as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Quick Actions') }}</h6></div>
            <div class="card-body p-2">
                @foreach($quickActions as $action => $meta)
                <button class="btn btn-soft-{{ $meta['color'] }} btn-sm btn-block text-left mb-1 quick-action-btn" data-action="{{ $action }}">
                    <i class="las {{ $meta['icon'] }} mr-1"></i>{{ translate($meta['label']) }}
                </button>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Conversation') }}</h6></div>
            <div class="card-body p-2">
                <button id="clear-chat-btn" class="btn btn-soft-danger btn-sm btn-block">
                    <i class="las la-trash mr-1"></i>{{ translate('Clear Chat') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Chat Window --}}
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="las la-comments mr-1"></i>{{ translate('Chat') }}</h6>
                <span id="typing-indicator" class="text-muted small d-none">
                    <span class="spinner-grow spinner-grow-sm text-primary mr-1"></span>{{ translate('AI is thinking...') }}
                </span>
            </div>
            <div class="card-body p-0">
                <div id="chat-messages" style="height:450px; overflow-y:auto; padding:1rem; background:#f8f9fa;">
                    <div class="chat-msg ai">
                        <div class="d-flex align-items-start mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2 flex-shrink-0" style="width:32px;height:32px;font-size:14px;">
                                <i class="las la-robot"></i>
                            </div>
                            <div class="bg-white border rounded p-3 shadow-sm" style="max-width:90%;">
                                <strong class="d-block mb-1 text-primary small">AI SEO Assistant</strong>
                                <p class="mb-0 small">{{ translate('Hello! I\'m your AI SEO Assistant. I can help you with on-page optimization, technical SEO, content strategy, link building, local SEO, and much more. What would you like to work on today?') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="input-group">
                    <textarea id="chat-input" class="form-control" rows="2"
                        placeholder="{{ translate('Ask anything about SEO... e.g., How can I improve my product page rankings?') }}"
                        style="resize:none;"></textarea>
                    <div class="input-group-append">
                        <button id="send-btn" class="btn btn-primary px-4">
                            <i class="las la-paper-plane"></i>
                        </button>
                    </div>
                </div>
                <small class="text-muted mt-1 d-block">{{ translate('Press Ctrl+Enter to send') }}</small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
var chatHistory = [];

function appendMessage(role, content, provider) {
    var isUser = (role === 'user');
    var html = '<div class="d-flex align-items-start mb-3 ' + (isUser ? 'flex-row-reverse' : '') + '">';
    html += '<div class="rounded-circle ' + (isUser ? 'bg-success' : 'bg-primary') + ' text-white d-flex align-items-center justify-content-center ' + (isUser ? 'ml-2' : 'mr-2') + ' flex-shrink-0" style="width:32px;height:32px;font-size:14px;">';
    html += '<i class="las ' + (isUser ? 'la-user' : 'la-robot') + '"></i></div>';
    html += '<div class="' + (isUser ? 'bg-primary text-white' : 'bg-white border') + ' rounded p-3 shadow-sm" style="max-width:90%;">';
    if (!isUser && provider) html += '<strong class="d-block mb-1 small" style="color:' + (isUser ? '#fff' : '#6c757d') + ';">' + provider + '</strong>';
    html += '<div class="small mb-0" style="white-space:pre-wrap;">' + escapeHtml(content) + '</div>';
    html += '</div></div>';

    $('#chat-messages').append(html);
    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
}

function escapeHtml(text) {
    return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function sendMessage(message) {
    if (!message.trim()) return;
    appendMessage('user', message);
    chatHistory.push({ role: 'user', content: message });
    $('#chat-input').val('');
    $('#typing-indicator').removeClass('d-none');
    $('#send-btn').prop('disabled', true);

    $.ajax({
        url: '{{ route('admin.seo-suite.ai_chat') }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            message: message,
            history: JSON.stringify(chatHistory.slice(-8)),
            provider: $('#ai-provider').val(),
            context: $('#ai-context').val()
        },
        success: function(res) {
            $('#typing-indicator').addClass('d-none');
            $('#send-btn').prop('disabled', false);
            var reply = res.response || 'No response received.';
            appendMessage('assistant', reply, res.provider);
            chatHistory.push({ role: 'assistant', content: reply });
        },
        error: function(xhr) {
            $('#typing-indicator').addClass('d-none');
            $('#send-btn').prop('disabled', false);
            appendMessage('assistant', 'Error: ' + (xhr.responseJSON?.message || 'Request failed. Check API key.'));
        }
    });
}

$(function() {
    $('#send-btn').on('click', function() {
        sendMessage($('#chat-input').val());
    });

    $('#chat-input').on('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') sendMessage($(this).val());
    });

    $('#clear-chat-btn').on('click', function() {
        chatHistory = [];
        $('#chat-messages').html('');
        appendMessage('assistant', 'Chat cleared. How can I help you with SEO today?');
    });

    $('.quick-action-btn').on('click', function() {
        var action = $(this).data('action');
        $('#typing-indicator').removeClass('d-none');
        $(this).prop('disabled', true);
        var self = this;

        $.ajax({
            url: '{{ route('admin.seo-suite.ai_quick') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                provider: $('#ai-provider').val()
            },
            success: function(res) {
                $('#typing-indicator').addClass('d-none');
                $(self).prop('disabled', false);
                appendMessage('assistant', res.response || 'No response', res.provider);
                chatHistory.push({ role: 'assistant', content: res.response || '' });
            },
            error: function() {
                $('#typing-indicator').addClass('d-none');
                $(self).prop('disabled', false);
            }
        });
    });
});
</script>
@endsection
