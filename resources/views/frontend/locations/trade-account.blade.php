@extends('frontend.layouts.app')

@section('meta_title', 'Contractor Trade Account | Wholesale HVAC & Plumbing Pricing | BHS Supplies Mississauga')
@section('meta_description', 'Set up a BHS Supplies trade account for wholesale HVAC and plumbing pricing in Mississauga. Volume pricing, no minimum order, priority stock. For licensed HVAC technicians, plumbers & gas contractors. Call (647) 456-2244.')
@section('meta_keywords', 'contractor trade account Mississauga, wholesale HVAC account GTA, plumbing contractor pricing Mississauga, HVAC wholesale B2B account, trade pricing HVAC plumbing GTA')

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Contractor Trade Account — BHS Supplies",
  "description": "Wholesale HVAC, plumbing, and hardware trade accounts for licensed contractors across Mississauga and the GTA. Volume pricing, no minimum order, priority stock access.",
  "provider": {
    "@type": "HVACBusiness",
    "name": "BHS Supplies",
    "telephone": "+1 (647) 456-2244",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "7040 Torbram Rd #8",
      "addressLocality": "Mississauga",
      "addressRegion": "ON",
      "postalCode": "L4T 3Z4",
      "addressCountry": "CA"
    }
  },
  "areaServed": ["Mississauga", "Brampton", "Toronto", "Vaughan", "Etobicoke", "Oakville", "Scarborough", "Greater Toronto Area"],
  "audience": {
    "@type": "Audience",
    "audienceType": "Licensed HVAC Technicians, Plumbers, Gas Fitters, Contractors"
  }
}
</script>
@endsection

