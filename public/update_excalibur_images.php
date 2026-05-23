<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

// Read credentials from .env
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
echo "Connected to DB: " . ($env['DB_DATABASE'] ?? 'bhssupplies1') . "\n";

$uploadDir = __DIR__ . '/uploads/all/';
$userId = 9;

// ─── Helpers ────────────────────────────────────────────────────────────────

function curlGet($url, $timeout = 20) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => ['Accept-Language: en-US,en;q=0.9'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function downloadImage($imageUrl, $uploadDir, $slug) {
    $r = curlGet($imageUrl, 30);
    if ($r['code'] != 200 || strlen($r['body']) < 500) return null;
    $ext = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
    $ext = explode('?', $ext)[0];
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) $ext = 'jpg';
    $filename = 'exc_' . preg_replace('/[^a-z0-9]/', '_', strtolower($slug)) . '_' . time() . mt_rand(100,999) . '.' . $ext;
    file_put_contents($uploadDir . $filename, $r['body']);
    return ['filename' => 'uploads/all/' . $filename, 'ext' => $ext, 'size' => strlen($r['body'])];
}

function searchDuckDuckGoImage($query) {
    $url = 'https://duckduckgo.com/?q=' . urlencode($query) . '&iax=images&ia=images';
    $r = curlGet($url, 15);
    if ($r['code'] != 200) return null;
    // Extract vqd token
    preg_match('/vqd=([\d-]+)/', $r['body'], $vqd);
    if (empty($vqd[1])) return null;
    $apiUrl = 'https://duckduckgo.com/i.js?l=us-en&o=json&q=' . urlencode($query) . '&vqd=' . $vqd[1] . '&f=,,,&p=1';
    $api = curlGet($apiUrl, 15);
    if ($api['code'] != 200) return null;
    $data = json_decode($api['body'], true);
    $results = $data['results'] ?? [];
    foreach ($results as $result) {
        $imgUrl = $result['image'] ?? null;
        if ($imgUrl && preg_match('/\.(jpg|jpeg|png|webp)/i', $imgUrl)) {
            return $imgUrl;
        }
    }
    return null;
}

// ─── Image Map ───────────────────────────────────────────────────────────────
// Each entry: keywords (ALL must match product name, case-insensitive), url(s) to try

$base = 'https://residential.excaliburwater.com/wp-content/uploads/2022/10/';
$base11 = 'https://residential.excaliburwater.com/wp-content/uploads/2022/11/';

