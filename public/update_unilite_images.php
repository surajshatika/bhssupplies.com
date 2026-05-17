<?php
set_time_limit(0);
ini_set('memory_limit', '256M');

$pdo = new PDO('mysql:host=localhost;dbname=bhssupplies1', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$uploadDir = __DIR__ . '/uploads/all/';
$userId = 9; // admin user

// ─── Helpers ────────────────────────────────────────────────────────────────

function curlGet($url, $timeout = 20) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => ['Accept-Language: en-GB,en;q=0.5'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function extractYesssImage($html) {
    preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m);
    foreach ($m[1] as $json) {
        $d = json_decode($json, true);
        $img = $d['image'] ?? null;
        if ($img) {
            $img = is_array($img) ? $img[0] : $img;
            // Convert resized URL to full-size
            if (preg_match('/cdn\.yesss\.co\.uk\/resized\/(.+?)\/\d+\/(.+)$/', $img, $x)) {
                return 'https://cdn.yesss.co.uk/images/' . $x[2];
            }
            return $img;
        }
    }
    return null;
}

function extractValoImage($html) {
    preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m);
    foreach ($m[1] as $json) {
        $d = json_decode($json, true);
        $img = $d['image'] ?? ($d['@graph'][0]['image'] ?? null);
        if ($img) return is_array($img) ? $img[0] : $img;
    }
    preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $og);
    return $og[1] ?? null;
}

function downloadImage($imageUrl, $uploadDir, $sku) {
    $r = curlGet($imageUrl, 30);
    if ($r['code'] != 200 || strlen($r['body']) < 1000) return null;
    $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
    if (!$ext || strlen($ext) > 5) $ext = 'jpg';
    $ext = strtolower(explode('?', $ext)[0]);
    $filename = 'unilite_' . preg_replace('/[^a-z0-9]/', '_', strtolower($sku)) . '_' . time() . '.' . $ext;
    $path = $uploadDir . $filename;
    file_put_contents($path, $r['body']);
    return ['filename' => 'uploads/all/' . $filename, 'ext' => $ext, 'size' => strlen($r['body'])];
}

// ─── Source Maps ────────────────────────────────────────────────────────────

$yesssUrls = [
    'PL-3'      => 'https://www.yesss.co.uk/275lm-led-inspection-light-die-cast-aluminium-3-x-aaa',
    'IL-425R'   => 'https://www.yesss.co.uk/compact-rechargeable-folding-inspection-light-6500k-425lm-ip20',
    'IL-925R'   => 'https://www.yesss.co.uk/rechargeable-folding-inspection-light-925lm-black-ip54',
    'IL-625R'   => 'https://www.yesss.co.uk/usb-rechargeable-inspection-light',
    'CRI-1250R' => 'https://www.yesss.co.uk/compact-detailing-light',
    'K-550'     => 'https://www.yesss.co.uk/compact-rechargeable-mini-work-light-6500k-550lm-ipx6',
    'SLR-1450'  => 'https://www.yesss.co.uk/compact-led-rechargeable-work-light-6500k-1450lm-ipx5',
    'SLR-1750'  => 'https://www.yesss.co.uk/compact-led-rechargeable-work-light-1750lm-ipx5',
    'SLR-5500'  => 'https://www.yesss.co.uk/50w-led-worklight-with-powerbank-6500k-5500lm-ip54',
    'MTB-5300'  => 'https://www.yesss.co.uk/multi-power-tool-battery-hybrid-site-light-6500k-5300lm-ip65',
    'PS-HDL2'   => 'https://www.yesss.co.uk/200-lumen-cree-led-helmet-headlight-3-x-aa-energizer-inc',
    'PS-HDL6R'  => 'https://www.yesss.co.uk/350-lumen-smd-led-dual-power-helmet-mountable-headlight-with-li-ion-battery-pack-micro-usb-lead-aaa-battery-case-3m-adheshive-helmet-mount',
    'HL-4R'     => 'https://www.yesss.co.uk/275lm-led-head-torch-rechargeable-usb-type-c',
    'HL-8R'     => 'https://www.yesss.co.uk/rechargeable-sensor-mode-headtorch-475lm-ip65',
    'HT-650R'   => 'https://www.yesss.co.uk/dual-led-head-torch-6500k-650lm-ip65',
    'NL-350R'   => 'https://www.yesss.co.uk/rechargeable-neck-light-350lm-ip65',
    'F-2700'    => 'https://www.yesss.co.uk/usb-flashlight-6500k-2700lm-black-aluminium-ip68',
    'F-1400'    => 'https://www.yesss.co.uk/dual-power-flashlight-6500k-1400lm-black-aluminium-ip68',
    'FR-400'    => 'https://www.yesss.co.uk/right-angle-usb-flashlight-6500k-400lm-black-ip65',
    'L-1800'    => 'https://www.yesss.co.uk/led-lantern-6500k-1800lm-ipx6',
    'MT5M2'     => 'https://www.yesss.co.uk/heavy-duty-tape-measure-27mm-5m',
];

