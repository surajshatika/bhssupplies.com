<style>
:root {
    --seo-ui-ink: #17212b;
    --seo-ui-muted: #667085;
    --seo-ui-line: #e6eaf0;
    --seo-ui-soft: #f7f9fc;
    --seo-ui-primary: #146c7e;
    --seo-ui-primary-soft: #e9f6f8;
    --seo-ui-success: #15805d;
    --seo-ui-warning: #a66a00;
    --seo-ui-danger: #c33f4a;
}

.aiz-main-content .aiz-titlebar {
    margin-top: .35rem !important;
    margin-bottom: 1rem !important;
    padding: .15rem 0;
}
.aiz-main-content .aiz-titlebar h1 {
    margin-bottom: .2rem !important;
    color: var(--seo-ui-ink);
    font-size: 1.45rem;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: 0;
}
.aiz-main-content .aiz-titlebar p {
    color: var(--seo-ui-muted) !important;
    font-size: .82rem;
    line-height: 1.45;
}
.aiz-main-content .card,
.aiz-main-content .mon-card,
.aiz-main-content .ai-board-stat,
.aiz-main-content .optimization-panel {
    border: 1px solid var(--seo-ui-line) !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(23, 33, 43, .035) !important;
}
.aiz-main-content .card-header,
.aiz-main-content .mon-card .mon-card-head {
    min-height: 49px;
    padding: .78rem 1rem;
    border-bottom: 1px solid var(--seo-ui-line) !important;
    background: #fff;
}
.aiz-main-content .card-header h5,
.aiz-main-content .card-header h6,
.aiz-main-content .mon-card .mon-card-head {
    color: var(--seo-ui-ink);
    font-size: .9rem;
    font-weight: 700 !important;
    letter-spacing: 0;
}
.aiz-main-content .card-body {
    padding: 1rem;
}
.aiz-main-content .advanced-metric,
.aiz-main-content .optimization-metric,
.aiz-main-content .mon-stat,
.aiz-main-content .seo-file-health {
    min-height: 98px;
    padding: .85rem !important;
    border: 1px solid var(--seo-ui-line) !important;
    border-radius: 7px !important;
    background: #fff;
    box-shadow: none;
}
.aiz-main-content .advanced-metric .metric-value,
.aiz-main-content .optimization-metric .metric-value,
.aiz-main-content .mon-stat .num {
    font-size: 1.45rem !important;
    letter-spacing: 0;
}
.aiz-main-content .advanced-action,
.aiz-main-content .optimization-action,
.aiz-main-content .mm-tile {
    border-radius: 7px !important;
    box-shadow: none !important;
}
.aiz-main-content .table-responsive {
    border-color: var(--seo-ui-line) !important;
    border-radius: 7px !important;
}
.aiz-main-content .table {
    color: #344054;
    font-size: .79rem;
}
.aiz-main-content .table thead th,
.aiz-main-content .mini-table th {
    border-bottom: 1px solid var(--seo-ui-line) !important;
    background: #f6f8fb;
    color: #667085;
    font-size: .69rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
    white-space: nowrap;
}
.aiz-main-content .table td,
.aiz-main-content .table th {
    padding: .58rem .62rem;
    vertical-align: middle;
}
.aiz-main-content .table tbody tr:hover {
    background: #f9fbfc;
}
.aiz-main-content .table a {
    color: #176b87;
    font-weight: 600;
}
.aiz-main-content .badge {
    max-width: 100%;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: 0;
    line-height: 1.3;
    white-space: normal;
}
.aiz-main-content .btn {
    border-radius: 6px;
    font-weight: 600;
    letter-spacing: 0;
}
.aiz-main-content .btn-primary {
    border-color: var(--seo-ui-primary);
    background: var(--seo-ui-primary);
}
.aiz-main-content .form-control {
    border-color: #d8dee8;
    border-radius: 6px;
}
.aiz-main-content .form-control:focus {
    border-color: #55a7b5;
    box-shadow: 0 0 0 3px rgba(20, 108, 126, .11);
}
.aiz-main-content .alert {
    border-radius: 7px;
    border-width: 1px;
}
.aiz-main-content .nav-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
}
.aiz-main-content .nav-tabs .nav-link {
    border-radius: 6px 6px 0 0;
    white-space: nowrap;
}
.aiz-main-content .tool-card {
    border: 1px solid var(--seo-ui-line) !important;
    border-radius: 7px !important;
    box-shadow: none !important;
}
.aiz-main-content .tool-card:hover {
    border-color: #a8d2d9 !important;
    box-shadow: 0 7px 18px rgba(23, 33, 43, .08) !important;
    transform: translateY(-2px) !important;
}
.aiz-main-content .mm-hero {
    margin-bottom: 1rem;
    border-radius: 8px;
    box-shadow: 0 5px 14px rgba(23, 33, 43, .08);
}
.aiz-main-content .mm-hero::before {
    display: none;
}
.aiz-main-content .mm-hero--seo {
    background: #235762;
}
.aiz-main-content .mm-hero .mm-hero-body {
    padding: 1.05rem 1.2rem;
}
.aiz-main-content .mm-hero h2 {
    font-size: 1.35rem;
    letter-spacing: 0;
}
.aiz-main-content .mm-hero .mm-hero-icon {
    width: 46px;
    height: 46px;
    border-radius: 8px;
}
.aiz-main-content .mm-chip {
    border-radius: 999px;
    font-size: .7rem;
}
.aiz-main-content .seo-dashboard-jumpbar {
    display: flex;
    align-items: center;
    gap: .3rem;
    margin-bottom: 1rem;
    padding: .42rem;
    overflow-x: auto;
    border: 1px solid var(--seo-ui-line);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(23, 33, 43, .035);
    scrollbar-width: thin;
}
.aiz-main-content .seo-dashboard-jumpbar a {
    display: inline-flex;
    align-items: center;
    gap: .32rem;
    padding: .42rem .58rem;
    border-radius: 6px;
    color: #52606d;
    font-size: .74rem;
    font-weight: 700;
    white-space: nowrap;
}
.aiz-main-content .seo-dashboard-jumpbar a:hover {
    background: var(--seo-ui-primary-soft);
    color: var(--seo-ui-primary);
    text-decoration: none;
}
.aiz-main-content .seo-section-anchor {
    scroll-margin-top: 82px;
}
.aiz-main-content .seo-board-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: .35rem;
}
.aiz-main-content .seo-tool-tile {
    min-height: 76px;
}
.aiz-main-content .seo-tool-icon {
    display: inline-flex;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    background: var(--seo-ui-primary-soft);
    color: var(--seo-ui-primary);
    font-size: 1.2rem;
}
.aiz-main-content .seo-tool-icon.success {
    background: rgba(21, 128, 93, .11);
    color: var(--seo-ui-success);
}

