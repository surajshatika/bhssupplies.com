<div class="perf-section">
    <div class="perf-section-header">
        <h5><i class="las la-robot"></i> {{ translate('AI Recommendations') }}</h5>
        <div class="d-flex align-items-center">
            <form action="{{ route('performance_optimizer.ai.run') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-soft-primary btn-sm" type="submit">
                    <i class="las la-sync"></i> {{ translate('Run Analysis Now') }}
                </button>
            </form>
        </div>
    </div>
    <div class="perf-section-body">
        <p class="mb-0">{{ translate('Rule-based recommendation engine. Inspects Web Vitals, image stats, database tables, cache state, security settings, and CSS/JS config — then produces actionable fixes. Many can be applied with one click.') }}</p>
    </div>
</div>

<div class="perf-top-stats">
    <div class="perf-stat-card perf-stat-yellow">
        <div class="perf-stat-value">{{ $stats['pending'] }}</div>
        <div class="perf-stat-label">{{ translate('Pending') }}</div>
        <div class="perf-stat-sub">{{ translate('Open recommendations') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-green">
        <div class="perf-stat-value">{{ $stats['applied'] }}</div>
        <div class="perf-stat-label">{{ translate('Applied This Month') }}</div>
        <div class="perf-stat-sub">{{ translate('Recommendations actioned') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-cyan">
        <div class="perf-stat-value">{{ $stats['autofixed'] }}</div>
        <div class="perf-stat-label">{{ translate('Auto-Fixed (30d)') }}</div>
        <div class="perf-stat-sub">{{ translate('Via Auto-Fix engine') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-blue">
        <div class="perf-stat-value">{{ $stats['dismissed'] }}</div>
        <div class="perf-stat-label">{{ translate('Dismissed') }}</div>
        <div class="perf-stat-sub">{{ translate('Marked as not-relevant') }}</div>
    </div>
</div>

<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-list"></i></span>{{ translate('Pending') }} <small class="text-muted ml-2">({{ count($pending) }})</small></h5>
            </div>
            <div class="perf-section-body p-0">
                @if($pending->isEmpty())
                    <div class="alert alert-light m-3 mb-0">
                        <i class="las la-check-circle text-success"></i>
                        {{ translate('No pending recommendations. Click "Run Analysis Now" to scan the site.') }}
                    </div>
                @else
                    @foreach($pending as $r)
                        @php
                            $sevColor = ['critical'=>'danger','high'=>'warning','medium'=>'primary','low'=>'secondary'][$r->severity] ?? 'secondary';
                        @endphp
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge badge-soft-{{ $sevColor }} mr-1">{{ strtoupper($r->severity) }}</span>
                                    <span class="badge badge-soft-info mr-1">{{ $r->category }}</span>
                                    @if($r->auto_fixable)
                                        <span class="badge badge-soft-success mr-1"><i class="las la-magic"></i> {{ translate('Auto-fixable') }}</span>
                                    @endif
                                    <small class="text-muted">{{ translate('Confidence') }}: {{ $r->confidence }}%</small>
                                </div>
                                <div>
                                    @if($r->auto_fixable)
                                        <form action="{{ route('performance_optimizer.ai.apply', $r->id) }}" method="POST" class="d-inline m-0">
                                            @csrf
                                            <button class="btn btn-soft-success btn-sm" type="submit"
                                                    onclick="return confirm('{{ translate('Apply this auto-fix? Most are reversible from history.') }}');">
                                                <i class="las la-bolt"></i> {{ translate('Apply') }}
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('performance_optimizer.ai.dismiss', $r->id) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <button class="btn btn-link p-1 text-muted btn-sm" type="submit" title="{{ translate('Dismiss') }}">
                                            <i class="las la-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <h6 class="mt-2 mb-1">{{ $r->title }}</h6>
                            <p class="small text-muted mb-0">{{ $r->body }}</p>
                            @if(!empty($r->evidence))
                                <details class="mt-1"><summary class="small text-muted">{{ translate('Evidence') }}</summary>
                                    <pre class="small mb-0" style="background:#f5f5f5;padding:6px;border-radius:4px;font-size:11px">{{ json_encode($r->evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-history"></i></span>{{ translate('Auto-Fix History') }} <small class="text-muted ml-2">({{ count($history) }})</small></h5>
            </div>
            <div class="perf-section-body p-0">
                @if($history->isEmpty())
                    <div class="alert alert-light m-3 mb-0"><i class="las la-info-circle"></i> {{ translate('No auto-fixes applied yet.') }}</div>
                @else
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ translate('When') }}</th>
                            <th>{{ translate('Action') }}</th>
                            <th>{{ translate('Title') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th style="width:120px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $h)
                            <tr>
                                <td><small>{{ optional($h->applied_at)->diffForHumans() }}</small></td>
                                <td><code style="font-size:11px">{{ $h->action }}</code></td>
                                <td><small>{{ $h->recommendation->title ?? '—' }}</small></td>
                                <td>
                                    @if($h->rolled_back_at)
                                        <span class="badge badge-soft-secondary">{{ translate('Rolled back') }}</span>
                                    @else
                                        <span class="badge badge-soft-success">{{ translate('Applied') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @unless($h->rolled_back_at)
                                        <form action="{{ route('performance_optimizer.ai.rollback', $h->id) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0 text-warning btn-sm"
                                                    onclick="return confirm('{{ translate('Roll back this fix?') }}');">
                                                <i class="las la-undo"></i> {{ translate('Rollback') }}
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    <div>
        <form action="{{ route('performance_optimizer.ai.save') }}" method="POST">
            @csrf
            <div class="perf-section">
                <div class="perf-section-header"><h5><span class="perf-section-icon"><i class="las la-cog"></i></span>{{ translate('Settings') }}</h5></div>
                <div class="perf-section-body">
                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                                <input type="hidden" name="perf_ai_recs_status" value="0">
                                <input type="checkbox" name="perf_ai_recs_status" value="1"
                                       @if(get_setting('perf_ai_recs_status', 1) == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                            <strong>{{ translate('Engine enabled') }}</strong>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <input type="hidden" name="perf_ai_recs_auto_apply" value="0">
                            <input type="checkbox" name="perf_ai_recs_auto_apply" value="1" class="mr-2"
                                   @if(get_setting('perf_ai_recs_auto_apply') == 1) checked @endif>
                            {{ translate('Auto-apply high-confidence fixes when cron runs') }}
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Auto-apply confidence threshold (%)') }}</label>
                        <input type="number" name="perf_ai_recs_auto_apply_threshold" class="form-control form-control-sm"
                               value="{{ get_setting('perf_ai_recs_auto_apply_threshold', 85) }}" min="50" max="100">
                        <small class="text-muted">{{ translate('Only auto-fixes at or above this confidence will run automatically.') }}</small>
                    </div>
                    <button class="btn btn-soft-primary btn-sm"><i class="las la-save"></i> {{ translate('Save') }}</button>
                </div>
            </div>
        </form>

        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('How it works') }}</div>
            <div class="perf-tips-body">
                <p>{{ translate('The engine runs 6 analyzers:') }}</p>
                <ul class="small pl-3 mb-2">
                    <li>{{ translate('Web Vitals — finds metrics over the "poor" P75 threshold') }}</li>
                    <li>{{ translate('Images — flags unconverted JPG/PNG batches') }}</li>
                    <li>{{ translate('Database — counts stale sessions / carts / notifications') }}</li>
                    <li>{{ translate('Cache — checks page cache enabled & populated') }}</li>
                    <li>{{ translate('Security — APP_DEBUG, force HTTPS, etc.') }}</li>
                    <li>{{ translate('CSS / JS — minify + defer settings') }}</li>
                </ul>
                <p class="mb-0"><strong>{{ translate('Cron:') }}</strong> <code>php artisan perf:ai-analysis --auto-apply</code></p>
            </div>
        </div>
    </div>
</div>
