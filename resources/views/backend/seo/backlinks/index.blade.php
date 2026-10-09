@extends('backend.layouts.app')

@section('content')
@include('backend.seo.partials.suite_nav')

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-0">{{ translate('Backlinks & Citations') }}</h1>
        <p class="text-muted mb-0 small">{{ translate('Track real links to bhssupplies.com: local citations to claim, outreach in progress, and verified live links (checked weekly).') }}</p>
    </div>
    <div class="d-flex flex-wrap" style="gap:.4rem">
        <form method="POST" action="{{ route('admin.seo_backlinks.verify') }}">@csrf
            <button class="btn btn-sm btn-soft-primary"><i class="las la-sync mr-1"></i>{{ translate('Verify now') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.seo_backlinks.seed') }}">@csrf
            <button class="btn btn-sm btn-soft-secondary"><i class="las la-map-marker mr-1"></i>{{ translate('Add Canadian citation targets') }}</button>
        </form>
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#bl-add"><i class="las la-plus mr-1"></i>{{ translate('Add link') }}</button>
    </div>
</div>

<div class="row gutters-10 mb-3">
    @foreach([
        ['Live links', $stats['live'], 'success', 'la-link'],
        ['Linking domains', $stats['live_domains'], 'primary', 'la-globe'],
        ['Follow (pass authority)', $stats['follow'], 'info', 'la-arrow-right'],
        ['Lost in 30 days', $stats['lost_30d'], $stats['lost_30d'] ? 'danger' : 'secondary', 'la-unlink'],
        ['Targets in progress', $stats['targets'], 'warning', 'la-bullseye'],
    ] as [$label, $value, $tone, $icon])
        <div class="col">
            <div class="card mb-0 h-100"><div class="card-body py-3">
                <div class="text-muted small"><i class="las {{ $icon }} text-{{ $tone }}"></i> {{ translate($label) }}</div>
                <div class="h3 mb-0 fw-700">{{ $value }}</div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <form class="d-flex flex-wrap w-100" style="gap:.4rem" method="GET">
                    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:220px" placeholder="{{ translate('Search site / URL') }}">
                    <select name="status" class="form-control form-control-sm" style="max-width:150px" onchange="this.form.submit()">
                        <option value="">{{ translate('All statuses') }}</option>
                        @foreach(\App\Models\SeoBacklink::STATUSES as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }} ({{ $statusCounts[$s] ?? 0 }})</option>
                        @endforeach
                    </select>
                    <select name="type" class="form-control form-control-sm" style="max-width:150px" onchange="this.form.submit()">
                        <option value="">{{ translate('All types') }}</option>
                        @foreach(\App\Models\SeoBacklink::TYPES as $t)
                            <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th>{{ translate('Site') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Anchor / rel') }}</th><th>{{ translate('Last check') }}</th><th></th>
                    </tr></thead>
                    <tbody>
                    @forelse($backlinks as $bl)
                        @php $tone = ['live'=>'success','submitted'=>'info','contacted'=>'primary','prospect'=>'secondary','lost'=>'danger','rejected'=>'dark'][$bl->status] ?? 'secondary'; @endphp
                        <tr>
                            <td>
                                <div class="fw-600">{{ $bl->site_name }} <span class="badge badge-inline badge-soft-secondary">{{ $bl->type }}</span></div>
                                @if($bl->source_url)<a class="small text-muted" href="{{ $bl->source_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($bl->source_url, 60) }}</a>
                                @elseif($bl->submit_url)<a class="small" href="{{ $bl->submit_url }}" target="_blank" rel="noopener">{{ translate('Open sign-up') }} ↗</a>@endif
                                @if($bl->notes)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($bl->notes, 90) }}</div>@endif
                            </td>
                            <td><span class="badge badge-inline badge-{{ $tone }}">{{ ucfirst($bl->status) }}</span></td>
                            <td class="small">{{ $bl->anchor_text ?: '—' }}@if($bl->rel)<br><span class="text-{{ $bl->rel === 'follow' ? 'success' : 'muted' }}">{{ $bl->rel }}</span>@endif</td>
                            <td class="small text-muted">{{ $bl->last_checked_at ? $bl->last_checked_at->diffForHumans() : '—' }}@if($bl->last_http_status !== null)<br>HTTP {{ $bl->last_http_status }}@endif</td>
                            <td class="text-right text-nowrap">
                                <button class="btn btn-xs btn-soft-primary" data-toggle="modal" data-target="#bl-edit-{{ $bl->id }}"><i class="las la-edit"></i></button>
                                <form method="POST" action="{{ route('admin.seo_backlinks.destroy', $bl) }}" class="d-inline" onsubmit="return confirm('{{ translate('Remove this entry?') }}')">@csrf
                                    <button class="btn btn-xs btn-soft-danger"><i class="las la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            {{ translate('Nothing tracked yet. Start with "Add Canadian citation targets", or import your Google Search Console links export.') }}
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer">{{ $backlinks->links() }}</div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Anchor text profile (live links)') }}</h6></div>
            <div class="card-body">
                @php $total = max(1, array_sum($profile)); @endphp
                @if(array_sum($profile) === 0)
                    <p class="text-muted small mb-0">{{ translate('Appears once links are verified live.') }}</p>
                @else
                    @foreach($profile as $label => $n)
                        <div class="d-flex justify-content-between small"><span>{{ translate($label) }}</span><span>{{ $n }} ({{ round($n / $total * 100) }}%)</span></div>
                        <div class="progress mb-2" style="height:6px"><div class="progress-bar" style="width:{{ $n / $total * 100 }}%"></div></div>
                    @endforeach
                    @if(($profile['Keyword / other'] / $total) > 0.3)
                        <div class="alert alert-warning small py-2 mb-0">{{ translate('Over 30% keyword anchors can look manipulative to Google. Favour branded/URL anchors in new outreach.') }}</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Import from Google Search Console') }}</h6></div>
            <div class="card-body">
                <p class="small text-muted">{{ translate('Search Console → Links → "Top linking sites" or "Latest links" → Export → CSV. Then upload here and click Verify now.') }}</p>
                <form method="POST" action="{{ route('admin.seo_backlinks.import') }}" enctype="multipart/form-data">@csrf
                    <input type="file" name="file" accept=".csv" class="form-control-file mb-2" required>
                    <button class="btn btn-sm btn-primary btn-block">{{ translate('Import CSV') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@php
    $formFields = function ($bl = null) {
        $v = fn($k) => old($k, $bl?->{$k});
        return view('backend.seo.backlinks.form_fields', ['v' => $v])->render();
    };
@endphp

<div class="modal fade" id="bl-add" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.seo_backlinks.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">{{ translate('Add link / target') }}</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">{!! $formFields() !!}</div>
        <div class="modal-footer"><button class="btn btn-primary">{{ translate('Save') }}</button></div>
    </form>
</div></div></div>

@foreach($backlinks as $bl)
<div class="modal fade" id="bl-edit-{{ $bl->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.seo_backlinks.update', $bl) }}">@csrf
        <div class="modal-header"><h5 class="modal-title">{{ $bl->site_name }}</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">{!! $formFields($bl) !!}</div>
        <div class="modal-footer"><button class="btn btn-primary">{{ translate('Save') }}</button></div>
    </form>
</div></div></div>
@endforeach
@endsection
