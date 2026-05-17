@extends('backend.layouts.app')

@section('content')
@include('backend.partials.modern_module_styles')

<div class="mm-hero mm-hero--social">
    <div class="mm-hero-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="mm-hero-icon mr-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <div>
                <h2>{{ translate('Social Media Automation') }}</h2>
                <p>{{ translate('Multi-platform AI-powered social media automation') }}</p>
                <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;">
                    <span class="mm-chip"><span class="mm-dot {{ $globalEnabled ? 'ok' : 'warn' }}"></span> {{ $globalEnabled ? translate('Auto-Post On') : translate('Auto-Post Off') }}</span>
                    <span class="mm-chip"><i class="las la-share-alt"></i> {{ count($platformStatus ?? []) }} {{ translate('platforms') }}</span>
                    <span class="mm-chip"><i class="las la-robot"></i> AI Agent</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3 mt-md-0" style="gap:.5rem;">
            <a href="{{ route('admin.social.compose') }}" class="mm-btn mm-btn-light">
                <i class="las la-edit"></i> {{ translate('Compose') }}
            </a>
            <a href="{{ route('admin.social.campaigns') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-bullhorn"></i> {{ translate('Campaigns') }}
            </a>
            <a href="{{ route('admin.social.settings') }}" class="mm-btn mm-btn-ghost">
                <i class="las la-cog"></i> {{ translate('Settings') }}
            </a>
        </div>
    </div>
</div>

{{-- Stats Row --}}
<div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['total']) }}</h3>
            <div class="mm-stat-label">{{ translate('Total Posts') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['success']) }}</h3>
            <div class="mm-stat-label">{{ translate('Successful') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['failed']) }}</h3>
            <div class="mm-stat-label">{{ translate('Failed') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="mm-stat">
            <div class="mm-stat-icon mm-tint-cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <h3 class="mm-stat-value">{{ number_format($stats['today']) }}</h3>
            <div class="mm-stat-label">{{ translate('Today') }}</div>
        </div>
    </div>
</div>

<div class="row gutters-16">
    {{-- Platform Status --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Platform Status') }}</h6>
                <div>
                    <span class="badge badge-pill badge-success mr-1">{{ translate('Global') }}</span>
                    <span class="badge badge-pill badge-info">{{ translate('Canada+') }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Platform') }}</th>
                                <th>{{ translate('Region') }}</th>
                                <th>{{ translate('Configured') }}</th>
                                <th>{{ translate('Auto-Post') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($platformStatus as $slug => $info)
                            <tr>
                                <td>
                                    <i class="{{ config('social_media.platforms.'.$slug.'.icon', 'las la-share-alt') }} mr-2" style="font-size:18px;"></i>
                                    {{ $info['label'] }}
                                </td>
                                <td>
                                    @if($info['region'] === 'canada')
                                        <span class="badge badge-info">CA</span>
                                    @elseif($info['region'] === 'both')
                                        <span class="badge badge-success">Global</span>
                                        <span class="badge badge-info">CA</span>
                                    @else
                                        <span class="badge badge-success">Global</span>
                                    @endif
                                </td>
                                <td>
                                    @if($info['configured'])
                                        <span class="badge badge-success"><i class="las la-check"></i> {{ translate('Yes') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ translate('Not Set') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($info['enabled'])
                                        <span class="badge badge-primary">{{ translate('Active') }}</span>
                                    @else
                                        <span class="badge badge-light text-muted">{{ translate('Off') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.social.settings') }}#{{ $slug }}" class="btn btn-sm btn-soft-primary">
                                        {{ translate('Configure') }}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Providers + Quick Compose --}}
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="las la-brain mr-1"></i>{{ translate('AI Providers') }}</h6>
            </div>
            <div class="card-body">
                @foreach($aiProviders as $key => $label)
                @php
                    $keyMap = ['openai' => 'social_ai_openai_key', 'claude' => 'social_ai_claude_key', 'grok' => 'social_ai_grok_key'];
                    $configured = !empty(\App\Models\SocialAutomationSetting::get($keyMap[$key] ?? ''));
                @endphp
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span>{{ $label }}</span>
                    @if($configured)
                        <span class="badge badge-success">{{ translate('Ready') }}</span>
                    @else
                        <a href="{{ route('admin.social.settings') }}#ai-settings" class="badge badge-warning">{{ translate('Set Key') }}</a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="las la-history mr-1"></i>{{ translate('Recent Activity') }}</h6>
            </div>
            <div class="card-body p-0">
                @forelse($recentLogs as $log)
                <div class="px-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between">
                        <small class="font-weight-600">
                            <i class="{{ config('social_media.platforms.'.$log->platform.'.icon', 'las la-share-alt') }}"></i>
                            {{ ucfirst($log->platform) }}
                        </small>
                        <span class="badge badge-sm {{ $log->status_badge }}">{{ $log->status }}</span>
                    </div>
                    <div class="text-muted" style="font-size:11px;">{{ $log->created_at->diffForHumans() }}</div>
                </div>
                @empty
                <div class="p-3 text-muted text-center">{{ translate('No posts yet') }}</div>
                @endforelse
                <div class="p-2 text-center">
                    <a href="{{ route('admin.social.logs') }}" class="btn btn-sm btn-soft-primary">{{ translate('View All Logs') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
