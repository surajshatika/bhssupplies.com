@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Off-Page AI SEO Tools') }}</h1>
            @if($project)
                <p class="text-muted mb-0">{{ $project->name }} - {{ $project->base_url }}</p>
            @endif
        </div>
    </div>
</div>

@if($setupRequired)
    <div class="alert alert-warning">
        {{ translate('SEO suite database tables are missing. Run the four SEO migrations to activate task history, score tracking, and redirects.') }}
    </div>
@endif

<div class="row gutters-16 mb-4">
    @foreach($features as $key => $label)
    <div class="col-xl-4 col-lg-4 col-md-6 mb-3">
        <div class="card h-100 shadow-sm border-0 tool-card" onclick="openToolModal('{{ $key }}', '{{ addslashes($label) }}')" style="cursor: pointer; transition: transform 0.2s;">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <i class="las la-bullhorn las-3x text-success mb-2"></i>
                <h6 class="font-weight-600 mb-0">{{ $label }}</h6>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Recent Off-Page Content Generation') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Feature') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Topic/Keyword') }}</th>
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
                            <td>{{ $run->input_payload['topic'] ?? ($run->input_payload['keyword'] ?? '-') }}</td>
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
            <form action="{{ route('admin.seo_off_page.run') }}" method="POST">
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
                        <!-- Topic Field -->
                        <div class="form-group tool-field" data-tools="guest_post_topics,guest_post_article,social_signal_posts,press_release">
                            <label>{{ translate('Topic or Subject') }}</label>
                            <input type="text" class="form-control" name="topic" placeholder="e.g. New Product Launch: Safety Helmet X-200">
                        </div>

                        <!-- Target URL -->
                        <div class="form-group tool-field" data-tools="backlink_outreach,social_signal_posts,guest_post_article" style="display:none">
                            <label>{{ translate('Target URL to Promote') }}</label>
                            <input type="url" class="form-control" name="url" placeholder="https://example.com/product/xyz">
                        </div>

                        <!-- Anchor Text -->
                        <div class="form-group tool-field" data-tools="anchor_text_profile" style="display:none">
                            <label>{{ translate('Current Primary Keywords (Comma separated)') }}</label>
                            <textarea class="form-control" rows="2" name="anchor_text" placeholder="buy helmets, industrial safety gear"></textarea>
                        </div>
                        
                        <!-- Contact/Target Site -->
                        <div class="form-group tool-field" data-tools="backlink_outreach" style="display:none">
                            <label>{{ translate('Target Prospect Website / Name') }}</label>
                            <input type="text" class="form-control" name="target_site" placeholder="e.g. IndustrialSafetyBlog.com">
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
        
        $('.tool-field').hide();
        $('.tool-field').each(function() {
            var tools = $(this).data('tools');
            if (tools && tools.indexOf(featureKey) !== -1) {
                $(this).show();
            }
        });

        if ($('.tool-field:visible').length === 0) {
            $('[data-tools*="guest_post_topics"]').show();
        }

        $('#toolModal').modal('show');
    }
</script>
@endsection
