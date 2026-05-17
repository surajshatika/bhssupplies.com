@extends('backend.layouts.app')

@section('content')
@php
    $providers = ['openai' => 'OpenAI (ChatGPT)', 'claude' => 'Claude', 'gemini' => 'Gemini', 'grok' => 'Grok (xAI)'];

    // Feature groups with icons and colors — clean, no duplicates
    $toolGroups = [
        'Sitemaps' => [
            'icon'  => 'la-sitemap',
            'color' => 'primary',
            'tools' => [
                'smart_sitemap'  => ['label' => 'Smart XML Sitemap',    'icon' => 'la-sitemap',   'desc' => 'Full site sitemap: products, categories, blogs, pages'],
                'video_sitemap'  => ['label' => 'Video SEO Sitemap',    'icon' => 'la-video',     'desc' => 'Video sitemap for embedded/hosted videos'],
                'blog_sitemap'   => ['label' => 'Blog / News Sitemap',  'icon' => 'la-newspaper', 'desc' => 'Blog & news articles sitemap for Google'],
                'rss_content'    => ['label' => 'RSS Content Feed',     'icon' => 'la-rss',       'desc' => 'RSS 2.0 feed for feed readers & aggregators'],
            ],
        ],
        'Technical SEO' => [
            'icon'  => 'la-code',
            'color' => 'info',
            'tools' => [
                'page_speed'     => ['label' => 'Page Speed Analyzer',      'icon' => 'la-tachometer-alt', 'desc' => 'Core Web Vitals analysis & fix recommendations'],
                'technical_audit'=> ['label' => 'Technical SEO Audit',      'icon' => 'la-search',         'desc' => '50+ technical SEO checks'],
                'robots'         => ['label' => 'Robots.txt Optimizer',     'icon' => 'la-robot',          'desc' => 'AI-optimized robots.txt generation'],
                'canonical'      => ['label' => 'Canonical URL Manager',    'icon' => 'la-link',           'desc' => 'Manage canonical tags to prevent duplication'],
                'broken_links'   => ['label' => 'Broken Link Checker',      'icon' => 'la-unlink',         'desc' => 'Detect & fix 404 broken links'],
                'competitor_gap' => ['label' => 'Competitor Gap Analyzer',  'icon' => 'la-chess',          'desc' => 'Find keyword gaps vs competitors'],
            ],
        ],
        'Content & Schema' => [
            'icon'  => 'la-code-branch',
            'color' => 'success',
            'tools' => [
                'faq_schema'     => ['label' => 'FAQ Schema Generator',     'icon' => 'la-question-circle','desc' => 'Auto-generate FAQ schema markup'],
                'ecommerce_seo'  => ['label' => 'E-commerce Product SEO',   'icon' => 'la-shopping-cart',  'desc' => 'Product schema & e-commerce optimization'],
                'local_seo'      => ['label' => 'Local SEO Optimizer',      'icon' => 'la-map-marker',     'desc' => 'Google Business & local citations'],
                'small_business_seo'=>['label'=> 'Small Business SEO',      'icon' => 'la-store',          'desc' => 'SMB SEO action plan & local schema'],
            ],
        ],
        'URL & Redirects' => [
            'icon'  => 'la-exchange-alt',
            'color' => 'warning',
            'tools' => [
                'redirection_manager'=>['label'=> 'Redirection Manager',    'icon' => 'la-exchange-alt',   'desc' => 'Manage 301/302 redirects'],
                'post_index_status'  =>['label'=> 'Post Index Status',      'icon' => 'la-search',         'desc' => 'Check if pages are indexed by Google'],
                'indexnow'           =>['label'=> 'IndexNow Submission',    'icon' => 'la-bolt',           'desc' => 'Instant URL submission to Bing/Yandex'],
                'webmaster_tools'    =>['label'=> 'Webmaster Tools',        'icon' => 'la-tools',          'desc' => 'Google, Bing, Yandex verification codes'],
            ],
        ],
        'AI Tools' => [
            'icon'  => 'la-robot',
            'color' => 'secondary',
            'tools' => [
                'ai_writing_assistant'=>['label'=> 'AI Writing Assistant',  'icon' => 'la-pen-nib',        'desc' => 'Generate, improve & paraphrase content'],
                'ai_image_generator'  =>['label'=> 'AI Image Generator',   'icon' => 'la-image',          'desc' => 'DALL-E 3 SEO-optimized image generation'],
                'ai_assistant'        =>['label'=> 'AI SEO Chat Assistant', 'icon' => 'la-comments',       'desc' => 'Chat with AI for SEO strategies'],
                'link_assistant'      =>['label'=> 'Link Assistant',        'icon' => 'la-link',           'desc' => 'Internal & external link opportunities'],
                'keyword_rank_tracker'=>['label'=> 'Keyword Rank Tracker',  'icon' => 'la-chart-line',     'desc' => 'Track keyword positions in Google'],
                'search_statistics'   =>['label'=> 'Search Statistics',     'icon' => 'la-chart-bar',      'desc' => 'SEO score trends & performance analytics'],
                'seo_revisions'       =>['label'=> 'SEO Revisions',         'icon' => 'la-history',        'desc' => 'Track & compare SEO score history'],
                'llms_txt'            =>['label'=> 'LLMs.txt Generator',    'icon' => 'la-file-code',      'desc' => 'AI crawler directive file'],
                'score_dashboard'     =>['label'=> 'SEO Score Dashboard',   'icon' => 'la-tachometer-alt', 'desc' => 'Weekly automated score tracking'],
            ],
        ],
    ];

    // Direct action routes (run immediately without modal)
    $directActions = [
        'smart_sitemap'  => 'admin.seo-suite.sitemap',
        'video_sitemap'  => 'admin.seo-suite.sitemap.video',
        'blog_sitemap'   => 'admin.seo-suite.sitemap.news',
        'rss_content'    => 'admin.seo-suite.rss',
        'robots'         => 'admin.seo-suite.robots',
        'post_index_status' => 'admin.seo-suite.index_status',
        'indexnow'       => 'admin.seo-suite.indexnow',
        'webmaster_tools'=> 'admin.seo-suite.webmaster',
        'ai_writing_assistant' => 'admin.seo-suite.ai_assistant',
        'ai_image_generator'   => 'admin.seo-suite.ai_images',
        'ai_assistant'         => 'admin.seo-suite.ai_assistant',
        'link_assistant'       => 'admin.seo-suite.link_assistant',
        'keyword_rank_tracker' => 'admin.seo-suite.keyword_tracker',
        'search_statistics'    => 'admin.seo-suite.search_stats',
        'seo_revisions'        => 'admin.seo-suite.revisions',
        'redirection_manager'  => 'admin.seo-suite.index',
    ];
