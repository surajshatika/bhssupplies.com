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
