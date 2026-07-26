@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="mm-hero mm-hero--blog">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
            </div>
            <div>
                <h2>{{ translate('AI Blog Automation') }}</h2>
                <p>{{ translate('Auto-generate SEO blogs from product categories with competitor keywords') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><span class="mm-dot {{ $settings['ai_blog_enabled'] ? 'ok' : 'warn' }}"></span>
                        @if($settings['ai_blog_enabled'])
                            {{ translate('Auto-generates at') }} {{ $settings['ai_blog_schedule_time'] ?: '08:00' }}
                        @else
                            {{ translate('Disabled') }}
                        @endif
                    </span>
                    <span class="mm-chip"><i class="las la-feather-alt"></i> {{ $settings['ai_blog_posts_per_day'] ?: 1 }}/{{ translate('day') }}</span>
                    @if($settings['ai_blog_post_to_social'])
                        <span class="mm-chip"><i class="las la-share"></i> {{ translate('Auto-shares') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
            <button class="mm-btn mm-btn-light" data-toggle="modal" data-target="#generateModal">
                <i class="las la-magic"></i> {{ translate('Generate Now') }}
            </button>
            <a href="{{ route('admin.ai-blog.settings') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-cog"></i> {{ translate('Settings') }}
            </a>
            <a href="{{ route('blog.index') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-list"></i> {{ translate('All Blogs') }}
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($totalBlogs) }}</h3>
            <div class="mm-stat-label">{{ translate('Total Blogs') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($publishedBlogs) }}</h3>
            <div class="mm-stat-label">{{ translate('Published') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($draftBlogs) }}</h3>
            <div class="mm-stat-label">{{ translate('Drafts') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($blogCategories->count()) }}</h3>
            <div class="mm-stat-label">{{ translate('Categories') }}</div>
        </div>
    </div>
</div>

<div class="row gutters-16">
    {{-- Recent AI Blogs --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Recently Generated Blogs') }}</h6>
                <a href="{{ route('blog.index') }}" class="btn btn-sm btn-soft-primary">{{ translate('View All') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Title') }}</th>
                            <th>{{ translate('Category') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Created') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBlogs as $blog)
                        <tr>
                            <td>
                                <span class="font-weight-600">{{ Str::limit($blog->title, 55) }}</span>
                                @if($blog->meta_keywords)
                                    <div style="font-size:11px;" class="text-muted mt-1">
                                        <i class="las la-tag"></i> {{ Str::limit($blog->meta_keywords, 60) }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $blog->category->category_name ?? '—' }}</td>
                            <td>
                                @if($blog->status)
                                    <span class="badge badge-success">{{ translate('Published') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ translate('Draft') }}</span>
                                    <button class="btn btn-xs btn-soft-success ml-1 publish-btn"
                                        data-id="{{ $blog->id }}"
                                        data-url="{{ route('admin.ai-blog.publish', $blog->id) }}">
                                        {{ translate('Publish') }}
                                    </button>
                                @endif
                            </td>
                            <td>{{ $blog->created_at->format('M d, H:i') }}</td>
                            <td>
                                <a href="{{ route('blog.edit', $blog->id) }}" class="btn btn-sm btn-soft-primary">
                                    <i class="las la-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                {{ translate('No blogs yet. Click "Generate Now" to create your first AI blog!') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Category Overview + AI Info --}}
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="las la-tags mr-1"></i>{{ translate('Blog Categories') }}</h6></div>
            <div class="card-body p-0">
                @foreach($blogCategories->take(8) as $cat)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <span class="small">{{ $cat->category_name }}</span>
                    <span class="badge badge-light">{{ $cat->posts_count }}</span>
                </div>
                @endforeach
                @if($blogCategories->count() > 8)
                <div class="px-3 py-2 text-muted small">+ {{ $blogCategories->count() - 8 }} more</div>
                @endif
                @if($blogCategories->isEmpty())
                <div class="p-3 text-muted small">{{ translate('Categories are auto-created from product categories') }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="las la-lightbulb mr-1"></i>{{ translate('How It Works') }}</h6></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0" style="font-size:13px;">
                    <li class="mb-2"><span class="badge badge-primary mr-2">1</span>{{ translate('Picks product category & grabs 4-5 product images') }}</li>
                    <li class="mb-2"><span class="badge badge-primary mr-2">2</span>{{ translate('AI creates 1300x650 banner and meta image') }}</li>
                    <li class="mb-2"><span class="badge badge-primary mr-2">3</span>{{ translate('Researches competitor keywords for top ranking') }}</li>
                    <li class="mb-2"><span class="badge badge-primary mr-2">4</span>{{ translate('Generates title, slug, short description, full HTML, and complete SEO meta') }}</li>
                    <li class="mb-0"><span class="badge badge-success mr-2">5</span>{{ translate('Auto-posts to social media platforms') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Generate Now Modal --}}
<div class="modal fade" id="generateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="las la-magic mr-1"></i>{{ translate('Generate AI Blog Posts') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Number of Posts') }}</label>
                            <select id="genCount" class="form-control aiz-selectpicker">
                                @for($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('AI Provider') }}</label>
                            <select id="genProvider" class="form-control aiz-selectpicker">
                                @foreach($aiProviders as $k => $lbl)
                                    <option value="{{ $k }}" {{ ($settings['ai_blog_provider'] ?? '') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Tone') }}</label>
                            <select id="genTone" class="form-control aiz-selectpicker">
                                @foreach($tones as $k => $lbl)
                                    <option value="{{ $k }}" {{ ($settings['ai_blog_tone'] ?? '') === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>{{ translate('Blog Title') }}</label>
                            <input type="text" id="genBlogTitle" class="form-control"
                                placeholder="{{ translate('Exact blog title. Leave blank for AI title.') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Slug') }}</label>
                            <input type="text" id="genSlug" class="form-control"
                                placeholder="{{ translate('auto-from-title') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Product Category (for images)') }}</label>
                            <select id="genProductCat" class="form-control aiz-selectpicker" data-live-search="true">
                                <option value="">{{ translate('Auto (random rotation)') }}</option>
                                @foreach($productCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Choose Blog Category') }}</label>
                            <select id="genBlogCategoryId" class="form-control aiz-selectpicker" data-live-search="true">
                                <option value="">{{ translate('Auto / create from new name') }}</option>
                                @foreach($blogCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Create New Blog Category') }} <small class="text-muted">({{ translate('if not exist') }})</small></label>
                            <input type="text" id="genBlogCat" class="form-control"
                                placeholder="{{ translate('e.g. Safety Equipment Tips') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Topic Override') }} <small class="text-muted">({{ translate('optional') }})</small></label>
                            <input type="text" id="genTopic" class="form-control"
                                placeholder="{{ translate('Leave blank for AI to decide') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Extra Keywords') }}</label>
                            <input type="text" id="genKeywords" class="form-control"
                                placeholder="{{ translate('comma separated, optional') }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>{{ translate('Competitor URLs') }} <small class="text-muted">({{ translate('AI extracts keywords from these') }})</small></label>
                            @php
                                $rawUrls = $settings['ai_blog_competitor_urls'] ?? '';
                                // Fix merged URLs: split on https:// and rejoin with comma
                                $normalized = preg_replace('/(https?:\/\/)/', '|||$1', $rawUrls);
                                $urlParts = array_filter(array_map('trim', explode('|||', $normalized)));
                                $displayUrls = count($urlParts) > 1 ? implode(', ', $urlParts) : $rawUrls;
                            @endphp
                            <input type="text" id="genCompetitors" class="form-control"
                                value="{{ $displayUrls }}"
                                placeholder="https://competitor1.com, https://competitor2.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Banner') }} <small class="text-muted">(1300x650)</small></label>
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                </div>
                                <div class="form-control file-amount">{{ translate('AI auto-generates if empty') }}</div>
                                <input type="hidden" id="genBanner" class="selected-files">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Meta Image') }} <small class="text-muted">{{ translate('uses banner if empty') }}</small></label>
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                </div>
                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                <input type="hidden" id="genMetaImage" class="selected-files">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Meta Title') }} <small class="text-muted">{{ translate('AI fills if empty') }}</small></label>
                            <input type="text" id="genMetaTitle" class="form-control" maxlength="190">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Meta Keywords') }} <small class="text-muted">{{ translate('comma-separated') }}</small></label>
                            <input type="text" id="genMetaKeywords" class="form-control" placeholder="hvac supplies Mississauga, plumbing supplies Toronto">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>{{ translate('Meta Description') }} <small class="text-muted">{{ translate('AI fills if empty') }}</small></label>
                            <textarea id="genMetaDescription" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="d-flex align-items-center">
                                <input type="checkbox" id="genPublish" class="mr-2" {{ ($settings['ai_blog_auto_publish'] ?? false) ? 'checked' : '' }}>
                                {{ translate('Auto-publish') }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="d-flex align-items-center">
                                <input type="checkbox" id="genSocial" class="mr-2" {{ ($settings['ai_blog_post_to_social'] ?? false) ? 'checked' : '' }}>
                                {{ translate('Post to social media') }}
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Result area --}}
                <div id="genResult" class="mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" id="generateSubmitBtn" class="btn btn-primary btn-lg">
                    <i class="las la-magic mr-1"></i>{{ translate('Generate') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(document).ready(function() {
    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    $('#genBlogTitle').on('input', function() {
        if (!$('#genSlug').data('dirty')) {
            $('#genSlug').val(slugify($(this).val()));
        }
    });

    $('#genSlug').on('input', function() {
        $(this).data('dirty', true).val(slugify($(this).val()));
    });

    $('#generateSubmitBtn').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin mr-1"></i>Generating (may take 30-60s)...');

        // Use jQuery val() which works with selectpicker
        var payload = {
            count:               $('#genCount').val(),
            provider:            $('#genProvider').val(),
            tone:                $('#genTone').val(),
            blog_title:          $('#genBlogTitle').val() || null,
            slug:                $('#genSlug').val() || null,
            blog_category_id:    $('#genBlogCategoryId').val() || null,
            product_category_id: $('#genProductCat').val() || null,
            category_name:       $('#genBlogCat').val() || null,
            banner_upload_id:    $('#genBanner').val() || null,
            meta_image_upload_id: $('#genMetaImage').val() || null,
            meta_title:          $('#genMetaTitle').val() || null,
            meta_description:    $('#genMetaDescription').val() || null,
            meta_keywords:       $('#genMetaKeywords').val() || null,
            topic:               $('#genTopic').val() || null,
            keywords:            $('#genKeywords').val() || null,
            competitor_urls:     $('#genCompetitors').val() || null,
            publish:             $('#genPublish').is(':checked') ? 1 : 0,
            post_to_social:      $('#genSocial').is(':checked') ? 1 : 0,
        };

        $.ajax({
            url: '{{ route("admin.ai-blog.generate") }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            data: JSON.stringify(payload),
            success: function(d) {
                btn.prop('disabled', false).html('<i class="las la-magic mr-1"></i>{{ translate("Generate") }}');
                var $result = $('#genResult').removeClass('d-none');

                if (d.success) {
                    var html = '<div class="alert alert-success">' + d.message + '</div><ul class="list-unstyled">';
                    $.each(d.blogs || [], function(i, b) {
                        html += '<li class="mb-2">'
                            + '<span class="badge badge-' + (b.status === 'Published' ? 'success' : 'secondary') + ' mr-1">' + b.status + '</span>'
                            + '<a href="' + b.edit_url + '" target="_blank">' + b.title + '</a>'
                            + ' <small class="text-muted">(' + b.category + ')</small></li>';
                    });
                    html += '</ul>';
                    $result.html(html);
                    setTimeout(function(){ location.reload(); }, 3000);
                } else {
                    $result.html('<div class="alert alert-danger">' + (d.message || 'Generation failed') + '</div>');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="las la-magic mr-1"></i>{{ translate("Generate") }}');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed. Check your API key in Settings.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                $('#genResult').removeClass('d-none').html('<div class="alert alert-danger">' + msg + '</div>');
            }
        });
    });
});

$(document).on('click', '.publish-btn', function(){
    var url = $(this).data('url');
    $.post(url, {'_token': '{{ csrf_token() }}'}, function(d){ if(d.success) location.reload(); });
});
</script>
@endsection
