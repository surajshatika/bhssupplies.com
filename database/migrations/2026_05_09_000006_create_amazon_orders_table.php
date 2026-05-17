<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('amazon_order_id', 100)->unique();
            $table->string('status', 50)->default('Pending');
            $table->string('buyer_email', 255)->nullable();
            $table->string('buyer_name', 255)->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('CAD');
            $table->json('order_items')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('amazon_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_orders');
    }
};
