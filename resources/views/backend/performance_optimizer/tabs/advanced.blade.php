<div class="perf-card">
    <div class="perf-card-header">
        <h5><i class="las la-cogs"></i> {{ translate('Advanced Performance Features') }}</h5>
    </div>
    <div class="perf-card-body">
        <form action="{{ route('performance_optimizer.settings.update') }}" method="POST">
            @csrf

            {{-- Speculation Rules --}}
            <div class="form-group row">
                <div class="col-md-3">
                    <label class="col-form-label">
                        {{ translate('Speculation Rules API') }}
                        <div class="text-muted small">{{ translate('Prerender pages on hover') }}</div>
                    </label>
                </div>
                <div class="col-md-9">
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="checkbox" name="perf_speculation_rules_status" value="1" @if(get_setting('perf_speculation_rules_status') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                    <small class="form-text text-muted">
                        {{ translate('Injects Speculation Rules JSON to instruct modern browsers to prerender the next page when a user hovers over a link, making navigation nearly instantaneous.') }}
                    </small>
                </div>
            </div>

            <hr>

            {{-- 3rd Party Script Localization --}}
            <div class="form-group row">
                <div class="col-md-3">
                    <label class="col-form-label">
                        {{ translate('Localize 3rd-Party Scripts') }}
                        <div class="text-muted small">{{ translate('Google Analytics, Facebook Pixel, GTM') }}</div>
                    </label>
                </div>
                <div class="col-md-9">
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="checkbox" name="perf_localize_scripts_status" value="1" @if(get_setting('perf_localize_scripts_status') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                    <small class="form-text text-muted">
                        {{ translate('Downloads known external scripts (like analytics.js) to your local server and serves them locally to fix the "Serve static assets with an efficient cache policy" PageSpeed penalty.') }}<br>
                        <strong>Note:</strong> {{ translate('You must set up a daily cron job for') }} <code>php artisan perf:localize-scripts</code> {{ translate('for this to work properly.') }}
                    </small>
                </div>
            </div>

            <hr>

            {{-- Resource Hints --}}
            <div class="form-group row">
                <div class="col-md-3">
                    <label class="col-form-label">
                        {{ translate('Preconnect Domains') }}
                    </label>
                </div>
                <div class="col-md-9">
                    <textarea name="perf_preconnect_domains" rows="4" class="form-control" placeholder="https://fonts.googleapis.com&#10;https://connect.facebook.net">{{ get_setting('perf_preconnect_domains') }}</textarea>
                    <small class="form-text text-muted">
                        {{ translate('Enter one domain per line (e.g. https://fonts.googleapis.com). The system will inject <link rel="preconnect"> and <link rel="dns-prefetch"> tags to reduce connection setup latency for external resources.') }}
                    </small>
                </div>
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Save Settings') }}</button>
            </div>
        </form>
    </div>
</div>