@media (max-width: 767.98px) {
    .aiz-main-content .aiz-titlebar h1 {
        font-size: 1.2rem;
    }
    .aiz-main-content .aiz-titlebar .text-md-right {
        margin-top: .7rem;
        text-align: left !important;
    }
    .aiz-main-content .card-header {
        align-items: flex-start !important;
        gap: .55rem;
    }
    .aiz-main-content .card-header form,
    .aiz-main-content .card-header .d-flex {
        max-width: 100%;
    }
    .aiz-main-content .card-header select {
        max-width: 100%;
    }
    .aiz-main-content .advanced-metric,
    .aiz-main-content .optimization-metric,
    .aiz-main-content .mon-stat {
        min-height: 88px;
    }
    .aiz-main-content .table {
        font-size: .75rem;
    }
    .aiz-main-content .mm-hero .mm-hero-body {
        padding: .9rem;
    }
    .aiz-main-content .mm-hero-icon {
        display: none !important;
    }
    .aiz-main-content .seo-board-actions {
        justify-content: flex-start;
        margin-top: .7rem;
    }
}
</style>

{{-- ============================================================
     Premium UI layer — shared across the whole AI SEO Suite.
     Loaded after the base tokens above so it cascades last and wins.
     Pure visual polish (depth, gradients, hover states); no markup
     or logic changes. Targets the equivalent class names used on
     every suite page (Suite, On-Page, Off-Page, Optimization,
     AI Board, Monitoring, Assistant, Writer, Keywords, Stats, etc.).
     ============================================================ --}}
