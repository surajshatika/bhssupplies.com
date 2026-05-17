<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('website_category_id');
            $table->string('amazon_category_id', 100)->nullable();
            $table->string('amazon_category_name', 255)->nullable();
            $table->string('amazon_product_type', 100)->nullable();
            $table->timestamps();

            $table->unique('website_category_id');
            $table->foreign('website_category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_category_mappings');
    }
};
