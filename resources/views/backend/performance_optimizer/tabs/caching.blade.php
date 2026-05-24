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
                                <option value="file"      @if(get_setting('perf_page_cache_driver','file') == 'file') selected @endif>{{ translate('File (default)') }}</option>
                                <option value="redis"     @if(get_setting('perf_page_cache_driver') == 'redis') selected @endif>Redis</option>
                                <option value="litespeed" @if(get_setting('perf_page_cache_driver') == 'litespeed') selected @endif>LiteSpeed Cache (LSCache)</option>
                                <option value="memcached" @if(get_setting('perf_page_cache_driver') == 'memcached') selected @endif>Memcached</option>
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

        {{-- ── LiteSpeed Cache ─────────────────────────────────────────────── --}}
        <div class="perf-section" id="lsc-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(0,170,80,.1);color:#00aa50"><i class="las la-server"></i></span>{{ translate('LiteSpeed Cache (LSCache)') }}</h5>
                @if(isset($lscInfo) && $lscInfo['litespeed_detected'])
                    <span class="badge badge-success px-2">{{ translate('LiteSpeed Detected') }}</span>
                @else
                    <span class="badge badge-secondary px-2">{{ translate('Not on LiteSpeed') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                @if(isset($lscInfo) && $lscInfo['litespeed_detected'])
                    <div class="alert alert-success py-2 mb-2 small">
                        <i class="las la-check-circle"></i> {{ translate('LiteSpeed Web Server detected.') }}
                        <code class="ml-1">{{ $lscInfo['server_info'] }}</code>
                    </div>
                @else
                    <div class="alert alert-warning py-2 mb-2 small">
                        <i class="las la-exclamation-triangle"></i>
                        {{ translate('LiteSpeed server not detected on this host. Select the LiteSpeed driver only when deployed on LiteSpeed Web Server or OpenLiteSpeed.') }}
                    </div>
                @endif
                <p class="text-muted small mb-2">{{ translate('Server-level page cache. PHP sets X-LiteSpeed-Cache-Control headers; LiteSpeed serves cached pages directly without invoking PHP on cache hits. No file or Redis storage is used by PHP.') }}</p>
                <div class="d-flex flex-wrap mb-2" style="gap:8px">
                    <form action="{{ route('performance_optimizer.cache.purge_litespeed') }}" method="POST"
                          onsubmit="return confirm('{{ translate('Purge all LiteSpeed cached pages?') }}')">
                        @csrf
                        <button class="btn btn-soft-danger btn-sm"><i class="las la-trash"></i> {{ translate('Purge All LSCache') }}</button>
                    </form>
                </div>
                <form action="{{ route('performance_optimizer.cache.purge_litespeed_tag') }}" method="POST"
                      class="d-flex align-items-center" style="gap:8px">
                    @csrf
                    <input type="text" name="tag" class="form-control form-control-sm"
                           placeholder="{{ translate('Tag, e.g. product_123') }}" style="max-width:220px">
                    <button class="btn btn-soft-warning btn-sm"><i class="las la-tag"></i> {{ translate('Purge by Tag') }}</button>
                </form>
            </div>
        </div>

        {{-- ── OPcache ─────────────────────────────────────────────────────── --}}
        <div class="perf-section" id="opcache-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(102,16,242,.1);color:#6610f2"><i class="las la-microchip"></i></span>{{ translate('PHP OPcache') }}</h5>
                @if(isset($opcacheStats) && $opcacheStats['enabled'])
                    <span class="badge badge-success px-2">{{ translate('Active') }}</span>
                @elseif(isset($opcacheStats) && $opcacheStats['available'])
                    <span class="badge badge-warning px-2">{{ translate('Disabled') }}</span>
                @else
                    <span class="badge badge-danger px-2">{{ translate('Not Available') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                @if(isset($opcacheStats) && $opcacheStats['enabled'])
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="p-2 rounded text-center" style="background:#f8f9fa">
                                <div class="h4 mb-0 @if($opcacheStats['hit_rate'] >= 90) text-success @elseif($opcacheStats['hit_rate'] >= 70) text-warning @else text-danger @endif">
                                    {{ $opcacheStats['hit_rate'] }}%
                                </div>
                                <small class="text-muted">{{ translate('Hit Rate') }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded text-center" style="background:#f8f9fa">
                                <div class="h4 mb-0">{{ $opcacheStats['cached_scripts'] }}</div>
                                <small class="text-muted">{{ translate('Cached Scripts') }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded text-center" style="background:#f8f9fa">
                                <div class="h5 mb-0">{{ $opcacheStats['memory_used_mb'] }}<small>MB</small></div>
                                <small class="text-muted">{{ translate('Used') }} / {{ $opcacheStats['memory_total_mb'] }}MB</small>
                            </div>
                        </div>
                    </div>
                    @if($opcacheStats['jit_enabled'])
                        <div class="alert alert-info py-1 mb-2 small">
                            <i class="las la-bolt"></i> {{ translate('JIT Compiler active') }} — {{ $opcacheStats['jit_buffer_mb'] }}MB {{ translate('buffer') }}
                        </div>
                    @endif
                    <small class="text-muted d-block mb-2">{{ translate('Hits') }}: {{ number_format($opcacheStats['hits']) }} · {{ translate('Misses') }}: {{ number_format($opcacheStats['misses']) }} · {{ translate('Restarts') }}: {{ $opcacheStats['restarts'] }}</small>
                @else
                    <p class="text-muted small mb-2">
                        @if(isset($opcacheStats) && $opcacheStats['available'])
                            {{ translate('OPcache is installed but not enabled. Add') }} <code>opcache.enable=1</code> {{ translate('to php.ini to activate it.') }}
                        @else
                            {{ translate('OPcache is not available on this server.') }}
                        @endif
                    </p>
                @endif
                @if(isset($opcacheStats) && $opcacheStats['available'])
                    <form action="{{ route('performance_optimizer.cache.flush_opcache') }}" method="POST"
                          onsubmit="return confirm('{{ translate('Flush OPcache? PHP will recompile scripts on the next request.') }}')">
                        @csrf
                        <button class="btn btn-soft-danger btn-sm"><i class="las la-sync"></i> {{ translate('Flush OPcache') }}</button>
                    </form>
                @endif
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
        {{-- ── Cached Pages List ───────────────────────────────────────────── --}}
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(23,162,184,.1);color:#17a2b8"><i class="las la-list-alt"></i></span>{{ translate('Cached Pages') }}</h5>
                <span class="badge badge-secondary">{{ $stats['pages'] }} {{ translate('pages') }} · {{ $stats['size'] }}</span>
            </div>
            <div class="perf-section-body">
                @if($stats['driver'] === 'litespeed')
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="las la-info-circle"></i>
                        {{ translate('LiteSpeed manages cache storage at the server level. PHP cannot enumerate LSCache pages.') }}
                    </div>
                @elseif($stats['driver'] === 'redis')
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="las la-info-circle"></i>
                        {{ translate('Redis stores pages in memory. Inspect cached keys via Redis CLI with pattern') }} <code>perf_page_cache_*</code>.
                    </div>
                @elseif(isset($cachedPages) && count($cachedPages) > 0)
                    <div class="table-responsive" style="max-height:320px;overflow-y:auto">
                        <table class="table table-sm table-bordered mb-0" style="font-size:12px">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('URL') }}</th>
                                    <th style="width:75px">{{ translate('Size') }}</th>
                                    <th style="width:140px">{{ translate('Cached At') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cachedPages as $page)
                                <tr>
                                    <td class="text-truncate" style="max-width:260px" title="{{ $page['url'] ?? '' }}">
                                        @if($page['url'])
                                            <a href="{{ $page['url'] }}" target="_blank" rel="noopener noreferrer" class="text-dark">{{ $page['url'] }}</a>
                                        @else
                                            <span class="text-muted">{{ translate('(URL not indexed — pre-existing cache)') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ isset($page['size_bytes']) ? round($page['size_bytes'] / 1024, 1) . ' KB' : '—' }}</td>
                                    <td class="text-nowrap text-muted">{{ $page['cached_at'] ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-1">{{ translate('Showing up to 50 most recently cached pages (file driver).') }}</small>
                @else
                    <p class="text-muted small mb-0">
                        {{ translate('No pages cached yet. Enable Page Cache and visit your storefront to populate the cache.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Cache Tips') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('Page Cache') }}</strong> {{ translate('is the biggest performance win — static HTML is ~10× faster than PHP rendering.') }}</p>
                <p><strong>{{ translate('Warm Cache') }}</strong> {{ translate('after enabling — visits all sitemap URLs to pre-generate pages.') }}</p>
                <p><strong>{{ translate('LiteSpeed Cache') }}</strong> {{ translate('is the fastest option when hosted on LiteSpeed Web Server. No PHP overhead on cache hits.') }}</p>
                <p><strong>{{ translate('OPcache') }}</strong> {{ translate('compiles PHP scripts to bytecode — typically 2–5× faster PHP execution. Flush after deployments.') }}</p>
                <p><strong>{{ translate('Cloudflare') }}</strong> {{ translate('provides the global CDN layer on top of page cache.') }}</p>
                <p>{{ translate('Cache is automatically cleared when you save products, pages, or settings.') }}</p>
            </div>
        </div>
    </div>
</div>
