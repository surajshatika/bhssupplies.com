@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

@php
    $totalRevenue = array_sum(array_map(fn($c) => $c['performance']['revenue'] ?? 0, $campaigns));
    $totalSpend   = array_sum(array_map(fn($c) => $c['performance']['spend']   ?? 0, $campaigns));
    $totalRoas    = $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : null;
    $totalOrders  = array_sum(array_map(fn($c) => $c['performance']['orders']  ?? 0, $campaigns));
    $totalClicks  = array_sum(array_map(fn($c) => $c['performance']['clicks']  ?? 0, $campaigns));
@endphp

<div class="mm-hero" style="background:linear-gradient(135deg,#10B981 0%,#0EA5E9 50%,#7B61FF 100%);">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            </div>
            <div>
                <h2>{{ translate('UTM Campaign Manager') }}</h2>
                <p>{{ translate('Build trackable URLs + short links, then track ROAS / CPA / CVR per campaign from the first-party warehouse.') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-bullhorn"></i> {{ count($campaigns) }} {{ translate('campaigns') }}</span>
                    <span class="mm-chip"><i class="las la-dollar-sign"></i> ${{ number_format($totalRevenue, 2) }} {{ translate('revenue') }}</span>
                    @if($totalRoas !== null)
                        <span class="mm-chip"><i class="las la-chart-line"></i> {{ $totalRoas }}x ROAS</span>
                    @endif
                </div>
            </div>
        </div>
        <button class="mm-btn mm-btn-light" data-toggle="collapse" data-target="#newCampaignForm">
            <i class="las la-plus"></i> {{ translate('New Campaign') }}
        </button>
    </div>
</div>

{{-- KPI strip --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="mm-stat"><div class="mm-stat-icon mm-tint-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div><h3 class="mm-stat-value">{{ number_format($totalClicks) }}</h3><div class="mm-stat-label">{{ translate('Clicks') }}</div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="mm-stat"><div class="mm-stat-icon mm-tint-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div><h3 class="mm-stat-value">{{ number_format($totalOrders) }}</h3><div class="mm-stat-label">{{ translate('Orders') }}</div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="mm-stat"><div class="mm-stat-icon mm-tint-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><h3 class="mm-stat-value">${{ number_format($totalRevenue, 2) }}</h3><div class="mm-stat-label">{{ translate('Revenue') }}</div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="mm-stat"><div class="mm-stat-icon mm-tint-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><h3 class="mm-stat-value">{{ $totalRoas !== null ? $totalRoas.'x' : '—' }}</h3><div class="mm-stat-label">{{ translate('Overall ROAS') }}</div></div>
    </div>
</div>

{{-- New campaign form (collapsed) --}}
<div class="collapse" id="newCampaignForm">
    <div class="mm-card mb-4">
        <div class="mm-card-header"><h5 class="mm-card-title"><i class="las la-plus-circle text-primary"></i> {{ translate('Create / Edit Campaign') }}</h5></div>
        <div class="mm-card-body">
            <form method="POST" action="{{ route('analytics.campaigns.save') }}">
                @csrf
                <input type="hidden" name="id" id="cmp-id">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small text-muted">{{ translate('Campaign Name') }}</label>
                        <input type="text" name="name" id="cmp-name" class="form-control" required placeholder="Spring HVAC Sale 2026">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small text-muted">{{ translate('Destination URL') }}</label>
                        <input type="url" name="destination_url" id="cmp-url" class="form-control" required placeholder="{{ url('/category/hvac-supplies') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small text-muted">utm_source</label>
                        <input type="text" name="utm_source" id="cmp-source" class="form-control" placeholder="google">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small text-muted">utm_medium</label>
                        <input type="text" name="utm_medium" id="cmp-medium" class="form-control" placeholder="cpc">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small text-muted">utm_campaign</label>
                        <input type="text" name="utm_campaign" id="cmp-campaign" class="form-control" placeholder="spring_sale">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small text-muted">{{ translate('Ad Spend (for ROAS)') }}</label>
                        <input type="number" step="0.01" min="0" name="ad_spend" id="cmp-spend" class="form-control" placeholder="500.00">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small text-muted">utm_term</label>
                        <input type="text" name="utm_term" id="cmp-term" class="form-control" placeholder="keyword">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small text-muted">utm_content</label>
                        <input type="text" name="utm_content" id="cmp-content" class="form-control" placeholder="banner_a">
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary"><i class="las la-save"></i> {{ translate('Save Campaign') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Campaigns table --}}
<div class="mm-card">
    <div class="mm-card-header">
        <h5 class="mm-card-title"><i class="las la-list"></i> {{ translate('Campaigns') }}</h5>
        <form method="GET" class="d-inline-flex align-items-center" style="gap:.5rem;">
            <small class="text-muted">{{ translate('ROAS window') }}:</small>
            <select name="window" class="form-control form-control-sm" onchange="this.form.submit()">
                @foreach([7,14,30,60,90] as $d)
                    <option value="{{ $d }}" {{ $window==$d?'selected':'' }}>{{ $d }}d</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="mm-card-body p-0">
        @if(empty($campaigns))
            <div class="text-center py-5 text-muted">
                <i class="las la-bullhorn" style="font-size:50px; opacity:.3;"></i>
                <p class="mt-2 mb-0">{{ translate('No campaigns yet — click "New Campaign" to build your first trackable link.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Campaign') }}</th>
                            <th>{{ translate('Short Link') }}</th>
                            <th>{{ translate('UTM') }}</th>
                            <th class="text-right">{{ translate('Clicks') }}</th>
                            <th class="text-right">{{ translate('Orders') }}</th>
                            <th class="text-right">{{ translate('Revenue') }}</th>
                            <th class="text-right">{{ translate('Spend') }}</th>
                            <th class="text-right">ROAS</th>
                            <th class="text-right">CPA</th>
                            <th class="text-right">CVR</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($campaigns as $c)
                        @php
                            $p = $c['performance'] ?? [];
                            $roas = $p['roas'] ?? null;
                            $roasColor = $roas === null ? 'secondary' : ($roas >= 3 ? 'success' : ($roas >= 1 ? 'warning' : 'danger'));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $c['name'] }}</strong>
                                <div class="small text-muted">{{ \Carbon\Carbon::parse($c['created_at'] ?? null)->diffForHumans() }}</div>
                            </td>
                            <td>
                                <a href="{{ url('/c/'.$c['short_code']) }}" target="_blank" class="text-primary">
                                    /c/{{ $c['short_code'] }}
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary ml-1" onclick="copyTxt('{{ url('/c/'.$c['short_code']) }}', this)">
                                    <i class="las la-copy"></i>
                                </button>
                            </td>
                            <td><small><code>{{ $c['utm_source'] ?? '' }}/{{ $c['utm_medium'] ?? '' }}/{{ $c['utm_campaign'] ?? '' }}</code></small></td>
                            <td class="text-right">{{ number_format($p['clicks'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($p['orders'] ?? 0) }}</td>
                            <td class="text-right">${{ number_format($p['revenue'] ?? 0, 2) }}</td>
                            <td class="text-right">${{ number_format($p['spend'] ?? 0, 2) }}</td>
                            <td class="text-right"><span class="badge badge-{{ $roasColor }}">{{ $roas !== null ? $roas.'x' : '—' }}</span></td>
                            <td class="text-right">{{ $p['cpa'] !== null ? '$'.$p['cpa'] : '—' }}</td>
                            <td class="text-right">{{ $p['cvr_pct'] ?? 0 }}%</td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-outline-primary" onclick='editCampaign(@json($c))'><i class="las la-edit"></i></button>
                                <form method="POST" action="{{ route('analytics.campaigns.delete', $c['id']) }}" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="las la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
function copyTxt(text, btn){
    navigator.clipboard.writeText(text).then(() => {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="las la-check text-success"></i>';
        setTimeout(() => btn.innerHTML = orig, 1500);
    });
}
function editCampaign(c){
    document.getElementById('cmp-id').value         = c.id;
    document.getElementById('cmp-name').value       = c.name;
    document.getElementById('cmp-url').value        = c.destination_url ? c.destination_url.split('?')[0] : '';
    document.getElementById('cmp-source').value     = c.utm_source   || '';
    document.getElementById('cmp-medium').value     = c.utm_medium   || '';
    document.getElementById('cmp-campaign').value   = c.utm_campaign || '';
    document.getElementById('cmp-term').value       = c.utm_term     || '';
    document.getElementById('cmp-content').value    = c.utm_content  || '';
    document.getElementById('cmp-spend').value      = c.ad_spend     || '';
    var collapse = document.getElementById('newCampaignForm');
    collapse.classList.add('show');
    collapse.scrollIntoView({ behavior:'smooth' });
}
</script>
@endsection
