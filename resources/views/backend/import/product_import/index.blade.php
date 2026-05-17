@extends('backend.layouts.app')

@section('page_title', translate('AI Product Import'))

@section('content')
<style>
.import-card { border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
.import-card .card-header { padding: 14px 20px; font-size: 15px; font-weight: 700; }
.result-table td, .result-table th { vertical-align: middle !important; font-size: 13px; }
.status-badge { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
.status-pending  { background: #adb5bd; }
.status-saving   { background: #fd7e14; animation: pulse .8s infinite; }
.status-saved    { background: #28a745; }
.status-error    { background: #dc3545; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.save-btn-saved { background: #28a745; color:#fff; border-color:#28a745; }
.save-btn-saved:hover { background: #218838; }
</style>

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-1">{{ translate('AI Product Import') }}</h1>
            <p class="text-muted mb-0 fs-13">{{ translate('Import products from PDF catalogs or competitor URLs with AI enrichment.') }}</p>
        </div>
    </div>
</div>

<div class="row gutters-16">

    {{-- PDF Import --}}
    <div class="col-lg-6 mb-4">
        <div class="card import-card">
            <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
                <i class="las la-file-pdf la-lg"></i>{{ translate('Import from PDF Catalog') }}
            </div>
            <div class="card-body">
                <form id="pdfImportForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-600">{{ translate('PDF File') }} <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" class="form-control" accept=".pdf" required>
                        <small class="text-muted">{{ translate('Max 10MB. Supports product catalog PDFs.') }}</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-600">{{ translate('AI Provider') }}</label>
                        <select name="provider" class="form-control form-control-sm">
                            <option value="gemini"  {{ config('seo.default_provider')==='gemini'  ? 'selected':'' }}>Gemini</option>
                            <option value="openai"  {{ config('seo.default_provider')==='openai'  ? 'selected':'' }}>OpenAI</option>
                            <option value="claude"  {{ config('seo.default_provider')==='claude'  ? 'selected':'' }}>Claude</option>
                        </select>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="pdfEnrich" name="ai_enrich" value="1" checked>
                        <label class="custom-control-label" for="pdfEnrich">{{ translate('AI Enrich (add SEO title, description, category)') }}</label>
                    </div>
                    <div class="d-flex" style="gap:8px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="runPreview('pdf')">
                            <i class="las la-eye mr-1"></i>{{ translate('Preview') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="las la-upload mr-1"></i>{{ translate('Import Products') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- URL Import --}}
    <div class="col-lg-6 mb-4">
        <div class="card import-card">
            <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                <i class="las la-globe la-lg"></i>{{ translate('Import from Competitor URLs') }}
            </div>
            <div class="card-body">
                <form id="urlImportForm">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-600">{{ translate('Product URLs') }} <span class="text-danger">*</span></label>
                        <textarea name="urls" class="form-control" rows="5"
                            placeholder="{{ translate('Enter one URL per line or comma-separated') }}" required></textarea>
                        <small class="text-muted">{{ translate('Up to 50 product URLs. One per line.') }}</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-600">{{ translate('AI Provider') }}</label>
                        <select name="provider" class="form-control form-control-sm">
                            <option value="gemini"  {{ config('seo.default_provider')==='gemini'  ? 'selected':'' }}>Gemini</option>
                            <option value="openai"  {{ config('seo.default_provider')==='openai'  ? 'selected':'' }}>OpenAI</option>
                            <option value="claude"  {{ config('seo.default_provider')==='claude'  ? 'selected':'' }}>Claude</option>
                        </select>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="urlEnrich" name="ai_enrich" value="1" checked>
                        <label class="custom-control-label" for="urlEnrich">{{ translate('AI Enrich') }}</label>
                    </div>
                    <div class="d-flex" style="gap:8px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="runPreview('url')">
                            <i class="las la-eye mr-1"></i>{{ translate('Preview') }}
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="las la-download mr-1"></i>{{ translate('Scrape & Import') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Loading --}}
<div id="importLoading" class="card import-card d-none">
    <div class="card-body text-center py-5">
        <div class="spinner-border text-primary mb-3" style="width:2.5rem;height:2.5rem;" role="status"></div>
        <p class="text-muted mb-0" id="loadingMsg">{{ translate('Scraping product data… please wait.') }}</p>
    </div>
</div>

{{-- Results Panel --}}
<div id="importResults" class="card import-card d-none">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 h6"><i class="las la-list-alt mr-1 text-primary"></i>{{ translate('Import Results') }}</h5>
        <span id="importSummary" class="badge badge-primary px-3 py-1" style="font-size:13px;"></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="result-table table table-hover table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th width="30">#</th>
                        <th width="50">{{ translate('Image') }}</th>
                        <th>{{ translate('Name / SKU') }}</th>
                        <th>{{ translate('Description') }}</th>
                        <th width="80">{{ translate('Price') }}</th>
                        <th width="100">{{ translate('Category') }}</th>
                        <th width="80">{{ translate('Source') }}</th>
                        <th width="110">{{ translate('Status') }}</th>
                        <th width="80">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody id="importTableBody"></tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span id="bulkProgress" class="text-muted fs-13"></span>
        <button id="bulkImportBtn" class="btn btn-primary d-none" onclick="bulkImport()">
            <i class="las la-check-circle mr-1"></i>{{ translate('Import All to Database') }}
        </button>
    </div>
</div>

@endsection

@section('script')
<script>
var importedProducts = [];
var CSRF = '{{ csrf_token() }}';
var SAVE_URL      = '{{ route("admin.import.products.save") }}';
var BULK_SAVE_URL = '{{ route("admin.import.products.bulk_save") }}';
var PDF_URL       = '{{ route("admin.import.products.pdf") }}';
var URLS_URL      = '{{ route("admin.import.products.urls") }}';
var PREVIEW_URL   = '{{ route("admin.import.products.preview") }}';
var CATEGORIES    = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));

/* ── Form submit handlers ── */
document.getElementById('pdfImportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitImport('pdf', new FormData(this));
});
document.getElementById('urlImportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitImport('url', new FormData(this));
});

/* ── Main import ── */
function submitImport(type, formData) {
    var url = type === 'pdf' ? PDF_URL : URLS_URL;
    setLoading(true, type === 'pdf' ? 'Parsing PDF and extracting products…' : 'Scraping product data from URL(s)…');

    fetch(url, {
        method:  'POST',
        headers: {'X-CSRF-TOKEN': CSRF},
        body:    formData,
    })
    .then(r => r.json())
    .then(data => {
        setLoading(false);
        if (data.success) {
            importedProducts = data.data.mapped || [];
            renderResults(importedProducts);
        } else {
            showError(data.message || 'Import failed.');
        }
    })
    .catch(err => { setLoading(false); showError('Request failed: ' + err); });
}

/* ── Preview (no save) ── */
function runPreview(type) {
    var form     = type === 'pdf' ? document.getElementById('pdfImportForm') : document.getElementById('urlImportForm');
    var formData = new FormData(form);
    formData.set('type', type);
    setLoading(true, 'Fetching preview data…');

    fetch(PREVIEW_URL, {
        method:  'POST',
        headers: {'X-CSRF-TOKEN': CSRF},
        body:    formData,
    })
    .then(r => r.json())
    .then(data => {
        setLoading(false);
        if (data.success) {
            var products = data.data.products
                ? (Array.isArray(data.data.products) ? data.data.products : Object.values(data.data.products))
                : [];
            importedProducts = products;
            renderResults(products, true);
        } else {
            showError(data.message || 'Preview failed.');
        }
    })
    .catch(err => { setLoading(false); showError('Request failed: ' + err); });
}

/* ── Render results table ── */
function renderResults(products, isPreview) {
    isPreview = isPreview || false;
    var tbody  = document.getElementById('importTableBody');
    var panel  = document.getElementById('importResults');
    var summary = document.getElementById('importSummary');
    var bulkBtn = document.getElementById('bulkImportBtn');

    tbody.innerHTML = '';
    panel.classList.remove('d-none');
    summary.textContent = products.length + ' product(s) found';
    document.getElementById('bulkProgress').textContent = '';

    if (products.length > 0 && !isPreview) {
        bulkBtn.classList.remove('d-none');
        bulkBtn.disabled = false;
        bulkBtn.innerHTML = '<i class="las la-check-circle mr-1"></i>Import All to Database';
    } else {
        bulkBtn.classList.add('d-none');
    }

    products.forEach(function(p, i) {
        var imgHtml = '';
        var imgSrc  = p.image || (Array.isArray(p.photos) && p.photos[0] ? p.photos[0] : '');
        if (imgSrc) {
            imgHtml = '<img src="' + imgSrc + '" style="width:40px;height:40px;object-fit:cover;border-radius:4px;" onerror="this.style.display=\'none\'">';
        } else {
            imgHtml = '<span class="text-muted" style="font-size:20px;"><i class="las la-image"></i></span>';
        }

        var descText = p.description ? p.description.substring(0, 80) + (p.description.length > 80 ? '…' : '') : '-';

        // Build category options
        var catOptions = '<option value="">-- Select --</option>';
        var currentCat = p.category || p.brand || '';
        CATEGORIES.forEach(function(c) {
            var sel = (c.name.toLowerCase() === currentCat.toLowerCase()) ? ' selected' : '';
            catOptions += '<option value="' + c.id + '"' + sel + '>' + c.name + '</option>';
        });

        var currentPrice = p.unit_price ? parseFloat(p.unit_price).toFixed(2) : '';

        var tr = document.createElement('tr');
        tr.id  = 'row-' + i;
        tr.innerHTML =
            '<td>' + (i+1) + '</td>' +
            '<td class="text-center">' + imgHtml + '</td>' +
            '<td><strong>' + (p.name || '-') + '</strong>' +
                (p.sku || p.barcode ? '<br><small class="text-muted">SKU: ' + (p.sku || p.barcode) + '</small>' : '') +
            '</td>' +
            '<td><small class="text-muted">' + descText + '</small></td>' +
            '<td>' +
                '<div class="input-group input-group-sm" style="min-width:90px;">' +
                    '<div class="input-group-prepend"><span class="input-group-text px-1">$</span></div>' +
                    '<input type="number" id="price-' + i + '" class="form-control form-control-sm" ' +
                        'value="' + currentPrice + '" min="0" step="0.01" style="width:70px;">' +
                '</div>' +
            '</td>' +
            '<td>' +
                '<select id="cat-' + i + '" class="form-control form-control-sm" style="min-width:130px;">' +
                    catOptions +
                '</select>' +
            '</td>' +
            '<td><span class="badge badge-light">' + (p.import_source || 'import') + '</span></td>' +
            '<td id="status-' + i + '"><span class="status-badge status-pending"></span>Pending</td>' +
            '<td>' + (isPreview ? '<span class="text-muted fs-12">Preview only</span>' :
                '<button class="btn btn-xs btn-outline-primary" id="savebtn-' + i + '" onclick="saveSingle(' + i + ')">Save</button>') +
            '</td>';
        tbody.appendChild(tr);
    });

    // Scroll to results
    panel.scrollIntoView({behavior: 'smooth', block: 'start'});
}

/* ── Save single product ── */
function saveSingle(index) {
    var product = importedProducts[index];
    if (!product) return;

    // Read editable values from the row
    var priceInput = document.getElementById('price-' + index);
    var catSelect  = document.getElementById('cat-' + index);
    if (priceInput) product.unit_price = parseFloat(priceInput.value) || 0;
    if (catSelect && catSelect.value) {
        product.category_id = parseInt(catSelect.value);
        product.category    = catSelect.options[catSelect.selectedIndex].text;
    }

    setRowStatus(index, 'saving', 'Saving…');
    document.getElementById('savebtn-' + index).disabled = true;

    fetch(SAVE_URL, {
        method:  'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body:    JSON.stringify({product: product}),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            var btn = document.getElementById('savebtn-' + index);
            if (data.skipped) {
                setRowStatus(index, 'saved',
                    '<a href="' + data.edit_url + '" target="_blank" class="badge badge-warning">Skipped (exists)</a>');
                btn.textContent = 'Exists';
                btn.className   = 'btn btn-xs btn-warning';
                btn.disabled    = true;
            } else {
                setRowStatus(index, 'saved',
                    '<a href="' + data.edit_url + '" target="_blank" class="badge badge-success">Saved ✔ Edit</a>');
                btn.textContent = '✔ Saved';
                btn.className   = 'btn btn-xs save-btn-saved';
                btn.disabled    = true;
            }
        } else {
            setRowStatus(index, 'error', 'Error: ' + (data.message || 'Failed'));
            document.getElementById('savebtn-' + index).disabled = false;
        }
    })
    .catch(err => {
        setRowStatus(index, 'error', 'Error: ' + err);
        document.getElementById('savebtn-' + index).disabled = false;
    });
}

/* ── Bulk save all ── */
function bulkImport() {
    if (!importedProducts.length) return;

    // Read editable price / category from every row before submitting
    importedProducts.forEach(function(product, i) {
        var priceInput = document.getElementById('price-' + i);
        var catSelect  = document.getElementById('cat-' + i);
        if (priceInput) product.unit_price = parseFloat(priceInput.value) || 0;
        if (catSelect && catSelect.value) {
            product.category_id = parseInt(catSelect.value);
            product.category    = catSelect.options[catSelect.selectedIndex].text;
        }
    });

    var btn = document.getElementById('bulkImportBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>Importing…';
    document.getElementById('bulkProgress').textContent = 'Sending ' + importedProducts.length + ' products…';

    fetch(BULK_SAVE_URL, {
        method:  'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body:    JSON.stringify({products: importedProducts}),
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<i class="las la-check-circle mr-1"></i>Done';

        // Update row statuses — saved
        importedProducts.forEach(function(p, i) {
            var saved   = data.saved   && data.saved.find(function(s){ return s.name === p.name; });
            var skipped = data.skipped && data.skipped.find(function(s){ return s.name === p.name; });
            var btn2    = document.getElementById('savebtn-' + i);
            if (saved) {
                setRowStatus(i, 'saved',
                    '<a href="' + saved.edit_url + '" target="_blank" class="badge badge-success">Saved ✔ Edit</a>');
                if (btn2) { btn2.textContent = '✔ Saved'; btn2.className = 'btn btn-xs save-btn-saved'; btn2.disabled = true; }
            } else if (skipped) {
                setRowStatus(i, 'saved',
                    '<a href="' + skipped.edit_url + '" target="_blank" class="badge badge-warning">Skipped (exists)</a>');
                if (btn2) { btn2.textContent = 'Exists'; btn2.className = 'btn btn-xs btn-warning'; btn2.disabled = true; }
            }
        });

        // Mark errors
        if (data.errors && data.errors.length) {
            data.errors.forEach(function(errMsg) {
                var match = errMsg.match(/^Row (\d+)/);
                if (match) setRowStatus(parseInt(match[1]), 'error', errMsg.replace(/^Row \d+ /, ''));
            });
        }

        var savedCount   = data.saved   ? data.saved.length   : 0;
        var skippedCount = data.skipped ? data.skipped.length : 0;
        document.getElementById('bulkProgress').innerHTML =
            '<span class="text-success fw-600">' + savedCount + ' saved</span>' +
            (skippedCount ? ' &nbsp;<span class="text-warning fw-600">' + skippedCount + ' skipped (already exist)</span>' : '') +
            (data.errors && data.errors.length ? ' &nbsp;<span class="text-danger">' + data.errors.length + ' failed</span>' : '');

        if (typeof AIZ !== 'undefined') {
            AIZ.plugins.notify(data.saved && data.saved.length ? 'success' : 'warning', data.message || 'Import complete.');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="las la-check-circle mr-1"></i>Retry';
        document.getElementById('bulkProgress').textContent = 'Error: ' + err;
    });
}

/* ── Helpers ── */
function setRowStatus(index, state, html) {
    var cell = document.getElementById('status-' + index);
    if (!cell) return;
    var dot = '<span class="status-badge status-' + state + '"></span>';
    cell.innerHTML = dot + html;
}

function setLoading(show, msg) {
    document.getElementById('importLoading').classList.toggle('d-none', !show);
    document.getElementById('importResults').classList.add('d-none');
    if (msg) document.getElementById('loadingMsg').textContent = msg;
}

function showError(msg) {
    if (typeof AIZ !== 'undefined') {
        AIZ.plugins.notify('danger', msg);
    } else {
        alert('Error: ' + msg);
    }
}
</script>
@endsection
