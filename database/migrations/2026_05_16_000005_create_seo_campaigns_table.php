<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_campaigns')) {
            return;
        }

        Schema::create('seo_campaigns', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id')->index();
            $t->string('name', 191);
            $t->string('type', 50)->default('off_page');
            $t->string('status', 30)->default('draft');
            $t->longText('settings_json')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_campaigns');
    }
};
