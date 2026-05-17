@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col">
			<h1 class="h3">{{ translate('Edit Page Information') }}</h1>
		</div>
	</div>
</div>
<div class="card">
	<ul class="nav nav-tabs nav-fill border-light">
		@foreach (\App\Models\Language::all() as $key => $language)
			<li class="nav-item">
				<a class="nav-link text-reset @if ($language->code == $lang) active @else bg-soft-dark border-light border-left-0 @endif py-3" href="{{ route('custom-pages.edit', ['id'=>$page->slug, 'lang'=> $language->code] ) }}">
					<img src="{{ static_asset('assets/img/flags/'.$language->code.'.png') }}" height="11" class="mr-1">
					<span>{{$language->name}}</span>
				</a>
			</li>
		@endforeach
	</ul>

	<form class="p-4" action="{{ route('custom-pages.update', $page->id) }}" method="POST" enctype="multipart/form-data">
		@csrf
		<input type="hidden" name="_method" value="PATCH">
		<input type="hidden" name="lang" value="{{ $lang }}">

		<div class="card-header px-0">
			<h6 class="fw-600 mb-0">{{ translate('Page Content') }}</h6>
		</div>
		<div class="card-body px-0">
			<div class="form-group row">
				<label class="col-sm-2 col-from-label" for="name">{{translate('Title')}} <span class="text-danger">*</span> <i class="las la-language text-danger" title="{{translate('Translatable')}}"></i></label>
				<div class="col-sm-10">
					<input type="text" class="form-control" placeholder="{{translate('Title')}}" name="title" value="{{ $page->getTranslation('title',$lang) }}" required>
				</div>
			</div>


				<div class="form-group row">
					<label class="col-sm-2 col-from-label" for="name">{{translate('Link')}} <span class="text-danger">*</span></label>
					<div class="col-sm-10">
						<div class="input-group d-block d-md-flex">
							@if($page->type == 'custom_page')
								<div class="input-group-prepend"><span class="input-group-text flex-grow-1">{{ route('home') }}/</span></div>
								<input type="text" class="form-control w-100 w-md-auto" placeholder="{{ translate('Slug') }}" name="slug" value="{{ $page->slug }}">
							@else
								<input class="form-control w-100 w-md-auto" value="{{ route('home') }}/{{ $page->slug }}" disabled>
							@endif
						</div>
						<small class="form-text text-muted">{{ translate('Use character, number, hypen only') }}</small>
					</div>
				</div>

			<div class="form-group row">
				<label class="col-sm-2 col-from-label" for="name">{{translate('Add Content')}} <span class="text-danger">*</span></label>
				<div class="col-sm-10">
					<textarea
						class="aiz-text-editor form-control"
						placeholder="{{translate('Content..')}}"
						data-buttons='[["font", ["bold", "underline", "italic", "clear"]],["para", ["ul", "ol", "paragraph"]],["style", ["style"]],["color", ["color"]],["table", ["table"]],["insert", ["link", "picture", "video"]],["view", ["fullscreen", "codeview", "undo", "redo"]]]'
						data-min-height="300"
						name="content"
						required
					>{!! $page->getTranslation('content',$lang) !!}</textarea>
				</div>
			</div>
		</div>

		<div class="card-header px-0">
			<div class="d-flex align-items-center justify-content-between">
				<h6 class="fw-600 mb-0">{{ translate('Seo Fields') }}</h6>
				<button type="button" id="aiSeoBtn" class="btn btn-sm btn-outline-primary"
				        onclick="aiAutoFillSeo()" title="{{ translate('Auto-fill with AI') }}">
					<i class="las la-magic mr-1"></i>{{ translate('AI Auto-Fill') }}
					<span id="aiSeoSpinner" class="spinner-border spinner-border-sm ml-1 d-none" role="status"></span>
				</button>
			</div>
		</div>
		<div class="card-body px-0">

			{{-- Meta Title --}}
			<div class="form-group row">
				<label class="col-sm-2 col-from-label">{{translate('Meta Title')}}</label>
				<div class="col-sm-10">
					<input type="text" class="form-control" placeholder="{{translate('Title')}}"
					       name="meta_title" id="meta_title"
					       value="{{ $page->meta_title }}"
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
				<label class="col-sm-2 col-from-label">{{translate('Meta Description')}}</label>
				<div class="col-sm-10">
					<textarea class="resize-off form-control" placeholder="{{translate('Description')}}"
					          name="meta_description" id="meta_description" rows="4"
					          oninput="updateSeoAnalysis()" maxlength="200">{!! $page->meta_description !!}</textarea>
					<div class="d-flex justify-content-between mt-1">
						<small class="text-muted">{{ translate('Ideal: 150–160 characters') }}</small>
						<small id="meta_desc_count" class="font-weight-600">0 / 160</small>
					</div>
					<div class="progress mt-1" style="height:4px;">
						<div id="meta_desc_bar" class="progress-bar" style="width:0%;"></div>
					</div>
				</div>
			</div>

			{{-- Live SEO Analysis Panel --}}
			<div class="form-group row">
				<div class="col-sm-10 offset-sm-2">
					<div class="border rounded p-3 bg-light" id="seoAnalysisPanel">
						<div class="d-flex align-items-center justify-content-between mb-2">
							<h6 class="mb-0 font-weight-600"><i class="las la-chart-bar mr-1 text-primary"></i>{{ translate('Live SEO Analysis') }}</h6>
							<div class="d-flex align-items-center">
								<span class="mr-2 text-muted small">{{ translate('Score') }}</span>
								<span id="seoScore" class="badge badge-secondary px-2 py-1" style="font-size:1rem;">0/100</span>
							</div>
						</div>
						<div class="progress mb-3" style="height:8px;">
							<div id="seoScoreBar" class="progress-bar bg-danger" role="progressbar" style="width:0%; transition:width 0.4s;"></div>
						</div>
						<div id="seoChecklist" class="row gutters-5 text-sm"></div>
						<div class="mt-3">
							<small class="text-muted font-weight-600 d-block mb-1">
								<i class="las la-google mr-1"></i>{{ translate('Google SERP Preview') }}
							</small>
							<div class="border rounded bg-white p-3" style="font-family:Arial,sans-serif; max-width:600px;">
								<div id="serpUrl" class="text-success small mb-1" style="font-size:13px;">
									{{ url('/') }}/{{ $page->slug }}
								</div>
								<div id="serpTitle" class="text-primary font-weight-600 mb-1"
								     style="font-size:18px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
									{{ $page->meta_title ?: $page->getTranslation('title', $lang) }}
								</div>
								<div id="serpDesc" class="text-secondary small"
								     style="font-size:13px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
									{{ $page->meta_description ?: 'Add a meta description to improve CTR from search results.' }}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="form-group row">
				<label class="col-sm-2 col-from-label" for="name">{{translate('Keywords')}}</label>
				<div class="col-sm-10">
					<textarea class="resize-off form-control" placeholder="{{translate('Keyword, Keyword')}}" name="keywords">{!! $page->keywords !!}</textarea>
					<small class="text-muted">{{ translate('Separate with coma') }}</small>
				</div>
			</div>

			<div class="form-group row">
				<label class="col-sm-2 col-from-label" for="name">{{translate('Meta Image')}}</label>
				<div class="col-sm-10">
					<div class="input-group" data-toggle="aizuploader" data-type="image">
						<div class="input-group-prepend">
							<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
						</div>
						<div class="form-control file-amount">{{ translate('Choose File') }}</div>
						<input type="hidden" name="meta_image" class="selected-files" value="{{ $page->meta_image }}">
					</div>
					<div class="file-preview"></div>
				</div>
			</div>

			<div class="text-right">
				<button type="submit" class="btn btn-primary">{{ translate('Update Page') }}</button>
			</div>
		</div>
	</form>
