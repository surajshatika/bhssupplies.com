@extends('frontend.layouts.app')

@section('meta_title', 'Leave a Google Review — BHS Supplies Mississauga')
@section('meta_description', 'Share your experience with BHS Supplies in Mississauga. Leave a Google review and help other contractors find the best wholesale HVAC and plumbing supplier in the GTA.')
@section('meta_robots', 'noindex, follow')

@section('content')
<div class="container mt-4 mb-5" style="max-width:860px;">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0 fs-13">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active">Leave a Review</li>
        </ol>
    </nav>

    {{-- Hero --}}
    <div class="text-center mb-5">
        <div class="mb-3" style="font-size:52px; line-height:1;">⭐⭐⭐⭐⭐</div>
        <h1 class="h2 fw-700 text-dark mb-2">Share Your Experience</h1>
        <p class="fs-16 text-gray mb-0">
            Your Google review helps other GTA contractors find BHS Supplies.<br>
            Takes <strong>60 seconds</strong> — and it means everything to our small team.
        </p>
    </div>

    {{-- Step 1 — Google Review --}}
    <div class="card border-0 shadow mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-700 fs-18 mr-3 flex-shrink-0"
                     style="width:44px;height:44px;background:#4285F4;">G</div>
                <div>
                    <h2 class="h5 fw-700 text-dark mb-0">Step 1 — Leave a Google Review</h2>
                    <p class="fs-13 text-gray mb-0">Opens Google Maps review form directly</p>
                </div>
            </div>

            <p class="fs-15 text-dark mb-3" style="line-height:1.8;">
                Click the button below to open our Google review form. You'll need a Google account (Gmail works). Rate us, write 1–3 sentences about your experience, and hit Post — that's it.
            </p>

            {{--
                IMPORTANT FOR ADMIN:
                Replace the href below with the actual BHS Supplies Google review shortlink.
                To get it: Google Business Profile dashboard → "Get more reviews" → copy the link.
                Format: https://g.page/r/XXXXXXXXXXXXXXXXXX/review
            --}}
            <a href="https://g.page/r/YOUR_GOOGLE_REVIEW_LINK_HERE/review"
               target="_blank" rel="noopener"
               class="btn btn-lg fw-700 d-block d-sm-inline-block mb-3"
               style="background:#4285F4; color:#fff; border:none; padding:14px 32px;">
                <i class="lab la-google mr-2"></i> Write a Google Review
            </a>

            <div class="alert alert-light border mb-0 fs-14" style="background:#f8f9ff;">
                <strong>What to mention (if you need ideas):</strong>
                <ul class="mb-0 mt-1" style="line-height:2;">
                    <li>Which products you bought (PEX, sheet metal, gas valves, etc.)</li>
                    <li>How fast you got your parts (same-day pickup)</li>
                    <li>How the staff helped you find the right fitting or part</li>
                    <li>How the pricing compared to other suppliers</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Step 2 — QR code for counter / invoices --}}
    <div class="card border-0 shadow mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-700 fs-18 mr-3 flex-shrink-0"
                     style="width:44px;height:44px;background:#34A853;">2</div>
                <div>
                    <h2 class="h5 fw-700 text-dark mb-0">QR Code — Print for Counter & Invoices</h2>
                    <p class="fs-13 text-gray mb-0">Customers scan to review — no typing required</p>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-sm-auto text-center mb-3 mb-sm-0">
                    {{--
                        This QR code points to /review (this page) until the Google link is set.
                        Once the Google shortlink is set above, update the QR code data parameter to that URL.
                        Google Charts QR API: https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl=URL
                    --}}
                    <img src="https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl={{ urlencode(route('review')) }}&choe=UTF-8"
                         alt="QR code — Leave a review for BHS Supplies"
                         width="180" height="180"
                         class="border rounded p-2">
                    <p class="fs-12 text-gray mt-2 mb-0">Scan to review BHS Supplies</p>
                </div>
                <div class="col-sm">
                    <h3 class="h6 fw-700 text-dark mb-2">How to use this QR code</h3>
                    <ol class="fs-14 text-dark mb-3 pl-4" style="line-height:2;">
                        <li>Download/screenshot the QR code</li>
                        <li>Print on A5 card stock and place at the counter</li>
                        <li>Add to the bottom of printed invoices</li>
                        <li>Add to your WhatsApp status or broadcast</li>
                    </ol>
                    <p class="fs-13 text-gray mb-0">
                        <strong>Tip:</strong> Print the card with the text:<br>
                        <em>"Scan to leave us a Google review — takes 60 seconds!"</em>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3 — WhatsApp broadcast --}}
    <div class="card border-0 shadow mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-700 fs-18 mr-3 flex-shrink-0"
                     style="width:44px;height:44px;background:#25D366;">3</div>
                <div>
                    <h2 class="h5 fw-700 text-dark mb-0">WhatsApp Broadcast — Send to Past Customers</h2>
                    <p class="fs-13 text-gray mb-0">Copy-paste ready message for your broadcast list</p>
                </div>
            </div>

            <p class="fs-14 text-gray mb-3">Use WhatsApp Business → Broadcast Lists → select your customer contacts → send this message. Do not send to a group (looks spammy). Broadcast = private, professional.</p>

            <div class="border rounded p-3 bg-light mb-3" style="font-family: monospace; font-size:14px; line-height:1.8; white-space: pre-wrap;">Hi [Name],

We appreciate your business at BHS Supplies!

If we've helped you find the right part — PEX pipe, sheet metal fittings, gas valves, or anything else — it would mean a lot if you could drop us a quick Google review. Takes 60 seconds:

