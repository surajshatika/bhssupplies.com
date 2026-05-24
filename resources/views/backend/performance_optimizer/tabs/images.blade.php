<div class="perf-layout-2col">
    <div>
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon"><i class="las la-image"></i></span>{{ translate('Image Conversion & Compression') }}</h5>
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.settings.update') }}" method="POST" class="row">
                    @csrf
                    <div class="col-md-4 mb-3">
                        <label class="small text-muted">{{ translate('Output Format') }}</label>
                        @php $avifOk = function_exists('imageavif'); @endphp
                        <select name="perf_image_format" class="form-control form-control-sm" disabled title="WebP is default; AVIF requires PHP 8.1+ GD AVIF">
                            <option>{{ translate('WebP (recommended)') }}</option>
                            @if($avifOk) <option>{{ translate('AVIF (smaller, PHP 8.1+)') }}</option> @endif
                        </select>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="small text-muted">{{ translate('Quality') }}: <strong>{{ get_setting('perf_image_webp_quality', 82) }}</strong></label>
                        <input type="range" name="perf_image_webp_quality" min="50" max="95" value="{{ get_setting('perf_image_webp_quality', 82) }}" class="form-control-range"
                               oninput="this.previousElementSibling.querySelector('strong').textContent=this.value">
                        <small class="text-muted d-flex justify-content-between"><span>50 ({{ translate('smaller') }})</span><span>95 ({{ translate('better quality') }})</span></small>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button class="btn btn-soft-primary btn-sm btn-block" type="submit"><i class="las la-save"></i> {{ translate('Save') }}</button>
                    </div>
                </form>

                <div class="d-flex flex-wrap" style="gap:8px">
                    <form action="{{ route('performance_optimizer.images.webp') }}" method="POST">
                        @csrf<input type="hidden" name="limit" value="100">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="las la-bolt"></i> {{ translate('Backup & Convert All (100)') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.images.avif') }}" method="POST">
                        @csrf<input type="hidden" name="limit" value="50">
                        <button class="btn btn-soft-primary btn-sm" type="submit" @if(!function_exists('imageavif')) disabled title="{{ translate('AVIF not supported on this server') }}" @endif>
                            <i class="las la-image"></i> {{ translate('Convert next 50 → AVIF') }}
                        </button>
                    </form>
                    <form action="{{ route('performance_optimizer.images.restore') }}" method="POST"
                          onsubmit="return confirm('{{ translate('Restore all original images from backup?') }}')">
                        @csrf
                        <button class="btn btn-soft-warning btn-sm" type="submit"><i class="las la-undo"></i> {{ translate('Restore All Originals') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.images.clean_backups') }}" method="POST">
                        @csrf<input type="hidden" name="days" value="30">
                        <button class="btn btn-soft-danger btn-sm" type="submit"><i class="las la-trash"></i> {{ translate('Clean backups > 30 days') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(40,167,69,.1);color:var(--perf-green)"><i class="las la-server"></i></span>{{ translate('Serve WebP Automatically (.htaccess)') }}</h5>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('When enabled, Apache will automatically serve .webp files to browsers that support it — no template changes needed.') }}</p>
                <div class="d-flex align-items-center">
                    <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                        <input type="checkbox" onchange="perfToggle(this, 'perf_image_serve_webp_auto')"
                               @if(get_setting('perf_image_serve_webp_auto') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                    <span>{{ translate('Enable WebP auto-serving') }}</span>
                </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(76,175,80,.1);color:#4caf50"><i class="las la-stopwatch"></i></span>{{ translate('Lazy Load Images, iFrames & Videos') }}</h5>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Load images only when they enter the viewport. Reduces initial page weight significantly.') }}</p>
                <div class="d-flex align-items-center">
                    <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                        <input type="checkbox" onchange="perfToggle(this, 'perf_image_lazyload')"
                               @if(get_setting('perf_image_lazyload') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                    <span>{{ translate('Enable Lazy Loading') }}</span>
                </div>
            </div>
        </div>

        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(255,193,7,.15);color:#b8860b"><i class="las la-star"></i></span>{{ translate('Auto-Preload LCP Hero Image') }}</h5>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" onchange="perfToggle(this, 'perf_lcp_preload_status')"
                           @if(get_setting('perf_lcp_preload_status') == 1) checked @endif>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="perf-section-body">
                <p>{{ translate('Automatically detects the first visible hero image on every page and injects') }} <code>&lt;link rel="preload" fetchpriority="high"&gt;</code> {{ translate('into') }} <code>&lt;head&gt;</code>. {{ translate('The same image gets') }} <code>loading="eager"</code> {{ translate('so the browser fetches it immediately. Directly improves LCP score.') }}</p>
                <div class="alert alert-info py-2 mb-0" style="font-size:12px">
                    <i class="las la-info-circle"></i>
                    {{ translate('Auto-detection scans') }} <code>&lt;body&gt;</code> {{ translate('for the first') }} <code>&lt;img src&gt;</code> {{ translate('that is not a tracking pixel, data URI, or already lazy-loaded. No configuration needed.') }}
                </div>
            </div>
        </div>

        @if(!empty($oversized ?? []))
        <div class="perf-section">
            <div class="perf-section-header">
                <h5><span class="perf-section-icon" style="background:rgba(220,53,69,.1);color:var(--perf-red)"><i class="las la-exclamation-triangle"></i></span>{{ translate('Oversized Images Detected') }}</h5>
                <span class="perf-pill">{{ count($oversized) }} {{ translate('files') }}</span>
            </div>
            <div class="perf-section-body p-0">
                <table class="perf-table">
                    <thead><tr><th>{{ translate('File') }}</th><th>{{ translate('Size') }}</th><th>{{ translate('Dimensions') }}</th></tr></thead>
                    <tbody>
                        @foreach($oversized as $o)
                            <tr>
                                <td><small>{{ $o['relative'] }}</small></td>
                                <td>{{ $o['size_human'] }}</td>
                                <td>{{ $o['width'] }} × {{ $o['height'] }}px</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="perf-section">
            <div class="perf-section-header"><h5><i class="las la-chart-bar"></i> {{ translate('Image Statistics') }}</h5></div>
            <div class="perf-section-body p-0">
                <table class="perf-table">
                    <tbody>
                        <tr><td>{{ translate('Total Images') }}</td><td class="text-right"><strong>{{ number_format($stats['total']) }}</strong></td></tr>
                        <tr><td>{{ translate('WebP Converted') }}</td><td class="text-right text-success"><strong>{{ number_format($stats['converted']) }}</strong></td></tr>
                        <tr><td>{{ translate('Not Converted') }}</td><td class="text-right text-warning"><strong>{{ number_format($stats['not_converted']) }}</strong></td></tr>
                        <tr><td>{{ translate('Space Saved') }}</td><td class="text-right text-info"><strong>{{ $stats['space_saved'] }}</strong></td></tr>
                        <tr><td>{{ translate('Backup Size') }}</td><td class="text-right"><strong>{{ $stats['backup_size'] }}</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="perf-tips">
            <div class="perf-tips-header"><i class="las la-lightbulb"></i> {{ translate('Image Tips') }}</div>
            <div class="perf-tips-body">
                <p><strong>{{ translate('Quality 82') }}</strong> {{ translate('is the sweet spot — visually indistinguishable from the original, ~30% smaller.') }}</p>
                <p>{{ translate('Originals are backed up to') }} <code>storage/app/performance-backup/</code> {{ translate('before conversion. Use "Restore All" to roll back.') }}</p>
                <p><strong>{{ translate('AVIF') }}</strong> {{ translate('is ~20% smaller than WebP but slower to encode and needs PHP 8.1+ GD with AVIF.') }}</p>
                <p>{{ translate('After bulk conversion, run') }} <strong>{{ translate('Clear page cache') }}</strong> {{ translate('so HTML re-renders with new image URLs.') }}</p>
            </div>
        </div>
    </div>
</div>
