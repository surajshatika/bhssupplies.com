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

class WaterSoftenersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('user_type', 'admin')->first();
        $adminId = $admin->id ?? 1;

        // ── 1. Create WATER SOFTENERS category ────────────────────────────
        $waterSoftenersCategory = Category::whereRaw('LOWER(name) = ?', ['water softeners'])->first();
        if (!$waterSoftenersCategory) {
            $catId = DB::table('categories')->insertGetId([
                'name'        => 'WATER SOFTENERS',
                'slug'        => 'water-softeners-' . rand(100, 999),
                'parent_id'   => 0,
                'level'       => 0,
                'order_level' => 0,
                'digital'     => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $waterSoftenersCategory = Category::find($catId);
        }

        $this->command->info("WATER SOFTENERS category ID: {$waterSoftenersCategory->id}");

        // ── 2. Create Mineral Tanks variable product ───────────────────────
        $productName = 'Mineral Tanks Black 2.5" Top Opening NSF Certified';

        // Skip if already exists
        if (Product::whereRaw('LOWER(name) = ?', [strtolower($productName)])->exists()) {
            $this->command->warn("Product already exists: {$productName}");
            return;
        }

        $variants = [
            ['label' => 'Tank 8x44',  'sku' => 'WTM0844B25', 'price' => 220.00, 'qty' => 10],
            ['label' => 'Tank 9x48',  'sku' => 'WTM0948B25', 'price' => 282.50, 'qty' => 10],
            ['label' => 'Tank 10x35', 'sku' => 'WTM1035B25', 'price' => 280.00, 'qty' => 10],
            ['label' => 'Tank 10x44', 'sku' => 'WTM1044B25', 'price' => 305.00, 'qty' => 10],
            ['label' => 'Tank 10x54', 'sku' => 'WTM1054B25', 'price' => 133.00, 'qty' => 10],
            ['label' => 'Tank 12x52', 'sku' => 'WTM1252B25', 'price' => 467.50, 'qty' => 10],
        ];

        // Attribute ID 1 = "Size" (already in DB)
        $attributeId  = 1;
        $optionValues = array_column($variants, 'label');

        $choiceOptions = json_encode([
            ['attribute_id' => $attributeId, 'values' => $optionValues]
        ], JSON_UNESCAPED_UNICODE);

        $attributes = json_encode([$attributeId]);

        // Slug (unique)
        $slug = Str::slug($productName);
        $slugCount = Product::where('slug', 'LIKE', $slug . '%')->count();
        if ($slugCount) {
            $slug .= '-' . ($slugCount + 1);
        }

        $minPrice = min(array_column($variants, 'price'));

        $product = Product::create([
            'name'                   => $productName,
            'added_by'               => 'admin',
            'user_id'                => $adminId,
            'category_id'            => $waterSoftenersCategory->id,
            'brand_id'               => null,
            'photos'                 => '',
            'thumbnail_img'          => '',
            'tags'                   => 'mineral tanks,water treatment,NSF certified',
            'description'            => '<p>Excalibur Water Systems Mineral Tanks. Black colour with 2.5" top opening. NSF Certified. Available in multiple sizes.</p>',
            'short_description'      => 'NSF Certified mineral tanks, black, 2.5" top opening. Multiple sizes available.',
            'unit_price'             => $minPrice,
            'purchase_price'         => 0,
            'variant_product'        => 1,
            'attributes'             => $attributes,
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
            'current_stock'          => array_sum(array_column($variants, 'qty')),
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
            'meta_title'             => $productName,
            'meta_description'       => 'Buy Excalibur Mineral Tanks Black 2.5" Top Opening NSF Certified. Available in 8x44, 9x48, 10x35, 10x44, 10x54, 12x52 sizes.',
            'meta_img'               => '',
            'slug'                   => $slug,
            'barcode'                => 'WTM-MINERAL-TANKS',
            'digital'                => 0,
            'auction_product'        => 0,
            'wholesale_product'      => 0,
            'rating'                 => 0,
            'refundable'             => 1,
        ]);

        // ── Create one ProductStock per variant ────────────────────────────
        foreach ($variants as $v) {
            // Variant string: remove spaces from label (matches get_combination_string logic)
            $variantStr = str_replace(' ', '', $v['label']);

            ProductStock::create([
                'product_id' => $product->id,
                'variant'    => str_replace(' ', '', $v['label']),
                'price'      => $v['price'],
                'sku'        => $v['sku'],
                'qty'        => $v['qty'],
                'image'      => null,
            ]);
        }

        // ── product_categories junction ────────────────────────────────────
        DB::table('product_categories')->insertOrIgnore([
            'product_id'  => $product->id,
            'category_id' => $waterSoftenersCategory->id,
        ]);

        // ── Translation ────────────────────────────────────────────────────
        $lang = env('DEFAULT_LANGUAGE', 'en');
        ProductTranslation::create([
            'lang'              => $lang,
            'product_id'        => $product->id,
            'name'              => $productName,
            'unit'              => 'pcs',
            'description'       => '<p>Excalibur Water Systems Mineral Tanks. Black colour with 2.5" top opening. NSF Certified. Available in multiple sizes.</p>',
            'short_description' => 'NSF Certified mineral tanks, black, 2.5" top opening. Multiple sizes available.',
        ]);

        $this->command->info("Created product: {$productName} (ID: {$product->id}) with " . count($variants) . " variants.");
    }
}