$hsCdn = 'https://cdn.handshake.fi/images/taskulamput/tuotekuvat/Unilite/thumbs/';
$handshakeUrls = [
    'IL-SIG1'    => $hsCdn . 'unilite_il_sig1_1_normal.jpg',
    'CRI-2300'   => $hsCdn . 'unilite_cri_2300_1_normal.jpg',
    'HL-5R'      => $hsCdn . 'unilite_hl_5r_1_normal.jpg',
    'HL-6R'      => $hsCdn . 'unilite_hl_6r_1_normal.jpg',
    'HL-7R'      => $hsCdn . 'unilite_hl_7r_1_normal.jpg',
    'PS-HDL9R'   => $hsCdn . 'unilite_ps_hdl9r_1_normal.jpg',
    'RAIL-HDL9R' => $hsCdn . 'unilite_rail_hdl9r_1_normal.jpg',
    'FL-2'       => $hsCdn . 'unilite_fl_2_1_normal.jpg',
    'FL-4R'      => $hsCdn . 'unilite_fl_4r_1_normal.jpg',
    'TRIPOD-SGL' => $hsCdn . 'unilite_tripod_sgl_1_normal.jpg',
    'TRIPOD-DBL' => $hsCdn . 'unilite_tripod_dbl_1_normal.jpg',
    'TRIPOD-360' => $hsCdn . 'unilite_tripod_360_1_normal.jpg',
    'ATEX-FL4'   => $hsCdn . 'unilite_fl4_1_normal.jpg',
    'ATEX-PL1'   => $hsCdn . 'unilite_pl1_1_normal.jpg',
    'ATEX-RA2'   => $hsCdn . 'unilite_ra2_1_normal.jpg',
    'ATEX-H2'    => $hsCdn . 'unilite_h2_1_normal.jpg',
];

$valostoreUrls = [
    'SLR-3500' => 'https://www.valostore.com/en/product/rechargeable_work_light_unilite_slr_3500',
    'SLR-6000' => 'https://www.valostore.com/en/product/rechargeable_worklight_unilite_slr_6000',
    'WCFL12'   => 'https://www.valostore.com/en/product/flashlight_unilite_wcfl12',
    'WCSGL'    => 'https://www.valostore.com/en/product/charger_unilite_wcsgl',
    'WCHX7'    => 'https://www.valostore.com/en/product/worklight_unilite_wchx7',
];

// Valostore dynamic prefix list for remaining products
$valosTypePrefixes = [
    'headlamp', 'flashlight', 'worklight', 'work_light', 'rechargeable_worklight',
    'rechargeable_work_light', 'site_light', 'inspection_lamp', 'universal_lamp',
    'atex_flashlight', 'atex_headlamp', 'neck_light', 'charger', 'battery',
    'rechargeable_battery', 'stand', 'organiser', 'knife', 'cutter', 'scissors',
    'safety_glasses', 'gloves', 'powerbank', 'cable', 'adapter', 'multitool',
];

