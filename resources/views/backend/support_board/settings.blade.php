@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="h3">{{ translate('AI Chat & Support Settings') }}</h1>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Chat Status') }}</h5></div>
            <div class="card-body">
                <div class="form-group row align-items-center mb-0">
                    <label class="col-8 col-form-label">{{ translate('Enable AI Chat Widget') }}</label>
                    <div class="col-4 text-right">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" onchange="updateActivation(this,'ai_chat_status')"
                                @if(get_setting('ai_chat_status') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Chat Visibility') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('support_board.settings.update') }}" method="POST">
                    @csrf
                    <select name="ai_chat_visibility" class="form-control mb-3">
                        <option value="all" @if(get_setting('ai_chat_visibility')=='all') selected @endif>{{ translate('All Visitors') }}</option>
                        <option value="logged_in" @if(get_setting('ai_chat_visibility')=='logged_in') selected @endif>{{ translate('Logged-in Only') }}</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('AI Provider') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('support_board.settings.update') }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="font-weight-bold">{{ translate('Select AI Provider') }}</label>
                            <select name="ai_chat_provider" class="form-control" onchange="toggleProviderFields(this.value)">
                                <option value="groq"   @if(get_setting('ai_chat_provider')=='groq'  ||!get_setting('ai_chat_provider')) selected @endif>Groq (Free ✓)</option>
                                <option value="claude" @if(get_setting('ai_chat_provider')=='claude') selected @endif>Anthropic Claude</option>
                                <option value="openai" @if(get_setting('ai_chat_provider')=='openai') selected @endif>OpenAI (ChatGPT)</option>
                                <option value="gemini" @if(get_setting('ai_chat_provider')=='gemini') selected @endif>Google Gemini</option>
                            </select>
                        </div>
                    </div>

                    {{-- Groq Fields --}}
                    <div id="groq-fields" class="border rounded p-3 mb-3 bg-light">
                        <h6 class="font-weight-bold mb-1" style="color:#f55036;">Groq <span class="badge badge-success">FREE</span></h6>
                        <small class="text-muted d-block mb-3">Free API key: <strong>console.groq.com</strong></small>
                        <div class="row">
                            <div class="col-md-8">
                                <label>{{ translate('API Key') }}</label>
                                <input type="text" name="groq_api_key" class="form-control mb-2"
                                    placeholder="gsk_..." value="{{ get_setting('groq_api_key') }}">
                            </div>
                            <div class="col-md-4">
                                <label>{{ translate('Model') }}</label>
                                <select name="groq_model" class="form-control">
                                    @php
                                        $groqModels = [
                                            'llama-3.1-8b-instant'   => 'Llama 3.1 8B (Fastest)',
                                            'llama-3.3-70b-versatile'=> 'Llama 3.3 70B (Best)',
                                            'gemma2-9b-it'           => 'Gemma2 9B',
                                        ];
                                    @endphp
                                    @foreach($groqModels as $val => $label)
                                        <option value="{{ $val }}" @if(get_setting('groq_model')==$val || (!get_setting('groq_model') && $val=='llama-3.1-8b-instant')) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Claude Fields --}}
                    <div id="claude-fields" class="border rounded p-3 mb-3 bg-light" style="display:none">
                        <h6 class="font-weight-bold mb-3" style="color:#7c3aed;">Anthropic Claude</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <label>{{ translate('API Key') }}</label>
                                <input type="text" name="anthropic_api_key" class="form-control mb-2"
                                    placeholder="sk-ant-..." value="{{ get_setting('anthropic_api_key') }}">
                            </div>
                            <div class="col-md-4">
                                <label>{{ translate('Model') }}</label>
                                <select name="claude_model" class="form-control">
                                    @php
                                        $claudeModels = [
                                            'claude-haiku-4-5-20251001'  => 'Claude Haiku 4.5 (Fast)',
                                            'claude-sonnet-4-6'          => 'Claude Sonnet 4.6 (Recommended)',
                                            'claude-opus-4-7'            => 'Claude Opus 4.7 (Powerful)',
                                        ];
                                    @endphp
                                    @foreach($claudeModels as $val => $label)
                                        <option value="{{ $val }}" @if(get_setting('claude_model')==$val || (!get_setting('claude_model') && $val=='claude-haiku-4-5-20251001')) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- OpenAI Fields --}}
                    <div id="openai-fields" class="border rounded p-3 mb-3 bg-light" style="display:none">
                        <h6 class="font-weight-bold mb-3 text-primary">OpenAI (ChatGPT)</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <label>{{ translate('API Key') }}</label>
                                <input type="text" name="openai_api_key" class="form-control mb-2"
                                    placeholder="sk-..." value="{{ get_setting('openai_api_key') }}">
                            </div>
                            <div class="col-md-4">
                                <label>{{ translate('Model') }}</label>
                                <select name="openai_model" class="form-control">
                                    @foreach(['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo'] as $m)
                                        <option value="{{ $m }}" @if(get_setting('openai_model')==$m) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Gemini Fields --}}
                    <div id="gemini-fields" class="border rounded p-3 mb-3 bg-light" style="display:none">
                        <h6 class="font-weight-bold mb-3 text-success">Google Gemini</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <label>{{ translate('API Key') }}</label>
                                <input type="text" name="gemini_api_key" class="form-control mb-2"
                                    placeholder="AIza..." value="{{ get_setting('gemini_api_key') }}">
                            </div>
                            <div class="col-md-4">
                                <label>{{ translate('Model') }}</label>
                                <select name="gemini_model" class="form-control">
                                    @foreach(['gemini-pro','gemini-1.5-flash','gemini-1.5-pro','gemini-2.0-flash'] as $m)
                                        <option value="{{ $m }}" @if(get_setting('gemini_model')==$m) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ translate('Save AI Settings') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Chat Customization') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('support_board.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">{{ translate('Welcome Message') }}</label>
                        <input type="text" name="ai_chat_welcome_msg" class="form-control"
                            value="{{ get_setting('ai_chat_welcome_msg') ?: 'Hi! How can I help you today?' }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">{{ translate('AI System Prompt') }}</label>
                        <textarea name="ai_chat_system_prompt" class="form-control" rows="5"
                            placeholder="Describe how the AI should behave. Leave blank for default.">{{ get_setting('ai_chat_system_prompt') }}</textarea>
                        <small class="text-muted">{{ translate('Example: You are a support agent for BHS Supplies. Help customers with HVAC, plumbing and hardware queries.') }}</small>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Conversations') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-between">
                <span class="text-muted">{{ translate('View and reply to all customer conversations') }}</span>
                <a href="{{ route('support_board.conversations') }}" class="btn btn-primary">
                    <i class="las la-comments mr-1"></i> {{ translate('View All Conversations') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
function toggleProviderFields(val) {
    document.getElementById('groq-fields').style.display   = val === 'groq'    ? '' : 'none';
    document.getElementById('claude-fields').style.display  = val === 'claude'  ? '' : 'none';
    document.getElementById('openai-fields').style.display  = val === 'openai'  ? '' : 'none';
    document.getElementById('gemini-fields').style.display  = val === 'gemini'  ? '' : 'none';
}
@php $provider = get_setting('ai_chat_provider') ?: 'groq'; @endphp
toggleProviderFields('{{ $provider }}');
function updateActivation(el, type) {
    $.post('{{ route("support_board.activation") }}', {
        _token: '{{ csrf_token() }}', type: type, value: $(el).is(':checked') ? 1 : 0
    }, function(data) {
        AIZ.plugins.notify(data == '1' ? 'success' : 'danger',
            data == '1' ? '{{ translate("Updated successfully") }}' : '{{ translate("Something went wrong") }}');
    });
}
</script>
@endsection
