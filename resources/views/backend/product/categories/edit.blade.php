@extends('backend.layouts.app')

@section('content')

@php
    CoreComponentRepository::instantiateShopRepository();
    CoreComponentRepository::initializeCache();
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{translate('Category Information')}}</h5>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-body p-0">
                <ul class="nav nav-tabs nav-fill border-light">
                    @foreach (\App\Models\Language::all() as $key => $language)
                    <li class="nav-item">
                        <a class="nav-link text-reset @if ($language->code == $lang) active @else bg-soft-dark border-light border-left-0 @endif py-3" href="{{ route('categories.edit', ['category'=>$category->id, 'lang'=> $language->code] ) }}">
                            <img src="{{ static_asset('assets/img/flags/'.$language->code.'.png') }}" height="11" class="mr-1">
                            <span>{{$language->name}}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
                <form class="p-4" action="{{ route('categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                    <input name="_method" type="hidden" value="PATCH">
    	            <input type="hidden" name="lang" value="{{ $lang }}">
                	@csrf
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">{{translate('Name')}} <i class="las la-language text-danger" title="{{translate('Translatable')}}"></i></label>
                        <div class="col-md-9">
                            <input type="text" name="name" value="{{ $category->getTranslation('name', $lang) }}" class="form-control" id="name" placeholder="{{translate('Name')}}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">{{translate('Parent Category')}}</label>
                        <div class="col-md-9">
                            <select class="select2 form-control aiz-selectpicker" name="parent_id" data-toggle="select2" data-placeholder="Choose ..."data-live-search="true" data-selected="{{ $category->parent_id }}">
                                <option value="0">{{ translate('No Parent') }}</option>
                                @foreach ($categories as $acategory)
                                    <option value="{{ $acategory->id }}">{{ $acategory->getTranslation('name') }}</option>
                                    @foreach ($acategory->childrenCategories as $childCategory)
                                        @include('categories.child_category', ['child_category' => $childCategory])
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">{{translate('Ordering Number')}}</label>
                        <div class="col-md-9">
                            <input type="number" name="order_level" value="{{ $category->order_level }}" class="form-control" id="order_level" placeholder="{{translate('Order Level')}}">
                            <small>{{translate('Higher number has high priority')}}</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">{{translate('Type')}}</label>
                        <div class="col-md-9">
                            <select name="digital" required class="form-control aiz-selectpicker mb-2 mb-md-0">
                                <option value="0" @if ($category->digital == '0') selected @endif>{{translate('Physical')}}</option>
                                <option value="1" @if ($category->digital == '1') selected @endif>{{translate('Digital')}}</option>
                            </select>
                        </div>
                    </div>
    	            <div class="form-group row">
                        <label class="col-md-3 col-form-label" for="signinSrEmail">{{translate('Banner')}} <small>({{ translate('200x200') }})</small></label>
                        <div class="col-md-9">
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                </div>
                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                <input type="hidden" name="banner" class="selected-files" value="{{ $category->banner }}">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label" for="signinSrEmail">{{translate('Icon')}} <small>({{ translate('32x32') }})</small></label>
                        <div class="col-md-9">
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                </div>
                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                <input type="hidden" name="icon" class="selected-files" value="{{ $category->icon }}">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>

                    {{-- ===== SEO SECTION ===== --}}
                    <div class="border-top pt-3 mt-2 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 font-weight-600 text-primary">
                                <i class="las la-search mr-1"></i>{{ translate('SEO Settings') }}
                            </h6>
                            <button type="button" id="aiSeoBtn" class="btn btn-sm btn-outline-primary"
                                    onclick="aiAutoFillSeo()" title="{{ translate('Auto-fill with AI') }}">
                                <i class="las la-magic mr-1"></i>{{ translate('AI Auto-Fill') }}
                                <span id="aiSeoSpinner" class="spinner-border spinner-border-sm ml-1 d-none" role="status"></span>
                            </button>
                        </div>

                        {{-- Meta Title --}}
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Meta Title')}}</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="meta_title" id="meta_title"
                                       value="{{ $category->meta_title }}"
                                       placeholder="{{translate('Meta Title')}}"
                                       oninput="updateSeoAnalysis()" maxlength="70">
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">{{ translate('Ideal: 50–60 characters') }}</small>
                                    <small id="meta_title_count" class="font-weight-600">0 / 60</small>
                                </div>
                                <div class="progress mt-1" style="height:4px;">
                                    <div id="meta_title_bar" class="progress-bar" style="width:0%;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Meta Description --}}
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Meta Description')}}</label>
                            <div class="col-md-9">
                                <textarea name="meta_description" id="meta_description" rows="4"
                                          class="form-control"
                                          oninput="updateSeoAnalysis()"
                                          maxlength="200">{{ $category->meta_description }}</textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">{{ translate('Ideal: 150–160 characters') }}</small>
                                    <small id="meta_desc_count" class="font-weight-600">0 / 160</small>
                                </div>
                                <div class="progress mt-1" style="height:4px;">
                                    <div id="meta_desc_bar" class="progress-bar" style="width:0%;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Slug --}}
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Slug')}}</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="text" placeholder="{{translate('Slug')}}" id="slug" name="slug"
                                           value="{{ $category->slug }}" class="form-control"
                                           oninput="updateSeoAnalysis()">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="autoSlug()" title="{{ translate('Generate from name') }}">
                                            <i class="las la-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">{{ url('/') }}/<span id="slug_preview" class="text-dark font-weight-600">{{ $category->slug }}</span></small>
                            </div>
                        </div>
                    </div>

                    {{-- ===== LIVE SEO ANALYSIS PANEL ===== --}}
                    <div class="border rounded p-3 mb-3 bg-light" id="seoAnalysisPanel">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 font-weight-600"><i class="las la-chart-bar mr-1 text-primary"></i>{{ translate('Live SEO Analysis') }}</h6>
                            <div class="d-flex align-items-center">
                                <span class="mr-2 text-muted small">{{ translate('Score') }}</span>
                                <span id="seoScore" class="badge badge-secondary px-2 py-1" style="font-size:1rem;">0/100</span>
                            </div>
                        </div>

                        {{-- Score bar --}}
                        <div class="progress mb-3" style="height:8px;">
                            <div id="seoScoreBar" class="progress-bar bg-danger transition-all" role="progressbar" style="width:0%; transition:width 0.4s;"></div>
                        </div>

                        {{-- Checklist --}}
                        <div id="seoChecklist" class="row gutters-5 text-sm"></div>

                        {{-- Google SERP Preview --}}
                        <div class="mt-3">
                            <small class="text-muted font-weight-600 d-block mb-1">
                                <i class="las la-google mr-1"></i>{{ translate('Google SERP Preview') }}
                            </small>
                            <div class="border rounded bg-white p-3" style="font-family:Arial,sans-serif; max-width:600px;">
                                <div id="serpUrl" class="text-success small mb-1" style="font-size:13px;">
                                    {{ url('/') }}/categories/{{ $category->slug }}
                                </div>
                                <div id="serpTitle" class="text-primary font-weight-600 mb-1"
                                     style="font-size:18px; cursor:pointer; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $category->meta_title ?: $category->getTranslation('name', $lang) }}
                                </div>
                                <div id="serpDesc" class="text-secondary small"
                                     style="font-size:13px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                                    {{ $category->meta_description ?: 'Add a meta description to improve CTR from search results.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- ===== END SEO SECTION ===== --}}

                    @if (get_setting('category_wise_commission') == 1)
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Commission Rate')}}</label>
                            <div class="col-md-9 input-group">
                                <input type="number" lang="en" min="0" step="0.01" id="commision_rate" name="commision_rate" value="{{ $category->commision_rate }}" class="form-control">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label">{{translate('Filtering Attributes')}}</label>
                        <div class="col-md-9">
                            <select class="select2 form-control aiz-selectpicker" name="filtering_attributes[]" data-toggle="select2" data-placeholder="Choose ..."data-live-search="true" data-selected="{{ $category->attributes->pluck('id') }}" multiple>
                                @foreach (\App\Models\Attribute::all() as $attribute)
                                    <option value="{{ $attribute->id }}">{{ $attribute->getTranslation('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{-- ===== CONTENT SECTION ===== --}}
                    <div class="border-top pt-3 mt-2 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 font-weight-600 text-primary">
                                <i class="las la-align-left mr-1"></i>{{ translate('Category Content') }}
                            </h6>
                            <button type="button" id="aiContentBtn" class="btn btn-sm btn-outline-primary"
                                    onclick="aiAutoFillContent()" title="{{ translate('Auto-fill with AI') }}">
                                <i class="las la-magic mr-1"></i>{{ translate('AI Auto-Fill') }}
                                <span id="aiContentSpinner" class="spinner-border spinner-border-sm ml-1 d-none" role="status"></span>
                            </button>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{ translate('Top Description') }}</label>
                            <div class="col-md-9">
                                <textarea name="top_description" class="form-control aiz-text-editor" rows="5"
                                          placeholder="{{ translate('Content shown above products on the category page') }}">{{ $category->top_description }}</textarea>
                                <small class="text-muted">{{ translate('Displayed above the product listing on the frontend.') }}</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{ translate('Bottom Description') }}</label>
                            <div class="col-md-9">
                                <textarea name="bottom_description" class="form-control aiz-text-editor" rows="5"
                                          placeholder="{{ translate('Content shown below products on the category page') }}">{{ $category->bottom_description }}</textarea>
                                <small class="text-muted">{{ translate('Displayed below the product listing on the frontend.') }}</small>
                            </div>
                        </div>
                    </div>
                    {{-- ===== END CONTENT SECTION ===== --}}

                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
const SITE_URL      = '{{ rtrim(url("/"), "/") }}';
const AI_URL        = '{{ route("admin.seo_optimization.generate_meta") }}';
const AI_CONTENT_URL = '{{ route("admin.seo_optimization.generate_category_content") }}';
const CSRF          = '{{ csrf_token() }}';

// ─── Run on page load ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    updateSeoAnalysis();
    // Auto-generate slug if empty
    if (!document.getElementById('slug').value.trim()) {
        autoSlug();
    }
});

