@php
    $sc = $audit['score'] ?? 0;
    $grade = $sc >= 90 ? 'A' : ($sc >= 75 ? 'B' : ($sc >= 60 ? 'C' : ($sc >= 40 ? 'D' : 'F')));
    $gradeColor = $sc >= 80 ? 'var(--perf-green)' : ($sc >= 50 ? 'var(--perf-yellow)' : 'var(--perf-red)');
    $critical = collect($audit['checks'] ?? [])->where('pass', false)->count();
    $high     = $critical; // we don't have severity in service, so split visually
@endphp

<div class="perf-top-stats">
    <div class="perf-stat-card" style="border-top-color: {{ $gradeColor }}">
        <div class="perf-stat-value" style="color: {{ $gradeColor }}; font-size:36px">{{ $sc }}%</div>
        <div class="perf-stat-label">{{ translate('Security Score') }}</div>
        <div class="perf-stat-sub"><span class="perf-grade-{{ $grade }}" style="font-size:24px;font-weight:800">{{ $grade }}</span></div>
    </div>
    <div class="perf-stat-card perf-stat-green">
        <div class="perf-stat-value">{{ $audit['passed'] ?? 0 }}</div>
        <div class="perf-stat-label">{{ translate('Checks Passed') }}</div>
        <div class="perf-stat-sub">{{ translate('of') }} {{ $audit['total'] ?? 0 }} {{ translate('total') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-red">
        <div class="perf-stat-value">{{ $critical }}</div>
        <div class="perf-stat-label">{{ translate('Issues Found') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-cyan">
        <div class="perf-stat-value"><i class="las la-shield-alt" style="font-size:36px"></i></div>
        <div class="perf-stat-label">{{ translate('Pro Audit') }}</div>
        <div class="perf-stat-sub">{{ translate('Active') }}</div>
    </div>
</div>

<div class="perf-section">
    <div class="perf-section-header">
        <h5><span class="perf-section-icon" style="background:rgba(220,53,69,.1);color:var(--perf-red)"><i class="las la-shield-alt"></i></span>{{ translate('Security Audit') }}</h5>
        <div>
            <small class="text-muted mr-2">{{ translate('Last scan') }}: {{ now()->format('Y-m-d H:i:s') }}</small>
            <form action="{{ route('performance_optimizer.security.run') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-primary btn-sm"><i class="las la-play"></i> {{ translate('Run Audit') }}</button>
            </form>
        </div>
    </div>
    <div class="perf-section-body p-0">
        <table class="perf-table">
            <thead>
                <tr>
                    <th>{{ translate('Check') }}</th>
                    <th style="width:90px">{{ translate('Status') }}</th>
                    <th style="width:90px">{{ translate('Severity') }}</th>
                    <th>{{ translate('Details') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($audit['checks'] ?? []) as $i => $c)
                    @php
                        // Heuristic severity by check name keywords
                        $title = $c['title'];
                        $sev = 'medium';
                        if (str_contains($title, 'DEBUG') || str_contains($title, '.env') || str_contains($title, 'admin') || str_contains($title, 'password')) $sev = 'critical';
                        elseif (str_contains($title, 'HTTPS') || str_contains($title, 'PHP version') || str_contains($title, 'storage')) $sev = 'high';
                        elseif (str_contains($title, 'XML-RPC') || str_contains($title, 'expose_php') || str_contains($title, 'Telescope')) $sev = 'low';
                    @endphp
                    <tr style="{{ $c['pass'] ? '' : 'background:#fff8e1' }}">
                        <td><strong>{{ $c['title'] }}</strong></td>
                        <td>
                            @if($c['pass'])
                                <span class="perf-sev" style="background:#d4edda;color:#155724"><i class="las la-check"></i> {{ translate('Pass') }}</span>
                            @else
                                <span class="perf-sev" style="background:#f8d7da;color:#721c24"><i class="las la-times"></i> {{ translate('Fail') }}</span>
                            @endif
                        </td>
                        <td><span class="perf-sev perf-sev-{{ $sev }}">{{ $sev }}</span></td>
                        <td>
                            @if(!$c['pass'])
                                <small>{{ $c['fix'] }}</small>
                            @else
                                <small class="text-muted">{{ translate('OK') }}.</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<form action="{{ route('performance_optimizer.settings.update') }}" method="POST">
    @csrf
    <div class="perf-layout-2col">
        <div>
            <div class="perf-section">
                <div class="perf-section-header"><h5><span class="perf-section-icon"><i class="las la-toggle-on"></i></span>{{ translate('Security Hardening Toggles') }}</h5></div>
                <div class="perf-section-body">
                    <div class="perf-toggle-row">
                        <div><strong>{{ translate('Block XML-RPC requests') }}</strong><small class="d-block text-muted">{{ translate('Returns 403 on /xmlrpc.php') }}</small></div>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="perf_security_block_xmlrpc" value="1" @if(get_setting('perf_security_block_xmlrpc') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="perf-toggle-row">
                        <div><strong>{{ translate('Hide PHP version') }}</strong><small class="d-block text-muted">{{ translate('Removes X-Powered-By header from responses') }}</small></div>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="perf_security_hide_php_version" value="1" @if(get_setting('perf_security_hide_php_version') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="perf-toggle-row">
                        <div><strong>{{ translate('Force HTTPS redirect') }}</strong><small class="d-block text-muted">{{ translate('301 redirect HTTP → HTTPS (skips localhost)') }}</small></div>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="perf_security_force_https" value="1" @if(get_setting('perf_security_force_https') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="perf-toggle-row">
                        <div><strong>{{ translate('Require strong passwords (admins)') }}</strong><small class="d-block text-muted">{{ translate('Future logins by admins must use 12+ char passwords') }}</small></div>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" name="perf_security_strong_pwd_required" value="1" @if(get_setting('perf_security_strong_pwd_required') == 1) checked @endif>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="mt-3 text-right">
                        <button class="btn btn-primary btn-sm">{{ translate('Save Security Settings') }}</button>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="perf-tips">
                <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Security Tips') }}</div>
                <div class="perf-tips-body">
                    <p><strong>{{ translate('APP_DEBUG=false') }}</strong> {{ translate('in production. Stack traces expose your code and env values.') }}</p>
                    <p><strong>{{ translate('Use HTTPS') }}</strong> {{ translate('always — checkout/login over HTTP is exploitable in seconds.') }}</p>
                    <p><strong>{{ translate('Rotate admin passwords') }}</strong> {{ translate('every quarter and never reuse them across sites.') }}</p>
                    <p>{{ translate('Audit logs at') }} <code>storage/logs/laravel.log</code> {{ translate('— check Error Logs tab.') }}</p>
                </div>
            </div>
        </div>
    </div>
</form>
