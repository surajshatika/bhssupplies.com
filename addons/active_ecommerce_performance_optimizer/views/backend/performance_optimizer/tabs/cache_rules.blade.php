<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-filter"></i></span>{{ translate('Cache Rules') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_cache_rules_status')"
                           @if(get_setting('perf_cache_rules_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Database-driven include / bypass rules for the page cache. Matched in priority order (lowest first). The first match wins.') }}</p>
                <small class="text-muted">
                    <i class="las la-info-circle"></i>
                    {{ translate('A "bypass" match returns the cache header') }} <code>X-Performance-Cache: BYPASS</code>.
                    {{ translate('A "cache" match with TTL overrides the global TTL.') }}
                </small>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(76,175,80,.1);color:#4caf50"><i class="las la-plus"></i></span>{{ translate('Add Rule') }}</h5>
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.cache_rules.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label class="small font-weight-bold">{{ translate('Path Pattern') }} <span class="text-danger">*</span></label>
                            <input type="text" name="pattern" class="form-control form-control-sm" required
                                   placeholder="/ | product/* | admin/*">
                            <small class="text-muted">{{ translate('Use glob (*, ?). Use "/" for the homepage. Leading slash optional.') }}</small>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small font-weight-bold">{{ translate('Action') }}</label>
                            <select name="action" class="form-control form-control-sm">
                                <option value="cache" selected>{{ translate('Cache') }}</option>
                                <option value="bypass">{{ translate('Bypass') }}</option>
                                <option value="vary_device">{{ translate('Vary by device') }}</option>
                                <option value="vary_locale">{{ translate('Vary by locale') }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small font-weight-bold">{{ translate('TTL (min)') }}</label>
                            <input type="number" name="ttl_minutes" class="form-control form-control-sm" min="0" max="43200"
                                   placeholder="{{ get_setting('perf_page_cache_ttl_minutes', 720) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label class="small font-weight-bold">{{ translate('Priority') }}</label>
                            <input type="number" name="priority" class="form-control form-control-sm" value="50" min="0" max="9999">
                        </div>
                        <div class="form-group col-md-10">
                            <label class="small font-weight-bold">{{ translate('Note') }}</label>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="{{ translate('Optional context') }}">
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="mb-0">
                            <input type="checkbox" name="enabled" value="1" checked> {{ translate('Enabled') }}
                        </label>
                        <button class="btn btn-soft-primary btn-sm">
                            <i class="las la-plus"></i> {{ translate('Add Rule') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-list"></i></span>{{ translate('Active Rules') }} <small class="text-muted ml-2">({{ count($rules ?? []) }})</small></h5>
                <form action="{{ route('performance_optimizer.cache_rules.store') }}" method="POST" class="d-inline-block ml-2">
                    @csrf
                    <input type="hidden" name="seed_defaults" value="1">
                    <button class="btn btn-soft-secondary btn-sm" type="submit"
                            onclick="return confirm('{{ translate('Add 11 sensible default cache rules? Existing rules will not be modified.') }}');">
                        <i class="las la-magic"></i> {{ translate('Apply Defaults') }}
                    </button>
                </form>
            </div>
            <div class="perf-section-body p-0">
                @if(empty($rules) || count($rules) === 0)
                    <div class="alert alert-light m-3 mb-0">
                        <i class="las la-info-circle"></i>
                        {{ translate('No rules yet. Add one above, or click "Apply Defaults".') }}
                    </div>
                @else
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th style="width:50px">{{ translate('Prio') }}</th>
                            <th>{{ translate('Pattern') }}</th>
                            <th>{{ translate('Action') }}</th>
                            <th>{{ translate('TTL') }}</th>
                            <th>{{ translate('Note') }}</th>
                            <th style="width:60px">{{ translate('On') }}</th>
                            <th style="width:80px">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $rule)
                            <tr>
                                <td><span class="perf-pill">{{ $rule->priority }}</span></td>
                                <td><code>{{ $rule->pattern }}</code></td>
                                <td>
                                    @php $c = $rule->action === 'bypass' ? 'danger' : ($rule->action === 'cache' ? 'success' : 'info'); @endphp
                                    <span class="badge badge-soft-{{ $c }}">{{ $rule->action }}</span>
                                </td>
                                <td><small>{{ $rule->ttl_minutes ? $rule->ttl_minutes . ' m' : '—' }}</small></td>
                                <td><small class="text-muted">{{ $rule->note }}</small></td>
                                <td>
                                    <form action="{{ route('performance_optimizer.cache_rules.toggle', $rule->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="checkbox" onchange="this.form.submit()" {{ $rule->enabled ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('performance_optimizer.cache_rules.destroy', $rule->id) }}" method="POST" class="m-0 d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger"
                                                onclick="return confirm('{{ translate('Delete this rule?') }}');"
                                                title="{{ translate('Delete') }}">
                                            <i class="las la-trash-alt"></i>
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
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('How Cache Rules work') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('Order matters:') }}</strong> {{ translate('Rules are checked in priority order (ascending). First match wins.') }}</p>
                <p><strong>Bypass</strong> — {{ translate('Always serve fresh from PHP. Use for cart, checkout, account, admin.') }}</p>
                <p><strong>Cache</strong> — {{ translate('Allowed to be cached. Uses the rule\'s TTL if set, otherwise the global TTL.') }}</p>
                <p><strong>{{ translate('TTL hierarchy:') }}</strong> {{ translate('rule TTL > global TTL setting.') }}</p>
            </div>
        </div>

        <div class="perf-section" style="background:#fff3cd">
            <div class="perf-section-header" style="background:#ffe69c">
                <h5><i class="las la-exclamation-triangle"></i> {{ translate('Important') }}</h5>
            </div>
            <div class="perf-section-body" style="font-size:13px">
                <p>{{ translate('Always bypass') }} <code>admin/*</code> {{ translate('— users will see stale admin pages otherwise.') }}</p>
                <p>{{ translate('Always bypass') }} <code>cart</code>{{ translate(',') }} <code>checkout/*</code>{{ translate(',') }} <code>my-account/*</code> {{ translate('— these have per-user data.') }}</p>
                <p>{{ translate('Page cache must be enabled in the Caching tab for these rules to take effect.') }}</p>
            </div>
        </div>
    </div>
</div>
