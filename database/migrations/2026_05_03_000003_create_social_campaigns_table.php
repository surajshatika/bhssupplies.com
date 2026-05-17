<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('custom'); // product_launch, sale_promotion, etc.
            $table->string('status')->default('draft'); // draft, active, paused, completed, archived
            $table->string('ai_provider')->default('openai');
            $table->string('tone')->default('professional');
            $table->text('topic')->nullable();
            $table->text('keywords')->nullable();
            $table->text('target_audience')->nullable();
            $table->json('platforms')->nullable(); // array of platform slugs
            $table->json('generated_content')->nullable(); // per-platform content cache
            $table->boolean('auto_post')->default(false);
            $table->string('schedule_type')->default('once'); // once, recurring
            $table->string('cron_expression')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('last_posted_at')->nullable();
            $table->integer('post_count')->default(0);
            $table->integer('total_reach')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_campaigns');
    }
};
