<?php

/**
 * Disables the MyFatoorah library's network self-updater.
 *
 * vendor/myfatoorah/library/autoload.php downloads and overwrites its own
 * source files from portal.myfatoorah.com once a day, on autoload. That adds
 * a cURL timeout to startup when offline and bypasses composer for updates.
 * This script injects an early `return;` so the updater never runs.
 *
 * Run automatically via composer post-autoload-dump.
 */
$file = __DIR__ . '/../vendor/myfatoorah/library/autoload.php';

if (!is_file($file)) {
    return; // package not installed
}

$marker = '// mf-self-updater-disabled';
$code   = file_get_contents($file);

if (strpos($code, $marker) !== false) {
    return; // already patched
}

$anchor  = "\$mfVersion = '2.2';";
$patched = str_replace(
    $anchor,
    $anchor . "\n$marker\nreturn;",
    $code,
    $count
);

if ($count === 1) {
    file_put_contents($file, $patched);
    echo "Patched myfatoorah autoload.php (self-updater disabled).\n";
} else {
    fwrite(STDERR, "WARNING: could not patch myfatoorah autoload.php (anchor not found — package updated?). Its network self-updater is active.\n");
}
