<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_queued_posts', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->foreignId('campaign_id')->nullable()->constrained('social_campaigns')->onDelete('set null');
            $table->longText('content');
            $table->json('options')->nullable(); // image_url, link, hashtags, etc.
            $table->string('status')->default('pending'); // pending, processing, sent, failed, cancelled
            $table->string('trigger')->default('manual');
            $table->string('ai_provider')->nullable();
            $table->integer('priority')->default(5); // 1=highest, 10=lowest
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_queued_posts');
    }
};