</div>
@endsection

@section('script')
<script>
const SITE_URL = '{{ rtrim(url("/"), "/") }}';
const AI_URL   = '{{ route("admin.seo_optimization.generate_meta") }}';
const CSRF     = '{{ csrf_token() }}';
const PAGE_SLUG = '{{ $page->slug }}';

document.addEventListener('DOMContentLoaded', function () {
    updateSeoAnalysis();
});

function getPageTitle() {
    // Try to read from the title input field
    var titleInput = document.querySelector('input[name="title"]');
    return titleInput ? titleInput.value.trim() : '';
}

function updateSeoAnalysis() {
    var title = document.getElementById('meta_title').value.trim();
    var desc  = document.getElementById('meta_description').value.trim();
    var name  = getPageTitle();
    var slug  = PAGE_SLUG;

    var tLen = title.length;
    var dLen = desc.length;

    updateCounter('meta_title_count', tLen, 60, 50, 60);
    updateCounter('meta_desc_count',  dLen, 160, 150, 160);
    updateProgressBar('meta_title_bar', tLen, 70,  50, 60);
    updateProgressBar('meta_desc_bar',  dLen, 200, 150, 160);

    document.getElementById('serpUrl').textContent   = SITE_URL + '/' + slug;
    document.getElementById('serpTitle').textContent = title || name || 'Page Title';
    document.getElementById('serpDesc').textContent  = desc  || 'Add a meta description to improve CTR from search results.';

    var titleEl = document.getElementById('serpTitle');
    titleEl.style.color = tLen > 60 ? '#c0392b' : (tLen >= 50 && tLen <= 60 ? '#1a0dab' : '#555');

    var checks = [
        { label: 'Meta title present',        pass: tLen > 0 },
        { label: 'Title 50–60 chars',         pass: tLen >= 50 && tLen <= 60 },
        { label: 'Meta description present',  pass: dLen > 0 },
        { label: 'Description 150–160 chars', pass: dLen >= 150 && dLen <= 160 },
        { label: 'Slug present',              pass: slug.length > 0 },
        { label: 'Slug is clean (no spaces)', pass: slug.length > 0 && !/\s/.test(slug) },
        { label: 'Keyword in title',          pass: name.length > 3 && tLen > 0 && title.toLowerCase().includes(name.toLowerCase().split(' ')[0]) },
        { label: 'Keyword in description',    pass: name.length > 3 && dLen > 0 && desc.toLowerCase().includes(name.toLowerCase().split(' ')[0]) },
    ];

    var passed = checks.filter(function(c){ return c.pass; }).length;
    var score  = Math.round((passed / checks.length) * 100);

    var scoreEl = document.getElementById('seoScore');
    var barEl   = document.getElementById('seoScoreBar');
    scoreEl.textContent = score + '/100';
    barEl.style.width   = score + '%';
    barEl.className = 'progress-bar ' + (score >= 75 ? 'bg-success' : score >= 50 ? 'bg-warning' : 'bg-danger');
    scoreEl.className   = 'badge px-2 py-1 ' + (score >= 75 ? 'badge-success' : score >= 50 ? 'badge-warning' : 'badge-danger');

    document.getElementById('seoChecklist').innerHTML = checks.map(function(c) {
        return '<div class="col-6 col-md-3 mb-1">' +
            '<span class="d-flex align-items-center" style="font-size:12px;">' +
            '<i class="las ' + (c.pass ? 'la-check-circle text-success' : 'la-times-circle text-danger') + ' mr-1" style="font-size:15px;"></i>' +
            '<span class="' + (c.pass ? 'text-dark' : 'text-muted') + '">' + c.label + '</span>' +
            '</span></div>';
    }).join('');
}

