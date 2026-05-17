<?php

namespace App\Services\Amazon;

use App\Models\Product;
use App\Models\Upload;

class AmazonImageService
{
    public function getProductImages(Product $product): array
    {
        $images = [];

        if ($product->thumbnail_img) {
            $upload = Upload::find($product->thumbnail_img);
            if ($upload) {
                $images['main'] = uploaded_asset($upload->file_name);
            }
        }

        if ($product->photos) {
            $photoIds = explode(',', $product->photos);
            foreach (array_slice($photoIds, 0, 8) as $photoId) {
                $upload = Upload::find(trim($photoId));
                if ($upload) {
                    $images['gallery'][] = uploaded_asset($upload->file_name);
                }
            }
        }

        return $images;
    }

    public function getMainImageUrl(Product $product): ?string
    {
        if (!$product->thumbnail_img) {
            return null;
        }

        $upload = Upload::find($product->thumbnail_img);
        return $upload ? uploaded_asset($upload->file_name) : null;
    }
}
