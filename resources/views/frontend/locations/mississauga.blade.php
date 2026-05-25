@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Mississauga | Wholesale PEX, Brass Fittings, Sheet Metal Duct | BHS Supplies')
@section('meta_description', 'BHS Supplies — wholesale HVAC equipment supplier in Mississauga. PEX pipe, brass fittings, sheet metal duct fittings, gas valves, refrigerants & more. Open 7 days. Same-day pickup at 7040 Torbram Rd #8. Call (647) 456-2244.')
@section('meta_keywords', 'HVAC supplies Mississauga, sheet metal duct fittings Mississauga, PEX pipe wholesale Mississauga, brass fittings supplier Mississauga, gas valve Mississauga, refrigerant supplier Mississauga, HVAC contractor supply Mississauga, plumbing supplies Mississauga wholesale')

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HVACBusiness",
  "name": "BHS Supplies",
  "@id": "{{ url('/hvac-supplies-mississauga') }}#localbusiness-mississauga",
  "url": "{{ url('/hvac-supplies-mississauga') }}",
  "telephone": "+1 (647) 456-2244",
  "email": "support@bhssupplies.com",
  "priceRange": "$$",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "7040 Torbram Rd #8",
    "addressLocality": "Mississauga",
    "addressRegion": "ON",
    "postalCode": "L4T 3Z4",
    "addressCountry": "CA"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 43.7046894,
    "longitude": -79.6631853
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
      "opens": "10:00",
      "closes": "18:00"
    },
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": "Sunday",
      "opens": "10:00",
      "closes": "14:00"
    }
  ],
  "areaServed": ["Mississauga", "Brampton", "Toronto", "Etobicoke", "Vaughan", "Oakville", "Greater Toronto Area"]
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">HVAC Supplies Mississauga</li>
        </ol>
    </nav>

    <div class="row">
        {{-- Main content --}}
        <div class="col-lg-8">

            <h1 class="h2 fw-700 text-dark mb-3">HVAC Supplies in Mississauga — Wholesale, Same-Day Pickup</h1>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                <strong>BHS Supplies</strong> is Mississauga's leading <strong>wholesale HVAC equipment supplier</strong> for licensed contractors, plumbers, and tradespeople across the GTA. Located at <strong>7040 Torbram Rd #8</strong>, we stock 2,000+ SKUs of HVAC parts, plumbing supplies, and hardware — all available for <strong>same-day walk-in pickup, 7 days a week</strong>. No minimum order. Wholesale pricing. Trade accounts available.
            </p>

            {{-- Products grid --}}
            <h2 class="h4 fw-600 mb-3 text-dark">What We Stock — HVAC Supplies Mississauga</h2>
            <div class="row mb-4">
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-wind text-primary mr-1"></i> Sheet Metal & Ductwork</h3>
                        <ul class="list-unstyled fs-14 text-gray mb-0">
                            <li class="mb-1">• Round & rectangular duct fittings</li>
                            <li class="mb-1">• Elbows, tees, reducers, takeoffs</li>
                            <li class="mb-1">• Flexible duct — R4.2, R6, R8</li>
                            <li class="mb-1">• Duct board, HVAC tape, mastic sealant</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-tint text-primary mr-1"></i> Plumbing Supplies</h3>
                        <ul class="list-unstyled fs-14 text-gray mb-0">
                            <li class="mb-1">• PEX pipe — A & B, all sizes</li>
                            <li class="mb-1">• Brass & copper fittings</li>
                            <li class="mb-1">• Push-fit connectors (SharkBite compatible)</li>
                            <li class="mb-1">• Backflow preventers, pressure regulators</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-fire text-primary mr-1"></i> Gas Supplies</h3>
                        <ul class="list-unstyled fs-14 text-gray mb-0">
                            <li class="mb-1">• Gas valves & ball valves</li>
                            <li class="mb-1">• Black iron pipe — all schedules</li>
                            <li class="mb-1">• CSST flexible gas piping</li>
                            <li class="mb-1">• Gas cocks, unions, fittings</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-snowflake text-primary mr-1"></i> HVAC Accessories</h3>
                        <ul class="list-unstyled fs-14 text-gray mb-0">
                            <li class="mb-1">• Refrigerants — R-410A, R-32, R-454B</li>
                            <li class="mb-1">• Air filters — 1", 2", 4", HEPA</li>
                            <li class="mb-1">• Thermostats & smart controls</li>
                            <li class="mb-1">• Exhaust fans, HRV accessories</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-tools text-primary mr-1"></i> Hardware & Fasteners</h3>
                        <ul class="list-unstyled fs-14 text-gray mb-0">
                            <li class="mb-1">• Screws, bolts, nuts — bulk packs</li>
                            <li class="mb-1">• Pipe hangers, brackets, clamps</li>
                            <li class="mb-1">• Drill bits, hole saws, step bits</li>
                            <li class="mb-1">• HVAC strapping & supports</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-hard-hat text-primary mr-1"></i> Safety & PPE</h3>
                        <ul class="list-unstyled fs-14 text-gray mb-0">
                            <li class="mb-1">• Safety gloves & work gloves</li>
                            <li class="mb-1">• Respirators & dust masks</li>
                            <li class="mb-1">• Safety goggles & glasses</li>
                            <li class="mb-1">• High-visibility vests</li>
                        </ul>
                    </div>
                </div>
            </div>

            <h2 class="h4 fw-600 mt-2 mb-3 text-dark">Wholesale Contractor Pricing — No Minimum Order</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                BHS Supplies offers <strong>wholesale pricing on HVAC supplies in Mississauga</strong> with no minimum order for walk-in customers. Licensed HVAC technicians, plumbers, and contractors can set up a <strong>trade account</strong> for volume pricing and priority stock access. Our <strong>B2B portal at bhssupplies.com</strong> lets you browse live inventory, place orders, and schedule same-day pickup — saving time on every job. Set up your trade account by calling <strong>(647) 456-2244</strong> or emailing <strong>support@bhssupplies.com</strong>.
            </p>

            <h2 class="h4 fw-600 mt-2 mb-3 text-dark">Sheet Metal Duct Fittings Supplier — Mississauga's #1 Choice</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                When HVAC contractors search for <strong>sheet metal duct fittings in Mississauga</strong>, BHS Supplies is the answer. We stock the complete range — round and rectangular, snap-lock and TDC, elbows in all degrees, tees, wyes, end caps, transitions, and more. Our flexible duct selection covers <strong>R4.2, R6, and R8 insulation ratings</strong> in all standard diameters. We also carry <strong>duct insulation wrap, aluminum tape, foil tape, and mastic sealant</strong>. Everything a sheet metal contractor needs is available for same-day pickup in Mississauga.
            </p>

            <h2 class="h4 fw-600 mt-2 mb-3 text-dark">Serving HVAC Contractors Across the GTA</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                From our Mississauga warehouse, we serve licensed contractors across <strong>Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, Scarborough, North York</strong>, and all of the Greater Toronto Area. Whether you need parts for a residential furnace install, a commercial HVAC retrofit, a gas line rough-in, or a plumbing renovation, BHS Supplies has the stock and the pricing to keep your crew moving. Walk in any day of the week or order at <strong>bhssupplies.com</strong>.
            </p>

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4 mt-4 mt-lg-0">

            {{-- Store info card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-700 fs-15">
                    <i class="las la-store mr-1"></i> Visit Our Mississauga Store
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled fs-14 mb-3">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="las la-map-marker text-primary mr-2 mt-1"></i>
                            <span><strong>7040 Torbram Rd #8</strong><br>Mississauga, ON L4T 3Z4</span>
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

            {{-- Map --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2884.283424164344!2d-79.6631853!3d43.7046894!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b3ebbfdf989b5%3A0xc3f8e6c7d1e8c9b!2s7040%20Torbram%20Rd%20%238%2C%20Mississauga%2C%20ON%20L4T%203Z4!5e0!3m2!1sen!2sca!4v1690000000000!5m2!1sen!2sca"
                        width="100%" height="300" style="border:0; border-radius:0 0 4px 4px;"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            {{-- Trade account CTA --}}
            <div class="card border-0 bg-light mb-4">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Set Up a Trade Account</h3>
                    <p class="fs-13 text-gray mb-3">Licensed contractors get volume pricing, priority stock access, and no minimum order. Call or email to register.</p>
                    <a href="tel:+16474562244" class="btn btn-sm btn-dark fw-600 mr-2">Call (647) 456-2244</a>
                </div>
            </div>

            {{-- Related location links --}}
            <div class="card border-0 border shadow-sm">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Also Serving</h3>
                    <ul class="list-unstyled fs-14 mb-0">
                        <li class="mb-1"><a href="{{ route('locations.brampton') }}" class="text-primary">HVAC Supplies Brampton</a></li>
                        <li class="mb-1"><a href="{{ route('locations.toronto') }}" class="text-primary">HVAC Supplies Toronto</a></li>
                        <li class="mb-1"><a href="{{ route('locations.etobicoke') }}" class="text-primary">HVAC Supplies Etobicoke</a></li>
                        <li class="mb-1"><a href="{{ route('locations.vaughan') }}" class="text-primary">HVAC Supplies Vaughan</a></li>
                        <li class="mb-1"><a href="{{ route('locations.oakville') }}" class="text-primary">HVAC Supplies Oakville</a></li>
                        <li class="mb-1"><a href="{{ route('locations.scarborough') }}" class="text-primary">HVAC Supplies Scarborough</a></li>
                        <li class="mb-1"><a href="{{ route('locations.markham') }}" class="text-primary">HVAC Supplies Markham</a></li>
                        <li class="mb-1"><a href="{{ route('locations.north-york') }}" class="text-primary">HVAC Supplies North York</a></li>
                        <li class="mb-1"><a href="{{ route('locations.burlington') }}" class="text-primary">HVAC Supplies Burlington</a></li>
                        <li><a href="{{ route('home') }}" class="text-primary">All Products — BHS Supplies</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
