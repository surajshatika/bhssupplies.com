<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_analytics')) {
            return;
        }

        Schema::create('seo_analytics', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('row_hash', 40)->unique();     // sha1(date|source|dimension|value)
            $t->date('date');
            $t->string('source', 32)->default('gsc'); // gsc | ga4 | bing
            $t->string('dimension', 32);              // query | page | country | device
            $t->string('value', 500);
            $t->unsignedInteger('clicks')->default(0);
            $t->unsignedInteger('impressions')->default(0);
            $t->decimal('ctr', 6, 4)->nullable();
            $t->decimal('position', 8, 2)->nullable();
            $t->timestamps();

            $t->index(['date', 'source', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_analytics');
    }
};
