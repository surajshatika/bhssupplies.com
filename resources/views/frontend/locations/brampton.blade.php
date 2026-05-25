@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Brampton | Wholesale PEX, Brass Fittings, Gas Valves | BHS Supplies')
@section('meta_description', 'BHS Supplies serves Brampton HVAC contractors & plumbers with wholesale sheet metal duct fittings, PEX pipe, brass fittings, CSST, gas valves & refrigerants. Same-day pickup. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies Brampton, sheet metal duct fittings Brampton, PEX pipe wholesale Brampton, brass fittings Brampton contractor, gas valve CSST Brampton, plumbing supplies wholesale Brampton GTA, HVAC contractor supply Brampton')

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "HVAC Supplies Brampton", "item": "{{ route('locations.brampton') }}"}
  ]
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies Brampton</li>
        </ol>
    </nav>

    <div class="row">
        {{-- Main content --}}
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies for Brampton Contractors — Wholesale Pricing, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> is the closest <strong>wholesale HVAC and plumbing supplier</strong> to Brampton contractors. Our warehouse at <strong>7040 Torbram Rd #8, Mississauga</strong> is minutes from Brampton via Highway 410 or Torbram Rd — making us the most convenient <strong>HVAC supply store near Brampton</strong> with 2,000+ SKUs in stock. Same-day walk-in pickup. Open <strong>7 days a week</strong>. Trade accounts with volume pricing available.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Sheet Metal Duct Fittings — Serving Brampton HVAC Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Brampton HVAC contractors rely on BHS Supplies for a complete stock of <strong>sheet metal duct fittings</strong> — round and rectangular elbows, tees, wyes, reducers, takeoffs, end caps, and transitions. We also carry <strong>flexible duct in R4.2, R6, and R8 insulation ratings</strong>, along with duct board, aluminum tape, foil tape, and mastic sealant. Whether you're installing a new residential furnace system in Brampton or completing a large commercial HVAC retrofit, BHS has everything you need for same-day pickup.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">PEX Pipe, Brass Fittings & Plumbing Supplies for Brampton Plumbers</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Brampton plumbers and gas fitters have trusted BHS for <strong>wholesale plumbing supplies</strong>. Our plumbing inventory includes <strong>PEX pipe (A and B types, all sizes, standard and oxygen-barrier)</strong>, <strong>brass fittings</strong> (ball valves, elbows, tees, couplings), <strong>copper fittings</strong>, push-fit connectors, <strong>gas valves</strong>, <strong>black iron pipe</strong> in all schedules, <strong>CSST flexible gas piping</strong>, backflow preventers, pressure reducing valves, and water heater parts. All at <strong>wholesale contractor pricing</strong> with no minimum order.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Gas Supplies for Brampton Gas Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                BHS Supplies is fully stocked for licensed gas contractors operating in Brampton and Peel Region. We carry <strong>CSST flexible gas lines</strong>, <strong>black iron pipe (schedule 40 and 80)</strong>, gas ball valves, drip legs, unions, and all associated fittings — plus <strong>gas cocks, appliance connectors</strong>, and <strong>pressure test gauges</strong>. Call ahead to confirm stock on large gas piping orders: <strong>(647) 456-2244</strong>.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Refrigerants & HVAC Accessories — Wholesale Brampton Prices</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                We stock <strong>R-410A, R-32, R-454B</strong>, and other refrigerant lines at <strong>wholesale contractor pricing</strong>. Brampton HVAC technicians also rely on BHS for <strong>air filters (all sizes, 1" to 4", including HEPA)</strong>, smart and programmable <strong>thermostats</strong>, exhaust fans, HRV accessories, and indoor air quality products. Need a large refrigerant order? Call ahead to (647) 456-2244 for availability.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Trade Accounts — Volume Pricing for Brampton Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                Set up a <strong>BHS trade account</strong> for exclusive volume pricing, no minimum order, and priority access to our full inventory. Ideal for Brampton-based HVAC companies, plumbing contractors, and gas fitters who need consistent supply. Register at <strong>bhssupplies.com</strong> or call <strong>(647) 456-2244</strong>. Our B2B portal lets you check live stock and schedule pickup before you leave your job site.
            </p>

            <h2 class="h4 fw-600 mb-3 text-dark">Quick Access from Brampton — Just Minutes via Hwy 410</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                From Brampton, take Highway 410 South to Derry Rd East, then south on Torbram Rd — our warehouse is at <strong>7040 Torbram Rd #8, Mississauga</strong>, less than 10 minutes from central Brampton. Open <strong>Monday–Saturday 10am–6pm and Sunday 10am–2pm</strong>. Serving all Brampton neighborhoods: Bramalea, Mount Pleasant, Castlemore, Heart Lake, and beyond. <strong>Call: (647) 456-2244 | Shop: bhssupplies.com</strong>
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
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4<br><small class="text-gray">(~10 min from Brampton via Hwy 410)</small></span>
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
                        <li class="mb-1"><a href="{{ route('locations.toronto') }}" class="text-primary">HVAC Supplies Toronto</a></li>
                        <li class="mb-1"><a href="{{ route('locations.vaughan') }}" class="text-primary">HVAC Supplies Vaughan</a></li>
                        <li class="mb-1"><a href="{{ route('locations.oakville') }}" class="text-primary">HVAC Supplies Oakville</a></li>
                        <li class="mb-1"><a href="{{ route('locations.burlington') }}" class="text-primary">HVAC Supplies Burlington</a></li>
                        <li><a href="{{ route('home') }}" class="text-primary">All Products — BHS Supplies</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