// Ordered: most specific first, fallback last
$imageMap = [
    // ── Water Softeners (specific series) ──────────────────────────────────
    [['value','softener'],      [$base.'prod-lbl-value-water-softener-1024-spgy-300x300.jpg',       $base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['superior','softener'],   [$base.'prod-lbl-superior-series-water-softener-1024w-spgy-300x300.jpg', $base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['premium','softener'],    [$base.'prod-lbl-premium-series-water-softener-1024w-spgy-300x300.jpg',  $base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['ultimate','softener'],   [$base.'prod-lbl-ultimate-series-water-softener-1024w-spgy-300x300.jpg', $base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['platinum'],              [$base.'platinum-smart-water-softener-1024w-ewsbr-spgy-300x300.jpg', $base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['chlor-a-soft'],          [$base.'prod-lbl-chlor-a-soft-1024w-spgy2-300x300.jpg',              $base.'prod-main-chlor-a-soft-768w-sply.jpg']],
    // ── DROP ────────────────────────────────────────────────────────────────
    [['drop','salt'],           [$base.'drop-salt-sensor-with-hub-1024w-ewsbr-spgy-300x300.jpg']],
    [['drop'],                  [$base.'drop-salt-sensor-with-hub-1024w-ewsbr-spgy-300x300.jpg']],
    // ── Soft-Tec ────────────────────────────────────────────────────────────
    [['soft-tec'],              [$base.'prod-main-soft-tec-scale-control-premium-model-768w-sply.jpg']],
    [['softtec'],               [$base.'prod-main-soft-tec-scale-control-premium-model-768w-sply.jpg']],
    // ── Iron/Sulphur/Zentec Filters ─────────────────────────────────────────
    [['zentec','hybrid'],       [$base.'prod-main-premium-zentec-hybrid-capsulate-enhanced-ozone-chemical-free-filter-768w-sply.png',
                                  $base.'prod-main-premium-zentec-hybrid-capsulate-chemical-free-filter-768w-sply.jpg']],
    [['zentec','air'],          [$base.'prod-main-premium-zentec-hybrid-capsulate-chemical-free-filter-768w-sply.jpg']],
    [['zentec'],                [$base.'prod-main-premium-zentec-hybrid-capsulate-chemical-free-filter-768w-sply.jpg']],
    [['air injection'],         [$base.'prod-main-premium-zentec-hybrid-capsulate-chemical-free-filter-768w-sply.jpg']],
    // ── Specialty Filters ───────────────────────────────────────────────────
    [['tannin'],                [$base.'prod-main-filtermax-premium-tannin-filter-768w-sply.jpg']],
    [['neutraliz'],             [$base.'prod-main-filtermax-premium-neutralizing-filter-768w-sply.jpg']],
    [['turbidity'],             [$base.'prod-main-filtermax-premium-turbidity-filter-768w-sply.jpg']],
    [['uranium'],               [$base.'prod-main-filtermax-uranium-filter-768w-sply.jpg']],
    [['nitrate'],               [$base.'main-prod-filtermax-nitrates-filter-768w-sply.jpg']],
    [['arsenic'],               [$base.'prod-main-filtermax-arsenic-filter-768w-sply.jpg']],
    [['lead'],                  [$base.'prod-main-filtermax-lead-filter-768w-sply.jpg']],
    [['chlorination'],          [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    [['degasif'],               [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    [['pfas'],                  ['https://residential.excaliburwater.com/wp-content/uploads/2025/10/prod-main-chemical-removal-filter-for-municipal-water-applications-768w-sply.jpg']],
    [['ultrafiltration'],       ['https://residential.excaliburwater.com/wp-content/uploads/2024/05/lbl-ultrafiltration-system-1024w-spgy2-300x300.jpg']],
    // ── UV Systems ──────────────────────────────────────────────────────────
    [['uv','lamp'],             [$base.'prod-main-premium-ultravioilet_system-mini-rack-768w-sply.jpg']],
    [['uv','sleeve'],           [$base.'prod-main-premium-ultravioilet_system-mini-rack-768w-sply.jpg']],
    [['ultraviolet'],           [$base.'prod-main-premium-ultravioilet_system-mini-rack-768w-sply.jpg']],
    [['uv'],                    [$base.'prod-main-premium-ultravioilet_system-mini-rack-768w-sply.jpg']],
    // ── RO Systems ──────────────────────────────────────────────────────────
    [['smart purifier'],        ['https://residential.excaliburwater.com/wp-content/uploads/2022/10/sureflo-smart-purifier-plus-reverse-osmosis-system-v3-1024w-spgy-300x300.jpg']],
    [['sureflo','preferred'],   ['https://residential.excaliburwater.com/wp-content/uploads/2026/03/lbl-preferred-reverse-osmosis-system-1024w-spgy-300x300.jpg']],
    [['sureflo','premium'],     ['https://residential.excaliburwater.com/wp-content/uploads/2022/10/lbl-premium-reverse-osmosis-system-1024w-spgy-300x300.jpg',
                                  $base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    [['sureflo','superior','plus'],['https://residential.excaliburwater.com/wp-content/uploads/2022/10/lbl-superior-plus-reverse-osmosis-system-1024w-spgy-300x300.jpg']],
    [['sureflo','superior'],    ['https://residential.excaliburwater.com/wp-content/uploads/2022/10/lbl-superior-reverse-osmosis-system-1024w-spgy-300x300.jpg']],
    [['sureflo','value'],       ['https://residential.excaliburwater.com/wp-content/uploads/2022/11/lbl-value-reverse-osmosis-system-1024w-spgy-300x300.jpg']],
    [['reverse osmosis'],       [$base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    [['sureflo'],               [$base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    [['membrane'],              [$base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    [['booster pump'],          [$base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    [['holding tank'],          [$base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    [['ro'],                    [$base.'prod-main-superior-reverse-osmosis-system-768w-sply.jpg']],
    // ── Filter Housings & Cartridges ────────────────────────────────────────
    [['filter housing'],        [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['sediment filter'],       [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['sediment cartridge'],    [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['carbon block'],          [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['coconut shell','carbon'],[$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['doulton'],               [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['ceramic'],               [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['cartridge'],             [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['dual gradient'],         [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    [['omnipure'],              [$base.'prod-main-housings-jumbo-768w-sply.jpg']],
    // ── Mineral Tanks ───────────────────────────────────────────────────────
    [['mineral tank'],          [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['chrome jacket'],         [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['tank cap'],              [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['turbulator'],            [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['distributor'],           [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    // ── Brine / Water Softener Parts ────────────────────────────────────────
    [['brine tank'],            [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['safety float'],          [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['brinewell'],             [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['control valve'],         [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['bypass valve'],          [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['adapter kit'],           [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['flow control'],          [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['res up'],                [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['resin cleaner'],         [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['silicone'],              [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    // ── Chemical Feed / Retention ───────────────────────────────────────────
    [['retention tank'],        [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    [['chemical','tank'],       [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    [['stenner'],               [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    [['feed pump'],             [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    [['flow switch'],           [$base.'prod-main-chlorination-disinfection-system-768w-sply.jpg']],
    // ── Media & Resins ──────────────────────────────────────────────────────
    [['greensand'],             [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['filter ox'],             [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['calcite'],               [$base.'prod-main-filtermax-premium-neutralizing-filter-768w-sply.jpg']],
    [['corosex'],               [$base.'prod-main-filtermax-premium-neutralizing-filter-768w-sply.jpg']],
    [['zeolite'],               [$base.'prod-main-filtermax-premium-turbidity-filter-768w-sply.jpg']],
    [['centaur'],               [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    [['catalytic carbon'],      [$base.'prod-main-filtermax-premium-chemical-removal-filter-768w-sply.jpg']],
    [['cation exchange'],       [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['anion','tannin'],        [$base.'prod-main-filtermax-premium-tannin-filter-768w-sply.jpg']],
    [['infinity exchange'],     [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['resin'],                 [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['media'],                 [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
    // ── Test Kits & Meters ──────────────────────────────────────────────────
    [['test kit'],              [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    [['tds meter'],             [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    [['spin touch'],            [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    [['analyzer'],              [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    [['air gap'],               [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    [['wrench'],                [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    [['stack puller'],          [$base.'prod-main-tools-wrench-excalibur-for-filter-housings-768w-sply.jpg']],
    // ── Generic softener fallback ───────────────────────────────────────────
    [['softener'],              [$base.'prod-main-ultimate-series-water-softener-768w-sply.jpg']],
    [['filter'],                [$base.'prod-main-premium-whole-home-filtration-system-768w-sply.jpg']],
];

function findImageUrl($productName, $imageMap) {
    $name = strtolower($productName);
    $bestMatch = null;
    $bestCount = 0;

    foreach ($imageMap as [$keywords, $urls]) {
        $matched = 0;
        foreach ($keywords as $kw) {
            if (strpos($name, strtolower($kw)) !== false) {
                $matched++;
            } else {
                $matched = 0;
                break;
            }
        }
        if ($matched > 0 && count($keywords) > $bestCount) {
            $bestMatch = $urls;
            $bestCount = count($keywords);
        }
    }
    return $bestMatch ?? [];
}

// ─── Main ────────────────────────────────────────────────────────────────────

// Works on both local and live – finds products in Excalibur categories with no image
$excaliburCategories = [
    'WATER SOFTENERS','SOFT-TEC SCALE CONTROL','DROP PRODUCTS','FILTERS',
    'IRON & SULPHUR FILTERS','TANNIN FILTERS','NEUTRALIZING FILTERS','TURBIDITY FILTERS',
    'SPECIALTY FILTERS','ULTRAVIOLET SYSTEMS','REVERSE OSMOSIS','MINERAL TANKS',
    'PARTS & ACCESSORIES','MEDIA & RESINS','CONTROL VALVES',
];
$placeholders = implode(',', array_fill(0, count($excaliburCategories), '?'));
$catStmt = $pdo->prepare("SELECT id FROM categories WHERE name IN ({$placeholders})");
$catStmt->execute($excaliburCategories);
$catIds = $catStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($catIds)) {
    die("No Excalibur categories found. Run the seeders first.\n");
}
$catIn = implode(',', $catIds);

$products = $pdo->query("
    SELECT DISTINCT p.id, p.name, p.thumbnail_img
    FROM products p
    JOIN product_categories pc ON pc.product_id = p.id
    WHERE pc.category_id IN ({$catIn})
    ORDER BY p.id
")->fetchAll(PDO::FETCH_ASSOC);

echo "Processing " . count($products) . " Excalibur products...\n\n";
flush();

$insertUpload = $pdo->prepare("
    INSERT INTO uploads (file_original_name, file_name, user_id, file_size, extension, type, created_at, updated_at)
    VALUES (:name, :file_name, :user_id, :file_size, :ext, 'image', NOW(), NOW())
");
$updateProduct = $pdo->prepare("UPDATE products SET thumbnail_img = :uid, photos = :uid WHERE id = :pid");

$ok = 0; $fail = 0; $skip = 0;
$log = [];

foreach ($products as $p) {
    $pid  = $p['id'];
    $name = $p['name'];

    // Skip if already has an image
    if (!empty($p['thumbnail_img'])) {
        echo "[{$pid}] SKIP (already has image): {$name}\n";
        $skip++;
        continue;
    }

    echo "[{$pid}] {$name}\n";
    flush();

    $imageUrl = null;
    $source   = '';

    // 1. Try mapped URLs
    $candidates = findImageUrl($name, $imageMap);
    foreach ($candidates as $url) {
        $r = curlGet($url, 10);
        if ($r['code'] == 200 && strlen($r['body']) > 2000) {
            $imageUrl = $url;
            $source   = 'Excalibur site';
            break;
        }
    }

    // 2. DuckDuckGo fallback
    if (!$imageUrl) {
        $query = $name . ' excalibur water system';
        $imageUrl = searchDuckDuckGoImage($query);
        if ($imageUrl) $source = 'DuckDuckGo';
    }

    if (!$imageUrl) {
        echo "  ✗ No image found\n";
        $log[] = "FAIL [{$pid}] {$name}";
        $fail++;
        continue;
    }

    echo "  Source: {$source}\n  URL: {$imageUrl}\n";
    flush();

    $slug = preg_replace('/[^a-z0-9]/', '_', strtolower(substr($name, 0, 40)));
    $file = downloadImage($imageUrl, $uploadDir, $slug);
    if (!$file) {
        echo "  ✗ Download failed\n";
        $log[] = "DOWNLOAD_FAIL [{$pid}] {$name}";
        $fail++;
        continue;
    }

    $insertUpload->execute([
        ':name'      => substr($name, 0, 191),
        ':file_name' => $file['filename'],
        ':user_id'   => $userId,
        ':file_size' => $file['size'],
        ':ext'       => $file['ext'],
    ]);
    $uploadId = $pdo->lastInsertId();

    $updateProduct->execute([':uid' => $uploadId, ':pid' => $pid]);

    echo "  ✓ Upload #{$uploadId} → {$file['filename']}\n";
    $log[] = "OK [{$pid}] {$name} → upload #{$uploadId}";
    $ok++;
    usleep(200000); // 0.2s polite delay
}

echo "\n\n=== RESULTS ===\n";
echo "OK:      {$ok}\n";
echo "Failed:  {$fail}\n";
echo "Skipped: {$skip}\n\n";
echo implode("\n", $log) . "\n";
