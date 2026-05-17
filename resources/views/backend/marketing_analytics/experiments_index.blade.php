@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

@php
    $runningCount = collect($experiments)->where('status', 'running')->count();
    $winners = collect($experiments)->filter(fn($e) => !empty($e['results']['winner']))->count();
@endphp

<div class="mm-hero" style="background:linear-gradient(135deg,#F59E0B 0%,#EF4444 50%,#7B61FF 100%);">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M10 2v7.31"/><path d="M14 9.3V1.99"/><path d="M8.5 2h7"/><path d="M14 9.3a6.5 6.5 0 1 1-4 0"/></svg>
            </div>
            <div>
                <h2>{{ translate('A/B Testing Lab') }}</h2>
                <p>{{ translate('Run experiments with deterministic visitor assignment, automatic conversion tracking, and 95%-confidence stat-sig detection.') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><i class="las la-flask"></i> {{ count($experiments) }} {{ translate('total') }}</span>
                    <span class="mm-chip"><span class="mm-dot ok"></span> {{ $runningCount }} {{ translate('running') }}</span>
                    <span class="mm-chip"><i class="las la-trophy"></i> {{ $winners }} {{ translate('with winner') }}</span>
                </div>
            </div>
        </div>
        <button class="mm-btn mm-btn-light" data-toggle="collapse" data-target="#newExpForm">
            <i class="las la-plus"></i> {{ translate('New Experiment') }}
        </button>
    </div>
</div>

{{-- New experiment form --}}
<div class="collapse" id="newExpForm">
    <div class="mm-card mb-4">
        <div class="mm-card-header"><h5 class="mm-card-title"><i class="las la-plus-circle text-primary"></i> {{ translate('Create / Edit Experiment') }}</h5></div>
        <div class="mm-card-body">
            <form method="POST" action="{{ route('analytics.experiments.save') }}">
                @csrf
                <input type="hidden" name="id" id="exp-id">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small text-muted">{{ translate('Name') }}</label>
                        <input type="text" name="name" id="exp-name" class="form-control" required placeholder="Hero CTA wording">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="small text-muted">{{ translate('Key (use in code)') }}</label>
                        <input type="text" name="key" id="exp-key" class="form-control" required placeholder="hero_cta" pattern="[a-z0-9_]+">
                        <small class="text-muted" style="font-size:11px;">{{ translate('Lowercase letters, numbers, underscores only.') }}</small>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="small text-muted">{{ translate('Status') }}</label>
                        <select name="status" id="exp-status" class="form-control">
                            <option value="running">{{ translate('Running') }}</option>
                            <option value="paused">{{ translate('Paused') }}</option>
                            <option value="completed">{{ translate('Completed') }}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="small text-muted">{{ translate('Goal Event') }}</label>
                    <select name="goal_event" id="exp-goal" class="form-control">
                        <option value="Purchase">Purchase</option>
                        <option value="AddToCart">AddToCart</option>
                        <option value="ViewContent">ViewContent</option>
                        <option value="Lead">Lead</option>
                        <option value="Subscribe">Subscribe</option>
                    </select>
                </div>

                <label class="small text-muted">{{ translate('Variants (first = control)') }}</label>
                <div id="variants-rows">
                    <div class="d-flex align-items-center mb-2" style="gap:.5rem;">
                        <input type="text" name="variant_keys[]" class="form-control" placeholder="A" value="A" required style="max-width:120px;">
                        <input type="number" name="variant_weights[]" class="form-control" placeholder="50" value="50" min="1" style="max-width:120px;">
                        <small class="text-muted">{{ translate('weight %') }}</small>
                    </div>
                    <div class="d-flex align-items-center mb-2" style="gap:.5rem;">
                        <input type="text" name="variant_keys[]" class="form-control" placeholder="B" value="B" required style="max-width:120px;">
                        <input type="number" name="variant_weights[]" class="form-control" placeholder="50" value="50" min="1" style="max-width:120px;">
                        <small class="text-muted">{{ translate('weight %') }}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addVariantRow()"><i class="las la-plus"></i> {{ translate('Add variant') }}</button>

                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-primary"><i class="las la-save"></i> {{ translate('Save Experiment') }}</button>
                </div>
            </form>

            <div class="alert alert-info mt-3 mb-0">
                <strong><i class="las la-info-circle"></i> {{ translate('In your Blade theme:') }}</strong>
                <code class="ml-2">{{ '@if(ab(\'your_key\') === \'B\') ... @else ... @endif' }}</code>
            </div>
        </div>
    </div>
</div>

{{-- Experiments list --}}
@if(empty($experiments))
    <div class="mm-card">
        <div class="mm-card-body text-center text-muted py-5">
            <i class="las la-flask" style="font-size:50px; opacity:.3;"></i>
            <p class="mt-2 mb-0">{{ translate('No experiments yet — click "New Experiment" to start your first A/B test.') }}</p>
        </div>
    </div>
@else
    @foreach($experiments as $exp)
        @php
            $r = $exp['results'] ?? [];
            $rows = $r['rows'] ?? [];
            $winner = $r['winner'] ?? null;
            $totalExp = array_sum(array_map(fn($x) => $x['exposures'] ?? 0, $rows));
        @endphp
        <div class="mm-card mb-4">
            <div class="mm-card-header">
                <div>
                    <h5 class="mm-card-title">
                        <i class="las la-flask text-warning"></i> {{ $exp['name'] }}
                        @if($exp['status'] === 'running')
                            <span class="badge badge-success ml-2">RUNNING</span>
                        @elseif($exp['status'] === 'paused')
                            <span class="badge badge-warning ml-2">PAUSED</span>
                        @else
                            <span class="badge badge-secondary ml-2">COMPLETED</span>
                        @endif
                        @if($winner)
                            <span class="badge badge-soft-success ml-2"><i class="las la-trophy"></i> Winner: {{ $winner }}</span>
                        @endif
                    </h5>
                    <small class="text-muted">Key: <code>{{ $exp['key'] }}</code> · Goal: <code>{{ $exp['goal_event'] ?? 'Purchase' }}</code> · {{ number_format($totalExp) }} {{ translate('exposures') }}</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick='editExperiment(@json($exp))'><i class="las la-edit"></i></button>
                    <form method="POST" action="{{ route('analytics.experiments.delete', $exp['id']) }}" class="d-inline" onsubmit="return confirm('Delete this experiment?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="las la-trash"></i></button>
                    </form>
                </div>
            </div>
            <div class="mm-card-body p-0">
                @if(empty($rows))
                    <div class="text-center text-muted py-4">{{ translate('No data yet.') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Variant') }}</th>
                                    <th class="text-right">{{ translate('Exposures') }}</th>
                                    <th class="text-right">{{ translate('Conversions') }}</th>
                                    <th class="text-right">{{ translate('Revenue') }}</th>
                                    <th class="text-right">{{ translate('Conv. Rate') }}</th>
                                    <th class="text-right">{{ translate('Lift vs Control') }}</th>
                                    <th class="text-right">{{ translate('Z-score') }}</th>
                                    <th class="text-center">{{ translate('Significant?') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row['variant'] }}</strong>
                                        @if($row['is_control']) <small class="text-muted">(control)</small> @endif
                                        @if($winner === $row['variant']) <i class="las la-trophy text-warning ml-1"></i> @endif
                                    </td>
                                    <td class="text-right">{{ number_format($row['exposures']) }}</td>
                                    <td class="text-right">{{ number_format($row['conversions']) }}</td>
                                    <td class="text-right">${{ number_format($row['revenue'], 2) }}</td>
                                    <td class="text-right"><strong>{{ $row['conv_rate_pct'] }}%</strong></td>
                                    <td class="text-right">
                                        @if($row['lift_pct'] === null)
                                            —
                                        @else
                                            <span class="text-{{ $row['lift_pct'] > 0 ? 'success' : ($row['lift_pct'] < 0 ? 'danger' : 'muted') }}">
                                                {{ $row['lift_pct'] > 0 ? '+' : '' }}{{ $row['lift_pct'] }}%
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $row['z_score'] ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($row['is_control'])
                                            <span class="text-muted">—</span>
                                        @elseif($row['significant'])
                                            <span class="badge badge-success">YES (95%)</span>
                                        @else
                                            <span class="badge badge-secondary">{{ translate('No') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endif
@endsection

@section('script')
<script>
function addVariantRow(){
    var html = `<div class="d-flex align-items-center mb-2" style="gap:.5rem;">
        <input type="text" name="variant_keys[]" class="form-control" placeholder="C" required style="max-width:120px;">
        <input type="number" name="variant_weights[]" class="form-control" placeholder="33" min="1" style="max-width:120px;">
        <small class="text-muted">{{ translate('weight %') }}</small>
        <button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.remove()"><i class="las la-times"></i></button>
    </div>`;
    document.getElementById('variants-rows').insertAdjacentHTML('beforeend', html);
}

function editExperiment(e){
    document.getElementById('exp-id').value     = e.id;
    document.getElementById('exp-name').value   = e.name;
    document.getElementById('exp-key').value    = e.key;
    document.getElementById('exp-status').value = e.status;
    document.getElementById('exp-goal').value   = e.goal_event || 'Purchase';

    var rows = document.getElementById('variants-rows');
    rows.innerHTML = '';
    (e.variants || []).forEach(v => {
        rows.insertAdjacentHTML('beforeend', `<div class="d-flex align-items-center mb-2" style="gap:.5rem;">
            <input type="text" name="variant_keys[]" class="form-control" value="${v.key}" required style="max-width:120px;">
            <input type="number" name="variant_weights[]" class="form-control" value="${v.weight}" min="1" style="max-width:120px;">
            <small class="text-muted">{{ translate('weight %') }}</small>
        </div>`);
    });

    var collapse = document.getElementById('newExpForm');
    collapse.classList.add('show');
    collapse.scrollIntoView({ behavior:'smooth' });
}
</script>
@endsection