@section('content')
<div class="container mt-4 mb-5">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">Contractor Trade Account</li>
        </ol>
    </nav>

    {{-- Hero banner --}}
    <div class="rounded p-4 p-lg-5 mb-4 text-white" style="background: linear-gradient(135deg, #1a1a2e 0%, #d43533 100%);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="h2 fw-700 mb-2">Contractor Trade Account</h1>
                <p class="fs-16 mb-3 opacity-90">Wholesale HVAC, Plumbing & Hardware Pricing for Licensed GTA Contractors</p>
                <ul class="list-unstyled fs-15 mb-0">
                    <li class="mb-1"><i class="las la-check-circle mr-2"></i> Volume pricing — no minimum order</li>
                    <li class="mb-1"><i class="las la-check-circle mr-2"></i> Priority stock access on 2,000+ SKUs</li>
                    <li class="mb-1"><i class="las la-check-circle mr-2"></i> B2B portal — order online, pick up same-day</li>
                    <li class="mb-1"><i class="las la-check-circle mr-2"></i> Open 7 days — Mississauga warehouse</li>
                </ul>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0 text-lg-right">
                <a href="tel:+16474562244" class="btn btn-light btn-lg fw-700 d-block d-lg-inline-block mb-2">
                    <i class="las la-phone mr-1"></i> (647) 456-2244
                </a>
                <a href="mailto:support@bhssupplies.com" class="btn btn-outline-light btn-lg fw-600 d-block d-lg-inline-block">
                    Email Us
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">

            <h2 class="h4 fw-700 mb-3 text-dark">Wholesale HVAC & Plumbing Pricing for Licensed Contractors</h2>
            <p class="fs-15 text-dark mb-4" style="line-height:1.8;">
                BHS Supplies offers <strong>contractor trade accounts</strong> for licensed HVAC technicians, plumbers, gas fitters, and trade professionals across Mississauga, Brampton, Toronto, Vaughan, Etobicoke, Oakville, and all of the GTA. A BHS trade account gives you <strong>wholesale pricing on all 2,000+ SKUs</strong>, priority access to in-stock inventory, and a streamlined B2B ordering experience — so you spend less time sourcing parts and more time on the job.
            </p>

            {{-- Benefits grid --}}
            <h2 class="h4 fw-600 mb-3 text-dark">Trade Account Benefits</h2>
            <div class="row mb-4">
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-dollar-sign text-primary mr-1"></i> Volume Pricing</h3>
                        <p class="fs-14 text-gray mb-0">Wholesale contractor pricing on HVAC supplies, plumbing parts, hardware, and safety gear — automatically applied at checkout and counter.</p>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-boxes text-primary mr-1"></i> No Minimum Order</h3>
                        <p class="fs-14 text-gray mb-0">Order exactly what you need — one fitting or a full pallet. No minimum order requirement for trade account holders or walk-in customers.</p>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-warehouse text-primary mr-1"></i> Priority Stock Access</h3>
                        <p class="fs-14 text-gray mb-0">Trade account holders get priority on high-demand items like refrigerants, sheet metal fittings, and PEX pipe — especially during peak season.</p>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-laptop text-primary mr-1"></i> B2B Online Portal</h3>
                        <p class="fs-14 text-gray mb-0">Browse live inventory, check trade pricing, place orders, and schedule same-day pickup at bhssupplies.com — available 24/7.</p>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-clock text-primary mr-1"></i> Same-Day Pickup, 7 Days</h3>
                        <p class="fs-14 text-gray mb-0">Order by 10am for same-day pickup. Walk in any day — Mon–Sat 10am–6pm, Sunday 10am–2pm. No waiting days for in-stock items.</p>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-headset text-primary mr-1"></i> Dedicated Account Support</h3>
                        <p class="fs-14 text-gray mb-0">A dedicated counter contact who knows your trade. Call (647) 456-2244 and reference your trade account for faster service.</p>
                    </div>
                </div>
            </div>

            {{-- Who qualifies --}}
            <h2 class="h4 fw-600 mb-3 text-dark">Who Qualifies for a Trade Account?</h2>
            <p class="fs-15 text-dark mb-3" style="line-height:1.8;">BHS Supplies trade accounts are available to:</p>
            <ul class="fs-15 text-dark mb-4" style="line-height:2;">
                <li><strong>Licensed HVAC technicians</strong> — G1, G2, 313A, 313D certificate holders</li>
                <li><strong>Licensed plumbers</strong> — 306A, 306D certificate holders</li>
                <li><strong>Gas fitters</strong> — G1 and G2 licensed contractors</li>
                <li><strong>Electricians</strong> — Licensed electrical contractors</li>
                <li><strong>HVAC and plumbing companies</strong> — Any registered Ontario trade business</li>
                <li><strong>Property management companies</strong> — With regular maintenance supply needs</li>
                <li><strong>Construction general contractors</strong> — Purchasing on behalf of licensed sub-trades</li>
            </ul>

            {{-- What you get access to --}}
            <h2 class="h4 fw-600 mb-3 text-dark">Trade Account Inventory — 2,000+ SKUs at Wholesale Pricing</h2>
            <div class="row mb-4">
                <div class="col-sm-6">
                    <ul class="fs-14 text-dark mb-3" style="line-height:2;">
                        <li>Sheet metal duct fittings — all types</li>
                        <li>Flexible duct — R4.2, R6, R8</li>
                        <li>PEX pipe — A & B, all sizes</li>
                        <li>Brass fittings — full range</li>
                        <li>Copper fittings — all types</li>
                        <li>Push-fit connectors</li>
                        <li>Gas valves — all sizes</li>
                    </ul>
                </div>
                <div class="col-sm-6">
                    <ul class="fs-14 text-dark mb-3" style="line-height:2;">
                        <li>Black iron pipe & CSST</li>
                        <li>Refrigerants — R-410A, R-32, R-454B</li>
                        <li>Air filters — all sizes incl. HEPA</li>
                        <li>Thermostats & smart controls</li>
                        <li>Water heater parts</li>
                        <li>Hardware, fasteners & hangers</li>
                        <li>Safety & PPE equipment</li>
                    </ul>
                </div>
            </div>

            {{-- How to register --}}
            <h2 class="h4 fw-600 mb-3 text-dark">How to Register — 3 Ways</h2>
            <div class="row mb-4">
                <div class="col-sm-4 mb-3">
                    <div class="text-center p-3 border rounded h-100">
                        <div class="fs-30 text-primary mb-2"><i class="las la-phone"></i></div>
                        <h3 class="h6 fw-700 text-dark mb-1">Call</h3>
                        <p class="fs-13 text-gray mb-2">Fastest — set up in one call</p>
                        <a href="tel:+16474562244" class="fw-700 text-dark">(647) 456-2244</a>
                    </div>
                </div>
                <div class="col-sm-4 mb-3">
                    <div class="text-center p-3 border rounded h-100">
                        <div class="fs-30 text-primary mb-2"><i class="las la-envelope"></i></div>
                        <h3 class="h6 fw-700 text-dark mb-1">Email</h3>
                        <p class="fs-13 text-gray mb-2">Send your licence # and business name</p>
                        <a href="mailto:support@bhssupplies.com" class="fw-700 text-dark fs-13">support@bhssupplies.com</a>
                    </div>
                </div>
                <div class="col-sm-4 mb-3">
                    <div class="text-center p-3 border rounded h-100">
                        <div class="fs-30 text-primary mb-2"><i class="las la-store"></i></div>
                        <h3 class="h6 fw-700 text-dark mb-1">Walk In</h3>
                        <p class="fs-13 text-gray mb-2">Bring your licence — set up at counter</p>
                        <span class="fw-700 text-dark fs-13">7040 Torbram Rd #8</span>
                    </div>
                </div>
            </div>

            {{-- Serving --}}
            <h2 class="h4 fw-600 mb-3 text-dark">Serving Contractors Across the GTA</h2>
            <p class="fs-15 text-dark mb-0" style="line-height:1.8;">
                BHS Supplies trade accounts are used by licensed contractors across <strong>Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, Scarborough, North York, Markham, Richmond Hill</strong>, and all of Peel, York, and Halton Regions. Whether you're a solo plumber running a van or an HVAC company with a fleet of trucks, a BHS trade account puts wholesale pricing and same-day pickup in your hands — 7 days a week.
            </p>

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4 mt-4 mt-lg-0">

            {{-- CTA card --}}
            <div class="card border-0 shadow mb-4" style="border-top: 4px solid #d43533 !important;">
                <div class="card-body p-4">
                    <h3 class="h5 fw-700 text-dark mb-3">Set Up Your Trade Account</h3>
                    <p class="fs-14 text-gray mb-3">Call or email with your contractor licence number and business name. Active same day.</p>
                    <a href="tel:+16474562244" class="btn btn-primary btn-block fw-700 mb-2">
                        <i class="las la-phone mr-1"></i> Call (647) 456-2244
                    </a>
                    <a href="mailto:support@bhssupplies.com" class="btn btn-outline-primary btn-block fw-600 mb-2">
                        <i class="las la-envelope mr-1"></i> Email Us
                    </a>
                    <a href="{{ route('search') }}" class="btn btn-outline-dark btn-block fw-600">
                        Browse Products First
                    </a>
                </div>
            </div>

            {{-- Store hours --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2"><i class="las la-store mr-1 text-primary"></i> Pickup Location</h3>
                    <p class="fs-14 text-dark mb-1 fw-600">7040 Torbram Rd #8</p>
                    <p class="fs-13 text-gray mb-3">Mississauga, ON L4T 3Z4</p>
                    <table class="table table-sm table-borderless fs-13 mb-0">
                        <tr><td class="text-gray pl-0">Mon – Sat</td><td class="fw-600 text-dark">10:00 AM – 6:00 PM</td></tr>
                        <tr><td class="text-gray pl-0">Sunday</td><td class="fw-600 text-dark">10:00 AM – 2:00 PM</td></tr>
                    </table>
                </div>
            </div>

            {{-- Cities served --}}
            <div class="card border-0 border shadow-sm mb-4">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Cities We Serve</h3>
                    <ul class="list-unstyled fs-14 mb-0">
                        <li class="mb-1"><a href="{{ route('locations.mississauga') }}" class="text-primary">Mississauga</a></li>
                        <li class="mb-1"><a href="{{ route('locations.brampton') }}" class="text-primary">Brampton</a></li>
                        <li class="mb-1"><a href="{{ route('locations.toronto') }}" class="text-primary">Toronto</a></li>
                        <li class="mb-1"><a href="{{ route('locations.etobicoke') }}" class="text-primary">Etobicoke</a></li>
                        <li class="mb-1"><a href="{{ route('locations.vaughan') }}" class="text-primary">Vaughan</a></li>
                        <li class="mb-1"><a href="{{ route('locations.oakville') }}" class="text-primary">Oakville</a></li>
                        <li><a href="{{ route('locations.scarborough') }}" class="text-primary">Scarborough</a></li>
                    </ul>
                </div>
            </div>

            {{-- Blog links --}}
            <div class="card border-0 border shadow-sm">
                <div class="card-body p-3">
                    <h3 class="h6 fw-700 text-dark mb-2">Contractor Resources</h3>
                    <ul class="list-unstyled fs-13 mb-0">
                        <li class="mb-1"><a href="{{ route('blog.details', 'sheet-metal-duct-fittings-supplier-mississauga') }}" class="text-primary">Sheet Metal Duct Fittings Guide</a></li>
                        <li class="mb-1"><a href="{{ route('blog.details', 'where-to-buy-pex-pipe-wholesale-mississauga') }}" class="text-primary">PEX Pipe Wholesale Guide</a></li>
                        <li class="mb-1"><a href="{{ route('blog.details', 'gas-valve-black-iron-pipe-supplier-mississauga') }}" class="text-primary">Gas Valve & Black Iron Pipe</a></li>
                        <li><a href="{{ route('blog') }}" class="text-primary">All Contractor Articles →</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
