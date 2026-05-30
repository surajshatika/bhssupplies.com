@extends('backend.layouts.app')

@section('content')
@php
    $scoreBadge = function ($score) {
        if ($score === null) return 'secondary';
        if ($score >= 80) return 'success';
        if ($score >= 50) return 'warning';
        return 'danger';
    };
@endphp

<style>
.ai-board-stat { padding: 1rem; border-radius: .5rem; background: #fff; border: 1px solid #eaeaea; }
.ai-board-stat .stat-num { font-size: 1.75rem; font-weight: 700; line-height: 1; }
.ai-board-stat .stat-label { font-size: .75rem; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }
.score-ring { display: inline-block; width: 44px; height: 44px; border-radius: 50%; line-height: 44px; text-align: center; color: #fff; font-weight: 700; font-size: .9rem; }
.score-ring.success { background: #1cc88a; }
.score-ring.warning { background: #f6c23e; }
.score-ring.danger  { background: #e74a3b; }
.score-ring.secondary { background: #b0b4bc; }
.tab-pill { padding: .4rem .85rem; border-radius: 20px; background: #f3f4f6; color: #374151; font-weight: 500; font-size: .85rem; margin-right: .35rem; display: inline-block; }
.tab-pill.active { background: #4e73df; color: #fff; }
.badge-soft-success { background: rgba(28,200,138,.15); color: #1cc88a; }
.badge-soft-danger  { background: rgba(231,74,59,.15); color: #e74a3b; }
.fix-drawer { position: fixed; top: 0; right: -560px; bottom: 0; width: 560px; max-width: 100%; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,.12); z-index: 1080; transition: right .25s ease; overflow-y: auto; }
.fix-drawer.open { right: 0; }
.fix-drawer .drawer-header { padding: 1rem 1.25rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.fix-drawer .drawer-body { padding: 1.25rem; }
.fix-drawer-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 1070; display: none; }
.fix-drawer-backdrop.open { display: block; }
.issues-list { font-size: .82rem; }
.issues-list li { padding: .15rem 0; }
.diff-panel { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: .35rem; padding: .75rem 1rem; font-size: .8rem; }
.diff-panel .field-label { font-weight: 600; color: #374151; }
.diff-panel .field-value { white-space: pre-wrap; word-break: break-word; }

.bulk-bar { position: sticky; bottom: 0; z-index: 30; background: #fff; border-top: 1px solid #e5e7eb; padding: .65rem 1rem; box-shadow: 0 -2px 12px rgba(0,0,0,.06); display: none; }
.bulk-bar.show { display: flex; align-items: center; gap: .75rem; }
.bulk-bar .bulk-count { font-weight: 600; }
.progress-modal-body .progress { height: 22px; border-radius: 11px; background: #eef0f4; }
.progress-modal-body .progress-bar { font-weight: 600; transition: width .35s ease; }
.modal-cost-row { display: flex; justify-content: space-between; padding: .35rem 0; font-size: .9rem; }
.modal-cost-row .key { color: #6b7280; }
.modal-cost-row .val { font-weight: 600; }
</style>

<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-7">
            <h1 class="h3 mb-0">{{ translate('AI SEO Board') }}</h1>
            <p class="text-muted mb-0 small">{{ translate('Scan every product, category, page and blog post — see what is missing and let AI fill the gaps.') }}</p>
        </div>
        <div class="col-md-5 text-md-right mt-3 mt-md-0">
            <div class="d-inline-flex align-items-center" style="gap:.35rem;">
                <select id="bulkLimit" class="form-control form-control-sm" style="width:auto;" title="{{ translate('How many matching URLs to fix in this run') }}">
                    <option value="100">100 {{ translate('per run') }}</option>
                    <option value="250">250 {{ translate('per run') }}</option>
                    <option value="500" selected>500 {{ translate('per run') }}</option>
                    <option value="1000">1000 {{ translate('per run') }}</option>
                </select>
                <button type="button" class="btn btn-success btn-sm js-bulk-trigger" data-mode="filtered">
                    <i class="las la-magic"></i> {{ translate('AI Fix All Filtered') }}
                </button>
            </div>
            <a href="{{ route('admin.seo.ai_board.index', ['type' => $type]) }}" class="btn btn-soft-secondary btn-sm ml-1">
                <i class="las la-sync"></i> {{ translate('Refresh') }}
            </a>
            <a href="{{ route('admin.seo-suite.settings.view') }}" class="btn btn-soft-primary btn-sm ml-1">
                <i class="las la-cog"></i> {{ translate('Settings') }}
            </a>
        </div>
    </div>
</div>

@include('backend.seo.partials.suite_nav')

<div class="alert alert-info py-2 small">
    <i class="las la-shield-alt mr-1"></i>
    {{ translate('Protection active: completed SEO URLs are not selected by autopilot. Use filters or selected rows only when you intentionally want to review specific URLs.') }}
</div>

{{-- Stat row --}}
<div class="row gutters-8 mb-3">
    <div class="col-6 col-md-3 col-xl mb-2">
        <div class="ai-board-stat">
            <div class="stat-num">{{ number_format($summary['total']) }}</div>
            <div class="stat-label">{{ translate('Total entities') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
        <div class="ai-board-stat">
            <div class="stat-num text-success">{{ $summary['avg_score'] }}/100</div>
            <div class="stat-label">{{ translate('Avg SEO score') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
        <div class="ai-board-stat">
            <div class="stat-num text-danger">{{ number_format($summary['critical']) }}</div>
            <div class="stat-label">{{ translate('Critical (<50)') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
        <div class="ai-board-stat">
            <div class="stat-num text-warning">{{ number_format($summary['warning']) }}</div>
            <div class="stat-label">{{ translate('Warning (50–79)') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl mb-2">
        <div class="ai-board-stat">
            <div class="stat-num text-info">{{ number_format($summary['good']) }}</div>
            <div class="stat-label">{{ translate('Good (80+)') }}</div>
        </div>
    </div>
</div>

{{-- Type tabs --}}
<div class="mb-3">
    @foreach (['product','category','page','blog'] as $tabType)
        <a href="{{ route('admin.seo.ai_board.index', array_merge(request()->except(['page','type']), ['type' => $tabType])) }}"
           class="tab-pill {{ $type === $tabType ? 'active' : '' }}">
            {{ ucfirst($tabType) }}s
            <span class="ml-1" style="opacity:.7;">{{ number_format($typeCounts[$tabType] ?? 0) }}</span>
        </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.seo.ai_board.index') }}" class="card mb-3">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="card-body py-3">
        <div class="row gutters-8 align-items-end">
            <div class="col-md-3">
                <label class="small mb-1">{{ translate('Search') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control form-control-sm" placeholder="{{ translate('Name or title…') }}">
            </div>
            <div class="col-md-2">
                <label class="small mb-1">{{ translate('Missing') }}</label>
                <select name="missing" class="form-control form-control-sm">
                    <option value="">{{ translate('Any') }}</option>
                    <option value="meta"   {{ $filters['missing'] === 'meta'   ? 'selected' : '' }}>{{ translate('No meta') }}</option>
                    <option value="og"     {{ $filters['missing'] === 'og'     ? 'selected' : '' }}>{{ translate('No OG image') }}</option>
                    <option value="schema" {{ $filters['missing'] === 'schema' ? 'selected' : '' }}>{{ translate('No schema') }}</option>
                    <option value="focus"  {{ $filters['missing'] === 'focus'  ? 'selected' : '' }}>{{ translate('No focus KW') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-1">{{ translate('Score range') }}</label>
                <div class="d-flex" style="gap: .25rem;">
                    <input type="number" min="0" max="100" name="min_score" value="{{ $filters['min_score'] }}" class="form-control form-control-sm" placeholder="min">
                    <input type="number" min="0" max="100" name="max_score" value="{{ $filters['max_score'] }}" class="form-control form-control-sm" placeholder="max">
                </div>
            </div>
            <div class="col-md-2">
                <label class="small mb-1">{{ translate('Sort') }}</label>
                <select name="sort" class="form-control form-control-sm">
                    <option value="recent"     {{ $filters['sort'] === 'recent'     ? 'selected' : '' }}>{{ translate('Recently updated') }}</option>
                    <option value="score_asc"  {{ $filters['sort'] === 'score_asc'  ? 'selected' : '' }}>{{ translate('Lowest score first') }}</option>
                    <option value="score_desc" {{ $filters['sort'] === 'score_desc' ? 'selected' : '' }}>{{ translate('Highest score first') }}</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="small mb-1">{{ translate('Per page') }}</label>
                <select name="per_page" class="form-control form-control-sm">
                    @foreach ([25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 text-right">
                <button class="btn btn-primary btn-sm w-100"><i class="las la-filter"></i> {{ translate('Apply') }}</button>
            </div>
        </div>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" class="js-select-all" title="{{ translate('Select all on this page') }}">
                        </th>
                        <th style="width:60px;">{{ translate('Score') }}</th>
                        <th>{{ translate('Title') }}</th>
                        <th style="width:120px;">{{ translate('Type') }}</th>
                        <th style="width:160px;">{{ translate('Signals') }}</th>
                        <th>{{ translate('Top issues') }}</th>
                        <th style="width:160px;" class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paginator->items() as $row)
                        @php $color = $scoreBadge($row['score']); @endphp
                        <tr data-row-id="{{ $row['type'] }}-{{ $row['id'] }}">
                            <td>
                                <input type="checkbox" class="js-row-check" data-type="{{ $row['type'] }}" data-id="{{ $row['id'] }}">
                            </td>
                            <td>
                                <span class="score-ring {{ $color }}" title="Grade {{ $row['grade'] }}">{{ $row['score'] }}</span>
                            </td>
                            <td>
                                <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="font-weight-600 text-dark">{{ $row['title'] }}</a>
                                <div class="text-muted small">{{ $row['url'] }}</div>
                            </td>
                            <td>
                                <span class="badge badge-soft-secondary">{{ $row['type_label'] }}</span>
                                <div class="text-muted small">{{ translate('Updated') }} {{ $row['updated_at'] }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $row['has_meta']     ? 'badge-soft-success' : 'badge-soft-danger' }}" title="Meta">M</span>
                                <span class="badge {{ $row['has_og']       ? 'badge-soft-success' : 'badge-soft-danger' }}" title="OG image">OG</span>
                                <span class="badge {{ $row['has_schema']   ? 'badge-soft-success' : 'badge-soft-danger' }}" title="Schema">S</span>
                                <span class="badge {{ $row['has_focus_kw'] ? 'badge-soft-success' : 'badge-soft-danger' }}" title="Focus keyword">K</span>
                            </td>
                            <td>
                                @if (count($row['issues']))
                                    <ul class="issues-list mb-0 pl-3">
                                        @foreach (array_slice($row['issues'], 0, 3) as $issue)
                                            <li>{{ $issue }}</li>
                                        @endforeach
                                        @if (count($row['issues']) > 3)
                                            <li class="text-muted">{{ translate('and') }} {{ count($row['issues']) - 3 }} {{ translate('more…') }}</li>
                                        @endif
                                    </ul>
                                @else
                                    <span class="text-success small">{{ translate('No critical issues') }}</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                <button class="btn btn-soft-primary btn-sm js-preview-btn"
                                        data-type="{{ $row['type'] }}"
                                        data-id="{{ $row['id'] }}"
                                        data-title="{{ $row['title'] }}"
                                        title="{{ translate('Preview & edit AI suggestions before applying') }}">
                                    <i class="las la-eye"></i> {{ translate('Preview') }}
                                </button>
                                <button class="btn btn-primary btn-sm js-fix-btn"
                                        data-type="{{ $row['type'] }}"
                                        data-id="{{ $row['id'] }}"
                                        data-title="{{ $row['title'] }}"
                                        title="{{ translate('Generate and apply in one click') }}">
                                    <i class="las la-magic"></i> {{ translate('AI Fix') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                {{ translate('No entities match the current filters.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $paginator->withQueryString()->links() }}
    </div>
</div>

{{-- Sticky bulk-action bar --}}
<div class="bulk-bar" id="bulkBar">
    <span class="bulk-count" id="bulkCount">0</span>
    <span class="text-muted">{{ translate('selected') }}</span>
    <button type="button" class="btn btn-success btn-sm ml-auto js-bulk-trigger" data-mode="selected">
        <i class="las la-magic"></i> {{ translate('AI Fix Selected') }}
    </button>
    <button type="button" class="btn btn-soft-secondary btn-sm js-clear-selection">
        {{ translate('Clear') }}
    </button>
</div>

{{-- Cost preview modal --}}
<div class="modal fade" id="bulkCostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="las la-coins mr-1"></i> {{ translate('Confirm AI Bulk Fix') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="bulkCostLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-3 mb-0">{{ translate('Estimating cost…') }}</p>
                </div>
                <div id="bulkCostBody" class="d-none">
                    <div class="modal-cost-row"><span class="key">{{ translate('Entities to fix') }}</span><span class="val" id="bcCount">—</span></div>
                    <div class="modal-cost-row"><span class="key">{{ translate('AI provider') }}</span><span class="val" id="bcProvider">—</span></div>
                    <div class="modal-cost-row"><span class="key">{{ translate('Mode') }}</span><span class="val" id="bcMode">—</span></div>
                    <div class="modal-cost-row"><span class="key">{{ translate('Estimated cost') }}</span><span class="val text-primary" id="bcCost">—</span></div>
                    <div class="alert alert-warning small mb-0 mt-3 d-none" id="bcSyncWarn">
                        <i class="las la-exclamation-triangle"></i>
                        {{ translate('Queue is set to sync, so large bulk fixes will be processed by cron in small chunks. Keep the scheduler cron active.') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="bulkConfirmRun" disabled>
                    <i class="las la-play mr-1"></i> {{ translate('Start Bulk Fix') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Progress modal --}}
<div class="modal fade" id="bulkProgressModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="las la-magic mr-1"></i> {{ translate('Bulk AI Fix in Progress') }}</h5>
            </div>
            <div class="modal-body progress-modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar bg-primary" id="bpBar" role="progressbar" style="width:0%;">0%</div>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <div><strong id="bpProcessed">0</strong> / <span id="bpTotal">0</span> {{ translate('processed') }}</div>
                    <div>
                        <span class="badge badge-success" id="bpSucceeded">0</span>
                        <span class="badge badge-warning"  id="bpSkipped">0</span>
                        <span class="badge badge-danger"   id="bpFailed">0</span>
                    </div>
                </div>
                <div class="text-muted small">{{ translate('Currently') }}: <span id="bpCurrent" class="font-weight-600">—</span></div>
                <div class="text-muted small mt-2">{{ translate('Cost so far') }}: <span class="font-weight-600" id="bpCost">$0.0000</span></div>
                <div id="bpErrors" class="alert alert-danger small mt-3 d-none"></div>
                <div id="bpDone" class="alert alert-success small mt-3 d-none">
                    <i class="las la-check-circle mr-1"></i> {{ translate('Batch finished — reload the page to see updated scores.') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-danger btn-sm" id="bpCancelBtn">{{ translate('Cancel batch') }}</button>
                <button type="button" class="btn btn-primary btn-sm d-none" id="bpReloadBtn" onclick="window.location.reload()">{{ translate('Reload page') }}</button>
                <button type="button" class="btn btn-soft-secondary btn-sm d-none" id="bpCloseBtn" data-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Drawer --}}
<div class="fix-drawer-backdrop js-close-drawer"></div>
<div class="fix-drawer" id="fixDrawer">
    <div class="drawer-header">
        <h5 class="mb-0">{{ translate('AI Fix Preview') }}</h5>
        <button class="btn btn-link p-0 text-muted js-close-drawer" type="button"><i class="las la-times la-lg"></i></button>
    </div>
    <div class="drawer-body">
        <div id="fixDrawerLoading" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-3 mb-0">{{ translate('Asking AI to generate SEO fixes…') }}</p>
        </div>
        <div id="fixDrawerError" class="alert alert-danger d-none"></div>
        <div id="fixDrawerResult" class="d-none">
            <p class="mb-1"><strong id="fixDrawerTitle"></strong></p>
            <div class="mb-3">
                <span class="badge badge-soft-secondary mr-1"><span id="fixDrawerSource"></span></span>
                <span class="badge badge-soft-success">
                    {{ translate('Score') }}:
                    <span id="fixScoreBefore"></span> → <span id="fixScoreAfter"></span>
                </span>
            </div>
            <div id="fixDrawerApplied"></div>

            {{-- Live SERP + social previews (pure client-side, no extra API call) --}}
            <div id="fixDrawerPreviews" class="mt-4 d-none">
                <div class="preview-label"><i class="lab la-google mr-1"></i>{{ translate('Google preview') }}</div>
                <div class="serp-card">
                    <div class="serp-url" id="pvSerpUrl"></div>
                    <div class="serp-title" id="pvSerpTitle"></div>
                    <div class="serp-desc" id="pvSerpDesc"></div>
                </div>

                <div class="preview-label mt-3"><i class="lab la-facebook mr-1"></i>{{ translate('Social share preview') }}</div>
                <div class="social-card">
                    <div class="social-img" id="pvSocialImg"></div>
                    <div class="social-meta">
                        <div class="social-host" id="pvSocialHost"></div>
                        <div class="social-title" id="pvSocialTitle"></div>
                        <div class="social-desc" id="pvSocialDesc"></div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="#" id="pvGoogleTest" target="_blank" rel="noopener" class="btn btn-sm btn-soft-info">
                        <i class="las la-vial mr-1"></i>{{ translate('Test rich results with Google') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Preview & Edit form (shown for the Preview flow) --}}
        <div id="previewForm" class="d-none">
            <p class="mb-1"><strong id="previewTitle"></strong></p>
            <div class="mb-3">
                <span class="badge badge-soft-secondary mr-1" id="previewSource"></span>
                <span class="badge badge-soft-info">{{ translate('Current score') }}: <span id="previewScore"></span></span>
            </div>
            <div class="alert alert-soft-info small py-2">
                <i class="las la-info-circle mr-1"></i>{{ translate('Edit any suggestion below, then Apply. Only empty fields are written — curated values are never overwritten.') }}
            </div>

            <div class="form-group" id="pf-title-group">
                <label class="field-label mb-1">{{ translate('Meta title') }} <small class="text-muted">(<span id="pf-title-count">0</span>/60)</small></label>
                <input type="text" id="pf-title" class="form-control form-control-sm" maxlength="70">
            </div>
            <div class="form-group" id="pf-desc-group">
                <label class="field-label mb-1">{{ translate('Meta description') }} <small class="text-muted">(<span id="pf-desc-count">0</span>/160)</small></label>
                <textarea id="pf-desc" class="form-control form-control-sm" rows="3" maxlength="320"></textarea>
            </div>
            <div class="form-group" id="pf-focus-group">
                <label class="field-label mb-1">{{ translate('Focus keyword') }}</label>
                <input type="text" id="pf-focus" class="form-control form-control-sm">
            </div>
            <div class="form-group" id="pf-secondary-group">
                <label class="field-label mb-1">{{ translate('Secondary keywords') }} <small class="text-muted">{{ translate('(comma separated)') }}</small></label>
                <textarea id="pf-secondary" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="form-check mb-3" id="pf-schema-group">
                <input class="form-check-input" type="checkbox" id="pf-schema" checked>
                <label class="form-check-label small" for="pf-schema">{{ translate('Generate structured data (schema) for this page') }}</label>
            </div>

            <button type="button" class="btn btn-success btn-block" id="pf-apply">
                <i class="las la-check mr-1"></i>{{ translate('Apply approved values') }}
                <span id="pf-apply-spinner" class="spinner-border spinner-border-sm ml-1 d-none"></span>
            </button>
        </div>
    </div>
</div>

<style>
.preview-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; font-weight:600; margin-bottom:.35rem; }
.serp-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; background:#fff; font-family:Arial,sans-serif; }
.serp-url { color:#202124; font-size:12px; line-height:1.3; }
.serp-title { color:#1a0dab; font-size:18px; line-height:1.3; margin:2px 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.serp-desc { color:#4d5156; font-size:13px; line-height:1.45; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.social-card { border:1px solid #dadde1; border-radius:8px; overflow:hidden; background:#fff; }
.social-img { height:140px; background:#eceff3 center/cover no-repeat; display:flex; align-items:center; justify-content:center; color:#9aa0a6; font-size:12px; }
.social-meta { padding:10px 12px; background:#f2f3f5; }
.social-host { color:#606770; font-size:11px; text-transform:uppercase; letter-spacing:.03em; }
.social-title { color:#1d2129; font-size:15px; font-weight:600; line-height:1.3; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.social-desc { color:#606770; font-size:12px; line-height:1.4; margin-top:2px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.serp-title.too-long, .social-title.too-long { color:#b91c1c; }
</style>

@endsection

@section('script')
<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const fixEndpoint = '{{ route("admin.seo.ai_board.fix") }}';
    const previewEndpoint = '{{ route("admin.seo.ai_board.preview") }}';
    const applyApprovedEndpoint = '{{ route("admin.seo.ai_board.apply_approved") }}';
    let previewCtx = null; // {type, id}

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function readJsonResponse(response) {
        return response.text().then(function (text) {
            let payload = null;
            try {
                payload = text ? JSON.parse(text) : {};
            } catch (e) {
                const clean = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 240);
                throw new Error(clean || ('Server returned non-JSON response (' + response.status + ')'));
            }

            if (!response.ok || payload.success === false) {
                throw new Error(payload.error || payload.message || ('Request failed (' + response.status + ')'));
            }

            return payload;
        });
    }

    function openDrawer() {
        document.getElementById('fixDrawer').classList.add('open');
        document.querySelector('.fix-drawer-backdrop').classList.add('open');
    }

    function closeDrawer() {
        document.getElementById('fixDrawer').classList.remove('open');
        document.querySelector('.fix-drawer-backdrop').classList.remove('open');
        document.getElementById('fixDrawerError').classList.add('d-none');
        document.getElementById('fixDrawerResult').classList.add('d-none');
        document.getElementById('previewForm').classList.add('d-none');
    }

    function showLoading(title) {
        document.getElementById('fixDrawerTitle').textContent = title;
        document.getElementById('fixDrawerLoading').classList.remove('d-none');
        document.getElementById('fixDrawerError').classList.add('d-none');
        document.getElementById('fixDrawerResult').classList.add('d-none');
        document.getElementById('fixDrawerPreviews').classList.add('d-none');
        document.getElementById('previewForm').classList.add('d-none');
    }

    function showError(msg) {
        document.getElementById('fixDrawerLoading').classList.add('d-none');
        const e = document.getElementById('fixDrawerError');
        e.textContent = msg;
        e.classList.remove('d-none');
    }

    function showResult(data) {
        document.getElementById('fixDrawerLoading').classList.add('d-none');
        document.getElementById('fixDrawerResult').classList.remove('d-none');
        document.getElementById('fixDrawerSource').textContent = data.source === 'ai' ? 'AI generated' : 'Template fallback';
        document.getElementById('fixScoreBefore').textContent = data.score_before;
        document.getElementById('fixScoreAfter').textContent  = data.score_after;

        renderPreviews(data.row || {});

        const apEl = document.getElementById('fixDrawerApplied');
        apEl.innerHTML = '';

        const applied = data.applied || {};
        if (Object.keys(applied).length === 0) {
            apEl.innerHTML = '<p class="text-muted">{{ translate("Nothing to fix — this entity already has complete SEO data.") }}</p>';
            return;
        }

        const panel = document.createElement('div');
        panel.className = 'diff-panel';
        for (const [field, value] of Object.entries(applied)) {
            const row = document.createElement('div');
            row.className = 'mb-2';
            row.innerHTML = '<div class="field-label">' + field + '</div><div class="field-value">' + (value || '').toString() + '</div>';
            panel.appendChild(row);
        }
        apEl.appendChild(panel);
    }

    function renderPreviews(row) {
        const wrap = document.getElementById('fixDrawerPreviews');
        if (!row || (!row.meta_title && !row.title)) { wrap.classList.add('d-none'); return; }
        wrap.classList.remove('d-none');

        const title = row.meta_title || row.title || '';
        const desc  = row.meta_description || '{{ translate('Add a meta description to improve click-through from search results.') }}';
        const url   = row.url || '{{ url('/') }}';
        let host = url, path = '';
        try { const u = new URL(url); host = u.hostname.replace(/^www\./, ''); path = u.pathname.replace(/\//g, ' › ').replace(/\s›\s$/, ''); } catch (e) {}

        // Google SERP
        document.getElementById('pvSerpUrl').textContent   = host + path;
        const st = document.getElementById('pvSerpTitle');
        st.textContent = title;
        st.classList.toggle('too-long', title.length > 60);
        document.getElementById('pvSerpDesc').textContent  = desc;

        // Social card
        const img = document.getElementById('pvSocialImg');
        if (row.og_image) { img.style.backgroundImage = 'url("' + row.og_image + '")'; img.textContent = ''; }
        else { img.style.backgroundImage = 'none'; img.textContent = '{{ translate('No OG image set') }}'; }
        document.getElementById('pvSocialHost').textContent = host;
        const st2 = document.getElementById('pvSocialTitle');
        st2.textContent = title;
        st2.classList.toggle('too-long', title.length > 70);
        document.getElementById('pvSocialDesc').textContent = desc;

        // Test-with-Google deep link
        document.getElementById('pvGoogleTest').href = 'https://search.google.com/test/rich-results?url=' + encodeURIComponent(url);
    }

    function showPreviewForm(data) {
        document.getElementById('fixDrawerLoading').classList.add('d-none');
        document.getElementById('fixDrawerResult').classList.add('d-none');
        document.getElementById('fixDrawerError').classList.add('d-none');
        document.getElementById('previewForm').classList.remove('d-none');

        previewCtx = { type: data.type, id: data.id };
        const s = data.suggestions || {};
        const cur = data.current || {};

        document.getElementById('previewTitle').textContent = data.title || '';
        document.getElementById('previewSource').textContent = data.source === 'ai' ? 'AI generated' : 'Template fallback';
        document.getElementById('previewScore').textContent = data.score_before;

        // Show a field only when a suggestion exists (i.e. the field is empty/missing).
        toggleField('pf-title-group', 'pf-title', s.meta_title, cur.meta_title);
        toggleField('pf-desc-group', 'pf-desc', s.meta_description, cur.meta_description);
        toggleField('pf-focus-group', 'pf-focus', s.focus_keyword, cur.focus_keyword);
        toggleField('pf-secondary-group', 'pf-secondary', s.secondary_keywords, null);

        document.getElementById('pf-schema-group').classList.toggle('d-none', !('schema' in s));
        document.getElementById('pf-schema').checked = !!s.schema;

        updateCount('pf-title', 'pf-title-count');
        updateCount('pf-desc', 'pf-desc-count');
    }

    function toggleField(groupId, inputId, suggestion, current) {
        const group = document.getElementById(groupId);
        const has = suggestion !== undefined && suggestion !== null && suggestion !== '';
        group.classList.toggle('d-none', !has);
        if (has) {
            document.getElementById(inputId).value = suggestion;
        }
    }

    function updateCount(inputId, countId) {
        const el = document.getElementById(countId);
        if (el) el.textContent = (document.getElementById(inputId).value || '').length;
    }

    function refreshRow(row) {
        if (!row || !row.id || !row.type) return;
        const tr = document.querySelector('tr[data-row-id="' + row.type + '-' + row.id + '"]');
        if (!tr) return;
        const ring = tr.querySelector('.score-ring');
        if (ring) {
            ring.textContent = row.score;
            ring.classList.remove('success','warning','danger','secondary');
            ring.classList.add(row.score >= 80 ? 'success' : (row.score >= 50 ? 'warning' : 'danger'));
        }
        const signals = tr.querySelectorAll('td')[3];
        if (signals) {
            const flags = [
                { ok: row.has_meta,     label: 'M',  title: 'Meta' },
                { ok: row.has_og,       label: 'OG', title: 'OG image' },
                { ok: row.has_schema,   label: 'S',  title: 'Schema' },
                { ok: row.has_focus_kw, label: 'K',  title: 'Focus keyword' },
            ];
            signals.innerHTML = flags.map(f =>
                '<span class="badge ' + (f.ok ? 'badge-soft-success' : 'badge-soft-danger') + '" title="' + f.title + '">' + f.label + '</span>'
            ).join(' ');
        }
    }

    document.querySelectorAll('.js-close-drawer').forEach(el => el.addEventListener('click', closeDrawer));

    // ── PREVIEW & EDIT FLOW ───────────────────────────────────────────────────
    document.querySelectorAll('.js-preview-btn').forEach(btn => btn.addEventListener('click', function () {
        showLoading(this.dataset.title);
        document.getElementById('fixDrawerLoading').querySelector('p').textContent = '{{ translate("Generating AI suggestions to review…") }}';
        openDrawer();

        fetch(previewEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ type: this.dataset.type, id: this.dataset.id })
        })
        .then(r => r.json().then(j => ({ status: r.status, json: j })))
        .then(({ status, json }) => {
            if (!json.success) { showError(json.error || 'Preview failed (HTTP ' + status + ')'); return; }
            showPreviewForm(json.data);
        })
        .catch(err => showError(err.message || 'Network error'));
    }));

    document.getElementById('pf-title').addEventListener('input', () => updateCount('pf-title', 'pf-title-count'));
    document.getElementById('pf-desc').addEventListener('input', () => updateCount('pf-desc', 'pf-desc-count'));

    document.getElementById('pf-apply').addEventListener('click', function () {
        if (!previewCtx) return;
        const spinner = document.getElementById('pf-apply-spinner');
        this.disabled = true; spinner.classList.remove('d-none');

        const payload = {
            type: previewCtx.type,
            id: previewCtx.id,
            meta_title: document.getElementById('pf-title').value,
            meta_description: document.getElementById('pf-desc').value,
            focus_keyword: document.getElementById('pf-focus').value,
            secondary_keywords: document.getElementById('pf-secondary').value,
            schema: document.getElementById('pf-schema').checked ? 1 : 0,
        };

        fetch(applyApprovedEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json().then(j => ({ status: r.status, json: j })))
        .then(({ status, json }) => {
            this.disabled = false; spinner.classList.add('d-none');
            if (!json.success) { showError(json.error || 'Apply failed (HTTP ' + status + ')'); return; }
            document.getElementById('previewForm').classList.add('d-none');
            document.getElementById('fixDrawerResult').classList.remove('d-none');
            showResult(json.data);
            refreshRow(json.data.row);
        })
        .catch(err => { this.disabled = false; spinner.classList.add('d-none'); showError(err.message || 'Network error'); });
    });

    document.querySelectorAll('.js-fix-btn').forEach(btn => btn.addEventListener('click', function () {
        const type = this.dataset.type;
        const id   = this.dataset.id;
        const title= this.dataset.title;

        showLoading(title);
        openDrawer();

        fetch(fixEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ type: type, id: id })
        })
        .then(r => r.json().then(j => ({ status: r.status, json: j })))
        .then(({ status, json }) => {
            if (!json.success) {
                showError(json.error || 'Fix failed (HTTP ' + status + ')');
                return;
            }
            showResult(json.data);
            refreshRow(json.data.row);
        })
        .catch(err => showError(err.message || 'Network error'));
    }));

    // ── BULK FIX FLOW ─────────────────────────────────────────────────────────
    const bulkEstimateUrl = '{{ route("admin.seo.ai_board.bulk_estimate") }}';
    const bulkRunUrl      = '{{ route("admin.seo.ai_board.bulk_run") }}';
    const bulkProgressUrlTpl = '{{ url("admin/seo-suite/ai-board/bulk/progress") }}/';
    const bulkCancelUrlTpl   = '{{ url("admin/seo-suite/ai-board/bulk/cancel") }}/';

    const currentFilters = {
        type:      '{{ $type }}',
        search:    {!! json_encode($filters['search']) !!},
        missing:   {!! json_encode($filters['missing']) !!},
        min_score: {!! json_encode($filters['min_score']) !!},
        max_score: {!! json_encode($filters['max_score']) !!},
        sort:      {!! json_encode($filters['sort']) !!},
    };

    function selectedTargets() {
        return Array.from(document.querySelectorAll('.js-row-check:checked'))
            .map(cb => ({ type: cb.dataset.type, id: parseInt(cb.dataset.id, 10) }));
    }

    function updateBulkBar() {
        const n = document.querySelectorAll('.js-row-check:checked').length;
        document.getElementById('bulkCount').textContent = n;
        document.getElementById('bulkBar').classList.toggle('show', n > 0);
    }

    document.querySelectorAll('.js-row-check').forEach(cb => cb.addEventListener('change', updateBulkBar));

    const selectAll = document.querySelector('.js-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.js-row-check').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });
    }

    document.querySelector('.js-clear-selection')?.addEventListener('click', function () {
        document.querySelectorAll('.js-row-check, .js-select-all').forEach(cb => cb.checked = false);
        updateBulkBar();
    });

    let activeBatchId = null;
    let progressTimer = null;
    let pendingMode = null;
    let pendingPayload = null;

    document.querySelectorAll('.js-bulk-trigger').forEach(btn => btn.addEventListener('click', function () {
        const mode = this.dataset.mode;
        pendingMode = mode;

        if (mode === 'selected') {
            const targets = selectedTargets();
            if (targets.length === 0) {
                alert('{{ translate("Select at least one row first.") }}');
                return;
            }
            pendingPayload = { mode: 'selected', targets: targets };
        } else {
            const limitEl = document.getElementById('bulkLimit');
            const limit = limitEl ? parseInt(limitEl.value, 10) : 500;
            pendingPayload = Object.assign({ mode: 'filtered', limit: limit }, currentFilters);
        }

        openCostModal(pendingPayload);
    }));

    function openCostModal(payload) {
        document.getElementById('bulkCostLoading').classList.remove('d-none');
        document.getElementById('bulkCostBody').classList.add('d-none');
        document.getElementById('bulkConfirmRun').disabled = true;
        $('#bulkCostModal').modal('show');

        fetch(bulkEstimateUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(readJsonResponse)
        .then(j => {
            document.getElementById('bulkCostLoading').classList.add('d-none');
            document.getElementById('bulkCostBody').classList.remove('d-none');
            document.getElementById('bcCount').textContent    = j.count;
            document.getElementById('bcProvider').textContent = j.provider + (j.ai_call ? ' (AI mode)' : ' - no API key, template fallback');
            document.getElementById('bcMode').textContent     = payload.mode === 'selected' ? 'Selected rows' : 'All matching filters';
            document.getElementById('bcCost').textContent     = j.ai_call ? ('$' + (j.estimated_usd || 0).toFixed(4)) : '$0 (free)';
            document.getElementById('bcSyncWarn').classList.toggle('d-none', !j.sync_warning);
            document.getElementById('bulkConfirmRun').disabled = (j.count === 0);
        })
        .catch(err => {
            document.getElementById('bulkCostLoading').classList.add('d-none');
            document.getElementById('bulkCostBody').classList.remove('d-none');
            document.getElementById('bulkCostBody').innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(err.message || 'Estimate failed') + '</div>';
        });
    }

    document.getElementById('bulkConfirmRun').addEventListener('click', function () {
        this.disabled = true;
        $('#bulkCostModal').modal('hide');

        // reset progress UI
        document.getElementById('bpProcessed').textContent = 0;
        document.getElementById('bpTotal').textContent     = 0;
        document.getElementById('bpSucceeded').textContent = 0;
        document.getElementById('bpSkipped').textContent   = 0;
        document.getElementById('bpFailed').textContent    = 0;
        document.getElementById('bpCurrent').textContent   = '—';
        document.getElementById('bpCost').textContent      = '$0.0000';
        document.getElementById('bpBar').style.width = '0%';
        document.getElementById('bpBar').textContent  = '0%';
        document.getElementById('bpErrors').classList.add('d-none');
        document.getElementById('bpDone').classList.add('d-none');
        document.getElementById('bpReloadBtn').classList.add('d-none');
        document.getElementById('bpCloseBtn').classList.add('d-none');
        document.getElementById('bpCancelBtn').classList.remove('d-none');

        $('#bulkProgressModal').modal('show');

        fetch(bulkRunUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(pendingPayload)
        })
        .then(readJsonResponse)
        .then(j => {
            activeBatchId = j.batch_id;
            applySnapshot(j.snapshot);
            startProgressPolling();
        })
        .catch(err => {
            document.getElementById('bpErrors').textContent = err.message || 'Network error';
            document.getElementById('bpErrors').classList.remove('d-none');
            document.getElementById('bpCloseBtn').classList.remove('d-none');
        });
    });

    function startProgressPolling() {
        if (progressTimer) clearInterval(progressTimer);
        progressTimer = setInterval(pollProgress, 2000);
        pollProgress();
    }

    function pollProgress() {
        if (!activeBatchId) return;
        fetch(bulkProgressUrlTpl + activeBatchId, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(readJsonResponse)
            .then(j => {
                applySnapshot(j.snapshot);
                if (j.snapshot.is_terminal) {
                    clearInterval(progressTimer);
                    progressTimer = null;
                    document.getElementById('bpCancelBtn').classList.add('d-none');
                    document.getElementById('bpReloadBtn').classList.remove('d-none');
                    document.getElementById('bpCloseBtn').classList.remove('d-none');
                    if (j.snapshot.status === 'completed') {
                        document.getElementById('bpDone').classList.remove('d-none');
                    }
                }
            })
            .catch(() => { /* swallow transient network errors */ });
    }

    function applySnapshot(s) {
        document.getElementById('bpProcessed').textContent = s.processed;
        document.getElementById('bpTotal').textContent     = s.total;
        document.getElementById('bpSucceeded').textContent = s.succeeded;
        document.getElementById('bpSkipped').textContent   = s.skipped;
        document.getElementById('bpFailed').textContent    = s.failed;
        document.getElementById('bpCurrent').textContent   = s.current_label || '—';
        document.getElementById('bpCost').textContent      = '$' + (s.actual_cost_usd || 0).toFixed(4);
        const pct = s.percent || 0;
        document.getElementById('bpBar').style.width  = pct + '%';
        document.getElementById('bpBar').textContent = pct + '%';

        if ((s.recent_errors || []).length > 0) {
            const el = document.getElementById('bpErrors');
            el.classList.remove('d-none');
            el.innerHTML = '<strong>{{ translate("Recent errors") }}:</strong><br>' +
                s.recent_errors.map(e => '<code>' + escapeHtml(e.msg || '') + '</code>').join('<br>');
        }
    }

    document.getElementById('bpCancelBtn').addEventListener('click', function () {
        if (!activeBatchId) return;
        if (!confirm('{{ translate("Cancel this batch? Already-fixed entities will remain fixed.") }}')) return;
        fetch(bulkCancelUrlTpl + activeBatchId, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(readJsonResponse).then(j => applySnapshot(j.snapshot));
    });

    updateBulkBar();
})();
</script>
@endsection
