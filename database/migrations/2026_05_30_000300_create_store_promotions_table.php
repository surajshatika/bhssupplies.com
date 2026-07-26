<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_promotions')) {
            return;
        }

        Schema::create('store_promotions', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Block type: 'banner' (image tile) or 'content' (rich HTML block).
            $t->string('type', 20)->default('banner');

            $t->string('title')->nullable();
            $t->string('subtitle')->nullable();      // badge, e.g. "UP TO 80% OFF"
            $t->string('image')->nullable();          // Upload id (aiz uploader) — banner type
            $t->longText('content')->nullable();      // rich HTML — content type
            $t->string('link_url', 500)->nullable();  // optional click-through URL
            $t->string('section', 80)->nullable();    // optional grouping label
            $t->string('width', 12)->default('full'); // full | half | third — tile width

            $t->integer('sort_order')->default(0);    // top-to-bottom order (drag & drop)
            $t->boolean('published')->default(true);
            $t->boolean('featured')->default(false);  // highlighted tile
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();

            $t->timestamps();

            $t->index(['published', 'sort_order'], 'store_promo_pub_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_promotions');
    }
};
