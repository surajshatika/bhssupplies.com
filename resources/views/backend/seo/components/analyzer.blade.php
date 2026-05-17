@php
    $product = $product ?? null;
    $seoTitle = $product ? ($product->meta_title ?: $product->name) : '';
    $seoDesc = $product ? ($product->meta_description ?: 'Add a meta description to improve click-through rate from search results.') : 'Add a meta description to improve click-through rate from search results.';
@endphp

<div class="card shadow-none border mt-4" id="seo-analyzer-widget">
    <div class="card-header bg-soft-primary py-2 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 h6 text-primary">
            <i class="las la-search mr-1"></i>{{ translate('Real-time SEO Analysis') }}
        </h6>
        <span id="seo-total-score" class="badge badge-danger px-2 py-1" style="font-size:.85rem;">0/100</span>
    </div>
    <div class="card-body pb-2">

        {{-- Score bar --}}
        <div class="progress mb-3" style="height:7px;">
            <div id="seo-score-bar" class="progress-bar bg-danger" role="progressbar"
                 style="width:0%; transition:width 0.4s;"></div>
        </div>

        {{-- Focus Keyword --}}
        <div class="form-group mb-3">
            <label class="form-label font-weight-600">{{ translate('Focus Keyword') }}</label>
            <input type="text" id="focus_keyword" class="form-control form-control-sm"
                   placeholder="{{ translate('Enter focus keyword…') }}"
                   oninput="analyzeSEO()">
            <small class="text-muted">{{ translate('The main keyword you want this product to rank for.') }}</small>
        </div>

        {{-- Checklist grid --}}
        <div id="seo-checklist" class="row gutters-5 mb-3"></div>

        {{-- Character counters --}}
        <div class="row gutters-10 mb-3">
            <div class="col-6">
                <div class="border rounded p-2 text-center">
                    <div class="small text-muted">Meta Title</div>
                    <div id="seo-title-len" class="font-weight-700 h5 mb-0 text-warning">0</div>
                    <div class="small text-muted">/ 60 chars</div>
                    <div class="progress mt-1" style="height:4px;">
                        <div id="seo-title-bar" class="progress-bar bg-warning" style="width:0%;"></div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2 text-center">
                    <div class="small text-muted">Meta Desc</div>
                    <div id="seo-desc-len" class="font-weight-700 h5 mb-0 text-warning">0</div>
                    <div class="small text-muted">/ 160 chars</div>
                    <div class="progress mt-1" style="height:4px;">
                        <div id="seo-desc-bar" class="progress-bar bg-warning" style="width:0%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Google SERP Preview --}}
        <div class="mb-3">
            <div class="small text-muted font-weight-600 mb-1">
                <i class="las la-google mr-1"></i>{{ translate('Google Preview') }}
            </div>
            <div class="border rounded bg-white p-2" style="font-family:Arial,sans-serif;">
                <div id="serp-url" class="text-success" style="font-size:12px;">{{ url('/') }}/products/...</div>
                <div id="serp-title" class="text-primary font-weight-600"
                     style="font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $seoTitle }}
                </div>
                <div id="serp-desc" class="text-secondary"
                     style="font-size:12px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                    {{ $seoDesc }}
                </div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="border-top pt-2 d-flex gap-2">
            <button type="button" id="seo-autofill-btn" class="btn btn-sm btn-primary flex-fill"
                    onclick="seoAutoFill()">
                <i class="las la-magic mr-1"></i>{{ translate('AI Auto-Fill SEO') }}
                <span id="seo-autofill-spinner" class="spinner-border spinner-border-sm ml-1 d-none"></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="seoSuggestLinks()">
                <i class="las la-link"></i>
            </button>
        </div>
        <div id="seo-autofill-note" class="mt-1 d-none">
            <small class="text-muted" id="seo-autofill-note-text"></small>
        </div>

    </div>
</div>

<style>
#seo-analyzer-widget .seo-item { font-size: 12px; margin-bottom: 4px; }
#seo-analyzer-widget .seo-item i { font-size: 14px; }
</style>
