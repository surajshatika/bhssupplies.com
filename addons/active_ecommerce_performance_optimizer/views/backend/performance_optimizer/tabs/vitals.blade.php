@php
    $hasData = !empty($samples) && count($samples) > 0;
@endphp

<div class="perf-section">
    <div class="perf-section-header">
        <h5><span class="perf-section-icon"><i class="las la-chart-line"></i></span>{{ translate('7-day Trend (P75)') }}</h5>
        <small class="text-muted">{{ translate('Samples collected from real visitors') }}</small>
    </div>
    <div class="perf-section-body">
        @if($hasData)
            <canvas id="perf-vitals-chart" height="80"></canvas>
        @else
            <div class="text-center py-4">
                <div style="font-size:48px;color:#cbd3da"><i class="las la-chart-area"></i></div>
                <h6 class="mt-2">{{ translate('No Web Vitals data collected yet') }}</h6>
                <p class="text-muted">{{ translate('Enable') }} <strong>{{ translate('Collect Web Vitals') }}</strong> {{ translate('in the settings panel on the right, then let real visitors browse your site.') }}</p>
                <small class="text-muted">{{ translate('Samples appear here within a few minutes of the first visit.') }}</small>
            </div>
        @endif
    </div>
</div>

<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(40,167,69,.1);color:var(--perf-green)"><i class="las la-history"></i></span>{{ translate('Recent Samples') }}</h5>
                <span class="perf-pill">{{ count($samples) }} {{ translate('latest') }}</span>
            </div>
            <div class="perf-section-body p-0" style="max-height:420px;overflow:auto">
                @if($hasData)
                    <table class="perf-table">
                        <thead>
                            <tr>
                                <th>{{ translate('Time') }}</th>
                                <th>{{ translate('Metric') }}</th>
                                <th>{{ translate('Value') }}</th>
                                <th>{{ translate('Rating') }}</th>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Device') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($samples as $v)
                            @php
                                $r = $v->rating ?: 'no-data';
                                $sev = $r === 'good' ? 'low' : ($r === 'poor' ? 'critical' : ($r === 'needs-improvement' ? 'medium' : 'low'));
                            @endphp
                            <tr>
                                <td><small>{{ $v->created_at?->diffForHumans() }}</small></td>
                                <td><span class="perf-pill">{{ $v->metric }}</span></td>
                                <td><strong>{{ $v->metric === 'CLS' ? round($v->value, 3) : round($v->value) }}</strong></td>
                                <td><span class="perf-sev perf-sev-{{ $sev }}">{{ $r }}</span></td>
                                <td><small>{{ \Illuminate\Support\Str::limit($v->url, 40) }}</small></td>
                                <td><i class="las la-{{ $v->device === 'mobile' ? 'mobile' : 'desktop' }}"></i> {{ $v->device }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-4 text-muted">{{ translate('No samples yet.') }}</div>
                @endif
            </div>
        </div>
    </div>

    <div>
        <form action="{{ route('performance_optimizer.settings.update') }}" method="POST">
            @csrf
            <div class="perf-section">
                <div class="perf-section-header"><h5><i class="las la-cog"></i> {{ translate('Vitals Settings') }}</h5></div>
                <div class="perf-section-body">
                    <div class="perf-toggle-row">
                        <strong>{{ translate('Collect Web Vitals') }}</strong>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="perf_vitals_collect_status" value="1" @if(get_setting('perf_vitals_collect_status') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="form-group mt-3">
                        <label class="small">{{ translate('Sample rate (%)') }}: <strong>{{ get_setting('perf_vitals_sample_rate', 10) }}</strong></label>
                        <input type="range" name="perf_vitals_sample_rate" min="1" max="100" value="{{ get_setting('perf_vitals_sample_rate', 10) }}" class="form-control-range"
                               oninput="this.previousElementSibling.querySelector('strong').textContent=this.value">
                        <small class="text-muted">{{ translate('Percentage of visitors to track. 10 = 10% of page views.') }}</small>
                    </div>
                    <button class="btn btn-soft-primary btn-sm btn-block">{{ translate('Save') }}</button>
                </div>
            </div>
        </form>
        <form action="{{ route('performance_optimizer.vitals.clear') }}" method="POST"
              onsubmit="return confirm('{{ translate('Delete all Web Vitals samples?') }}')">
            @csrf
            <button class="btn btn-soft-danger btn-sm btn-block">{{ translate('Clear all samples') }}</button>
        </form>

        <div class="perf-tips mt-3">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('What is Core Web Vitals?') }}</div>
            <div class="perf-tips-body">
                <p><strong>LCP</strong> ({{ translate('Largest Contentful Paint') }}) — {{ translate('time to render the main content. Target: < 2.5s.') }}</p>
                <p><strong>INP</strong> ({{ translate('Interaction to Next Paint') }}) — {{ translate('responsiveness to user input. Target: < 200ms.') }}</p>
                <p><strong>CLS</strong> ({{ translate('Cumulative Layout Shift') }}) — {{ translate('visual stability. Target: < 0.1.') }}</p>
                <p>{{ translate('Google uses these in search ranking.') }}</p>
            </div>
        </div>
    </div>
</div>

@if($hasData)
@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var labels = @json($trend['labels']);
    var series = @json($trend['series']);
    var colors = { LCP:'#2196f3', CLS:'#9c27b0', INP:'#ff9800', FCP:'#4caf50', TTFB:'#607d8b' };
    var datasets = [];
    Object.keys(series).forEach(function(metric){
        datasets.push({
            label: metric,
            data: series[metric],
            borderColor: colors[metric] || '#999',
            backgroundColor: (colors[metric] || '#999') + '22',
            tension: 0.3,
            spanGaps: true,
            yAxisID: metric === 'CLS' ? 'y2' : 'y1',
        });
    });
    var ctx = document.getElementById('perf-vitals-chart');
    if (ctx && window.Chart) {
        new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y1: { type:'linear', position:'left',  title:{ display:true, text:'ms' } },
                    y2: { type:'linear', position:'right', title:{ display:true, text:'CLS' }, grid:{ drawOnChartArea:false } },
                }
            }
        });
    }
})();
</script>
@endpush
@endif
