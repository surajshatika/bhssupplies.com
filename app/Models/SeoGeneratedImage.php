<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One record per AI-generated image — powers the AI Image Generator's
 * revision history and links the image to its media-library Upload.
 */
class SeoGeneratedImage extends Model
{
    protected $table = 'seo_generated_images';

    protected $guarded = [];

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }
}
