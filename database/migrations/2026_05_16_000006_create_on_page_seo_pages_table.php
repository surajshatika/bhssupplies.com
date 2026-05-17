<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('on_page_seo_pages')) {
            return;
        }

        Schema::create('on_page_seo_pages', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id')->nullable()->index();
            $t->string('url', 191);
            $t->string('title', 191)->nullable();
            $t->string('focus_keyword', 191)->nullable();
            $t->text('meta_description')->nullable();
            $t->string('h1', 191)->nullable();
            $t->unsignedInteger('word_count')->nullable();
            $t->unsignedInteger('images_count')->nullable();
            $t->unsignedInteger('internal_links_count')->nullable();
            $t->decimal('seo_score', 5, 2)->nullable();
            $t->string('seo_grade', 2)->nullable();
            $t->string('status', 30)->default('pending');
            $t->string('provider', 50)->nullable();
            $t->longText('input_payload')->nullable();
            $t->longText('result_payload')->nullable();
            $t->timestamp('last_audited_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('on_page_seo_pages');
    }
};
