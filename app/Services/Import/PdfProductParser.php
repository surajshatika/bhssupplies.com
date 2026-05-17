<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// smalot/pdfparser uses PSR-0; ensure it's loaded even if classmap isn't rebuilt
if (!class_exists(\Smalot\PdfParser\Parser::class, false)) {
    $smalotAutoload = base_path('vendor/smalot/pdfparser/src/Smalot/PdfParser/Parser.php');
    if (file_exists($smalotAutoload)) {
        spl_autoload_register(function (string $class) {
            if (str_starts_with($class, 'Smalot\\')) {
                $relative = str_replace(['Smalot\\', '\\'], ['', '/'], $class);
                $file     = base_path("vendor/smalot/pdfparser/src/Smalot/{$relative}.php");
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }, true, true);
    }
}

class PdfProductParser
{
    /**
     * Parse product data from an uploaded PDF file path.
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['error' => 'File not found: ' . $filePath, 'products' => []];
        }

        $text = $this->extractText($filePath);
        if (empty($text)) {
            return ['error' => 'Could not extract text from PDF.', 'products' => []];
        }

        return $this->parseProducts($text, basename($filePath));
    }

    /**
     * Parse products from raw text (e.g. pasted or pre-extracted).
     */
    public function parseText(string $text, string $source = 'manual'): array
    {
        return $this->parseProducts($text, $source);
    }

    private function extractText(string $filePath): string
    {
        // Primary: smalot/pdfparser (pure PHP)
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($filePath);
                $text   = $pdf->getText();
                if (!empty($text) && strlen(trim($text)) > 20) {
                    return trim($text);
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Secondary: pdftotext (poppler-utils)
        $escaped       = escapeshellarg($filePath);
        $pdftotextBins = [
            'pdftotext',
            'C:/poppler/bin/pdftotext',
            'C:/Program Files/poppler/bin/pdftotext',
            'C:/tools/poppler/bin/pdftotext',
        ];

        foreach ($pdftotextBins as $bin) {
            $cmd    = escapeshellarg($bin) . " -layout {$escaped} - 2>nul";
            $output = shell_exec($cmd);
            if (!empty($output) && strlen(trim($output)) > 20) {
                return trim($output);
            }
        }

        // Last resort: extract text from PDF BT...ET stream markers
        $content = file_get_contents($filePath) ?? '';
        $text    = '';
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $streams)) {
            foreach ($streams[1] as $stream) {
                preg_match_all('/\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\)/', $stream, $strs);
                foreach ($strs[1] as $s) {
                    $decoded = stripcslashes($s);
                    $decoded = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                    if (strlen(trim($decoded)) > 1) {
                        $text .= $decoded . ' ';
                    }
                }
            }
        }

        return strlen(trim($text)) > 50 ? trim($text) : '';
    }

    private function parseProducts(string $text, string $source): array
    {
        // Normalise line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $products = [];

        // ── Strategy 1: KNIPEX-style catalog (SKU + Name on same line) ──────────
        // Pattern: optional leading number, then "XX XX XXX" SKU, then product name
        // e.g.  "82 01 150  Cobra® Pliers 150 mm"
        $knipexProducts = $this->parseKnipexCatalog($text, $source);
        if (count($knipexProducts) >= 3) {
            return [
                'products'      => $knipexProducts,
                'total_parsed'  => count($knipexProducts),
                'source'        => $source,
                'raw_text_len'  => strlen($text),
                'parse_strategy'=> 'knipex-catalog',
            ];
        }

        // ── Strategy 2: Table-like format (tab/pipe-separated columns) ───────────
        $tableProducts = $this->parseTableFormat($text, $source);
        if (count($tableProducts) >= 3) {
            return [
                'products'      => $tableProducts,
                'total_parsed'  => count($tableProducts),
                'source'        => $source,
                'raw_text_len'  => strlen($text),
                'parse_strategy'=> 'table',
            ];
        }

        // ── Strategy 3: Block-based fallback (blank-line separated) ─────────────
        $blocks = preg_split('/\n{2,}/', $text);
        foreach ($blocks as $block) {
            $block = trim($block);
            if (strlen($block) < 10) continue;

            $product = $this->extractProductFromBlock($block);
            if ($product) {
                $product['import_source'] = $source;
                $products[] = $product;
            }
        }

        return [
            'products'      => $products,
            'total_parsed'  => count($products),
            'source'        => $source,
            'raw_text_len'  => strlen($text),
            'parse_strategy'=> 'block',
        ];
    }

    /**
     * Parse KNIPEX-style catalog lines where SKU precedes the product name.
     * Handles formats:
     *   "82 01 150  Cobra® Pliers 150 mm"
     *   "8201150  Cobra® Pliers"
     *   "Cat# 82 01 150 | Product name | $29.99"
     */
    private function parseKnipexCatalog(string $text, string $source): array
    {
        $products = [];
        $lines    = explode("\n", $text);
        $n        = count($lines);

        for ($i = 0; $i < $n; $i++) {
            $line = trim($lines[$i]);

            // Match KNIPEX-style SKU at the start or after optional whitespace/delimiter
            // Format: "82 01 150" or "8201150" or "82-01-150"
            if (!preg_match('/(?:^|\s)(\d{2}[\s\-]?\d{2}[\s\-]?\d{3,4}(?:[\s\-]\d+)?)\s+(.{5,})/u', $line, $m)) {
                continue;
            }

            $sku  = preg_replace('/[\s\-]+/', ' ', trim($m[1]));
            $name = trim($m[2]);

            // Skip if name looks like noise (pure numbers or too short)
            if (strlen($name) < 4 || is_numeric($name)) continue;

            // Look ahead for description on subsequent lines
            $description = '';
            $price       = null;
            $imageUrl    = '';

            for ($j = $i + 1; $j <= min($i + 4, $n - 1); $j++) {
                $next = trim($lines[$j]);
                if (empty($next)) break;

                // Stop if next line is another product
                if (preg_match('/^\d{2}[\s\-]\d{2}[\s\-]\d{3}/', $next)) break;

                // Extract price
                if ($price === null && preg_match('/[\$£€]\s*([\d,]+\.?\d{0,2})/', $next, $pm)) {
                    $price = (float) str_replace(',', '', $pm[1]);
                }

                // Extract image URL
                if (empty($imageUrl) && preg_match('/https?:\/\/\S+/i', $next, $um)) {
                    $foundUrl = rtrim($um[0], '.,;)"\'');
                    $ext      = strtolower(pathinfo(parse_url($foundUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $imageUrl = $foundUrl;
                    }
                }

                // Accumulate description
                if (!preg_match('/^[\$£€\d]/', $next) && strlen($next) > 5) {
                    $description .= ($description ? ' ' : '') . $next;
                }
            }

            $products[] = [
                'name'          => $this->cleanProductName($name),
                'sku'           => $sku,
                'price'         => $price,
                'description'   => Str::limit($description, 500),
                'image'         => $imageUrl,
                'import_source' => $source,
            ];
        }

        return $products;
    }

    /**
     * Parse tab or pipe-separated table lines with header detection.
     * e.g. "SKU | Name | Price | Description"
     */
    private function parseTableFormat(string $text, string $source): array
    {
        $products = [];
        $lines    = array_map('trim', explode("\n", $text));

        // Detect separator (tab or pipe)
        $tabCount  = substr_count($text, "\t");
        $pipeCount = substr_count($text, '|');
        $sep       = $tabCount > $pipeCount ? "\t" : '|';

        // Find header row
        $headerIdx = -1;
        $headers   = [];
        foreach ($lines as $i => $line) {
            $cols = array_map('trim', explode($sep, $line));
            if (count($cols) >= 2) {
                $lowerCols = array_map('strtolower', $cols);
                if (array_intersect($lowerCols, ['name', 'product', 'sku', 'model', 'description', 'price'])) {
                    $headerIdx = $i;
                    $headers   = $lowerCols;
                    break;
                }
            }
        }

        $nameCol  = array_search('name', $headers) ?: array_search('product', $headers) ?: 0;
        $skuCol   = array_search('sku', $headers) ?: array_search('model', $headers) ?: false;
        $priceCol = array_search('price', $headers) ?: false;
        $descCol  = array_search('description', $headers) ?: false;
        $imgCol   = array_search('image', $headers) ?: array_search('photo', $headers) ?: false;

        $start = $headerIdx >= 0 ? $headerIdx + 1 : 0;

        for ($i = $start; $i < count($lines); $i++) {
            $cols = array_map('trim', explode($sep, $lines[$i]));
            if (count($cols) < 2) continue;

            $name = $cols[$nameCol] ?? '';
            if (strlen($name) < 3 || is_numeric($name)) continue;

            $sku   = $skuCol !== false  ? ($cols[$skuCol]   ?? null) : null;
            $price = $priceCol !== false ? ($cols[$priceCol] ?? null) : null;
            $desc  = $descCol !== false  ? ($cols[$descCol]  ?? '')   : '';
            $img   = $imgCol !== false   ? ($cols[$imgCol]   ?? '')   : '';

            if ($price) {
                $price = (float) preg_replace('/[^\d.]/', '', $price) ?: null;
            }

            $products[] = [
                'name'          => $this->cleanProductName($name),
                'sku'           => $sku,
                'price'         => $price,
                'description'   => Str::limit($desc, 500),
                'image'         => filter_var($img, FILTER_VALIDATE_URL) ? $img : '',
                'import_source' => $source,
            ];
        }

        return $products;
    }

    private function extractProductFromBlock(string $block): ?array
    {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        if (empty($lines)) return null;

        // Extract HTTP(S) URLs
        $imageUrl = '';
        if (preg_match_all('/https?:\/\/\S+/i', $block, $urlMatches)) {
            foreach ($urlMatches[0] as $foundUrl) {
                $foundUrl = rtrim($foundUrl, '.,;)"\'');
                $ext      = strtolower(pathinfo(parse_url($foundUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                    $imageUrl = $foundUrl;
                    break;
                }
                if (empty($imageUrl)) {
                    $imageUrl = $foundUrl;
                }
            }
        }

        // Remove URLs before further parsing
        $cleanBlock = preg_replace('/https?:\/\/\S+/i', '', $block);
        $lines      = array_values(array_filter(array_map('trim', explode("\n", $cleanBlock))));
        if (empty($lines)) return null;

        $name = array_shift($lines);
        $rest = implode(' ', $lines);

        // Skip structural noise
        if (preg_match('/^\d+ \d+ obj$/', trim($name)) || strlen($name) < 3) {
            return null;
        }
        // Skip if it looks like a page number or header
        if (preg_match('/^(page|chapter|\d+)$/i', trim($name))) {
            return null;
        }

        // Extract price
        $price = null;
        if (preg_match('/[\$£€]\s*(\d[\d,]*\.?\d{0,2})/', $rest, $pm)) {
            $price = (float) str_replace(',', '', $pm[1]);
        } elseif (preg_match('/\b(\d{1,4}\.\d{2})\b/', $rest, $pm)) {
            $price = (float) $pm[1];
        }

        // Extract SKU — KNIPEX format first, then generic
        $sku = null;
        if (preg_match('/\b(\d{2}\s+\d{2}\s+\d{3,})\b/', $rest . ' ' . $name, $sm)) {
            $sku = preg_replace('/\s+/', ' ', $sm[1]);
        } elseif (preg_match('/\b([A-Z]{1,4}\d{3,10}|\d{6,12})\b/', $rest, $sm)) {
            $sku = $sm[1];
        }

        $description = trim(preg_replace('/[\$£€]\s*\d[\d,]*\.?\d{0,2}/', '', $rest));
        $description = trim(preg_replace('/\s{2,}/', ' ', $description));

        return [
            'name'        => $this->cleanProductName($name),
            'sku'         => $sku,
            'price'       => $price,
            'description' => Str::limit($description, 500),
            'image'       => $imageUrl,
        ];
    }

    private function cleanProductName(string $name): string
    {
        // Decode HTML entities
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Remove leading SKU-like prefixes: "82 01 150 – Product Name" → "Product Name"
        $name = preg_replace('/^\d{2}[\s\-]\d{2}[\s\-]\d{3,}\s*[\-–—:]\s*/', '', $name);
        // Collapse whitespace
        return trim(preg_replace('/\s{2,}/', ' ', $name));
    }
}