<style>
.aiz-main-content {
    --sx-primary: #146c7e;
    --sx-primary-2: #1f9e8c;
}

/* ---- Hero: depth + on-brand gradient ---- */
.aiz-main-content .mm-hero--seo {
    background: linear-gradient(125deg, #0e4654 0%, #146c7e 48%, #1f9e8c 100%) !important;
    border-radius: 16px !important;
    box-shadow: 0 14px 34px rgba(14, 76, 92, .26) !important;
}
.aiz-main-content .mm-hero::before {
    display: block !important;
    background-image:
        radial-gradient(circle at 88% 12%, rgba(255,255,255,.16), transparent 42%),
        radial-gradient(circle at 8% 96%, rgba(255,255,255,.10), transparent 46%);
}
.aiz-main-content .mm-hero .mm-hero-body { padding: 1.55rem 1.8rem !important; }
.aiz-main-content .mm-hero h2 { font-size: 1.55rem !important; letter-spacing: -.01em !important; }
.aiz-main-content .mm-hero .mm-hero-icon {
    width: 56px !important; height: 56px !important; border-radius: 15px !important;
    background: rgba(255,255,255,.20) !important;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
}
.aiz-main-content .mm-hero .mm-chip {
    background: rgba(255,255,255,.16) !important;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.14);
    padding: .4rem .8rem !important;
}

/* ---- Section cards: soft layered shadow + accented headers ---- */
.aiz-main-content .card,
.aiz-main-content .mon-card,
.aiz-main-content .optimization-panel {
    border: 1px solid #e9edf3 !important;
    border-radius: 14px !important;
    box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 10px 26px rgba(16,24,40,.05) !important;
}
.aiz-main-content .card-header,
.aiz-main-content .mon-card .mon-card-head {
    background: linear-gradient(180deg, #fcfdff 0%, #f6f9fc 100%) !important;
    border-bottom: 1px solid #eef1f6 !important;
    padding: .9rem 1.2rem !important;
}
.aiz-main-content .card-header h5,
.aiz-main-content .card-header h6 {
    position: relative;
    padding-left: .82rem;
    font-size: .95rem !important;
}
.aiz-main-content .card-header h5::before,
.aiz-main-content .card-header h6::before {
    content: "";
    position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    width: 4px; height: 1.05em; border-radius: 3px;
    background: linear-gradient(180deg, var(--sx-primary), var(--sx-primary-2));
}
/* nested card-header-tabs shouldn't get the accent bar */
.aiz-main-content .card-header .nav-tabs h5::before { display: none; }

/* ---- Metric / stat tiles: premium feel with hover lift + accent ---- */
.aiz-main-content .advanced-metric,
.aiz-main-content .optimization-metric,
.aiz-main-content .mon-stat,
.aiz-main-content .ai-board-stat {
    border: 1px solid #e9edf3 !important;
    border-radius: 13px !important;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%) !important;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.aiz-main-content .advanced-metric::after,
.aiz-main-content .optimization-metric::after,
.aiz-main-content .mon-stat::after,
.aiz-main-content .ai-board-stat::after {
    content: "";
    position: absolute; left: 0; top: 0; height: 3px; width: 100%;
    background: linear-gradient(90deg, var(--sx-primary), var(--sx-primary-2));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .22s ease;
}
.aiz-main-content .advanced-metric:hover,
.aiz-main-content .optimization-metric:hover,
.aiz-main-content .mon-stat:hover,
.aiz-main-content .ai-board-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(16,24,40,.10) !important;
    border-color: #cfe3e7 !important;
}
.aiz-main-content .advanced-metric:hover::after,
.aiz-main-content .optimization-metric:hover::after,
.aiz-main-content .mon-stat:hover::after,
.aiz-main-content .ai-board-stat:hover::after { transform: scaleX(1); }
.aiz-main-content .advanced-metric .metric-value,
.aiz-main-content .optimization-metric .metric-value,
.aiz-main-content .mon-stat .num {
    font-size: 1.72rem !important;
    font-weight: 800 !important;
    letter-spacing: -.01em !important;
}