function findValoImage($sku, $prefixes) {
    $skuLower = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $sku));
    foreach ($prefixes as $prefix) {
        $url = "https://www.valostore.com/en/product/{$prefix}_unilite_{$skuLower}";
        $r = curlGet($url, 10);
        if ($r['code'] == 200) {
            $img = extractValoImage($r['body']);
            if ($img) return $img;
        }
    }
    return null;
}

// ─── Main Loop ───────────────────────────────────────────────────────────────

$products = $pdo->query("
    SELECT p.id, p.name, p.thumbnail_img,
           TRIM(SUBSTRING_INDEX(p.name, ' ', 1)) as sku
    FROM products p
    WHERE p.category_id = 152
       OR p.category_id IN (SELECT id FROM categories WHERE parent_id = 152)
    ORDER BY p.id
")->fetchAll(PDO::FETCH_ASSOC);

$ok = 0; $fail = 0; $skipped = 0;
$log = [];

$insertUpload = $pdo->prepare("
    INSERT INTO uploads (file_original_name, file_name, user_id, file_size, extension, type, created_at, updated_at)
    VALUES (:name, :file_name, :user_id, :file_size, :ext, 'image', NOW(), NOW())
");
$updateProduct = $pdo->prepare("UPDATE products SET thumbnail_img = :uid WHERE id = :pid");

echo "Processing " . count($products) . " products...\n\n";

foreach ($products as $p) {
    $pid  = $p['id'];
    $name = $p['name'];
    $sku  = $p['sku'];

    echo "[{$pid}] {$sku} - {$name}\n";

    $imageUrl = null;
    $source   = '';

    // 1. YESSS
    if (isset($yesssUrls[$sku])) {
        $r = curlGet($yesssUrls[$sku]);
        if ($r['code'] == 200) {
            $imageUrl = extractYesssImage($r['body']);
            if ($imageUrl) $source = 'YESSS';
        }
    }

    // 2. Handshake CDN direct
    if (!$imageUrl && isset($handshakeUrls[$sku])) {
        $imageUrl = $handshakeUrls[$sku];
        $source   = 'Handshake';
    }

    // 3. Valostore confirmed
    if (!$imageUrl && isset($valostoreUrls[$sku])) {
        $r = curlGet($valostoreUrls[$sku]);
        if ($r['code'] == 200) {
            $imageUrl = extractValoImage($r['body']);
            if ($imageUrl) $source = 'Valostore';
        }
    }

    // 4. Valostore dynamic search
    if (!$imageUrl) {
        $imageUrl = findValoImage($sku, $valosTypePrefixes);
        if ($imageUrl) $source = 'Valostore-auto';
    }

    if (!$imageUrl) {
        echo "  ✗ No image found\n";
        $log[] = "FAIL: [{$pid}] {$sku}";
        $fail++;
        continue;
    }

    echo "  Source: {$source} → {$imageUrl}\n";

    // Download image
    $file = downloadImage($imageUrl, $uploadDir, $sku);
    if (!$file) {
        echo "  ✗ Download failed\n";
        $log[] = "DOWNLOAD_FAIL: [{$pid}] {$sku} from {$imageUrl}";
        $fail++;
        continue;
    }

    // Insert upload record
    $insertUpload->execute([
        ':name'      => $sku,
        ':file_name' => $file['filename'],
        ':user_id'   => $userId,
        ':file_size' => $file['size'],
        ':ext'       => $file['ext'],
    ]);
    $uploadId = $pdo->lastInsertId();

    // Update product
    $updateProduct->execute([':uid' => $uploadId, ':pid' => $pid]);

    echo "  ✓ Saved as upload #{$uploadId} ({$file['filename']})\n";
    $log[] = "OK: [{$pid}] {$sku} → upload #{$uploadId}";
    $ok++;

    // Small delay to be polite
    usleep(300000); // 0.3s
}

echo "\n\n=== RESULTS ===\n";
echo "OK:      {$ok}\n";
echo "Failed:  {$fail}\n";
echo "Skipped: {$skipped}\n\n";
echo implode("\n", $log) . "\n";
