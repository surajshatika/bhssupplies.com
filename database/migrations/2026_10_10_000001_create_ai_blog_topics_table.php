<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tracks which categories/products already have an AI blog post, so the daily
// generator works through every category, then every product, without repeats.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_blog_topics')) {
            return;
        }
        Schema::create('ai_blog_topics', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 20);          // category | product
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('blog_id')->nullable();
            $table->string('status', 20)->default('done'); // done | failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_blog_topics');
    }
};
