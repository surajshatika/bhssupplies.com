{{-- ── One-click combined purge banner ────────────────────────────────────── --}}
<div class="alert d-flex align-items-center justify-content-between flex-wrap mb-3 py-2 px-3"
     style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);border:none;border-radius:10px;gap:10px">
    <div class="text-white">
        <strong><i class="las la-fire mr-1"></i>{{ translate('Purge All Caches') }}</strong>
        <span class="d-block small" style="opacity:.75;font-size:11px">
            {{ translate('Clears Page Cache + LiteSpeed Cache + Cloudflare CDN in one click') }}
        </span>
    </div>
    <form action="{{ route('performance_optimizer.cache.purge_everything') }}" method="POST"
          onsubmit="return confirm('{{ translate('Purge ALL caches? (Page Cache + LiteSpeed + Cloudflare)') }}')">
        @csrf
        <button class="btn btn-danger btn-sm font-weight-bold">
            <i class="las la-bomb mr-1"></i>{{ translate('Purge Everything') }}
        </button>
    </form>
</div>

{{-- ── Active driver status card ────────────────────────────────────────────── --}}
@php
    $_activeDriver = get_setting('perf_page_cache_driver', 'file');
    $_cacheOn      = (int) get_setting('perf_page_cache_status', 0) === 1;
    $_cmsFastMode  = (int) get_setting('perf_cms_fast_mode', 1) === 1;
    $_driverLabels = ['file' => 'File Cache', 'redis' => 'Redis', 'litespeed' => 'LiteSpeed Cache (LSCache)', 'memcached' => 'Memcached'];
    $_driverIcons  = ['file' => 'la-hdd', 'redis' => 'la-database', 'litespeed' => 'la-server', 'memcached' => 'la-memory'];
@endphp
<div class="d-flex flex-wrap align-items-stretch mb-3" style="gap:12px">
    {{-- CMS safe fast path --}}
    <div class="flex-fill p-3 rounded" style="border:2px solid {{ $_cmsFastMode ? '#0ea5e9' : '#ffc107' }};background:#fff;min-width:220px">
        <div class="d-flex align-items-center" style="gap:10px">
            <span style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:{{ $_cmsFastMode ? 'rgba(14,165,233,.1)' : 'rgba(255,193,7,.15)' }};font-size:18px;color:{{ $_cmsFastMode ? '#0ea5e9' : '#b8860b' }}">
                <i class="las la-tachometer-alt"></i>
            </span>
            <div>
                <div class="font-weight-bold" style="font-size:13px">{{ translate('CMS Fast Mode') }}</div>
                <div style="font-size:15px;font-weight:700;color:{{ $_cmsFastMode ? '#0ea5e9' : '#b8860b' }}">
                    {{ $_cmsFastMode ? translate('Lightweight') : translate('Advanced rewrites') }}
                </div>
            </div>
            <label class="aiz-switch aiz-switch-success mb-0 ml-auto">
                <input type="checkbox" onchange="perfToggle(this, 'perf_cms_fast_mode')" @if($_cmsFastMode) checked @endif>
                <span class="slider round"></span>
            </label>
        </div>
        <small class="text-muted d-block mt-1">{{ translate('Keeps Active eCommerce runtime HTML untouched for smoother loading.') }}</small>
    </div>
    {{-- Page cache active driver --}}
    <div class="flex-fill p-3 rounded" style="border:2px solid {{ $_cacheOn ? '#28a745' : '#dee2e6' }};background:#fff;min-width:220px">
        <div class="d-flex align-items-center" style="gap:10px">
            <span style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:{{ $_cacheOn ? 'rgba(40,167,69,.12)' : '#f8f9fa' }};font-size:18px;color:{{ $_cacheOn ? '#28a745' : '#aaa' }}">
                <i class="las {{ $_driverIcons[$_activeDriver] ?? 'la-bolt' }}"></i>
            </span>
            <div>
                <div class="font-weight-bold" style="font-size:13px">{{ translate('Page Cache Driver') }}</div>
                <div style="font-size:15px;font-weight:700;color:{{ $_cacheOn ? '#28a745' : '#6c757d' }}">
                    {{ $_driverLabels[$_activeDriver] ?? strtoupper($_activeDriver) }}
                </div>
            </div>
            <span class="badge ml-auto {{ $_cacheOn ? 'badge-success' : 'badge-secondary' }}">
                {{ $_cacheOn ? translate('ON') : translate('OFF') }}
            </span>
        </div>
        @if(!$_cacheOn)
        <small class="text-danger d-block mt-1">{{ translate('Page cache is disabled — toggle ON to activate') }}</small>
        @elseif($_activeDriver === 'memcached' && !class_exists(\Memcached::class))
        <small class="text-danger d-block mt-1">{{ translate('Memcached PHP extension is missing. File cache fallback is active.') }}</small>
        @endif
    </div>
    {{-- OPcache independent status --}}
    <div class="flex-fill p-3 rounded" style="border:2px solid {{ (isset($opcacheStats) && $opcacheStats['enabled']) ? '#6610f2' : '#dee2e6' }};background:#fff;min-width:220px">
        <div class="d-flex align-items-center" style="gap:10px">
            <span style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(102,16,242,.1);font-size:18px;color:#6610f2">
                <i class="las la-microchip"></i>
            </span>
            <div>
                <div class="font-weight-bold" style="font-size:13px">{{ translate('PHP OPcache') }}</div>
                <div style="font-size:12px;color:#6c757d">{{ translate('Bytecode cache — independent') }}</div>
            </div>
            @if(isset($opcacheStats) && $opcacheStats['enabled'])
                <span class="badge badge-light ml-auto" style="color:#6610f2;border:1px solid #6610f2">{{ $opcacheStats['hit_rate'] }}% {{ translate('Hit Rate') }}</span>
            @else
                <span class="badge badge-secondary ml-auto">{{ translate('Inactive') }}</span>
            @endif
        </div>
        <small class="text-muted d-block mt-1" style="font-size:10px">
            <i class="las la-info-circle"></i> {{ translate('Works alongside any page cache driver — no conflict') }}
        </small>
    </div>
