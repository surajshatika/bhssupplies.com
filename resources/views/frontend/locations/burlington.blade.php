@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Burlington | Wholesale Sheet Metal, PEX, Gas Valves | BHS Supplies')
@section('meta_description', 'BHS Supplies serves Burlington HVAC contractors & plumbers with wholesale sheet metal duct fittings, PEX pipe, brass fittings, CSST, gas valves & refrigerants. Same-day pickup ~25 min from Burlington. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies Burlington, sheet metal duct fittings Burlington, PEX pipe wholesale Burlington contractor, gas valve CSST Burlington, HVAC supply store near Burlington, plumbing supplies Burlington Halton wholesale')

@section('canonical'){{ route('locations.burlington') }}@endsection

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "HVAC Supplies Burlington", "item": "{{ route('locations.burlington') }}"}
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "@id": "{{ route('locations.burlington') }}#service",
  "name": "Wholesale HVAC & Plumbing Supplies — Burlington",
  "description": "BHS Supplies provides wholesale HVAC equipment, sheet metal duct fittings, PEX pipe, gas valves, and plumbing supplies for licensed contractors in Burlington, Aldershot, and Halton Region. Approximately 25 minutes via the QEW East.",
  "url": "{{ route('locations.burlington') }}",
  "serviceType": "Wholesale HVAC & Plumbing Supply",
  "areaServed": {"@type": "City", "name": "Burlington", "addressCountry": "CA"},
  "provider": {"@type": "HVACBusiness", "@id": "{{ url('/') }}#localbusiness", "name": "BHS Supplies", "telephone": "+1-647-456-2244"}
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies Burlington</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies for Burlington Contractors — Wholesale, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> serves licensed HVAC contractors, plumbers, and gas fitters across Burlington, Aldershot, Waterdown, and all of Halton Region from our <strong>Mississauga warehouse at 7040 Torbram Rd #8</strong> — approximately <strong>25 minutes from Burlington via the QEW East</strong>. With 2,000+ SKUs in stock and <strong>same-day walk-in pickup available 7 days a week</strong>, BHS is Burlington contractors' fastest-access <strong>wholesale HVAC supply store</strong>. No minimum order. Trade accounts with volume pricing available.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Sheet Metal Duct Fittings for Burlington HVAC Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Burlington HVAC contractors working on new builds, commercial retrofits, and residential replacements rely on BHS for a complete supply of <strong>sheet metal duct fittings</strong>: round and rectangular elbows, tees, wyes, reducers, takeoffs, end caps, and transitions. We also carry <strong>flexible duct in R4.2, R6, and R8 insulation ratings</strong>, duct board, aluminum tape, and mastic sealant. Call ahead for large duct orders: <strong>(647) 456-2244</strong>.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">PEX Pipe, Brass Fittings & Plumbing Supplies — Burlington Wholesale</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Burlington and Halton Region plumbers choose BHS Supplies for competitive <strong>wholesale plumbing supply pricing</strong>. We stock the full range of <strong>PEX-A and PEX-B pipe</strong>, <strong>brass fittings</strong> (ball valves, elbows, tees), copper fittings, push-fit connectors, <strong>gas valves</strong>, <strong>black iron pipe (all schedules)</strong>, <strong>CSST flexible gas piping</strong>, water heater parts, and backflow preventers.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Refrigerants & HVAC Accessories — Burlington Contractor Pricing</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Burlington HVAC technicians rely on BHS for in-stock <strong>R-410A, R-32, and R-454B</strong> at wholesale contractor pricing. We also stock <strong>air filters (all sizes, including HEPA)</strong>, smart and programmable <strong>thermostats</strong>, exhaust fans, HRV accessories, and vacuum pumps. Serving Burlington, Oakville, Milton, and all of Halton Region.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Trade Accounts — Volume Pricing for Burlington Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Set up a <strong>BHS trade account</strong> for volume pricing and priority stock access — ideal for Burlington and Oakville HVAC companies and plumbing contractors. Register at <strong>bhssupplies.com</strong> or call <strong>(647) 456-2244</strong>. Our B2B portal lets you check live inventory and order for same-day pickup before you leave Burlington.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Getting Here from Burlington — ~25 Minutes via QEW</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                From Burlington: Take the <strong>QEW East</strong> toward Mississauga, then north on Highway 427 to Derry Rd and east to Torbram Rd — or continue on the 401 East to Airport Rd. Our warehouse is at <strong>7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</strong>. Open <strong>Mon–Sat 10am–6pm and Sunday 10am–2pm</strong>. Free parking on site. Serving Burlington, Aldershot, Waterdown, Oakville, Milton, and all of Halton Region.
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
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4<br><small class="text-gray">(~25 min from Burlington via QEW East)</small></span>
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
                        <li class="mb-1"><a href="{{ route('locations.oakville') }}" class="text-primary">HVAC Supplies Oakville</a></li>
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
