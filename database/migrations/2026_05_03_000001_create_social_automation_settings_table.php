<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_automation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->enum('type', ['string', 'boolean', 'json', 'integer', 'secret'])->default('string');
            $table->string('group')->default('general'); // platform slug or 'general' / 'ai'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_automation_settings');
    }
};
