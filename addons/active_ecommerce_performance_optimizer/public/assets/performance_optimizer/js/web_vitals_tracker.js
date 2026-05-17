/* Performance Optimizer — Web Vitals Tracker
 * Lightweight Core Web Vitals collector (LCP, CLS, FCP, TTFB, INP).
 * Included via {!! perf_vitals_tracker_script() !!} (optional helper) or embedded inline.
 */
(function () {
    if (window.__perfVitalsLoaded) return;
    window.__perfVitalsLoaded = true;

    var endpoint = (window.PERF_VITALS_ENDPOINT || '/performance-optimizer/collect-vital');
    var rate     = (typeof window.PERF_VITALS_RATE === 'number' ? window.PERF_VITALS_RATE : 10);
    if (Math.random() * 100 > rate) return;

    var token  = document.querySelector('meta[name="csrf-token"]');
    var csrf   = token ? token.getAttribute('content') : '';
    var conn   = (navigator.connection && navigator.connection.effectiveType) || '';
    var device = /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop';

    function send(metric, value) {
        try {
            var body = JSON.stringify({
                metric: metric, value: value, url: location.pathname,
                device: device, connection: conn,
                user_agent: (navigator.userAgent || '').substring(0, 240),
                _token: csrf
            });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
            } else {
                fetch(endpoint, {
                    method: 'POST',
                    keepalive: true,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: body
                });
            }
        } catch (e) {}
    }

    // LCP
    try {
        new PerformanceObserver(function (list) {
            var entries = list.getEntries();
            var last = entries[entries.length - 1];
            if (last) send('LCP', last.renderTime || last.loadTime || last.startTime);
        }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) {}

    // CLS
    try {
        var cls = 0;
        new PerformanceObserver(function (list) {
            list.getEntries().forEach(function (e) { if (!e.hadRecentInput) cls += e.value; });
        }).observe({ type: 'layout-shift', buffered: true });
        addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') send('CLS', cls);
        });
    } catch (e) {}

    // FCP
    try {
        new PerformanceObserver(function (list) {
            list.getEntries().forEach(function (e) {
                if (e.name === 'first-contentful-paint') send('FCP', e.startTime);
            });
        }).observe({ type: 'paint', buffered: true });
    } catch (e) {}

    // TTFB
    try {
        var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
        if (nav) send('TTFB', nav.responseStart);
    } catch (e) {}

    // INP
    try {
        var maxInp = 0;
        new PerformanceObserver(function (list) {
            list.getEntries().forEach(function (e) {
                if (e.duration > maxInp) maxInp = e.duration;
            });
        }).observe({ type: 'event', buffered: true, durationThreshold: 40 });
        addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden' && maxInp > 0) send('INP', maxInp);
        });
    } catch (e) {}
})();
