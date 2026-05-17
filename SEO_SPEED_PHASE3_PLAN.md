# BHS Supplies — Phase 3, 4 & 5 Implementation Plan
**Date:** April 22, 2026  
**Current Scores:** PageSpeed Desktop ~45 | Lighthouse ~28-41 (varies) | SEObility 63% | SEO Site Checkup 68/100  
**Why scores vary:** GreenGeek shared hosting TTFB fluctuates 500ms–2000ms. Score 28 = slow server moment; 76 = fast moment. The score variance IS the problem.

---

## SCORE VARIABILITY ROOT CAUSE

The core bottleneck is **server response time (TTFB)**. Lighthouse scores collapse when TTFB > 1.8s because:
- TTFB 1840ms → FCP cannot be < 1840ms → Performance score tanks
- No code fix can overcome a slow TTFB without CDN or server caching

**Only 3 things fix TTFB on shared hosting:**
1. Cloudflare CDN (caches HTML at edge — best option)
2. OPcache (not available on GreenGeek basic plans)
3. Full-page HTML caching in Laravel (complex, session/CSRF issues)

---

## PHASE 3 — DEPLOY PENDING CHANGES (Immediate)

### 3.1 Run on Production Server Now
```bash
# Apply the WebP upload format migration
php artisan migrate

# Bulk-convert existing JPG/PNG uploads to WebP (run in batches)
php artisan images:convert-webp --limit=200 --quality=80
php artisan images:convert-webp --limit=200 --quality=80 --offset=200

# Clear all caches so new settings take effect
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3.2 Admin Panel — Fix These Settings Manually
Go to **Admin → Website Settings**:

| Setting | Current Value | Set To |
|---------|--------------|--------|
| Website Name | "BHS Wholesale & Distributor \| Quality AC..." | `BHS Supplies` |
| Site Motto | "BHS WHOLESALE & DISTRIBUTOR" | `Wholesale HVAC, Plumbing & Electrical` |
| Meta Title | Long duplicated title | `BHS Supplies \| HVAC, Plumbing & Electrical — Mississauga` |
| Meta Description | Current (OK-ish) | `Shop HVAC parts, plumbing & electrical supplies at BHS Supplies — Canada's wholesale distributor. Fast shipping across Canada.` |
| Image Upload Format | default/jpg | **webp** (set this FIRST) |

---

## PHASE 4 — CLOUDFLARE FREE CDN (Highest Impact — 1-2 hours setup)

**Expected gain: +20–30 PageSpeed points, TTFB drops from 1840ms to 200–400ms**

### Why Cloudflare is the #1 Priority
- Caches HTML page at Cloudflare's edge nodes globally
- Automatic WebP conversion for browsers that support it
- Minifies HTML, CSS, JS automatically
- Free HTTPS, HTTP/2, HTTP/3
- DDoS protection included
- Zero code changes needed

### Setup Steps
1. Create free account at cloudflare.com
2. Add site: `bhssupplies.com`
3. Cloudflare scans existing DNS records (import them)
4. Go to GreenGeek cPanel → change nameservers to Cloudflare's (e.g. `sue.ns.cloudflare.com`)
5. Wait 24-48 hours for DNS propagation

### Cloudflare Settings to Enable (after setup)
| Setting | Location | Value |
|---------|----------|-------|
| Auto Minify | Speed → Optimization | CSS ✓ JS ✓ HTML ✓ |
| Rocket Loader | Speed → Optimization | OFF (breaks JS) |
| Polish (WebP) | Speed → Optimization | Lossless |
| Browser Cache TTL | Caching → Configuration | 1 year |
| Caching Level | Caching → Configuration | Standard |
| Always Use HTTPS | SSL/TLS | On |
| HTTP/2 | Network | On |
| HTTP/3 / QUIC | Network | On |
| Brotli | Network | On |

