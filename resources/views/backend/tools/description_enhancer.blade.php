@extends('backend.layouts.app')

@section('page_title', translate('Description Enhancer'))

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Product Description Enhancer') }}</h1>
            <p class="text-muted mb-0">{{ translate('One-time tool to clean and professionally style all stored product descriptions.') }}</p>
        </div>
    </div>
</div>

<div class="row">

    {{-- Stats cards --}}
    <div class="col-12 mb-4">
        <div class="row gutters-10">
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-none border py-3">
                    <div class="h2 fw-800 text-primary mb-0">{{ $stats['total'] }}</div>
                    <div class="text-muted fs-13">{{ translate('Total Products') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-none border py-3">
                    <div class="h2 fw-800 text-success mb-0">{{ $stats['with_desc'] }}</div>
                    <div class="text-muted fs-13">{{ translate('With Description') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-none border py-3">
                    <div class="h2 fw-800 text-warning mb-0">{{ $stats['has_table'] }}</div>
                    <div class="text-muted fs-13">{{ translate('Have Spec Tables') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-none border py-3">
                    <div class="h2 fw-800 text-info mb-0">{{ $stats['has_list'] }}</div>
                    <div class="text-muted fs-13">{{ translate('Have Feature Lists') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- What it does --}}
    <div class="col-md-5 mb-4">
        <div class="card shadow-none border">
            <div class="card-header bg-soft-primary">
                <h5 class="mb-0 h6 text-primary"><i class="las la-magic mr-1"></i>{{ translate('What this tool does') }}</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 pl-4" style="font-size:14px; line-height:2;">
                    <li>Removes conflicting inline <code>width</code> &amp; <code>font-family</code> styles</li>
                    <li>Adds <code>bhs-spec-table</code> class to all <code>&lt;table&gt;</code> elements</li>
                    <li>Detects 2-column spec tables (Article No / EAN) &amp; marks them</li>
                    <li>Adds <code>bhs-features-list</code> class to bullet lists</li>
                    <li>Removes empty <code>&lt;p&gt;</code> tags and extra <code>&lt;br&gt;</code> chains</li>
                    <li>Cleans all leftover empty <code>style=""</code> attributes</li>
                </ul>
            </div>
        </div>

        <div class="card shadow-none border mt-3">
            <div class="card-header bg-soft-warning">
                <h5 class="mb-0 h6 text-warning"><i class="las la-exclamation-triangle mr-1"></i>{{ translate('Before you run') }}</h5>
            </div>
            <div class="card-body fs-13 text-muted" style="line-height:1.8;">
                <i class="las la-check-circle text-success mr-1"></i> Safe to run multiple times (idempotent)<br>
                <i class="las la-check-circle text-success mr-1"></i> Only saves products where HTML actually changed<br>
                <i class="las la-database text-info mr-1"></i> Recommended: take a DB backup first<br>
                <i class="las la-clock text-primary mr-1"></i> ~{{ ceil($stats['with_desc'] / 20) }} batches of 20 products
            </div>
        </div>
    </div>

    {{-- Run panel --}}
    <div class="col-md-7 mb-4">
        <div class="card shadow-none border">
            <div class="card-header">
                <h5 class="mb-0 h6"><i class="las la-play-circle mr-1"></i>{{ translate('Run Enhancement') }}</h5>
            </div>
            <div class="card-body">

                {{-- Progress --}}
                <div id="progress-wrap" class="mb-3 d-none">
                    <div class="d-flex justify-content-between mb-1">
                        <span id="progress-label" class="fs-13 text-muted">Starting…</span>
                        <span id="progress-pct" class="fs-13 fw-600 text-primary">0%</span>
                    </div>
                    <div class="progress" style="height:10px; border-radius:6px;">
                        <div id="progress-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width:0%; transition:width .4s;"></div>
                    </div>
                    <div class="mt-2 d-flex justify-content-between fs-12 text-muted">
                        <span id="progress-processed">0 processed</span>
                        <span id="progress-updated" class="text-success fw-600">0 updated</span>
                    </div>
                </div>

                {{-- Result --}}
                <div id="result-box" class="alert d-none mb-3"></div>

                {{-- Log --}}
                <div id="log-wrap" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fs-12 text-muted fw-600">{{ translate('Log') }}</span>
                        <button type="button" class="btn btn-xs btn-light" onclick="document.getElementById('log-box').innerHTML=''">{{ translate('Clear') }}</button>
                    </div>
                    <div id="log-box" class="bg-dark text-success rounded p-2"
                         style="font-family:monospace; font-size:12px; height:180px; overflow-y:auto; line-height:1.6;">
                    </div>
                </div>

                <div class="mt-3 d-flex" style="gap:10px;">
                    <button type="button" id="btn-run" class="btn btn-primary btn-lg flex-fill"
                            onclick="startEnhancement()">
                        <i class="las la-magic mr-1"></i>{{ translate('Enhance All Descriptions') }}
                    </button>
                    <button type="button" id="btn-stop" class="btn btn-danger btn-lg d-none"
                            onclick="stopEnhancement()">
                        <i class="las la-stop-circle mr-1"></i>{{ translate('Stop') }}
                    </button>
                </div>
                <p class="text-muted mt-2 mb-0 fs-12">
                    {{ translate('Processing') }} <b>{{ $stats['with_desc'] }}</b> {{ translate('products in batches of 20.') }}
                </p>

            </div>
        </div>
    </div>

</div>

{{-- CSS preview of how descriptions will look after enhancement --}}
<div class="card shadow-none border mb-4">
    <div class="card-header">
        <h5 class="mb-0 h6"><i class="las la-eye mr-1"></i>{{ translate('After Enhancement – Style Preview') }}</h5>
    </div>
    <div class="card-body">
        <style>
        /* ══════════════════════════════════════════════
           BHS Supplies – Product Description Styles
           Applied globally via description_styles.css
           (also embedded here for preview)
        ══════════════════════════════════════════════ */
        .desc-preview { font-size: 14.5px; line-height: 1.8; color: #333; }

        /* Feature bullet list */
        .desc-preview .bhs-features-list,
        .aiz-editor-data .bhs-features-list,
        .pd-description-wrap .bhs-features-list {
            list-style: none; padding-left: 0; margin: 12px 0;
        }
        .desc-preview .bhs-features-list li,
        .aiz-editor-data .bhs-features-list li,
        .pd-description-wrap .bhs-features-list li {
            padding: 5px 5px 5px 28px; position: relative;
            border-bottom: 1px solid #f5f5f5; font-size: 14px;
        }
        .desc-preview .bhs-features-list li::before,
        .aiz-editor-data .bhs-features-list li::before,
        .pd-description-wrap .bhs-features-list li::before {
            content: "✔"; position: absolute; left: 6px;
            color: #ff6b00; font-weight: 700; font-size: 12px; top: 6px;
        }

        /* General spec table */
        .desc-preview .bhs-spec-table,
        .aiz-editor-data .bhs-spec-table,
        .pd-description-wrap .bhs-spec-table {
            width: 100%; border-collapse: collapse; margin: 16px 0;
            font-size: 13.5px; border-radius: 8px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .desc-preview .bhs-spec-table td,
        .desc-preview .bhs-spec-table th,
        .aiz-editor-data .bhs-spec-table td,
        .aiz-editor-data .bhs-spec-table th,
        .pd-description-wrap .bhs-spec-table td,
        .pd-description-wrap .bhs-spec-table th {
            padding: 9px 14px; border: 1px solid #e8eaed; vertical-align: top;
        }
        .desc-preview .bhs-spec-table tr:nth-child(even) td,
        .aiz-editor-data .bhs-spec-table tr:nth-child(even) td,
        .pd-description-wrap .bhs-spec-table tr:nth-child(even) td { background: #f8f9fa; }
        .desc-preview .bhs-spec-table tr:first-child td,
        .desc-preview .bhs-spec-table th,
        .aiz-editor-data .bhs-spec-table tr:first-child td,
        .aiz-editor-data .bhs-spec-table th,
        .pd-description-wrap .bhs-spec-table tr:first-child td,
        .pd-description-wrap .bhs-spec-table th {
            background: #1a1a2e; color: #fff; font-weight: 700;
        }

        /* 2-column spec table (Article No / EAN style) */
        .desc-preview .bhs-spec-table--2col td:first-child,
        .aiz-editor-data .bhs-spec-table--2col td:first-child,
        .pd-description-wrap .bhs-spec-table--2col td:first-child {
            width: 38%; font-weight: 600; color: #555; background: #f1f3f5;
        }
        .desc-preview .bhs-spec-table--2col tr:first-child,
        .aiz-editor-data .bhs-spec-table--2col tr:first-child,
        .pd-description-wrap .bhs-spec-table--2col tr:first-child { display: none; } /* no dark header for spec tables */
        .desc-preview .bhs-spec-table--2col td,
        .aiz-editor-data .bhs-spec-table--2col td,
        .pd-description-wrap .bhs-spec-table--2col td {
            background: #fff !important; border-color: #e8eaed;
        }
        .desc-preview .bhs-spec-table--2col tr:nth-child(even) td:first-child,
        .aiz-editor-data .bhs-spec-table--2col tr:nth-child(even) td:first-child,
        .pd-description-wrap .bhs-spec-table--2col tr:nth-child(even) td:first-child { background: #f1f3f5 !important; }
        .desc-preview .bhs-spec-table--2col tr:nth-child(even) td:last-child,
        .aiz-editor-data .bhs-spec-table--2col tr:nth-child(even) td:last-child,
        .pd-description-wrap .bhs-spec-table--2col tr:nth-child(even) td:last-child { background: #fafafa !important; }
        </style>

        <div class="row">
            <div class="col-md-6">
                <p class="fs-12 fw-600 text-muted mb-2 text-uppercase">Feature List</p>
                <div class="desc-preview border rounded p-3">
                    <ul class="bhs-features-list">
                        <li>Unique combination: Front grip and side grip function</li>
                        <li>High-grip front jaw with robust teeth and high gear ratio</li>
                        <li>Very slim pliers: work even in confined spaces</li>
                        <li>Reliable, frontal gripping of flat workpieces up to 6mm</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <p class="fs-12 fw-600 text-muted mb-2 text-uppercase">Spec Table (2-col)</p>
                <div class="desc-preview border rounded p-3">
                    <table class="bhs-spec-table bhs-spec-table--2col">
                        <tbody>
                            <tr><td>Article No.</td><td>82 01 150</td></tr>
                            <tr><td>EAN</td><td>4003773089353</td></tr>
                            <tr><td>Pliers</td><td>Grey atramentized</td></tr>
                            <tr><td>Head</td><td>Polished</td></tr>
                            <tr><td>Weight</td><td>130 g</td></tr>
                            <tr><td>Dimensions</td><td>150 × 49 × 14 mm</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
var _running  = false;
var _offset   = 0;
var _totalUpd = 0;
var _total    = {{ $stats['with_desc'] }};

function log(msg, cls) {
    cls = cls || 'text-success';
    var box = document.getElementById('log-box');
    box.innerHTML += '<span class="' + cls + '">' + msg + '</span>\n';
    box.scrollTop = box.scrollHeight;
}

function startEnhancement() {
    _running  = true;
    _offset   = 0;
    _totalUpd = 0;

    document.getElementById('btn-run').classList.add('d-none');
    document.getElementById('btn-stop').classList.remove('d-none');
    document.getElementById('progress-wrap').classList.remove('d-none');
    document.getElementById('log-wrap').classList.remove('d-none');
    document.getElementById('result-box').classList.add('d-none');

    log('[' + new Date().toLocaleTimeString() + '] Starting enhancement of {{ $stats['with_desc'] }} products…');
    runBatch();
}

function stopEnhancement() {
    _running = false;
    log('[STOPPED]', 'text-warning');
    finishUI('warning', 'Enhancement stopped. ' + _totalUpd + ' products updated so far.');
}

function runBatch() {
    if (!_running) return;

    $.ajax({
        url:  '{{ route("admin.tools.enhance_descriptions.batch") }}',
        type: 'POST',
        data: { offset: _offset, _token: '{{ csrf_token() }}' },
        success: function(r) {
            _totalUpd += r.updated;
            _offset    = r.processed;

            var pct = Math.min(100, Math.round((_offset / _total) * 100));
            document.getElementById('progress-bar').style.width  = pct + '%';
            document.getElementById('progress-pct').textContent  = pct + '%';
            document.getElementById('progress-processed').textContent = _offset + ' of ' + _total + ' processed';
            document.getElementById('progress-updated').textContent   = _totalUpd + ' updated';

            if (r.updated > 0) {
                log('[' + _offset + '/' + _total + '] Batch done — ' + r.updated + ' descriptions updated');
            } else {
                log('[' + _offset + '/' + _total + '] Batch done — no changes needed', 'text-muted');
            }

            if (r.done || _offset >= _total) {
                _running = false;
                finishUI('success', '✔ Enhancement complete! ' + _totalUpd + ' out of ' + _total + ' descriptions were updated.');
            } else {
                setTimeout(runBatch, 150);
            }
        },
        error: function(xhr) {
            log('[ERROR] ' + (xhr.responseText || 'Request failed'), 'text-danger');
            _running = false;
            finishUI('danger', 'An error occurred. Check the log above.');
        }
    });
}

function finishUI(type, message) {
    var bar = document.getElementById('progress-bar');
    bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
    bar.className = 'progress-bar bg-' + type;

    var box = document.getElementById('result-box');
    box.className = 'alert alert-' + type;
    box.innerHTML = '<i class="las la-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + ' mr-1"></i>' + message;
    box.classList.remove('d-none');

    document.getElementById('btn-stop').classList.add('d-none');
    document.getElementById('btn-run').classList.remove('d-none');
    document.getElementById('btn-run').innerHTML = '<i class="las la-redo mr-1"></i>{{ translate("Run Again") }}';
}
</script>
@endsection
