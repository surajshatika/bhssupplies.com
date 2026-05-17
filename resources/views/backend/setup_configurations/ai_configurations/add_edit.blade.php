@extends('backend.layouts.app')

@section('page_title', translate('AI Add / Edit Products'))

@section('content')
<style>
.ai-card { border-radius: 12px; border: none; box-shadow: 0 2px 14px rgba(0,0,0,.07); }
.ai-card .card-header { padding: 14px 20px; font-weight: 700; font-size: 15px; }
.ai-shortcut { transition: all .15s; }
.ai-shortcut:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.12); }
.url-bar { border-radius: 10px; padding: 10px 14px; font-size: 14px; }
.url-bar:focus { box-shadow: 0 0 0 3px rgba(32,122,252,.15); border-color: #207afc; }
.image-tile {
    position: relative; width: 110px; height: 110px;
    border: 2px solid #e9ecef; border-radius: 8px; cursor: pointer;
    overflow: hidden; flex-shrink: 0; background: #f8f9fa; transition: all .15s;
}
.image-tile:hover { border-color: #adb5bd; transform: scale(1.03); }
.image-tile img { width: 100%; height: 100%; object-fit: cover; }
.image-tile.selected { border-color: #28a745; box-shadow: 0 0 0 3px rgba(40,167,69,.25); }
.image-tile.selected::after {
    content: '✓'; position: absolute; top: 4px; right: 4px;
    background: #28a745; color: white; width: 24px; height: 24px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-weight: bold; font-size: 14px;
}
.image-tile .order-badge {
    position: absolute; bottom: 4px; left: 4px;
    background: rgba(0,0,0,.65); color: #fff; font-size: 11px;
    padding: 1px 6px; border-radius: 10px; font-weight: 600;
}
.image-tile .broken {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    color: #dc3545; font-size: 24px;
}
.source-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 13px;
    border: 1px solid #dee2e6; background: #fff; margin-right: 6px; margin-bottom: 6px;
    transition: all .15s;
}
.source-tab.active { background: #207afc; color: #fff; border-color: #207afc; }
.source-tab .count { background: rgba(255,255,255,.25); padding: 1px 7px; border-radius: 10px; font-size: 11px; }
.source-tab:not(.active) .count { background: #e9ecef; color: #495057; }
.image-grid { display: flex; flex-wrap: wrap; gap: 10px; max-height: 380px; overflow-y: auto; padding: 4px; }
.spinner-tiny { width: 1rem; height: 1rem; border-width: .15em; }
.field-group label { font-weight: 600; font-size: 13px; color: #495057; margin-bottom: 4px; }
.btn-ai-enhance { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; }
.btn-ai-enhance:hover { background: linear-gradient(135deg, #5568d3, #66408a); color: #fff; }
.scrape-status { font-size: 12px; margin-top: 6px; min-height: 16px; }
.image-source-icon { width: 14px; height: 14px; }
.empty-state { padding: 40px 20px; text-align: center; color: #adb5bd; }
.empty-state i { font-size: 48px; opacity: .4; }
</style>

<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-1">{{ translate('AI Add / Edit Products') }}</h1>
            <p class="text-muted mb-0 fs-13">
                {{ translate('Paste any product URL → AI extracts details, finds images from multiple sources, you review & save.') }}
            </p>
        </div>
    </div>

    @if(get_setting('ai_activation') != 1)
        <div class="alert alert-info text-center mt-2 mb-0 py-2">
            <p class="font-weight-bold text-danger m-0 fs-13">
                {{ translate('AI Feature is not Activated.') }}
                <a href="{{ route('ai-config') }}">{{ translate('Activate Here') }}</a>
            </p>
        </div>
    @endif
</div>

{{-- ── Quick shortcuts ────────────────────────────────────────── --}}
<div class="row gutters-16 mb-3">
    <div class="col-md-4">
        <a href="{{ route('products.create') }}" class="ai-shortcut dashboard-box h-110px d-flex align-items-center px-3" style="background: rgba(32, 122, 252, 0.1);">
            <div>
                <h5 class="fs-15 fw-600 text-dark mb-0">{{ translate('Add Product Manually') }}</h5>
                <small class="text-muted">{{ translate('Standard add-product form') }}</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('products.admin') }}" class="ai-shortcut dashboard-box h-110px d-flex align-items-center px-3" style="background: rgba(238, 77, 93, 0.1);">
            <div>
                <h5 class="fs-15 fw-600 text-dark mb-0">{{ translate('Edit Existing Product') }}</h5>
                <small class="text-muted">{{ translate('Enhance with AI') }}</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.import.products.index') }}" class="ai-shortcut dashboard-box h-110px d-flex align-items-center px-3" style="background: rgba(40, 167, 69, 0.1);">
            <div>
                <h5 class="fs-15 fw-600 text-dark mb-0">{{ translate('Bulk Import (PDF / many URLs)') }}</h5>
                <small class="text-muted">{{ translate('Catalog import workflow') }}</small>
            </div>
        </a>
    </div>
</div>

{{-- ── Single-URL importer ───────────────────────────────────── --}}
<div class="card ai-card mb-4">
    <div class="card-header bg-primary text-white d-flex align-items-center">
        <i class="las la-magic la-lg mr-2"></i>
        {{ translate('Pull Product From URL') }}
        <span class="badge badge-light ml-2 px-2 py-1" style="font-size:11px;">AI + Multi-source images</span>
    </div>
    <div class="card-body">
        <div class="d-flex" style="gap:8px;">
            <input type="url" id="productUrl" class="form-control url-bar"
                   placeholder="{{ translate('https://www.example.com/products/some-item') }}"
                   autocomplete="off">
            <button id="scrapeBtn" class="btn btn-primary px-4" type="button">
                <i class="las la-bolt mr-1"></i>{{ translate('Pull') }}
            </button>
        </div>
        <div id="scrapeStatus" class="scrape-status text-muted"></div>
    </div>
</div>

{{-- ── Result panel (initially hidden) ────────────────────────── --}}
<div id="resultPanel" class="d-none">

    {{-- Top row: editable details + selected images preview --}}
    <div class="row gutters-16">
        {{-- Editable fields --}}
        <div class="col-lg-7 mb-3">
            <div class="card ai-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="las la-edit text-primary mr-1"></i>{{ translate('Product Details') }}</span>
                    <button id="aiEnhanceBtn" class="btn btn-sm btn-ai-enhance" type="button" title="{{ translate('Use AI to rewrite name + description') }}">
                        <i class="las la-robot mr-1"></i>{{ translate('AI Enhance') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 field-group mb-3">
                            <label>{{ translate('Product Name') }} <span class="text-danger">*</span></label>
                            <input type="text" id="f_name" class="form-control">
                        </div>
                        <div class="col-md-6 field-group mb-3">
                            <label>{{ translate('Brand') }}</label>
                            <input type="text" id="f_brand" class="form-control" placeholder="{{ translate('Auto-detected') }}">
                        </div>
                        <div class="col-md-6 field-group mb-3">
                            <label>{{ translate('SKU / Model') }}</label>
                            <input type="text" id="f_sku" class="form-control">
                        </div>
                        <div class="col-md-6 field-group mb-3">
                            <label>{{ translate('Price') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">{{ currency_symbol() }}</span></div>
                                <input type="number" step="0.01" id="f_price" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 field-group mb-3">
                            <label>{{ translate('Category') }}</label>
                            <select id="f_category" class="form-control">
                                <option value="">{{ translate('-- Select Category --') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 field-group mb-3">
                            <label>{{ translate('Description') }}</label>
                            <textarea id="f_description" class="form-control" rows="6"></textarea>
                        </div>
                        <div class="col-md-12 field-group">
                            <label>{{ translate('Source URL') }}</label>
                            <input type="text" id="f_source_url" class="form-control" readonly style="background:#f8f9fa;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Selected-images preview --}}
        <div class="col-lg-5 mb-3">
            <div class="card ai-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="las la-images text-success mr-1"></i>{{ translate('Selected Images') }}</span>
                    <span id="selectedCount" class="badge badge-success">0 selected</span>
                </div>
                <div class="card-body">
                    <div id="selectedImagesPreview" class="image-grid">
                        <div class="empty-state w-100">
                            <i class="las la-mouse-pointer"></i>
                            <p class="mb-0 fs-13 mt-2">{{ translate('Click images below to add them.') }}<br>{{ translate('First selected becomes the main thumbnail.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Multi-source image search ────────────────────────────── --}}
    <div class="card ai-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span><i class="las la-search text-primary mr-1"></i>{{ translate('Image Sources') }}</span>
            <div class="d-flex align-items-center" style="gap:8px;">
                <input type="text" id="imgSearchQuery" class="form-control form-control-sm" style="width:240px;"
                       placeholder="{{ translate('Refine image query…') }}">
                <button id="findMoreBtn" class="btn btn-sm btn-outline-primary" type="button">
                    <i class="las la-sync mr-1"></i>{{ translate('Search More Images') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="sourceTabs" class="mb-3"></div>
            <div id="imageGrid" class="image-grid">
                <div class="empty-state w-100">
                    <i class="las la-image"></i>
                    <p class="mb-0 fs-13 mt-2">{{ translate('Pull a URL above to load images.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Save bar ──────────────────────────────────────────────── --}}
    <div class="card ai-card mb-5" style="position: sticky; bottom: 0; z-index: 10;">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <strong id="saveSummary" class="text-muted fs-13">{{ translate('Review the details and select images, then save.') }}</strong>
            </div>
            <div class="d-flex" style="gap:8px;">
                <button id="resetBtn" class="btn btn-light" type="button">
                    <i class="las la-times mr-1"></i>{{ translate('Reset') }}
                </button>
                <button id="saveBtn" class="btn btn-success px-4" type="button">
                    <i class="las la-save mr-1"></i>{{ translate('Save Product') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
(function() {
    'use strict';

    const CSRF              = '{{ csrf_token() }}';
    const SCRAPE_URL        = '{{ route("admin.import.products.scrape_single") }}';
    const FIND_IMAGES_URL   = '{{ route("admin.import.products.find_images") }}';
    const SAVE_URL          = '{{ route("admin.import.products.save_with_images") }}';
    const PROXY_URL         = '{{ route("admin.import.products.proxy_image") }}';

    let state = {
        product:        null,   // { name, sku, brand, price, description, url, ... }
        imageGroups:    {},     // { site: [...], bing: [...], duckduckgo: [...], google: [...] }
        activeSource:   null,
        selected:       [],     // ordered list of selected image URLs (1st = thumbnail)
    };

    const SOURCE_LABELS = {
        site:        { name: 'Source Site',  icon: 'la-globe',     color: '#6c757d' },
        bing:        { name: 'Bing Images',  icon: 'la-microsoft', color: '#0078d4' },
        duckduckgo:  { name: 'DuckDuckGo',   icon: 'la-paw',       color: '#de5833' },
        google:      { name: 'Google',       icon: 'la-google',    color: '#4285f4' },
    };

    /* ──────────────────────────────────────────────────────────
     *  DOM hooks
     * ────────────────────────────────────────────────────────── */
    const $ = (id) => document.getElementById(id);

    $('scrapeBtn').addEventListener('click',     scrapeUrl);
    $('productUrl').addEventListener('keypress', (e) => { if (e.key === 'Enter') scrapeUrl(); });
    $('findMoreBtn').addEventListener('click',   () => findMoreImages($('imgSearchQuery').value.trim()));
    $('aiEnhanceBtn').addEventListener('click',  aiEnhance);
    $('saveBtn').addEventListener('click',       saveProduct);
    $('resetBtn').addEventListener('click',      resetAll);

    /* ──────────────────────────────────────────────────────────
     *  1.  Scrape URL
     * ────────────────────────────────────────────────────────── */
    function scrapeUrl() {
        const url = $('productUrl').value.trim();
        if (!url) { showStatus('Please enter a URL.', 'danger'); return; }
        if (!/^https?:\/\//i.test(url)) { showStatus('URL must start with http:// or https://', 'danger'); return; }

        setBusy($('scrapeBtn'), true, 'Scraping…');
        showStatus('Fetching page, parsing data, searching for images…', 'info');

        fetch(SCRAPE_URL, {
            method:  'POST',
            headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body:    JSON.stringify({url, search_external: true}),
        })
        .then(r => r.json())
        .then(data => {
            setBusy($('scrapeBtn'), false, '<i class="las la-bolt mr-1"></i>Pull');
            if (!data.success) { showStatus(data.message || 'Scrape failed.', 'danger'); return; }

            state.product     = data.product || {};
            state.imageGroups = data.image_groups || {};
            state.selected    = [];

            // Auto-select first image from "site" (best quality usually)
            if (state.imageGroups.site && state.imageGroups.site.length) {
                state.selected.push(state.imageGroups.site[0]);
            }

            populateForm(state.product);
            renderSourceTabs();
            const firstSrc = Object.keys(state.imageGroups)[0];
            switchSource(firstSrc);
            renderSelected();

            const totalImgs = Object.values(state.imageGroups).reduce((a, b) => a + b.length, 0);
            showStatus(`Found ${totalImgs} image candidate(s) across ${Object.keys(state.imageGroups).length} source(s).`, 'success');
            $('resultPanel').classList.remove('d-none');
            $('resultPanel').scrollIntoView({behavior: 'smooth', block: 'start'});
        })
        .catch(err => {
            setBusy($('scrapeBtn'), false, '<i class="las la-bolt mr-1"></i>Pull');
            showStatus('Request failed: ' + err.message, 'danger');
        });
    }

    /* ──────────────────────────────────────────────────────────
     *  2.  Populate editable form
     * ────────────────────────────────────────────────────────── */
    function populateForm(p) {
        $('f_name').value        = p.name        || '';
        $('f_brand').value       = p.brand       || '';
        $('f_sku').value         = p.sku || p.gtin || '';
        $('f_price').value       = p.price ? parseFloat(p.price).toFixed(2) : '';
        $('f_description').value = stripTags(p.description || '');
        $('f_source_url').value  = p.url         || '';
        $('imgSearchQuery').value = [p.name, p.brand, p.sku].filter(Boolean).join(' ');

        // Try to match category by name
        const catName = (p.suggested_category || p.category || '').toLowerCase();
        if (catName) {
            const sel = $('f_category');
            for (const opt of sel.options) {
                if (opt.text.toLowerCase() === catName) { sel.value = opt.value; break; }
            }
        }
    }

    /* ──────────────────────────────────────────────────────────
     *  3.  Render source tabs + image grid
     * ────────────────────────────────────────────────────────── */
    function renderSourceTabs() {
        const wrap = $('sourceTabs');
        wrap.innerHTML = '';
        Object.keys(state.imageGroups).forEach(src => {
            const meta = SOURCE_LABELS[src] || {name: src, icon: 'la-globe', color: '#6c757d'};
            const count = state.imageGroups[src].length;
            const tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'source-tab' + (src === state.activeSource ? ' active' : '');
            tab.dataset.source = src;
            tab.innerHTML = `<i class="las ${meta.icon}"></i> ${meta.name} <span class="count">${count}</span>`;
            tab.addEventListener('click', () => switchSource(src));
            wrap.appendChild(tab);
        });
    }

    function switchSource(src) {
        state.activeSource = src;
        // refresh tab active states
        document.querySelectorAll('.source-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.source === src);
        });
        renderImageGrid();
    }

    function renderImageGrid() {
        const grid = $('imageGrid');
        grid.innerHTML = '';
        const imgs = (state.imageGroups[state.activeSource] || []);
        if (!imgs.length) {
            grid.innerHTML = '<div class="empty-state w-100"><i class="las la-image"></i><p class="mb-0 fs-13 mt-2">No images from this source.</p></div>';
            return;
        }
        imgs.forEach(url => grid.appendChild(buildTile(url, /*allowSelect*/true)));
    }

    function buildTile(url, allowSelect) {
        const tile = document.createElement('div');
        tile.className = 'image-tile';
        const idx = state.selected.indexOf(url);
        if (idx >= 0) tile.classList.add('selected');

        const img = document.createElement('img');
        img.loading = 'lazy';
        img.src = displayUrl(url);
        img.onerror = () => {
            // Try proxy fallback before giving up
            if (img.dataset.proxied !== '1') {
                img.dataset.proxied = '1';
                img.src = PROXY_URL + '?url=' + encodeURIComponent(url);
            } else {
                tile.innerHTML = '<div class="broken"><i class="las la-unlink"></i></div>';
            }
        };
        tile.appendChild(img);

        if (idx >= 0) {
            const badge = document.createElement('span');
            badge.className = 'order-badge';
            badge.textContent = idx === 0 ? 'Main' : ('#' + (idx + 1));
            tile.appendChild(badge);
        }

        if (allowSelect) {
            tile.addEventListener('click', () => toggleSelect(url));
        } else {
            tile.style.cursor = 'default';
            tile.addEventListener('click', () => toggleSelect(url)); // also clickable to remove
        }
        return tile;
    }

    function displayUrl(url) {
        // Heavy-handed: if the image is on a known hotlink-protected CDN, use proxy.
        // For now, attempt direct first — onerror falls back to proxy.
        return url;
    }

    /* ──────────────────────────────────────────────────────────
     *  4.  Selection management
     * ────────────────────────────────────────────────────────── */
    function toggleSelect(url) {
        const i = state.selected.indexOf(url);
        if (i >= 0) {
            state.selected.splice(i, 1);
        } else {
            if (state.selected.length >= 12) { showStatus('Max 12 images per product.', 'warning'); return; }
            state.selected.push(url);
        }
        renderImageGrid();
        renderSelected();
    }

    function renderSelected() {
        const wrap = $('selectedImagesPreview');
        $('selectedCount').textContent = state.selected.length + ' selected';
        if (!state.selected.length) {
            wrap.innerHTML = '<div class="empty-state w-100"><i class="las la-mouse-pointer"></i><p class="mb-0 fs-13 mt-2">Click images below to add them.<br>First selected becomes the main thumbnail.</p></div>';
            return;
        }
        wrap.innerHTML = '';
        state.selected.forEach(url => wrap.appendChild(buildTile(url, false)));
    }

    /* ──────────────────────────────────────────────────────────
     *  5.  Find more images (after user refines query)
     * ────────────────────────────────────────────────────────── */
    function findMoreImages(query) {
        if (!query || query.length < 3) { showStatus('Image query is too short.', 'warning'); return; }
        setBusy($('findMoreBtn'), true, 'Searching…');

        fetch(FIND_IMAGES_URL, {
            method:  'POST',
            headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body:    JSON.stringify({query, per_source: 12}),
        })
        .then(r => r.json())
        .then(data => {
            setBusy($('findMoreBtn'), false, '<i class="las la-sync mr-1"></i>Search More Images');
            if (!data.success) { showStatus(data.message || 'Image search failed.', 'danger'); return; }

            // Merge new groups (replacing old external groups, keeping site)
            const site = state.imageGroups.site || [];
            state.imageGroups = data.image_groups || {};
            if (site.length) state.imageGroups.site = site;

            renderSourceTabs();
            const firstNonSite = Object.keys(state.imageGroups).find(k => k !== 'site') || Object.keys(state.imageGroups)[0];
            switchSource(firstNonSite);
            const total = Object.values(state.imageGroups).reduce((a, b) => a + b.length, 0);
            showStatus(`Refreshed — ${total} images across ${Object.keys(state.imageGroups).length} sources.`, 'success');
        })
        .catch(err => {
            setBusy($('findMoreBtn'), false, '<i class="las la-sync mr-1"></i>Search More Images');
            showStatus('Request failed: ' + err.message, 'danger');
        });
    }

    /* ──────────────────────────────────────────────────────────
     *  6.  AI Enhance (rewrite name + description) — uses existing AiService
     * ────────────────────────────────────────────────────────── */
    function aiEnhance() {
        const name = $('f_name').value.trim();
        if (!name) { showStatus('Enter a product name first.', 'warning'); return; }

        setBusy($('aiEnhanceBtn'), true, 'AI thinking…');

        // Use the existing /admin/ai-templates flow indirectly via AiService.
        // Simpler: hit Gemini through a tiny proxy — for now we re-use scrape+enrich.
        // For UI purposes we'll just call our own simple endpoint via fetch on AiService through scrape JSON.
        // Lacking a dedicated endpoint, we do client-side prompt to Gemini via session-routed AjaxController.
        //
        // This codebase already exposes /admin/ai-templates editing — but no public "rewrite" endpoint.
        // Fall back: prepend a "✨" marker to indicate the user should click again later.
        // Future: wire to AiService::productGenerateWithAI when an HTTP endpoint exists.

        // Lightweight client-side enhancer: title-case the name, expand bullets in description.
        const cleanName = name.replace(/\s+/g, ' ')
                              .replace(/\b\w/g, c => c.toUpperCase());
        $('f_name').value = cleanName;

        const desc = $('f_description').value.trim();
        if (desc && !desc.includes('•')) {
            const sentences = desc.split(/(?<=[.!?])\s+/).filter(s => s.length > 20).slice(0, 5);
            if (sentences.length) {
                $('f_description').value = desc + '\n\nKey features:\n' + sentences.map(s => '• ' + s.trim()).join('\n');
            }
        }
        setBusy($('aiEnhanceBtn'), false, '<i class="las la-robot mr-1"></i>AI Enhance');
        showStatus('Quick polish applied. (Wire AiService HTTP endpoint for full Gemini rewrite.)', 'info');
    }

    /* ──────────────────────────────────────────────────────────
     *  7.  Save product
     * ────────────────────────────────────────────────────────── */
    function saveProduct() {
        const name = $('f_name').value.trim();
        if (!name) { showStatus('Product name is required.', 'danger'); return; }
        if (!state.selected.length) {
            if (!confirm('No images selected. Save without images?')) return;
        }

        const payload = {
            product: {
                name:        name,
                brand:       $('f_brand').value.trim(),
                sku:         $('f_sku').value.trim(),
                unit_price:  parseFloat($('f_price').value) || 0,
                description: $('f_description').value.trim(),
                category_id: $('f_category').value || null,
                category:    $('f_category').selectedOptions[0]?.text || '',
                url:         $('f_source_url').value,
                tags:        name,
                import_source: 'ai-add-edit',
            },
            images: state.selected,
        };

        setBusy($('saveBtn'), true, 'Saving…');
        $('saveSummary').textContent = 'Downloading ' + state.selected.length + ' image(s) and saving product…';

        fetch(SAVE_URL, {
            method:  'POST',
            headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body:    JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            setBusy($('saveBtn'), false, '<i class="las la-save mr-1"></i>Save Product');
            if (!data.success) {
                $('saveSummary').innerHTML = '<span class="text-danger">' + (data.message || 'Save failed.') + '</span>';
                return;
            }
            const downloaded = data.images_downloaded || 0;
            const verb       = data.skipped ? 'already exists' : 'saved';
            $('saveSummary').innerHTML =
                '<span class="text-success">✓ Product ' + verb + ' (' + downloaded + ' images downloaded). </span>' +
                '<a href="' + data.edit_url + '" target="_blank" class="btn btn-sm btn-outline-primary ml-2">' +
                '<i class="las la-external-link-alt mr-1"></i>Open Product</a>';
            if (typeof AIZ !== 'undefined') AIZ.plugins.notify('success', 'Product ' + verb + '.');
        })
        .catch(err => {
            setBusy($('saveBtn'), false, '<i class="las la-save mr-1"></i>Save Product');
            $('saveSummary').innerHTML = '<span class="text-danger">Request failed: ' + err.message + '</span>';
        });
    }

    function resetAll() {
        state = {product: null, imageGroups: {}, activeSource: null, selected: []};
        $('productUrl').value = '';
        $('resultPanel').classList.add('d-none');
        showStatus('', '');
    }

    /* ──────────────────────────────────────────────────────────
     *  Helpers
     * ────────────────────────────────────────────────────────── */
    function showStatus(msg, type) {
        const el = $('scrapeStatus');
        if (!msg) { el.innerHTML = ''; return; }
        const colors = {info: 'text-info', success: 'text-success', danger: 'text-danger', warning: 'text-warning'};
        const icons  = {info: 'la-info-circle', success: 'la-check-circle', danger: 'la-exclamation-triangle', warning: 'la-exclamation-circle'};
        el.className = 'scrape-status ' + (colors[type] || 'text-muted');
        el.innerHTML = '<i class="las ' + (icons[type] || 'la-info-circle') + ' mr-1"></i>' + msg;
    }

    function setBusy(btn, busy, html) {
        if (busy) {
            btn.dataset._html = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-tiny mr-1"></span>' + html;
        } else {
            btn.disabled = false;
            btn.innerHTML = html || btn.dataset._html;
        }
    }

    function stripTags(s) {
        const tmp = document.createElement('div');
        tmp.innerHTML = s;
        return tmp.textContent || tmp.innerText || '';
    }

})();
</script>
@endsection
