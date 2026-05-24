<?php
/**
 * Exports product-name → image-file mapping for Excalibur products.
 * Run locally, then use the output SQL on the live server.
 * DELETE after use.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$products = DB::table('products as p')
    ->join('uploads as u', 'u.id', '=', 'p.thumbnail_img')
    ->whereBetween('p.id', [1691, 1857])
    ->whereNotNull('p.thumbnail_img')
    ->select('p.name', 'p.slug', 'u.file_name', 'u.file_size', 'u.extension')
    ->get();

$adminId = DB::table('users')->where('user_type', 'admin')->value('id') ?? 1;

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="apply_excalibur_images.sql"');

echo "-- Excalibur Image Assignment SQL\n";
echo "-- Run on LIVE server after git pull\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($products as $p) {
    $name     = addslashes($p->name);
    $slug     = addslashes($p->slug ?? '');
    $file     = addslashes($p->file_name);
    $ext      = addslashes($p->extension ?? 'jpg');
    $size     = (int)$p->file_size;
    $now      = date('Y-m-d H:i:s');

    echo "-- {$p->name}\n";
    echo "SET @uid = (SELECT id FROM uploads WHERE file_name = '{$file}' LIMIT 1);\n";
    echo "SET @uid = IFNULL(@uid, (SELECT LAST_INSERT_ID() FROM (SELECT 1) t WHERE (SELECT ROW_COUNT()) = 0));\n";
    echo "INSERT IGNORE INTO uploads (file_original_name, file_name, user_id, extension, type, file_size, created_at, updated_at) VALUES ('{$name}', '{$file}', {$adminId}, '{$ext}', 'image', {$size}, '{$now}', '{$now}');\n";
    echo "SET @uid = IFNULL(@uid, LAST_INSERT_ID());\n";
    echo "UPDATE products SET thumbnail_img = @uid, photos = @uid WHERE name = '{$name}' AND (thumbnail_img IS NULL OR thumbnail_img != @uid);\n\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
echo "-- Done: {$products->count()} products\n";
