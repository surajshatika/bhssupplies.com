<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExcaliburSystemsSeeder extends Seeder
{
    private int $adminId;
    private string $lang;

    public function run(): void
    {
        $admin = User::where('user_type', 'admin')->first();
        $this->adminId = $admin->id ?? 1;
        $this->lang = env('DEFAULT_LANGUAGE', 'en');

        $ws  = $this->cat('WATER SOFTENERS');
        $st  = $this->cat('SOFT-TEC SCALE CONTROL');
        $dp  = $this->cat('DROP PRODUCTS');
        $fl  = $this->cat('FILTERS');
        $ir  = $this->cat('IRON & SULPHUR FILTERS');
        $tn  = $this->cat('TANNIN FILTERS');
        $ne  = $this->cat('NEUTRALIZING FILTERS');
        $tu  = $this->cat('TURBIDITY FILTERS');
        $sp  = $this->cat('SPECIALTY FILTERS');
        $uv  = $this->cat('ULTRAVIOLET SYSTEMS');
        $ro  = $this->cat('REVERSE OSMOSIS');
        $mt  = $this->cat('MINERAL TANKS');

        // ── WATER SOFTENERS ──────────────────────────────────────────────────
        $this->variable('Value Series Water Softener', $ws,
            'EWS water softener, 5 button metered demand, black mineral tank, brine tank included.',
            'Excalibur Value Series whole home water softener. Available in 30000 to 90000 grain capacity.',
            'water softener,value,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-S30BF', 'price'=>1712.50],
                ['label'=>'45000 Grain','sku'=>'EWS-S45BF', 'price'=>1992.50],
                ['label'=>'60000 Grain','sku'=>'EWS-S60BF', 'price'=>2447.50],
                ['label'=>'75000 Grain','sku'=>'EWS-S75BF', 'price'=>2687.50],
                ['label'=>'90000 Grain','sku'=>'EWS-S90BF', 'price'=>3625.00],
            ]
        );

        $this->variable('Superior Series Water Softener', $ws,
            'EWS Superior Series water softener, 5 button metered demand, black mineral tank, brine tank included. 7-year unlimited warranty.',
            'Excalibur Superior Series whole home water softener. 30000 to 60000 grain capacity.',
            'water softener,superior,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SB30BF', 'price'=>1975.00],
                ['label'=>'45000 Grain','sku'=>'EWS-SB45BF', 'price'=>2055.00],
                ['label'=>'60000 Grain','sku'=>'EWS-SB60BF', 'price'=>2525.00],
            ]
        );

        $this->variable('Superior Turbulator Series Water Softener', $ws,
            'EWS Superior Turbulator Series water softener with cyclone distributor for superior regeneration efficiency. 7-year unlimited warranty.',
            'Excalibur Superior Turbulator water softener. 30000 to 60000 grain capacity.',
            'water softener,superior,turbulator,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SB30BFT', 'price'=>2035.00],
                ['label'=>'45000 Grain','sku'=>'EWS-SB45BFT', 'price'=>2127.50],
                ['label'=>'60000 Grain','sku'=>'EWS-SB60BFT', 'price'=>2565.00],
            ]
        );

        $this->variable('Premium Series Water Softener', $ws,
            'EWS Premium Series water softener with chrome jacket, 5 button metered demand. Bypass valve and adapter kit included. 10-year unlimited warranty.',
            'Excalibur Premium Series water softener with chrome jacket. 30000 to 60000 grain capacity.',
            'water softener,premium,chrome,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SP30BFJCT', 'price'=>2215.00],
                ['label'=>'45000 Grain','sku'=>'EWS-SP45BFJCT', 'price'=>2370.00],
                ['label'=>'60000 Grain','sku'=>'EWS-SP60BFJCT', 'price'=>2775.00],
            ]
        );

        $this->variable('Chlor-A-Soft Water Softener', $ws,
            'EWS Chlor-A-Soft Series water softener designed for municipal chlorinated water. Protects resin from chlorine degradation. 10-year unlimited warranty.',
            'Excalibur Chlor-A-Soft water softener for chlorinated water. 30000 to 60000 grain.',
            'water softener,chloramine,chlorine,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SPREF30', 'price'=>2215.00],
                ['label'=>'45000 Grain','sku'=>'EWS-SPREF45', 'price'=>2370.00],
                ['label'=>'60000 Grain','sku'=>'EWS-SPREF60', 'price'=>2860.00],
            ]
        );

        $this->variable('Chlor-A-Soft US Series Water Softener', $ws,
            'EWS Chlor-A-Soft US Series water softener for chlorinated and chloraminated municipal water. Premium chrome jacket. 10-year unlimited warranty.',
            'Excalibur Chlor-A-Soft US Series for chlorinated/chloraminated water. 30000 to 60000 grain.',
            'water softener,chloramine,US,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SPREF30US', 'price'=>2262.50],
                ['label'=>'45000 Grain','sku'=>'EWS-SPREF45US', 'price'=>2615.00],
                ['label'=>'60000 Grain','sku'=>'EWS-SPREF60US', 'price'=>2902.50],
            ]
        );

        $this->variable('Ultimate Superior Water Softener', $ws,
            'EWS Ultimate Superior Series high-capacity water softener with twin-tank design for continuous soft water. 12-year unlimited warranty.',
            'Excalibur Ultimate Superior water softener. 30000 or 50000 grain capacity.',
            'water softener,ultimate,superior,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SUS30BF', 'price'=>2340.00],
                ['label'=>'50000 Grain','sku'=>'EWS-SUS50BF', 'price'=>2902.50],
            ]
        );

        $this->variable('Ultimate Premium Water Softener', $ws,
            'EWS Ultimate Premium Series high-capacity water softener with chrome jacket and twin-tank design. 20-year unlimited warranty.',
            'Excalibur Ultimate Premium water softener. 30000 or 50000 grain capacity.',
            'water softener,ultimate,premium,chrome,EWS',
            [
                ['label'=>'30000 Grain','sku'=>'EWS-SU30BF', 'price'=>2592.50],
                ['label'=>'50000 Grain','sku'=>'EWS-SU50BF', 'price'=>3160.00],
            ]
        );

        $this->simple('Cabinet Value Series Water Softener 30000 Grain', 'EWS-S30BFC', 1712.50, $ws,
            '<p>Excalibur Cabinet Value Series water softener in an all-in-one cabinet design. 30,000 grain capacity.</p>',
            'Excalibur Cabinet Value Series water softener, 30000 grain, all-in-one design.',
            'water softener,cabinet,value,EWS'
        );

        $this->simple('Cabinet Superior Series Water Softener 30000 Grain', 'EWS-SB30BFC', 1975.00, $ws,
            '<p>Excalibur Cabinet Superior Series water softener in all-in-one cabinet design. 30,000 grain capacity. 7-year unlimited warranty.</p>',
            'Excalibur Cabinet Superior Series water softener, 30000 grain.',
            'water softener,cabinet,superior,EWS'
        );

        $this->simple('Cabinet Premium Series Water Softener 30000 Grain', 'EWS-SP30BFC', 2215.00, $ws,
            '<p>Excalibur Cabinet Premium Series water softener with chrome finish, all-in-one cabinet design. 30,000 grain capacity. 10-year unlimited warranty.</p>',
            'Excalibur Cabinet Premium Series water softener, 30000 grain, chrome finish.',
            'water softener,cabinet,premium,EWS'
        );

        $this->simple('Chlor-A-Soft Cabinet Water Softener 22000 Grain', 'EWS-SPREF22C', 2215.00, $ws,
            '<p>Excalibur Chlor-A-Soft Cabinet Series water softener for chlorinated municipal water. 22,000 grain capacity in a compact cabinet design.</p>',
            'Excalibur Chlor-A-Soft cabinet water softener, 22000 grain, for chlorinated water.',
            'water softener,cabinet,chlor-a-soft,EWS'
        );

        // ── SOFT-TEC ──────────────────────────────────────────────────────────
        $this->simple('Soft-Tec Superior Scale Control System', 'EWS-POUBCS075', 785.00, $st,
            '<p>Excalibur Soft-Tec Superior Scale Control System. Salt-free water conditioning technology that prevents scale buildup without removing beneficial minerals.</p>',
            'Soft-Tec Superior salt-free scale control system.',
            'soft-tec,scale control,salt-free,EWS'
        );

        $this->simple('Soft-Tec Premium Scale Control System', 'EWS-POUPCS075', 997.50, $st,
            '<p>Excalibur Soft-Tec Premium Scale Control System. Chrome jacket design. Salt-free water conditioning technology.</p>',
            'Soft-Tec Premium salt-free scale control system with chrome jacket.',
            'soft-tec,scale control,salt-free,premium,EWS'
        );

        $this->simple('Soft-Tec Superior No-Salt Water Softener', 'EWS-NSB0844', 1645.00, $st,
            '<p>Excalibur Soft-Tec Superior No-Salt Water Softener. Combines scale prevention technology with backwashable filter media.</p>',
            'Soft-Tec Superior no-salt water softener system.',
            'soft-tec,no-salt,superior,EWS'
        );

        $this->simple('Soft-Tec Premium No-Salt Water Softener', 'EWS-NSP0844', 1895.00, $st,
            '<p>Excalibur Soft-Tec Premium No-Salt Water Softener with chrome jacket. Combines scale prevention with backwashable filtration.</p>',
            'Soft-Tec Premium no-salt water softener with chrome jacket.',
            'soft-tec,no-salt,premium,EWS'
        );

        $this->simple('Soft-Tec Dual Home No-Salt System', 'EWS-DH20NS', 825.00, $st,
            '<p>Excalibur Soft-Tec Dual Home No-Salt System. Salt-free scale control for the whole home.</p>',
            'Soft-Tec dual home no-salt scale control system.',
            'soft-tec,no-salt,dual,EWS'
        );

        // ── DROP PRODUCTS ─────────────────────────────────────────────────────
        $this->variable('DROP Smart Chlor-A-Soft Water Softener', $dp,
            'DROP Smart Water Softener with Chlor-A-Soft technology. App-controlled, WiFi connected, leak detection included.',
            'DROP Smart Chlor-A-Soft WiFi water softener. 30000 or 45000 grain.',
            'DROP,smart,wifi,water softener',
            [
                ['label'=>'30000 Grain','sku'=>'EWDS-SPREF30', 'price'=>4115.00],
                ['label'=>'45000 Grain','sku'=>'EWDS-SPREF45', 'price'=>4300.00],
            ]
        );

        $this->variable('DROP Smart Premium Water Softener', $dp,
            'DROP Smart Premium Water Softener. App-controlled, WiFi connected with leak detection. Chrome jacket.',
            'DROP Smart Premium WiFi water softener. 30000 or 45000 grain.',
            'DROP,smart,wifi,water softener,premium',
            [
                ['label'=>'30000 Grain','sku'=>'EWDS-SP30BFJCT', 'price'=>4115.00],
                ['label'=>'45000 Grain','sku'=>'EWDS-SP45BFJCT', 'price'=>4300.00],
            ]
        );

        $this->simple('ClearWave Electronic Water Conditioner', 'CWS-HPSK', 3455.00, $dp,
            '<p>ClearWave Electronic Water Conditioner. Uses electronic frequency waves to prevent and remove scale buildup without salt or chemicals.</p>',
            'ClearWave electronic water conditioner, no salt required.',
            'clearwave,electronic,scale control'
        );

        $this->simple('ClearWave 2 Electronic Water Conditioner', 'CWS-HPSK2', 3330.00, $dp,
            '<p>ClearWave 2 Electronic Water Conditioner. Updated model with enhanced scale prevention technology.</p>',
            'ClearWave 2 electronic water conditioner.',
            'clearwave,electronic,scale control'
        );

        $this->simple('DROP Hub Smart Water Controller', 'CS-DHUB', 675.00, $dp,
            '<p>DROP Hub is the central controller for the DROP Smart Water Protection System. Monitors and controls all DROP devices.</p>',
            'DROP Hub smart water system controller.',
            'DROP,hub,smart water'
        );

        $this->simple('DROP Smart Strip Flow Sensor', 'CS-DSS', 428.00, $dp,
            '<p>DROP Smart Strip flow sensor for the DROP Smart Water Protection System. Detects water flow and leaks.</p>',
            'DROP Smart Strip flow sensor for leak detection.',
            'DROP,sensor,leak detection'
        );

        $this->simple('DROP Home Protection System', 'CS-DHPS', 2125.00, $dp,
            '<p>DROP Home Protection System. Complete whole-home water monitoring and automatic shutoff system.</p>',
            'DROP complete home water protection system with auto shutoff.',
            'DROP,home protection,leak detection'
        );

        $this->simple('DROP Leak Sensor', 'CS-LS2', 445.00, $dp,
            '<p>DROP Leak Sensor for the DROP Smart Water System. Detects water presence and alerts via app.</p>',
            'DROP wireless leak sensor.',
            'DROP,leak sensor,water detection'
        );

        $this->simple('DROP Smart Shut Off Kit', 'CS-DSSK', 1100.00, $dp,
            '<p>DROP Smart Shut Off Kit. Automatically shuts off water supply when a leak is detected in your DROP system.</p>',
            'DROP automatic water shutoff valve kit.',
            'DROP,shutoff,valve,leak protection'
        );

        $this->simple('DROP Power Supply Adapter', 'CS-20017X222', 141.00, $dp,
            '<p>DROP Power Supply Adapter for DROP Smart Water System components.</p>',
            'DROP system power supply adapter.',
            'DROP,power supply,accessory'
        );

        // ── CARBON FILTERS ────────────────────────────────────────────────────
        $this->variable('Filtermax Superior Chemical Removal Filter', $fl,
            '<p>Excalibur Filtermax Superior Chemical Removal Filter. Removes chlorine, chloramines, taste, odour and chemical contaminants. Black mineral tank. Bypass valve and adapter kit included.</p>',
            'Filtermax Superior whole home chemical removal filter. 0.75 to 2.0 ft3.',
            'filter,carbon,chlorine,superior,EWS',
            [
                ['label'=>'0.75 ft3','sku'=>'EWS-POUBCS075','price'=>785.00],
                ['label'=>'1.0 ft3', 'sku'=>'EWS-POUBCS1',  'price'=>812.50],
                ['label'=>'1.5 ft3', 'sku'=>'EWS-POUBCS15', 'price'=>985.00],
                ['label'=>'2.0 ft3', 'sku'=>'EWS-POUBCS2',  'price'=>1277.50],
            ]
        );

        $this->variable('Filtermax Premium Chemical Removal Filter', $fl,
            '<p>Excalibur Filtermax Premium Chemical Removal Filter with chrome jacket. Superior chemical removal performance. Bypass valve and adapter kit included.</p>',
            'Filtermax Premium whole home chemical removal filter with chrome jacket. 0.75 to 2.0 ft3.',
            'filter,carbon,chlorine,premium,chrome,EWS',
            [
                ['label'=>'0.75 ft3','sku'=>'EWS-POUPCS075','price'=>997.50],
                ['label'=>'1.0 ft3', 'sku'=>'EWS-POUPCS1',  'price'=>1050.00],
                ['label'=>'1.5 ft3', 'sku'=>'EWS-POUPCS15', 'price'=>1145.00],
                ['label'=>'2.0 ft3', 'sku'=>'EWS-POUPCS2',  'price'=>1430.00],
            ]
        );

        $this->variable('Value Coconut Shell Carbon Backwash Filter', $fl,
            '<p>Excalibur Value Series Coconut Shell Carbon Backwashing Filter. Reduces chlorine, taste, odour and organic contaminants. Black mineral tank with electronic timer. 5-year unlimited warranty.</p>',
            'Excalibur Value coconut shell carbon backwash filter. 1.0 to 2.0 ft3.',
            'filter,coconut shell,carbon,backwash,value,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTCS1', 'price'=>1572.50],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTCS15','price'=>1727.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTCS2', 'price'=>2080.00],
            ]
        );

        $this->variable('Value Catalytic Carbon Backwash Filter', $fl,
            '<p>Excalibur Value Series Catalytic Carbon Backwashing Filter. Enhanced removal of chloramines, hydrogen sulfide, and organic compounds. Black mineral tank with electronic timer. 5-year unlimited warranty.</p>',
            'Excalibur Value catalytic carbon backwash filter for chloramines. 1.0 to 2.0 ft3.',
            'filter,catalytic carbon,chloramine,backwash,value,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTCE1', 'price'=>1870.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTCE15','price'=>2412.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTCE2', 'price'=>2897.50],
            ]
        );

        $this->variable('Superior Coconut Shell Carbon Backwash Filter', $fl,
            '<p>Excalibur Superior Series Coconut Shell Carbon Backwashing Filter. Black mineral tank, electronic timer. Bypass valve and adapter kit included. 7-year unlimited warranty.</p>',
            'Excalibur Superior coconut shell carbon backwash filter. 1.0 to 2.0 ft3.',
            'filter,coconut shell,carbon,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTSCS1', 'price'=>1650.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTSCS15','price'=>1807.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTSCS2', 'price'=>2167.50],
            ]
        );

        $this->variable('Superior Centaur Catalytic Carbon Backwash Filter', $fl,
            '<p>Excalibur Superior Series Centaur Catalytic Carbon Backwashing Filter. Enhanced removal of chloramines, hydrogen sulfide. Black mineral tank. 7-year unlimited warranty.</p>',
            'Excalibur Superior Centaur catalytic carbon filter for chloramines. 1.0 to 2.0 ft3.',
            'filter,centaur,catalytic carbon,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTSCE1', 'price'=>2025.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTSCE15','price'=>2570.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTSCE2', 'price'=>3115.00],
            ]
        );

        $this->variable('Premium Coconut Shell Carbon Backwash Filter', $fl,
            '<p>Excalibur Premium Series Coconut Shell Carbon Backwashing Filter. Chrome jacket, black cap. Bypass valve and adapter kit included. 10-year unlimited warranty.</p>',
            'Excalibur Premium coconut shell carbon backwash filter with chrome jacket. 1.0 to 2.0 ft3.',
            'filter,coconut shell,carbon,premium,chrome,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPCS1', 'price'=>1720.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPCS15','price'=>1905.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPCS2', 'price'=>2360.00],
            ]
        );

        $this->variable('Premium Catalytic Carbon Backwash Filter', $fl,
            '<p>Excalibur Premium Series Catalytic Carbon Backwashing Filter. Chrome jacket, black cap. Advanced chloramine and contaminant removal. 10-year unlimited warranty.</p>',
            'Excalibur Premium catalytic carbon filter with chrome jacket. 1.0 to 2.0 ft3.',
            'filter,catalytic carbon,premium,chrome,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPCE1', 'price'=>2182.50],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPCE15','price'=>2727.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPCE2', 'price'=>3582.50],
            ]
        );

        $this->variable('Premium Whole Home Carbon and Heavy Metals Filter', $fl,
            '<p>Excalibur Premium Whole Home Carbon and Heavy Metals Backwashing Filter. Removes lead, heavy metals, chlorine, and organic compounds. Chrome jacket. 10-year unlimited warranty.</p>',
            'Excalibur Premium whole home carbon and heavy metals filter. 1.0 or 2.0 ft3.',
            'filter,carbon,heavy metals,lead,premium,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPLDCS1','price'=>2377.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPLDCS2','price'=>3150.00],
            ]
        );

        // ── ZENTEC OZONE FILTERS ──────────────────────────────────────────────
        $this->variable('Zentec Superior Ozone Capsulate Filter', $fl,
            '<p>Excalibur Zentec Superior Ozone Capsulate Filter. Uses ozone technology to eliminate iron, sulphur, manganese, and organic compounds. Electronic timer. 12-year unlimited warranty.</p>',
            'Zentec Superior ozone capsulate filter for iron, sulphur and manganese. 1.0 to 2.0 ft3.',
            'zentec,ozone,iron,sulphur,manganese,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTSZC1EO', 'price'=>3597.50],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTSZC15EO','price'=>3957.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTSZC2EO', 'price'=>4217.50],
            ]
        );

        $this->variable('Zentec Superior Ozone High-Capacity Filter', $fl,
            '<p>Excalibur Zentec Superior Ozone High-Capacity Filter. Enhanced ozone treatment for high iron and sulphur levels. 12-year unlimited warranty.</p>',
            'Zentec Superior ozone high-capacity filter. 1.0 to 2.0 ft3.',
            'zentec,ozone,iron,sulphur,high capacity,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTSZC1EOP', 'price'=>4075.90],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTSZC15EOP','price'=>4511.28],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTSZC2EOP', 'price'=>5340.45],
            ]
        );

        $this->variable('Zentec Premium Ozone High-Capacity Filter', $fl,
            '<p>Excalibur Zentec Premium Ozone High-Capacity Filter with chrome jacket. Maximum ozone treatment for high contaminant levels. 20-year unlimited warranty.</p>',
            'Zentec Premium ozone high-capacity filter with chrome jacket. 1.0 to 2.0 ft3.',
            'zentec,ozone,iron,sulphur,premium,chrome,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPZC1EOP', 'price'=>4257.90],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPZC15EOP','price'=>4863.85],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPZC2EOP', 'price'=>5976.25],
            ]
        );

        $this->simple('Zentec Ozone Generator CD207', 'OZT-CD207', 1500.00, $fl,
            '<p>Zentec Ozone Generator CD207. Generates ozone for use with Zentec ozone filtration systems.</p>',
            'Zentec ozone generator CD207 for ozone filtration systems.',
            'zentec,ozone,generator'
        );

        $this->simple('Zentec Ozone Generator CD208', 'OZT-CD208', 2250.00, $fl,
            '<p>Zentec Ozone Generator CD208. High-output ozone generator for use with Zentec ozone filtration systems.</p>',
            'Zentec ozone generator CD208 high output.',
            'zentec,ozone,generator'
        );

        $this->simple('Zentec Ozone Replacement Diffuser 33218R', 'OZT-33218R', 244.00, $fl,
            '<p>Zentec Ozone Diffuser replacement part 33218R for Zentec ozone filtration systems.</p>',
            'Zentec ozone diffuser replacement part.',
            'zentec,ozone,replacement,part'
        );

        $this->simple('Zentec Infinity Hybrid Filtration System', 'EWS-FS1OM2', 4737.50, $fl,
            '<p>Excalibur Zentec Infinity Hybrid Filtration System. Removes hardness, tannins, ferrous iron, manganese and hydrogen sulfide. Electronic metered high efficiency automatic whole house filter. Service flow up to 6 GPM. Res-up feeder and cleaner included.</p>',
            'Zentec Infinity Hybrid Filtration System for hardness, iron, tannins, and H2S.',
            'zentec,infinity,hybrid,filter,EWS'
        );

        $this->simple('PFAS Lead-Lag Ion Exchange Filtration System', 'EWS-BTPPFAS25', 10800.00, $sp,
            '<p>Excalibur PFAS Lead/Lag Ion Exchange Filtration System. 2.5 cuft. Provides non-detect PFAS reading for municipal or well water applications.</p>',
            'PFAS lead-lag ion exchange filtration system, 2.5 cuft.',
            'PFAS,ion exchange,filtration,EWS'
        );

        $this->simple('Ultrafiltration Drinking Water System', 'FH-TH10UF', 222.50, $sp,
            '<p>Excalibur Ultrafiltration Drinking Water System. Removes benzene, chlorine, chloramines, dioxane, organic matter, pesticides and heavy metals. 3-stage system with brushed nickel faucet.</p>',
            'Excalibur ultrafiltration drinking water system with 3-stage filtration.',
            'ultrafiltration,drinking water,filter'
        );

        // ── ZENTEC AIR INJECTION FILTERS ──────────────────────────────────────
        $this->variable('Zentec Value Air Injection Iron Sulphur Filter', $ir,
            '<p>Excalibur Zentec Value Air Injection Capsulate Filter for iron, sulphur and manganese removal. Uses air injection technology without chemicals. Black mineral tank, timer included. Bypass valve and adapter kit included. 7-year unlimited warranty.</p>',
            'Zentec Value air injection filter for iron, sulphur and manganese. 0.9 to 1.7 ft3.',
            'zentec,air injection,iron,sulphur,manganese,value,EWS',
            [
                ['label'=>'0.9 ft3','sku'=>'EWS-BTZC075','price'=>2065.00],
                ['label'=>'1.2 ft3','sku'=>'EWS-BTZC1',  'price'=>2177.50],
                ['label'=>'1.7 ft3','sku'=>'EWS-BTZC15', 'price'=>2525.00],
            ]
        );

        $this->variable('Zentec Superior Air Injection Iron Sulphur Filter', $ir,
            '<p>Excalibur Zentec Superior Air Injection Capsulate Filter. Black mineral tank with 3/4" check valve. Timer included. Bypass valve and adapter kit included. 12-year unlimited warranty.</p>',
            'Zentec Superior air injection filter for iron, sulphur and manganese. 0.9 to 1.7 ft3.',
            'zentec,air injection,iron,sulphur,manganese,superior,EWS',
            [
                ['label'=>'0.9 ft3','sku'=>'EWS-BTSZC075','price'=>2107.50],
                ['label'=>'1.2 ft3','sku'=>'EWS-BTSZC1',  'price'=>2177.50],
                ['label'=>'1.7 ft3','sku'=>'EWS-BTSZC15', 'price'=>2577.50],
            ]
        );

        $this->variable('Zentec Premium Air Injection Iron Sulphur Filter', $ir,
            '<p>Excalibur Zentec Premium Air Injection Capsulate Filter. Chrome jacket, black cap with 3/4" check valve. Timer included. Bypass valve and adapter kit included. 20-year unlimited warranty.</p>',
            'Zentec Premium air injection filter for iron, sulphur and manganese. 0.9 to 1.7 ft3.',
            'zentec,air injection,iron,sulphur,premium,chrome,EWS',
            [
                ['label'=>'0.9 ft3','sku'=>'EWS-BTPZC075','price'=>2270.00],
                ['label'=>'1.2 ft3','sku'=>'EWS-BTPZC1',  'price'=>2340.00],
                ['label'=>'1.7 ft3','sku'=>'EWS-BTPZC15', 'price'=>2852.50],
            ]
        );

        $this->variable('Zentec Value Hybrid Iron and Sulphur Backwashable Filter', $ir,
            '<p>Excalibur Zentec Value Hybrid Iron and Sulphur Backwashable Filter. Removes iron, hydrogen sulfide and manganese. Black mineral tank with electronic timer. Bypass valve and adapter kit included. 5-year unlimited warranty.</p>',
            'Zentec Value hybrid iron and sulphur backwashable filter. 0.75 to 1.5 ft3.',
            'zentec,hybrid,iron,sulphur,backwash,value,EWS',
            [
                ['label'=>'0.75 ft3','sku'=>'EWS-BTZH075','price'=>1630.00],
                ['label'=>'1.0 ft3', 'sku'=>'EWS-BTZH1',  'price'=>1682.50],
                ['label'=>'1.5 ft3', 'sku'=>'EWS-BTZH15', 'price'=>2105.00],
            ]
        );

        $this->variable('Zentec Superior Hybrid Iron and Sulphur Backwashable Filter', $ir,
            '<p>Excalibur Zentec Superior Hybrid Iron and Sulphur Backwashable Filter. Black mineral tank with electronic timer. Bypass valve and adapter kit included. 7-year unlimited warranty.</p>',
            'Zentec Superior hybrid iron and sulphur backwashable filter. 0.75 to 1.5 ft3.',
            'zentec,hybrid,iron,sulphur,superior,EWS',
            [
                ['label'=>'0.75 ft3','sku'=>'EWS-BTSZH075','price'=>1687.50],
                ['label'=>'1.0 ft3', 'sku'=>'EWS-BTSZH1',  'price'=>1740.00],
                ['label'=>'1.5 ft3', 'sku'=>'EWS-BTSZH15', 'price'=>2160.00],
            ]
        );

        $this->variable('Zentec Premium Hybrid Iron and Sulphur Backwashable Filter', $ir,
            '<p>Excalibur Zentec Premium Hybrid Iron and Sulphur Backwashable Filter. Chrome jacket, black cap with electronic timer. Bypass valve and adapter kit included. 10-year unlimited warranty.</p>',
            'Zentec Premium hybrid iron and sulphur filter with chrome jacket. 0.75 to 1.5 ft3.',
            'zentec,hybrid,iron,sulphur,premium,chrome,EWS',
            [
                ['label'=>'0.75 ft3','sku'=>'EWS-BTPZH075','price'=>1770.00],
                ['label'=>'1.0 ft3', 'sku'=>'EWS-BTPZH1',  'price'=>1825.00],
                ['label'=>'1.5 ft3', 'sku'=>'EWS-BTPZH15', 'price'=>2397.50],
            ]
        );

        // ── TANNIN FILTERS ────────────────────────────────────────────────────
        $this->variable('Value Tannin Filter', $tn,
            '<p>Excalibur Filtermax Value Tannin Filter with downflow electronic timer. Removes tannins that cause yellow/brown water staining. Black mineral tank and brine tank included. 5-year unlimited warranty.</p>',
            'Excalibur Value tannin filter with electronic timer. 1.0 to 2.0 ft3.',
            'tannin filter,value,EWS,tannin',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-TFTN1', 'price'=>2570.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-TFTN15','price'=>3075.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-TFTN2', 'price'=>3845.00],
            ]
        );

        $this->variable('Superior Tannin Filter', $tn,
            '<p>Excalibur Filtermax Superior Tannin Filter with downflow electronic timer. Black mineral tank and brine tank included. Bypass valve and adapter kit included. 7-year unlimited warranty.</p>',
            'Excalibur Superior tannin filter with electronic timer. 1.0 to 2.0 ft3.',
            'tannin filter,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-TFSTN1', 'price'=>2570.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-TFSTN15','price'=>3075.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-TFSTN2', 'price'=>3845.00],
            ]
        );

        $this->variable('Premium Tannin Filter', $tn,
            '<p>Excalibur Filtermax Premium Tannin Filter with downflow electronic timer. Zentec hybrid chrome jacket. Bypass valve and adapter kit included. 10-year unlimited warranty.</p>',
            'Excalibur Premium tannin filter with chrome jacket. 1.0 to 2.0 ft3.',
            'tannin filter,premium,chrome,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-TFPTN1', 'price'=>2980.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-TFPTN15','price'=>3585.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-TFPTN2', 'price'=>4415.00],
            ]
        );

        // ── NEUTRALIZING FILTERS ──────────────────────────────────────────────
        $this->variable('Value Neutralizing Filter', $ne,
            '<p>Excalibur Filtermax Value Neutralizing Filter with electronic timer. Raises low pH water to neutral, reduces corrosion. Crushed marble/calcite media. Black mineral tank. 5-year unlimited warranty.</p>',
            'Excalibur Value neutralizing filter for low pH acidic water. 1.0 to 2.0 ft3.',
            'neutralizer,pH,acid,calcite,value,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPH1', 'price'=>1350.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPH15','price'=>1455.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPH2', 'price'=>1685.50],
            ]
        );

        $this->variable('Superior Neutralizing Filter', $ne,
            '<p>Excalibur Filtermax Superior Neutralizing Filter with electronic timer. Black mineral tank. Bypass valve and adapter kit included. 7-year unlimited warranty.</p>',
            'Excalibur Superior neutralizing filter for low pH acidic water. 1.0 to 2.0 ft3.',
            'neutralizer,pH,acid,calcite,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTSPH1', 'price'=>1410.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTSPH15','price'=>1455.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTSPH2', 'price'=>1745.00],
            ]
        );

        $this->variable('Premium Neutralizing Filter', $ne,
            '<p>Excalibur Filtermax Premium Neutralizing Filter with electronic timer. Chrome jacket and black cap. Bypass valve and adapter kit included. 10-year unlimited warranty.</p>',
            'Excalibur Premium neutralizing filter for low pH water with chrome jacket. 1.0 to 2.0 ft3.',
            'neutralizer,pH,acid,premium,chrome,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPPH1', 'price'=>1475.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPPH15','price'=>1590.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPPH2', 'price'=>1922.50],
            ]
        );

        // ── TURBIDITY FILTERS ─────────────────────────────────────────────────
        $this->variable('Value Turbidity Filter', $tu,
            '<p>Excalibur Filtermax Value Turbidity Filter with electronic timer. Clinoptilolite natural media reduces suspended matter and turbidity. Black mineral tank. 5-year unlimited warranty.</p>',
            'Excalibur Value turbidity filter for sediment and suspended matter. 1.0 to 2.0 ft3.',
            'turbidity,sediment,filter,value,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTAG1', 'price'=>1350.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTAG15','price'=>1460.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTAG2', 'price'=>1685.00],
            ]
        );

        $this->variable('Superior Turbidity Filter', $tu,
            '<p>Excalibur Filtermax Superior Turbidity Filter with electronic timer. Black mineral tank. Bypass valve and adapter kit included. 7-year unlimited warranty.</p>',
            'Excalibur Superior turbidity filter for suspended matter. 1.0 to 2.0 ft3.',
            'turbidity,sediment,filter,superior,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTSAG1', 'price'=>1410.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTSAG15','price'=>1517.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTSAG2', 'price'=>1685.00],
            ]
        );

        $this->variable('Premium Turbidity Filter', $tu,
            '<p>Excalibur Filtermax Premium Turbidity Filter with electronic timer. Chrome jacket and black cap. Bypass valve and adapter kit included. 10-year unlimited warranty.</p>',
            'Excalibur Premium turbidity filter with chrome jacket. 1.0 to 2.0 ft3.',
            'turbidity,sediment,filter,premium,chrome,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPAG1', 'price'=>1475.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPAG15','price'=>1590.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPAG2', 'price'=>1922.50],
            ]
        );

        // ── SPECIALTY FILTERS ─────────────────────────────────────────────────
        $this->variable('Filtermax Uranium Filter', $sp,
            '<p>Excalibur Filtermax Uranium Filter with electronic timer. Chrome jacket and black cap. Removes uranium from drinking water. Twin tank design. 10-year unlimited warranty.</p>',
            'Excalibur uranium removal filter with chrome jacket. 1.0 to 2.0 ft3.',
            'uranium,filter,specialty,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPUR1', 'price'=>2702.50],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPUR15','price'=>3700.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPUR2', 'price'=>4042.50],
            ]
        );

        $this->variable('Filtermax Nitrate and Nitrite Filter', $sp,
            '<p>Excalibur Filtermax Nitrate and Nitrite Filter with electronic timer. Chrome jacket and black cap. Twin tank design for complete nitrate removal. 10-year unlimited warranty.</p>',
            'Excalibur nitrate and nitrite removal filter with chrome jacket. 1.0 to 2.0 ft3.',
            'nitrate,nitrite,filter,specialty,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPNR1', 'price'=>2702.50],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPNR15','price'=>3700.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPNR2', 'price'=>4042.50],
            ]
        );

        $this->variable('Filtermax Lead Removal Filter', $sp,
            '<p>Excalibur Filtermax Lead Removal Filter with electronic timer. Chrome jacket and black cap. Removes lead and heavy metals. Bypass valve and adapter kit included. 10-year unlimited warranty.</p>',
            'Excalibur lead removal filter with chrome jacket. 1.0 to 2.0 ft3.',
            'lead,heavy metals,filter,specialty,EWS',
            [
                ['label'=>'1.0 ft3','sku'=>'EWS-BTPLD1', 'price'=>2945.00],
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPLD15','price'=>3830.00],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPLD2', 'price'=>4642.50],
            ]
        );

        $this->variable('Filtermax Arsenic Removal Filter', $sp,
            '<p>Excalibur Filtermax Arsenic Removal Filter with electronic timer. Chrome jacket and black cap. Removes arsenic from drinking water. 2-year unlimited warranty.</p>',
            'Excalibur arsenic removal filter with chrome jacket. 1.5 to 2.5 ft3.',
            'arsenic,filter,specialty,EWS',
            [
                ['label'=>'1.5 ft3','sku'=>'EWS-BTPAR15S','price'=>5682.50],
                ['label'=>'2.0 ft3','sku'=>'EWS-BTPAR2S', 'price'=>9475.00],
                ['label'=>'2.5 ft3','sku'=>'EWS-BTPAR25S','price'=>10782.50],
            ]
        );

        $this->variable('Degassifier Methane Gas Removal System', $sp,
            '<p>Excalibur Degasification System. Aerator that naturally removes methane, radon, hydrogen sulfide and carbon dioxide from well water supply. Includes submersible pump, pressure tank, solenoid valve, blower and spray bar. 5-year unlimited warranty.</p>',
            'Excalibur degassifier for methane, radon, hydrogen sulfide and CO2 removal.',
            'degassifier,aeration,methane,radon,hydrogen sulfide',
            [
                ['label'=>'115V','sku'=>'A30VOD','price'=>11995.00],
                ['label'=>'220V','sku'=>'A50VOD','price'=>11995.00],
            ]
        );

        // ── ULTRAVIOLET SYSTEMS ───────────────────────────────────────────────
        $this->simple('Signature Series Premium UV System 8GPM', 'UVS-8GPMTP110L', 1177.50, $uv,
            '<p>Excalibur Signature Series Premium Ultraviolet System. Mini Rack 8 GPM with 10" pre-sediment filter. Complete with stainless steel UV chamber and wall mount bracket.</p>',
            'Signature Series Premium UV system, 8 GPM, with 10" sediment pre-filter.',
            'UV,ultraviolet,8gpm,signature,premium'
        );

        $this->simple('Signature Series Optimum UV System 8GPM with Colour Screen', 'UVS-8GPMTP220LP', 1987.50, $uv,
            '<p>Excalibur Signature Series Optimum UV System. Mini Rack 8 GPM with 20" sediment and carbon pre-filters. Luminor colour screen display.</p>',
            'Signature Optimum UV 8 GPM with colour screen, sediment and carbon filters.',
            'UV,ultraviolet,8gpm,signature,optimum,colour screen'
        );

        $this->simple('Signature Series Optimum UV System 8GPM', 'UVS-8GPMTP220L', 1799.00, $uv,
            '<p>Excalibur Signature Series Optimum UV System. Mini Rack 8 GPM with 20" sediment and carbon pre-filters.</p>',
            'Signature Optimum UV 8 GPM with sediment and carbon filters.',
            'UV,ultraviolet,8gpm,signature,optimum'
        );

        $this->simple('Signature Series Optimum UV System 15GPM with Colour Screen', 'UVS-15GPMTP220LP', 2549.00, $uv,
            '<p>Excalibur Signature Series Optimum UV System. Mini Rack 15 GPM with 20" sediment and carbon pre-filters. Luminor colour screen display.</p>',
            'Signature Optimum UV 15 GPM with colour screen and pre-filters.',
            'UV,ultraviolet,15gpm,signature,optimum,colour screen'
        );

        $this->simple('Signature Series Optimum UV System 15GPM', 'UVS-15GPMTP220L', 2305.00, $uv,
            '<p>Excalibur Signature Series Optimum UV System. Mini Rack 15 GPM with 20" sediment and carbon pre-filters.</p>',
            'Signature Optimum UV 15 GPM with sediment and carbon pre-filters.',
            'UV,ultraviolet,15gpm,signature,optimum'
        );

        $this->simple('Signature Series Ultimate UV NSF System 8GPM', 'UVS-NSF840LP', 2875.00, $uv,
            '<p>Excalibur Signature Series Ultimate Ultraviolet NSF/ANSI 55 Class A Certified System. 8 GPM 40,000 mJ dose. Luminor colour screen display.</p>',
            'Signature Ultimate UV NSF certified system, 8 GPM, 40000 mJ, colour screen.',
            'UV,ultraviolet,NSF,8gpm,signature,ultimate'
        );

        $this->simple('Signature Series Ultimate UV NSF System 18GPM', 'UVS-NSF1840LP', 4075.00, $uv,
            '<p>Excalibur Signature Series Ultimate Ultraviolet NSF/ANSI 55 Class A Certified System. 18 GPM 40,000 mJ dose. Luminor colour screen display.</p>',
            'Signature Ultimate UV NSF certified system, 18 GPM, 40000 mJ, colour screen.',
            'UV,ultraviolet,NSF,18gpm,signature,ultimate'
        );

        $this->variable('Premium Mini Rack UV Single 10 Inch', $uv,
            '<p>Excalibur Premium Mini Rack UV Sterilizer System with single 10" jumbo sediment pre-filter. Wall mount bracket, isolation valves and pressure gauges included.</p>',
            'Premium Mini Rack UV with single 10" sediment filter. 8 or 10 GPM.',
            'UV,ultraviolet,mini rack,sediment,premium',
            [
                ['label'=>'8 GPM', 'sku'=>'UVS-8GPMTP110', 'price'=>1237.50],
                ['label'=>'10 GPM','sku'=>'UVS-10GPMTP110','price'=>1310.00],
            ]
        );

        $this->variable('Premium Mini Rack UV Double 10 Inch', $uv,
            '<p>Excalibur Premium Mini Rack UV Sterilizer System with double 10" jumbo sediment and carbon pre-filters. Wall mount bracket included.</p>',
            'Premium Mini Rack UV with double 10" sediment and carbon filters. 8 or 10 GPM.',
            'UV,ultraviolet,mini rack,carbon,premium',
            [
                ['label'=>'8 GPM', 'sku'=>'UVS-8GPMTP210', 'price'=>1432.50],
                ['label'=>'10 GPM','sku'=>'UVS-10GPMTP210','price'=>1470.00],
            ]
        );

        $this->variable('Premium Mini Rack UV Single 20 Inch', $uv,
            '<p>Excalibur Premium Mini Rack UV Sterilizer System with single 20" jumbo sediment pre-filter. Wall mount bracket included.</p>',
            'Premium Mini Rack UV with single 20" sediment filter. 8, 10 or 13 GPM.',
            'UV,ultraviolet,mini rack,20 inch,sediment',
            [
                ['label'=>'8 GPM', 'sku'=>'UVS-8GPMTP120', 'price'=>1310.00],
                ['label'=>'10 GPM','sku'=>'UVS-10GPMTP120','price'=>1377.50],
                ['label'=>'13 GPM','sku'=>'UVS-13GPMTP120','price'=>1525.00],
            ]
        );

        $this->variable('Premium Mini Rack UV Double 20 Inch', $uv,
            '<p>Excalibur Premium Mini Rack UV Sterilizer System with double 20" jumbo sediment and carbon pre-filters. Wall mount bracket included.</p>',
            'Premium Mini Rack UV with double 20" sediment and carbon filters. 8, 10 or 13 GPM.',
            'UV,ultraviolet,mini rack,20 inch,carbon',
            [
                ['label'=>'8 GPM', 'sku'=>'UVS-8GPMTP220', 'price'=>1505.00],
                ['label'=>'10 GPM','sku'=>'UVS-10GPMTP220','price'=>1575.00],
                ['label'=>'13 GPM','sku'=>'UVS-13GPMTP220','price'=>1792.50],
            ]
        );

        $this->simple('Value Economy UV Sterilizer 8GPM 30000mJ', 'UVS-8GPM30SLC', 860.00, $uv,
            '<p>UV Dynamics Value Economy Ultra Violet Sterilizer. 8 GPM, 30,000 mJ dose. For residential well water disinfection.</p>',
            'Value Economy UV sterilizer, 8 GPM, 30,000 mJ.',
            'UV,ultraviolet,value,8gpm,sterilizer'
        );

        $this->simple('Value Economy UV Sterilizer 8GPM 40000mJ', 'UVS-8GPM40SLE', 955.00, $uv,
            '<p>UV Dynamics Value Economy Ultra Violet Sterilizer. 8 GPM, 40,000 mJ dose. For residential well water disinfection.</p>',
            'Value Economy UV sterilizer, 8 GPM, 40,000 mJ.',
            'UV,ultraviolet,value,8gpm,sterilizer'
        );

        $this->simple('Value Economy UV Sterilizer 10GPM 30000mJ', 'UVS-10GPM30SLE', 955.00, $uv,
            '<p>UV Dynamics Value Economy Ultra Violet Sterilizer. 10 GPM, 30,000 mJ dose. For residential well water disinfection.</p>',
            'Value Economy UV sterilizer, 10 GPM, 30,000 mJ.',
            'UV,ultraviolet,value,10gpm,sterilizer'
        );

        $this->variable('NSF Certified UV Sterilizer System', $uv,
            '<p>Excalibur NSF/ANSI 55 Class A Certified Ultra Violet Sterilizers. 40,000 mJ dose for complete disinfection. Available in 8, 11, 14 and 20 GPM flow rates.</p>',
            'NSF/ANSI 55 Class A certified UV sterilizer, 40,000 mJ. 8 to 20 GPM.',
            'UV,ultraviolet,NSF,certified,sterilizer',
            [
                ['label'=>'8 GPM', 'sku'=>'UVS-NSF840', 'price'=>2857.50],
                ['label'=>'11 GPM','sku'=>'UVS-NSF1140','price'=>2925.00],
                ['label'=>'14 GPM','sku'=>'UVS-NSF1440','price'=>3070.00],
                ['label'=>'20 GPM','sku'=>'UVS-NSF2040','price'=>3565.00],
            ]
        );

        // ── REVERSE OSMOSIS ───────────────────────────────────────────────────
        $this->simple('Value 3 Stage RO Drinking Water System 35GPD', 'EWR-3035', 602.50, $ro,
            '<p>Excalibur Value 3 Stage Manifold Reverse Osmosis Drinking Water System. 35 GPD membrane. Comes complete with faucet, tank and installation fitting kit. 3-year unlimited warranty (excluding consumables).</p>',
            'Excalibur Value 3-stage RO system, 35 GPD. Complete with faucet and tank.',
            'reverse osmosis,RO,drinking water,3 stage,value'
        );

        $this->simple('Superior 5 Stage RO System 75GPD', 'EWR-5075C', 625.00, $ro,
            '<p>Excalibur Superior 5 Stage Reverse Osmosis System. 75 GPD membrane with manual autoflush. Stages: sediment, 2x carbon block, TFC membrane, coconut polishing. Complete with faucet and tank. 5-year warranty (excluding consumables).</p>',
            'Superior 5-stage RO system, 75 GPD, with manual autoflush.',
            'reverse osmosis,RO,5 stage,75gpd,superior'
        );

        $this->simple('Superior 6 Stage RO System 75GPD with Enalka', 'EWR-5075E', 735.00, $ro,
            '<p>Excalibur Superior 6 Stage Reverse Osmosis System with Enalka remineralizing filter. 75 GPD with manual autoflush. 5-year warranty (excluding consumables).</p>',
            'Superior 6-stage RO system, 75 GPD, with Enalka remineralizing filter.',
            'reverse osmosis,RO,6 stage,75gpd,enalka,remineralizing'
        );

        $this->simple('Superior Plus 5 Stage RO System 75GPD', 'EWR-5075CP', 680.00, $ro,
            '<p>Excalibur Superior Plus 5 Stage Reverse Osmosis System. 75 GPD with pressure reducing valve and leak detection. Manual autoflush. 5-year warranty (excluding consumables).</p>',
            'Superior Plus 5-stage RO, 75 GPD, with pressure reducing valve and leak detection.',
            'reverse osmosis,RO,5 stage,75gpd,leak detection'
        );

        $this->simple('Superior Plus 6 Stage RO System 75GPD with Enalka', 'EWR-5075EP', 790.00, $ro,
            '<p>Excalibur Superior Plus 6 Stage Reverse Osmosis System with Enalka remineralizing filter. 75 GPD, pressure reducing valve and leak detection. 5-year warranty.</p>',
            'Superior Plus 6-stage RO, 75 GPD, Enalka filter plus leak detection.',
            'reverse osmosis,RO,6 stage,75gpd,enalka,leak detection'
        );

        $this->simple('Premium 6 Stage RO System 100GPD with Booster Pump', 'EWR-5100C', 1037.50, $ro,
            '<p>Excalibur Premium 6 Stage Reverse Osmosis System. 100 GPD with booster pump and automatic autoflush. Stages: sediment, 2x carbon block, booster pump, 100 GPD TFC membrane, coconut polishing. 7-year warranty.</p>',
            'Premium 6-stage RO, 100 GPD, with booster pump and auto-flush.',
            'reverse osmosis,RO,6 stage,100gpd,booster pump,premium'
        );

        $this->variable('Premium 7 Stage RO System 100GPD with Remineralizer', $ro,
            '<p>Excalibur Premium 7 Stage Reverse Osmosis System. 100 GPD with booster pump, automatic autoflush, and remineralizing filter. Choose neutralizer (calcite/GAC) or alkaline (calcite/corosex) final stage. 7-year warranty (excluding consumables).</p>',
            'Premium 7-stage RO, 100 GPD, with booster pump and remineralizing filter. Choose neutralizer or alkaline.',
            'reverse osmosis,RO,7 stage,100gpd,remineralizing,alkaline',
            [
                ['label'=>'With Neutralizer','sku'=>'EWR-5100E', 'price'=>1217.50],
                ['label'=>'With Alkaline',   'sku'=>'EWR-5100EA','price'=>1327.50],
            ]
        );

        $this->simple('Premium 7 Stage RO System 100GPD Ceramic and Enalka', 'EWR-7100P', 1360.00, $ro,
            '<p>Excalibur Premium 7 Stage Reverse Osmosis System. 100 GPD with booster pump, ceramic 0.9 micron filter and Enalka mineral pH correction. Automatic autoflush. 7-year warranty.</p>',
            'Premium 7-stage RO, 100 GPD, with ceramic filter, booster pump and Enalka.',
            'reverse osmosis,RO,7 stage,100gpd,ceramic,enalka'
        );

        $this->simple('Smart Purifier PLUS Tankless RO System', 'EWR-301000', 1245.00, $ro,
            '<p>Excalibur Smart Purifier PLUS Tankless Reverse Osmosis System. Most advanced tankless RO with two high-capacity filters and smart faucet with digital TDS display. Includes brass nickel faucet and installation kit. 2-year warranty (excluding consumables).</p>',
            'Smart Purifier PLUS tankless RO system with TDS display faucet.',
            'reverse osmosis,RO,tankless,smart purifier,TDS'
        );

        $this->variable('Modern RO Faucet', $ro,
            '<p>Modern style reverse osmosis drinking water faucet. Available in Matte Black, Chrome, and Brushed Nickel finishes.</p>',
            'Modern RO faucet. Available in Matte Black, Chrome or Brushed Nickel.',
            'RO faucet,modern,kitchen',
            [
                ['label'=>'Matte Black',    'sku'=>'RO-F105BKM','price'=>215.00],
                ['label'=>'Chrome',         'sku'=>'RO-F105CM', 'price'=>147.50],
                ['label'=>'Brushed Nickel', 'sku'=>'RO-F105BNM','price'=>147.50],
            ]
        );

        $this->variable('Antique RO Faucet', $ro,
            '<p>Antique style reverse osmosis drinking water faucet. Available in Matte Black, Chrome, and Brushed Nickel finishes.</p>',
            'Antique RO faucet. Available in Matte Black, Chrome or Brushed Nickel.',
            'RO faucet,antique,kitchen',
            [
                ['label'=>'Matte Black',    'sku'=>'RO-F105BK','price'=>215.00],
                ['label'=>'Chrome',         'sku'=>'RO-F104',  'price'=>155.00],
                ['label'=>'Brushed Nickel', 'sku'=>'RO-F105BN','price'=>147.50],
            ]
        );

        $this->variable('TDS Display RO Faucet', $ro,
            '<p>TDS display reverse osmosis faucet showing outgoing water quality. Available in Matte Black, Chrome, and Brushed Nickel finishes.</p>',
            'RO faucet with TDS display. Available in 3 finishes.',
            'RO faucet,TDS,display',
            [
                ['label'=>'Matte Black',    'sku'=>'RO-F1002','price'=>168.00],
                ['label'=>'Chrome',         'sku'=>'RO-F1001','price'=>168.00],
                ['label'=>'Brushed Nickel', 'sku'=>'RO-F1000','price'=>112.50],
            ]
        );

        $this->variable('Dual RO Faucet', $ro,
            '<p>Dual purpose reverse osmosis drinking water faucet. Available in Matte Black, Chrome, and Brushed Nickel finishes.</p>',
            'Dual RO faucet. Available in 3 finishes.',
            'RO faucet,dual,kitchen',
            [
                ['label'=>'Matte Black',    'sku'=>'RO-F105BKD','price'=>112.50],
                ['label'=>'Chrome',         'sku'=>'RO-F105CMD','price'=>100.00],
                ['label'=>'Brushed Nickel', 'sku'=>'RO-F105BND','price'=>112.50],
            ]
        );

        // ── MINERAL TANKS ADDITIONS ───────────────────────────────────────────
        $this->addMineralTank13x54();

        $this->variable('Mineral Tanks Natural 4.0 Inch Top Opening NSF Certified', $mt,
            '<p>Excalibur Mineral Tanks Natural colour with 4.0" top opening. NSF Certified to NSF/ANSI Standard 61. Available in 14"x65" and 16"x65" sizes.</p>',
            'NSF Certified mineral tanks, natural, 4.0" top opening. 14x65 and 16x65 sizes.',
            'mineral tank,NSF,4 inch,natural',
            [
                ['label'=>'Tank 14x65','sku'=>'WTM1465N40','price'=>807.50],
                ['label'=>'Tank 16x65','sku'=>'WTM1665N40','price'=>947.50],
            ]
        );

        $this->command->info('ExcaliburSystemsSeeder complete.');
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function addMineralTank13x54(): void
    {
        $product = Product::whereRaw('LOWER(name) = ?', ['mineral tanks black 2.5" top opening nsf certified'])->first();
        if (!$product) {
            $this->command->warn('Mineral Tanks 2.5" product not found – skipping 13x54 addition.');
            return;
        }
        if (ProductStock::where('product_id', $product->id)->where('sku', 'WTM1354B25')->exists()) {
            $this->command->warn('Tank 13x54 variant already exists.');
            return;
        }
        ProductStock::create([
            'product_id' => $product->id,
            'variant'    => 'Tank13x54',
            'price'      => 517.50,
            'sku'        => 'WTM1354B25',
            'qty'        => 10,
            'image'      => null,
        ]);
        // Update choice_options to include new variant
        $co = json_decode($product->choice_options, true);
        $co[0]['values'][] = 'Tank 13x54';
        $product->choice_options = json_encode($co);
        $product->current_stock  = $product->current_stock + 10;
        $product->save();
        $this->command->info('Added Tank 13x54 variant to Mineral Tanks product.');
    }

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
