<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The original create migration declared pair_hash, but the table had
// already been created by an earlier migration and was skipped behind a
// hasTable() guard — so seo:check-broken-links failed on every run.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('seo_broken_links') || Schema::hasColumn('seo_broken_links', 'pair_hash')) {
            return;
        }

        Schema::table('seo_broken_links', function (Blueprint $table) {
            $table->string('pair_hash', 40)->nullable()->after('id');
        });

        DB::table('seo_broken_links')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('seo_broken_links')->where('id', $row->id)
                    ->update(['pair_hash' => sha1($row->source_url . '|' . $row->target_url)]);
            }
        });

        // Keep the newest row per pair before enforcing uniqueness.
        $dupes = DB::table('seo_broken_links')->select('pair_hash', DB::raw('MAX(id) as keep_id'))
            ->groupBy('pair_hash')->havingRaw('COUNT(*) > 1')->get();
        foreach ($dupes as $d) {
            DB::table('seo_broken_links')->where('pair_hash', $d->pair_hash)->where('id', '!=', $d->keep_id)->delete();
        }

        Schema::table('seo_broken_links', function (Blueprint $table) {
            $table->unique('pair_hash');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('seo_broken_links', 'pair_hash')) {
            Schema::table('seo_broken_links', function (Blueprint $table) {
                $table->dropUnique(['pair_hash']);
                $table->dropColumn('pair_hash');
            });
        }
    }
};
