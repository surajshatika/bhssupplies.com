<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExcaliburPartsSeeder extends Seeder
{
    private int $adminId;
    private string $lang;

    public function run(): void
    {
        $admin = User::where('user_type', 'admin')->first();
        $this->adminId = $admin->id ?? 1;
        $this->lang = env('DEFAULT_LANGUAGE', 'en');

        $parts  = $this->cat('PARTS & ACCESSORIES');
        $media  = $this->cat('MEDIA & RESINS');
        $ro     = $this->cat('REVERSE OSMOSIS');
        $uv     = $this->cat('ULTRAVIOLET SYSTEMS');
        $mt     = $this->cat('MINERAL TANKS');
        $ctrl   = $this->cat('CONTROL VALVES');

        // ── FILTER HOUSINGS ───────────────────────────────────────────────────
        $this->variable('Jumbo 10 Inch Filter Housing', $parts,
            '<p>Big Blue 10" jumbo filter housing. 3/4" FNPT connections with pressure relief button. Blue sump, black cap. Max 85 PSI operating pressure. Available plain or as complete kit with isolation valves.</p>',
            'Big Blue 10" jumbo filter housing, 3/4" FNPT, with pressure relief.',
            'filter housing,big blue,10 inch,jumbo',
            [
                ['label'=>'Plain','sku'=>'FH-10BB075', 'price'=>120.00],
                ['label'=>'Complete Kit','sku'=>'FH-10BB075C','price'=>135.00],
            ]
        );

        $this->variable('Jumbo 20 Inch Filter Housing', $parts,
            '<p>Big Blue 20" jumbo filter housing. 3/4" FNPT connections with pressure relief button. Blue sump, black cap. Max 85 PSI. Available plain or as complete kit.</p>',
            'Big Blue 20" jumbo filter housing, 3/4" FNPT, with pressure relief.',
            'filter housing,big blue,20 inch,jumbo',
            [
                ['label'=>'Plain','sku'=>'FH-20BB075', 'price'=>162.50],
                ['label'=>'Complete Kit','sku'=>'FH-20BB075C','price'=>180.00],
            ]
        );

        $this->variable('Jumbo 2-Stage 10 Inch Filter System', $parts,
            '<p>Excalibur Jumbo 2-Stage 10" filter system. Complete with inlet and outlet isolation valves, pressure gauges and housing wrench. Filters not included.</p>',
            'Jumbo 2-stage 10" filter system with isolation valves and gauges.',
            'filter system,2 stage,10 inch,jumbo',
            [
                ['label'=>'2 Stage 10 inch','sku'=>'FH-DH10C','price'=>495.00],
                ['label'=>'3 Stage 10 inch','sku'=>'FH-TH10C','price'=>617.50],
            ]
        );

        $this->variable('Jumbo 2-Stage 20 Inch Filter System', $parts,
            '<p>Excalibur Jumbo filter system for 20" housings. Complete with inlet and outlet isolation valves, pressure gauges and housing wrench. Filters not included.</p>',
            'Jumbo filter system for 20" housings with isolation valves and gauges.',
            'filter system,20 inch,jumbo',
            [
                ['label'=>'2 Stage 20 inch','sku'=>'FH-DH20C','price'=>625.50],
                ['label'=>'3 Stage 20 inch','sku'=>'FH-TH20C','price'=>770.00],
            ]
        );

        $this->variable('Filter Housing Mounting Bracket', $parts,
            '<p>Mounting brackets with screws for jumbo filter housings. Available in single, double or triple housing configurations.</p>',
            'Filter housing mounting bracket. Single, double or triple configurations.',
            'filter housing,bracket,mounting',
            [
                ['label'=>'Single','sku'=>'FH-FM25AC','price'=>30.00],
                ['label'=>'Double','sku'=>'FH-FM30AC','price'=>57.50],
                ['label'=>'Triple','sku'=>'FH-FM40AC','price'=>105.00],
            ]
        );

        // ── SEDIMENT FILTERS ──────────────────────────────────────────────────
        $this->variable('Polypropylene Sediment Filter', $parts,
            '<p>Excalibur polypropylene spun sediment filters. 100% pure polypropylene, bacteria and chemical resistant. True gradient density for efficient filtration.</p>',
            'Polypropylene sediment filter cartridges in multiple sizes.',
            'sediment filter,polypropylene,cartridge',
            [
                ['label'=>'2.5x10 inch 5 micron','sku'=>'SMF-PP1005',  'price'=>5.00],
                ['label'=>'4.5x10 inch 5 micron','sku'=>'SMF-PP10B05', 'price'=>25.00],
                ['label'=>'4.5x20 inch 5 micron','sku'=>'SMF-PP20B05', 'price'=>45.00],
            ]
        );

        $this->variable('Carbon Block Filter Cartridge', $parts,
            '<p>Excalibur carbon block filter cartridges for chlorine, taste and odour reduction. Coconut shell acid washed carbon.</p>',
            'Carbon block filter cartridges in standard and Big Blue sizes.',
            'carbon block,filter,chlorine,cartridge',
            [
                ['label'=>'2.5x10 inch Standard','sku'=>'SMF-CBC1005',  'price'=>17.50],
                ['label'=>'4.5x10 inch Big Blue', 'sku'=>'SMF-CBC10B05','price'=>85.00],
                ['label'=>'4.5x20 inch Big Blue', 'sku'=>'SMF-CBC20B05','price'=>142.50],
            ]
        );

        $this->variable('Dual Gradient Density Jumbo Filter 10 Inch', $parts,
            '<p>Excalibur dual gradient density polypropylene cartridges for 10" Jumbo housings. True gradient reduces differential pressure for longer life. 8 per case.</p>',
            'DGD dual gradient density jumbo sediment cartridge, 10" Jumbo. 8 per case.',
            'DGD,dual gradient,sediment,10 inch,jumbo',
            [
                ['label'=>'25-1 micron','sku'=>'SMF-DGD10BB2501','price'=>32.50],
                ['label'=>'25-5 micron','sku'=>'SMF-DGD10BB2505','price'=>32.50],
                ['label'=>'50-25 micron','sku'=>'SMF-DGD10BB5025','price'=>32.50],
            ]
        );

        $this->variable('Dual Gradient Density Jumbo Filter 20 Inch', $parts,
            '<p>Excalibur dual gradient density polypropylene cartridges for 20" Jumbo housings. 6 per case.</p>',
            'DGD dual gradient density jumbo sediment cartridge, 20" Jumbo. 6 per case.',
            'DGD,dual gradient,sediment,20 inch,jumbo',
            [
                ['label'=>'25-1 micron','sku'=>'SMF-DGD20BB2501','price'=>85.00],
                ['label'=>'25-5 micron','sku'=>'SMF-DGD20BB2505','price'=>85.00],
                ['label'=>'50-25 micron','sku'=>'SMF-DGD20BB5025','price'=>85.00],
            ]
        );

        $this->simple('Filter Coconut Shell Carbon 2x10 Inch Polishing', 'SMF-AICRO', 20.00, $parts,
            '<p>Excalibur coconut shell carbon 2"x10" polishing filter with 1/4" FNPT connections. For reverse osmosis post-polishing and inline applications.</p>',
            'Coconut shell carbon 2"x10" polishing filter, 1/4" FNPT.',
            'coconut carbon,polishing,RO,filter'
        );

        $this->simple('Doulton Ceramic Sterasyl Filter 2x10 Inch', 'CRF-W9220402', 130.00, $parts,
            '<p>Doulton Ceramic Sterasyl filter cartridge. 2"x9-3/4" standard 10" size. 0.9 micron absolute rating for bacteria removal.</p>',
            'Doulton Ceramic Sterasyl filter, 2"x10", 0.9 micron bacteria removal.',
            'ceramic,Doulton,bacteria,filter,0.9 micron'
        );

        $this->variable('Pleated Sediment Cartridge 4.5x20 Inch', $parts,
            '<p>Pleated sediment cartridges for 4.5"x20" Jumbo filter housings. Very fine particle removal.</p>',
            'Pleated sediment cartridges for 20" Jumbo housings. 0.35 or 1 micron.',
            'pleated,sediment,20 inch,jumbo',
            [
                ['label'=>'0.35 micron nominal','sku'=>'SMF-PL20B035','price'=>220.00],
                ['label'=>'1 micron absolute',  'sku'=>'SMF-PL20B01A','price'=>220.00],
            ]
        );

        // ── RO COMPONENTS & REPLACEMENT PARTS ────────────────────────────────
        $this->variable('RO Replacement Membrane', $ro,
            '<p>Excalibur reverse osmosis replacement membrane. Filmtec thin film composite (TFC) membranes for residential RO systems.</p>',
            'Filmtec TFC reverse osmosis replacement membrane. 75 or 100 GPD.',
            'RO,membrane,replacement,Filmtec',
            [
                ['label'=>'75 GPD', 'sku'=>'RO-TFC75', 'price'=>117.50],
                ['label'=>'100 GPD','sku'=>'RO-TFC100','price'=>160.00],
            ]
        );

        $this->simple('RO Booster Pump 110V', 'RO-BP110V', 222.50, $ro,
            '<p>Excalibur Reverse Osmosis Booster Pump 110V. Increases water pressure for improved RO performance. 80-100 PSI output.</p>',
            'RO booster pump 110V, 80-100 PSI.',
            'RO,booster pump,pressure'
        );

        $this->variable('RO Holding Tank', $ro,
            '<p>Excalibur RO holding tanks for reverse osmosis systems. Available in 5 or 14 gallon capacity.</p>',
            'RO storage/holding tank. 5 gallon plastic or 14 gallon metal.',
            'RO,tank,holding,storage',
            [
                ['label'=>'5 Gallon Plastic','sku'=>'RO-TNK5',  'price'=>140.00],
                ['label'=>'14 Gallon Metal', 'sku'=>'RO-TNK14W','price'=>485.00],
            ]
        );

        $this->simple('Omnipure Neutralizer Calcite GAC Cartridge', 'SMF-K2551BB1', 55.00, $ro,
            '<p>Omnipure Neutralizer Calcite/GAC cartridge. Raises pH from 5.5 to neutral and reduces chlorine in RO drinking water.</p>',
            'Omnipure neutralizer calcite/GAC cartridge for RO systems, raises pH.',
            'RO,neutralizer,calcite,pH,cartridge'
        );

        $this->simple('Omnipure Alkaline Calcite Corosex Cartridge', 'SMF-K2551BB2', 55.00, $ro,
            '<p>Omnipure Alkaline Calcite/Corosex cartridge. Raises pH from 4.5 to neutral for RO drinking water remineralization.</p>',
            'Omnipure alkaline calcite/corosex cartridge for RO systems, raises pH.',
            'RO,alkaline,calcite,corosex,pH,cartridge'
        );

        $this->simple('Smart Purifier PLUS PCT Replacement Filter', 'SMF-RO1000PCF', 100.00, $ro,
            '<p>PCT replacement filter cartridge for Excalibur Smart Purifier PLUS Tankless RO System (EWR 301000).</p>',
            'Replacement PCT filter for Smart Purifier PLUS RO system.',
            'RO,replacement,filter,Smart Purifier'
        );

        $this->simple('Smart Purifier PLUS Replacement Membrane', 'RO-1000M', 357.50, $ro,
            '<p>Replacement reverse osmosis membrane for Excalibur Smart Purifier PLUS Tankless RO System (EWR 301000).</p>',
            'Replacement RO membrane for Smart Purifier PLUS system.',
            'RO,membrane,replacement,Smart Purifier'
        );

        $this->simple('Smart Purifier PLUS Alkaline Filter', 'SMF-RO1000AF', 128.00, $ro,
            '<p>Alkaline replacement filter for Excalibur Smart Purifier PLUS Tankless RO System (EWR 301000).</p>',
            'Alkaline replacement filter for Smart Purifier PLUS RO system.',
            'RO,alkaline,filter,Smart Purifier'
        );

        // ── UV REPLACEMENT PARTS ──────────────────────────────────────────────
        $this->variable('UV Replacement Lamp for 8GPM and 10GPM Rack', $uv,
            '<p>Replacement UV lamp for Excalibur UV Dynamics Mini Rack and sterilizer systems. Full 1-year (9,000 hour) warranty.</p>',
            'Replacement UV lamp. Fits 8/10 GPM UV Dynamics rack models.',
            'UV,lamp,replacement,8gpm,10gpm',
            [
                ['label'=>'8 and 10 GPM Rack', 'sku'=>'UVS-400152UVD','price'=>187.50],
                ['label'=>'13 GPM',             'sku'=>'UVS-400128',   'price'=>232.50],
                ['label'=>'15-40 GPM',          'sku'=>'UVS-400158',   'price'=>320.00],
            ]
        );

        $this->variable('UV Replacement Quartz Sleeve', $uv,
            '<p>Replacement quartz sleeve for Excalibur UV Dynamics mini rack and sterilizer systems.</p>',
            'Replacement quartz sleeve for UV Dynamics systems.',
            'UV,sleeve,quartz,replacement',
            [
                ['label'=>'8 and 10 GPM','sku'=>'UVS-400151','price'=>130.00],
                ['label'=>'13 GPM',      'sku'=>'UVS-400129','price'=>150.00],
                ['label'=>'15-40 GPM',   'sku'=>'UVS-400157','price'=>252.50],
            ]
        );

        $this->variable('UV Replacement Lamp for Luminor Signature Series', $uv,
            '<p>Replacement UV lamp for Excalibur Luminor Signature Series systems. 1-year (9,000 hour) warranty.</p>',
            'Replacement UV lamp for Luminor Signature Series systems.',
            'UV,lamp,Luminor,Signature,replacement',
            [
                ['label'=>'8 GPM Rack','sku'=>'UVS-EXW-L420', 'price'=>140.00],
                ['label'=>'15 GPM',    'sku'=>'UVS-EXWL420C', 'price'=>221.00],
            ]
        );

        $this->simple('UV Wifi Module Concierge Luminor', 'UVS-MODAPP', 280.00, $uv,
            '<p>WiFi Module for Excalibur Luminor Colour Screen UV Systems. Enables remote monitoring and control via app.</p>',
            'WiFi module for Luminor colour screen UV systems.',
            'UV,wifi,module,Luminor,smart'
        );

        $this->simple('UV NSF Solenoid Valve Kit 1 Inch 120VDC', 'UVS-400455', 747.50, $uv,
            '<p>1" Solenoid Valve Kit with remote solenoid interface for NSF UV systems. 120VDC.</p>',
            '1" solenoid valve kit for NSF UV systems, 120VDC.',
            'UV,solenoid valve,NSF,accessory'
        );

        // ── CONTROL VALVES ────────────────────────────────────────────────────
        $this->variable('Control Valve 1 Inch Electronic Metered Demand 5 Button', $ctrl,
            '<p>Excalibur 1" Electronic Metered Demand 5-Button Control Valve for water softeners. Bypass and 3/4" brass adapter kit included with Complete version. 36 selectable regeneration cycles, double backwash, days override.</p>',
            'Excalibur 1" 5-button electronic metered demand control valve.',
            'control valve,softener,metered,demand,EWS',
            [
                ['label'=>'Standard', 'sku'=>'CLK-V1DME', 'price'=>1000.00],
                ['label'=>'Complete', 'sku'=>'CLK-V1DMEC','price'=>1055.00],
            ]
        );

        $this->simple('Control Valve 1 Inch Electronic Demand Filter', 'CLK-V1DMZ', 1000.00, $ctrl,
            '<p>Excalibur 1" Electronic Demand Filter Control Valve. For backwashing filter systems.</p>',
            'Excalibur 1" electronic demand filter control valve.',
            'control valve,filter,demand,EWS'
        );

        $this->variable('Control Valve 1 Inch Electronic Metered Demand 4 Button', $ctrl,
            '<p>Excalibur 1" Electronic Metered Demand 4-Button Control Valve. Bypass and 3/4" brass adapter kit included with Complete version.</p>',
            'Excalibur 1" 4-button electronic metered demand control valve.',
            'control valve,softener,4 button,EWS',
            [
                ['label'=>'Standard', 'sku'=>'CLK-V1EEDME', 'price'=>1060.00],
                ['label'=>'Complete', 'sku'=>'CLK-V1EEDMEC','price'=>1110.00],
            ]
        );

        $this->variable('Control Valve 1 Inch Electronic Timer Softener 3 Button', $ctrl,
            '<p>Excalibur 1" Electronic Timer 3-Button Control Valve for water softeners.</p>',
            'Excalibur 1" 3-button electronic timer softener control valve.',
            'control valve,softener,timer,3 button,EWS',
            [
                ['label'=>'Standard', 'sku'=>'CLK-V1TCDTE', 'price'=>740.00],
                ['label'=>'Complete', 'sku'=>'CLK-V1TCDTEC','price'=>875.00],
            ]
        );

        $this->simple('Control Valve 1 Inch Electronic Timer Filter 3 Button', 'CLK-V1TCBTZ', 705.00, $ctrl,
            '<p>Excalibur 1" Electronic Timer 3-Button Control Valve for backwashing filter systems.</p>',
            'Excalibur 1" 3-button electronic timer filter control valve.',
            'control valve,filter,timer,3 button,EWS'
        );

        $this->simple('Bypass Valve 1 Inch Plastic', 'CLK-V3006', 95.00, $ctrl,
            '<p>Excalibur 1" plastic bypass valve for water softeners and filters.</p>',
            '1" plastic bypass valve.',
            'bypass valve,1 inch,plastic'
        );

        $this->simple('External Inline Mixing Valve', 'CLK-V4099', 100.00, $ctrl,
            '<p>External Inline Mixing Valve for blending treated and untreated water to achieve desired hardness levels.</p>',
            'External inline mixing valve for water hardness blending.',
            'mixing valve,inline,water softener'
        );

        // ── ADAPTER KITS ──────────────────────────────────────────────────────
        $this->variable('Adapter Kit Brass', $parts,
            '<p>Excalibur brass adapter kits for connecting water softeners and filters to plumbing. Available in 3/4" and 1" sizes.</p>',
            'Brass adapter kit for water softener and filter installation. 3/4" or 1".',
            'adapter kit,brass,installation',
            [
                ['label'=>'3/4 inch','sku'=>'CLK-V300703','price'=>45.00],
                ['label'=>'1 inch',  'sku'=>'CLK-V300702','price'=>55.00],
            ]
        );

        $this->variable('Adapter Kit PVC Plastic', $parts,
            '<p>Excalibur PVC plastic adapter kits for water softener and filter installation. Various connection types.</p>',
            'PVC plastic adapter kit for water softener installation.',
            'adapter kit,PVC,plastic,installation',
            [
                ['label'=>'3/4 and 1 inch Solvent Elbow','sku'=>'CLK-V300701','price'=>25.00],
                ['label'=>'1 inch Male Thread Elbow',     'sku'=>'CLK-V3007',  'price'=>25.00],
            ]
        );

        $this->variable('Adapter Kit Shark Bite', $parts,
            '<p>Excalibur Shark Bite push-fit adapter kits for quick installation of water softeners and filters.</p>',
            'Shark Bite push-fit adapter kit. 3/4" or 1".',
            'adapter kit,shark bite,push fit,installation',
            [
                ['label'=>'3/4 inch','sku'=>'CLK-V300712','price'=>77.50],
                ['label'=>'1 inch',  'sku'=>'CLK-V300713','price'=>100.00],
            ]
        );

        $this->variable('Adapter Kit John Guest Plastic', $parts,
            '<p>Excalibur John Guest plastic quick connect adapter kits for water softeners and filters.</p>',
            'John Guest plastic quick connect adapter kit. 3/4".',
            'adapter kit,John Guest,quick connect,installation',
            [
                ['label'=>'3/4 inch Elbow',   'sku'=>'CLK-V300715','price'=>82.50],
                ['label'=>'3/4 inch Straight', 'sku'=>'CLK-V300719','price'=>85.00],
                ['label'=>'1 inch JG Kit',     'sku'=>'CLK-V300717','price'=>95.00],
                ['label'=>'1 inch JG 90 Elbow','sku'=>'CLK-V300720','price'=>85.00],
            ]
        );

        // ── BRINE TANKS ───────────────────────────────────────────────────────
        $this->variable('Brine Tank Round Black Assembled', $parts,
            '<p>Excalibur round black brine tanks, assembled complete with brine well, safety float, two-piece overflow set and brine tubing.</p>',
            'Assembled round black brine tank with brine well, float and fittings.',
            'brine tank,salt tank,assembled,water softener',
            [
                ['label'=>'16x33 Complete','sku'=>'CLK-BT1633C','price'=>207.50],
                ['label'=>'18x33 Complete','sku'=>'CLK-BT1833C','price'=>240.00],
                ['label'=>'18x40 Complete','sku'=>'CLK-BT1840C','price'=>272.50],
            ]
        );

        $this->variable('Brine Tank Round Black Empty', $parts,
            '<p>Excalibur round black brine tanks, empty. For use with water softeners.</p>',
            'Empty round black brine tank for water softeners.',
            'brine tank,salt tank,empty',
            [
                ['label'=>'18x33 inch','sku'=>'CLK-BT1833', 'price'=>175.00],
                ['label'=>'18x40 inch','sku'=>'CLK-BT1840B','price'=>202.50],
            ]
        );

        $this->variable('Safety Float and Brinewell', $parts,
            '<p>Excalibur safety float and brinewell assembly for brine tanks. Prevents brine tank overfill.</p>',
            'Safety float and brinewell assembly for brine tanks.',
            'safety float,brinewell,brine tank',
            [
                ['label'=>'28 inch Brinewell','sku'=>'CLK-H470028','price'=>110.00],
                ['label'=>'36 inch Brinewell','sku'=>'CLK-H707836','price'=>120.00],
            ]
        );

        // ── RETENTION TANKS ───────────────────────────────────────────────────
        $this->variable('Retention Tank', $parts,
            '<p>Excalibur retention tanks for chemical injection contact time. Comes with 1" straight male thread adapter and 1-1/4" and 1-1/2" PVC solvent fittings.</p>',
            'Retention tank for chemical injection contact time. 40, 80 or 120 gallon.',
            'retention tank,chemical injection,chlorination',
            [
                ['label'=>'40 Gallon', 'sku'=>'TNK-C2550','price'=>930.00],
                ['label'=>'80 Gallon', 'sku'=>'TNK-C2251','price'=>1250.00],
                ['label'=>'120 Gallon','sku'=>'TNK-C2252','price'=>1565.00],
            ]
        );

        // ── CHEMICAL SOLUTION TANKS ───────────────────────────────────────────
        $this->variable('Chemical Solution Tank', $parts,
            '<p>Excalibur chemical solution tanks for chlorine, potassium permanganate or other chemical injection applications.</p>',
            'Chemical solution tank for injection systems. 15 or 35 gallon.',
            'chemical tank,solution,chlorination,injection',
            [
                ['label'=>'15 Gallon 14x24','sku'=>'CLK-CS1415G','price'=>105.00],
                ['label'=>'35 Gallon 18x32','sku'=>'CLK-CS1835G','price'=>242.50],
            ]
        );

        // ── CHEMICAL FEED PUMPS ───────────────────────────────────────────────
        $this->variable('Stenner Chemical Feed Pump 17GPD', $parts,
            '<p>Stenner chemical feed pump, 17 GPD capacity. For chlorine, hydrogen peroxide, potassium permanganate injection. Available in 110V or 230V.</p>',
            'Stenner 17 GPD chemical feed pump for chlorination and injection.',
            'chemical pump,Stenner,chlorination,injection',
            [
                ['label'=>'110V','sku'=>'PMP-85MPH17110','price'=>1547.50],
                ['label'=>'230V','sku'=>'PMP-85MPH17230','price'=>1547.50],
            ]
        );

        $this->simple('Flow Switch 115V for Chemical Feed', 'PMP-CF112N', 960.00, $parts,
            '<p>Flow switch 115V to activate chemical feed pump. Specify 3/4" or 1" threaded tee at time of order.</p>',
            'Flow switch 115V for activating chemical feed pumps.',
            'flow switch,chemical pump,115V'
        );

        // ── MINERAL TANK ACCESSORIES ──────────────────────────────────────────
        $this->variable('Mineral Tank Chrome Jacket', $mt,
            '<p>Chrome wrap jackets for Excalibur mineral tanks. Gives a premium look to your water treatment system. Protective peel-off cover included. Caps sold separately.</p>',
            'Chrome wrap jacket for mineral tanks. Multiple sizes.',
            'chrome jacket,mineral tank,wrap',
            [
                ['label'=>'9x48 inch', 'sku'=>'JW-0948C','price'=>130.00],
                ['label'=>'10x35 inch','sku'=>'JW-1035C','price'=>122.50],
                ['label'=>'10x44 inch','sku'=>'JW-1044C','price'=>130.00],
                ['label'=>'10x54 inch','sku'=>'JW-1054C','price'=>157.50],
                ['label'=>'12x52 inch','sku'=>'JW-1252C','price'=>252.50],
                ['label'=>'13x54 inch','sku'=>'JW-1354C','price'=>292.50],
            ]
        );

        $this->variable('Mineral Tank Black Cap', $mt,
            '<p>Black caps for Excalibur mineral tanks with chrome jackets. Order caps separately to complete the chrome jacket system.</p>',
            'Black mineral tank cap. 9", 10", 12" or 13".',
            'tank cap,black,mineral tank',
            [
                ['label'=>'9 inch', 'sku'=>'JW-C09','price'=>25.00],
                ['label'=>'10 inch','sku'=>'JW-C10','price'=>25.00],
                ['label'=>'12 inch','sku'=>'JW-C12','price'=>30.00],
                ['label'=>'13 inch','sku'=>'JW-C13','price'=>40.00],
            ]
        );

        $this->variable('Turbulator Cyclone Distributor Tube', $mt,
            '<p>Excalibur Turbulator Cyclone Distributor for mineral tanks. 1.05" diameter for superior regeneration efficiency. Requires 1.05" and 13/16" riser pipe.</p>',
            'Turbulator cyclone distributor tube, 1.05" diameter. Multiple lengths.',
            'turbulator,distributor,cyclone,mineral tank',
            [
                ['label'=>'35 inch','sku'=>'CLK-DT35','price'=>70.00],
                ['label'=>'44 inch','sku'=>'CLK-DT44','price'=>70.00],
                ['label'=>'48 inch','sku'=>'CLK-DT48','price'=>70.00],
                ['label'=>'52 inch','sku'=>'CLK-DT52','price'=>70.00],
                ['label'=>'54 inch','sku'=>'CLK-DT54','price'=>70.00],
            ]
        );

        $this->simple('Drain Line Flow Control 1.0 GPM', 'CLK-V3162010', 7.50, $parts,
            '<p>Drain Line Flow Control (DLFC) for water softener backwash drain. 1.0 GPM rating.</p>',
            'Drain line flow control (DLFC) for softener backwash. 1.0 GPM.',
            'DLFC,drain,flow control,softener'
        );

        $this->variable('Drain Line Flow Control DLFC', $parts,
            '<p>Excalibur Drain Line Flow Controls for water softener and filter backwash drain lines. Multiple GPM ratings available.</p>',
            'DLFC backwash drain flow control. Multiple GPM ratings.',
            'DLFC,drain,flow control,backwash',
            [
                ['label'=>'0.7 GPM','sku'=>'CLK-V3162007','price'=>7.50],
                ['label'=>'1.3 GPM','sku'=>'CLK-V3162013','price'=>7.50],
                ['label'=>'1.7 GPM','sku'=>'CLK-V3162017','price'=>7.50],
                ['label'=>'2.2 GPM','sku'=>'CLK-V3162022','price'=>7.50],
                ['label'=>'2.7 GPM','sku'=>'CLK-V3162027','price'=>7.50],
                ['label'=>'3.2 GPM','sku'=>'CLK-V3162032','price'=>7.50],
                ['label'=>'4.2 GPM','sku'=>'CLK-V3162042','price'=>7.50],
                ['label'=>'5.3 GPM','sku'=>'CLK-V3162053','price'=>7.50],
                ['label'=>'6.5 GPM','sku'=>'CLK-V3162065','price'=>7.50],
                ['label'=>'7.5 GPM','sku'=>'CLK-V3162075','price'=>7.50],
            ]
        );

        $this->simple('Res Up Resin Cleaner 1 Quart', 'CHM-T600104', 20.00, $parts,
            '<p>Excalibur Res Up Resin Cleaner Solution, 1 Quart. Cleans and maintains ion exchange resin in water softeners.</p>',
            'Res Up resin cleaner solution, 1 quart.',
            'resin cleaner,Res Up,water softener,maintenance'
        );

        $this->simple('Res Up Resin Cleaner 1 Gallon', 'CHM-T600204', 40.00, $parts,
            '<p>Excalibur Res Up Resin Cleaner Solution, 1 Gallon. Cleans and maintains ion exchange resin in water softeners.</p>',
            'Res Up resin cleaner solution, 1 gallon.',
            'resin cleaner,Res Up,water softener,maintenance'
        );

        $this->simple('Silicone NSF Food Grade Lubricant', 'CHM-3005', 92.50, $parts,
            '<p>Silicone NSF food grade lubricant 5.3 oz tube. For O-rings, seals and valves in water treatment equipment.</p>',
            'NSF food grade silicone lubricant, 5.3 oz tube.',
            'silicone,lubricant,NSF,O-ring,maintenance'
        );

        $this->simple('Air Gap 1.5 Inch Drain Softener or Filter Backwash', '1.5-AIRGAP-SOFT', 50.00, $parts,
            '<p>Air gap for 1.5" drain line for water softener or filter backwash connections. Required for code compliance in many jurisdictions.</p>',
            'Air gap 1.5" drain connection for softener or filter backwash.',
            'air gap,drain,softener,filter,installation'
        );

        $this->simple('Wrench Service Valve Excalibur', 'CLK-V3193', 27.50, $parts,
            '<p>Excalibur Water Systems service valve wrench for control valve maintenance.</p>',
            'Service valve wrench for Excalibur control valves.',
            'wrench,service valve,tool'
        );

        $this->simple('WS1 Stack Puller Tool', 'CLK-V3022', 40.00, $parts,
            '<p>WS1 Stack Puller tool for Excalibur control valve stack removal during servicing.</p>',
            'WS1 stack puller tool for control valve servicing.',
            'stack puller,tool,control valve,service'
        );

        // ── MEDIA & RESINS ────────────────────────────────────────────────────
        $this->simple('Media Gravel Flint Support Bed', 'MED-A8072', 50.00, $media,
            '<p>Excalibur Gravel Flint #20 support media. 1.0 ft³ bag (100 lbs). Used as support bed beneath filter media to prevent migration and improve flow distribution.</p>',
            'Gravel flint #20 support media. 1.0 ft³ bag, 100 lbs.',
            'gravel,support media,filter,media'
        );

        $this->simple('Greensand Plus Iron Oxidation Media', 'MED-A804201', 252.50, $media,
            '<p>Greensand Plus iron oxidation media. 0.5 ft³ bag (40 lbs). For iron, manganese and hydrogen sulfide removal in backwashing iron filters.</p>',
            'Greensand Plus iron/manganese/H2S removal media. 0.5 ft³ bag.',
            'greensand,iron removal,manganese,media'
        );

        $this->simple('Filter Ox Iron Oxidation Media', 'MED-A8045', 252.50, $media,
            '<p>Filter Ox iron oxidation media. 0.5 ft³ bag (40 lbs). For iron, manganese and hydrogen sulfide removal.</p>',
            'Filter Ox iron/manganese oxidation media. 0.5 ft³ bag.',
            'filter ox,iron removal,manganese,media'
        );

        $this->simple('Media Coconut Shell Activated Carbon', 'MED-A8060', 250.00, $media,
            '<p>Coconut shell activated carbon media. 1.0 ft³ bag (30 lbs). For chlorine, taste, odour and organic compound removal in backwashing carbon filters.</p>',
            'Coconut shell activated carbon media. 1.0 ft³ bag, 30 lbs.',
            'coconut carbon,GAC,media,filter'
        );

        $this->simple('Media Coconut Shell Catalytic Carbon CS', 'MED-A1240', 515.00, $media,
            '<p>Coconut Shell Catalytic Carbon (CS) media. 1.0 ft³ bag (30 lbs). Enhanced chloramine, hydrogen sulfide and organic removal.</p>',
            'Coconut shell catalytic carbon media. 1.0 ft³ bag, 30 lbs.',
            'catalytic carbon,coconut,chloramine,media'
        );

        $this->simple('Media Centaur Catalytic Carbon', 'MED-A8056', 677.50, $media,
            '<p>Centaur catalytic activated carbon media. 1.0 ft³ bag (33 lbs). Premium catalytic carbon for chloramine, hydrogen sulfide and iron removal.</p>',
            'Centaur catalytic carbon media. 1.0 ft³ bag, 33 lbs.',
            'centaur,catalytic carbon,chloramine,media'
        );

        $this->simple('Media Calcite pH Correction', 'MED-A802101', 90.00, $media,
            '<p>Calcite pH correction media. 0.55 ft³ bag (50 lbs). Self-sacrificing neutralizing media for raising low pH acidic water.</p>',
            'Calcite pH correction neutralizing media. 0.55 ft³ bag.',
            'calcite,pH,neutralizer,media'
        );

        $this->simple('Media Corosex pH Correction', 'MED-A8011', 205.00, $media,
            '<p>Corosex pH correction media. 0.66 ft³ bag (50 lbs). Magnesium oxide media for aggressive low pH water correction.</p>',
            'Corosex pH correction media. 0.66 ft³ bag.',
            'corosex,pH,neutralizer,media'
        );

        $this->simple('Media Turbidity Natural Zeolite', 'MED-A8023', 140.00, $media,
            '<p>Natural zeolite turbidity filtration media. 1.0 ft³ bag (50 lbs). Clinoptilolite media for suspended matter and turbidity reduction.</p>',
            'Natural zeolite turbidity media. 1.0 ft³ bag, 50 lbs.',
            'turbidity,zeolite,media,sediment'
        );

        $this->variable('Cation Exchange Softening Resin', $media,
            '<p>Excalibur cation exchange softening resin. High capacity ion exchange resin for water softeners. Available in standard or fine mesh.</p>',
            'Cation exchange softening resin for water softeners. Standard or fine mesh. 1.0 ft³ bag.',
            'resin,cation,softener,ion exchange',
            [
                ['label'=>'Standard Mesh 40 lbs','sku'=>'MED-A1213','price'=>322.50],
                ['label'=>'Fine Mesh 45 lbs',    'sku'=>'MED-A800', 'price'=>365.00],
            ]
        );

        $this->simple('Resin Anion Tannin Removal', 'MED-A72MP', 975.00, $media,
            '<p>Anion exchange tannin removal resin. 1.0 ft³ bag (40 lbs). For use in tannin filter systems to remove tannins causing yellow water discolouration.</p>',
            'Anion tannin removal resin. 1.0 ft³ bag, 40 lbs.',
            'tannin,resin,anion,ion exchange,media'
        );

        $this->simple('Infinity Exchange Resin', 'MED-OSM', 1030.00, $media,
            '<p>Infinity Exchange Resin. 1.0 ft³ bag (40 lbs). For use in Zentec Infinity Hybrid Filtration Systems. Minimum 2 ft³ per vessel required.</p>',
            'Infinity exchange resin for Zentec Infinity systems. 1.0 ft³ bag.',
            'infinity,resin,Zentec,media'
        );

        // ── TEST KITS ─────────────────────────────────────────────────────────
        $this->simple('AR-42 Test Kit Hardness Iron pH Sulphur', 'CHM-359003', 1110.00, $parts,
            '<p>AR-42 Professional Water Test Kit. Tests for hardness, iron, pH and sulphur. Complete with all reagents and Octa-Slide color comparator.</p>',
            'AR-42 water test kit for hardness, iron, pH and sulphur.',
            'test kit,water test,hardness,iron,pH,sulphur'
        );

        $this->simple('AR-42 Test Kit Hardness Iron pH Sulphur Tannins', 'CHM-XX01273', 2350.00, $parts,
            '<p>AR-42 Professional Water Test Kit. Tests for hardness, iron, pH, sulphur and tannins. Complete with all reagents.</p>',
            'AR-42 water test kit for hardness, iron, pH, sulphur and tannins.',
            'test kit,water test,hardness,tannin'
        );

        $this->simple('Tannin Test Kit TL', 'CHM-7831', 562.50, $parts,
            '<p>Tannin Test Kit TL. Tests for tannin levels in water. Used to determine tannin filter sizing and performance.</p>',
            'Tannin water test kit TL.',
            'test kit,tannin,water test'
        );

        $this->simple('Municipal Hardness and Chlorine Test Kit', 'CHM-XX01533', 247.50, $parts,
            '<p>Combination test kit for municipal water. Tests hardness and chlorine levels.</p>',
            'Municipal water test kit for hardness and chlorine.',
            'test kit,municipal,hardness,chlorine'
        );

        $this->simple('TDS Meter Pen Type', 'KIT-TDS-METER', 85.00, $parts,
            '<p>TDS Meter pen type. Measures total dissolved solids. Range 0-9990 ppm with 1 ppm resolution up to 999 ppm.</p>',
            'TDS pen meter, 0-9990 ppm range.',
            'TDS meter,total dissolved solids,water quality'
        );

        $this->variable('Spin Touch Disk Reagent', $parts,
            '<p>Excalibur Spin Touch Disk Reagents for use with the Water Link Spin Touch water testing system.</p>',
            'Spin Touch disk reagents for Water Link Spin Touch. 50 discs per pack.',
            'Spin Touch,reagent,water test,Water Link',
            [
                ['label'=>'Well Water Discs 50 pack',    'sku'=>'CHM-4337H','price'=>570.00],
                ['label'=>'Treated Water Discs 50 pack', 'sku'=>'CHM-4336H','price'=>570.00],
            ]
        );

        $this->simple('Water Link Spin Touch Analyzer', 'CHM-3585', 5250.00, $parts,
            '<p>Water Link Spin Touch photometric water analyzer. Professional water testing device for accurate multi-parameter analysis.</p>',
            'Water Link Spin Touch professional water analyzer.',
            'Spin Touch,Water Link,analyzer,water test'
        );

        $this->command->info('ExcaliburPartsSeeder complete.');
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function cat(string $name, int $parentId = 0): int
    {
        $existing = Category::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return $existing->id;
        }
        return DB::table('categories')->insertGetId([
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . rand(100, 999),
            'parent_id'   => $parentId,
            'level'       => $parentId > 0 ? 1 : 0,
            'order_level' => 0,
            'digital'     => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function variable(string $name, int $catId, string $desc, string $shortDesc, string $tags, array $variants): void
    {
        if (Product::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            $this->command->warn("Skip (exists): {$name}");
            return;
        }

        $optionValues  = array_column($variants, 'label');
        $choiceOptions = json_encode([['attribute_id' => 1, 'values' => $optionValues]], JSON_UNESCAPED_UNICODE);
        $slug = Str::slug($name);
        if (Product::where('slug', 'LIKE', $slug . '%')->count()) {
            $slug .= '-' . rand(100, 999);
        }
        $minPrice = min(array_column($variants, 'price'));

        $product = Product::create([
            'name'                   => $name,
            'added_by'               => 'admin',
            'user_id'                => $this->adminId,
            'category_id'            => $catId,
            'brand_id'               => null,
            'photos'                 => '',
            'thumbnail_img'          => '',
            'tags'                   => $tags,
            'description'            => $desc,
            'short_description'      => $shortDesc,
            'unit_price'             => $minPrice,
            'purchase_price'         => 0,
            'variant_product'        => 1,
            'attributes'             => json_encode([1]),
            'choice_options'         => $choiceOptions,
            'colors'                 => json_encode([]),
            'variations'             => json_encode([]),
            'todays_deal'            => 0,
            'published'              => 1,
            'approved'               => 1,
            'stock_visibility_state' => 'quantity',
            'cash_on_delivery'       => 1,
            'featured'               => 0,
            'seller_featured'        => 0,
            'current_stock'          => count($variants) * 10,
            'unit'                   => 'pcs',
            'weight'                 => 0,
            'min_qty'                => 1,
            'low_stock_quantity'     => 1,
            'discount'               => 0,
            'discount_type'          => 'percent',
            'shipping_type'          => 'free',
            'shipping_cost'          => 0,
            'is_quantity_multiplied' => 0,
            'num_of_sale'            => 0,
            'meta_title'             => $name,
            'meta_description'       => $shortDesc,
            'meta_img'               => '',
            'slug'                   => $slug,
            'barcode'                => $variants[0]['sku'],
            'digital'                => 0,
            'auction_product'        => 0,
            'wholesale_product'      => 0,
            'rating'                 => 0,
            'refundable'             => 1,
        ]);

        foreach ($variants as $v) {
            ProductStock::create([
                'product_id' => $product->id,
                'variant'    => str_replace(' ', '', $v['label']),
                'price'      => $v['price'],
                'sku'        => $v['sku'],
                'qty'        => $v['qty'] ?? 10,
                'image'      => null,
            ]);
        }

        DB::table('product_categories')->insertOrIgnore([
            'product_id'  => $product->id,
            'category_id' => $catId,
        ]);

        ProductTranslation::create([
            'lang'              => $this->lang,
            'product_id'        => $product->id,
            'name'              => $name,
            'unit'              => 'pcs',
            'description'       => $desc,
            'short_description' => $shortDesc,
        ]);

        $this->command->info("Created variable product: {$name} (ID: {$product->id})");
    }

    private function simple(string $name, string $sku, float $price, int $catId, string $desc, string $shortDesc, string $tags): void
    {
        if (Product::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            $this->command->warn("Skip (exists): {$name}");
            return;
        }

        $slug = Str::slug($name);
        if (Product::where('slug', 'LIKE', $slug . '%')->count()) {
            $slug .= '-' . rand(100, 999);
        }

        $product = Product::create([
            'name'                   => $name,
            'added_by'               => 'admin',
            'user_id'                => $this->adminId,
            'category_id'            => $catId,
            'brand_id'               => null,
            'photos'                 => '',
            'thumbnail_img'          => '',
            'tags'                   => $tags,
            'description'            => $desc,
            'short_description'      => $shortDesc,
            'unit_price'             => $price,
            'purchase_price'         => 0,
            'variant_product'        => 0,
            'attributes'             => json_encode([]),
            'choice_options'         => json_encode([]),
            'colors'                 => json_encode([]),
            'variations'             => json_encode([]),
            'todays_deal'            => 0,
            'published'              => 1,
            'approved'               => 1,
            'stock_visibility_state' => 'quantity',
            'cash_on_delivery'       => 1,
            'featured'               => 0,
            'seller_featured'        => 0,
            'current_stock'          => 10,
            'unit'                   => 'pcs',
            'weight'                 => 0,
            'min_qty'                => 1,
            'low_stock_quantity'     => 1,
            'discount'               => 0,
            'discount_type'          => 'percent',
            'shipping_type'          => 'free',
            'shipping_cost'          => 0,
            'is_quantity_multiplied' => 0,
            'num_of_sale'            => 0,
            'meta_title'             => $name,
            'meta_description'       => $shortDesc,
            'meta_img'               => '',
            'slug'                   => $slug,
            'barcode'                => $sku,
            'digital'                => 0,
            'auction_product'        => 0,
            'wholesale_product'      => 0,
            'rating'                 => 0,
            'refundable'             => 1,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'variant'    => '',
            'price'      => $price,
            'sku'        => $sku,
            'qty'        => 10,
            'image'      => null,
        ]);

        DB::table('product_categories')->insertOrIgnore([
            'product_id'  => $product->id,
            'category_id' => $catId,
        ]);

        ProductTranslation::create([
            'lang'              => $this->lang,
            'product_id'        => $product->id,
            'name'              => $name,
            'unit'              => 'pcs',
            'description'       => $desc,
            'short_description' => $shortDesc,
        ]);

        $this->command->info("Created simple product: {$name} (ID: {$product->id})");
    }
}
