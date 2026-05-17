{{-- ===========================================================
     Multi-channel marketing pixels (frontend snippets)
     Each block fires only when its channel is enabled in admin.
     CAPI dedup: server uses identical event_id pattern so Meta/TikTok/etc.
     can deduplicate browser + server events.
     =========================================================== --}}

{{-- TikTok Pixel --}}
@if((int) get_setting('tiktok_capi_enabled') === 1 && env('TIKTOK_PIXEL_ID'))
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
  ttq.load('{{ env('TIKTOK_PIXEL_ID') }}');
  ttq.page();
}(window, document, 'ttq');
</script>
@endif

{{-- Pinterest Tag --}}
@if((int) get_setting('pinterest_capi_enabled') === 1 && env('PINTEREST_TAG_ID'))
<script>
!function(e){if(!window.pintrk){window.pintrk = function () {
  window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var
  n=window.pintrk;n.queue=[],n.version="3.0";var
  t=document.createElement("script");t.async=!0,t.src=e;var
  r=document.getElementsByTagName("script")[0];
  r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
  pintrk('load', '{{ env('PINTEREST_TAG_ID') }}');
  pintrk('page');
</script>
<noscript><img height="1" width="1" style="display:none;" alt="" src="https://ct.pinterest.com/v3/?event=init&tid={{ env('PINTEREST_TAG_ID') }}&noscript=1" /></noscript>
@endif

{{-- Snapchat Pixel --}}
@if((int) get_setting('snapchat_capi_enabled') === 1 && env('SNAPCHAT_PIXEL_ID'))
<script>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};a.queue=[];var s='script';r=t.createElement(s);r.async=!0;r.src=n;var u=t.getElementsByTagName(s)[0];u.parentNode.insertBefore(r,u);})(window,document,'https://sc-static.net/scevent.min.js');
snaptr('init', '{{ env('SNAPCHAT_PIXEL_ID') }}', { @auth 'user_email': '{{ hash('sha256', strtolower(auth()->user()->email)) }}' @endauth });
snaptr('track', 'PAGE_VIEW');
</script>
@endif

{{-- LinkedIn Insight Tag --}}
@if((int) get_setting('linkedin_capi_enabled') === 1 && env('LINKEDIN_PARTNER_ID'))
<script type="text/javascript">
_linkedin_partner_id = "{{ env('LINKEDIN_PARTNER_ID') }}";
window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
window._linkedin_data_partner_ids.push(_linkedin_partner_id);
</script>
<script type="text/javascript">
(function(l) { if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])}; window.lintrk.q=[]} var s = document.getElementsByTagName("script")[0]; var b = document.createElement("script"); b.type = "text/javascript";b.async = true; b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js"; s.parentNode.insertBefore(b, s);})(window.lintrk);
</script>
<noscript><img height="1" width="1" style="display:none;" alt="" src="https://px.ads.linkedin.com/collect/?pid={{ env('LINKEDIN_PARTNER_ID') }}&fmt=gif" /></noscript>
@endif

{{-- X (Twitter) Pixel --}}
@if((int) get_setting('twitter_capi_enabled') === 1 && env('TWITTER_PIXEL_ID'))
<script>
!function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments)},
s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='https://static.ads-twitter.com/uwt.js',
a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
twq('config','{{ env('TWITTER_PIXEL_ID') }}');
</script>
@endif

{{-- Microsoft Clarity --}}
@if((int) get_setting('clarity_capi_enabled') === 1 && env('CLARITY_PROJECT_ID'))
<script type="text/javascript">
(function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window, document, "clarity", "script", "{{ env('CLARITY_PROJECT_ID') }}");
</script>
@endif

{{-- Google Ads gtag (Enhanced Conversions on browser side) --}}
@if((int) get_setting('google_ads_capi_enabled') === 1 && env('GOOGLE_ADS_CUSTOMER_ID'))
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-{{ env('GOOGLE_ADS_CUSTOMER_ID') }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'AW-{{ env('GOOGLE_ADS_CUSTOMER_ID') }}', { 'allow_enhanced_conversions': true });
</script>
@endif