/* ---- Action / file-health / tool cards ---- */
.aiz-main-content .advanced-action,
.aiz-main-content .optimization-action,
.aiz-main-content .seo-file-health {
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%) !important;
    border-radius: 11px !important;
    transition: transform .16s ease, box-shadow .16s ease;
}
.aiz-main-content .advanced-action:hover,
.aiz-main-content .optimization-action:hover,
.aiz-main-content .seo-file-health:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(16,24,40,.08) !important;
}

/* ---- Buttons: rounder, subtle depth, lift on hover ---- */
.aiz-main-content .btn { border-radius: 9px !important; }
.aiz-main-content .btn-xs { border-radius: 7px !important; }
.aiz-main-content .btn-primary {
    background: linear-gradient(135deg, var(--sx-primary), var(--sx-primary-2)) !important;
    border: none !important;
    box-shadow: 0 4px 13px rgba(20,108,126,.28);
}
.aiz-main-content .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 7px 18px rgba(20,108,126,.34); }
.aiz-main-content .btn-soft-primary:hover,
.aiz-main-content .btn-soft-success:hover,
.aiz-main-content .btn-soft-info:hover,
.aiz-main-content .btn-soft-danger:hover { transform: translateY(-1px); }

/* ---- Progress bars: pill + gradient fills ---- */
.aiz-main-content .progress { background: #eef1f6 !important; border-radius: 999px !important; overflow: hidden; }
.aiz-main-content .progress-bar { border-radius: 999px !important; }
.aiz-main-content .progress-bar.bg-primary { background: linear-gradient(90deg, var(--sx-primary), var(--sx-primary-2)) !important; }
.aiz-main-content .progress-bar.bg-success { background: linear-gradient(90deg, #15805d, #25b487) !important; }

/* ---- Tables: crisper headers, gentle row hover ---- */
.aiz-main-content .table thead th,
.aiz-main-content .mini-table th {
    background: linear-gradient(180deg, #f7f9fc, #f1f4f9) !important;
    color: #5a6677 !important;
    border-top: none !important;
}
.aiz-main-content .table tbody tr { transition: background .12s ease; }
.aiz-main-content .table tbody tr:hover { background: #f4fafb !important; }

/* ---- Tabs: clean pill/underline style ---- */
.aiz-main-content .nav-tabs.card-header-tabs { border-bottom: none; gap: .15rem; }
.aiz-main-content .nav-tabs .nav-link {
    border: none !important;
    color: #667085 !important;
    font-weight: 700 !important;
    padding: .55rem .9rem !important;
    border-radius: 9px !important;
    transition: background .15s ease, color .15s ease;
}
.aiz-main-content .nav-tabs .nav-link:hover { background: #eef6f7 !important; color: var(--sx-primary) !important; }
.aiz-main-content .nav-tabs .nav-link.active {
    background: linear-gradient(135deg, var(--sx-primary), var(--sx-primary-2)) !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(20,108,126,.26);
}

/* ---- Jump bar: pill links + active/hover states ---- */
.aiz-main-content .seo-dashboard-jumpbar {
    border-radius: 12px !important;
    box-shadow: 0 4px 14px rgba(16,24,40,.06) !important;
    padding: .5rem !important;
}
.aiz-main-content .seo-dashboard-jumpbar a {
    border: 1px solid transparent;
    transition: all .15s ease;
}
.aiz-main-content .seo-dashboard-jumpbar a:hover {
    background: var(--sx-primary) !important;
    color: #fff !important;
    box-shadow: 0 4px 11px rgba(20,108,126,.25);
}

/* ---- Suite top strip: align with premium look ---- */
.aiz-main-content .seo-suite-strip { border-radius: 12px !important; box-shadow: 0 4px 14px rgba(16,24,40,.06) !important; }
.aiz-main-content .seo-suite-strip .seo-nav-link.active {
    background: linear-gradient(135deg, var(--sx-primary), var(--sx-primary-2)) !important;
    color: #fff !important;
}

/* ---- Score ring: soft glow behind the dial ---- */
.aiz-main-content .seo-score-ring { position: relative; }
.aiz-main-content .seo-score-ring::before {
    content: "";
    position: absolute; inset: 14px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(20,108,126,.06), transparent 70%);
}

/* ---- Tool tiles: smoother surface + icon chips ---- */
.aiz-main-content .seo-tool-icon { box-shadow: inset 0 0 0 1px rgba(20,108,126,.10); }

/* ---- Alerts: softer, modern ---- */
.aiz-main-content .alert { border-radius: 11px !important; }

/* ============================================================
   LAYOUT FIXES — stop status chips from collapsing & overlapping
   ============================================================ */

/* ---- Badge core reset ----
   The theme's aiz-core.css forces .badge to a FIXED 18x18px,
   inline-flex, centred box (designed for tiny dot badges). With real
   text labels in narrow columns that collapses them into circles
   ("Re", "co", "St") and overlaps adjacent chips. Restore proper,
   content-sized pills that never shrink when used as flex items. */
.aiz-main-content .badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    width: auto !important;          /* override the theme's fixed 18px */
    height: auto !important;         /* let padding define the height */
    min-width: 0 !important;
    max-width: 100% !important;      /* never spill outside the parent box */
    padding: .34em .64em !important;
    line-height: 1.25 !important;
    white-space: normal !important;  /* wrap inside the box instead of overflowing */
    word-break: normal !important;
    overflow-wrap: anywhere;         /* break long unbroken tokens (e.g. URLs) */
    text-align: left;
    vertical-align: middle;
}
/* sub-lines deliberately placed inside a badge may still wrap */
.aiz-main-content .badge .d-block { white-space: normal; }
/* keep genuine tiny dot badges as dots (theme defaults) */
.aiz-main-content .badge.badge-dot {
    width: 8px !important; height: 8px !important; min-width: 8px !important; padding: 0 !important;
}
/* The theme leaves SOLID badges inheriting dark text (it only ever used
   them as text-less dots). With real labels that is unreadable on
   saturated backgrounds, so set proper contrast. */
.aiz-main-content .badge-primary,
.aiz-main-content .badge-success,
.aiz-main-content .badge-danger,
.aiz-main-content .badge-info,
.aiz-main-content .badge-secondary,
.aiz-main-content .badge-dark { color: #fff !important; }
.aiz-main-content .badge-warning,
.aiz-main-content .badge-light { color: #4a3b00 !important; }

/* Header / toolbar rows: title on the left absorbs the spare width
   (and wraps), while the trailing action/chip cluster keeps its
   natural width instead of being crushed. */
.aiz-main-content .card-header.justify-content-between > *:last-child,
.aiz-main-content .card-header > .d-flex,
.aiz-main-content .card-header > form.d-flex,
.aiz-main-content .d-flex.flex-wrap.justify-content-between > *:last-child {
    flex-shrink: 0;
}
.aiz-main-content .card-header.justify-content-between > *:first-child,
.aiz-main-content .d-flex.flex-wrap.justify-content-between > *:first-child {
    flex: 1 1 auto;
    min-width: 0;
}

/* Action / chip clusters wrap neatly as rows of whole chips. */
.aiz-main-content .card-header > .d-flex.flex-wrap,
.aiz-main-content .d-flex.flex-wrap.justify-content-between > *:last-child {
    row-gap: .35rem;
    column-gap: .3rem;
}

/* Long text / URLs in table cells wrap inside the cell instead of
   pushing content outside the card. (Cells using .text-truncate keep
   their own ellipsis behaviour.) */
.aiz-main-content .table td:not(.text-truncate),
.aiz-main-content .table th { overflow-wrap: anywhere; word-break: break-word; }

/* ============================================================
   BRAND PALETTE — match the storefront (navy banner + red→orange)
   Loaded last so it overrides the teal defaults everywhere.
   Storefront refs: banner #1a1a2e→#0f3460, red #e8241a, orange #ff6b00.
   ============================================================ */
.aiz-main-content {
    --sx-primary:        #e8241a;   /* brand red */
    --sx-primary-2:      #ff6b00;   /* brand orange */
    /* Re-tint the base design tokens so every inherited rule follows. */
    --seo-ui-primary:      #e8241a;
    --seo-ui-primary-soft: #fdeae5;
    --seo-ui-ink:          #1a1a2e;
}

/* Hero → storefront navy banner */
.aiz-main-content .mm-hero--seo {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%) !important;
    box-shadow: 0 14px 34px rgba(15, 52, 96, .30) !important;
}

/* Primary buttons / tabs / jumpbar shadows recoloured to brand red */
.aiz-main-content .btn-primary { box-shadow: 0 4px 13px rgba(232, 36, 26, .28); }
.aiz-main-content .btn-primary:hover { box-shadow: 0 7px 18px rgba(232, 36, 26, .34); }
.aiz-main-content .nav-tabs .nav-link.active { box-shadow: 0 4px 12px rgba(232, 36, 26, .26); }
.aiz-main-content .nav-tabs .nav-link:hover { background: #fdeee9 !important; color: var(--sx-primary) !important; }
.aiz-main-content .seo-dashboard-jumpbar a:hover { box-shadow: 0 4px 11px rgba(232, 36, 26, .22); }
.aiz-main-content .seo-suite-strip .seo-nav-link:hover { background: #fdeee9 !important; color: #c41d14 !important; }

/* Primary colour family → brand red (numbers, icons, soft chips) */
.aiz-main-content .text-primary { color: #e8241a !important; }
.aiz-main-content .badge-primary { background: #e8241a !important; border-color: #e8241a !important; }
.aiz-main-content .badge-soft-primary { background: rgba(232, 36, 26, .10) !important; color: #c41d14 !important; }
.aiz-main-content .btn-soft-primary { background: rgba(232, 36, 26, .10) !important; color: #c41d14 !important; }
.aiz-main-content .btn-soft-primary:hover { background: rgba(232, 36, 26, .16) !important; color: #a8170f !important; }
.aiz-main-content .border-primary,
.aiz-main-content .module-card.on-page { border-color: #e8241a !important; }

/* Inputs, links, hovers, accents */
.aiz-main-content .form-control:focus { border-color: #ff8a4d !important; box-shadow: 0 0 0 3px rgba(232, 36, 26, .12) !important; }
.aiz-main-content .table a { color: #0f3460 !important; }
.aiz-main-content .table tbody tr:hover { background: #fff6f2 !important; }
.aiz-main-content .seo-score-ring::before { background: radial-gradient(circle, rgba(232, 36, 26, .06), transparent 70%); }
.aiz-main-content .seo-tool-icon { box-shadow: inset 0 0 0 1px rgba(232, 36, 26, .12); }
</style>
