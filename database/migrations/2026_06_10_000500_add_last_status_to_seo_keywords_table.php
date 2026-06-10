<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_keywords', function (Blueprint $table) {
            if (!Schema::hasColumn('seo_keywords', 'last_status')) {
                $table->string('last_status')->nullable()->after('last_checked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_keywords', function (Blueprint $table) {
            if (Schema::hasColumn('seo_keywords', 'last_status')) {
                $table->dropColumn('last_status');
            }
        });
    }
};
