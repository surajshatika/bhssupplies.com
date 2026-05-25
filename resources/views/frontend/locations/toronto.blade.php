@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Toronto | Wholesale PEX, Brass Fittings, Sheet Metal Duct | BHS Supplies')
@section('meta_description', 'BHS Supplies serves Toronto HVAC contractors & plumbers with wholesale sheet metal duct fittings, PEX pipe, brass fittings, gas valves, refrigerants & more. Same-day pickup in Mississauga. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies Toronto, sheet metal duct fittings Toronto, PEX pipe wholesale Toronto, brass fittings Toronto contractor, gas valve supplier Toronto GTA, refrigerant Toronto, plumbing supplies wholesale Toronto')

@section('canonical'){{ route('locations.toronto') }}@endsection

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "HVAC Supplies Toronto", "item": "{{ route('locations.toronto') }}"}
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "@id": "{{ route('locations.toronto') }}#service",
  "name": "Wholesale HVAC & Plumbing Supplies — Toronto",
  "description": "BHS Supplies provides wholesale HVAC equipment, plumbing supplies, and hardware for licensed contractors in Toronto. Same-day pickup from Mississauga warehouse. Open 7 days.",
  "url": "{{ route('locations.toronto') }}",
  "serviceType": "Wholesale HVAC & Plumbing Supply",
  "areaServed": {
    "@type": "City",
    "name": "Toronto",
    "addressCountry": "CA"
  },
  "provider": {
    "@type": "HVACBusiness",
    "@id": "{{ url('/') }}#localbusiness",
    "name": "BHS Supplies",
    "telephone": "+1-647-456-2244"
  },
  "hoursAvailable": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
    "opens": "10:00",
    "closes": "18:00"
  }
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies Toronto</li>
        </ol>
    </nav>

    <div class="row">
        {{-- Main content --}}
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies for Toronto Contractors — Wholesale, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> is the GTA's preferred <strong>wholesale HVAC and plumbing supplier</strong> for licensed contractors working in Toronto, Etobicoke, Scarborough, North York, and across the city. Stocked at our <strong>Mississauga warehouse at 7040 Torbram Rd #8</strong> — minutes from Highway 427 and the 401 — we carry 2,000+ SKUs of HVAC parts, plumbing supplies, and hardware available for <strong>same-day walk-in pickup, 7 days a week</strong>. Trade accounts available. No minimum order.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Sheet Metal Duct Fittings — In Stock for Toronto Jobs</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Toronto HVAC contractors rely on BHS Supplies for a complete range of <strong>sheet metal duct fittings</strong> — round and rectangular elbows, tees, wyes, reducers, takeoffs, end caps, and transitions. We also carry a full selection of <strong>flexible duct (R4.2, R6, R8)</strong>, duct board, aluminum tape, foil tape, and mastic sealant. Whether you're working on a downtown Toronto condo installation or a residential replacement in Etobicoke, our warehouse is stocked and ready for same-day pickup.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">PEX Pipe, Brass & Copper Fittings for Toronto Plumbers</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Plumbers working across Toronto, Scarborough, and North York count on BHS for <strong>wholesale plumbing supplies</strong>. We stock <strong>PEX pipe (A and B types, all diameters, oxygen barrier and standard)</strong>, brass fittings (elbows, tees, ball valves, couplings), copper fittings, <strong>push-fit connectors</strong>, <strong>gas valves</strong>, <strong>black iron pipe</strong> in all schedules, <strong>CSST flexible gas piping</strong>, backflow preventers, pressure regulators, and <strong>water heater parts</strong>. All at wholesale contractor pricing with no minimum order.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Refrigerants, Thermostats & HVAC Accessories</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Toronto HVAC technicians trust BHS for refrigerant supply. We stock <strong>R-410A, R-32, R-454B</strong>, and other refrigerant lines at <strong>wholesale contractor pricing</strong>. Our HVAC accessories inventory includes <strong>air filters (1", 2", 4" and HEPA)</strong>, programmable and smart <strong>thermostats and controls</strong>, exhaust fans, HRV units, and indoor air quality equipment — all available for same-day pickup at our Mississauga location, easily accessible from Highway 427.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Trade Accounts for Toronto Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                BHS Supplies offers <strong>trade accounts for licensed Toronto-area contractors</strong> — HVAC technicians, plumbers, gas fitters, and electricians. Trade accounts include volume pricing, priority stock access, and no minimum order. Register online at <strong>bhssupplies.com</strong> or call <strong>(647) 456-2244</strong>. Our B2B portal lets you check live inventory, place orders, and schedule same-day pickup before you leave your last job.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Easy Access from Toronto — 7040 Torbram Rd, Mississauga</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                Our warehouse at <strong>7040 Torbram Rd #8, Mississauga</strong> is accessible from Toronto via Highway 427 North to Derry Rd, or via the 401 West to Airport Rd. We are open <strong>Monday–Saturday 10am–6pm and Sunday 10am–2pm</strong>. Call ahead for large or bulk orders: <strong>(647) 456-2244</strong>. Serving Toronto, Etobicoke, Scarborough, North York, York, East York, and all GTA zones.
            </p>

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4 mt-4 mt-lg-0">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-700 fs-15">
                    <i class="las la-store mr-1"></i> Pickup Location — Mississauga
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled fs-14 mb-3">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="las la-map-marker text-primary mr-2 mt-1"></i>
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4<br><small class="text-gray">(Minutes from Hwy 427 & 401)</small></span>
                        </li>
                        <li class="mb-2">
                            <i class="las la-phone text-primary mr-2"></i>
                            <a href="tel:+16474562244" class="text-dark fw-600">(647) 456-2244</a>
                        </li>
                        <li class="mb-2">
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
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2884.283424164344!2d-79.6631853!3d43.7046894!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b3ebbfdf989b5%3A0xc3f8e6c7d1e8c9b!2s7040%20Torbram%20Rd%20%238%2C%20Mississauga%2C%20ON%20L4T%203Z4!5e0!3m2!1sen!2sca!4v1690000000000!5m2!1sen!2sca"
                        width="100%" height="280" style="border:0; border-radius:0 0 4px 4px;"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <div class="card border-0 border shadow-sm">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Also Serving</h3>
                    <ul class="list-unstyled fs-14 mb-0">
                        <li class="mb-1"><a href="{{ route('locations.mississauga') }}" class="text-primary">HVAC Supplies Mississauga</a></li>
                        <li class="mb-1"><a href="{{ route('locations.brampton') }}" class="text-primary">HVAC Supplies Brampton</a></li>
                        <li class="mb-1"><a href="{{ route('locations.etobicoke') }}" class="text-primary">HVAC Supplies Etobicoke</a></li>
                        <li class="mb-1"><a href="{{ route('locations.north-york') }}" class="text-primary">HVAC Supplies North York</a></li>
                        <li class="mb-1"><a href="{{ route('locations.scarborough') }}" class="text-primary">HVAC Supplies Scarborough</a></li>
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
