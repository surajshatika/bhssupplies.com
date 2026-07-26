@extends('backend.layouts.app')

@section('content')
@php
    $providers = ['openai' => 'OpenAI (ChatGPT)', 'claude' => 'Claude (Anthropic)', 'gemini' => 'Gemini (Google)', 'grok' => 'Grok (xAI)'];
    $tones     = ['professional','friendly','authoritative','persuasive','technical','conversational'];
    $languages = ['French','Spanish','German','Italian','Portuguese','Hindi','Arabic','Chinese (Simplified)'];
    $emailTypes = [
        'newsletter' => 'Newsletter update',
        'product_announcement' => 'New product announcement',
        'promotion' => 'Promotion / offer',
        'trade_account' => 'Trade account invite',
        're_engagement' => 'Re-engagement',
    ];
    $defaultProvider = $settings['default_provider'] ?? 'openai';
@endphp

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-pen-nib mr-2 text-primary"></i>{{ translate('AI Writing Assistant') }}</h1>
            <p class="text-muted mb-0">{{ translate('Generate, rewrite, simplify, translate, and build tables, FAQs & email copy — Canada/B2B tuned.') }}</p>
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
    {{-- Controls --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Settings') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="small font-weight-600">{{ translate('Mode') }}</label>
                    <select id="wa-task" class="form-control form-control-sm">
                        @foreach($modes as $val => $label)
                            <option value="{{ $val }}">{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="small font-weight-600">{{ translate('Focus keyword') }}</label>
                    <input type="text" id="wa-keyword" class="form-control form-control-sm" placeholder="{{ translate('e.g. safety signs Canada') }}">
                </div>

                <div class="row">
                    <div class="col-6 form-group">
                        <label class="small font-weight-600">{{ translate('AI provider') }}</label>
                        <select id="wa-provider" class="form-control form-control-sm">
                            @foreach($providers as $val => $label)
                                <option value="{{ $val }}" @if($defaultProvider === $val) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 form-group">
                        <label class="small font-weight-600">{{ translate('Tone') }}</label>
                        <select id="wa-tone" class="form-control form-control-sm">
                            @foreach($tones as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="small font-weight-600">{{ translate('Length') }}</label>
                    <select id="wa-length" class="form-control form-control-sm">
                        <option value="short">{{ translate('Short (100-150 words)') }}</option>
                        <option value="medium" selected>{{ translate('Medium (200-300 words)') }}</option>
                        <option value="long">{{ translate('Long (400-600 words)') }}</option>
                    </select>
                </div>

                <div class="form-group d-none" id="wa-lang-wrap">
                    <label class="small font-weight-600">{{ translate('Translate to') }}</label>
                    <select id="wa-language" class="form-control form-control-sm">
                        @foreach($languages as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
                    </select>
                </div>

                <div class="form-group d-none" id="wa-email-wrap">
                    <label class="small font-weight-600">{{ translate('Email type') }}</label>
                    <select id="wa-email-type" class="form-control form-control-sm">
                        @foreach($emailTypes as $val => $label)<option value="{{ $val }}">{{ translate($label) }}</option>@endforeach
                    </select>
                </div>

                <button id="wa-run" class="btn btn-primary btn-block">
                    <i class="las la-magic mr-1"></i>{{ translate('Generate') }}
                    <span id="wa-spinner" class="spinner-border spinner-border-sm ml-1 d-none"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Editor --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Your content') }}</h6>
                <span class="small text-muted" id="wa-in-count">0 {{ translate('words') }}</span>
            </div>
            <div class="card-body">
                <textarea id="wa-content" class="form-control" rows="7"
                          placeholder="{{ translate('Paste content to rewrite/translate/summarize, or leave blank when generating from a keyword…') }}"></textarea>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Result') }}</h6>
                <div>
                    <span class="badge badge-soft-info mr-2 d-none" id="wa-out-meta"></span>
                    <button id="wa-copy" class="btn btn-sm btn-soft-secondary d-none"><i class="las la-copy mr-1"></i>{{ translate('Copy') }}</button>
                </div>
            </div>
            <div class="card-body">
                <div id="wa-error" class="alert alert-danger d-none"></div>
                <div id="wa-empty" class="text-center text-muted py-5">
                    <i class="las la-pen-fancy la-3x mb-2 d-block text-light"></i>
                    {{ translate('Pick a mode and click Generate.') }}
                </div>
                <div id="wa-output" class="d-none" style="white-space:pre-wrap; line-height:1.6;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const endpoint = '{{ route("admin.seo-suite.ai_writing") }}';
    const $ = id => document.getElementById(id);

    function safeHref(value) {
        const raw = String(value == null ? '' : value).trim();
        if (!raw) return '';
        try {
            const url = new URL(raw, window.location.href);
            return ['http:', 'https:', 'mailto:'].includes(url.protocol) ? url.href : '';
        } catch (e) {
            return '';
        }
    }

    function sanitizeHtml(value) {
        const template = document.createElement('template');
        template.innerHTML = String(value == null ? '' : value);
        const allowedTags = new Set([
            'A', 'B', 'BLOCKQUOTE', 'BR', 'CODE', 'EM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
            'HR', 'I', 'LI', 'OL', 'P', 'PRE', 'S', 'STRONG', 'TABLE', 'TBODY', 'TD', 'TFOOT',
            'TH', 'THEAD', 'TR', 'U', 'UL'
        ]);

        Array.from(template.content.querySelectorAll('*')).forEach(function(el) {
            if (!allowedTags.has(el.tagName)) {
                el.replaceWith(...Array.from(el.childNodes));
                return;
            }

            Array.from(el.attributes).forEach(function(attr) {
                const keepTableSpan = ['TD', 'TH'].includes(el.tagName) && ['colspan', 'rowspan'].includes(attr.name);
                const keepLinkAttr = el.tagName === 'A' && ['href', 'title', 'target'].includes(attr.name);
                if (!keepTableSpan && !keepLinkAttr) el.removeAttribute(attr.name);
            });

            if (el.tagName === 'A') {
                const href = safeHref(el.getAttribute('href'));
                if (href) el.setAttribute('href', href);
                else el.removeAttribute('href');

                if (el.getAttribute('target') === '_blank') el.setAttribute('rel', 'noopener noreferrer');
                else el.removeAttribute('target');
            }
        });

        return template.innerHTML;
    }

    // Show/hide mode-specific inputs.
    function syncModeInputs() {
        const task = $('wa-task').value;
        $('wa-lang-wrap').classList.toggle('d-none', task !== 'translate');
        $('wa-email-wrap').classList.toggle('d-none', task !== 'email');
        $('wa-run').innerHTML = (task === 'generate'
            ? '<i class="las la-magic mr-1"></i>{{ translate("Generate") }}'
            : '<i class="las la-sync mr-1"></i>{{ translate("Run") }}')
            + '<span id="wa-spinner" class="spinner-border spinner-border-sm ml-1 d-none"></span>';
    }

    function countWords() {
        const w = ($('wa-content').value.trim().match(/\S+/g) || []).length;
        $('wa-in-count').textContent = w + ' {{ translate("words") }}';
    }

    $('wa-task').addEventListener('change', syncModeInputs);
    $('wa-content').addEventListener('input', countWords);

    $('wa-run').addEventListener('click', function () {
        const task = $('wa-task').value;
        const content = $('wa-content').value.trim();
        if (['improve','expand','shorten','simplify','formal','persuasive','change_tone','paraphrase','fix_grammar','key_points','table','translate','summarize'].includes(task) && !content) {
            showError('{{ translate("This mode needs some content in the box on the left.") }}');
            return;
        }

        setLoading(true);
        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({
                task: task,
                content: content,
                keyword: $('wa-keyword').value.trim(),
                tone: $('wa-tone').value,
                length: $('wa-length').value,
                provider: $('wa-provider').value,
                target_language: $('wa-language').value,
                email_type: $('wa-email-type').value,
            })
        })
        .then(r => r.json())
        .then(data => {
            setLoading(false);
            if (data.error) { showError(data.error); return; }
            renderResult(data);
        })
        .catch(err => { setLoading(false); showError(err.message || 'Network error'); });
    });

    let lastFormat = 'text';

    function renderResult(data) {
        $('wa-error').classList.add('d-none');
        $('wa-empty').classList.add('d-none');
        const out = $('wa-output');
        out.classList.remove('d-none');
        lastFormat = data.format || 'text';
        if (lastFormat === 'html') { out.innerHTML = sanitizeHtml(data.result); }
        else { out.textContent = data.result || ''; }

        const meta = $('wa-out-meta');
        meta.textContent = (data.word_count || 0) + ' words · ' + lastFormat;
        meta.classList.remove('d-none');
        $('wa-copy').classList.remove('d-none');
    }

    $('wa-copy').addEventListener('click', function () {
        const out = $('wa-output');
        const text = lastFormat === 'html' ? out.innerHTML : out.innerText;
        navigator.clipboard.writeText(text).then(() => {
            this.innerHTML = '<i class="las la-check mr-1"></i>{{ translate("Copied") }}';
            setTimeout(() => { this.innerHTML = '<i class="las la-copy mr-1"></i>{{ translate("Copy") }}'; }, 1500);
        });
    });

    function setLoading(on) {
        $('wa-run').disabled = on;
        const sp = $('wa-spinner');
        if (sp) sp.classList.toggle('d-none', !on);
    }
    function showError(msg) {
        const e = $('wa-error');
        e.textContent = msg;
        e.classList.remove('d-none');
        $('wa-empty').classList.add('d-none');
        $('wa-output').classList.add('d-none');
    }

    syncModeInputs();
})();
</script>
@endsection
