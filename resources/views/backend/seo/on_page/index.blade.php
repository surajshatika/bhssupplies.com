@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('On-Page AI SEO Tools') }}</h1>
            @if($project)
                <p class="text-muted mb-0">{{ $project->name }} - {{ $project->base_url }}</p>
            @endif
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

@if($setupRequired)
    <div class="alert alert-warning">
        {{ translate('SEO suite database tables are missing. Run the four SEO migrations to activate task history, score tracking, and redirects.') }}
    </div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 h6">{{ translate('Local On-Page AI Strategy') }}</h5>
        <span class="badge badge-soft-primary">{{ translate('Mississauga / Brampton / Toronto first') }}</span>
    </div>
    <div class="card-body">
        <div class="row gutters-16">
            <div class="col-md-4 mb-3 mb-md-0">
                <strong class="small d-block mb-2">{{ translate('Primary Targets') }}</strong>
                <span class="badge badge-success mr-1">Mississauga</span>
                <span class="badge badge-success mr-1">Brampton</span>
                <span class="badge badge-success">Toronto</span>
            </div>
            <div class="col-md-5 mb-3 mb-md-0">
                <strong class="small d-block mb-2">{{ translate('Secondary Targets') }}</strong>
                @foreach(['Etobicoke','Vaughan','Oakville','Scarborough','Markham','North York','Burlington'] as $city)
                    <span class="badge badge-soft-info mr-1 mb-1">{{ $city }}</span>
                @endforeach
            </div>
            <div class="col-md-3">
                <strong class="small d-block mb-2">{{ translate('Conversion Intent') }}</strong>
                <span class="badge badge-soft-warning mr-1">{{ translate('Trade Account') }}</span>
                <span class="badge badge-soft-warning">{{ translate('Leave a Review') }}</span>
            </div>
        </div>
        <div class="alert alert-info py-2 mt-3 mb-0 small">
            <i class="las la-brain mr-1"></i>
            {{ translate('Use Local On-Page Blueprint for page-level title, description, headings, schema, internal links, competitor-gap angles, and quick fixes. Autopilot continues to skip SEO-done URLs.') }}
        </div>
    </div>
</div>

<div class="row gutters-16 mb-4">
    @foreach($features as $key => $label)
    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
        <div class="card h-100 shadow-sm border-0 tool-card" onclick="openToolModal('{{ $key }}', '{{ addslashes($label) }}')" style="cursor: pointer; transition: transform 0.2s;">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <i class="las la-magic las-3x text-primary mb-2"></i>
                <h6 class="font-weight-600 mb-0">{{ $label }}</h6>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Recent On-Page Runs') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Feature') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Target URL/Topic') }}</th>
                        <th>{{ translate('Created') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ data_get($features, $run->feature, $run->feature) }}</td>
                            <td>
                                <span class="badge badge-inline badge-{{ $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ $run->status }}
                                </span>
                            </td>
                            <td>{{ $run->url ?: (isset($run->input_payload['topic']) ? $run->input_payload['topic'] : '-') }}</td>
                            <td>{{ $run->created_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ translate('No runs yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Dynamic Tool Modal -->
<div class="modal fade" id="toolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toolModalLabel">{{ translate('Run Tool') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.seo_on_page.run') }}" method="POST">
                @csrf
                <input type="hidden" name="feature" id="toolFeatureInput">
                <div class="modal-body">
                    <div class="form-group mb-4">
                        <label>{{ translate('AI Provider') }}</label>
                        <select name="provider" class="form-control">
                            <option value="">{{ translate('System Default') }}</option>
                            <option value="openai">OpenAI</option>
                            <option value="claude">Claude</option>
                            <option value="gemini">Gemini</option>
                        </select>
                    </div>

                    <div id="dynamicFields">
                        <!-- URL Field -->
                        <div class="form-group tool-field" data-tools="local_onpage_blueprint,meta_tags,keyword_density,heading_structure,internal_links,readability,seo_audit,open_graph,schema_markup">
                            <label>{{ translate('Target URL') }}</label>
                            <input type="url" class="form-control" name="url" placeholder="https://example.com/page">
                        </div>

                        <!-- Content writer topic -->
                        <div class="form-group tool-field" data-tools="local_onpage_blueprint,content_writer" style="display:none">
                            <label>{{ translate('Topic for Article') }}</label>
                            <input type="text" class="form-control" name="topic" placeholder="e.g. Benefits of Industrial Gloves">
                        </div>
                        
                        <!-- Primary Keyword -->
                        <div class="form-group tool-field" data-tools="local_onpage_blueprint,keyword_density,content_writer,meta_tags,heading_structure,seo_audit" style="display:none">
                            <label>{{ translate('Primary Keyword') }}</label>
                            <input type="text" class="form-control" name="keyword" placeholder="HVAC supplies Mississauga">
                            <small class="text-muted">{{ translate('AI will prioritize Mississauga, Brampton, Toronto, nearby GTA cities, Trade Account, Leave a Review, and configured competitor gap angles.') }}</small>
                        </div>

                        <!-- Images field -->
                        <div class="form-group tool-field" data-tools="alt_text,open_graph" style="display:none">
                            <label>{{ translate('Images (One per line)') }}</label>
                            <textarea class="form-control" rows="4" name="images" placeholder="https://.../img1.jpg"></textarea>
                        </div>
                        
                        <!-- Content snippet -->
                        <div class="form-group tool-field" data-tools="readability,heading_structure,keyword_density" style="display:none">
                            <label>{{ translate('Page Content Snippet (Optional if URL provided)') }}</label>
                            <textarea class="form-control" rows="5" name="content"></textarea>
                        </div>
                        
                        <!-- Type of Schema -->
                        <div class="form-group tool-field" data-tools="schema_markup" style="display:none">
                            <label>{{ translate('Schema Type Payload (e.g. FAQ, Product data)') }}</label>
                            <textarea class="form-control" rows="3" name="extra_payload" placeholder='{"type":"FAQ","items":[]}'></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Execute Task') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('.tool-card').on('mouseenter', function() {
        $(this).addClass('shadow-lg').css('transform', 'translateY(-3px)');
    }).on('mouseleave', function() {
        $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
    });

    function openToolModal(featureKey, sectionLabel) {
        $('#toolFeatureInput').val(featureKey);
        $('#toolModalLabel').text(sectionLabel);
        
        // Hide all dynamically initially
        $('.tool-field').hide();
        
        // Show fields that apply to this featureKey
        $('.tool-field').each(function() {
            var tools = $(this).data('tools');
            if (tools && tools.indexOf(featureKey) !== -1) {
                $(this).show();
            }
        });

        // Fallback: If no field matched, at least show target URL and Topic
        if ($('.tool-field:visible').length === 0) {
            $('[data-tools*="meta_tags"]').show();
        }

        $('#toolModal').modal('show');
    }
</script>
@endsection
