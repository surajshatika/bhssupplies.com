<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-file-code"></i></span>{{ translate('Minify CSS') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_css_minify_status')"
                           @if(get_setting('perf_css_minify_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Strips comments and whitespace from CSS files. Saves 20-40% on file size. Generates') }} <code>*.min.css</code> {{ translate('next to source files.') }}</p>
                <form action="{{ route('performance_optimizer.cssjs.minify_css') }}" method="POST">
                    @csrf
                    <button class="btn btn-soft-primary btn-sm"><i class="las la-bolt"></i> {{ translate('Minify CSS Now') }}</button>
                </form>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(255,193,7,.15);color:#b8860b"><i class="las la-code"></i></span>{{ translate('Minify JavaScript') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_js_minify_status')"
                           @if(get_setting('perf_js_minify_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Compresses JS files (uses JShrink). Saves 15-30% per file. Run') }} <code>composer require tedivm/jshrink</code> {{ translate('if not installed.') }}</p>
                <form action="{{ route('performance_optimizer.cssjs.minify_js') }}" method="POST">
                    @csrf
                    <button class="btn btn-soft-warning btn-sm"><i class="las la-bolt"></i> {{ translate('Minify JS Now') }}</button>
                </form>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(33,150,243,.1);color:var(--perf-blue)"><i class="las la-forward"></i></span>{{ translate('Defer Non-Critical Scripts') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_js_defer_status')"
                           @if(get_setting('perf_js_defer_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Adds') }} <code>defer</code> {{ translate('attribute to non-critical script tags. Eliminates render-blocking JavaScript.') }}</p>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(156,39,176,.1);color:var(--perf-purple)"><i class="las la-hand-pointer"></i></span>{{ translate('Delay Script Execution (On User Interaction)') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_js_delay_status')"
                           @if(get_setting('perf_js_delay_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Loads non-critical scripts only after first user interaction (click, scroll, keydown, touch). Best for chat widgets, analytics, etc.') }}</p>
                <div class="alert alert-info py-2 mb-0" style="font-size:12px">
                    <i class="las la-info-circle"></i> {{ translate('Use JS Defer Exclude below to protect scripts that must load immediately.') }}
                </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(40,167,69,.1);color:var(--perf-green)"><i class="las la-eye"></i></span>{{ translate('Critical CSS (Above-the-Fold)') }}</h5>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Inlines above-fold CSS in') }} <code>&lt;head&gt;</code> {{ translate('for instant first paint. Full CSS loads asynchronously.') }}</p>
                <form action="{{ route('performance_optimizer.settings.update') }}" method="POST">
                    @csrf
                    <textarea name="perf_critical_css" class="form-control mb-2" rows="4" placeholder="body{margin:0;font-family:sans-serif} .header{height:60px;...}">{{ get_setting('perf_critical_css') }}</textarea>
                    <button class="btn btn-soft-success btn-sm">{{ translate('Save Critical CSS') }}</button>
                </form>
                @if(get_setting('perf_critical_css'))
                    <div class="alert alert-warning py-2 mt-2 mb-0" style="font-size:12px">
                        <i class="las la-exclamation-triangle"></i> {{ translate('If layout breaks, disable this first.') }}
                    </div>
                @endif
            </div>
        </div>

        @if(!empty($missing_dimensions))
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(220,53,69,.1);color:var(--perf-red)"><i class="las la-expand"></i></span>{{ translate('Missing img width/height') }}</h5>
                <span class="perf-pill">{{ count($missing_dimensions) }} {{ translate('found') }}</span>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Images without explicit width/height cause Cumulative Layout Shift. Auto-fix reads actual pixel dimensions and writes them into blade templates.') }}</p>
                <form action="{{ route('performance_optimizer.cssjs.fix_dims') }}" method="POST" class="mb-3"
                      onsubmit="return confirm('{{ translate('Auto-add width/height to static <img> tags in frontend views?') }}')">
                    @csrf
                    <button class="btn btn-soft-warning btn-sm" type="submit"><i class="las la-magic"></i> {{ translate('Auto-Fix Dimensions') }}</button>
                </form>
                <div style="max-height:280px;overflow:auto">
                    <table class="perf-table">
                        <thead><tr><th>{{ translate('File') }}</th><th>{{ translate('Line') }}</th><th>{{ translate('Snippet') }}</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($missing_dimensions, 0, 30) as $m)
                                <tr>
                                    <td><small>{{ $m['file'] }}</small></td>
                                    <td>{{ $m['line'] }}</td>
                                    <td><code style="font-size:11px">{{ \Illuminate\Support\Str::limit($m['snippet'], 70) }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(108,117,125,.15);color:#6c757d"><i class="las la-ban"></i></span>{{ translate('Exclusion Lists (Safety)') }}</h5>
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.settings.update') }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold">{{ translate('CSS minify excludes') }}</label>
                        <textarea name="perf_css_minify_exclude" class="form-control" rows="5" placeholder="custom.css&#10;sweetalert*">{{ get_setting('perf_css_minify_exclude') }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold">{{ translate('JS minify excludes') }}</label>
                        <textarea name="perf_js_minify_exclude" class="form-control" rows="5" placeholder="firebase-app.js&#10;jquery*">{{ get_setting('perf_js_minify_exclude') }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold">{{ translate('JS Defer/Delay excludes') }}</label>
                        <textarea name="perf_js_defer_exclude" class="form-control" rows="5" placeholder="jquery&#10;firebase&#10;recaptcha">{{ get_setting('perf_js_defer_exclude') }}</textarea>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">{{ translate('One pattern per line. Wildcards supported (e.g.') }} <code>jquery*</code>).</small>
                        <button class="btn btn-soft-primary btn-sm float-right">{{ translate('Save Exclusions') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('CSS / JS Tips') }}</div>
            <div class="perf-tips-body">
                <p><i class="las la-check-circle text-success"></i> <strong>{{ translate('Minify CSS first') }}</strong> — {{ translate('safe, always improves performance.') }}</p>
                <p><i class="las la-check-circle text-success"></i> <strong>{{ translate('Defer JS') }}</strong> — {{ translate('test on checkout / form pages first.') }}</p>
                <p><i class="las la-exclamation-triangle text-warning"></i> <strong>{{ translate('Delay JS') }}</strong> — {{ translate('exclude inline event scripts.') }}</p>
                <p><i class="las la-exclamation-triangle text-warning"></i> <strong>{{ translate('Critical CSS') }}</strong> — {{ translate('may cause flash of unstyled content on complex layouts.') }}</p>
                <p><i class="las la-times-circle text-danger"></i> {{ translate('If any feature breaks the site, use') }} <strong>{{ translate('Emergency Disable All') }}</strong> {{ translate('at top.') }}</p>
            </div>
        </div>
    </div>
</div>