### Page Rules (Free = 3 rules)
```
Rule 1: bhssupplies.com/*
  Cache Level: Cache Everything
  Edge Cache TTL: 4 hours
  
Rule 2: bhssupplies.com/admin/*
  Cache Level: Bypass
  
Rule 3: bhssupplies.com/cart*
  Cache Level: Bypass
```

**Important:** After Cloudflare, run `php artisan optimize` on server — the combination of edge caching + optimized PHP will be transformative.

---

## PHASE 5 — INLINE CRITICAL CSS (Eliminates Render-Blocking)

**Expected gain: +10–15 PageSpeed points after Cloudflare**

### The Problem
`vendors.css` (469KB) and `aiz-core.css` (327KB) block rendering until fully downloaded. Even with GZIP they're ~100KB each. The browser cannot paint anything until both download.

### The Solution
Inline only the CSS needed for above-fold rendering (~8–12KB), then load full CSS asynchronously.

### Step 1 — Extract Critical CSS
Use an automated tool on the production site:
```bash
# Option A: Use npx critical (Node.js tool)
npx critical https://www.bhssupplies.com --inline --minify > critical.css

# Option B: Chrome DevTools Coverage tab
# 1. Open DevTools → More tools → Coverage
# 2. Click record, scroll homepage once
# 3. Filter for vendors.css and aiz-core.css
# 4. Export only used CSS
```

### Step 2 — Create the Critical CSS File
Save extracted critical CSS to:
`d:/wamp/www/bhssupplies1/public/assets/css/critical.css`

Typical critical CSS for this site will include:
- Bootstrap grid: `.container`, `.row`, `.col-*`, `.d-flex`, `.d-none`
- Header: `.aiz-main-wrapper`, `.z-1020`, `.z-1025`, `.z-1035`
- Nav spacing: `.h-35px`, `.h-60px`, `.logo-bar-area`
- Hero: `.home-banner-area`, `.home-slider`, `.carousel-box`
- The CSS variables are already inline ✓

### Step 3 — Implement in app.blade.php
Replace the blocking CSS links with:
```html
<!-- Inline critical CSS (above-fold only) -->
<style>
{!! file_get_contents(public_path('assets/css/critical.css')) !!}
</style>

<!-- Full CSS loads asynchronously (no render-blocking) -->
<link rel="preload" href="...vendors.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="...vendors.css"></noscript>
<link rel="preload" href="...aiz-core.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="...aiz-core.css"></noscript>
<link rel="stylesheet" href="...custom-style.css">
```

**Risk:** If critical.css is missing styles, below-fold content flashes unstyled for ~200ms. Test thoroughly before deploying.

---

## PHASE 6 — JAVASCRIPT OPTIMIZATION

**Expected gain: +5–8 PageSpeed points (TBT improvement)**

### 6.1 Move Inline JS Blocks to External File
`app.blade.php` has ~400 lines of inline JavaScript (flash notifications, popup duration, sale alerts, etc.). These execute synchronously on every page load.

**Create:** `public/assets/js/aiz-inline.js`
Move these sections from `app.blade.php` to the external file:
- Lines ~539–600: Flash notification handler
- Lines ~601–650: Popup duration/stacking code  
- Lines ~700–900: Sale alert ticker
- Lines ~1300–1450: Product modal handlers

Load it at the bottom of body with `defer`:
```html
<script defer src="{{ static_asset('assets/js/aiz-inline.js?v=') }}{{ get_setting('current_version') }}"></script>
```

**Note:** Move only code that doesn't need server-side variables. Code using `{{ $php_var }}` must stay inline.

### 6.2 Evaluate Removing Clarity.ms
Microsoft Clarity adds 146ms CPU time. If not actively used, remove it from GTM to recover those milliseconds.

### 6.3 Code-Split vendors.js
Long-term: Split vendors.js into:
- `core.js` (jQuery + Bootstrap only, ~150KB)
- `plugins.js` (everything else, load per-page as needed)
This requires a build system change (Webpack/Vite).

