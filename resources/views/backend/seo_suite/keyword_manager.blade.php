@extends('backend.layouts.app')
@section('content')
@include('backend.partials.modern_module_styles')

@php
$groups = [
    'related'    => ['label' => 'Related Keywords to Target',    'color' => 'primary',  'icon' => 'la-bullseye',    'desc' => 'Autopilot weaves these into titles, descriptions, and content on every entity.'],
    'competitor' => ['label' => 'Competitor Keywords to Outrank','color' => 'danger',   'icon' => 'la-chess-knight', 'desc' => 'Keywords your competitors rank for — autopilot uses these naturally without keyword-stuffing.'],
];
$allKeywords = ['related' => $related, 'competitor' => $competitor];
@endphp

<style>
.kw-row { display:flex; align-items:center; gap:.5rem; padding:.45rem .6rem; border-radius:6px; transition:background .15s; }
.kw-row:hover { background:#f8f9ff; }
.kw-badge { font-size:.78rem; padding:.28rem .65rem; border-radius:20px; font-weight:500; }
.kw-index { width:28px; text-align:right; color:#aaa; font-size:.75rem; flex-shrink:0; }
.kw-text  { flex:1; font-size:.88rem; }
.kw-edit-input { flex:1; font-size:.88rem; border:1px solid #4e73df; border-radius:5px; padding:.2rem .5rem; outline:none; }
.kw-actions { display:flex; gap:.3rem; flex-shrink:0; }
.kw-actions .btn { padding:.18rem .45rem; font-size:.75rem; }
.kw-add-row { background:#f0f4ff; border-radius:8px; padding:.6rem .8rem; display:flex; gap:.5rem; margin-bottom:.5rem; }
.kw-add-row input { flex:1; border:1px solid #c5cef7; border-radius:5px; padding:.3rem .6rem; font-size:.88rem; }
.kw-add-row button { white-space:nowrap; }
.kw-search { border:1px solid #e0e4f0; border-radius:6px; padding:.3rem .7rem; font-size:.85rem; width:100%; }
.count-badge { font-size:.75rem; }
.kw-list-wrap { max-height:520px; overflow-y:auto; }
.tab-kw.active { border-bottom:2px solid #4e73df; color:#4e73df !important; font-weight:600; }
.tab-kw { cursor:pointer; padding:.4rem .9rem; font-size:.9rem; color:#666; border-bottom:2px solid transparent; }
.toast-kw { position:fixed; bottom:20px; right:20px; z-index:9999; min-width:260px; }
</style>

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0"><i class="las la-tags mr-2 text-primary"></i>{{ translate('Keyword Manager') }}</h4>
            <small class="text-muted">{{ translate('Add, edit, or delete target and competitor keywords. Changes apply to all autopilot runs immediately.') }}</small>
        </div>
        <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="las la-arrow-left mr-1"></i>{{ translate('Back to SEO Suite') }}
        </a>
    </div>

    {{-- How it works alert --}}
    <div class="alert alert-info alert-dismissible fade show py-2 small mb-4" role="alert">
        <i class="las la-info-circle mr-1"></i>
        <strong>{{ translate('How keywords work:') }}</strong>
        {{ translate('Every keyword saved here is automatically woven into AI-generated titles, meta descriptions, content, and secondary keyword lists on the next autopilot run. No manual steps needed after saving.') }}
        <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
    </div>

    <div class="row">
        @foreach($groups as $groupKey => $group)
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <div>
                        <span class="badge badge-{{ $group['color'] }} mr-2">
                            <i class="las {{ $group['icon'] }}"></i>
                        </span>
                        <strong>{{ translate($group['label']) }}</strong>
                        <span class="badge badge-soft-{{ $group['color'] }} ml-2 count-badge" id="count-{{ $groupKey }}">
                            {{ count($allKeywords[$groupKey]) }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted mb-3">{{ translate($group['desc']) }}</p>

                    {{-- Add new keyword --}}
                    <div class="kw-add-row">
                        <input type="text"
                               id="add-input-{{ $groupKey }}"
                               placeholder="{{ translate('Type a keyword and press Add…') }}"
                               onkeydown="if(event.key==='Enter') addKeyword('{{ $groupKey }}')">
                        <button class="btn btn-sm btn-{{ $group['color'] }}" onclick="addKeyword('{{ $groupKey }}')">
                            <i class="las la-plus mr-1"></i>{{ translate('Add') }}
                        </button>
                    </div>

                    {{-- Search filter --}}
                    <input type="text"
                           class="kw-search mb-3"
                           placeholder="{{ translate('Search keywords…') }}"
                           oninput="filterList('{{ $groupKey }}', this.value)">

                    {{-- Keyword list --}}
                    <div class="kw-list-wrap" id="list-{{ $groupKey }}">
                        @forelse($allKeywords[$groupKey] as $i => $kw)
                        <div class="kw-row" data-kw="{{ e(strtolower($kw)) }}" id="row-{{ $groupKey }}-{{ $i }}">
                            <span class="kw-index">{{ $i + 1 }}</span>
                            <span class="kw-text">{{ $kw }}</span>
                            <div class="kw-actions">
                                <button class="btn btn-xs btn-outline-primary" title="{{ translate('Edit') }}"
                                        onclick="startEdit(this, '{{ $groupKey }}', '{{ addslashes($kw) }}')">
                                    <i class="las la-pen"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger" title="{{ translate('Delete') }}"
                                        onclick="deleteKeyword('{{ $groupKey }}', '{{ addslashes($kw) }}', this)">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted small py-4" id="empty-{{ $groupKey }}">
                            <i class="las la-tag la-2x mb-2 d-block"></i>
                            {{ translate('No keywords yet. Add your first one above.') }}
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Toast notification --}}
<div class="toast-kw">
    <div id="kw-toast" class="toast" role="alert" data-delay="2800">
        <div class="toast-header">
            <i class="las la-check-circle text-success mr-2" id="toast-icon"></i>
            <strong class="mr-auto" id="toast-title">Done</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast"><span>&times;</span></button>
        </div>
        <div class="toast-body" id="toast-msg"></div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

function showToast(msg, success = true) {
    document.getElementById('toast-icon').className = success
        ? 'las la-check-circle text-success mr-2'
        : 'las la-exclamation-circle text-danger mr-2';
    document.getElementById('toast-title').textContent = success ? '{{ translate("Saved") }}' : '{{ translate("Error") }}';
    document.getElementById('toast-msg').textContent   = msg;
    $('#kw-toast').toast('show');
}

function updateCount(group, count) {
    document.getElementById('count-' + group).textContent = count;
}

function refreshIndex(group) {
    const rows = document.querySelectorAll('#list-' + group + ' .kw-row');
    rows.forEach((row, i) => {
        const idx = row.querySelector('.kw-index');
        if (idx) idx.textContent = i + 1;
    });
}

function filterList(group, query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('#list-' + group + ' .kw-row').forEach(row => {
        row.style.display = (!q || row.dataset.kw.includes(q)) ? '' : 'none';
    });
}

function addKeyword(group) {
    const input   = document.getElementById('add-input-' + group);
    const keyword = input.value.trim();
    if (!keyword) { input.focus(); return; }

    fetch('{{ route("admin.seo-suite.keyword_manager.add") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ group, keyword })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.error, false); return; }
        appendRow(group, keyword, data.count - 1);
        updateCount(group, data.count);
        input.value = '';
        input.focus();
        // remove empty placeholder if present
        const empty = document.getElementById('empty-' + group);
        if (empty) empty.remove();
        showToast('"' + keyword + '" {{ translate("added") }}');
    })
    .catch(() => showToast('{{ translate("Network error.") }}', false));
}

function appendRow(group, keyword, index) {
    const list = document.getElementById('list-' + group);
    const div  = document.createElement('div');
    div.className = 'kw-row';
    div.dataset.kw = keyword.toLowerCase();
    div.innerHTML = `
        <span class="kw-index">${index + 1}</span>
        <span class="kw-text">${escHtml(keyword)}</span>
        <div class="kw-actions">
            <button class="btn btn-xs btn-outline-primary" title="{{ translate('Edit') }}"
                    onclick="startEdit(this, '${group}', '${escJs(keyword)}')">
                <i class="las la-pen"></i>
            </button>
            <button class="btn btn-xs btn-outline-danger" title="{{ translate('Delete') }}"
                    onclick="deleteKeyword('${group}', '${escJs(keyword)}', this)">
                <i class="las la-trash"></i>
            </button>
        </div>`;
    list.appendChild(div);
}

function startEdit(btn, group, keyword) {
    const row     = btn.closest('.kw-row');
    const textSpan = row.querySelector('.kw-text');
    const actions  = row.querySelector('.kw-actions');

    textSpan.style.display = 'none';
    actions.style.display  = 'none';

    const input = document.createElement('input');
    input.type       = 'text';
    input.className  = 'kw-edit-input';
    input.value      = keyword;

    const saveBtn   = document.createElement('button');
    saveBtn.className = 'btn btn-xs btn-primary';
    saveBtn.innerHTML = '<i class="las la-check"></i>';
    saveBtn.title     = '{{ translate("Save") }}';

    const cancelBtn   = document.createElement('button');
    cancelBtn.className = 'btn btn-xs btn-secondary';
    cancelBtn.innerHTML = '<i class="las la-times"></i>';
    cancelBtn.title     = '{{ translate("Cancel") }}';

    const editActions = document.createElement('div');
    editActions.className = 'kw-actions';
    editActions.appendChild(saveBtn);
    editActions.appendChild(cancelBtn);

    row.insertBefore(input, actions);
    row.appendChild(editActions);
    input.focus();
    input.select();

    const doSave = () => {
        const newVal = input.value.trim();
        if (!newVal || newVal === keyword) { doCancel(); return; }
        saveKeyword(group, keyword, newVal, row, textSpan, actions, input, editActions);
    };
    const doCancel = () => {
        input.remove();
        editActions.remove();
        textSpan.style.display = '';
        actions.style.display  = '';
    };

    saveBtn.onclick  = doSave;
    cancelBtn.onclick = doCancel;
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter')  doSave();
        if (e.key === 'Escape') doCancel();
    });
}

function saveKeyword(group, oldKw, newKw, row, textSpan, actions, input, editActions) {
    fetch('{{ route("admin.seo-suite.keyword_manager.update") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ group, old: oldKw, new: newKw })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.error, false); return; }
        textSpan.textContent   = newKw;
        row.dataset.kw         = newKw.toLowerCase();
        // Patch onclick attrs with new keyword value
        const editBtn = actions.querySelector('button:first-child');
        const delBtn  = actions.querySelector('button:last-child');
        if (editBtn) editBtn.setAttribute('onclick', `startEdit(this, '${group}', '${escJs(newKw)}')`);
        if (delBtn)  delBtn.setAttribute('onclick',  `deleteKeyword('${group}', '${escJs(newKw)}', this)`);

        input.remove(); editActions.remove();
        textSpan.style.display = '';
        actions.style.display  = '';
        showToast('{{ translate("Keyword updated") }}');
    })
    .catch(() => showToast('{{ translate("Network error.") }}', false));
}

function deleteKeyword(group, keyword, btn) {
    if (!confirm('{{ translate("Delete this keyword?") }}\n"' + keyword + '"')) return;

    fetch('{{ route("admin.seo-suite.keyword_manager.delete") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ group, keyword })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.error, false); return; }
        const row = btn.closest('.kw-row');
        row.style.transition = 'opacity .25s';
        row.style.opacity    = '0';
        setTimeout(() => {
            row.remove();
            updateCount(group, data.count);
            refreshIndex(group);
            if (data.count === 0) {
                const list = document.getElementById('list-' + group);
                list.innerHTML = `<div class="text-center text-muted small py-4">
                    <i class="las la-tag la-2x mb-2 d-block"></i>
                    {{ translate('No keywords yet. Add your first one above.') }}</div>`;
            }
        }, 250);
        showToast('"' + keyword + '" {{ translate("deleted") }}');
    })
    .catch(() => showToast('{{ translate("Network error.") }}', false));
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escJs(str) {
    return String(str).replace(/\\/g,'\\\\').replace(/'/g,"\\'");
}
</script>
@endsection
