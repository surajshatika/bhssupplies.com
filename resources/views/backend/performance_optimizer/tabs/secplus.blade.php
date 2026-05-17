<div class="perf-section">
    <div class="perf-section-header"><h5><i class="las la-user-shield"></i> {{ translate('Security+') }}</h5></div>
    <div class="perf-section-body">
        <p class="mb-0">{{ translate('Defends against aggressive crawlers, prevents image hotlinking, and surfaces slow database queries with index suggestions.') }}</p>
        <small class="text-muted">
            <i class="las la-info-circle"></i>
            {{ translate('Bot Protection and Hotlink Protection require their middlewares to be registered in') }} <code>app/Http/Kernel.php</code>.
            {{ translate('See INSTALL_HOOKS.md.') }}
        </small>
    </div>
</div>

<form action="{{ route('performance_optimizer.secplus.save') }}" method="POST">
    @csrf
    <div class="row">
        {{-- Bot Protection ───────────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="perf-section">
                <div class="perf-section-header">
                    <h5>
                        <span class="perf-section-icon" style="background:rgba(220,53,69,.1);color:#dc3545"><i class="las la-robot"></i></span>
                        {{ translate('Bot Protection') }}
                    </h5>
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="hidden" name="perf_bot_protect_status" value="0">
                        <input type="checkbox" name="perf_bot_protect_status" value="1"
                               @if(get_setting('perf_bot_protect_status') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="perf-section-body">
                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Rate limit (per minute, per IP+UA)') }}</label>
                        <input type="number" name="perf_bot_rate_limit_per_min" class="form-control form-control-sm"
                               value="{{ get_setting('perf_bot_rate_limit_per_min', 60) }}" min="0" max="10000">
                        <small class="text-muted">{{ translate('0 disables rate limiting. Exceeding returns HTTP 429.') }}</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">{{ translate('Hard block list (one UA substring per line)') }}</label>
                        <textarea name="perf_bot_block_list" rows="6" class="form-control form-control-sm"
                                  style="font-family:monospace;font-size:12px">{{ get_setting('perf_bot_block_list') }}</textarea>
                        <small class="text-muted">{{ translate('Case-insensitive substring match. Returns HTTP 403.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hotlink Protection ────────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="perf-section">
                <div class="perf-section-header">
                    <h5>
                        <span class="perf-section-icon" style="background:rgba(255,193,7,.1);color:#ffc107"><i class="las la-link"></i></span>
                        {{ translate('Hotlink Protection') }}
                    </h5>
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="hidden" name="perf_hotlink_protect_status" value="0">
                        <input type="checkbox" name="perf_hotlink_protect_status" value="1"
                               @if(get_setting('perf_hotlink_protect_status') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="perf-section-body">
                    <p class="small text-muted">{{ translate('Returns 403 on') }} <code>/uploads/*</code> {{ translate('requests whose Referer is from a foreign domain.') }}</p>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">{{ translate('Additional allowed domains') }}</label>
                        <textarea name="perf_hotlink_allowed_domains" rows="8" class="form-control form-control-sm"
                                  style="font-family:monospace;font-size:12px"
                                  placeholder="cdn.example.com&#10;facebook.com">{{ get_setting('perf_hotlink_allowed_domains') }}</textarea>
                        <small class="text-muted">{{ translate('Your app host is always allowed. One domain per line. Subdomains match automatically.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Slow Query Analyzer ──────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="perf-section">
                <div class="perf-section-header">
                    <h5>
                        <span class="perf-section-icon" style="background:rgba(102,16,242,.1);color:#6610f2"><i class="las la-database"></i></span>
                        {{ translate('Slow Query Analyzer') }}
                    </h5>
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="hidden" name="perf_slow_query_status" value="0">
                        <input type="checkbox" name="perf_slow_query_status" value="1"
                               @if(get_setting('perf_slow_query_status') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="perf-section-body">
                    <p class="small text-muted">{{ translate('Captures slow queries via Laravel\'s DB::listen hook and groups by normalized statement.') }}</p>
                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Threshold (ms)') }}</label>
                        <input type="number" name="perf_slow_query_threshold_ms" class="form-control form-control-sm"
                               value="{{ get_setting('perf_slow_query_threshold_ms', 500) }}" min="50" max="60000">
                        <small class="text-muted">{{ translate('Queries slower than this are recorded.') }}</small>
                    </div>
                    <p class="small mb-0"><strong>{{ $slow_count }}</strong> {{ translate('slow queries currently tracked.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-soft-primary"><i class="las la-save"></i> {{ translate('Save Security+ settings') }}</button>
    </div>
</form>

{{-- Slow Queries table ────────────────────────────────────────── --}}
<div class="perf-section">
    <div class="perf-section-header">
        <h5><span class="perf-section-icon"><i class="las la-list"></i></span>{{ translate('Slow Queries') }} <small class="text-muted ml-2">({{ $slow_count }})</small></h5>
        <form action="{{ route('performance_optimizer.secplus.slowq.scan') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-soft-secondary btn-sm" type="submit">
                <i class="las la-sync"></i> {{ translate('Run Sample Scan') }}
            </button>
        </form>
    </div>
    <div class="perf-section-body p-0">
        @if($slow_queries->isEmpty())
            <div class="alert alert-light m-3 mb-0">
                <i class="las la-info-circle"></i>
                {{ translate('No slow queries captured yet. Enable the analyzer above and browse the site for a few minutes to populate this list.') }}
            </div>
        @else
        <table class="perf-table">
            <thead>
                <tr>
                    <th>{{ translate('Query') }}</th>
                    <th style="width:90px">{{ translate('Avg ms') }}</th>
                    <th style="width:90px">{{ translate('Max ms') }}</th>
                    <th style="width:60px">{{ translate('Hits') }}</th>
                    <th>{{ translate('Suggested fix') }}</th>
                    <th style="width:140px">{{ translate('Last seen') }}</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($slow_queries as $q)
                    <tr>
                        <td><code style="font-size:11px">{{ \Illuminate\Support\Str::limit($q->query_text, 200) }}</code></td>
                        <td><span class="perf-pill" style="background:#fff3cd;color:#856404">{{ $q->avg_time_ms }}</span></td>
                        <td><small>{{ $q->max_time_ms }}</small></td>
                        <td><small>{{ $q->occurrences }}</small></td>
                        <td><small class="text-muted">{{ $q->suggested_index ?? '—' }}</small></td>
                        <td><small>{{ optional($q->last_seen)->diffForHumans() }}</small></td>
                        <td>
                            <form action="{{ route('performance_optimizer.secplus.slowq.dismiss', $q->id) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-danger" title="{{ translate('Dismiss') }}">
                                    <i class="las la-times"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
