<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_meta')) {
            return;
        }

        Schema::create('seo_meta', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Polymorphic owner — Product, Category, Page, Blog, etc.
            $t->string('model_type', 191);
            $t->unsignedBigInteger('model_id');

            $t->string('lang', 8)->default('en');

            // Search snippet
            $t->string('meta_title', 191)->nullable();
            $t->text('meta_description')->nullable();
            $t->string('focus_keyword', 191)->nullable();
            $t->text('secondary_keywords')->nullable();

            // Indexability
            $t->string('canonical_url', 500)->nullable();
            $t->string('robots_meta', 100)->default('index, follow');

            // Open Graph
            $t->string('og_title', 191)->nullable();
            $t->text('og_description')->nullable();
            $t->string('og_image', 500)->nullable();
            $t->string('og_type', 30)->default('website');

            // Twitter Card
            $t->string('twitter_card', 30)->default('summary_large_image');
            $t->string('twitter_title', 191)->nullable();
            $t->text('twitter_description')->nullable();
            $t->string('twitter_image', 500)->nullable();

            // Structured data
            $t->longText('schema_json')->nullable();
            $t->longText('breadcrumbs_json')->nullable();

            // Score cache
            $t->decimal('seo_score', 5, 2)->nullable();
            $t->string('seo_grade', 2)->nullable();
            $t->longText('analysis_checks')->nullable();
            $t->timestamp('last_analyzed_at')->nullable();

            $t->timestamps();

            $t->unique(['model_type', 'model_id', 'lang'], 'seo_meta_morph_lang_unique');
            $t->index('seo_score', 'seo_meta_score_index');
            $t->index('focus_keyword', 'seo_meta_focus_keyword_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
    }
};
