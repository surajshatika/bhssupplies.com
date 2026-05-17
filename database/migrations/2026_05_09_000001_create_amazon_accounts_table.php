<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('seller_id', 100);
            $table->string('marketplace_id', 50)->default('A2EUQ1WTGCTBG2');
            $table->text('lwa_client_id');
            $table->text('lwa_client_secret');
            $table->text('aws_access_key')->nullable();
            $table->text('aws_secret_key')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_accounts');
    }
};