// ─── Slug helpers ─────────────────────────────────────────────────────────────
function slugify(text) {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

function autoSlug() {
    const name = document.getElementById('name').value.trim();
    if (name) {
        document.getElementById('slug').value = slugify(name);
        updateSeoAnalysis();
    }
}

// Auto-update slug when name changes (only if slug hasn't been manually edited)
let slugManuallyEdited = {{ $category->slug ? 'true' : 'false' }};
document.getElementById('slug').addEventListener('input', () => { slugManuallyEdited = true; });
document.getElementById('name').addEventListener('input', function () {
    if (!slugManuallyEdited) {
        document.getElementById('slug').value = slugify(this.value);
    }
    updateSeoAnalysis();
});

// ─── Live SEO Analysis ────────────────────────────────────────────────────────
function updateSeoAnalysis() {
    const title = document.getElementById('meta_title').value.trim();
    const desc  = document.getElementById('meta_description').value.trim();
    const slug  = document.getElementById('slug').value.trim();
    const name  = document.getElementById('name').value.trim();

    const tLen = title.length;
    const dLen = desc.length;

    // ── Character counters ──
    updateCounter('meta_title_count',  tLen, 60,  50,  60);
    updateCounter('meta_desc_count',   dLen, 160, 150, 160);
    updateProgressBar('meta_title_bar', tLen, 70,  50,  60);
    updateProgressBar('meta_desc_bar',  dLen, 200, 150, 160);

    // ── Slug preview ──
    document.getElementById('slug_preview').textContent = slug || slugify(name);
    document.getElementById('serpUrl').textContent      = SITE_URL + '/categories/' + (slug || slugify(name));

    // ── SERP preview ──
    document.getElementById('serpTitle').textContent = title || name || 'Category Page Title';
    document.getElementById('serpDesc').textContent  = desc  || 'Add a meta description to improve CTR from search results.';

    // Color title in SERP if too long
    const titleEl = document.getElementById('serpTitle');
    titleEl.style.color = tLen > 60 ? '#c0392b' : (tLen >= 50 && tLen <= 60 ? '#1a0dab' : '#555');

    // ── Build checklist ──
    const checks = [
        { label: 'Meta title present',       pass: tLen > 0 },
        { label: 'Title 50–60 chars',        pass: tLen >= 50 && tLen <= 60 },
        { label: 'Meta description present', pass: dLen > 0 },
        { label: 'Description 150–160 chars',pass: dLen >= 150 && dLen <= 160 },
        { label: 'Slug present',             pass: slug.length > 0 },
        { label: 'Slug is clean (no spaces)',pass: slug.length > 0 && !/\s/.test(slug) },
        { label: 'Keyword in title',         pass: name.length > 3 && tLen > 0 && title.toLowerCase().includes(name.toLowerCase().split(' ')[0]) },
        { label: 'Keyword in description',   pass: name.length > 3 && dLen > 0 && desc.toLowerCase().includes(name.toLowerCase().split(' ')[0]) },
    ];

    const passed = checks.filter(c => c.pass).length;
    const score  = Math.round((passed / checks.length) * 100);

    // ── Score display ──
    const scoreEl = document.getElementById('seoScore');
    const barEl   = document.getElementById('seoScoreBar');
    scoreEl.textContent = score + '/100';
    barEl.style.width   = score + '%';
    barEl.className = 'progress-bar transition-all ' + (score >= 75 ? 'bg-success' : score >= 50 ? 'bg-warning' : 'bg-danger');
    scoreEl.className   = 'badge px-2 py-1 ' + (score >= 75 ? 'badge-success' : score >= 50 ? 'badge-warning' : 'badge-danger');

    // ── Render checklist ──
    const checklistEl = document.getElementById('seoChecklist');
    checklistEl.innerHTML = checks.map(c => `
        <div class="col-6 col-md-3 mb-1">
            <span class="d-flex align-items-center" style="font-size:12px;">
                <i class="las ${c.pass ? 'la-check-circle text-success' : 'la-times-circle text-danger'} mr-1" style="font-size:15px;"></i>
                <span class="${c.pass ? 'text-dark' : 'text-muted'}">${c.label}</span>
            </span>
        </div>
    `).join('');
}

function updateCounter(elId, len, max, idealMin, idealMax) {
    const el = document.getElementById(elId);
    el.textContent = len + ' / ' + idealMax;
    el.className = 'font-weight-600 ' +
        (len >= idealMin && len <= idealMax ? 'text-success' :
         len > idealMax ? 'text-danger' : 'text-warning');
}

function updateProgressBar(elId, len, max, idealMin, idealMax) {
    const el  = document.getElementById(elId);
    const pct = Math.min(100, Math.round((len / max) * 100));
    el.style.width = pct + '%';
    el.className = 'progress-bar ' +
        (len >= idealMin && len <= idealMax ? 'bg-success' :
         len > idealMax ? 'bg-danger' : 'bg-warning');
}

// ─── AI Auto-Fill Content ─────────────────────────────────────────────────────
function aiAutoFillContent() {
    const name = document.getElementById('name').value.trim();
    if (!name) {
        alert('Please enter a category name first.');
        return;
    }

    const btn     = document.getElementById('aiContentBtn');
    const spinner = document.getElementById('aiContentSpinner');
    btn.disabled    = true;
    spinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('_token', CSRF);
    formData.append('name', name);

    fetch(AI_CONTENT_URL, { method: 'POST', body: formData })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            const topEl    = $('textarea[name="top_description"]');
            const bottomEl = $('textarea[name="bottom_description"]');

            if (typeof $.fn.summernote !== 'undefined') {
                topEl.summernote('code', data.top_description || '');
                bottomEl.summernote('code', data.bottom_description || '');
            } else {
                topEl.val(data.top_description || '');
                bottomEl.val(data.bottom_description || '');
            }

            const srcBadge = data.source === 'ai'
                ? '<span class="badge badge-success ml-1">AI</span>'
                : '<span class="badge badge-secondary ml-1" title="' + (data.note||'') + '">Template</span>';
            document.getElementById('aiContentBtn').innerHTML =
                '<i class="las la-magic mr-1"></i>{{ translate("AI Auto-Fill") }} ' + srcBadge;
        } else {
            alert(data.message || 'AI content generation failed.');
        }
    })
    .catch(err => alert('Request failed: ' + err))
    .finally(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
    });
}

