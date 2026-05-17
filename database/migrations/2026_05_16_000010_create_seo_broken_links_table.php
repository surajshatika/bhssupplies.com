<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_broken_links')) {
            return;
        }

        Schema::create('seo_broken_links', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('pair_hash', 40)->unique();   // sha1(source_url|target_url)
            $t->string('source_url', 500);
            $t->string('target_url', 500);
            $t->unsignedSmallInteger('status_code')->nullable();
            $t->string('state', 20)->default('broken');   // broken | ok | timeout | resolved | ignored
            $t->unsignedInteger('hit_count')->default(1);
            $t->timestamp('first_seen_at')->nullable();
            $t->timestamp('last_checked_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->index('state');
            $t->index('last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_broken_links');
    }
};
