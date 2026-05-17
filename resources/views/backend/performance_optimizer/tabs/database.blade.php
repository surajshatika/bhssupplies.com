{{-- Inner stat strip (4 small DB cards) --}}
<div class="perf-top-stats">
    <div class="perf-stat-card perf-stat-red">
        <div class="perf-stat-value">{{ number_format($stats['old_carts'] ?? 0) }}</div>
        <div class="perf-stat-label">{{ translate('Abandoned Carts (30d)') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-yellow">
        <div class="perf-stat-value">{{ number_format($stats['sessions'] ?? 0) }}</div>
        <div class="perf-stat-label">{{ translate('Old Sessions') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-blue">
        <div class="perf-stat-value">{{ number_format($stats['failed_jobs'] ?? 0) }}</div>
        <div class="perf-stat-label">{{ translate('Failed Jobs') }}</div>
    </div>
    <div class="perf-stat-card perf-stat-cyan">
        <div class="perf-stat-value">{{ number_format(($stats['old_notifications'] ?? 0) + ($stats['expired_otps'] ?? 0) + ($stats['personal_tokens'] ?? 0) + ($stats['password_resets'] ?? 0)) }}</div>
        <div class="perf-stat-label">{{ translate('Other Stale Rows') }}</div>
    </div>
</div>

<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-broom"></i></span>{{ translate('Cleanup Tasks') }}</h5>
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.database.clean') }}" method="POST"
                      onsubmit="return confirm('{{ translate('Delete the selected rows? This cannot be undone.') }}')">
                    @csrf
                    @php
                        $rows = [
                            'sessions'          => [translate('Clean Expired Sessions (30+ days)'),       translate('Removes old sessions from the sessions table.'),    $stats['sessions'] ?? 0],
                            'failed_jobs'       => [translate('Clean Old Failed Jobs'),                   translate('Removes all entries from failed_jobs table.'),       $stats['failed_jobs'] ?? 0],
                            'password_resets'   => [translate('Clean Stale Password Resets (>7 days)'),   translate('Removes unused password reset tokens.'),             $stats['password_resets'] ?? 0],
                            'personal_tokens'   => [translate('Clean Expired API Tokens'),                translate('Removes personal_access_tokens past their expires_at.'), $stats['personal_tokens'] ?? 0],
                            'old_notifications' => [translate('Clean Read Notifications (>30 days)'),     translate('Removes already-read notifications older than 30 days.'),$stats['old_notifications'] ?? 0],
                            'old_carts'         => [translate('Clean Abandoned Carts (>30 days)'),        translate('Removes guest cart rows that were never converted.'),$stats['old_carts'] ?? 0],
                            'expired_otps'      => [translate('Clean Expired OTP Codes (>24h)'),          translate('Removes OTP email codes past their validity window.'),$stats['expired_otps'] ?? 0],
                        ];
                    @endphp
                    @foreach($rows as $k => $row)
                        <div class="perf-toggle-row">
                            <div>
                                <label class="d-flex align-items-center mb-0">
                                    <input type="checkbox" name="items[]" value="{{ $k }}" class="mr-2 perf-clean-check" checked>
                                    <strong>{{ $row[0] }}</strong>
                                </label>
                                <small class="text-muted d-block" style="margin-left:24px">{{ $row[1] }}</small>
                            </div>
                            <span class="perf-pill">{{ number_format($row[2]) }} {{ translate('rows') }}</span>
                        </div>
                    @endforeach

                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="las la-broom"></i> {{ translate('Run Cleanup Now') }}</button>
                </form>
                        <form action="{{ route('performance_optimizer.database.optimize') }}" method="POST" class="d-inline ml-1"
                              onsubmit="return confirm('{{ translate('Run OPTIMIZE TABLE on all tables? This may take a while on large DBs.') }}')">
                            @csrf
                            <button class="btn btn-soft-warning btn-sm"><i class="las la-tools"></i> {{ translate('Optimize Database Tables (OPTIMIZE TABLE)') }}</button>
                        </form>
                    </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(76,175,80,.1);color:#4caf50"><i class="las la-clock"></i></span>{{ translate('Auto-Cleanup Schedule') }}</h5>
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.settings.update') }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-6 mb-2">
                        <label class="small">{{ translate('Auto cleanup') }}</label>
                        <select name="perf_db_auto_clean_status" class="form-control form-control-sm">
                            <option value="0" @if(get_setting('perf_db_auto_clean_status') == 0) selected @endif>{{ translate('Manual Only') }}</option>
                            <option value="1" @if(get_setting('perf_db_auto_clean_status') == 1) selected @endif>{{ translate('Daily (via cron)') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="small">{{ translate('Keep records (days)') }}</label>
                        <input type="number" name="perf_db_auto_clean_keep_days" class="form-control form-control-sm" value="{{ get_setting('perf_db_auto_clean_keep_days', 30) }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-soft-primary btn-sm">{{ translate('Save Schedule') }}</button>
                        <small class="text-muted ml-2">{{ translate('Requires') }} <code>php artisan schedule:run</code> {{ translate('in cron.') }}</small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Database Tips') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('OPTIMIZE TABLE') }}</strong> {{ translate('reclaims space and defragments InnoDB tables after large deletes.') }}</p>
                <p><strong>{{ translate('Abandoned carts') }}</strong> {{ translate('cleanup is conservative — only deletes carts past 30 days, never customer/order data.') }}</p>
                <p>{{ translate('Schedule weekly cleanup for best ongoing performance:') }}<br>
                <code>$schedule->command('perf:auto-clean')->dailyAt('03:30');</code></p>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('.perf-clean-check')?.parentElement?.parentElement?.parentElement?.addEventListener('click', function(){});
</script>
