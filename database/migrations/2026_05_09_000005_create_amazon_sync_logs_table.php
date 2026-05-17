<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('amazon_product_id')->nullable();
            $table->enum('action', ['upload', 'price_sync', 'inventory_sync', 'order_import', 'deactivate']);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('amazon_product_id')->references('id')->on('amazon_products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_sync_logs');
    }
};
