@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies North York | Wholesale Sheet Metal, PEX, Gas Valves | BHS Supplies')
@section('meta_description', 'BHS Supplies serves North York HVAC contractors & plumbers with wholesale sheet metal duct fittings, PEX pipe, brass fittings, CSST, gas valves & refrigerants. Same-day pickup ~20 min from North York. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies North York, sheet metal duct fittings North York, PEX pipe wholesale North York, gas valve contractor North York, HVAC supply store near North York Toronto, plumbing supplies North York wholesale')

@section('canonical'){{ route('locations.north-york') }}@endsection

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "HVAC Supplies North York", "item": "{{ route('locations.north-york') }}"}
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "@id": "{{ route('locations.north-york') }}#service",
  "name": "Wholesale HVAC & Plumbing Supplies — North York",
  "description": "BHS Supplies provides wholesale HVAC equipment, sheet metal duct fittings, PEX pipe, gas valves, and plumbing supplies for licensed contractors in North York, Downsview, Willowdale, and Don Mills. Approximately 20 minutes via Highway 401 West.",
  "url": "{{ route('locations.north-york') }}",
  "serviceType": "Wholesale HVAC & Plumbing Supply",
  "areaServed": {"@type": "City", "name": "North York", "addressCountry": "CA"},
  "provider": {"@type": "HVACBusiness", "@id": "{{ url('/') }}#localbusiness", "name": "BHS Supplies", "telephone": "+1-647-456-2244"}
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies North York</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies for North York Contractors — Wholesale, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> is a top-choice <strong>wholesale HVAC and plumbing supplier</strong> for licensed contractors working in North York, Downsview, Jane and Finch, Don Mills, and Willowdale. Our <strong>Mississauga warehouse at 7040 Torbram Rd #8</strong> is approximately <strong>20 minutes from North York via Highway 401 West or Highway 400</strong>. We carry 2,000+ SKUs with <strong>same-day walk-in pickup available 7 days a week</strong>. No minimum order. Trade accounts with volume pricing available.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Sheet Metal Duct Fittings for North York HVAC Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                North York HVAC contractors — from residential furnace replacements to commercial condo retrofits — rely on BHS Supplies for a full selection of <strong>sheet metal duct fittings</strong>: round and rectangular elbows, tees, wyes, reducers, takeoffs, end caps, and transitions. We carry <strong>flexible duct in R4.2, R6, and R8 insulation ratings</strong>, duct board, aluminum tape, and mastic sealant — all for same-day pickup.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">PEX Pipe, Brass & Copper Fittings — North York Plumber Wholesale</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                North York plumbers and gas contractors choose BHS for <strong>wholesale plumbing supply pricing</strong>. We stock <strong>PEX-A and PEX-B pipe (all sizes)</strong>, <strong>brass ball valves, elbows, tees, and couplings</strong>, copper fittings, push-fit connectors, <strong>gas valves</strong>, <strong>black iron pipe (all schedules)</strong>, <strong>CSST flexible gas piping</strong>, backflow preventers, water heater parts, and more.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Refrigerants & HVAC Accessories for North York Technicians</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                In stock for North York HVAC technicians: <strong>R-410A, R-32, R-454B</strong> at wholesale pricing, <strong>air filters in all sizes</strong>, programmable and smart <strong>thermostats</strong>, exhaust fans, HRV accessories, and all HVAC service tools including vacuum pumps, manifold gauges, and recovery machines.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Trade Accounts for North York Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Set up a <strong>BHS trade account</strong> for volume pricing and priority stock access — ideal for North York-based HVAC companies and plumbing contractors. Register at <strong>bhssupplies.com</strong> or call <strong>(647) 456-2244</strong>. Our B2B portal lets you check live inventory and place pickup orders before leaving your job site.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Getting Here from North York — Via Highway 401 West</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                From North York: Take <strong>Highway 401 West</strong> to Airport Rd / Torbram Rd North — our warehouse is at <strong>7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</strong>. Alternatively from Downsview, take Highway 400 South to Highway 401 West. Open <strong>Mon–Sat 10am–6pm and Sunday 10am–2pm</strong>. Free parking on site. Serving North York, Downsview, Willowdale, Don Mills, and all central Toronto zones.
            </p>

        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-700 fs-15">
                    <i class="las la-store mr-1"></i> Pickup Location — Mississauga
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled fs-14 mb-3">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="las la-map-marker text-primary mr-2 mt-1"></i>
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4<br><small class="text-gray">(~20 min from North York via Hwy 401)</small></span>
                        </li>
                        <li class="mb-2">
                            <i class="las la-phone text-primary mr-2"></i>
                            <a href="tel:+16474562244" class="text-dark fw-600">(647) 456-2244</a>
                        </li>
                        <li>
                            <i class="las la-envelope text-primary mr-2"></i>
                            <a href="mailto:support@bhssupplies.com" class="text-dark">support@bhssupplies.com</a>
                        </li>
                    </ul>
                    <div class="border-top pt-3">
                        <p class="fw-700 fs-14 mb-2 text-dark">Store Hours</p>
                        <table class="table table-sm table-borderless fs-13 mb-0">
                            <tr><td class="text-gray pl-0">Mon – Sat</td><td class="fw-600 text-dark">10:00 AM – 6:00 PM</td></tr>
                            <tr><td class="text-gray pl-0">Sunday</td><td class="fw-600 text-dark">10:00 AM – 2:00 PM</td></tr>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('search') }}" class="btn btn-primary btn-block fw-600 mb-2">Shop All Products</a>
                        <a href="tel:+16474562244" class="btn btn-outline-primary btn-block fw-600">Call to Order</a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2884.283424164344!2d-79.6631853!3d43.7046894!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b3ebbfdf989b5%3A0xc3f8e6c7d1e8c9b!2s7040%20Torbram%20Rd%20%238%2C%20Mississauga%2C%20ON%20L4T%203Z4!5e0!3m2!1sen!2sca!4v1690000000000!5m2!1sen!2sca"
                        width="100%" height="260" style="border:0;border-radius:0 0 4px 4px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <div class="card border-0 border shadow-sm">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Also Serving</h3>
                    <ul class="list-unstyled fs-14 mb-0">
                        <li class="mb-1"><a href="{{ route('locations.mississauga') }}" class="text-primary">HVAC Supplies Mississauga</a></li>
                        <li class="mb-1"><a href="{{ route('locations.toronto') }}" class="text-primary">HVAC Supplies Toronto</a></li>
                        <li class="mb-1"><a href="{{ route('locations.etobicoke') }}" class="text-primary">HVAC Supplies Etobicoke</a></li>
                        <li class="mb-1"><a href="{{ route('locations.vaughan') }}" class="text-primary">HVAC Supplies Vaughan</a></li>
                        <li><a href="{{ route('home') }}" class="text-primary">All Products — BHS Supplies</a></li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Contractor Resources</h3>
                    <ul class="list-unstyled fs-14 mb-0">
                        <li class="mb-2"><a href="{{ route('trade-account') }}" class="text-primary fw-600"><i class="las la-id-card mr-1"></i> Set Up Trade Account</a></li>
                        <li class="mb-2"><a href="{{ url('/blogs') }}" class="text-primary"><i class="las la-book mr-1"></i> Contractor Guides & Tips</a></li>
                        <li><a href="{{ route('review') }}" class="text-primary"><i class="las la-star mr-1"></i> Leave Us a Review</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
