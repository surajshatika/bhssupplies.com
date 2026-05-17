{{-- 4 system stat cards --}}
<div class="perf-top-stats">
    <div class="perf-stat-card perf-stat-blue">
        <div class="perf-stat-value">{{ $monitor['memory_used'] }}</div>
        <div class="perf-stat-label">{{ translate('Memory Usage') }}</div>
        <div class="perf-stat-sub">{{ translate('of') }} {{ $monitor['memory_limit'] }}
            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-primary" style="width:{{ $monitor['memory_used_percent'] }}%"></div></div>
        </div>
    </div>
    <div class="perf-stat-card perf-stat-green">
        <div class="perf-stat-value">{{ $monitor['disk_free'] }}</div>
        <div class="perf-stat-label">{{ translate('Disk Free') }}</div>
        <div class="perf-stat-sub">{{ translate('of') }} {{ $monitor['disk_total'] }}
            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-{{ $monitor['disk_used_percent'] > 85 ? 'danger' : ($monitor['disk_used_percent'] > 70 ? 'warning' : 'success') }}" style="width:{{ $monitor['disk_used_percent'] }}%"></div></div>
        </div>
    </div>
    <div class="perf-stat-card perf-stat-yellow">
        <div class="perf-stat-value">{{ $monitor['cpu_cores'] }}</div>
        <div class="perf-stat-label">{{ translate('CPU Cores') }}</div>
        <div class="perf-stat-sub">{{ translate('Load avg') }}: {{ $monitor['load_avg'] }}</div>
    </div>
    <div class="perf-stat-card perf-stat-purple">
        <div class="perf-stat-value">@if($monitor['opcache']['enabled']){{ $monitor['opcache']['hit_rate'] }}%@else—@endif</div>
        <div class="perf-stat-label">{{ translate('OPcache Hit Rate') }}</div>
        <div class="perf-stat-sub">@if($monitor['opcache']['enabled']){{ $monitor['opcache']['cached_scripts'] }} {{ translate('scripts cached') }}@else{{ translate('OPcache disabled') }}@endif</div>
    </div>
</div>

