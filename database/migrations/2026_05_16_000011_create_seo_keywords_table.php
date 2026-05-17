<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_keywords')) {
            return;
        }

        Schema::create('seo_keywords', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id')->nullable()->index();
            $t->string('keyword', 191);
            $t->string('target_url', 500)->nullable();
            $t->string('country', 8)->default('us');
            $t->string('device', 16)->default('desktop');     // desktop | mobile
            $t->string('engine', 16)->default('google');

            $t->unsignedSmallInteger('rank_current')->nullable();
            $t->unsignedSmallInteger('rank_previous')->nullable();
            $t->unsignedSmallInteger('rank_best')->nullable();
            $t->unsignedSmallInteger('rank_worst')->nullable();

            $t->unsignedInteger('search_volume')->nullable();
            $t->decimal('difficulty', 5, 2)->nullable();
            $t->decimal('cpc_usd', 8, 4)->nullable();

            $t->boolean('is_active')->default(true);
            $t->timestamp('last_checked_at')->nullable();
            $t->json('history')->nullable();
            $t->timestamps();

            $t->unique(['project_id', 'keyword', 'country', 'device'], 'seo_keywords_uniq');
            $t->index('rank_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_keywords');
    }
};
