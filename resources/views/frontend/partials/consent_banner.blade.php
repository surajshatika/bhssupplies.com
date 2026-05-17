{{-- ===========================================================
     GDPR / CCPA Consent Banner with Google Consent Mode v2.
     Renders only if consent is enabled in admin and the visitor
     has not yet stored a consent decision in localStorage.
     =========================================================== --}}

@if((int) get_setting('marketing_consent_enabled') === 1)
<style>
.mm-consent {
    position: fixed; bottom: 18px; left: 18px; right: 18px;
    max-width: 720px; margin: 0 auto;
    background: #1f2937; color: #fff;
    border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,.25);
    padding: 18px 20px; z-index: 99999;
    font-family: inherit; font-size: 14px;
    transition: transform .25s ease, opacity .25s ease;
}
.mm-consent.mm-consent--hidden { opacity:0; pointer-events:none; transform: translateY(20px); }
.mm-consent__title { font-weight: 700; font-size: 15px; margin: 0 0 4px; color:#fff; }
.mm-consent__desc  { color: rgba(255,255,255,.78); margin: 0 0 12px; font-size: 13px; line-height: 1.4; }
.mm-consent__actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
.mm-consent__btn { padding:.5rem 1rem; border-radius: 8px; border:0; font-weight:600; cursor: pointer; font-size: 13px; }
.mm-consent__btn--accept   { background:#10B981; color:#fff; }
.mm-consent__btn--reject   { background:rgba(255,255,255,.10); color:#fff; }
.mm-consent__btn--customize{ background:transparent; color:#A5B4FC; text-decoration: underline; padding:.5rem 0; }
.mm-consent__cats { display:none; margin-top: 14px; border-top:1px solid rgba(255,255,255,.10); padding-top:12px; }
.mm-consent__cats.show { display:block; }
.mm-consent__cat { display:flex; align-items:center; justify-content:space-between; padding:6px 0; }
.mm-consent__cat label { color:rgba(255,255,255,.85); font-size:13px; margin:0; }
.mm-consent__cat small { color: rgba(255,255,255,.55); font-size: 11.5px; display:block; }
.mm-consent__sw { position: relative; width:36px; height:20px; background:rgba(255,255,255,.18); border-radius:11px; cursor:pointer; transition: background .2s; }
.mm-consent__sw.on  { background:#10B981; }
.mm-consent__sw.off-locked { background:rgba(255,255,255,.30); cursor:not-allowed; }
.mm-consent__sw::after { content:''; position:absolute; top:2px; left:2px; width:16px; height:16px; background:#fff; border-radius:50%; transition: left .2s; }
.mm-consent__sw.on::after { left: 18px; }
@media (max-width: 600px) {
    .mm-consent { bottom: 0; left:0; right:0; border-radius: 14px 14px 0 0; padding: 14px 16px; }
}
</style>

<script>
// Google Consent Mode v2 — set defaults BEFORE any tag fires.
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
    'ad_storage':            'denied',
    'ad_user_data':          'denied',
    'ad_personalization':    'denied',
    'analytics_storage':     'denied',
    'functionality_storage': 'granted',
    'personalization_storage':'denied',
    'security_storage':      'granted',
    'wait_for_update':       500
});

(function() {
    var KEY = 'mm_consent_v2';
    var stored = null;
    try { stored = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) {}

    function applyConsent(c) {
        gtag('consent', 'update', {
            'ad_storage':            c.marketing ? 'granted' : 'denied',
            'ad_user_data':          c.marketing ? 'granted' : 'denied',
            'ad_personalization':    c.marketing ? 'granted' : 'denied',
            'analytics_storage':     c.analytics ? 'granted' : 'denied',
            'personalization_storage':c.preferences ? 'granted' : 'denied'
        });
        // Suppress server-side tracking middleware via cookie consent flag
        document.cookie = 'mm_consent=' + encodeURIComponent(JSON.stringify(c)) + ';max-age=' + (60*60*24*395) + ';path=/;samesite=lax';
        window.dispatchEvent(new CustomEvent('mm-consent-update', { detail: c }));
    }

    function persist(c) {
        try { localStorage.setItem(KEY, JSON.stringify(c)); } catch (e) {}
        applyConsent(c);
    }

    if (stored) {
        applyConsent(stored);
        return; // already decided — no banner
    }

    // Render banner
    var banner = document.createElement('div');
    banner.className = 'mm-consent';
    banner.innerHTML = `
        <p class="mm-consent__title">{{ translate('We respect your privacy') }}</p>
        <p class="mm-consent__desc">
            {{ translate('We use cookies to improve your experience, analyse traffic, and serve relevant ads. You can choose which categories to allow.') }}
        </p>
        <div class="mm-consent__actions">
            <button type="button" class="mm-consent__btn mm-consent__btn--accept" data-act="accept">{{ translate('Accept all') }}</button>
            <button type="button" class="mm-consent__btn mm-consent__btn--reject" data-act="reject">{{ translate('Reject all') }}</button>
            <button type="button" class="mm-consent__btn mm-consent__btn--customize" data-act="toggle">{{ translate('Customize') }}</button>
        </div>
        <div class="mm-consent__cats" id="mm-consent-cats">
            <div class="mm-consent__cat">
                <div>
                    <label>{{ translate('Necessary') }}</label>
                    <small>{{ translate('Required for the site to function — cannot be disabled.') }}</small>
                </div>
                <div class="mm-consent__sw on off-locked"></div>
            </div>
            <div class="mm-consent__cat">
                <div>
                    <label>{{ translate('Analytics') }}</label>
                    <small>{{ translate('Helps us understand site usage (GA4, internal warehouse).') }}</small>
                </div>
                <div class="mm-consent__sw" data-cat="analytics"></div>
            </div>
            <div class="mm-consent__cat">
                <div>
                    <label>{{ translate('Marketing') }}</label>
                    <small>{{ translate('Used to serve personalised ads (Meta, TikTok, Google Ads pixels).') }}</small>
                </div>
                <div class="mm-consent__sw" data-cat="marketing"></div>
            </div>
            <div class="mm-consent__cat">
                <div>
                    <label>{{ translate('Preferences') }}</label>
                    <small>{{ translate('Remember language, currency, recently viewed.') }}</small>
                </div>
                <div class="mm-consent__sw" data-cat="preferences"></div>
            </div>
            <button type="button" class="mm-consent__btn mm-consent__btn--accept mt-2" data-act="save">{{ translate('Save preferences') }}</button>
        </div>
    `;
    document.body.appendChild(banner);

    banner.querySelectorAll('.mm-consent__sw[data-cat]').forEach(sw => {
        sw.addEventListener('click', () => sw.classList.toggle('on'));
    });

    banner.addEventListener('click', function(e) {
        var act = e.target.getAttribute('data-act');
        if (!act) return;

        if (act === 'accept') {
            persist({ analytics: true, marketing: true, preferences: true });
            banner.classList.add('mm-consent--hidden');
            setTimeout(() => banner.remove(), 250);
        } else if (act === 'reject') {
            persist({ analytics: false, marketing: false, preferences: false });
            banner.classList.add('mm-consent--hidden');
            setTimeout(() => banner.remove(), 250);
        } else if (act === 'toggle') {
            document.getElementById('mm-consent-cats').classList.toggle('show');
        } else if (act === 'save') {
            var get = (cat) => !!banner.querySelector('.mm-consent__sw[data-cat="' + cat + '"]').classList.contains('on');
            persist({ analytics: get('analytics'), marketing: get('marketing'), preferences: get('preferences') });
            banner.classList.add('mm-consent--hidden');
            setTimeout(() => banner.remove(), 250);
        }
    });
})();
</script>
@endif
