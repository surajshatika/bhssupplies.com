<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_projects')) {
            return;
        }

        Schema::create('seo_projects', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 191);
            $t->string('slug', 191)->unique();
            $t->string('base_url', 191)->nullable();
            $t->string('default_provider', 191)->default('openai');
            $t->longText('settings_json')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_projects');
    }
};