👉 {{ route('review') }}

If there's ever anything we can do better, just reply here first and we'll make it right.

Thank you!
— BHS Supplies | 7040 Torbram Rd #8, Mississauga
📞 (647) 456-2244</div>

            <div class="row" style="gap:0;">
                <div class="col-sm-6 mb-2 pr-sm-2">
                    <div class="border rounded p-3 h-100">
                        <p class="fs-13 fw-700 text-dark mb-1">When to send</p>
                        <ul class="fs-13 text-gray mb-0 pl-4" style="line-height:1.8;">
                            <li>Right after a customer's first purchase</li>
                            <li>After a contractor sets up a trade account</li>
                            <li>During slow periods (mid-week morning)</li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-6 mb-2">
                    <div class="border rounded p-3 h-100">
                        <p class="fs-13 fw-700 text-dark mb-1">Review targets</p>
                        <table class="table table-sm table-borderless fs-13 mb-0">
                            <tr><td class="text-gray pl-0">Week 1</td><td class="fw-600">5 reviews</td></tr>
                            <tr><td class="text-gray pl-0">Week 2</td><td class="fw-600">13 reviews</td></tr>
                            <tr><td class="text-gray pl-0">Month 1</td><td class="fw-600">23+ reviews</td></tr>
                            <tr><td class="text-gray pl-0">Month 3</td><td class="fw-600">40+ reviews</td></tr>
                            <tr><td class="text-gray pl-0">Month 6</td><td class="fw-600">88+ reviews</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 4 — Review response templates --}}
    <div class="card border-0 shadow mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-700 fs-18 mr-3 flex-shrink-0"
                     style="width:44px;height:44px;background:#EA4335;">4</div>
                <div>
                    <h2 class="h5 fw-700 text-dark mb-0">Review Response Templates</h2>
                    <p class="fs-13 text-gray mb-0">Reply to every review — keywords embedded naturally</p>
                </div>
            </div>

            <p class="fs-14 text-gray mb-3">Reply to <strong>every</strong> Google review — positive and negative. Google ranks businesses higher when owners are responsive. Use these keyword-rich templates and personalise with the customer's name.</p>

            <div class="mb-3">
                <p class="fs-13 fw-700 text-dark mb-1">5-star response template:</p>
                <div class="border rounded p-3 bg-light fs-13" style="line-height:1.8;">
                    Thank you [Name]! We're glad we could help you find [product — e.g., sheet metal duct fittings / PEX pipe / gas valves] for your job in [city]. We carry a full range of HVAC supplies and plumbing parts at wholesale pricing — same-day pickup available at our Mississauga location. See you next time!
                </div>
            </div>

            <div class="mb-3">
                <p class="fs-13 fw-700 text-dark mb-1">4-star / neutral response template:</p>
                <div class="border rounded p-3 bg-light fs-13" style="line-height:1.8;">
                    Thanks for the feedback, [Name]! We appreciate your business and are always working to improve. If there's anything specific we can do better, please call us at (647) 456-2244 or stop by our Mississauga location — Mon–Sat 10am–6pm, Sunday 10am–2pm.
                </div>
            </div>

            <div class="mb-0">
                <p class="fs-13 fw-700 text-dark mb-1">Negative review response template:</p>
                <div class="border rounded p-3 bg-light fs-13" style="line-height:1.8;">
                    Hi [Name], we're sorry to hear your experience didn't meet expectations. We take all feedback seriously. Please contact us directly at (647) 456-2244 or support@bhssupplies.com so we can make this right. We'd appreciate the chance to resolve this for you. — BHS Supplies Team
                </div>
            </div>
        </div>
    </div>

    {{-- Google Posts reminder --}}
    <div class="card border-0 shadow mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-700 text-dark mb-3"><i class="las la-calendar-alt text-primary mr-1"></i> Weekly Google Posts Calendar</h2>
            <p class="fs-14 text-gray mb-3">Post every Monday. Use the GBP dashboard → "Add update". Photos required — always include a product photo.</p>
            <div class="table-responsive">
                <table class="table table-bordered fs-13 mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Week</th>
                            <th>Post Title</th>
                            <th>Primary Keyword</th>
                            <th>CTA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-600">Week 1</td>
                            <td>Sheet metal duct fittings in stock — same-day pickup Mississauga</td>
                            <td class="text-gray">sheet metal duct fittings Mississauga</td>
                            <td>Order online</td>
                        </tr>
                        <tr>
                            <td class="fw-600">Week 2</td>
                            <td>PEX pipe, brass &amp; copper fittings — wholesale prices for GTA contractors</td>
                            <td class="text-gray">PEX pipe brass fittings wholesale GTA</td>
                            <td>Call (647) 456-2244</td>
                        </tr>
                        <tr>
                            <td class="fw-600">Week 3</td>
                            <td>Gas valves, black iron pipe &amp; CSST — fully stocked at BHS Mississauga</td>
                            <td class="text-gray">gas valve black iron pipe Mississauga</td>
                            <td>Order online</td>
                        </tr>
                        <tr>
                            <td class="fw-600">Week 4</td>
                            <td>Summer HVAC supplies — refrigerant, air filters &amp; thermostats available now</td>
                            <td class="text-gray">HVAC supplies Mississauga refrigerant</td>
                            <td>Shop now</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Back link --}}
    <div class="text-center">
        <a href="{{ route('home') }}" class="btn btn-outline-primary fw-600">← Back to BHS Supplies</a>
    </div>

</div>
@endsection
