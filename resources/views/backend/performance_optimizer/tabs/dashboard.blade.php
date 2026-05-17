<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-bolt"></i></span>{{ translate('Quick Actions') }}</h5>
            </div>
            <div class="perf-section-body">
                <div class="perf-action-grid">
                    <form action="{{ route('performance_optimizer.images.webp') }}" method="POST" class="perf-action">
                        @csrf<input type="hidden" name="limit" value="100">
                        <button class="btn btn-soft-primary" type="submit"><i class="las la-image"></i> {{ translate('Convert 100 to WebP') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.cssjs.minify_css') }}" method="POST" class="perf-action">
                        @csrf
                        <button class="btn btn-soft-success" type="submit"><i class="las la-file-code"></i> {{ translate('Minify all CSS') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.cssjs.minify_js') }}" method="POST" class="perf-action">
                        @csrf
                        <button class="btn btn-soft-success" type="submit"><i class="las la-code"></i> {{ translate('Minify all JS') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.cache.clear') }}" method="POST" class="perf-action">
                        @csrf
                        <button class="btn btn-soft-warning" type="submit"><i class="las la-broom"></i> {{ translate('Clear page cache') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.cache.warm') }}" method="POST" class="perf-action">
                        @csrf
                        <button class="btn btn-soft-info" type="submit"><i class="las la-fire"></i> {{ translate('Warm cache') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.cache.laravel_clear') }}" method="POST" class="perf-action">
                        @csrf
                        <button class="btn btn-soft-danger" type="submit"><i class="las la-redo"></i> {{ translate('Clear Laravel cache') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-clipboard-list"></i></span>{{ translate('Recent Activity') }}</h5>
                <a href="{{ route('performance_optimizer.logs') }}" class="btn btn-link btn-sm p-0">{{ translate('View all') }} <i class="las la-arrow-right"></i></a>
            </div>
            <div class="perf-section-body p-0">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Action') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('When') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse(($recent_logs ?? []) as $log)
                        <tr>
                            <td><span class="perf-pill">{{ $log->type }}</span></td>
                            <td>{{ $log->action }}</td>
                            <td>
                                @if($log->status === 'success')
                                    <span class="perf-sev perf-sev-low">{{ translate('success') }}</span>
                                @else
                                    <span class="perf-sev perf-sev-critical">{{ translate('failed') }}</span>
                                @endif
                            </td>
                            <td><small>{{ $log->created_at?->diffForHumans() }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ translate('No activity yet. Run a few actions to see them here.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Dashboard Tips') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('Start with WebP conversion') }}</strong> - {{ translate('typically saves 25-35% on image weight with zero visible quality loss.') }}</p>
                <p><strong>{{ translate('Enable Page Cache') }}</strong> - {{ translate('static HTML delivery is much faster than re-rendering PHP for every visit.') }}</p>
                <p><strong>{{ translate('Clean DB monthly') }}</strong> - {{ translate('expired sessions, old failed jobs and read notifications can balloon the DB.') }}</p>
                <p>{{ translate('Use') }} <code>perf:auto-clean</code> {{ translate('cron command for automated nightly cleanup.') }}</p>
            </div>
        </div>
    </div>
</div>
