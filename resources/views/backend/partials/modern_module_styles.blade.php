{{-- ============================================================
     Modern Module Design Tokens — shared by:
     Marketing Analytics, AI SEO Suite, Social Media (AI), AI Blog, Amazon
     Include in any backend view via @include('backend.partials.modern_module_styles')
     ============================================================ --}}
<style>
:root {
    --mm-radius:        14px;
    --mm-radius-sm:     10px;
    --mm-shadow:        0 6px 24px rgba(20,20,40,.06);
    --mm-shadow-hover:  0 12px 32px rgba(20,20,40,.10);
    --mm-grad-marketing:  linear-gradient(135deg,#4285F4 0%,#34A853 100%);
    --mm-grad-seo:        linear-gradient(135deg,#7B61FF 0%,#FF61D2 100%);
    --mm-grad-social:     linear-gradient(135deg,#FF6B6B 0%,#FFD93D 50%,#6BCB77 100%);
    --mm-grad-blog:       linear-gradient(135deg,#0EA5E9 0%,#8B5CF6 100%);
    --mm-grad-amazon:     linear-gradient(135deg,#FF9900 0%,#232F3E 100%);
    --mm-grad-reviews:    linear-gradient(135deg,#FBBC04 0%,#EA4335 100%);
}

/* Hero banner — used at the top of each module's main page */
.mm-hero {
    border:0;
    border-radius: var(--mm-radius);
    box-shadow: var(--mm-shadow);
    color:#fff;
    overflow:hidden;
    position:relative;
    margin-bottom:1.5rem;
}
.mm-hero::before {
    content:"";
    position:absolute; inset:0;
    background-image: radial-gradient(circle at 90% 20%, rgba(255,255,255,.12), transparent 40%),
                      radial-gradient(circle at 10% 80%, rgba(255,255,255,.10), transparent 40%);
    pointer-events:none;
}
.mm-hero .mm-hero-body { padding:1.5rem 1.75rem; position:relative; z-index:1; }
.mm-hero h2 { color:#fff; font-weight:700; margin:0 0 .25rem; letter-spacing:-.01em; }
.mm-hero p  { color:rgba(255,255,255,.85); margin:0; font-size:14px; }
.mm-hero .mm-hero-icon {
    width:56px; height:56px;
    background:rgba(255,255,255,.18);
    border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    backdrop-filter: blur(4px);
}
.mm-hero .mm-hero-icon svg { width:30px; height:30px; }

/* Variants */
.mm-hero--marketing { background: var(--mm-grad-marketing); }
.mm-hero--seo       { background: var(--mm-grad-seo); }
.mm-hero--social    { background: var(--mm-grad-social); }
.mm-hero--blog      { background: var(--mm-grad-blog); }
.mm-hero--amazon    { background: var(--mm-grad-amazon); }
.mm-hero--reviews   { background: var(--mm-grad-reviews); }

/* Pill chips inside hero */
.mm-chip {
    display:inline-flex; align-items:center;
    background:rgba(255,255,255,.18);
    color:#fff; padding:.35rem .75rem;
    border-radius:999px; font-size:12.5px; font-weight:600;
    backdrop-filter: blur(3px);
}
.mm-chip i { margin-right:.35rem; }

/* Modern stat card */
.mm-stat {
    border:1px solid #eef0f4;
    border-radius: var(--mm-radius);
    background:#fff;
    padding:1.25rem;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    height:100%;
    box-shadow: 0 1px 3px rgba(20,20,40,.04);
    position:relative;
    overflow:hidden;
}
.mm-stat:hover {
    transform: translateY(-3px);
    box-shadow: var(--mm-shadow-hover);
    border-color:transparent;
}
.mm-stat .mm-stat-icon {
    width:42px; height:42px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    margin-bottom:.75rem;
}
.mm-stat .mm-stat-icon svg { width:22px; height:22px; }
.mm-stat .mm-stat-value { font-size:24px; font-weight:700; color:#1f2937; line-height:1.1; margin:0; }
.mm-stat .mm-stat-label { font-size:12.5px; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-top:.2rem; }
.mm-stat .mm-stat-delta { font-size:12px; font-weight:600; margin-top:.5rem; display:inline-flex; align-items:center; gap:.25rem; }
.mm-stat .mm-stat-delta.up   { color:#10B981; }
.mm-stat .mm-stat-delta.down { color:#EF4444; }

/* Accent color helpers for icon backgrounds */
.mm-tint-blue   { background:rgba(66,133,244,.10); color:#4285F4; }
.mm-tint-green  { background:rgba(52,168,83,.10);  color:#34A853; }
.mm-tint-red    { background:rgba(234,67,53,.10);  color:#EA4335; }
.mm-tint-yellow { background:rgba(251,188,4,.12);  color:#FBBC04; }
.mm-tint-purple { background:rgba(123,97,255,.10); color:#7B61FF; }
.mm-tint-pink   { background:rgba(255,97,210,.10); color:#FF61D2; }
.mm-tint-orange { background:rgba(255,153,0,.10);  color:#FF9900; }
.mm-tint-cyan   { background:rgba(14,165,233,.10); color:#0EA5E9; }
.mm-tint-slate  { background:rgba(35,47,62,.10);   color:#232F3E; }

/* Module section card */
.mm-card {
    border:1px solid #eef0f4;
    border-radius: var(--mm-radius);
    background:#fff;
    box-shadow: 0 1px 3px rgba(20,20,40,.04);
    overflow:hidden;
    margin-bottom:1.25rem;
}
.mm-card .mm-card-header {
    padding:1rem 1.25rem;
    border-bottom:1px solid #f3f4f6;
    display:flex; align-items:center; justify-content:space-between;
}
.mm-card .mm-card-title { margin:0; font-weight:700; font-size:15px; color:#111827; }
.mm-card .mm-card-body  { padding:1.25rem; }

/* Tile (action launcher) */
.mm-tile {
    display:flex; align-items:flex-start; gap:.85rem;
    padding:1rem; border:1px solid #eef0f4;
    border-radius: var(--mm-radius-sm); background:#fff;
    text-decoration:none; color:inherit;
    transition: all .18s ease;
    height:100%;
}
.mm-tile:hover {
    text-decoration:none; color:inherit;
    transform: translateY(-2px);
    border-color: #c7d2fe;
    box-shadow: var(--mm-shadow);
}
.mm-tile .mm-tile-icon {
    width:38px; height:38px; flex-shrink:0;
    border-radius:10px;
    display:flex; align-items:center; justify-content:center;
}
.mm-tile h6 { margin:0 0 .15rem; font-weight:700; font-size:14px; color:#111827; }
.mm-tile p  { margin:0; font-size:12.5px; color:#6b7280; line-height:1.4; }

/* Status dot */
.mm-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:.4rem; }
.mm-dot.ok   { background:#10B981; box-shadow: 0 0 0 3px rgba(16,185,129,.18); }
.mm-dot.warn { background:#FBBC04; box-shadow: 0 0 0 3px rgba(251,188,4,.18); }
.mm-dot.err  { background:#EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,.18); }

/* Sleek button override */
.mm-btn {
    border-radius: 10px;
    padding: .55rem 1.1rem;
    font-weight: 600;
    font-size: 13.5px;
    letter-spacing: .01em;
    border: none;
    transition: all .15s ease;
    display:inline-flex; align-items:center; gap:.4rem;
}
.mm-btn:hover { transform: translateY(-1px); }
.mm-btn-light { background:#fff; color:#111827; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.mm-btn-light:hover { background:#fff; color:#111827; box-shadow: 0 4px 14px rgba(0,0,0,.10); }
.mm-btn-ghost { background: rgba(255,255,255,.18); color:#fff; backdrop-filter:blur(3px); }
.mm-btn-ghost:hover { background: rgba(255,255,255,.28); color:#fff; }

/* Section header with action */
.mm-section-head {
    display:flex; align-items:center; justify-content:space-between;
    margin: 0 0 1rem;
}
.mm-section-head h5 { margin:0; font-weight:700; font-size:16px; color:#111827; }
.mm-section-head .subtitle { font-size:13px; color:#6b7280; margin-top:.15rem; }

/* Responsive tweaks */
@media (max-width: 576px) {
    .mm-hero .mm-hero-body { padding:1.1rem 1.1rem; }
    .mm-hero h2 { font-size: 1.15rem; }
    .mm-stat .mm-stat-value { font-size:20px; }
}
</style>
