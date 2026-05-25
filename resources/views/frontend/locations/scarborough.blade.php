@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Scarborough | Wholesale Sheet Metal, PEX, Gas Valves | BHS Supplies')
@section('meta_description', 'BHS Supplies serves Scarborough HVAC contractors & plumbers with wholesale sheet metal duct fittings, PEX pipe, brass fittings, CSST, gas valves & refrigerants. Same-day pickup. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies Scarborough, sheet metal duct fittings Scarborough, PEX pipe wholesale Scarborough, gas valve contractor Scarborough, HVAC supply store near Scarborough, plumbing supplies Scarborough GTA wholesale')

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "HVAC Supplies Scarborough", "item": "{{ route('locations.scarborough') }}"}
  ]
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies Scarborough</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies for Scarborough Contractors — Wholesale, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> serves licensed HVAC contractors, plumbers, and gas fitters working in Scarborough, North York, Pickering, and east-end Toronto from our <strong>Mississauga warehouse at 7040 Torbram Rd #8</strong>. Accessible via Highway 401 West, our warehouse offers 2,000+ SKUs for <strong>same-day walk-in pickup, 7 days a week</strong>. Wholesale pricing, no minimum order, trade accounts available.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Sheet Metal Duct Fittings for Scarborough HVAC Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Scarborough and east-end Toronto HVAC contractors working on residential systems, commercial retrofits, and high-rise condo installs rely on BHS Supplies for a complete stock of <strong>sheet metal duct fittings</strong> — round and rectangular elbows, tees, wyes, reducers, takeoffs, end caps, and transitions. We also carry <strong>flexible duct in R4.2, R6, and R8</strong>, duct tape, mastic sealant, and duct board — all for same-day pickup.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">PEX Pipe, Brass & Copper Fittings — Scarborough Plumber Wholesale</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Scarborough plumbers and gas contractors choose BHS for <strong>wholesale plumbing supply pricing</strong>. We stock <strong>PEX-A and PEX-B pipe (all sizes)</strong>, <strong>brass ball valves, elbows, tees, and couplings</strong>, copper fittings, push-fit connectors, <strong>gas valves</strong>, <strong>black iron pipe (all schedules)</strong>, <strong>CSST flexible gas piping</strong>, backflow preventers, water heater parts, and more.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Refrigerants & HVAC Accessories for Scarborough Technicians</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                In stock for Scarborough HVAC technicians: <strong>R-410A, R-32, R-454B</strong> at wholesale pricing, <strong>air filters in all sizes</strong>, programmable and smart <strong>thermostats</strong>, exhaust fans, HRV accessories, and all HVAC service tools including vacuum pumps, manifold gauges, and recovery machines.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Trade Accounts for Scarborough & East Toronto Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Set up a <strong>BHS trade account</strong> for volume pricing and priority stock access — ideal for Scarborough-based HVAC companies and plumbing contractors with regular needs. Register at <strong>bhssupplies.com</strong> or call <strong>(647) 456-2244</strong>.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Getting Here from Scarborough — Via Highway 401 West</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                From Scarborough: Take <strong>Highway 401 West</strong> to Airport Rd / Torbram Rd — our warehouse is at <strong>7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</strong>. Open <strong>Mon–Sat 10am–6pm and Sunday 10am–2pm</strong>. Free parking on site. Serving Scarborough, Agincourt, Malvern, Wexford, North York, Pickering, and all east-end Toronto zones.
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
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4<br><small class="text-gray">(Via Hwy 401 West from Scarborough)</small></span>
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
        </div>
    </div>
</div>
@endsection