@endphp

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-5">
            <h1 class="h3">{{ translate('Technical Optimization & SEO Tools') }}</h1>
            @if($project)
                <p class="text-muted mb-0">{{ $project->name }} — {{ $project->base_url }}</p>
            @endif
        </div>
        <div class="col-md-7 text-md-right">
            <form action="{{ route('admin.seo-suite.sitemap') }}" method="POST" class="d-inline-block">
                @csrf
                <button class="btn btn-soft-primary mr-2">
                    <i class="las la-sitemap mr-1"></i>{{ translate('Generate Sitemap') }}
                </button>
            </form>
            <form action="{{ route('admin.seo-suite.robots') }}" method="POST" class="d-inline-block">
                @csrf
                <button class="btn btn-soft-success mr-2">
                    <i class="las la-robot mr-1"></i>{{ translate('Generate Robots') }}
                </button>
            </form>
            <a href="{{ route('admin.seo-suite.index') }}" class="btn btn-soft-secondary">
                <i class="las la-tachometer-alt mr-1"></i>{{ translate('Dashboard') }}
            </a>
        </div>
    </div>
</div>

@if($setupRequired)
    <div class="alert alert-warning">
        <i class="las la-exclamation-triangle mr-1"></i>
        {{ translate('SEO suite database tables are missing. Run the four SEO migrations to activate full features.') }}
    </div>
@endif