function updateCounter(elId, len, max, idealMin, idealMax) {
    var el = document.getElementById(elId);
    el.textContent = len + ' / ' + idealMax;
    el.className = 'font-weight-600 ' +
        (len >= idealMin && len <= idealMax ? 'text-success' :
         len > idealMax ? 'text-danger' : 'text-warning');
}

function updateProgressBar(elId, len, max, idealMin, idealMax) {
    var el  = document.getElementById(elId);
    var pct = Math.min(100, Math.round((len / max) * 100));
    el.style.width = pct + '%';
    el.className = 'progress-bar ' +
        (len >= idealMin && len <= idealMax ? 'bg-success' :
         len > idealMax ? 'bg-danger' : 'bg-warning');
}

function aiAutoFillSeo() {
    var name = getPageTitle();
    if (!name) {
        alert('Please enter a page title first.');
        return;
    }

    var btn     = document.getElementById('aiSeoBtn');
    var spinner = document.getElementById('aiSeoSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');

    var formData = new FormData();
    formData.append('_token', CSRF);
    formData.append('name', name);
    formData.append('description', 'Page about ' + name + '. ' + name + ' information and details.');

    fetch(AI_URL, { method: 'POST', body: formData })
    .then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            var titleField = document.getElementById('meta_title');
            var descField  = document.getElementById('meta_description');
            titleField.value = data.title || '';
            descField.value  = data.description || '';
            updateSeoAnalysis();

            var srcBadge = data.source === 'ai'
                ? '<span class="badge badge-success ml-1">AI</span>'
                : '<span class="badge badge-secondary ml-1">Template</span>';
            btn.innerHTML = '<i class="las la-magic mr-1"></i>{{ translate("AI Auto-Fill") }} ' + srcBadge;

            titleField.classList.add('border-success');
            descField.classList.add('border-success');
            setTimeout(function() {
                titleField.classList.remove('border-success');
                descField.classList.remove('border-success');
            }, 2000);
        } else {
            alert(data.message || 'AI generation failed.');
        }
    })
    .catch(function(err) { alert('Request failed: ' + err); })
    .finally(function() {
        btn.disabled = false;
        spinner.classList.add('d-none');
    });
}
</script>
@endsection
