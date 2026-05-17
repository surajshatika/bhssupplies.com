@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">{{ translate('Compose & Schedule Post') }}</h1>
            <p class="text-muted mb-0">{{ translate('Write or generate AI content and post to multiple platforms') }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.social.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Dashboard') }}
            </a>
        </div>
    </div>
</div>

<div class="row gutters-16">
    {{-- Compose Panel --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="las la-edit mr-1"></i>{{ translate('Compose Post') }}</h6></div>
            <div class="card-body">

                {{-- Platform Selector --}}
                <div class="form-group">
                    <label>{{ translate('Platforms') }} <span class="text-danger">*</span></label>
                    <div class="row">
                        @foreach($platforms as $slug => $info)
                        <div class="col-6 col-md-4 mb-2">
                            <label class="platform-checkbox d-flex align-items-center p-2 border rounded" style="cursor:pointer;" data-slug="{{ $slug }}">
                                <input type="checkbox" class="platform-check mr-2" value="{{ $slug }}" id="plt_{{ $slug }}">
                                <i class="{{ $info['icon'] }} mr-1" style="font-size:16px;"></i>
                                <span class="small">{{ $info['label'] }}</span>
                                @if(in_array($info['region'], ['canada','both']))
                                    <span class="badge badge-info ml-auto" style="font-size:9px;">CA</span>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- AI Generation Row --}}
                <div class="card bg-soft-primary mb-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="las la-magic mr-2 text-primary" style="font-size:20px;"></i>
                            <strong>{{ translate('AI Content Generator') }}</strong>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" id="aiTopic" class="form-control form-control-sm"
                                    placeholder="{{ translate('Topic or product to write about...') }}">
                            </div>
                            <div class="col-md-3">
                                <select id="aiTone" class="form-control form-control-sm aiz-selectpicker">
                                    @foreach($tones as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="aiProvider" class="form-control form-control-sm aiz-selectpicker">
                                    @foreach($aiProviders as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="generateBtn" class="btn btn-primary btn-sm btn-block">
                                    <i class="las la-magic"></i> {{ translate('Generate') }}
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-5">
                                <input type="text" id="aiKeywords" class="form-control form-control-sm"
                                    placeholder="{{ translate('Keywords (comma separated, optional)') }}">
                            </div>
                            <div class="col-md-5">
                                <input type="text" id="aiAudience" class="form-control form-control-sm"
                                    placeholder="{{ translate('Target audience (optional)') }}">
                            </div>
                            <div class="col-md-2">
                                <button id="hashtagBtn" class="btn btn-soft-primary btn-sm btn-block">
                                    # Tags
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Content Area --}}
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label>{{ translate('Post Content') }} <span class="text-danger">*</span></label>
                        <small id="charCount" class="text-muted">0 chars</small>
                    </div>
                    <textarea id="postContent" class="form-control" rows="6"
                        placeholder="{{ translate('Write your post here or generate with AI above...') }}"></textarea>
                </div>

                {{-- Media Options --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="small">{{ translate('Image URL') }} <span class="text-muted">(Instagram, Pinterest required)</span></label>
                            <input type="url" id="imageUrl" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="small">{{ translate('Video URL') }} <span class="text-muted">(TikTok required)</span></label>
                            <input type="url" id="videoUrl" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="small">{{ translate('Link URL') }}</label>
                            <input type="url" id="linkUrl" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="small">{{ translate('Schedule At') }} <span class="text-muted">(leave blank to post now)</span></label>
                            <input type="datetime-local" id="scheduleAt" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex justify-content-between">
                    <button id="variantsBtn" class="btn btn-soft-secondary">
                        <i class="las la-clone mr-1"></i>{{ translate('Generate Variants') }}
                    </button>
                    <button id="postNowBtn" class="btn btn-primary btn-lg px-5">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Post / Schedule') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Queue Panel --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="las la-clock mr-1"></i>{{ translate('Scheduled Queue') }}</h6>
                <span class="badge badge-primary">{{ $queuedPosts->total() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($queuedPosts as $post)
                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-start">
                    <div>
                        <div class="d-flex align-items-center mb-1">
                            <i class="{{ config('social_media.platforms.'.$post->platform.'.icon', 'las la-share-alt') }} mr-1"></i>
                            <strong class="small text-capitalize">{{ $post->platform }}</strong>
                            <span class="badge badge-sm badge-info ml-2">{{ $post->status }}</span>
                        </div>
                        <div class="text-muted" style="font-size:11px;">
                            {{ mb_substr($post->content, 0, 80) }}...
                        </div>
                        <div style="font-size:11px;" class="text-primary">
                            <i class="las la-clock"></i>
                            {{ $post->scheduled_at ? $post->scheduled_at->format('M d, H:i') : 'ASAP' }}
                        </div>
                    </div>
                    <button class="btn btn-sm btn-soft-danger delete-queued" data-id="{{ $post->id }}"
                        data-url="{{ route('admin.social.queue.delete', $post) }}">
                        <i class="las la-times"></i>
                    </button>
                </div>
                @empty
                <div class="p-4 text-center text-muted">
                    <i class="las la-inbox" style="font-size:32px;"></i>
                    <br><small>{{ translate('Queue is empty') }}</small>
                </div>
                @endforelse
            </div>
            @if($queuedPosts->hasPages())
            <div class="p-2">{{ $queuedPosts->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Variants Modal --}}
<div class="modal fade" id="variantsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Content Variants') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="variantsBody">
                <div class="text-center py-4"><i class="las la-spinner la-spin" style="font-size:30px;"></i></div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
const CSRF = '{{ csrf_token() }}';

// Char count
document.getElementById('postContent').addEventListener('input', function(){
    document.getElementById('charCount').textContent = this.value.length + ' chars';
});

// Platform checkbox style
document.querySelectorAll('.platform-check').forEach(cb => {
    cb.addEventListener('change', function(){
        this.closest('.platform-checkbox').classList.toggle('border-primary', this.checked);
        this.closest('.platform-checkbox').classList.toggle('bg-soft-primary', this.checked);
    });
});

// Generate AI content
document.getElementById('generateBtn').addEventListener('click', function(){
    const platform = document.querySelector('.platform-check:checked')?.value;
    const topic = document.getElementById('aiTopic').value.trim();
    if(!topic){ alert('{{ translate("Enter a topic first") }}'); return; }
    const tone = document.getElementById('aiTone').value;
    const provider = document.getElementById('aiProvider').value;
    const keywords = document.getElementById('aiKeywords').value;
    const audience = document.getElementById('aiAudience').value;

    this.disabled = true;
    this.innerHTML = '<i class="las la-spinner la-spin"></i>';

    fetch('{{ route("admin.social.ai.generate") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({platform: platform || 'facebook', topic, tone, provider, keywords, target_audience: audience})
    }).then(r => r.json()).then(d => {
        this.disabled = false;
        this.innerHTML = '<i class="las la-magic"></i> {{ translate("Generate") }}';
        if(d.success){
            document.getElementById('postContent').value = d.content;
            document.getElementById('charCount').textContent = d.content.length + ' chars';
        } else {
            alert(d.message || '{{ translate("Generation failed") }}');
        }
    }).catch(() => {
        this.disabled = false;
        this.innerHTML = '<i class="las la-magic"></i> {{ translate("Generate") }}';
    });
});

// Generate hashtags
document.getElementById('hashtagBtn').addEventListener('click', function(){
    const topic = document.getElementById('aiTopic').value.trim();
    if(!topic){ alert('{{ translate("Enter a topic first") }}'); return; }
    this.disabled = true;
    fetch('{{ route("admin.social.ai.hashtags") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({topic, provider: document.getElementById('aiProvider').value})
    }).then(r => r.json()).then(d => {
        this.disabled = false;
        if(d.success && d.hashtags.length){
            const ta = document.getElementById('postContent');
            ta.value += '\n' + d.hashtags.join(' ');
        }
    }).catch(() => { this.disabled = false; });
});

// Generate variants
document.getElementById('variantsBtn').addEventListener('click', function(){
    const content = document.getElementById('postContent').value.trim();
    if(!content){ alert('{{ translate("Write content first") }}'); return; }
    document.getElementById('variantsBody').innerHTML = '<div class="text-center py-4"><i class="las la-spinner la-spin" style="font-size:30px;"></i></div>';
    new bootstrap.Modal(document.getElementById('variantsModal')).show();
    // Note: variants endpoint can be added; for now show the content itself
    document.getElementById('variantsBody').innerHTML = `<div class="alert alert-info">{{ translate("Variants feature: generate and click 'Use This' to replace your content.") }}</div>
        <div class="bg-light p-3 rounded"><pre style="white-space:pre-wrap;font-family:inherit;">${content}</pre></div>`;
});

// Post now
document.getElementById('postNowBtn').addEventListener('click', function(){
    const platforms = Array.from(document.querySelectorAll('.platform-check:checked')).map(c => c.value);
    const content = document.getElementById('postContent').value.trim();
    if(!platforms.length){ alert('{{ translate("Select at least one platform") }}'); return; }
    if(!content){ alert('{{ translate("Write some content first") }}'); return; }

    this.disabled = true;
    this.innerHTML = '<i class="las la-spinner la-spin"></i> {{ translate("Posting...") }}';

    const payload = {
        platforms,
        content,
        image_url: document.getElementById('imageUrl').value,
        video_url: document.getElementById('videoUrl').value,
        link: document.getElementById('linkUrl').value,
        schedule: document.getElementById('scheduleAt').value,
        ai_provider: document.getElementById('aiProvider').value,
    };

    fetch('{{ route("admin.social.post") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(d => {
        this.disabled = false;
        this.innerHTML = '<i class="las la-paper-plane mr-1"></i>{{ translate("Post / Schedule") }}';
        if(d.success){
            showToast(d.message, 'success');
            document.getElementById('postContent').value = '';
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(d.message || '{{ translate("Failed") }}', 'danger');
        }
    }).catch(() => {
        this.disabled = false;
        this.innerHTML = '<i class="las la-paper-plane mr-1"></i>{{ translate("Post / Schedule") }}';
    });
});

// Delete queued post
document.querySelectorAll('.delete-queued').forEach(btn => {
    btn.addEventListener('click', function(){
        if(!confirm('{{ translate("Remove from queue?") }}')) return;
        fetch(this.dataset.url, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        }).then(r => r.json()).then(d => { if(d.success) this.closest('.border-bottom').remove(); });
    });
});

function showToast(msg, type){
    const el = document.createElement('div');
    el.className = `alert alert-${type} alert-dismissible position-fixed`;
    el.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
    el.innerHTML = msg + '<button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
</script>
@endpush
@endsection
