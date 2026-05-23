<?php
/**
 * Excalibur Products – SQL Export Script
 * Run this on LOCAL to download SQL, then import on live via phpMyAdmin.
 * DELETE this file from the server after use.
 */

$envPath = __DIR__ . '/../.env';
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}
$dsn = 'mysql:host=' . ($env['DB_HOST'] ?? 'localhost') . ';dbname=' . ($env['DB_DATABASE'] ?? 'bhssupplies1') . ';port=' . ($env['DB_PORT'] ?? '3306');
$pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="excalibur_products_export.sql"');

$catIds   = range(170, 184);
$prodIds  = range(1691, 1857);

echo "-- Excalibur Products Export\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Run on live server AFTER git pull\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

// ── Categories ───────────────────────────────────────────────────────────────
echo "-- CATEGORIES\n";
$rows = $pdo->query("SELECT * FROM categories WHERE id IN (" . implode(',', $catIds) . ")")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
    $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($r)));
    echo "INSERT IGNORE INTO `categories` ({$cols}) VALUES ({$vals});\n";
}
echo "\n";

// ── Products (thumbnail cleared – images already committed to git, uploads will be re-linked) ──
echo "-- PRODUCTS\n";
$rows = $pdo->query("SELECT * FROM products WHERE id IN (" . implode(',', $prodIds) . ")")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
    $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($r)));
    echo "INSERT IGNORE INTO `products` ({$cols}) VALUES ({$vals});\n";
}
echo "\n";

// ── Product Translations ─────────────────────────────────────────────────────
echo "-- PRODUCT TRANSLATIONS\n";
$rows = $pdo->query("SELECT * FROM product_translations WHERE product_id IN (" . implode(',', $prodIds) . ")")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
    $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($r)));
    echo "INSERT IGNORE INTO `product_translations` ({$cols}) VALUES ({$vals});\n";
}
echo "\n";

// ── Product Stocks ───────────────────────────────────────────────────────────
echo "-- PRODUCT STOCKS\n";
$rows = $pdo->query("SELECT * FROM product_stocks WHERE product_id IN (" . implode(',', $prodIds) . ")")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
    $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($r)));
    echo "INSERT IGNORE INTO `product_stocks` ({$cols}) VALUES ({$vals});\n";
}
echo "\n";

// ── Product Categories (junction) ────────────────────────────────────────────
echo "-- PRODUCT CATEGORIES\n";
$rows = $pdo->query("SELECT * FROM product_categories WHERE product_id IN (" . implode(',', $prodIds) . ")")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
    $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($r)));
    echo "INSERT IGNORE INTO `product_categories` ({$cols}) VALUES ({$vals});\n";
}
echo "\n";

// ── Uploads (image file records) ─────────────────────────────────────────────
echo "-- UPLOADS (image records for products above)\n";
$uploadIds = $pdo->query("
    SELECT thumbnail_img FROM products
    WHERE id IN (" . implode(',', $prodIds) . ")
    AND thumbnail_img IS NOT NULL
")->fetchAll(PDO::FETCH_COLUMN);

if ($uploadIds) {
    $rows = $pdo->query("SELECT * FROM uploads WHERE id IN (" . implode(',', $uploadIds) . ")")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
        $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($r)));
        echo "INSERT IGNORE INTO `uploads` ({$cols}) VALUES ({$vals});\n";
    }
}
echo "\n";

echo "SET FOREIGN_KEY_CHECKS=1;\n";
echo "\n-- Export complete. " . count($prodIds) . " products, " . count($catIds) . " categories.\n";
