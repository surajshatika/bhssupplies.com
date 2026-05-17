<div class="perf-log-layout">
    {{-- Left: Log sources --}}
    <div class="perf-log-sources">
        <div class="perf-card-header" style="border-radius:0">
            <h5 style="font-size:13px"><i class="las la-folder-open"></i> {{ translate('Log Sources') }}</h5>
            <a href="{{ route('performance_optimizer.logs') }}" class="btn btn-light btn-sm p-1" title="{{ translate('Refresh') }}"><i class="las la-redo"></i></a>
        </div>
        @foreach($log_sources as $category => $sources)
            <div class="perf-log-cat">{{ translate($category) }}</div>
            @foreach($sources as $s)
                @php $isActive = $active_source && $active_source['key'] === $s['key']; @endphp
                <a href="{{ route('performance_optimizer.logs', ['src' => $s['key']]) }}"
                   class="perf-log-source {{ $isActive ? 'active' : '' }} {{ $s['available'] ? '' : 'perf-log-source-disabled' }}"
                   @if(!$s['available']) onclick="return false" title="{{ translate('Not available on this server') }}" @endif>
                    <i class="perf-log-source-icon las {{ $s['key'] === 'optimizer' ? 'la-list' : ($s['key'] === 'laravel' ? 'la-bug' : ($s['available'] ? 'la-file-alt' : 'la-times-circle')) }}"></i>
                    <div class="perf-log-source-info">
                        <div class="perf-log-source-name">{{ $s['name'] }}</div>
                        <div class="perf-log-source-size">{{ $s['available'] ? $s['size'] : translate('Not available') }}</div>
                    </div>
                </a>
            @endforeach
        @endforeach
    </div>

    {{-- Right: Log content viewer --}}
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5>
                    <span class="perf-section-icon"><i class="las la-file-alt"></i></span>
                    @if($active_source)
                        {{ $active_source['name'] }}
                    @else
                        {{ translate('Select a log source') }}
                    @endif
                </h5>
                <div>
                    @if($active_source && ($active_source['key'] ?? null) === 'laravel')
                        <form action="{{ route('performance_optimizer.logs.clear_error') }}" method="POST" class="d-inline"
                              onsubmit="return confirm('{{ translate('Truncate laravel.log?') }}')">
                            @csrf
                            <button class="btn btn-soft-danger btn-sm"><i class="las la-trash"></i> {{ translate('Clear') }}</button>
                        </form>
                    @endif
                    @if($active_source && ($active_source['key'] ?? null) === 'optimizer')
                        <form action="{{ route('performance_optimizer.logs.clear_optimization') }}" method="POST" class="d-inline"
                              onsubmit="return confirm('{{ translate('Clear optimization activity log?') }}')">
                            @csrf
                            <button class="btn btn-soft-danger btn-sm"><i class="las la-trash"></i> {{ translate('Clear') }}</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="perf-section-body">
                @if($active_source && ($active_source['key'] ?? null) === 'optimizer')
                    {{-- Optimization Activity Log uses DB table --}}
                    <table class="perf-table">
                        <thead><tr><th>#</th><th>{{ translate('Type') }}</th><th>{{ translate('Action') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Target') }}</th><th>{{ translate('When') }}</th></tr></thead>
                        <tbody>
                        @forelse($optimization_logs as $log)
                            <tr>
                                <td>{{ $log->id }}</td>
                                <td><span class="perf-pill">{{ $log->type }}</span></td>
                                <td>{{ $log->action }}</td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="perf-sev perf-sev-low">{{ translate('success') }}</span>
                                    @else
                                        <span class="perf-sev perf-sev-critical">{{ translate('failed') }}</span>
                                    @endif
                                </td>
                                <td><small>{{ \Illuminate\Support\Str::limit($log->target ?? '-', 60) }}</small></td>
                                <td><small>{{ $log->created_at?->diffForHumans() }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">{{ translate('No activity logged yet.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="mt-3">{{ $optimization_logs->links() }}</div>
                @elseif(!empty($content_lines))
                    <small class="text-muted d-block mb-2">
                        <strong>{{ translate('Path') }}:</strong> <code>{{ $active_source['path'] }}</code> · <strong>{{ translate('Size') }}:</strong> {{ $content_size }} · <strong>{{ translate('Showing') }}:</strong> {{ count($content_lines) }} {{ translate('lines') }}
                    </small>
                    <pre class="perf-log-pre">{{ implode("\n", $content_lines) }}</pre>
                @else
                    <div class="text-center py-5 text-muted">
                        <div style="font-size:48px"><i class="las la-mouse-pointer"></i></div>
                        <h6 class="mt-2">{{ translate('Select a log source from the left') }}</h6>
                        <small>{{ translate('Available sources are highlighted; greyed sources are not present on this server.') }}</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
