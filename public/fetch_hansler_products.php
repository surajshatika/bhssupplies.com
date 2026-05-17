<?php
function fetchJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

// Search by title containing "Unilite"
$result = fetchJson('https://www.hansler.com/search/suggest.json?q=unilite&resources[type]=product&resources[limit]=250');
echo "HTTP: " . $result['code'] . "\n";
if ($result['code'] == 200) {
    $data = json_decode($result['body'], true);
    $products = $data['resources']['results']['products'] ?? [];
    echo "Found: " . count($products) . " products\n\n";
    foreach ($products as $p) {
        echo "Title: " . $p['title'] . "\n";
        echo "Image: " . ($p['image'] ?? $p['featured_image']['url'] ?? 'NO IMAGE') . "\n\n";
    }
    // Debug: show first product structure
    if ($products) {
        echo "\nFirst product keys: " . implode(', ', array_keys($products[0])) . "\n";
        echo json_encode($products[0], JSON_PRETTY_PRINT);
    }
}
