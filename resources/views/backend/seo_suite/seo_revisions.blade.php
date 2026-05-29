@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3"><i class="las la-history mr-2 text-secondary"></i>{{ translate('SEO Revisions') }}</h1>
            <p class="text-muted mb-0">{{ translate('Track all SEO score changes and analyze improvement trends over time.') }}</p>
        </div>
        <div class="col-md-4 text-md-right">
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-arrow-left mr-1"></i>{{ translate('Back to Suite') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

@php
    $totalRevisions = count($histories);
    $avgScore = $totalRevisions ? round(array_sum(array_column($histories, 'score')) / $totalRevisions) : 0;
    $latestScore = $histories[0]['score'] ?? 0;
    $prevScore   = $histories[1]['score'] ?? 0;
    $scoreDelta  = $latestScore - $prevScore;
@endphp

<div class="row gutters-16 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-3">
            <div class="h2 mb-1">{{ $totalRevisions }}</div>
            <div class="text-muted small">{{ translate('Total Revisions') }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-3">
            <div class="h2 mb-1 text-primary">{{ $latestScore }}</div>
            <div class="text-muted small">{{ translate('Latest Score') }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-3">
            <div class="h2 mb-1 text-info">{{ $avgScore }}</div>
            <div class="text-muted small">{{ translate('Average Score') }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-3">
            <div class="h2 mb-1 {{ $scoreDelta >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $scoreDelta >= 0 ? '+' : '' }}{{ $scoreDelta }}
            </div>
            <div class="text-muted small">{{ translate('vs Previous') }}</div>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('Revision History') }}</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Score') }}</th>
                                <th>{{ translate('Grade') }}</th>
                                <th>{{ translate('Trend') }}</th>
                                <th>{{ translate('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $i => $history)
                            @php
                                $prev = $histories[$i + 1]['score'] ?? null;
                                $delta = $prev !== null ? ($history['score'] - $prev) : null;
                            @endphp
                            <tr>
                                <td>{{ $totalRevisions - $i }}</td>
                                <td class="text-truncate" style="max-width:220px;">
                                    <a href="{{ $history['url'] ?? '#' }}" target="_blank" class="small">
                                        {{ $history['url'] ?? '-' }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress mr-2" style="height:6px; width:60px;">
                                            <div class="progress-bar bg-{{ ($history['score'] ?? 0) >= 80 ? 'success' : (($history['score'] ?? 0) >= 50 ? 'warning' : 'danger') }}"
                                                 style="width:{{ $history['score'] ?? 0 }}%"></div>
                                        </div>
                                        <strong>{{ $history['score'] ?? 0 }}</strong>
                                    </div>
                                </td>
                                <td><span class="badge badge-soft-info">{{ $history['grade'] ?? '-' }}</span></td>
                                <td>
                                    @if($delta !== null)
                                        @if($delta > 0)
                                            <span class="text-success small"><i class="las la-arrow-up mr-1"></i>+{{ $delta }}</span>
                                        @elseif($delta < 0)
                                            <span class="text-danger small"><i class="las la-arrow-down mr-1"></i>{{ $delta }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap small text-muted">
                                    {{ isset($history['recorded_at']) ? \Carbon\Carbon::parse($history['recorded_at'])->format('M d, Y H:i') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ translate('No SEO revision history yet. Run SEO audits to populate this table.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ translate('About SEO Revisions') }}</h6></div>
            <div class="card-body small text-muted">
                <p>{{ translate('SEO Revisions automatically track your SEO score over time whenever you run SEO audits.') }}</p>
                <p>{{ translate('Use this to:') }}</p>
                <ul class="pl-3">
                    <li>{{ translate('Monitor progress after optimizations') }}</li>
                    <li>{{ translate('Identify score drops quickly') }}</li>
                    <li>{{ translate('Compare before/after changes') }}</li>
                    <li>{{ translate('Build your SEO improvement report') }}</li>
                </ul>
                <a href="{{ route('admin.seo-suite.index', ['tab' => 'run']) }}" class="btn btn-soft-primary btn-sm btn-block">
                    <i class="las la-play mr-1"></i>{{ translate('Run SEO Audit') }}
                </a>
            </div>
        </div>

        @if($totalRevisions >= 2)
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Score Trend') }}</h6></div>
            <div class="card-body">
                <canvas id="revisionChart" height="150"></canvas>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
@if(count($histories) >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
var labels = @json(array_map(fn($h) => \Carbon\Carbon::parse($h['recorded_at'])->format('M d'), array_reverse($histories)));
var scores = @json(array_column(array_reverse($histories), 'score'));
new Chart(document.getElementById('revisionChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{ label: 'Score', data: scores, borderColor: '#1cc88a', backgroundColor: 'rgba(28,200,138,0.1)', borderWidth: 2, fill: true, tension: 0.4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100 } } }
});
</script>
@endif
@endsection
