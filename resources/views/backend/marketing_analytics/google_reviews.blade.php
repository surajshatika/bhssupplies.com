@extends('backend.layouts.app')

@section('content')
@php
    $totalStored = count($summary['reviews']);
    $avgStored   = $totalStored > 0
        ? collect($summary['reviews'])->avg('rating')
        : ($summary['rating'] ?? 0);
    $maxDist     = max($summary['distribution']) ?: 1;
@endphp

<div class="row gutters-16">

    {{-- HEADER --}}
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <h3 class="fs-20 fw-700 mb-0">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="#fbbc04" style="vertical-align:-3px;">
                        <path d="M12 .288l2.833 8.718h9.167l-7.416 5.389 2.833 8.718-7.417-5.388-7.416 5.388 2.833-8.718L0 9.006h9.167z"/>
                    </svg>
                    {{ translate('Google Reviews Dashboard') }}
                </h3>
                <small class="text-muted">
                    @if($lastSyncedAt)
                        <i class="las la-clock"></i> {{ translate('Last synced') }}: {{ \Carbon\Carbon::parse($lastSyncedAt)->format('M d, Y H:i') }}
                        ({{ \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() }})
                    @else
                        <i class="las la-info-circle"></i> {{ translate('Never synced yet') }}
                    @endif
                </small>
            </div>
            <div class="d-flex">
                <form action="{{ route('google-reviews.sync-now') }}" method="POST" id="dash-sync-form" class="d-inline-block mr-2">
                    @csrf
                    <button type="submit" id="dash-sync-btn" class="btn btn-success">
                        <i class="las la-sync-alt"></i> {{ translate('Sync Now') }}
                    </button>
                </form>
                <a href="{{ route('google-reviews-config') }}" class="btn btn-outline-primary">
                    <i class="las la-cog"></i> {{ translate('Settings') }}
                </a>
            </div>
        </div>
    </div>

    {{-- OVERVIEW BAND --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fff7e6 0%, #ffe6b3 100%);">
            <div class="card-body text-center py-4">
                <div class="display-3 fw-700" style="color:#f9a825;">{{ number_format($summary['rating'] ?? 0, 1) }}</div>
                <div class="mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($summary['rating'] ?? 0))
                            <i class="las la-star text-warning fs-25"></i>
                        @elseif($i - ($summary['rating'] ?? 0) < 1)
                            <i class="las la-star-half-alt text-warning fs-25"></i>
                        @else
                            <i class="lar la-star text-muted fs-25"></i>
                        @endif
                    @endfor
                </div>
                <h5 class="mb-1">{{ $summary['business_name'] ?? 'Business' }}</h5>
                <p class="text-muted small mb-3">
                    <i class="las la-users"></i>
                    {{ number_format($summary['total']) }} {{ translate('verified Google reviews') }}
                </p>

                @if(!empty($summary['address']))
                    <div class="small text-muted mb-1"><i class="las la-map-marker-alt"></i> {{ $summary['address'] }}</div>
                @endif
                @if(!empty($summary['phone']))
                    <div class="small text-muted mb-1"><i class="las la-phone"></i> {{ $summary['phone'] }}</div>
                @endif

                @if(!empty($summary['place_url']))
                    <a href="{{ $summary['place_url'] }}" target="_blank" class="btn btn-sm btn-light shadow-sm mt-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#4285F4" style="vertical-align:-3px;">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        {{ translate('View on Google') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- RATING DISTRIBUTION --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-600"><i class="las la-chart-bar text-primary"></i> {{ translate('Rating Distribution (from synced reviews)') }}</h6>
            </div>
            <div class="card-body">
                @for($star = 5; $star >= 1; $star--)
                    @php
                        $count = $summary['distribution'][$star] ?? 0;
                        $pct   = $totalStored > 0 ? round(($count / $totalStored) * 100) : 0;
                        $barColor = $star >= 4 ? 'bg-success' : ($star == 3 ? 'bg-warning' : 'bg-danger');
                    @endphp
                    <div class="d-flex align-items-center mb-2">
                        <div class="text-nowrap mr-3" style="width:55px;">
                            <strong>{{ $star }}</strong> <i class="las la-star text-warning"></i>
                        </div>
                        <div class="progress flex-grow-1" style="height:18px; border-radius:10px;">
                            <div class="progress-bar {{ $barColor }}" role="progressbar"
                                 style="width: {{ $pct }}%; border-radius:10px; font-weight:600; font-size:12px;">
                                {{ $pct > 5 ? $pct.'%' : '' }}
                            </div>
                        </div>
                        <div class="text-nowrap ml-3 text-muted" style="width:50px; text-align:right;">{{ $count }}</div>
                    </div>
                @endfor

                <hr class="my-3">

                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="mb-0 fw-700 text-primary">{{ $totalStored }}</h4>
                        <small class="text-muted">{{ translate('Synced') }}</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0 fw-700 text-success">{{ number_format($avgStored, 1) }}</h4>
                        <small class="text-muted">{{ translate('Avg (synced)') }}</small>
                    </div>
                    <div class="col-4">
                        <h4 class="mb-0 fw-700 text-warning">{{ number_format($summary['total']) }}</h4>
                        <small class="text-muted">{{ translate('Total on Google') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AUTO-UPDATE BANNER --}}
    <div class="col-12 mt-3">
        <div class="alert alert-info d-flex align-items-center mb-3">
            <div class="mr-3 d-flex align-items-center justify-content-center rounded-circle bg-info text-white" style="width:40px;height:40px;">
                <i class="las la-calendar-week fs-20"></i>
            </div>
            <div class="flex-grow-1">
                <strong>{{ translate('Automatic Weekly Sync Active') }}</strong>
                <div class="small text-muted">
                    {{ translate('Reviews refresh automatically every') }} <strong>{{ translate('Monday at 06:30') }}</strong>
                    {{ translate('via Laravel scheduler. No manual action needed.') }}
                </div>
            </div>
            <span class="badge badge-soft-success">{{ translate('Active') }}</span>
        </div>
    </div>

    {{-- REVIEWS LIST --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="mb-0 fw-600"><i class="las la-comments text-primary"></i> {{ translate('Recent Reviews') }}</h6>
                <div class="btn-group btn-group-sm" role="group" id="filter-buttons">
                    <button type="button" class="btn btn-outline-secondary active" data-filter="all">{{ translate('All') }}</button>
                    <button type="button" class="btn btn-outline-success" data-filter="5">5★</button>
                    <button type="button" class="btn btn-outline-success" data-filter="4">4★</button>
                    <button type="button" class="btn btn-outline-warning" data-filter="3">3★</button>
                    <button type="button" class="btn btn-outline-danger" data-filter="2">2★</button>
                    <button type="button" class="btn btn-outline-danger" data-filter="1">1★</button>
                </div>
            </div>
            <div class="card-body p-3">
                @if($totalStored === 0)
                    <div class="text-center py-5">
                        <i class="las la-inbox fs-50 text-muted"></i>
                        <h5 class="mt-2 text-muted">{{ translate('No reviews synced yet') }}</h5>
                        <p class="text-muted">{{ translate('Click "Sync Now" to fetch reviews from Google.') }}</p>
                    </div>
                @else
                    <div class="row" id="reviews-list">
                        @foreach($summary['reviews'] as $review)
                            <div class="col-md-6 col-lg-4 mb-3 review-card" data-rating="{{ $review['rating'] }}">
                                <div class="card h-100 border shadow-sm review-card-inner">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            @if(!empty($review['profile_photo_url']))
                                                <img src="{{ $review['profile_photo_url'] }}"
                                                     alt="{{ $review['author_name'] }}"
                                                     class="rounded-circle mr-2 border"
                                                     style="width:42px;height:42px;object-fit:cover;"
                                                     onerror="this.style.display='none'">
                                            @else
                                                <div class="rounded-circle mr-2 d-flex align-items-center justify-content-center bg-primary text-white fw-600"
                                                     style="width:42px;height:42px;font-size:16px;">
                                                    {{ strtoupper(substr($review['author_name'], 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="flex-grow-1">
                                                @if(!empty($review['author_url']))
                                                    <a href="{{ $review['author_url'] }}" target="_blank" class="text-dark fw-600 text-decoration-none d-block" style="line-height:1.2;">
                                                        {{ $review['author_name'] }}
                                                    </a>
                                                @else
                                                    <div class="fw-600" style="line-height:1.2;">{{ $review['author_name'] }}</div>
                                                @endif
                                                <small class="text-muted">{{ $review['relative_time_description'] }}</small>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review['rating'])
                                                    <i class="las la-star text-warning"></i>
                                                @else
                                                    <i class="lar la-star text-muted"></i>
                                                @endif
                                            @endfor
                                        </div>

                                        @if(!empty($review['text']))
                                            <p class="text-secondary mb-0 review-text" style="font-size:14px; line-height:1.5;">
                                                {{ \Illuminate\Support\Str::limit($review['text'], 220) }}
                                            </p>
                                            @if(strlen($review['text']) > 220)
                                                <button type="button" class="btn btn-link btn-sm p-0 mt-1" onclick="this.previousElementSibling.innerText = `{{ addslashes($review['text']) }}`; this.remove();">
                                                    {{ translate('Read more') }} <i class="las la-angle-down"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
// Filter buttons
document.querySelectorAll('#filter-buttons button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#filter-buttons button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        var filter = this.dataset.filter;
        document.querySelectorAll('.review-card').forEach(card => {
            if (filter === 'all' || card.dataset.rating === filter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// AJAX Sync from dashboard
document.getElementById('dash-sync-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('dash-sync-btn');
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
            AIZ.plugins.notify('success', data.message + ' (' + data.count + ' reviews)');
            setTimeout(() => location.reload(), 800);
        } else {
            AIZ.plugins.notify('danger', data.message || 'Sync failed');
            btn.disabled = false; btn.innerHTML = orig;
        }
    })
    .catch(err => {
        AIZ.plugins.notify('danger', 'Network error');
        btn.disabled = false; btn.innerHTML = orig;
    });
});
</script>
<style>
.review-card-inner { transition: transform .15s ease, box-shadow .15s ease; }
.review-card-inner:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,.08) !important; }
.fs-22 { font-size: 22px !important; }
.fs-25 { font-size: 25px !important; }
.fs-20 { font-size: 20px !important; }
.fs-50 { font-size: 50px !important; }
.fw-700 { font-weight: 700 !important; }
.fw-600 { font-weight: 600 !important; }
</style>
@endsection
