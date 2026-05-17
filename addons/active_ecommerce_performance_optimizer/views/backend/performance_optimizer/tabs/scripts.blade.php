<div class="perf-layout-2col">
    <div>
        {{-- Master toggle ------------------------------------------------ --}}
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-tasks"></i></span>{{ translate('Script Manager') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_script_manager_status')"
                           @if(get_setting('perf_script_manager_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Per-page allow / deny / defer / async / delay matrix for') }} <code>&lt;script&gt;</code> {{ translate('tags. Rules match against the script\'s') }} <code>src</code> {{ translate('or inline content via case-insensitive substring search. Lowest priority number wins.') }}</p>
                <small class="text-muted">
                    <i class="las la-info-circle"></i>
                    {{ translate('Rules only apply on frontend pages while the master switch is ON and the addon master switch is ON.') }}
                </small>
            </div>
        </div>

        {{-- Add Rule form ------------------------------------------------- --}}
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(76,175,80,.1);color:#4caf50"><i class="las la-plus"></i></span>{{ translate('Add Rule') }}</h5>
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.scripts.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label class="small font-weight-bold">{{ translate('Script Pattern') }} <span class="text-danger">*</span></label>
                            <input type="text" name="script_pattern" class="form-control form-control-sm" required
                                   placeholder="firebase, recaptcha, slick, aiz-uploader, googletagmanager">
                            <small class="text-muted">{{ translate('Substring matched (case-insensitive) against script src or inline content.') }}</small>
                        </div>
                        <div class="form-group col-md-5">
                            <label class="small font-weight-bold">{{ translate('Route Pattern') }}</label>
                            <input type="text" name="route_pattern" class="form-control form-control-sm" value="*"
                                   placeholder="* | checkout* | product/* | cart">
                            <small class="text-muted">{{ translate('Glob match against the URL path (no leading /).') }}</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small font-weight-bold">{{ translate('Action') }}</label>
                            <select name="action" class="form-control form-control-sm">
                                <option value="allow">{{ translate('Allow (no change)') }}</option>
                                <option value="deny">{{ translate('Deny (remove)') }}</option>
                                <option value="defer" selected>{{ translate('Defer') }}</option>
                                <option value="async">{{ translate('Async') }}</option>
                                <option value="delay">{{ translate('Delay (until interaction)') }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small font-weight-bold">{{ translate('Device') }}</label>
                            <select name="device_filter" class="form-control form-control-sm">
                                <option value="any" selected>{{ translate('Any') }}</option>
                                <option value="mobile">{{ translate('Mobile only') }}</option>
                                <option value="desktop">{{ translate('Desktop only') }}</option>
                                <option value="tablet">{{ translate('Tablet only') }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small font-weight-bold">{{ translate('Priority') }}</label>
                            <input type="number" name="priority" class="form-control form-control-sm" value="50" min="0" max="9999">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small font-weight-bold">{{ translate('Note') }}</label>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="{{ translate('Optional reason / context') }}">
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

        {{-- Existing rules table ---------------------------------------- --}}
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-list"></i></span>{{ translate('Active Rules') }} <small class="text-muted ml-2">({{ count($rules ?? []) }})</small></h5>
                <form action="{{ route('performance_optimizer.scripts.store') }}" method="POST" class="d-inline-block ml-2">
                    @csrf
                    <input type="hidden" name="seed_defaults" value="1">
                    <button class="btn btn-soft-secondary btn-sm" type="submit"
                            onclick="return confirm('{{ translate('Add 10 sensible default rules for Active eCommerce? Existing rules are not changed.') }}');">
                        <i class="las la-magic"></i> {{ translate('Apply Defaults') }}
                    </button>
                </form>
            </div>
            <div class="perf-section-body p-0">
                @if(empty($rules) || count($rules) === 0)
                    <div class="alert alert-light m-3 mb-0">
                        <i class="las la-info-circle"></i>
                        {{ translate('No rules yet. Add one above, or click "Apply Defaults" for sensible Active eCommerce presets.') }}
                    </div>
                @else
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th style="width:50px">{{ translate('Prio') }}</th>
                            <th>{{ translate('Script Pattern') }}</th>
                            <th>{{ translate('Route') }}</th>
                            <th>{{ translate('Device') }}</th>
                            <th>{{ translate('Action') }}</th>
                            <th>{{ translate('Note') }}</th>
                            <th style="width:60px">{{ translate('On') }}</th>
                            <th style="width:80px">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $rule)
                            <tr id="rule-{{ $rule->id }}">
                                <td><span class="perf-pill">{{ $rule->priority }}</span></td>
                                <td><code>{{ $rule->script_pattern }}</code></td>
                                <td><small><code>{{ $rule->route_pattern }}</code></small></td>
                                <td><small>{{ $rule->device_filter }}</small></td>
                                <td>
                                    @php
                                        $actColors = ['allow'=>'secondary','deny'=>'danger','defer'=>'primary','async'=>'info','delay'=>'warning'];
                                        $c = $actColors[$rule->action] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-soft-{{ $c }}">{{ $rule->action }}</span>
                                </td>
                                <td><small class="text-muted">{{ $rule->note }}</small></td>
                                <td>
                                    <form action="{{ route('performance_optimizer.scripts.toggle', $rule->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="checkbox" onchange="this.form.submit()" {{ $rule->enabled ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('performance_optimizer.scripts.destroy', $rule->id) }}" method="POST" class="m-0 d-inline">
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

    {{-- Right column: tips ---------------------------------------------- --}}
    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('How Script Manager works') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('Match order:') }}</strong> {{ translate('Rules are evaluated lowest-priority-first. The first matching rule wins per script tag.') }}</p>
                <p><strong>Allow</strong> — {{ translate('Whitelist a script before it gets caught by a broader deny rule.') }}</p>
                <p><strong>Deny</strong> — {{ translate('Strip the tag from HTML entirely. Use for analytics on auth pages.') }}</p>
                <p><strong>Defer / Async</strong> — {{ translate('Adds the attribute if not already present.') }}</p>
                <p><strong>Delay</strong> — {{ translate('Replaces') }} <code>type</code> {{ translate('with') }} <code>text/perf-delay</code>. {{ translate('Requires the delay-JS bootstrap (Caching tab → "Delay JS until interaction").') }}</p>
            </div>
        </div>

        <div class="perf-section" style="background:#fff3cd">
            <div class="perf-section-header" style="background:#ffe69c">
                <h5><i class="las la-exclamation-triangle"></i> {{ translate('Warnings') }}</h5>
            </div>
            <div class="perf-section-body" style="font-size:13px">
                <p>{{ translate('Denying') }} <code>jquery</code> {{ translate('will break the site. Always allow it.') }}</p>
                <p>{{ translate('Denying') }} <code>recaptcha</code> {{ translate('site-wide breaks checkout/login. Use route-scoped rules.') }}</p>
                <p>{{ translate('Test thoroughly after each rule — some Active eCommerce themes inline critical JS that doesn\'t have a unique pattern.') }}</p>
            </div>
        </div>
    </div>
</div>
