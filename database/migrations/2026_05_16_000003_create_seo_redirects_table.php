<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_redirects')) {
            return;
        }

        Schema::create('seo_redirects', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('source_url', 191)->unique();
            $t->string('target_url', 191);
            $t->unsignedSmallInteger('status_code')->default(301);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('hit_count')->default(0);
            $t->timestamp('last_hit_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->index(['is_active', 'source_url'], 'seo_redirects_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_redirects');
    }
};