// ─── AI Auto-Fill ─────────────────────────────────────────────────────────────
function aiAutoFillSeo() {
    const name = document.getElementById('name').value.trim();
    if (!name) {
        alert('Please enter a category name first.');
        return;
    }

    const btn     = document.getElementById('aiSeoBtn');
    const spinner = document.getElementById('aiSeoSpinner');
    btn.disabled    = true;
    spinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('_token', CSRF);
    formData.append('name', name);
    formData.append('description', 'Category page for ' + name + ' products. Shop our full range of ' + name + ' with fast delivery and competitive pricing.');

    fetch(AI_URL, {
        method: 'POST',
        body: formData,
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            // Fill meta title
            const titleField = document.getElementById('meta_title');
            titleField.value = data.title || '';

            // Fill meta description
            const descField = document.getElementById('meta_description');
            descField.value = data.description || '';

            // Auto-generate slug if not manually edited
            if (!slugManuallyEdited) {
                document.getElementById('slug').value = slugify(name);
            }

            updateSeoAnalysis();

            // Show source badge
            const srcBadge = data.source === 'ai'
                ? '<span class="badge badge-success ml-1">AI</span>'
                : '<span class="badge badge-secondary ml-1" title="' + (data.note||'') + '">Template</span>';
            document.getElementById('aiSeoBtn').innerHTML =
                '<i class="las la-magic mr-1"></i>{{ translate("AI Auto-Fill") }} ' + srcBadge;

            // Visual feedback
            titleField.classList.add('border-success');
            descField.classList.add('border-success');
            setTimeout(() => {
                titleField.classList.remove('border-success');
                descField.classList.remove('border-success');
            }, 2000);
        } else {
            alert(data.message || 'AI generation failed. Check your API key in SEO Settings.');
        }
    })
    .catch(err => {
        alert('Request failed: ' + err);
    })
    .finally(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
    });
}

// Intercept category form submit → AJAX → handle JSON redirect
document.addEventListener('DOMContentLoaded', function () {
    var categoryForm = document.querySelector('form[action*="categories"]');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var form = this;
            var formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.message) {
                    alert(data.message);
                }
            })
            .catch(function() { form.submit(); });
        });
    }
});
</script>
@endsection