<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(220,53,69,.1);color:var(--perf-red)"><i class="las la-heartbeat"></i></span>{{ translate('Environment Health') }}</h5>
                <a href="{{ route('performance_optimizer.monitor') }}" class="btn btn-light btn-sm"><i class="las la-redo"></i> {{ translate('Refresh') }}</a>
            </div>
            <div class="perf-section-body p-0">
                <div class="perf-health-list">
                    @foreach($health as $h)
                        <div class="perf-health-row" style="padding-left:18px;padding-right:18px">
                            <div>
                                <span class="perf-health-dot perf-health-{{ $h['pass'] ? 'pass' : 'fail' }}"></span>
                                <strong>{{ $h['label'] }}</strong>
                            </div>
                            <small class="text-muted">{{ $h['detail'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-puzzle-piece"></i></span>{{ translate('PHP Extensions') }}</h5>
            </div>
            <div class="perf-section-body">
                @foreach($monitor['extensions'] as $name => $loaded)
                    <span class="perf-pill" style="background:{{ $loaded ? '#d4edda' : '#f1f3f5' }};color:{{ $loaded ? '#155724' : '#6c757d' }};margin:2px">
                        {{ $loaded ? '✓' : '✗' }} {{ $name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div>
        {{-- Server/PHP/Laravel/Database info nav-pills --}}
        <div class="perf-section">
            <div class="perf-section-header">
                <ul class="nav nav-pills" id="perf-monitor-tabs" style="margin:-12px -18px;padding:8px 18px">
                    <li class="nav-item"><a class="nav-link active py-1 px-3" data-toggle="tab" href="#perf-info-server">{{ translate('Server') }}</a></li>
                    <li class="nav-item"><a class="nav-link py-1 px-3"        data-toggle="tab" href="#perf-info-php">{{ translate('PHP') }}</a></li>
                    <li class="nav-item"><a class="nav-link py-1 px-3"        data-toggle="tab" href="#perf-info-laravel">{{ translate('Laravel') }}</a></li>
                    <li class="nav-item"><a class="nav-link py-1 px-3"        data-toggle="tab" href="#perf-info-db">{{ translate('Database') }}</a></li>
                </ul>
            </div>
            <div class="perf-section-body p-0">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="perf-info-server">
                        <table class="perf-table">
                            <tbody>
                                <tr><td>software</td><td><code>{{ $monitor['server_software'] }}</code></td></tr>
                                <tr><td>os</td><td><code>{{ $monitor['os'] }}</code></td></tr>
                                <tr><td>hostname</td><td><code>{{ $monitor['hostname'] }}</code></td></tr>
                                <tr><td>document_root</td><td><code>{{ \Illuminate\Support\Str::limit($monitor['document_root'], 50) }}</code></td></tr>
                                <tr><td>server_admin</td><td><code>{{ $monitor['server_admin'] }}</code></td></tr>
                                <tr><td>ip</td><td><code>{{ $monitor['server_ip'] }}</code></td></tr>
                                <tr><td>port</td><td><code>{{ $monitor['server_port'] }}</code></td></tr>
                                <tr><td>protocol</td><td><code>{{ $monitor['server_protocol'] }}</code></td></tr>
                                <tr><td>tz</td><td><code>{{ $monitor['timezone'] }}</code></td></tr>
                                <tr><td>time</td><td><code>{{ $monitor['now'] }}</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="perf-info-php">
                        <table class="perf-table">
                            <tbody>
                                <tr><td>version</td><td><code>{{ $monitor['php_version'] }}</code></td></tr>
                                <tr><td>SAPI</td><td><code>{{ $monitor['sapi'] }}</code></td></tr>
                                <tr><td>memory_limit</td><td><code>{{ $monitor['memory_limit'] }}</code></td></tr>
                                <tr><td>memory_peak</td><td><code>{{ $monitor['memory_peak'] }}</code></td></tr>
                                <tr><td>max_execution_time</td><td><code>{{ $monitor['max_execution'] }}s</code></td></tr>
                                <tr><td>upload_max_filesize</td><td><code>{{ $monitor['upload_max'] }}</code></td></tr>
                                <tr><td>post_max_size</td><td><code>{{ $monitor['post_max'] }}</code></td></tr>
                                @if($monitor['opcache']['enabled'])
                                    <tr><td>opcache.hits</td><td><code>{{ number_format($monitor['opcache']['hits']) }}</code></td></tr>
                                    <tr><td>opcache.misses</td><td><code>{{ number_format($monitor['opcache']['misses']) }}</code></td></tr>
                                    <tr><td>opcache.memory_used</td><td><code>{{ $monitor['opcache']['memory_used'] }}</code></td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="perf-info-laravel">
                        <table class="perf-table">
                            <tbody>
                                <tr><td>version</td><td><code>{{ $monitor['laravel_version'] }}</code></td></tr>
                                <tr><td>environment</td><td><code class="text-{{ $monitor['env'] === 'production' ? 'success' : 'warning' }}">{{ $monitor['env'] }}</code></td></tr>
                                <tr><td>debug</td><td><code class="text-{{ $monitor['debug'] ? 'danger' : 'success' }}">{{ $monitor['debug'] ? 'ON' : 'OFF' }}</code></td></tr>
                                <tr><td>app.url</td><td><code>{{ $monitor['app_url'] }}</code></td></tr>
                                <tr><td>timezone</td><td><code>{{ $monitor['timezone'] }}</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="perf-info-db">
                        <table class="perf-table">
                            <tbody>
                                <tr><td>MySQL version</td><td><code>{{ $monitor['mysql']['version'] }}</code></td></tr>
                                <tr><td>database</td><td><code>{{ $monitor['mysql']['db'] }}</code></td></tr>
                                <tr><td>data size</td><td><code>{{ $monitor['mysql']['size'] }}</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('System Tips') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('OPcache hit rate < 95%') }}</strong> {{ translate('means PHP is recompiling files often. Bump') }} <code>opcache.memory_consumption</code>.</p>
                <p>{{ translate('Keep') }} <strong>{{ translate('Disk Used < 85%') }}</strong> {{ translate('for safe operation.') }}</p>
                <p><strong>{{ translate('Memory Limit') }}</strong> {{ translate('of 256M is recommended; 128M may cause Excel imports/exports to fail.') }}</p>
            </div>
        </div>
    </div>
</div>
