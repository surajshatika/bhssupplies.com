<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('store_promotions', 'show_badge')) {
                $table->boolean('show_badge')->default(true)->after('subtitle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_promotions', function (Blueprint $table) {
            if (Schema::hasColumn('store_promotions', 'show_badge')) {
                $table->dropColumn('show_badge');
            }
        });
    }
};