</div>

@php
    $_cacheTest = session('cache_test_result');
@endphp
<div class="perf-section">
    <div class="perf-section-header">
        <h5><span class="perf-section-icon" style="background:rgba(14,165,233,.1);color:#0ea5e9"><i class="las la-stopwatch"></i></span>{{ translate('Live Cache Test') }}</h5>
        @if($_cacheTest)
            <span class="badge badge-light">{{ $_cacheTest['tested_at'] ?? '' }}</span>
        @endif
    </div>
    <div class="perf-section-body">
        <form action="{{ route('performance_optimizer.cache.test_url') }}" method="POST" class="row align-items-end">
            @csrf
            <div class="col-lg-9 col-md-8 mb-2">
                <label class="small">{{ translate('Storefront URL') }}</label>
                <input type="url" name="url" class="form-control form-control-sm"
                       value="{{ old('url', $_cacheTest['url'] ?? url('/')) }}"
                       placeholder="{{ url('/') }}">
            </div>
            <div class="col-lg-3 col-md-4 mb-2">
                <button class="btn btn-soft-info btn-sm btn-block">
                    <i class="las la-vial"></i> {{ translate('Test Cache') }}
                </button>
            </div>
        </form>

        @if($_cacheTest)
            @php
                $_diag = $_cacheTest['diagnosis'] ?? [];
                $_first = $_cacheTest['first'] ?? [];
                $_second = $_cacheTest['second'] ?? [];
                $_expectedOk = (bool) ($_diag['cacheable'] ?? false);
                $_cacheBadge = function ($probe) {
                    $headers = $probe['headers'] ?? [];
                    return $headers['x-performance-cache']
                        ?? $headers['x-litespeed-cache']
                        ?? $headers['cf-cache-status']
                        ?? 'NO HEADER';
                };
            @endphp
            <div class="mt-3">
                <div class="alert py-2 mb-3 {{ $_expectedOk ? 'alert-success' : 'alert-warning' }}">
                    <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                        <strong>{{ translate('Expected') }}:</strong>
                        <span class="badge {{ $_expectedOk ? 'badge-success' : 'badge-warning' }}">
                            {{ $_expectedOk ? translate('Cacheable') : translate('Bypass') }}
                        </span>
                        <span class="badge badge-light">{{ translate('Driver') }}: {{ strtoupper($_diag['driver'] ?? '-') }}</span>
                        <span class="badge badge-light">{{ translate('TTL') }}: {{ $_diag['ttl_minutes'] ?? 0 }}m</span>
                        @if(array_key_exists('stored_copy', $_diag) && $_diag['stored_copy'] !== null)
                            <span class="badge {{ $_diag['stored_copy'] ? 'badge-success' : 'badge-secondary' }}">
                                {{ $_diag['stored_copy'] ? translate('Stored copy found') : translate('No stored copy') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="row">
                    @foreach(['First request' => $_first, 'Second request' => $_second] as $_label => $_probe)
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ translate($_label) }}</strong>
                                    <span class="badge {{ ($_probe['ok'] ?? false) ? 'badge-light' : 'badge-danger' }}">
                                        {{ ($_probe['ok'] ?? false) ? ($_probe['status'] ?? '-') : translate('Failed') }}
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap" style="gap:8px">
                                    <span class="perf-pill">{{ translate('TTFB') }} {{ $_probe['time_ms'] ?? 0 }}ms</span>
                                    <span class="perf-pill">{{ translate('Size') }} {{ $_probe['size_kb'] ?? 0 }}KB</span>
                                    <span class="perf-pill">{{ $_cacheBadge($_probe) }}</span>
                                </div>
                                @if(!($_probe['ok'] ?? false))
                                    <small class="text-danger d-block mt-2">{{ $_probe['error'] ?? translate('Request failed') }}</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(!empty($_diag['reasons']) || !empty($_diag['warnings']))
                    <div class="row">
                        @if(!empty($_diag['reasons']))
                            <div class="col-md-6 mb-2">
                                <strong class="small text-warning">{{ translate('Bypass Reasons') }}</strong>
                                <ul class="small mb-0 pl-3">
                                    @foreach($_diag['reasons'] as $_reason)
                                        <li>{{ $_reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if(!empty($_diag['warnings']))
                            <div class="col-md-6 mb-2">
                                <strong class="small text-info">{{ translate('Notes') }}</strong>
                                <ul class="small mb-0 pl-3">
                                    @foreach($_diag['warnings'] as $_warning)
                                        <li>{{ $_warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered mb-0" style="font-size:12px">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Header') }}</th>
                                <th>{{ translate('First') }}</th>
                                <th>{{ translate('Second') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $_allHeaders = array_unique(array_merge(
                                    array_keys($_first['headers'] ?? []),
                                    array_keys($_second['headers'] ?? [])
                                ));
                            @endphp
                            @forelse($_allHeaders as $_header)
                                <tr>
                                    <td><code>{{ $_header }}</code></td>
                                    <td>{{ ($_first['headers'][$_header] ?? '-') }}</td>
                                    <td>{{ ($_second['headers'][$_header] ?? '-') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">{{ translate('No cache headers detected.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

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
                        <input type="hidden" name="max" value="5">
                        <button class="btn btn-soft-success btn-sm"><i class="las la-fire"></i> {{ translate('Warm Cache (Preload Pages)') }}</button>
                    </form>
                </div>
                <small class="text-muted">
                    @if(($stats['driver'] ?? '') === 'litespeed')
                        {{ translate('Currently cached') }}: <strong>{{ translate('Server-managed') }}</strong> · {{ translate('Driver') }}: <strong>{{ strtoupper($stats['driver']) }}</strong>
                    @else
                        {{ translate('Currently cached') }}: <strong>{{ $stats['pages'] }}</strong> {{ translate('pages') }} ({{ $stats['size'] }}) · {{ translate('Driver') }}: <strong>{{ strtoupper($stats['driver']) }}</strong>
                    @endif
                </small>

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
                @if(get_setting('perf_page_cache_driver') === 'litespeed')
                    <span class="badge badge-success px-2"><i class="las la-check-circle"></i> {{ translate('Active Driver') }}</span>
                @elseif(isset($lscInfo) && $lscInfo['litespeed_detected'])
                    <span class="badge badge-info px-2">{{ translate('Available — not selected') }}</span>
                @else
                    <span class="badge badge-secondary px-2">{{ translate('Server not detected') }}</span>
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

        {{-- ── Divider between Page Cache and independent caches ──────────────── --}}
        <div class="d-flex align-items-center my-2" style="gap:8px">
            <hr class="flex-fill m-0">
            <small class="text-muted px-2 text-nowrap" style="font-size:10px;letter-spacing:.5px;text-transform:uppercase">
                {{ translate('PHP-Level Cache (independent — works with any page cache driver)') }}
            </small>
            <hr class="flex-fill m-0">
        </div>

        {{-- ── OPcache ─────────────────────────────────────────────────────── --}}
        <div class="perf-section" id="opcache-section">
            <div class="perf-section-header">
                <h5>
                    <span class="perf-section-icon" style="background:rgba(102,16,242,.1);color:#6610f2"><i class="las la-microchip"></i></span>
                    {{ translate('PHP OPcache') }}
                    <small class="text-muted font-weight-normal ml-1" style="font-size:11px">({{ translate('Bytecode Cache') }})</small>
                </h5>
                @if(isset($opcacheStats) && $opcacheStats['enabled'])
                    <span class="badge px-2" style="background:rgba(102,16,242,.12);color:#6610f2;border:1px solid #6610f2">
                        <i class="las la-check-circle"></i> {{ translate('Active · Independent') }}
                    </span>
                @elseif(isset($opcacheStats) && $opcacheStats['available'])
                    <span class="badge badge-warning px-2">{{ translate('Installed but disabled') }}</span>
                @else
                    <span class="badge badge-danger px-2">{{ translate('Not Available') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                <div class="alert py-2 mb-3 small" style="background:rgba(102,16,242,.06);border:1px solid rgba(102,16,242,.2);color:#4a0e9e">
                    <i class="las la-info-circle mr-1"></i>
                    <strong>{{ translate('OPcache ≠ Page Cache.') }}</strong>
                    {{ translate('OPcache compiles PHP scripts to bytecode so PHP runs faster. It works alongside File, Redis, LiteSpeed, or any page cache driver — enabling both is recommended.') }}
                </div>
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
                @elseif(in_array($stats['driver'], ['redis', 'memcached'], true) && (!isset($cachedPages) || count($cachedPages) === 0))
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="las la-info-circle"></i>
                        {{ strtoupper($stats['driver']) }} {{ translate('stores pages in memory. The table shows entries tracked by Performance Optimizer after the latest warm or visit.') }}
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
                    <small class="text-muted d-block mt-1">{{ translate('Showing up to 50 most recently tracked cached pages.') }}</small>
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
