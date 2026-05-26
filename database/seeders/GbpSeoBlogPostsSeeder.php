<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GbpSeoBlogPostsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the "HVAC & Plumbing Tips" blog category exists
        $category = BlogCategory::firstOrCreate(
            ['slug' => 'hvac-plumbing-tips'],
            ['category_name' => 'HVAC & Plumbing Tips', 'slug' => 'hvac-plumbing-tips']
        );

        $posts = [
            [
                'title'             => 'Where to Buy PEX Pipe Wholesale in Mississauga',
                'slug'              => 'where-to-buy-pex-pipe-wholesale-mississauga',
                'meta_title'        => 'Where to Buy PEX Pipe Wholesale in Mississauga | BHS Supplies',
                'meta_description'  => 'Looking for wholesale PEX pipe in Mississauga? BHS Supplies stocks PEX-A and PEX-B pipe in all sizes with same-day walk-in pickup. Contractor pricing, no minimums. Call (647) 456-2244.',
                'meta_keywords'     => 'PEX pipe wholesale Mississauga, buy PEX pipe contractor Mississauga, PEX-A PEX-B pipe supplier GTA, plumbing supply Mississauga',
                'short_description' => 'BHS Supplies is Mississauga\'s go-to wholesale PEX pipe supplier. PEX-A and PEX-B in all sizes, oxygen-barrier and standard, available for same-day pickup. No minimum order.',
                'description'       => '<h2>Wholesale PEX Pipe Supplier in Mississauga — BHS Supplies</h2>
<p>Licensed plumbers and contractors across Mississauga, Brampton, and the GTA rely on <strong>BHS Supplies at 7040 Torbram Rd #8, Mississauga</strong> for wholesale PEX pipe pricing with same-day availability. If you\'ve been searching for where to buy PEX pipe wholesale near Mississauga, look no further.</p>

<h2>What PEX Pipe Do We Stock?</h2>
<p>Our plumbing inventory includes a full range of <strong>PEX pipe options</strong> to meet every residential and commercial job requirement:</p>
<ul>
<li><strong>PEX-A pipe</strong> (expansion type) — all standard diameters from 3/8" to 1-1/2"</li>
<li><strong>PEX-B pipe</strong> (crimp/clamp type) — all standard diameters</li>
<li><strong>Oxygen-barrier PEX</strong> — for radiant heat and hydronic heating systems</li>
<li><strong>Potable water PEX</strong> — NSF-certified for drinking water applications</li>
</ul>
<p>All PEX pipe is sold by the foot or in coil lengths. No minimum order for walk-in customers.</p>

<h2>PEX Fittings & Accessories — Also in Stock</h2>
<p>Along with PEX pipe, BHS Supplies stocks compatible <strong>brass fittings</strong> (elbows, tees, couplings, reducers), <strong>push-fit connectors</strong> (SharkBite compatible), <strong>PEX crimp rings, clamp rings</strong>, and <strong>manifolds</strong> for multi-zone radiant systems. Everything for your PEX rough-in, in one location.</p>

<h2>Wholesale Pricing for Licensed Contractors</h2>
<p>BHS Supplies offers <strong>wholesale contractor pricing on PEX pipe in Mississauga</strong> with no minimum order for walk-in customers. Licensed plumbers can also set up a <strong>trade account</strong> for volume pricing and priority stock access. Call <strong>(647) 456-2244</strong> or visit us at 7040 Torbram Rd #8 to set up your account.</p>

<h2>Same-Day Pickup — Open 7 Days a Week</h2>
<p>Our Mississauga warehouse is open <strong>Monday–Saturday 10am–6pm</strong> and <strong>Sunday 10am–2pm</strong>. You can walk in, call ahead, or order at <strong>bhssupplies.com</strong> for same-day pickup. Serving plumbers across Mississauga, Brampton, Toronto, Etobicoke, Vaughan, and all of the GTA.</p>
<p><strong>Address:</strong> 7040 Torbram Rd #8, Mississauga, ON L4T 3Z4 | <strong>Phone:</strong> (647) 456-2244</p>',
            ],
            [
                'title'             => 'Sheet Metal Duct Fittings Supplier in Mississauga — In Stock, Same-Day Pickup',
                'slug'              => 'sheet-metal-duct-fittings-supplier-mississauga',
                'meta_title'        => 'Sheet Metal Duct Fittings Mississauga | In Stock, Same-Day | BHS Supplies',
                'meta_description'  => 'BHS Supplies stocks a complete range of sheet metal duct fittings in Mississauga — elbows, tees, reducers, flexible duct. Same-day pickup. Wholesale contractor pricing. Call (647) 456-2244.',
                'meta_keywords'     => 'sheet metal duct fittings Mississauga, sheet metal duct supplier GTA, flexible duct Mississauga, HVAC duct fittings contractor Mississauga',
                'short_description' => 'BHS Supplies is Mississauga\'s complete sheet metal duct fittings supplier — round, rectangular, flexible duct, and all accessories. Same-day pickup, wholesale pricing.',
                'description'       => '<h2>Sheet Metal Duct Fittings — Fully Stocked in Mississauga</h2>
<p>When HVAC contractors in Mississauga, Brampton, and the GTA need <strong>sheet metal duct fittings fast</strong>, they go to <strong>BHS Supplies at 7040 Torbram Rd #8</strong>. We stock the complete range of round and rectangular sheet metal duct fittings for residential and commercial HVAC installations — all available for same-day walk-in pickup.</p>

<h2>What Sheet Metal Duct Fittings Do We Carry?</h2>
<p>Our sheet metal inventory includes:</p>
<ul>
<li><strong>Round duct fittings</strong> — elbows (22.5°, 45°, 90°), tees, wyes, reducers, end caps, offsets</li>
<li><strong>Rectangular duct fittings</strong> — elbows, transitions, takeoffs, end caps</li>
<li><strong>Flexible duct</strong> — R4.2, R6, and R8 insulation ratings in all standard diameters (4" to 20")</li>
<li><strong>Duct board</strong> — rigid fiberglass duct board panels</li>
<li><strong>HVAC tape</strong> — aluminum foil tape, silver UL-listed duct tape</li>
<li><strong>Mastic sealant</strong> — water-based, brush-on duct mastic</li>
<li><strong>Duct insulation wrap</strong> — for field-insulating metal duct</li>
</ul>

<h2>Round vs Rectangular Duct Fittings — Which Do You Need?</h2>
<p><strong>Round spiral duct</strong> is the standard for residential forced-air systems and most light commercial HVAC. It\'s easier to install, seals more reliably, and is available in sizes from 4" to 30". <strong>Rectangular duct</strong> is commonly used in commercial applications, plenum spaces, and where height clearance is limited. BHS stocks both types in full size ranges.</p>

<h2>Flexible Duct — R4.2, R6, R8 in Stock</h2>
<p>We stock <strong>flexible duct in R4.2, R6, and R8 insulation values</strong> — the most commonly specified ratings for residential and light commercial HVAC. R8 insulated flex duct is required in many Ontario building code applications for unconditioned spaces. All flex duct is in stock in 25-foot lengths and available for same-day pickup.</p>

<h2>Wholesale Pricing for Mississauga HVAC Contractors</h2>
<p>BHS Supplies offers <strong>wholesale pricing on all sheet metal duct fittings</strong> with no minimum order for walk-in customers. Trade accounts are available for licensed contractors who need volume pricing and priority stock. Set up your trade account by calling <strong>(647) 456-2244</strong> or visiting us at <strong>7040 Torbram Rd #8, Mississauga</strong>.</p>
<p>Open 7 days: Mon–Sat 10am–6pm, Sunday 10am–2pm. Serving Mississauga, Brampton, Toronto, Vaughan, Oakville, and all of the GTA.</p>',
            ],
            [
                'title'             => 'Best HVAC Supply Store in Mississauga for Contractors — Why Contractors Choose BHS',
                'slug'              => 'best-hvac-supply-store-mississauga-contractors',
                'meta_title'        => 'Best HVAC Supply Store Mississauga for Contractors | BHS Supplies',
                'meta_description'  => 'Looking for the best HVAC supply store in Mississauga? BHS Supplies offers 2,000+ SKUs, wholesale contractor pricing, trade accounts, and same-day pickup — open 7 days a week.',
                'meta_keywords'     => 'best HVAC supply store Mississauga, HVAC supply store near me Mississauga, HVAC contractor supply Mississauga, wholesale HVAC store GTA',
                'short_description' => 'Mississauga HVAC contractors choose BHS Supplies for 2,000+ SKUs, same-day pickup, 7-day availability, and wholesale trade pricing. Here\'s why.',
                'description'       => '<h2>Why Mississauga HVAC Contractors Choose BHS Supplies</h2>
<p>If you\'re searching for the <strong>best HVAC supply store in Mississauga</strong>, you\'ve found it. <strong>BHS Supplies at 7040 Torbram Rd #8</strong> serves licensed HVAC technicians, plumbers, gas fitters, and contractors across the GTA with one of the most comprehensive in-stock inventories in Mississauga.</p>

<h2>2,000+ SKUs — All In Stock</h2>
<p>Unlike smaller suppliers that have limited inventory and long lead times, BHS Supplies maintains a full warehouse of <strong>2,000+ SKUs</strong> across HVAC, plumbing, and hardware categories. From <strong>sheet metal duct fittings and flexible duct</strong> to <strong>PEX pipe, brass fittings, gas valves, refrigerants, and air filters</strong> — if you need it for your HVAC or plumbing job, we have it.</p>

<h2>Open 7 Days a Week</h2>
<p>Most HVAC supply stores in Mississauga are closed on Sundays or have limited Saturday hours. BHS Supplies is open <strong>Monday–Saturday 10am–6pm and Sunday 10am–2pm</strong>, because we know contractors work weekends. No more scrambling for emergency parts on a Saturday afternoon.</p>

<h2>Same-Day Walk-In Pickup</h2>
<p>Order online at <strong>bhssupplies.com</strong> or walk in — every in-stock item is available for <strong>same-day pickup</strong>. Call ahead for large or unusual orders: <strong>(647) 456-2244</strong>. Our staff will have your order ready when you arrive.</p>

<h2>Wholesale Contractor Pricing</h2>
<p>BHS offers <strong>wholesale HVAC supplies pricing</strong> with no minimum order for walk-in customers. Licensed contractors who set up a <strong>trade account</strong> get additional volume pricing, priority stock access, and a dedicated account contact. This is the pricing structure that helps contractors stay on budget on every job.</p>

<h2>Trade Accounts — Set Up in Minutes</h2>
<p>Trade account registration is simple. Call <strong>(647) 456-2244</strong> or email <strong>support@bhssupplies.com</strong> with your contractor license number and business name. Once set up, your trade account is active for our B2B portal at bhssupplies.com — browse live inventory, check prices, and order for same-day pickup.</p>

<h2>Visit Us — 7040 Torbram Rd #8, Mississauga</h2>
<p>Conveniently located at <strong>7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</strong> — accessible from Brampton (10 min via Hwy 410), Toronto (20 min via Hwy 427), and Vaughan (25 min via Hwy 427/400). Serving HVAC and plumbing contractors across all of the GTA.</p>',
            ],
            [
                'title'             => 'Gas Valve & Black Iron Pipe Supplier in Mississauga — BHS Supplies',
                'slug'              => 'gas-valve-black-iron-pipe-supplier-mississauga',
                'meta_title'        => 'Gas Valve & Black Iron Pipe Mississauga | Wholesale | BHS Supplies',
                'meta_description'  => 'BHS Supplies stocks gas valves, black iron pipe in all schedules, CSST flexible gas pipe, and all gas fittings in Mississauga. Wholesale pricing. Same-day pickup. (647) 456-2244.',
                'meta_keywords'     => 'gas valve Mississauga, black iron pipe supplier Mississauga, CSST gas pipe Mississauga, gas fittings wholesale GTA, gas contractor supply Mississauga',
                'short_description' => 'BHS Supplies is Mississauga\'s fully stocked gas supply store — gas valves, black iron pipe, CSST, unions, and all gas fittings at wholesale contractor pricing.',
                'description'       => '<h2>Gas Valves, Black Iron Pipe & CSST — In Stock in Mississauga</h2>
<p>Licensed gas contractors and plumbers across Mississauga, Brampton, and the GTA rely on <strong>BHS Supplies</strong> for a complete range of <strong>gas supply products</strong>. Located at <strong>7040 Torbram Rd #8, Mississauga</strong>, we carry everything for residential and commercial gas line installations — from rough-in to final connection.</p>

<h2>Gas Valves — All Types & Sizes</h2>
<p>Our gas valve inventory includes:</p>
<ul>
<li><strong>Gas ball valves</strong> — 1/2", 3/4", 1", 1-1/4", 1-1/2", 2" — full port and standard port</li>
<li><strong>Gas cocks</strong> (plug valves) — all sizes</li>
<li><strong>Appliance gas valves</strong> — for furnaces, water heaters, dryers</li>
<li><strong>Manual shutoff valves</strong> — all sizes and configurations</li>
<li><strong>Gas pressure regulators</strong> — 2-stage and single-stage</li>
</ul>

<h2>Black Iron Pipe — All Schedules & Sizes</h2>
<p>We stock <strong>black iron pipe (Schedule 40)</strong> in all standard lengths and diameters from 1/4" to 2" for natural gas and propane distribution systems. Our black iron fittings inventory includes:</p>
<ul>
<li>Elbows (90° and 45°), street elbows</li>
<li>Tees, crosses, couplings, unions</li>
<li>Reducers, bushings, caps, plugs</li>
<li>Nipples — all standard lengths</li>
<li>Flanges — all standard sizes</li>
</ul>

<h2>CSST Flexible Gas Piping</h2>
<p>BHS Supplies stocks <strong>CSST flexible gas piping</strong> (corrugated stainless steel tubing) for residential and light commercial gas distribution. CSST is the modern standard for gas rough-in in new construction and renovation — faster to install than black iron, with fewer connections and lower leak risk. We carry CSST in 1/2" through 1-1/2" and sell by the foot.</p>

<h2>Wholesale Pricing for Licensed Gas Contractors</h2>
<p>All gas supply products are available at <strong>wholesale contractor pricing</strong> with no minimum order. Trade accounts for licensed contractors include volume pricing. Call <strong>(647) 456-2244</strong> to set up your account or to confirm large-order availability before your visit.</p>
<p><strong>Store hours:</strong> Mon–Sat 10am–6pm, Sunday 10am–2pm | <strong>Address:</strong> 7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</p>',
            ],
            [
                'title'             => 'Brass Fittings Wholesale in Mississauga — Contractor Pricing at BHS Supplies',
                'slug'              => 'brass-fittings-wholesale-mississauga-contractor-pricing',
                'meta_title'        => 'Brass Fittings Wholesale Mississauga | Contractor Pricing | BHS Supplies',
                'meta_description'  => 'BHS Supplies stocks wholesale brass fittings in Mississauga — elbows, tees, ball valves, couplings, reducers. Contractor pricing, no minimums. Same-day pickup. Call (647) 456-2244.',
                'meta_keywords'     => 'brass fittings wholesale Mississauga, brass fittings contractor GTA, plumbing brass fittings supplier Mississauga, copper brass fittings Mississauga',
                'short_description' => 'BHS Supplies carries the full range of brass fittings at wholesale contractor pricing in Mississauga — elbows, tees, couplings, ball valves, reducers, and more.',
                'description'       => '<h2>Wholesale Brass Fittings in Mississauga — BHS Supplies</h2>
<p>Plumbers and contractors across Mississauga, Brampton, and Toronto trust <strong>BHS Supplies</strong> for wholesale brass fittings pricing. Located at <strong>7040 Torbram Rd #8, Mississauga</strong>, we carry the full range of brass fittings for plumbing, hydronic, and gas applications — available for same-day walk-in pickup.</p>

<h2>Brass Fittings We Stock</h2>
<p>Our brass fittings inventory covers every standard plumbing and hydronic connection:</p>
<ul>
<li><strong>Brass elbows</strong> — 90° and 45°, street and regular, all sizes 1/4" to 2"</li>
<li><strong>Brass tees</strong> — equal and reducing, all sizes</li>
<li><strong>Brass couplings</strong> — full and half, reducing</li>
<li><strong>Brass ball valves</strong> — full port and standard, 1/4" to 2"</li>
<li><strong>Brass reducers and bushings</strong> — all standard size combinations</li>
<li><strong>Brass unions</strong> — all sizes</li>
<li><strong>Brass nipples</strong> — close, short, long, and extra-long in all sizes</li>
<li><strong>Brass caps and plugs</strong> — all sizes</li>
<li><strong>Sweat-to-thread adapters</strong> — male and female in all sizes</li>
</ul>

<h2>Push-Fit Brass Fittings (SharkBite Compatible)</h2>
<p>We also stock <strong>push-fit brass fittings</strong> compatible with SharkBite and similar systems — couplings, elbows, tees, ball valves — all for copper, PEX, and CPVC pipe. Push-fit fittings are increasingly popular for service calls and renovation work where soldering is impractical.</p>

<h2>Wholesale Contractor Pricing</h2>
<p>BHS Supplies offers <strong>wholesale brass fittings pricing</strong> for licensed plumbers and contractors with no minimum order. Volume pricing through our <strong>trade account program</strong> is available for contractors who need consistent supply across multiple jobs. Set up your trade account by calling <strong>(647) 456-2244</strong>.</p>

<h2>Same-Day Pickup — 7 Days a Week</h2>
<p>All brass fittings are in stock at our <strong>Mississauga warehouse</strong> for same-day walk-in pickup. Open Mon–Sat 10am–6pm and Sunday 10am–2pm. Order at bhssupplies.com or call ahead for large orders. Serving Mississauga, Brampton, Toronto, Etobicoke, Vaughan, and all of the GTA.</p>',
            ],
            [
                'title'             => 'Refrigerant Supplier in Mississauga — R-410A, R-32, R-454B In Stock',
                'slug'              => 'refrigerant-supplier-mississauga-r410a-r32-r454b',
                'meta_title'        => 'Refrigerant Supplier Mississauga — R-410A, R-32, R-454B | BHS Supplies',
                'meta_description'  => 'BHS Supplies stocks R-410A, R-32, and R-454B refrigerants at wholesale contractor pricing in Mississauga. Same-day pickup. Licensed contractors only. Call (647) 456-2244.',
                'meta_keywords'     => 'refrigerant supplier Mississauga, R-410A Mississauga, R-32 R-454B Mississauga, HVAC refrigerant wholesale GTA, refrigerant contractor pricing Mississauga',
                'short_description' => 'BHS Supplies is a licensed refrigerant supplier in Mississauga — R-410A, R-32, R-454B, and more at wholesale contractor pricing with same-day pickup.',
                'description'       => '<h2>Refrigerant Supplier in Mississauga — BHS Supplies</h2>
<p>Licensed HVAC technicians across Mississauga, Brampton, Toronto, and the GTA rely on <strong>BHS Supplies at 7040 Torbram Rd #8, Mississauga</strong> for in-stock refrigerant at <strong>wholesale contractor pricing</strong>. We carry the most common refrigerant lines used in residential and commercial HVAC — available for same-day pickup.</p>

<h2>Refrigerants We Stock</h2>
<ul>
<li><strong>R-410A</strong> — the current standard for residential and light commercial AC and heat pump systems (being phased out; stock up now)</li>
<li><strong>R-32</strong> — lower GWP alternative to R-410A, increasingly specified in new equipment</li>
<li><strong>R-454B</strong> (Puron Advance) — the A2L replacement refrigerant for new residential HVAC equipment under the new HFC phase-down rules</li>
<li><strong>R-22</strong> — recovered/reclaimed stock for servicing older systems</li>
</ul>
<p>Call ahead to confirm large cylinder availability: <strong>(647) 456-2244</strong>.</p>

<h2>Understanding the Refrigerant Transition (A2L Refrigerants)</h2>
<p>The HVAC industry is in the middle of a major refrigerant transition. Under Canada\'s HFC phase-down regulations, <strong>R-410A is being phased out</strong> in new equipment. The leading replacement refrigerant for residential systems is <strong>R-454B</strong> (sold as Puron Advance by Carrier). R-32 is also gaining traction. BHS Supplies stocks all transition refrigerants so GTA contractors are ready for new equipment installations as the market shifts.</p>

<h2>HVAC Service Tools — Also in Stock</h2>
<p>Alongside refrigerant, BHS stocks the tools licensed HVAC technicians need for refrigerant work:</p>
<ul>
<li>Digital manifold gauge sets</li>
<li>Refrigerant recovery machines</li>
<li>Vacuum pumps (2-stage)</li>
<li>Refrigerant leak detectors</li>
<li>Refrigerant scales (digital)</li>
<li>Charging hoses and service valves</li>
</ul>

<h2>Wholesale Pricing for Licensed HVAC Technicians</h2>
<p>Refrigerant is sold to <strong>licensed HVAC technicians only</strong> at wholesale contractor pricing. Trade account holders get volume pricing on refrigerant orders. Set up your trade account: call <strong>(647) 456-2244</strong> or email <strong>support@bhssupplies.com</strong>.</p>
<p><strong>Store hours:</strong> Mon–Sat 10am–6pm, Sunday 10am–2pm | <strong>Address:</strong> 7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</p>',
            ],
            [
                'title'             => 'Plumbing Rough-In Supplies Mississauga — Same-Day Pickup for Contractors',
                'slug'              => 'plumbing-rough-in-supplies-mississauga-same-day-pickup',
                'meta_title'        => 'Plumbing Rough-In Supplies Mississauga | Same-Day Pickup | BHS Supplies',
                'meta_description'  => 'BHS Supplies stocks all plumbing rough-in supplies in Mississauga — copper fittings, PEX, PVC, CPVC, brass, CSST. Wholesale contractor pricing. Open 7 days. (647) 456-2244.',
                'meta_keywords'     => 'plumbing rough-in supplies Mississauga, plumbing fittings wholesale Mississauga, copper fittings Mississauga contractor, plumbing supply store Mississauga GTA',
                'short_description' => 'BHS Supplies is your complete plumbing rough-in supplier in Mississauga — PEX, copper, brass, CSST, and all fittings at wholesale pricing with same-day pickup.',
                'description'       => '<h2>Plumbing Rough-In Supplies — BHS Supplies Mississauga</h2>
<p>Whether you\'re framing a new build or renovating a bathroom in Mississauga, BHS Supplies has every <strong>plumbing rough-in supply</strong> you need in stock for same-day pickup. Located at <strong>7040 Torbram Rd #8, Mississauga</strong>, we serve licensed plumbers and contractors across the GTA with <strong>wholesale plumbing supplies</strong> and no minimum order.</p>

<h2>Water Supply — Pipe & Fittings</h2>
<p>For water supply rough-in, we stock:</p>
<ul>
<li><strong>PEX pipe</strong> — A and B types, 3/8" to 1-1/2", standard and oxygen-barrier</li>
<li><strong>Copper pipe and fittings</strong> — Types L and M, all standard sizes</li>
<li><strong>CPVC pipe and fittings</strong> — for chlorinated water systems</li>
<li><strong>Push-fit fittings</strong> — SharkBite compatible for copper, PEX, CPVC</li>
<li><strong>Brass fittings</strong> — full range of elbows, tees, couplings, ball valves</li>
</ul>

<h2>Drain, Waste & Vent — ABS and PVC</h2>
<p>For DWV rough-in:</p>
<ul>
<li><strong>ABS pipe and fittings</strong> — 1-1/2" to 4" — elbows, tees, wyes, P-traps</li>
<li><strong>PVC pipe and fittings</strong> — SDR-35 and schedule 40</li>
<li><strong>ABS cement and primer</strong></li>
<li><strong>PVC cement and primer</strong></li>
<li><strong>Fernco flexible couplings</strong> — all sizes</li>
</ul>

<h2>Gas Rough-In Supplies</h2>
<ul>
<li><strong>Black iron pipe and fittings</strong> — schedule 40, all sizes</li>
<li><strong>CSST flexible gas piping</strong> — 1/2" to 1-1/2"</li>
<li><strong>Gas ball valves</strong> — all sizes</li>
<li><strong>Gas cocks and unions</strong></li>
</ul>

<h2>Water Heater Parts</h2>
<p>We also stock a full range of <strong>water heater parts and accessories</strong> — anode rods, expansion tanks, T&P relief valves, drains, dielectric unions, and water heater supply lines for both tank and tankless water heaters.</p>

<h2>Same-Day Pickup — Open 7 Days</h2>
<p>All plumbing supplies are in stock at our <strong>Mississauga warehouse</strong>. Open Mon–Sat 10am–6pm and Sunday 10am–2pm. Order at bhssupplies.com or call ahead for large orders: <strong>(647) 456-2244</strong>. Serving Mississauga, Brampton, Toronto, Etobicoke, Vaughan, Oakville, and all of the GTA.</p>',
            ],
            [
                'title'             => 'HVAC Supplies in Brampton — GTA Contractors Served from Mississauga',
                'slug'              => 'hvac-supplies-brampton-gta-contractors',
                'meta_title'        => 'HVAC Supplies Brampton — Wholesale PEX, Sheet Metal, Gas Valves | BHS Supplies',
                'meta_description'  => 'BHS Supplies serves Brampton HVAC contractors from Mississauga — 10 min via Hwy 410. Sheet metal duct fittings, PEX pipe, brass fittings, gas valves. Same-day pickup. (647) 456-2244.',
                'meta_keywords'     => 'HVAC supplies Brampton, HVAC supply store near Brampton, sheet metal duct Brampton, PEX pipe Brampton wholesale, gas valve CSST Brampton contractor',
                'short_description' => 'BHS Supplies serves Brampton HVAC and plumbing contractors from our Mississauga warehouse — just 10 minutes via Highway 410. Full HVAC and plumbing stock, same-day pickup.',
                'description'       => '<h2>HVAC Supplies for Brampton Contractors — BHS Supplies</h2>
<p>If you\'re a licensed HVAC contractor or plumber working in Brampton, <strong>BHS Supplies is your closest wholesale HVAC supply store</strong>. Our warehouse at <strong>7040 Torbram Rd #8, Mississauga</strong> is approximately 10 minutes from central Brampton via Highway 410 — making us faster to reach than most Brampton or Mississauga alternatives.</p>

<h2>How to Get Here from Brampton</h2>
<p>From central Brampton: Take <strong>Highway 410 South</strong> to <strong>Derry Rd East</strong>, then south on Torbram Rd. We\'re at 7040 Torbram Rd #8 — look for the BHS Supplies sign. Free parking on site. The entire drive from Bramalea or Mount Pleasant is under 15 minutes.</p>

<h2>What Brampton HVAC Contractors Buy at BHS</h2>
<p>Brampton contractors rely on BHS Supplies for:</p>
<ul>
<li><strong>Sheet metal duct fittings</strong> — full range, same-day</li>
<li><strong>Flexible duct</strong> — R4.2, R6, R8 in all diameters</li>
<li><strong>PEX pipe and brass fittings</strong> — wholesale pricing</li>
<li><strong>Gas valves, black iron pipe, and CSST</strong></li>
<li><strong>Refrigerants</strong> — R-410A, R-32, R-454B</li>
<li><strong>Air filters</strong> — all sizes including HEPA</li>
<li><strong>Thermostats and controls</strong> — programmable and smart</li>
<li><strong>Hardware, fasteners, and safety/PPE</strong></li>
</ul>

<h2>Trade Accounts for Brampton Contractors</h2>
<p>Set up a <strong>BHS trade account</strong> and get volume pricing, priority stock, and no minimum order — ideal for Brampton-based HVAC companies and plumbing contractors who need consistent supply. Register by calling <strong>(647) 456-2244</strong> or visiting bhssupplies.com.</p>

<h2>Open 7 Days — Including Weekends</h2>
<p>BHS Supplies is open <strong>Monday–Saturday 10am–6pm and Sunday 10am–2pm</strong>. When you need parts for an emergency call on a Saturday or Sunday in Brampton, we\'re open when other suppliers aren\'t. Walk in or order ahead at bhssupplies.com.</p>
<p><strong>Address:</strong> 7040 Torbram Rd #8, Mississauga, ON L4T 3Z4 | <strong>Phone:</strong> (647) 456-2244</p>',
            ],
        ];

        foreach ($posts as $data) {
            Blog::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $category->id,
                    'status'      => 1,
                    'news'        => 0,
                    'event'       => 0,
                    'going_on'    => 0,
                ])
            );
        }

        $this->command->info('GBP SEO Blog Posts seeder complete — ' . count($posts) . ' posts created.');
    }
}
