@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">{{ translate('AI Content Campaigns') }}</h1>
            <p class="text-muted mb-0">{{ translate('Create campaigns, generate AI content per platform, and schedule auto-posts') }}</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-toggle="modal" data-target="#createCampaignModal">
                <i class="las la-plus mr-1"></i>{{ translate('New Campaign') }}
            </button>
            <a href="{{ route('admin.social.index') }}" class="btn btn-soft-secondary ml-2">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Campaign') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('AI Provider') }}</th>
                        <th>{{ translate('Platforms') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Posts') }}</th>
                        <th>{{ translate('Last Posted') }}</th>
                        <th>{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                    <tr>
                        <td>
                            <strong>{{ $campaign->name }}</strong>
                            <div class="text-muted small">{{ Str::limit($campaign->topic, 60) }}</div>
                        </td>
                        <td><span class="badge badge-secondary">{{ $campaign->type }}</span></td>
                        <td>{{ $aiProviders[$campaign->ai_provider] ?? $campaign->ai_provider }}</td>
                        <td>
                            @foreach((array)$campaign->platforms as $p)
                                <i class="{{ config('social_media.platforms.'.$p.'.icon', 'las la-share-alt') }}" title="{{ ucfirst($p) }}" style="font-size:16px;" class="mr-1"></i>
                            @endforeach
                        </td>
                        <td><span class="badge {{ $campaign->status_badge }}">{{ ucfirst($campaign->status) }}</span></td>
                        <td>{{ $campaign->post_count }}</td>
                        <td>{{ $campaign->last_posted_at ? $campaign->last_posted_at->diffForHumans() : '—' }}</td>
                        <td>
                            <button class="btn btn-sm btn-soft-success generate-btn" data-id="{{ $campaign->id }}"
                                data-url="{{ route('admin.social.campaigns.generate', $campaign) }}">
                                <i class="las la-magic"></i> {{ translate('Generate') }}
                            </button>
                            @if($campaign->generated_content)
                            <button class="btn btn-sm btn-soft-primary view-content-btn" data-content="{{ json_encode($campaign->generated_content) }}">
                                <i class="las la-eye"></i>
                            </button>
                            @endif
                            @if($campaign->status === 'draft')
                            <button class="btn btn-sm btn-soft-info status-btn" data-id="{{ $campaign->id }}"
                                data-status="active" data-url="{{ route('admin.social.campaigns.status', $campaign) }}">
                                {{ translate('Activate') }}
                            </button>
                            @elseif($campaign->status === 'active')
                            <button class="btn btn-sm btn-soft-warning status-btn" data-id="{{ $campaign->id }}"
                                data-status="paused" data-url="{{ route('admin.social.campaigns.status', $campaign) }}">
                                {{ translate('Pause') }}
                            </button>
                            @endif
                            <form action="{{ route('admin.social.campaigns.delete', $campaign) }}" method="POST" class="d-inline-block"
                                onsubmit="return confirm('{{ translate('Delete this campaign?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-soft-danger"><i class="las la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="las la-bullhorn" style="font-size:40px;"></i>
                            <br>{{ translate('No campaigns yet. Create your first one!') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $campaigns->links() }}</div>
    </div>
</div>

{{-- Create Campaign Modal --}}
<div class="modal fade" id="createCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.social.campaigns.create') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('New AI Campaign') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Campaign Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="{{ translate('e.g. Summer Sale 2026') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('Type') }}</label>
                                <select name="type" class="form-control aiz-selectpicker">
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ translate('AI Provider') }}</label>
                                <select name="ai_provider" class="form-control aiz-selectpicker">
                                    @foreach($aiProviders as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>{{ translate('Topic / Brief') }} <span class="text-danger">*</span></label>
                                <textarea name="topic" class="form-control" rows="3" required
                                    placeholder="{{ translate('Describe what to post about...') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Keywords') }}</label>
                                <input type="text" name="keywords" class="form-control" placeholder="{{ translate('comma separated') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Target Audience') }}</label>
                                <input type="text" name="target_audience" class="form-control" placeholder="{{ translate('e.g. Canadian homeowners 25-45') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Content Tone') }}</label>
                                <select name="tone" class="form-control aiz-selectpicker">
                                    @foreach($tones as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Schedule Type') }}</label>
                                <select name="schedule_type" class="form-control aiz-selectpicker" id="scheduleTypeSelect">
                                    <option value="once">{{ translate('One-time') }}</option>
                                    <option value="recurring">{{ translate('Recurring (Cron)') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="scheduledAtField">
                            <div class="form-group">
                                <label>{{ translate('Schedule At') }}</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6 d-none" id="cronField">
                            <div class="form-group">
                                <label>{{ translate('Cron Expression') }}</label>
                                <input type="text" name="cron_expression" class="form-control" placeholder="0 9 * * *">
                                <small class="text-muted">{{ translate('e.g. 0 9 * * * = daily at 9am') }}</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>{{ translate('Platforms') }} <span class="text-danger">*</span></label>
                                <div class="row">
                                    @foreach($platforms as $slug => $info)
                                    <div class="col-6 col-md-4 col-lg-3 mb-2">
                                        <label class="d-flex align-items-center" style="cursor:pointer;">
                                            <input type="checkbox" name="platforms[]" value="{{ $slug }}" class="mr-2">
                                            <i class="{{ $info['icon'] }} mr-1"></i>
                                            <span class="small">{{ $info['label'] }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label class="d-flex align-items-center">
                                    <input type="checkbox" name="auto_post" value="1" class="mr-2">
                                    {{ translate('Auto-post when campaign is active') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Create Campaign') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Content Modal --}}
<div class="modal fade" id="viewContentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Generated Content') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="contentModalBody"></div>
        </div>
    </div>
</div>

@push('script')
<script>
document.getElementById('scheduleTypeSelect')?.addEventListener('change', function(){
    const isRecurring = this.value === 'recurring';
    document.getElementById('cronField').classList.toggle('d-none', !isRecurring);
    document.getElementById('scheduledAtField').classList.toggle('d-none', isRecurring);
});

document.querySelectorAll('.generate-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        const url = this.dataset.url;
        this.disabled = true;
        this.innerHTML = '<i class="las la-spinner la-spin"></i> Generating...';
        fetch(url, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
        })
        .then(r => r.json())
        .then(d => {
            if(d.success){
                aiAlert('{{ translate("Content generated! Review it by clicking the eye icon.") }}', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                aiAlert(d.message || '{{ translate("Generation failed") }}', 'error');
                this.disabled = false;
                this.innerHTML = '<i class="las la-magic"></i> {{ translate("Generate") }}';
            }
        }).catch(() => {
            this.disabled = false;
            this.innerHTML = '<i class="las la-magic"></i> {{ translate("Generate") }}';
        });
    });
});

document.querySelectorAll('.view-content-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        const content = JSON.parse(this.dataset.content || '{}');
        let html = '';
        for (const [platform, text] of Object.entries(content)) {
            html += `<div class="mb-3"><h6 class="text-capitalize"><i class="las la-share-alt mr-1"></i>${platform}</h6>
                <div class="bg-light rounded p-3"><pre style="white-space:pre-wrap;font-family:inherit;">${text || '—'}</pre></div></div>`;
        }
        document.getElementById('contentModalBody').innerHTML = html || '<p class="text-muted">No content generated yet.</p>';
        new bootstrap.Modal(document.getElementById('viewContentModal')).show();
    });
});

document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        fetch(this.dataset.url, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
            body: JSON.stringify({status: this.dataset.status})
        }).then(r => r.json()).then(d => { if(d.success) location.reload(); });
    });
});

function aiAlert(msg, type) {
    const el = document.createElement('div');
    el.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible position-fixed`;
    el.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
    el.innerHTML = msg + '<button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
</script>
@endpush
@endsection