{{-- Score Cards --}}
<div class="row gutters-16 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                @php
                    $score = $dashboard['current_score'] ?? 0;
                    $scoreColor = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                @endphp
                <div class="h2 mb-1 text-{{ $scoreColor }}">{{ $score }}</div>
                <div class="text-muted small">{{ translate('Current SEO Score') }}</div>
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar bg-{{ $scoreColor }}" style="width:{{ $score }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-primary">{{ $dashboard['current_grade'] ?? 'N/A' }}</div>
                <div class="text-muted small">{{ translate('Grade') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-info">{{ $dashboard['average_score'] ?? 0 }}</div>
                <div class="text-muted small">{{ translate('Avg Score (History)') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h2 mb-1 text-success">{{ $runs->where('status','completed')->count() }}</div>
                <div class="text-muted small">{{ translate('Completed Runs') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Tool Groups --}}
@foreach($toolGroups as $groupName => $group)
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="las {{ $group['icon'] }} text-{{ $group['color'] }} mr-2 la-lg"></i>
        <h6 class="mb-0 font-weight-600">{{ translate($groupName) }}</h6>
    </div>
    <div class="card-body">
        <div class="row gutters-16">
            @foreach($group['tools'] as $featureKey => $tool)
            <div class="col-xl-2 col-lg-3 col-md-4 col-6 mb-3">
                @if(isset($directActions[$featureKey]) && in_array($featureKey, ['smart_sitemap','video_sitemap','blog_sitemap','rss_content','robots']))
                {{-- Direct POST action --}}
                <form action="{{ route($directActions[$featureKey]) }}" method="POST" class="h-100">
                    @csrf
                    <button type="submit" class="btn btn-block h-100 p-0 border-0 bg-transparent text-left w-100">
                        <div class="card h-100 shadow-sm border-0 tool-card tool-card-direct text-center"
                             style="cursor:pointer; transition:all 0.2s;" title="{{ $tool['desc'] }}">
                            <div class="card-body d-flex flex-column justify-content-center py-3 px-2">
                                <i class="las {{ $tool['icon'] }} la-2x text-{{ $group['color'] }} mb-2"></i>
                                <h6 class="font-weight-600 mb-1" style="font-size:0.78rem; line-height:1.3;">{{ $tool['label'] }}</h6>
                                <p class="text-muted mb-0" style="font-size:0.68rem; line-height:1.3;">{{ $tool['desc'] }}</p>
                            </div>
                        </div>
                    </button>
                </form>
                @elseif(isset($directActions[$featureKey]))
                {{-- Direct GET link --}}
                <a href="{{ route($directActions[$featureKey]) }}" class="h-100 d-block text-decoration-none">
                    <div class="card h-100 shadow-sm border-0 tool-card text-center"
                         style="cursor:pointer; transition:all 0.2s;" title="{{ $tool['desc'] }}">
                        <div class="card-body d-flex flex-column justify-content-center py-3 px-2">
                            <i class="las {{ $tool['icon'] }} la-2x text-{{ $group['color'] }} mb-2"></i>
                            <h6 class="font-weight-600 mb-1" style="font-size:0.78rem; line-height:1.3;">{{ $tool['label'] }}</h6>
                            <p class="text-muted mb-0" style="font-size:0.68rem; line-height:1.3;">{{ $tool['desc'] }}</p>
                        </div>
                    </div>
                </a>
                @else
                {{-- Modal trigger --}}
                <div class="card h-100 shadow-sm border-0 tool-card text-center"
                     style="cursor:pointer; transition:all 0.2s;"
                     title="{{ $tool['desc'] }}"
                     onclick="openToolModal('{{ $featureKey }}', '{{ addslashes($tool['label']) }}', '{{ addslashes($tool['desc']) }}')">
                    <div class="card-body d-flex flex-column justify-content-center py-3 px-2">
                        <i class="las {{ $tool['icon'] }} la-2x text-{{ $group['color'] }} mb-2"></i>
                        <h6 class="font-weight-600 mb-1" style="font-size:0.78rem; line-height:1.3;">{{ $tool['label'] }}</h6>
                        <p class="text-muted mb-0" style="font-size:0.68rem; line-height:1.3;">{{ $tool['desc'] }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

{{-- Recent Runs + Score Trend --}}
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('Recent Optimization Runs') }}</h5>
                <a href="{{ route('admin.seo-suite.revisions') }}" class="btn btn-xs btn-soft-primary">{{ translate('All History') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Feature') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('URL') }}</th>
                                <th>{{ translate('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                                <tr>
                                    <td>{{ data_get($features, $run->feature, $run->feature) }}</td>
                                    <td>
                                        <span class="badge badge-inline badge-{{ $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ $run->status }}
                                        </span>
                                    </td>
                                    <td class="text-truncate" style="max-width:180px;">{{ $run->url ?: '—' }}</td>
                                    <td class="text-nowrap small text-muted">{{ optional($run->created_at)->format('M d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ translate('No runs yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        {{-- Quick Redirect --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">{{ translate('Quick Redirect') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.seo-suite.redirects.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small">{{ translate('From') }}</label>
                        <input type="text" class="form-control form-control-sm" name="source_url" placeholder="/old-page" required>
                    </div>
                    <div class="form-group">
                        <label class="small">{{ translate('To') }}</label>
                        <input type="text" class="form-control form-control-sm" name="target_url" placeholder="/new-page" required>
                    </div>
                    <div class="d-flex">
                        <select class="form-control form-control-sm mr-2" name="status_code" style="width:auto;">
                            <option value="301">301 Permanent</option>
                            <option value="302">302 Temporary</option>
                        </select>
                        <button class="btn btn-warning btn-sm flex-shrink-0">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Score Trend --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Score Trend') }}</h5></div>
            <div class="card-body p-0">
                @if(count($dashboard['trend'] ?? []) > 0)
                    @foreach($dashboard['trend'] as $trend)
                        <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                            <span class="text-muted small">{{ $trend['date'] }}</span>
                            <div>
                                <span class="badge badge-soft-{{ ($trend['score'] ?? 0) >= 80 ? 'success' : (($trend['score'] ?? 0) >= 50 ? 'warning' : 'danger') }}">
                                    {{ $trend['score'] }}
                                </span>
                                <span class="badge badge-soft-info ml-1">{{ $trend['grade'] }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center py-3 mb-0">{{ translate('No score history yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Run Tool Modal --}}
<div class="modal fade" id="toolModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toolModalLabel">{{ translate('Run SEO Tool') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('admin.seo_optimization.run') }}" method="POST">
                @csrf
                <input type="hidden" name="feature" id="toolFeatureInput">
                <div class="modal-body">
                    <p id="toolModalDesc" class="text-muted small mb-3"></p>
                    <div class="form-group">
                        <label>{{ translate('AI Provider') }}</label>
                        <select name="provider" class="form-control">
                            <option value="">{{ translate('System Default') }}</option>
                            @foreach($providers as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- URL field --}}
                    <div class="form-group tool-field" data-tools="page_speed,technical_audit,canonical,broken_links,competitor_gap,score_dashboard">
                        <label>{{ translate('Target URL') }}</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com/page">
                    </div>
                    {{-- Competitor keywords --}}
                    <div class="form-group tool-field d-none" data-tools="competitor_gap">
                        <label>{{ translate('Your Keywords') }}</label>
                        <textarea class="form-control" rows="2" name="our_keywords" placeholder="safety gloves, PPE equipment"></textarea>
                        <label class="mt-2">{{ translate('Competitor Keywords') }}</label>
                        <textarea class="form-control" rows="2" name="comp1_keywords"></textarea>
                    </div>
                    {{-- FAQ / E-commerce --}}
                    <div class="form-group tool-field d-none" data-tools="faq_schema,ecommerce_seo">
                        <label>{{ translate('FAQ Pairs (Q | A) or Product JSON') }}</label>
                        <textarea class="form-control" rows="4" name="faq" placeholder="What is it? | It is a safety product."></textarea>
                    </div>
                    {{-- Local SEO --}}
                    <div class="form-group tool-field d-none" data-tools="local_seo,small_business_seo">
                        <label>{{ translate('Business Name') }}</label>
                        <input type="text" class="form-control" name="business_name" value="{{ get_setting('seo_local_business_name', get_setting('website_name')) }}">
                        <label class="mt-2">{{ translate('City / Location') }}</label>
                        <input type="text" class="form-control" name="city" value="{{ config('seo.local_business.city', '') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="las la-play mr-1"></i>{{ translate('Run Tool') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function() {
    // Tool card hover effects
    $(document).on('mouseenter', '.tool-card', function() {
        $(this).addClass('shadow').css({'transform':'translateY(-3px)', 'border-color':'rgba(0,0,0,.15)'});
    }).on('mouseleave', '.tool-card', function() {
        $(this).removeClass('shadow').css({'transform':'translateY(0)', 'border-color':''});
    });
});

function openToolModal(featureKey, label, desc) {
    $('#toolFeatureInput').val(featureKey);
    $('#toolModalLabel').text(label);
    $('#toolModalDesc').text(desc || '');

    // Show relevant fields
    $('.tool-field').addClass('d-none').removeClass('d-block');
    var featureFields = {
        'page_speed':    ['page_speed'],
        'technical_audit':['page_speed'],
        'canonical':     ['page_speed'],
        'broken_links':  ['page_speed'],
        'competitor_gap':['page_speed', 'competitor_gap'],
        'faq_schema':    ['faq_schema'],
        'ecommerce_seo': ['faq_schema'],
        'local_seo':     ['local_seo'],
        'small_business_seo': ['local_seo'],
        'score_dashboard': ['page_speed'],
    };

    var relevantGroups = featureFields[featureKey] || ['page_speed'];
    relevantGroups.forEach(function(group) {
        $('[data-tools*="' + group + '"]').removeClass('d-none').addClass('d-block');
    });

    if ($('.tool-field:not(.d-none)').length === 0) {
        $('[data-tools*="page_speed"]').first().removeClass('d-none').addClass('d-block');
    }

    $('#toolModal').modal('show');
}
</script>
@endsection
