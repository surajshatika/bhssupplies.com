<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-bolt"></i></span>{{ translate('Page Cache (Static HTML)') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_page_cache_status')"
                           @if(get_setting('perf_page_cache_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Saves rendered pages as static HTML files. Next visit is served directly — no PHP, no DB.') }}</p>
                <div class="d-flex flex-wrap mb-2" style="gap:8px">
                    <form action="{{ route('performance_optimizer.cache.clear') }}" method="POST"
                          onsubmit="return confirm('{{ translate('Clear all cached pages?') }}')">
                        @csrf
                        <button class="btn btn-soft-danger btn-sm"><i class="las la-trash"></i> {{ translate('Clear Page Cache') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.cache.warm') }}" method="POST">
                        @csrf
                        <button class="btn btn-soft-success btn-sm"><i class="las la-fire"></i> {{ translate('Warm Cache (Preload Pages)') }}</button>
                    </form>
                </div>
                <small class="text-muted">{{ translate('Currently cached') }}: <strong>{{ $stats['pages'] }}</strong> {{ translate('pages') }} ({{ $stats['size'] }}) · {{ translate('Driver') }}: <strong>{{ strtoupper($stats['driver']) }}</strong></small>

                <hr>
                <form action="{{ route('performance_optimizer.settings.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="small">{{ translate('Cache driver') }}</label>
                            <select name="perf_page_cache_driver" class="form-control form-control-sm">
                                <option value="file"  @if(get_setting('perf_page_cache_driver') == 'file') selected @endif>file</option>
                                <option value="redis" @if(get_setting('perf_page_cache_driver') == 'redis') selected @endif>redis</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="small">{{ translate('TTL (minutes)') }}</label>
                            <input type="number" name="perf_page_cache_ttl_minutes" class="form-control form-control-sm" value="{{ get_setting('perf_page_cache_ttl_minutes', 720) }}">
                        </div>
                        <div class="col-md-4 mb-2 d-flex align-items-end">
                            <button class="btn btn-soft-primary btn-sm btn-block">{{ translate('Save') }}</button>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small">{{ translate('Exclude paths') }}</label>
                            <textarea name="perf_page_cache_exclude_paths" class="form-control form-control-sm" rows="3">{{ get_setting('perf_page_cache_exclude_paths') }}</textarea>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small">{{ translate('Exclude cookies') }}</label>
                            <textarea name="perf_page_cache_exclude_cookies" class="form-control form-control-sm" rows="3">{{ get_setting('perf_page_cache_exclude_cookies') }}</textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(40,167,69,.1);color:var(--perf-green)"><i class="las la-memory"></i></span>{{ translate('Object / Query Cache') }}</h5>
            </div>
            <div class="perf-section-body">
                <p>{{ translate("Laravel's built-in cache for DB queries. Current driver:") }} <code>{{ config('cache.default') }}</code></p>
                <form action="{{ route('performance_optimizer.cache.laravel_clear') }}" method="POST">
                    @csrf
                    <button class="btn btn-soft-danger btn-sm"><i class="las la-redo"></i> {{ translate('Clear Laravel Cache (cache/config/view/route)') }}</button>
                </form>
                <form action="{{ route('performance_optimizer.cache.optimize') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-soft-primary btn-sm ml-1"><i class="las la-bolt"></i> {{ translate('Optimize (rebuild caches)') }}</button>
                </form>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(255,193,7,.15);color:#b8860b"><i class="las la-hdd"></i></span>{{ translate('Browser Caching (.htaccess)') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_browser_cache_status')"
                           @if(get_setting('perf_browser_cache_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Sets') }} <code>Cache-Control</code> {{ translate('and') }} <code>Expires</code> {{ translate('headers so browsers cache static assets for up to 1 year.') }}</p>
                <small class="text-muted">{{ translate('Add the following to') }} <code>public/.htaccess</code>: {{ translate('mod_expires snippet appears in /admin/performance-optimizer/caching when enabled.') }}</small>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(156,39,176,.1);color:var(--perf-purple)"><i class="las la-mouse-pointer"></i></span>{{ translate('Preload Links on Hover') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_preload_hover_status')"
                           @if(get_setting('perf_preload_hover_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Prefetches a page in the browser the moment a user hovers over a link — feels instant on click. Adds ~1KB inline JS.') }}</p>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(247,108,30,.12);color:#f76c1e"><i class="las la-cloud"></i></span>{{ translate('Cloudflare Integration') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_cloudflare_status')"
                           @if(get_setting('perf_cloudflare_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Cache pages in Cloudflare and purge cache remotely when content changes.') }}</p>
                <form action="{{ route('performance_optimizer.settings.update') }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-6 mb-2">
                        <label class="small">{{ translate('Zone ID') }}</label>
                        <input type="text" name="perf_cloudflare_zone_id" class="form-control form-control-sm"
                               placeholder="abcdef1234567890..." value="{{ get_setting('perf_cloudflare_zone_id') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="small">{{ translate('API Token') }}</label>
                        <input type="password" name="perf_cloudflare_api_token" class="form-control form-control-sm"
                               placeholder="your-cloudflare-api-token" value="{{ get_setting('perf_cloudflare_api_token') }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-soft-warning btn-sm"><i class="las la-bolt"></i> {{ translate('Save Cloudflare Settings') }}</button>
                        <small class="text-muted ml-2">{{ translate('Get a free API token from') }} <code>dash.cloudflare.com</code></small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Cache Tips') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('Page Cache') }}</strong> {{ translate('is the biggest performance win — static HTML is ~10× faster than PHP rendering.') }}</p>
                <p><strong>{{ translate('Warm Cache') }}</strong> {{ translate('after enabling — visits all sitemap URLs to pre-generate pages.') }}</p>
                <p><strong>{{ translate('Cloudflare') }}</strong> {{ translate('provides the global CDN layer.') }}</p>
                <p>{{ translate('Cache is automatically cleared when you save products, pages, or settings.') }}</p>
            </div>
        </div>
    </div>
</div>
