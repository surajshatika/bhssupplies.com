@extends('backend.layouts.app')

@section('content')
<div class="row gutters-16">

    {{-- HERO STATUS STRIP --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #4285F4 0%, #34A853 100%);">
            <div class="card-body text-white py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="mr-3 d-flex align-items-center justify-content-center rounded-circle bg-white" style="width:56px;height:56px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#4285F4">
                                    <path d="M12 .288l2.833 8.718h9.167l-7.416 5.389 2.833 8.718-7.417-5.388-7.416 5.388 2.833-8.718L0 9.006h9.167z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white mb-1 fw-700">{{ translate('Google Reviews Integration') }}</h3>
                                <p class="mb-0 opacity-75">{{ translate('Direct Google Places API — no 3rd-party widgets. Auto-syncs every Monday.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                        @if($isEnabled)
                            <span class="badge badge-light text-success px-3 py-2"><i class="las la-check-circle"></i> {{ translate('Active') }}</span>
                        @else
                            <span class="badge badge-light text-warning px-3 py-2"><i class="las la-exclamation-circle"></i> {{ translate('Not Configured') }}</span>
                        @endif
                        @if($lastSyncedAt)
                            <div class="text-white-50 small mt-2">
                                <i class="las la-clock"></i> {{ translate('Last sync') }}: {{ \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- LEFT: CONFIG FORM --}}
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0 h6"><i class="las la-cog text-primary"></i> {{ translate('Google Reviews Configuration') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('google_reviews.update') }}" method="POST">
                    @csrf

                    {{-- Enable Toggle --}}
                    <div class="form-group d-flex align-items-center bg-light p-3 rounded">
                        <label class="aiz-switch aiz-switch-success mb-0 mr-3">
                            <input value="1" name="google_reviews_enabled" type="checkbox" @if(get_setting('google_reviews_enabled') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                        <div>
                            <strong>{{ translate('Enable Google Reviews') }}</strong>
                            <div class="small text-muted">{{ translate('Activates Places API fetching and weekly auto-sync.') }}</div>
                        </div>
                    </div>

                    {{-- Place ID --}}
                    <div class="form-group">
                        <input type="hidden" name="types[]" value="GOOGLE_PLACE_ID">
                        <label class="col-form-label">
                            <strong>{{ translate('Google Place ID') }}</strong>
                            <small class="text-muted ml-1">{{ translate('(required)') }}</small>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="las la-map-marker-alt text-danger"></i></span>
                            </div>
                            <input type="text" class="form-control" name="GOOGLE_PLACE_ID"
                                value="{{ env('GOOGLE_PLACE_ID') }}"
                                placeholder="ChIJN1t_tDeuEmsRUsoyG83frY4">
                        </div>
                        <small class="form-text text-muted">
                            {{ translate('Find your Place ID at') }}
                            <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">developers.google.com/maps/.../place-id</a>
                        </small>
                    </div>

                    {{-- API Key --}}
                    <div class="form-group">
                        <input type="hidden" name="types[]" value="GOOGLE_PLACES_API_KEY">
                        <label class="col-form-label">
                            <strong>{{ translate('Google Places API Key') }}</strong>
                            <small class="text-muted ml-1">{{ translate('(required)') }}</small>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="las la-key text-warning"></i></span>
                            </div>
                            <input type="password" class="form-control" id="api-key-field" name="GOOGLE_PLACES_API_KEY"
                                value="{{ env('GOOGLE_PLACES_API_KEY') }}"
                                placeholder="AIzaSy...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('api-key-field', this)"><i class="las la-eye"></i></button>
                            </div>
                        </div>
                        <small class="form-text text-muted">{{ translate('Enable Places API in Google Cloud Console and create an API key.') }}</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label"><strong>{{ translate('Reviews Language') }}</strong></label>
                                <select name="google_reviews_language" class="form-control aiz-selectpicker">
                                    @php $curLang = get_setting('google_reviews_language', 'en'); @endphp
                                    <option value="en" {{ $curLang=='en'?'selected':'' }}>English</option>
                                    <option value="hi" {{ $curLang=='hi'?'selected':'' }}>Hindi</option>
                                    <option value="es" {{ $curLang=='es'?'selected':'' }}>Spanish</option>
                                    <option value="fr" {{ $curLang=='fr'?'selected':'' }}>French</option>
                                    <option value="de" {{ $curLang=='de'?'selected':'' }}>German</option>
                                    <option value="ar" {{ $curLang=='ar'?'selected':'' }}>Arabic</option>
                                    <option value="pt" {{ $curLang=='pt'?'selected':'' }}>Portuguese</option>
                                    <option value="zh" {{ $curLang=='zh'?'selected':'' }}>Chinese</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label"><strong>{{ translate('Sort Order') }}</strong></label>
                                <select name="google_reviews_sort" class="form-control aiz-selectpicker">
                                    @php $curSort = get_setting('google_reviews_sort', 'most_relevant'); @endphp
                                    <option value="most_relevant" {{ $curSort=='most_relevant'?'selected':'' }}>{{ translate('Most Relevant') }}</option>
                                    <option value="newest" {{ $curSort=='newest'?'selected':'' }}>{{ translate('Newest First') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label"><strong>{{ translate('Frontend Min Rating Filter') }}</strong></label>
                                <select name="google_reviews_min_rating" class="form-control aiz-selectpicker">
                                    @php $curMin = (int) get_setting('google_reviews_min_rating', 1); @endphp
                                    @for($i=1;$i<=5;$i++)
                                        <option value="{{ $i }}" {{ $curMin==$i?'selected':'' }}>
                                            {{ $i }}★ {{ translate('and above') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group d-flex align-items-end h-100">
                                <div class="bg-light p-3 rounded w-100">
                                    <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                                        <input value="1" name="google_reviews_show_frontend" type="checkbox" @if(get_setting('google_reviews_show_frontend') == 1) checked @endif>
                                        <span class="slider round"></span>
                                    </label>
                                    <strong>{{ translate('Display on Frontend') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0 text-right border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4"><i class="las la-save"></i> {{ translate('Save Configuration') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SYNC ACTION CARD --}}
        <div class="card border-success">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1"><i class="las la-sync-alt text-success"></i> {{ translate('Manual Sync') }}</h6>
                        <p class="text-muted mb-0 small">
                            {{ translate('Force-refresh reviews from Google. Auto-sync runs') }}
                            <strong>{{ translate('every Monday at 06:30') }}</strong>.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-right mt-2 mt-md-0">
                        <form action="{{ route('google-reviews.sync-now') }}" method="POST" id="sync-now-form">
                            @csrf
                            <button type="submit" class="btn btn-success" id="sync-now-btn" {{ $isEnabled ? '' : 'disabled' }}>
                                <i class="las la-sync-alt"></i> {{ translate('Sync Now') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: INSTRUCTIONS + LIVE PREVIEW --}}
    <div class="col-lg-5">

        {{-- LIVE PREVIEW --}}
        @if($summary['rating'] !== null)
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 h6"><i class="las la-eye text-primary"></i> {{ translate('Live Snapshot') }}</h5>
            </div>
            <div class="card-body text-center">
                <div class="display-4 fw-700 text-warning">{{ number_format($summary['rating'], 1) }}</div>
                <div class="mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($summary['rating']))
                            <i class="las la-star text-warning fs-22"></i>
                        @elseif($i - $summary['rating'] < 1)
                            <i class="las la-star-half-alt text-warning fs-22"></i>
                        @else
                            <i class="lar la-star text-muted fs-22"></i>
                        @endif
                    @endfor
                </div>
                <p class="text-muted small mb-2">{{ $summary['total'] }} {{ translate('total reviews on Google') }}</p>
                @if(!empty($summary['business_name']))
                    <h6 class="mb-1">{{ $summary['business_name'] }}</h6>
                @endif
                @if(!empty($summary['address']))
                    <small class="text-muted d-block">{{ $summary['address'] }}</small>
                @endif
                @if(!empty($summary['place_url']))
                    <a href="{{ $summary['place_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary mt-3">
                        <i class="lab la-google"></i> {{ translate('View on Google') }}
                    </a>
                @endif
                <a href="{{ route('google-reviews-dashboard') }}" class="btn btn-sm btn-primary mt-3 ml-1">
                    <i class="las la-chart-bar"></i> {{ translate('Full Dashboard') }}
                </a>
            </div>
        </div>
        @endif

        {{-- INSTRUCTIONS --}}
        <div class="card bg-light border-0">
            <div class="card-header bg-light">
                <h5 class="mb-0 h6"><i class="las la-question-circle text-info"></i> {{ translate('Setup Instructions') }}</h5>
            </div>
            <div class="card-body">
                <ol class="pl-3 mb-0">
                    <li class="mb-2">{{ translate('Go to') }} <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a> {{ translate('and select or create a project.') }}</li>
                    <li class="mb-2">{{ translate('Navigate to APIs & Services → Library → enable') }} <strong>Places API</strong>.</li>
                    <li class="mb-2">{{ translate('Go to APIs & Services → Credentials → Create Credentials → API Key.') }}</li>
                    <li class="mb-2">{{ translate('Restrict the key to') }} <strong>Places API</strong> {{ translate('+ your server IP.') }}</li>
                    <li class="mb-2">{{ translate('Find your business Place ID using the') }} <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Place ID Finder</a>.</li>
                    <li class="mb-2">{{ translate('Paste both values, save, and click Sync Now.') }}</li>
                </ol>
                <div class="alert alert-warning small mt-3 mb-0">
                    <i class="las la-exclamation-triangle"></i>
                    <strong>{{ translate('Quota note:') }}</strong>
                    {{ translate('Google Places API returns max 5 reviews per call. Weekly auto-sync keeps quota usage minimal.') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function togglePwd(id, btn) {
    var el = document.getElementById(id);
    if (el.type === 'password') { el.type = 'text'; btn.innerHTML = '<i class="las la-eye-slash"></i>'; }
    else { el.type = 'password'; btn.innerHTML = '<i class="las la-eye"></i>'; }
}

document.getElementById('sync-now-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('sync-now-btn');
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="las la-spinner la-spin"></i> {{ translate("Syncing...") }}';

    fetch(this.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            AIZ.plugins.notify('success', data.message + ' (' + data.count + ')');
            setTimeout(() => location.reload(), 800);
        } else {
            AIZ.plugins.notify('danger', data.message || 'Sync failed');
            btn.disabled = false; btn.innerHTML = orig;
        }
    })
    .catch(err => {
        AIZ.plugins.notify('danger', 'Network error: ' + err.message);
        btn.disabled = false; btn.innerHTML = orig;
    });
});
</script>
@endsection
