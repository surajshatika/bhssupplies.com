<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-font"></i></span>{{ translate('Font Display Strategy') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_fonts_swap_status')"
                           @if(get_setting('perf_fonts_swap_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Forces') }} <code>font-display: swap</code> {{ translate('so text is visible immediately while web fonts load. Eliminates Flash of Invisible Text (FOIT).') }}</p>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(76,175,80,.1);color:#4caf50"><i class="las la-bolt"></i></span>{{ translate('Preload Critical Fonts') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_fonts_preload_status')"
                           @if(get_setting('perf_fonts_preload_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Injects') }} <code>&lt;link rel="preload" as="font"&gt;</code> {{ translate('tags in the page head so the browser fetches fonts in parallel with HTML — eliminates render-blocking font requests.') }}</p>
                <form action="{{ route('performance_optimizer.fonts.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="perf_fonts_preload_status" value="{{ (int) get_setting('perf_fonts_preload_status') }}">
                    <input type="hidden" name="perf_fonts_swap_status"    value="{{ (int) get_setting('perf_fonts_swap_status') }}">
                    <label class="small font-weight-bold">{{ translate('Font URLs to preload (one per line)') }}</label>
                    <textarea name="perf_fonts_preload_list" class="form-control" rows="5"
                              placeholder="{{ asset('assets/fonts/inter-regular.woff2') }}&#10;{{ asset('assets/fonts/inter-bold.woff2') }}">{{ get_setting('perf_fonts_preload_list') }}</textarea>
                    <small class="text-muted">{{ translate('Use absolute URLs ending in .woff2 / .woff / .ttf. crossorigin="anonymous" is added automatically.') }}</small>
                    <div class="mt-2">
                        <button class="btn btn-soft-primary btn-sm">{{ translate('Save Font Settings') }}</button>
                    </div>
                </form>
            </div>
        </div>

        @if(!empty($list))
        <div class="perf-section">
            <div class="perf-section-header"><h5><span class="perf-section-icon"><i class="las la-list"></i></span>{{ translate('Currently Preloaded') }}</h5></div>
            <div class="perf-section-body p-0">
                <table class="perf-table">
                    <thead><tr><th>{{ translate('Font URL') }}</th><th>{{ translate('Type') }}</th></tr></thead>
                    <tbody>
                        @foreach($list as $url)
                            <tr>
                                <td><small>{{ $url }}</small></td>
                                <td><span class="perf-pill">{{ strtoupper(pathinfo(parse_url($url, PHP_URL_PATH) ?? $url, PATHINFO_EXTENSION)) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Font Optimization Tips') }}</div>
            <div class="perf-tips-body">
                <p>{{ translate('Only load the font weights you actually use. If 500 isn\'t used, remove it from your Google Fonts URL.') }}</p>
                <p><strong>{{ translate('Preload sparingly') }}</strong> — {{ translate('preload only the 1-2 fonts visible on first paint. Preloading too much hurts performance.') }}</p>
                <p><strong>{{ translate('Use .woff2') }}</strong> — {{ translate('it\'s ~30% smaller than .woff and supported by all modern browsers.') }}</p>
            </div>
        </div>

        <div class="perf-section" style="background:#e7f5ff">
            <div class="perf-section-header" style="background:#d0ebff">
                <h5><i class="las la-question-circle"></i> {{ translate('Why Self-Host Fonts?') }}</h5>
            </div>
            <div class="perf-section-body" style="font-size:13px">
                <p>✓ {{ translate('Eliminates DNS lookup + connection to') }} <code>fonts.googleapis.com</code></p>
                <p>✓ {{ translate('Eliminates connection to') }} <code>fonts.gstatic.com</code></p>
                <p>✓ <code>font-display: swap</code> {{ translate('prevents Flash of Invisible Text (FOIT)') }}</p>
                <p>✓ {{ translate('Fonts cached by browser alongside other local assets') }}</p>
                <p>✓ {{ translate('Privacy: no data sent to Google servers') }}</p>
            </div>
        </div>
    </div>
</div>
