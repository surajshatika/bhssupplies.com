<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 40)->nullable()->index();
            $table->string('provider', 40)->nullable()->index();
            $table->string('template_id', 100)->nullable();
            $table->string('context', 80)->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('message_preview', 500)->nullable();
            $table->text('response')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
