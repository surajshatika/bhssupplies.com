<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_analytics', function (Blueprint $table) {
            if (!Schema::hasColumn('seo_analytics', 'row_hash')) {
                $table->string('row_hash', 40)->nullable()->after('value');
                $table->unique('row_hash', 'seo_analytics_row_hash_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_analytics', function (Blueprint $table) {
            if (Schema::hasColumn('seo_analytics', 'row_hash')) {
                $table->dropUnique('seo_analytics_row_hash_unique');
                $table->dropColumn('row_hash');
            }
        });
    }
};
