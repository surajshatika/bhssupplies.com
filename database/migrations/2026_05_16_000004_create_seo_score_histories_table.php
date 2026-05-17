<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_score_histories')) {
            $this->ensureTargetColumns();
            return;
        }

        Schema::create('seo_score_histories', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id')->nullable();
            $t->unsignedBigInteger('seo_run_id')->nullable();
            $t->string('target_type', 50)->nullable();
            $t->unsignedBigInteger('target_id')->nullable();
            $t->string('url', 2048)->nullable();
            $t->unsignedInteger('score')->default(0);
            $t->string('grade', 4)->default('F');
            $t->longText('metrics')->nullable();
            $t->timestamp('recorded_at')->nullable();
            $t->timestamps();

            $t->index(['project_id', 'recorded_at'], 'seo_score_histories_project_id_recorded_at_index');
            $t->index(['target_type', 'target_id'], 'seo_score_histories_target_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_score_histories');
    }

    protected function ensureTargetColumns(): void
    {
        // Existing prod tables predate target_type/target_id — add them in-place.
        if (!Schema::hasColumn('seo_score_histories', 'target_type')) {
            Schema::table('seo_score_histories', function (Blueprint $t) {
                $t->string('target_type', 50)->nullable()->after('seo_run_id');
                $t->unsignedBigInteger('target_id')->nullable()->after('target_type');
                $t->index(['target_type', 'target_id'], 'seo_score_histories_target_index');
            });
        }
    }
};
