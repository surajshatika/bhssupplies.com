<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('account_id');
            $table->string('amazon_sku', 191)->unique();
            $table->string('asin', 20)->nullable();
            $table->enum('status', ['pending', 'active', 'inactive', 'error'])->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('amazon_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_products');
    }
};
