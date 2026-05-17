<div class="perf-section">
    <div class="perf-section-header"><h5><i class="las la-cloud"></i> {{ translate('Edge / CDN Integration') }}</h5></div>
    <div class="perf-section-body">
        <p class="mb-0">{{ translate('Connect a CDN provider to cache HTML and assets at the edge. When you save changes to products or categories, the addon automatically purges the relevant cache (if Auto-Purge is enabled).') }}</p>
    </div>
</div>

<div class="row">
    {{-- Cloudflare ───────────────────────────────────────────────── --}}
    <div class="col-md-6">
        <div class="perf-section">
            <div class="perf-section-header">
                <h5>
                    <span class="perf-section-icon" style="background:rgba(243,148,30,.1);color:#f3941e"><i class="las la-cloud"></i></span>
                    Cloudflare
                </h5>
                @if($cf_configured)
                    <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.edge.cloudflare.save') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                                <input type="hidden" name="perf_cloudflare_status" value="0">
                                <input type="checkbox" name="perf_cloudflare_status" value="1"
                                       @if(get_setting('perf_cloudflare_status') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                            <strong>{{ translate('Enable Cloudflare integration') }}</strong>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Zone ID') }}</label>
                        <input type="text" name="perf_cloudflare_zone_id" class="form-control form-control-sm"
                               value="{{ get_setting('perf_cloudflare_zone_id') }}"
                               placeholder="023e105f4ecef8ad9ca31a8372d0c353">
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('API Token (Zone.Cache Purge permission)') }}</label>
                        <input type="password" name="perf_cloudflare_api_token" class="form-control form-control-sm"
                               value="{{ get_setting('perf_cloudflare_api_token') }}"
                               autocomplete="new-password" placeholder="••••••••">
                        <small class="text-muted">
                            {{ translate('Create at Cloudflare dashboard → My Profile → API Tokens. Use "Edit zone DNS" template or a custom token with Zone.Cache Purge + Zone.Zone Read.') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <input type="hidden" name="perf_cloudflare_auto_purge" value="0">
                            <input type="checkbox" name="perf_cloudflare_auto_purge" value="1" class="mr-2"
                                   @if(get_setting('perf_cloudflare_auto_purge', 1) == 1) checked @endif>
                            {{ translate('Auto-purge on product / category save') }}
                        </label>
                    </div>

                    <div class="d-flex">
                        <button class="btn btn-soft-primary btn-sm mr-2"><i class="las la-save"></i> {{ translate('Save') }}</button>
                    </div>
                </form>
                <hr>
                <div class="d-flex">
                    <form action="{{ route('performance_optimizer.edge.cloudflare.test') }}" method="POST" class="d-inline mr-2">
                        @csrf
                        <button class="btn btn-soft-secondary btn-sm" type="submit"><i class="las la-vial"></i> {{ translate('Test Connection') }}</button>
                    </form>
                    <form action="{{ route('performance_optimizer.edge.cloudflare.purge') }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-soft-danger btn-sm" type="submit"
                                onclick="return confirm('{{ translate('Purge ALL Cloudflare cache? Visitors will see slower responses until cache rebuilds.') }}');">
                            <i class="las la-trash-alt"></i> {{ translate('Purge All') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Bunny.net ─────────────────────────────────────────────────── --}}
    <div class="col-md-6">
        <div class="perf-section">
            <div class="perf-section-header">
                <h5>
                    <span class="perf-section-icon" style="background:rgba(255,138,0,.1);color:#ff8a00"><i class="las la-rabbit"></i></span>
                    Bunny.net
                </h5>
                @if($bn_configured)
                    <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                <form action="{{ route('performance_optimizer.edge.bunny.save') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                                <input type="hidden" name="perf_bunny_status" value="0">
                                <input type="checkbox" name="perf_bunny_status" value="1"
                                       @if(get_setting('perf_bunny_status') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                            <strong>{{ translate('Enable Bunny.net integration') }}</strong>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Pull Zone ID') }}</label>
                        <input type="text" name="perf_bunny_pull_zone" class="form-control form-control-sm"
                               value="{{ get_setting('perf_bunny_pull_zone') }}" placeholder="123456">
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('API Key') }}</label>
                        <input type="password" name="perf_bunny_api_key" class="form-control form-control-sm"
                               value="{{ get_setting('perf_bunny_api_key') }}"
                               autocomplete="new-password" placeholder="••••••••">
                        <small class="text-muted">{{ translate('bunny.net → Account Settings → API Access.') }}</small>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('CDN Hostname (optional)') }}</label>
                        <input type="text" name="perf_bunny_cdn_hostname" class="form-control form-control-sm"
                               value="{{ get_setting('perf_bunny_cdn_hostname') }}"
                               placeholder="bhs.b-cdn.net">
                    </div>

                    <div class="d-flex">
                        <button class="btn btn-soft-primary btn-sm mr-2"><i class="las la-save"></i> {{ translate('Save') }}</button>
                    </div>
                </form>
                <hr>
                <form action="{{ route('performance_optimizer.edge.bunny.purge') }}" method="POST">
                    @csrf
                    <button class="btn btn-soft-danger btn-sm" type="submit"
                            onclick="return confirm('{{ translate('Purge ALL Bunny.net cache?') }}');">
                        <i class="las la-trash-alt"></i> {{ translate('Purge All') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- CloudFront ────────────────────────────────────────────────── --}}
    <div class="col-md-6">
        <div class="perf-section">
            <div class="perf-section-header">
                <h5>
                    <span class="perf-section-icon" style="background:rgba(255,153,0,.1);color:#f90"><i class="lab la-aws"></i></span>
                    AWS CloudFront
                </h5>
                @if($cfront_configured)
                    <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                @elseif(!$aws_sdk_present)
                    <span class="badge badge-soft-warning">{{ translate('AWS SDK missing') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                @if(!$aws_sdk_present)
                    <div class="alert alert-warning small">
                        {{ translate('AWS SDK is not installed. Run') }} <code>composer require aws/aws-sdk-php</code>
                        {{ translate('on the server to enable CloudFront.') }}
                    </div>
                @endif
                <form action="{{ route('performance_optimizer.edge.cloudfront.save') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                                <input type="hidden" name="perf_cloudfront_status" value="0">
                                <input type="checkbox" name="perf_cloudfront_status" value="1"
                                       @if(get_setting('perf_cloudfront_status') == 1) checked @endif
                                       @if(!$aws_sdk_present) disabled @endif>
                                <span class="slider round"></span>
                            </label>
                            <strong>{{ translate('Enable CloudFront integration') }}</strong>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Distribution ID') }}</label>
                        <input type="text" name="perf_cloudfront_distribution" class="form-control form-control-sm"
                               value="{{ get_setting('perf_cloudfront_distribution') }}" placeholder="E2QWRUHEXAMPLE">
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Access Key ID') }}</label>
                        <input type="text" name="perf_cloudfront_access_key" class="form-control form-control-sm"
                               value="{{ get_setting('perf_cloudfront_access_key') }}" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('Secret Access Key') }}</label>
                        <input type="password" name="perf_cloudfront_secret" class="form-control form-control-sm"
                               value="{{ get_setting('perf_cloudfront_secret') }}" autocomplete="new-password">
                    </div>

                    <div class="d-flex">
                        <button class="btn btn-soft-primary btn-sm mr-2"><i class="las la-save"></i> {{ translate('Save') }}</button>
                    </div>
                </form>
                <hr>
                <form action="{{ route('performance_optimizer.edge.cloudfront.invalidate') }}" method="POST">
                    @csrf
                    <button class="btn btn-soft-danger btn-sm" type="submit"
                            onclick="return confirm('{{ translate('Invalidate /* on CloudFront? AWS bills $0.005 per path after the first 1000/month.') }}');">
                        <i class="las la-trash-alt"></i> {{ translate('Invalidate /*') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Image CDN ──────────────────────────────────────────────────── --}}
    <div class="col-md-6">
        <div class="perf-section">
            <div class="perf-section-header">
                <h5>
                    <span class="perf-section-icon" style="background:rgba(0,123,255,.1);color:#007bff"><i class="las la-image"></i></span>
                    {{ translate('Image CDN') }}
                </h5>
                @if((int) get_setting('perf_image_cdn_status') === 1)
                    <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                @endif
            </div>
            <div class="perf-section-body">
                <p class="small text-muted">{{ translate('Rewrites image URLs in HTML to load through a CDN host (e.g. ImageKit, Cloudinary, Bunny). Only applies to') }} <code>/uploads/*</code> {{ translate('and') }} <code>/public/uploads/*</code>.</p>
                <form action="{{ route('performance_optimizer.edge.image_cdn.save') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="d-flex align-items-center">
                            <label class="aiz-switch aiz-switch-success mb-0 mr-2">
                                <input type="hidden" name="perf_image_cdn_status" value="0">
                                <input type="checkbox" name="perf_image_cdn_status" value="1"
                                       @if(get_setting('perf_image_cdn_status') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                            <strong>{{ translate('Enable Image CDN rewrite') }}</strong>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">{{ translate('CDN URL prefix') }}</label>
                        <input type="text" name="perf_image_cdn_url" class="form-control form-control-sm"
                               value="{{ get_setting('perf_image_cdn_url') }}" placeholder="https://bhs.b-cdn.net">
                        <small class="text-muted">{{ translate('Full origin (with scheme). Trailing slash is normalized automatically.') }}</small>
                    </div>
                    <button class="btn btn-soft-primary btn-sm"><i class="las la-save"></i> {{ translate('Save') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
