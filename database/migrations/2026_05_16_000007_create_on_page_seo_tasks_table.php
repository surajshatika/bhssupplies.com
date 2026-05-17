<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('on_page_seo_tasks')) {
            return;
        }

        Schema::create('on_page_seo_tasks', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('page_id')->index();
            $t->unsignedBigInteger('seo_run_id')->nullable()->index();
            $t->string('feature', 80);
            $t->string('status', 30)->default('pending');
            $t->longText('result_payload')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('on_page_seo_tasks');
    }
};