---

## PHASE 7 — SEO CONTENT & STRUCTURE

**Expected gain: SEObility 63% → 80%+**

### 7.1 Fix Remaining SEObility Issues
| Issue | Fix Location | Action |
|-------|-------------|--------|
| Meta data 67% | Admin → Settings | Fix title + description as in Phase 3.2 |
| Page quality 57% | Homepage content | Add 300+ words of actual content below slider |
| Links 67% | Internal linking | Add footer navigation links, breadcrumbs on category pages |
| External factors 6% | Off-site | Register Google My Business, get directory listings |

### 7.2 Add Homepage Text Content
Google needs readable text content to understand what the page is about.
Add a section below the product carousels in `classic/index.blade.php`:
```html
<section class="py-4">
  <div class="container">
    <h2>Canada's Trusted Wholesale HVAC & Plumbing Distributor</h2>
    <p>BHS Supplies in Mississauga offers contractors and trades professionals...</p>
  </div>
</section>
```

### 7.3 Improve Internal Links
- Add breadcrumbs to category/product pages (Schema.org BreadcrumbList)
- Ensure footer has links to all top categories
- Add "Related products" internal links on product pages

### 7.4 Google My Business
- Register/verify at business.google.com
- Matches the address/phone in the Schema markup
- This directly impacts Local SEO (contractors searching "HVAC supplies Mississauga")

---

## EXPECTED SCORE PROGRESSION

| After | PageSpeed Desktop | Mobile | SEObility | GTmetrix |
|-------|------------------|--------|-----------|---------|
| Phase 3 deploy | 48–55 | 25–35 | 65% | D |
| Phase 4 (Cloudflare) | **70–82** | **50–65** | 68% | C |
| Phase 5 (Critical CSS) | 78–88 | 58–72 | 70% | B |
| Phase 6 (JS opt) | 82–90 | 62–75 | 70% | B |
| Phase 7 (SEO content) | 82–90 | 62–75 | **78–85%** | B |

---

## FILES CHANGED (All Sessions Summary)

### Session 1–3 Changes (Deployed):
| File | Change |
|------|--------|
| `resources/views/frontend/layouts/app.blade.php` | rand() fix, async fonts, resource hints, Organization Schema, DB caching, meta title fix |
| `resources/views/frontend/classic/index.blade.php` | H1 tag, LCP preload, slider dimensions/lazy loading, brand lazy loading, flash deal lazy loading, SEO content section |
| `resources/views/frontend/inc/nav.blade.php` | TopBanner caching |
| `resources/views/header/header1.blade.php` | Logo alt + dimensions |
| `app/Http/Helpers.php` | Cached 6 uncached helper functions |
| `.htaccess` | HTTPS redirect, www redirect, WebP rewrite, GZIP, cache headers, security headers |
| `app/Console/Commands/ConvertImagesToWebP.php` | New artisan command |
| `database/migrations/2026_04_22_000001_set_webp_upload_format.php` | WebP as default format |

### Session 4 Changes (Current — Deploy Now):
| File | Change |
|------|--------|
| `resources/views/frontend/classic/index.blade.php` | SEO content section (300+ words), brand/flash deal lazy loading |
| `resources/views/frontend/product_listing.blade.php` | BreadcrumbList JSON-LD schema |
| `resources/views/frontend/product_details.blade.php` | Product JSON-LD schema, BreadcrumbList schema, LCP preload for first product image |
| `resources/views/frontend/classic/partials/product_box_1.blade.php` | `loading="lazy"` + width/height on product images |
| `resources/views/frontend/product_box_for_listing_page.blade.php` | `loading="lazy"` + width/height on product images |

### Still to Deploy:
```bash
php artisan migrate                           # sets webp upload format
php artisan images:convert-webp --limit=500  # converts existing images
php artisan cache:clear && php artisan optimize
```
