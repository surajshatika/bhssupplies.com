{{--
    Copy this <li> block into resources/views/backend/inc/admin_sidenav.blade.php
    inside the existing aiz-side-nav-list (place near other addons, e.g. after Support Board).
--}}
@if(addon_is_activated('performance_optimizer'))
<li class="aiz-side-nav-item">
    <a href="{{ route('performance_optimizer.dashboard') }}"
       class="aiz-side-nav-link {{ areActiveRoutesHome(['performance_optimizer.dashboard','performance_optimizer.images','performance_optimizer.cssjs','performance_optimizer.scripts.index','performance_optimizer.cache','performance_optimizer.cache_rules.index','performance_optimizer.edge.index','performance_optimizer.database','performance_optimizer.fonts','performance_optimizer.vitals','performance_optimizer.secplus.index','performance_optimizer.security','performance_optimizer.ai.index','performance_optimizer.logs','performance_optimizer.monitor']) }}">
        <i class="las la-tachometer-alt aiz-side-nav-icon"></i>
        <span class="aiz-side-nav-text">{{ translate('Performance Optimizer') }}</span>
        <span class="aiz-side-nav-arrow"></span>
    </a>
    <ul class="aiz-side-nav-list level-2">
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.dashboard') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.dashboard']) }}">
                <span class="aiz-side-nav-text">{{ translate('Dashboard') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.images') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.images']) }}">
                <span class="aiz-side-nav-text">{{ translate('Images (WebP / AVIF)') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.cssjs') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.cssjs']) }}">
                <span class="aiz-side-nav-text">{{ translate('CSS / JS') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.scripts.index') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.scripts.index']) }}">
                <span class="aiz-side-nav-text">{{ translate('Script Manager') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.cache') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.cache']) }}">
                <span class="aiz-side-nav-text">{{ translate('Page Cache') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.cache_rules.index') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.cache_rules.index']) }}">
                <span class="aiz-side-nav-text">{{ translate('Cache Rules') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.edge.index') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.edge.index']) }}">
                <span class="aiz-side-nav-text">{{ translate('Edge / CDN') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.database') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.database']) }}">
                <span class="aiz-side-nav-text">{{ translate('Database') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.fonts') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.fonts']) }}">
                <span class="aiz-side-nav-text">{{ translate('Fonts') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.vitals') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.vitals']) }}">
                <span class="aiz-side-nav-text">{{ translate('Web Vitals') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.secplus.index') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.secplus.index']) }}">
                <span class="aiz-side-nav-text">{{ translate('Security+') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.security') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.security']) }}">
                <span class="aiz-side-nav-text">{{ translate('Security Audit') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.ai.index') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.ai.index']) }}">
                <span class="aiz-side-nav-text">{{ translate('AI Recommendations') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.logs') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.logs']) }}">
                <span class="aiz-side-nav-text">{{ translate('Logs') }}</span>
            </a>
        </li>
        <li class="aiz-side-nav-item">
            <a href="{{ route('performance_optimizer.monitor') }}"
               class="aiz-side-nav-link {{ areActiveRoutes(['performance_optimizer.monitor']) }}">
                <span class="aiz-side-nav-text">{{ translate('System Monitor') }}</span>
            </a>
        </li>
    </ul>
</li>
@endif
