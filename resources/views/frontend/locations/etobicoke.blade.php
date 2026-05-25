@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Etobicoke | Wholesale PEX, Sheet Metal, Gas Valves | BHS Supplies')
@section('meta_description', 'BHS Supplies serves Etobicoke HVAC contractors with wholesale sheet metal duct fittings, PEX pipe, brass fittings, gas valves & refrigerants. Same-day pickup 15 min from Etobicoke via Hwy 427. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies Etobicoke, sheet metal duct fittings Etobicoke, PEX pipe wholesale Etobicoke, gas valve Etobicoke contractor, HVAC supply store near Etobicoke, plumbing supplies Etobicoke wholesale')

@section('canonical'){{ route('locations.etobicoke') }}@endsection

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "HVAC Supplies Etobicoke", "item": "{{ route('locations.etobicoke') }}"}
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "@id": "{{ route('locations.etobicoke') }}#service",
  "name": "Wholesale HVAC & Plumbing Supplies — Etobicoke",
  "description": "BHS Supplies provides wholesale HVAC equipment, sheet metal duct fittings, PEX pipe, gas valves, and plumbing supplies for licensed contractors in Etobicoke and west-end Toronto. Approximately 15 minutes via Highway 427.",
  "url": "{{ route('locations.etobicoke') }}",
  "serviceType": "Wholesale HVAC & Plumbing Supply",
  "areaServed": {"@type": "City", "name": "Etobicoke", "addressCountry": "CA"},
  "provider": {"@type": "HVACBusiness", "@id": "{{ url('/') }}#localbusiness", "name": "BHS Supplies", "telephone": "+1-647-456-2244"}
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies Etobicoke</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies for Etobicoke Contractors — Wholesale, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> is the closest <strong>wholesale HVAC and plumbing supplier</strong> for licensed contractors working in Etobicoke, Rexdale, Islington, Mimico, and Long Branch. Our Mississauga warehouse at <strong>7040 Torbram Rd #8</strong> is approximately <strong>15 minutes from central Etobicoke via Highway 427 South</strong> — making us faster to reach than most central Toronto suppliers. We carry 2,000+ SKUs with <strong>same-day walk-in pickup, open 7 days a week</strong>. No minimum order. Trade accounts available.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Sheet Metal Duct Fittings — Etobicoke HVAC Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Etobicoke HVAC contractors working on residential replacements, condo retrofits, and commercial builds rely on BHS for <strong>sheet metal duct fittings</strong> — elbows, tees, wyes, reducers, transitions, and takeoffs in both round and rectangular. Full stock of <strong>flexible duct R4.2, R6, R8</strong>, duct tape, mastic sealant, and duct board. Call ahead by 9am and your order is ready when you arrive — skip the wait.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">PEX Pipe, Brass Fittings & Plumbing for Etobicoke Plumbers</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Etobicoke and west-end Toronto plumbers choose BHS Supplies for <strong>wholesale plumbing supplies</strong>. We stock <strong>PEX-A and PEX-B pipe (all sizes, standard and oxygen-barrier)</strong>, <strong>brass ball valves, elbows, tees, and couplings</strong>, copper fittings, push-fit connectors, <strong>gas valves</strong>, <strong>black iron pipe</strong> in all schedules, <strong>CSST flexible gas piping</strong>, backflow preventers, and water heater parts. All at contractor wholesale pricing.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Gas Supplies for Etobicoke Gas Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                BHS stocks everything for licensed gas contractors operating in Etobicoke and the west end: <strong>gas ball valves (all sizes)</strong>, <strong>black iron pipe (schedule 40, all diameters)</strong>, gas cocks, CSST, unions, drip legs, and appliance connectors. Call ahead for large gas piping orders: <strong>(647) 456-2244</strong>.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Refrigerants & HVAC Accessories</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                In stock for Etobicoke HVAC technicians: <strong>R-410A, R-32, R-454B</strong> at wholesale pricing, <strong>air filters in all sizes</strong>, smart and programmable <strong>thermostats</strong>, exhaust fans, and HRV accessories. Order online at bhssupplies.com or call <strong>(647) 456-2244</strong>.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Getting Here from Etobicoke — ~15 Minutes via Hwy 427</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                From Etobicoke: Take <strong>Highway 427 South</strong> to Derry Rd East, then south on Torbram Rd. From Rexdale or Islington, it's a straight shot south on Highway 27 / Torbram Rd. Our warehouse is at <strong>7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</strong>. Open <strong>Mon–Sat 10am–6pm and Sunday 10am–2pm</strong>. Serving Etobicoke, Rexdale, Islington, Mimico, Long Branch, and all west-end Toronto zones.
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
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4<br><small class="text-gray">(~15 min from Etobicoke via Hwy 427)</small></span>
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
                        <li class="mb-1"><a href="{{ route('locations.brampton') }}" class="text-primary">HVAC Supplies Brampton</a></li>
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
