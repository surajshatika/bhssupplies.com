<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_generated_images')) {
            return;
        }

        Schema::create('seo_generated_images', function (Blueprint $t) {
            $t->bigIncrements('id');

            $t->unsignedBigInteger('user_id')->nullable();

            $t->string('keyword', 255)->nullable();
            $t->text('prompt')->nullable();
            $t->text('revised_prompt')->nullable();

            $t->string('style', 120)->nullable();
            $t->string('purpose', 40)->nullable();
            $t->string('size', 20)->nullable();
            $t->string('quality', 20)->default('standard');

            $t->text('source_url')->nullable();   // provider URL (expires)
            $t->string('local_url', 500)->nullable(); // permanent media-library URL
            $t->unsignedBigInteger('upload_id')->nullable(); // Upload row when saved to library

            $t->string('alt_text', 255)->nullable();
            $t->string('filename', 191)->nullable();

            $t->timestamps();

            $t->index('user_id', 'seo_gen_images_user_index');
            $t->index('created_at', 'seo_gen_images_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_generated_images');
    }
};
