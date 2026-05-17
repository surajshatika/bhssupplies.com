<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\Category;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImportUniliteProducts extends Command
{
    protected $signature = 'import:unilite {--dry-run : Preview without saving} {--skip-images : Skip image downloading}';
    protected $description = 'Import all Unilite 2024 Canada price list products';

    private const MAIN_CAT_ID  = 152;
    private const BRAND_NAME   = 'Unilite';
    private const MARKUP       = 1.20;   // List Price + 20%
    private const DISCOUNT_PCT = 25;     // 25% discount shown to customers

    private ?int   $adminUserId = null;
    private ?int   $brandId     = null;
    private array  $subCatCache = [];

    // ────────────────────────────────────────────────────────────────────────
    // All products: [product_code, barcode/sku, list_price_cad, short_desc, sub_category]
    // ────────────────────────────────────────────────────────────────────────
    private array $products = [
        // INSPECTION LIGHTS
        ['PL-3',      '5013581004430', 39.99,  '275 Lumen Pocket Inspection Light 3xAAA',            'Inspection Lights'],
        ['IL-425R',   '5013581005635', 109.99, '425 Lumen Folding Inspection Light',                 'Inspection Lights'],
        ['IL-925R',   '5013581005062', 139.99, '925 Lumen Folding Inspection Light',                 'Inspection Lights'],
        ['IL-625R',   '5013581004744', 119.99, '625 Lumen Inspection Light',                         'Inspection Lights'],
        ['IL-SIG1',   '5013581001323', 179.99, '1100 Lumen Signalling Inspection Light',             'Inspection Lights'],
        ['CRI-1250R', '5013581004737', 189.99, '1250 Lumen High CRI Inspection Light',               'Inspection Lights'],

        // WORK LIGHTS
        ['K-550',     '5013581005741', 59.99,  '550 Lumen Compact Mini Work Light',                  'Work Lights'],
        ['WL-450R',   '5013581005680', 89.99,  '450 Lumen Dual Beam Compact Work Light',             'Work Lights'],
        ['SLR-1450',  '5013581005161', 159.99, '1450 Lumen Compact Rotating Work Light',             'Work Lights'],
        ['SLR-1750',  '5013581004713', 179.99, '1750 Lumen Work Light with Powerbank',               'Work Lights'],
        ['HX1500R',   '5013581004829', 159.99, '1500 Lumen Compact Work Light',                      'Work Lights'],
        ['CRI-1650R', '5013581004942', 219.99, '1650 Lumen High CRI Compact Work Light',             'Work Lights'],

        // SITE LIGHTS
        ['RF-2000',    '5013581005147', 179.99, '2000 Lumen Heavy Duty Floodlight',                          'Site Lights'],
        ['RF-3300',    '5013581005154', 239.99, '3300 Lumen Heavy Duty Floodlight',                          'Site Lights'],
        ['RF-5400',    '5013581005086', 339.99, '5400 Lumen Dual Power Floodlight',                          'Site Lights'],
        ['SLR-3500',   '5013581005048', 299.99, '3500 Lumen Site Light with Removable Battery',              'Site Lights'],
        ['SLR-5500',   '5013581004973', 359.99, '5500 Lumen Site Light with Removable Battery',              'Site Lights'],
        ['SLR-4400',   '5013581004980', 349.99, '4400 Lumen Dual Power Site Light',                          'Site Lights'],
        ['SLR-6000',   '5013581004997', 499.99, '6000 Lumen Dual Power Site Light',                          'Site Lights'],
        ['SP-4500',    '5013581004782', 429.99, '4500 Lumen Dual Power Site Light with 10W Bluetooth Speaker','Site Lights'],
        ['MTB-5300',   '5013581005376', 329.99, '5300 Lumen Power Tool Battery Site Light',                  'Site Lights'],
        ['MTB-10000',  '5013581005482', 409.99, '10000 Lumen Power Tool Battery Site Light',                 'Site Lights'],
        ['CRI-2300',   '5013581004720', 359.99, '2300 Lumen High CRI Dual Power Site Light',                 'Site Lights'],
        ['CRI-3250',   '5013581004874', 359.99, '3250 Lumen High CRI Dual Power Site Light',                 'Site Lights'],
        ['RL-5250',    '5013581005017', 389.99, '5250 Lumen 360 Degree Dual Power Site Lantern',             'Site Lights'],

        // HEADLAMPS
        ['CRI-H200R',  '5013581004768', 129.99, '200 Lumen High CRI Sensor Headlamp',                  'Headlamps'],
        ['PS-HDL2',    '5013581003365', 79.99,  '200 Lumen Helmet Headlamp 3xAAA',                    'Headlamps'],
        ['HL-4R',      '5013581000197', 99.99,  '275 Lumen Helmet Headlamp',                           'Headlamps'],
        ['HL-5R',      '5013581000203', 109.99, '325 Lumen Sensor Headlamp',                           'Headlamps'],
        ['PS-HDL6R',   '5013581003402', 154.99, '350 Lumen Dual Power Helmet Headlamp',                'Headlamps'],
        ['HT-450',     '5013581005031', 84.99,  '450 Lumen Dual LED Headlamp 3xAAA',                  'Headlamps'],
        ['HL-6R',      '5013581000210', 159.99, '450 Lumen Dual LED Headlamp',                         'Headlamps'],
        ['HL-7R',      '5013581000234', 119.99, '475 Lumen Dual Beam Sensor Headlamp',                 'Headlamps'],
        ['HL-8R',      '5013581004928', 119.99, '475 Lumen Dual Beam Sensor Headlamp Rechargeable',    'Headlamps'],
        ['HT-650R',    '5013581005024', 164.99, '650 Lumen Dual LED Dual Power Headlamp',              'Headlamps'],
        ['HT-680R',    '5013581005116', 159.99, '680 Lumen High CRI Dual LED Headlamp',                'Headlamps'],
        ['PS-HDL9R',   '5013581003426', 239.99, '750 Lumen Headlamp with Twist Dimmer Switch',         'Headlamps'],
        ['RAIL-HDL9R', '5013581003433', 239.99, '750 Lumen Rail Headlamp with Twist Dimmer Switch',    'Headlamps'],
        ['HT-900R',    '5013581005109', 179.99, '900 Lumen High Power Headlamp',                       'Headlamps'],
        ['HL-11R',     '5013581000272', 289.99, '1100 Lumen High Power Headlamp with Twist Dimmer Switch','Headlamps'],

        // MISC. LIGHTING
        ['BE-02+',  '5013581004164', 39.99,  '150 Lumen USB Beanie Light',                    'Misc. Lighting'],
        ['BLT-1',   '5013581004140', 69.99,  '250 Lumen USB Beanie Light',                    'Misc. Lighting'],
        ['NL-350R', '5013581005130', 109.99, '350 Lumen Neck Light',                           'Misc. Lighting'],
        ['PB-7800', '5013581004911', 119.99, '7800mAh Powerbank with 250 Lumen Flashlight',    'Misc. Lighting'],

        // FLASHLIGHTS
        ['FL-2',     '5013581004393', 79.99,  '200 Lumen Aluminum Flashlight 1xAA',              'Flashlights'],
        ['FL-4R',    '5013581004386', 119.99, '450 Lumen Rechargeable Aluminum Flashlight',      'Flashlights'],
        ['FL-550R',  '5013581004447', 139.99, '550 Lumen Rechargeable Aluminum Flashlight',      'Flashlights'],
        ['FL-1300R', '5013581004454', 179.99, '1300 Lumen Aluminum Flashlight',                  'Flashlights'],
        ['F-1400',   '5013581005666', 159.99, '1400 Lumen Dual Power Aluminum Flashlight',       'Flashlights'],
        ['F-2700',   '5013581005673', 199.99, '2700 Lumen USB Aluminum Flashlight',              'Flashlights'],
        ['FR-400',   '5013581005642', 109.99, '400 Lumen Right Angle USB Flashlight',            'Flashlights'],
        ['FR-1200',  '5013581005659', 159.99, '1200 Lumen Dual Power Right Angle Flashlight',    'Flashlights'],
        ['L-1800',   '5013581004966', 229.99, '1800 Lumen Dual LED Lantern',                     'Flashlights'],

        // WIRELESS CHARGING
        ['WCFL12', '5013581002627', 169.99, '1200 Lumen Wireless Flashlight',         'Wireless Charging'],
        ['WCIL11', '5013581005215', 179.99, '1100 Lumen Wireless Inspection Light',   'Wireless Charging'],
        ['WCHX7',  '5013581002665', 139.99, '700 Lumen Wireless Compact Work Light',  'Wireless Charging'],
        ['WCHT5',  '5013581005222', 139.99, '550 Lumen Wireless Headlamp',            'Wireless Charging'],
        ['WCSGL',  '5013581002641', 89.99,  'Single Wireless Charging Pad',           'Wireless Charging'],
        ['WCDBL',  '5013581002634', 139.99, 'Double Wireless Charging Pad',           'Wireless Charging'],

        // INTRINSICALLY SAFE (SKUs from PDF duplicate Wireless Charging — using product code as barcode)
        ['ATEX-FL4', 'ATEX-FL4', 119.99, '150 Lumen Zone 0 Intrinsically Safe Flashlight',         'Intrinsically Safe'],
        ['ATEX-PL1', 'ATEX-PL1', 89.99,  '65 Lumen Zone 0 Intrinsically Safe Penlight',             'Intrinsically Safe'],
        ['ATEX-RA2', 'ATEX-RA2', 159.99, '350 Lumen Zone 0 Intrinsically Safe Right Angle Torch',  'Intrinsically Safe'],
        ['ATEX-H2',  'ATEX-H2',  169.99, '225 Lumen Zone 0 Intrinsically Safe Headlamp',           'Intrinsically Safe'],

        // TRIPODS & ACCESSORIES
        ['TRIPOD-MINI',      '5013581003761', 44.99,  'Heavy Duty Mini Tripod',                            'Tripods & Accessories'],
        ['TRIPOD-SGL',       '5013581004607', 139.99, 'Extendable Tripod Single',                          'Tripods & Accessories'],
        ['TRIPOD-DBL',       '5013581004614', 149.99, 'Extendable Tripod for Two Lights',                  'Tripods & Accessories'],
        ['TRIPOD-SGL-WHEEL', '5013581004775', 179.99, 'Extendable Tripod with Wheels',                     'Tripods & Accessories'],
        ['TRIPOD-360',       '5013581004683', 169.99, 'Extendable Tripod with Flat Magnetic Plate',        'Tripods & Accessories'],
        ['LARGE-MAGNET',     '5013581004522', 39.99,  'Super Strong Magnet for Site Lights',               'Tripods & Accessories'],

        // KNIVES & CUTTING
        ['KC1',   '5013581008919', 17.99, 'Knife Cutter with 18mm Snap-off Blades',                       'Knives & Cutting'],
        ['UK1',   '5013581008896', 24.99, 'Folding Utility Knife with 4xSK5 Steel Blades',                'Knives & Cutting'],
        ['KC2',   '5013581008902', 24.99, 'Heavy Duty Cutter with 5xSK5 Steel Blades',                    'Knives & Cutting'],
        ['FK3',   '5013581008889', 59.99, 'Heavy Duty Folding Utility Knife Clip Point Blade',             'Knives & Cutting'],
        ['EK4',   '5013581008872', 59.99, 'Heavy Duty Folding Utility Knife Drop Point Blade',             'Knives & Cutting'],
        ['EK5',   '5013581008858', 69.99, 'Heavy Duty Folding Utility Knife Sheepsfoot Blade',             'Knives & Cutting'],
        ['ES-6',  '5013581008339', 64.99, 'Heavy Duty 6 Inch Electricians Scissors',                      'Knives & Cutting'],
        ['MFS-8', '5013581008346', 59.99, 'Heavy Duty 8 Inch Multi-Function Scissors',                    'Knives & Cutting'],

        // TAPE MEASURES
        ['MT5M4SL', '5013581008964', 34.99, 'Heavy Duty 5m Tape Measure 19mm Blade with Self Lock',       'Tape Measures'],
        ['MT5M2',   '5013581008940', 34.99, 'Heavy Duty 5m Tape Measure 27mm Blade with Superblade',      'Tape Measures'],
        ['MT8M3',   '5013581008957', 69.99, 'Heavy Duty 8m Tape Measure 32mm Blade with Superblade',      'Tape Measures'],

        // SAFETY GLASSES
        ['SG-YIO',      '5013581009466', 29.99, 'Safety Glasses with Indoor Outdoor Lenses',              'Safety Glasses'],
        ['SG-YFG',      '5013581009497', 39.99, 'Safety Glasses with Clear Lenses and Foam Gasket',       'Safety Glasses'],
        ['SG-YCB',      '5013581009480', 49.99, 'Safety Glasses with Clear Blue Light Lenses',            'Safety Glasses'],
        ['SG-YDS',      '5013581009473', 39.99, 'Safety Glasses with Dark Smoke Lenses',                  'Safety Glasses'],
        ['GLASSES-KIT', '5013581009503', 19.99, 'Safety Glasses Accessories Kit Case Cloth and Lanyard',  'Safety Glasses'],

        // SAFETY GLOVES
        ['UG-I2C4', 'UG-I2C4', 69.99, 'Heavy Duty Cut-D Impact Gloves',  'Safety Gloves'],
        ['UG-TW1',  'UG-TW1',  79.99, 'Thermal Waterproof Gloves',        'Safety Gloves'],

        // STORAGE POUCHES
        ['OP-1B', '5013581007554', 64.99, 'Set of 2 Heavy Duty Storage Zip Pouches',               'Storage Pouches'],
        ['OP-2B', '5013581007561', 64.99, 'Set of 2 Heavy Duty Stand-up Zip Pouches',              'Storage Pouches'],
        ['OP-3B', '5013581007578', 79.99, 'Super Heavy Duty Storage Pouch with Various Pockets',   'Storage Pouches'],

        // MERCHANDISE
        ['CAP-FLEX',       '5013581003693', 36.99, 'Black Flex-Fit Cap',                                   'Merchandise'],
        ['WATER-FLASK',    '5013581003525', 21.99, '500ml Stainless Steel 304 Vacuum Flask',               'Merchandise'],
        ['NOTEPAD',        '5013581003570', 22.99, 'A5 Hardback Notepad with Lined Pages',                 'Merchandise'],
        ['NOTEBOOK-STONE', '5013581003792', 14.99, 'A6 Flexible Notebook with Waterproof Stone Paper',     'Merchandise'],
        ['CARABINER-12KN', '5013581003808', 17.99, 'Heavy Duty 12KN Aluminum Carabiner',                  'Merchandise'],
        ['BAG',            '5013581003679', 24.99, 'Yellow Drawstring Gym Bag',                            'Merchandise'],
        ['ICE-SCRAPER',    '5013581003495', 12.99, 'Black Frosted Ice Scraper 3 Scraping Sides',           'Merchandise'],
        ['AIR-FRESHENER',  '5013581003778', 29.99, 'High Quality Air Freshener',                           'Merchandise'],
        ['MULTI-TOOL-CARD','5013581003709', 12.99, 'Stainless Steel Black 46-in-1 Multi Tool Card',        'Merchandise'],
        ['BAR-MAT-UNI',    '5013581003754', 39.99, 'Super Tough PVC Bar Mat',                              'Merchandise'],
        ['COASTERS-UNI',   '5013581003747', 24.99, 'Pack of 4 Unilite PVC Coasters',                       'Merchandise'],

        // SPARES & ACCESSORIES
        ['14500-FUELBAR',   '5013581002610', 24.99,  'Fuel Bar 14500 3.7v 800mAh Li-ion Battery for FL-4R and FL-550R',           'Spares & Accessories'],
        ['18650-2000MAH',   '5013581002481', 24.99,  '3.7v 2000mAh 18650 Li-ion Battery for HL-6R HDL9R HL-11R',                 'Spares & Accessories'],
        ['18650-2600MAH',   '5013581002597', 29.99,  '3.7v 2600mAh 18650 Li-ion Battery for HL-6R HDL9R HL-11R',                 'Spares & Accessories'],
        ['21700-5000MAH',   '5013581005178', 49.99,  '3.7v 5000mAh 21700 Li-ion Battery with USB-C for SLR-1450',                'Spares & Accessories'],
        ['BATTERY-HDL6R',   '5013581002498', 25.99,  'Spare Battery for PS-HDL6R Headlamp 3.7v 1800mAh Li-po',                   'Spares & Accessories'],
        ['BATTERY-HT650R',  '5013581002337', 24.99,  'Spare Battery for HT-650R Headlamp 3.8v 1100mAh Li-po',                    'Spares & Accessories'],
        ['BATTERY-SLR3000', '5013581004638', 119.99, 'Spare Battery for SLR-3000 6600mAh Li-ion',                                'Spares & Accessories'],
        ['BATTERY-SLR5500', '5013581005055', 139.99, 'Spare Battery for SLR-3000 SLR-3500 and SLR-5500 7.4v 7500mAh Li-ion',    'Spares & Accessories'],
        ['CHARGER-12V2A',   '5013581002375', 29.99,  '12V 2A Charger with UK EU US AU Adaptors for SLR-3000 SLR-3500 SLR-5500', 'Spares & Accessories'],
        ['CHARGER-15V1-5A', '5013581002382', 29.99,  '15V 1.5A Charger with UK EU US AU Adaptors for Other Site Lights',         'Spares & Accessories'],
        ['CHARGER-20V1-1A', '5013581005468', 44.99,  '20V 1.1A Charger for Unilite MTB Battery with Global Adaptors',            'Spares & Accessories'],
        ['CHARGER-24V2-7A', '5013581005406', 89.99,  '24V 2.7A Mains Lead Charger for Unilite MTB-5300 Site Light',              'Spares & Accessories'],
        ['MTB-BATTERY',     '5013581005390', 169.99, 'Unilite Battery for MTB Series 14.8v 5200mAh Li-ion with USB Power Bank',  'Spares & Accessories'],
        ['MTB-ADAP-MILWAU', '5013581005413', 14.99,  'Spare Milwaukee Battery Adaptor for MTB Site Light Series',                'Spares & Accessories'],
        ['MTB-ADAP-DEWALT', '5013581005444', 14.99,  'Spare DeWalt Battery Adaptor for MTB Site Light Series',                   'Spares & Accessories'],
        ['MTB-ADAP-MAKITA', '5013581005420', 14.99,  'Spare Makita Battery Adaptor for MTB Site Light Series',                   'Spares & Accessories'],
        ['MTB-ADAP-METABO', '5013581005437', 14.99,  'Spare Metabo Battery Adaptor for MTB Site Light Series',                   'Spares & Accessories'],
        ['MTB-ADAP-UNILITE','5013581005451', 14.99,  'Spare Unilite Battery Adaptor for MTB Site Light Series',                  'Spares & Accessories'],
        ['SPARE-BE-02PLUS', '5013581004218', 24.99,  'Spare 150 Lumen USB Lights for Unilite Beanies',                           'Spares & Accessories'],
        ['DC-USBCABLE',     '5013581002542', 10.99,  'DC-USB 1m Charging Cable',                                                 'Spares & Accessories'],
        ['TYPEC-CABLE',     '5013581002399', 10.99,  'USB-C 1m Charging Cable',                                                  'Spares & Accessories'],
        ['MICRO-USBCABLE',  '5013581002580', 10.99,  'Micro-USB 1m Charging Cable',                                              'Spares & Accessories'],
        ['12-24-USBCAR',    '5013581002559', 14.99,  '12/24V USB Car Adaptor',                                                   'Spares & Accessories'],
        ['3MVHB-MOUNT',     '5013581002467', 9.99,   '3M VHB Adhesive Helmet Mount for PS-HDL2 HL-4R and HL-7R',                 'Spares & Accessories'],
        ['MOUNT-HDL6R',     '5013581002450', 9.99,   '3M VHB Adhesive Helmet Mount for PS-HDL6R',                                'Spares & Accessories'],
        ['CARTRIDGE-HDL6R', '5013581002443', 9.99,   'Spare 3xAAA Battery Cartridge for PS-HDL6R',                               'Spares & Accessories'],
    ];

    public function handle(): int
    {
        $isDryRun    = $this->option('dry-run');
        $skipImages  = $this->option('skip-images');

        $this->info('Unilite 2024 Canada Product Importer');
        $this->info('Total products: ' . count($this->products));
        $this->info('Pricing: List Price × ' . self::MARKUP . ' → ' . self::DISCOUNT_PCT . '% discount applied');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('DRY RUN — nothing will be saved.');
            $this->table(
                ['Code', 'SKU/Barcode', 'List CAD', 'Unit Price', 'Sub-Category'],
                array_map(fn($p) => [
                    $p[0], $p[1],
                    '$' . number_format($p[2], 2),
                    '$' . number_format(round($p[2] * self::MARKUP, 2), 2),
                    $p[4],
                ], $this->products)
            );
            return self::SUCCESS;
        }

        // Bootstrap shared resources
        $this->adminUserId = User::where('user_type', 'admin')->value('id') ?? 1;
        $this->brandId     = $this->resolveOrCreateBrand();

        $saved   = 0;
        $skipped = 0;
        $errors  = 0;

        $bar = $this->output->createProgressBar(count($this->products));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->start();

        foreach ($this->products as [$code, $barcode, $listPrice, $shortDesc, $subCatName]) {
            $bar->setMessage($code);

            // Skip if barcode already exists
            if ($this->findExisting($barcode, $code)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                $subCatId  = $this->resolveSubCategory($subCatName);
                $unitPrice = round($listPrice * self::MARKUP, 2);

                // Build full product name: "CODE Short Description"
                $productName = $code . ' ' . $shortDesc;

                // Try to get better name + image from Unilite website
                $imageUrl = '';
                if (!$skipImages) {
                    [$webName, $imageUrl] = $this->fetchFromUnilite($code);
                    if ($webName) {
                        $productName = $webName;
                    }
                    if (!$imageUrl) {
                        $imageUrl = $this->fetchFromFallbackSites($code);
                    }
                }

                $uploadId = $imageUrl ? $this->downloadImage($imageUrl) : 0;
                $imageVal = $uploadId > 0 ? (string) $uploadId : '';

                $slug = $this->uniqueSlug(Str::slug($productName));

                $product = DB::transaction(function () use (
                    $code, $barcode, $shortDesc, $productName,
                    $unitPrice, $subCatId, $imageVal, $slug
                ) {
                    $p = Product::create([
                        'name'                   => $productName,
                        'added_by'               => 'admin',
                        'user_id'                => $this->adminUserId,
                        'category_id'            => $subCatId,
                        'brand_id'               => $this->brandId,
                        'photos'                 => $imageVal,
                        'thumbnail_img'          => $imageVal,
                        'tags'                   => 'Unilite,' . $code,
                        'description'            => $shortDesc,
                        'short_description'      => $shortDesc,
                        'unit_price'             => $unitPrice,
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
                        'discount'               => self::DISCOUNT_PCT,
                        'discount_type'          => 'percent',
                        'shipping_type'          => 'free',
                        'shipping_cost'          => 0,
                        'is_quantity_multiplied' => 0,
                        'num_of_sale'            => 0,
                        'meta_title'             => $productName,
                        'meta_description'       => $shortDesc,
                        'meta_img'               => $imageVal,
                        'slug'                   => $slug,
                        'barcode'                => $barcode,
                        'digital'                => 0,
                        'auction_product'        => 0,
                        'wholesale_product'      => 0,
                        'rating'                 => 0,
                        'refundable'             => 1,
                    ]);

                    // Stock record
                    ProductStock::create([
                        'product_id' => $p->id,
                        'variant'    => '',
                        'price'      => $unitPrice,
                        'sku'        => $barcode,
                        'qty'        => 10,
                        'image'      => $imageVal ?: null,
                    ]);

                    // Translation
                    $lang = env('DEFAULT_LANGUAGE', 'en');
                    ProductTranslation::create([
                        'lang'              => $lang,
                        'product_id'        => $p->id,
                        'name'              => $productName,
                        'unit'              => 'pcs',
                        'description'       => $shortDesc,
                        'short_description' => $shortDesc,
                    ]);

                    // Assign to both main category (Unilite 152) and sub-category
                    $pivotRows = [['product_id' => $p->id, 'category_id' => self::MAIN_CAT_ID]];
                    if ($subCatId !== self::MAIN_CAT_ID) {
                        $pivotRows[] = ['product_id' => $p->id, 'category_id' => $subCatId];
                    }
                    DB::table('product_categories')->insertOrIgnore($pivotRows);

                    return $p;
                });

                $saved++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("[$code] " . $e->getMessage());
            }

            $bar->advance();
            usleep(300000); // 0.3s polite delay between scrapes
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done — Saved: $saved | Skipped (exists): $skipped | Errors: $errors");

        return self::SUCCESS;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveOrCreateBrand(): int
    {
        $brand = DB::table('brands')->where('name', self::BRAND_NAME)->first();
        if ($brand) {
            return $brand->id;
        }
        return DB::table('brands')->insertGetId([
            'name'       => self::BRAND_NAME,
            'slug'       => 'unilite-' . rand(100, 999),
            'logo'       => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveSubCategory(string $name): int
    {
        if (isset($this->subCatCache[$name])) {
            return $this->subCatCache[$name];
        }

        $cat = Category::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('parent_id', self::MAIN_CAT_ID)
            ->first();

        if (!$cat) {
            $id = DB::table('categories')->insertGetId([
                'name'        => $name,
                'slug'        => Str::slug($name) . '-unilite-' . rand(100, 999),
                'parent_id'   => self::MAIN_CAT_ID,
                'level'       => 1,
                'order_level' => 0,
                'digital'     => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            // Add to product_categories so category appears for main Unilite cat hierarchy
        } else {
            $id = $cat->id;
        }

        $this->subCatCache[$name] = $id;
        return $id;
    }

    private function findExisting(string $barcode, string $code): bool
    {
        if (Product::where('barcode', $barcode)->exists()) return true;
        return Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($code) . '%'])->exists();
    }

    private function uniqueSlug(string $base): string
    {
        $slug  = $base;
        $count = Product::where('slug', 'LIKE', $slug . '%')->count();
        return $count ? $slug . '-' . ($count + 1) : $slug;
    }

    /**
     * Try to fetch product name and main image from unilite.co.uk.
     * Returns [name|null, imageUrl|''].
     */
    private function fetchFromUnilite(string $code): array
    {
        $slug = strtolower(str_replace(['+', '*', ' '], ['-plus', '', '-'], $code));
        $urlsToTry = [
            "https://unilite.co.uk/product/{$slug}/",
            "https://unilite.co.uk/products/{$slug}/",
        ];

        foreach ($urlsToTry as $url) {
            $html = $this->httpGet($url);
            if (!$html || strlen($html) < 500) continue;

            $name     = $this->extractProductName($html, $code);
            $imageUrl = $this->extractOgImage($html);
            if (!$imageUrl) {
                $imageUrl = $this->extractFirstProductImage($html);
            }

            if ($name || $imageUrl) {
                return [$name, $imageUrl];
            }
        }

        return [null, ''];
    }

    /**
     * Try fallback sites (yesss.co.uk, hansler.com, diy.com) to find an image.
     */
    private function fetchFromFallbackSites(string $code): string
    {
        $sites = [
            "https://www.yesss.co.uk/search?q=" . urlencode('Unilite ' . $code),
            "https://www.hansler.com/search?q=" . urlencode('Unilite ' . $code),
            "https://www.diy.com/search?term="  . urlencode('Unilite ' . $code),
        ];

        foreach ($sites as $url) {
            $html = $this->httpGet($url);
            if (!$html) continue;

            $img = $this->extractFirstProductImage($html);
            if ($img) return $img;
        }

        return '';
    }

    private function extractProductName(string $html, string $code): ?string
    {
        // Try og:title first
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m) ||
            preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']/', $html, $m)) {
            $title = trim(preg_replace('/\s*\|\s*.+$/', '', $m[1])); // strip " | Site Name"
            if ($title && stripos($title, $code) !== false) {
                return $title;
            }
        }

        // Try <h1>
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $m)) {
            $h1 = trim(strip_tags($m[1]));
            if ($h1 && strlen($h1) > 3 && strlen($h1) < 120) {
                return $h1;
            }
        }

        return null;
    }

    private function extractOgImage(string $html): string
    {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m) ||
            preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    private function extractFirstProductImage(string $html): string
    {
        // Look for product images — common patterns
        $patterns = [
            '/<img[^>]+class=["\'][^"\']*product[^"\']*["\'][^>]+src=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/i',
            '/<img[^>]+src=["\']([^"\']+\.(jpg|jpeg|png|webp))["\'][^>]+class=["\'][^"\']*product[^"\']*["\']/i',
            '/data-src=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/i',
        ];
        foreach ($patterns as $pat) {
            if (preg_match($pat, $html, $m)) {
                $src = $m[1];
                if (strpos($src, 'http') !== 0) continue;
                return $src;
            }
        }
        return '';
    }

    private function httpGet(string $url): string
    {
        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout'       => 10,
                    'user_agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'ignore_errors' => true,
                    'header'        => "Accept: text/html,application/xhtml+xml\r\nAccept-Language: en-GB,en;q=0.9\r\n",
                ],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            return (string) @file_get_contents($url, false, $ctx);
        } catch (\Throwable) {
            return '';
        }
    }

    private function downloadImage(string $url): int
    {
        if (empty($url) || preg_match('#(127\.0\.0\.1|localhost)#i', $url)) return 0;

        $ext    = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
        $binary = $this->httpGet($url);

        if (empty($binary) || strlen($binary) < 500) return 0;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($binary);
        if (!str_starts_with($mime, 'image/')) return 0;

        $dir = public_path('uploads/all');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'unilite_import_' . uniqid() . '.' . $ext;
        file_put_contents($dir . '/' . $filename, $binary);

        return Upload::insertGetId([
            'file_name'          => 'uploads/all/' . $filename,
            'file_original_name' => Str::limit(pathinfo($filename, PATHINFO_FILENAME), 240),
            'user_id'            => $this->adminUserId,
            'file_size'          => strlen($binary),
            'extension'          => $ext,
            'type'               => 'image',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }
}
