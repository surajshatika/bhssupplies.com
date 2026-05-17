<?php

namespace App\Services\Amazon;

use App\Models\AmazonCategoryMapping;
use App\Models\Product;
use App\Models\Upload;

class AmazonProductMapper
{
    public function toListingPayload(Product $product): array
    {
        $mapping  = AmazonCategoryMapping::where('website_category_id', $product->category_id)->first();
        $brandName = optional($product->brand)->name ?? config('amazon.default_brand');

        return [
            'productType' => $mapping?->amazon_product_type ?? 'PRODUCT',
            'attributes'  => [
                'item_name'        => [['value' => $product->getTranslation('name', 'en') ?: $product->name]],
                'brand'            => [['value' => $brandName]],
                'list_price'       => [['value' => (float) $product->unit_price, 'currency' => 'CAD']],
                'fulfillment_availability' => [[
                    'fulfillment_channel_code' => 'DEFAULT',
                    'quantity'                 => max(0, (int) $product->current_stock),
                ]],
                'item_package_weight' => [[
                    'value' => (float) $product->weight,
                    'unit'  => 'kilograms',
                ]],
                'product_description' => [['value' => strip_tags($product->description ?? '')]],
                'bullet_point'       => $this->buildBulletPoints($product),
                'main_product_image_locator' => $this->buildImages($product),
            ],
        ];
    }

    public function buildSku(Product $product): string
    {
        if ($product->barcode) {
            return $product->barcode;
        }

        return config('amazon.sku_prefix') . $product->id;
    }

    public function toPricePayload(Product $product): array
    {
        return [
            'productType' => 'PRODUCT',
            'patches'     => [[
                'op'    => 'replace',
                'path'  => '/attributes/list_price',
                'value' => [['value' => (float) $product->unit_price, 'currency' => 'CAD']],
            ]],
        ];
    }

    public function toInventoryPayload(Product $product): array
    {
        return [
            'productType' => 'PRODUCT',
            'patches'     => [[
                'op'    => 'replace',
                'path'  => '/attributes/fulfillment_availability',
                'value' => [[
                    'fulfillment_channel_code' => 'DEFAULT',
                    'quantity'                 => max(0, (int) $product->current_stock),
                ]],
            ]],
        ];
    }

    private function buildBulletPoints(Product $product): array
    {
        $points = [];

        if ($product->unit) {
            $points[] = ['value' => 'Unit: ' . $product->unit];
        }
        if ($product->weight) {
            $points[] = ['value' => 'Weight: ' . $product->weight . ' kg'];
        }
        if ($product->tags) {
            $points[] = ['value' => 'Tags: ' . $product->tags];
        }

        return $points ?: [['value' => $product->name]];
    }

    private function buildImages(Product $product): array
    {
        $images = [];

        if ($product->thumbnail_img) {
            $upload = Upload::find($product->thumbnail_img);
            if ($upload) {
                $images[] = ['media_location' => uploaded_asset($upload->file_name)];
            }
        }

        return $images;
    }
}
