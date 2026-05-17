<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_post_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50);
            $table->string('trigger', 50)->default('manual');
            $table->string('campaign_id', 50)->nullable();
            $table->string('ai_provider', 30)->nullable();
            $table->longText('content_sent');
            $table->string('status', 30);
            $table->text('response')->nullable();
            $table->string('post_id', 191)->nullable();
            $table->string('post_url', 191)->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_logs');
    }
};
