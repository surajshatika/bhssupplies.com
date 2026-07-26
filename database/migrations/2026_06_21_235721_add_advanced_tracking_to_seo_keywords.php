<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seo_keywords', function (Blueprint $table) {
            $table->string('keyword_intent')->nullable()->after('search_volume'); // Navigational, Informational, Transactional
            $table->string('cluster')->nullable()->after('keyword_intent'); // Category or Topic Tag
            $table->json('serp_features')->nullable()->after('history'); // Featured Snippets, Local Pack, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_keywords', function (Blueprint $table) {
            $table->dropColumn(['keyword_intent', 'cluster', 'serp_features']);
        });
    }
};
