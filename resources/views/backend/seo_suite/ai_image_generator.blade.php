@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-image mr-2 text-secondary"></i>{{ translate('AI Image Generator (SEO)') }}</h1>
            <p class="text-muted mb-0">{{ translate('Generate SEO-optimized images using DALL-E 3. Requires OpenAI API key.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Image Settings') }}</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ translate('Keyword / Subject') }} <span class="text-danger">*</span></label>
                    <input type="text" id="img-keyword" class="form-control" placeholder="safety gloves industrial" required>
                </div>
                <div class="form-group">
                    <label>{{ translate('Purpose') }}</label>
                    <select id="img-purpose" class="form-control">
                        <option value="product">{{ translate('Product Photo') }}</option>
                        <option value="blog">{{ translate('Blog / Article') }}</option>
                        <option value="social">{{ translate('Social Media') }}</option>
                        <option value="banner">{{ translate('Website Banner') }}</option>
                        <option value="infographic">{{ translate('Infographic') }}</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-7 form-group">
                        <label>{{ translate('Layout / Size') }}</label>
                        <select id="img-size" class="form-control">
                            <option value="1024x1024">1024×1024 (Square)</option>
                            <option value="1792x1024">1792×1024 (Landscape)</option>
                            <option value="1024x1792">1024×1792 (Portrait)</option>
                        </select>
                    </div>
                    <div class="col-5 form-group">
                        <label>{{ translate('Quality') }}</label>
                        <select id="img-quality" class="form-control">
                            <option value="standard">{{ translate('Standard') }}</option>
                            <option value="hd">{{ translate('HD (2× cost)') }}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ translate('Style') }}</label>
                    <select id="img-style" class="form-control">
                        @foreach(($styles ?? []) as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ translate('Custom Prompt (optional override)') }}</label>
                    <textarea id="img-custom-prompt" class="form-control" rows="3" placeholder="{{ translate('Describe exactly what you want...') }}"></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="img-save-local" value="1" checked>
                    <label class="form-check-label small" for="img-save-local">{{ translate('Save to media library (reusable + permanent URL)') }}</label>
                </div>
                <button id="img-generate-btn" class="btn btn-primary w-100">
                    <i class="las la-magic mr-1"></i>{{ translate('Generate Image') }}
                </button>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body p-3">
                <h6 class="small font-weight-bold mb-2">{{ translate('Tips for Best Results') }}</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-1"><i class="las la-check text-success mr-1"></i>{{ translate('Be specific about product type') }}</li>
                    <li class="mb-1"><i class="las la-check text-success mr-1"></i>{{ translate('Mention colors, materials') }}</li>
                    <li class="mb-1"><i class="las la-check text-success mr-1"></i>{{ translate('Specify industry/use case') }}</li>
                    <li class="mb-1"><i class="las la-check text-success mr-1"></i>{{ translate('Product photos work best with white background') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Generated Images') }}</h6>
                <span id="img-loading-indicator" class="text-muted small d-none">
                    <span class="spinner-border spinner-border-sm text-primary mr-1"></span>{{ translate('Generating...') }}
                </span>
            </div>
            <div class="card-body">
                <div id="img-results" style="min-height:350px;">
                    <div class="text-center py-5 text-muted">
                        <i class="las la-image la-3x mb-3 d-block text-light"></i>
                        {{ translate('Generated images will appear here') }}
                    </div>
                </div>
            </div>
        </div>

        <div id="img-meta-box" class="card mt-3 d-none">
            <div class="card-header"><h6 class="mb-0">{{ translate('SEO Metadata for Image') }}</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="small">{{ translate('Suggested Alt Text') }}</label>
                            <input type="text" id="img-alt-text" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="small">{{ translate('Suggested Filename') }}</label>
                            <input type="text" id="img-filename" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="small">{{ translate('Revised Prompt (by DALL-E)') }}</label>
                    <textarea id="img-revised-prompt" class="form-control form-control-sm" rows="2" readonly></textarea>
                </div>
            </div>
        </div>

        {{-- Revision history --}}
        @if(($history ?? collect())->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0"><i class="las la-history mr-1"></i>{{ translate('Recent Generations') }}</h6></div>
            <div class="card-body">
                <div class="row gutters-5">
                    @foreach($history as $h)
                        <div class="col-3 col-md-2 mb-2">
                            <a href="{{ $h->local_url ?: $h->source_url }}" target="_blank" rel="noopener" title="{{ $h->keyword }} · {{ $h->quality }} · {{ $h->size }}">
                                <img src="{{ $h->local_url ?: $h->source_url }}" class="img-fluid rounded border" style="aspect-ratio:1; object-fit:cover;" alt="{{ $h->alt_text }}">
                            </a>
                        </div>
                    @endforeach
                </div>
                <p class="small text-muted mb-0">{{ translate('Provider image URLs expire after ~1 hour. Images saved to the media library keep a permanent URL.') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    $('#img-generate-btn').on('click', function() {
        var keyword = $('#img-keyword').val().trim();
        if (!keyword) { alert('Please enter a keyword.'); return; }

        $('#img-loading-indicator').removeClass('d-none');
        $('#img-generate-btn').prop('disabled', true);
        $('#img-results').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted small">Generating image with DALL-E 3...</div></div>');

        $.ajax({
            url: '{{ route('admin.seo-suite.ai_images.generate') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                keyword: keyword,
                purpose: $('#img-purpose').val(),
                size: $('#img-size').val(),
                quality: $('#img-quality').val(),
                style: $('#img-style').val(),
                custom_prompt: $('#img-custom-prompt').val(),
                save_local: $('#img-save-local').is(':checked') ? 1 : 0
            },
            success: function(res) {
                $('#img-loading-indicator').addClass('d-none');
                $('#img-generate-btn').prop('disabled', false);

                if (res.error) {
                    $('#img-results').html('<div class="alert alert-danger"><i class="las la-exclamation-circle mr-1"></i>' + res.error + '</div><div class="text-muted small mt-2">Prompt used: ' + (res.prompt || '') + '</div>');
                    return;
                }

                var html = '';
                if (res.images && res.images.length > 0) {
                    res.images.forEach(function(img) {
                        var displayUrl = img.local_url || img.url;
                        html += '<div class="mb-3 border rounded p-2">';
                        html += '<img src="' + displayUrl + '" class="img-fluid rounded shadow-sm mb-2" style="max-height:480px; width:auto;" alt="' + img.alt_text + '">';
                        html += '<div class="d-flex flex-wrap mt-1" style="gap:.4rem;">';
                        html += '<a href="' + displayUrl + '" target="_blank" class="btn btn-sm btn-soft-primary"><i class="las la-external-link-alt mr-1"></i>Open</a>';
                        html += '<a href="' + displayUrl + '" download="' + img.filename + '" class="btn btn-sm btn-soft-success"><i class="las la-download mr-1"></i>Download</a>';
                        if (img.local_url) {
                            html += '<span class="btn btn-sm btn-soft-info disabled"><i class="las la-check mr-1"></i>In media library</span>';
                        }
                        html += '</div>';

                        // Set-as-OG mini form
                        html += '<div class="bg-light rounded p-2 mt-2">';
                        html += '<div class="small font-weight-bold mb-1"><i class="las la-share-alt mr-1"></i>{{ translate('Set as OG / social image for') }}</div>';
                        html += '<div class="d-flex flex-wrap align-items-center" style="gap:.35rem;">';
                        html += '<select class="form-control form-control-sm og-type" style="width:auto;"><option value="product">Product</option><option value="category">Category</option><option value="page">Page</option><option value="blog">Blog</option></select>';
                        html += '<input type="number" class="form-control form-control-sm og-id" placeholder="ID" style="width:90px;">';
                        html += '<button class="btn btn-sm btn-primary og-apply" data-upload="' + (img.upload_id || '') + '" data-url="' + img.url + '">{{ translate('Apply') }}</button>';
                        html += '<span class="og-status small ml-1"></span>';
                        html += '</div></div>';
                        html += '</div>';

                        // Fill meta box
                        $('#img-alt-text').val(img.alt_text);
                        $('#img-filename').val(img.filename);
                        $('#img-revised-prompt').val(img.revised_prompt || res.prompt);
                        $('#img-meta-box').removeClass('d-none');
                    });
                } else {
                    html = '<div class="alert alert-warning">No images returned.</div>';
                }
                $('#img-results').html(html);
            },
            error: function(xhr) {
                $('#img-loading-indicator').addClass('d-none');
                $('#img-generate-btn').prop('disabled', false);
                $('#img-results').html('<div class="alert alert-danger">Error: ' + (xhr.responseJSON?.message || 'Request failed') + '</div>');
            }
        });
    });

    // Apply a generated image as an entity's OG / social image.
    $('#img-results').on('click', '.og-apply', function() {
        var $btn = $(this);
        var $wrap = $btn.closest('.d-flex');
        var type = $wrap.find('.og-type').val();
        var id   = $wrap.find('.og-id').val();
        var $status = $wrap.find('.og-status');
        if (!id) { $status.html('<span class="text-danger">{{ translate('Enter an ID') }}</span>'); return; }

        $btn.prop('disabled', true);
        $status.html('<span class="text-muted">…</span>');

        $.ajax({
            url: '{{ route('admin.seo-suite.ai_images.apply_og') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                type: type,
                id: id,
                upload_id: $btn.data('upload') || '',
                image_url: $btn.data('url') || ''
            },
            success: function(res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    $status.html('<span class="text-success"><i class="las la-check"></i> {{ translate('Applied') }}</span>');
                } else {
                    $status.html('<span class="text-danger">' + (res.error || 'Failed') + '</span>');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                $status.html('<span class="text-danger">' + (xhr.responseJSON?.error || xhr.responseJSON?.message || 'Failed') + '</span>');
            }
        });
    });
});
</script>
@endsection
