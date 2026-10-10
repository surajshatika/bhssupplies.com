<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_backlinks')) {
            return;
        }

        Schema::create('seo_backlinks', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('domain')->index();
            // citation | directory | association | supplier | press | blog | other
            $table->string('type', 30)->default('other');
            // prospect | contacted | submitted | live | lost | rejected
            $table->string('status', 20)->default('prospect')->index();
            $table->string('submit_url', 500)->nullable();   // where to sign up / pitch
            $table->string('source_url', 500)->nullable();   // page that (should) link to us
            $table->string('target_url', 500)->nullable();   // our page it links to
            $table->string('anchor_text')->nullable();
            $table->string('rel', 40)->nullable();            // follow | nofollow | sponsored | ugc
            $table->string('contact')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 20)->default('manual');  // manual | seed | gsc_import
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->timestamp('first_seen_live_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamps();
            $table->unique(['domain', 'source_url'], 'seo_backlinks_domain_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_backlinks');
    }
};
