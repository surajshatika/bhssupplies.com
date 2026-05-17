<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_runs')) {
            return;
        }

        Schema::create('seo_runs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id')->nullable()->index();
            $t->string('module', 50);
            $t->string('feature', 100);
            $t->string('provider', 50)->nullable();
            $t->string('status', 30)->default('queued')->index();
            $t->string('target_type', 50)->nullable();
            $t->unsignedBigInteger('target_id')->nullable();
            $t->string('url', 2048)->nullable();
            $t->longText('input_payload')->nullable();
            $t->longText('result_payload')->nullable();
            $t->text('error_message')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();

            $t->index(['target_type', 'target_id'], 'seo_runs_target_index');
            $t->index(['module', 'status'], 'seo_runs_module_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_runs');
    }
};
