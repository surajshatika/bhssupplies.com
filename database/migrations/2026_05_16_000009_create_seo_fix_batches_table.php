<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_fix_batches')) {
            return;
        }

        Schema::create('seo_fix_batches', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id')->nullable()->index();
            $t->string('label', 191)->nullable();
            $t->string('status', 30)->default('queued')->index();
            $t->string('provider', 50)->nullable();

            $t->unsignedInteger('total')->default(0);
            $t->unsignedInteger('processed')->default(0);
            $t->unsignedInteger('succeeded')->default(0);
            $t->unsignedInteger('failed')->default(0);
            $t->unsignedInteger('skipped')->default(0);

            $t->string('current_label', 191)->nullable();

            $t->decimal('estimated_cost_usd', 8, 4)->nullable();
            $t->decimal('actual_cost_usd', 8, 4)->default(0);

            $t->longText('target_ids')->nullable();   // JSON: [{type,id}, ...]
            $t->longText('options')->nullable();       // JSON: filter selections, fix rules
            $t->longText('error_log')->nullable();

            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_fix_batches');
    }
};
