<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_settings')
            ->where('type', 'uploaded_image_format')
            ->update(['value' => 'webp']);

        // If the row doesn't exist yet, insert it
        if (!DB::table('business_settings')->where('type', 'uploaded_image_format')->exists()) {
            DB::table('business_settings')->insert([
                'type'  => 'uploaded_image_format',
                'value' => 'webp',
                'lang'  => 'default',
            ]);
        }

        // Clear business_settings cache so get_setting() picks up the new value
        \Illuminate\Support\Facades\Cache::forget('business_settings');
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->where('type', 'uploaded_image_format')
            ->update(['value' => 'default']);

        \Illuminate\Support\Facades\Cache::forget('business_settings');
    }
};
