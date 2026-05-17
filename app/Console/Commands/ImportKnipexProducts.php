<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImportKnipexProducts extends Command
{
    protected $signature = 'import:knipex
                            {--pdf= : Full path to KNIPEX PDF catalog}
                            {--category=135 : Category ID to assign all products}
                            {--user=9 : User ID for imported products}
                            {--stock=10 : Default stock quantity}
                            {--dry-run : Parse only — do not save to DB}
                            {--limit=0 : Limit number of products to import (0 = all)}
                            {--skip=0 : Skip first N products}';

    protected $description = 'Import KNIPEX Canada products from a PDF catalog';

    /* ── Config ──────────────────────────────────────────────────── */
    private int    $categoryId;
    private int    $userId;
    private int    $defaultStock;
    private bool   $dryRun;
    private string $defaultTags    = 'KNIPEX,pliers,tools,professional,Canada';
    private string $defaultUnit    = 'Piece';
    private string $defaultBrand   = 'KNIPEX';

    /* ── Counters ────────────────────────────────────────────────── */
    private int $saved   = 0;
    private int $skipped = 0;
    private int $failed  = 0;
    private int $noImage = 0;

    public function handle(): int
    {
        $this->categoryId   = (int) $this->option('category');
        $this->userId       = (int) $this->option('user');
        $this->defaultStock = (int) $this->option('stock');
        $this->dryRun       = (bool) $this->option('dry-run');
        $limit              = (int) $this->option('limit');
        $skip               = (int) $this->option('skip');

        $pdfPath = $this->option('pdf');

        if (!$pdfPath || !file_exists($pdfPath)) {
            $this->error('Please provide a valid PDF path: --pdf=/path/to/knipex-catalog.pdf');
            return 1;
        }

        $this->info("Parsing PDF: {$pdfPath}");
        $products = $this->parseKnipexPdf($pdfPath);

        if (empty($products)) {
            $this->error('No products parsed from PDF. Check parser output.');
            return 1;
        }

        $this->info('Parsed ' . count($products) . ' products from PDF.');

        // Apply skip / limit
        if ($skip > 0) {
            $products = array_slice($products, $skip);
        }
        if ($limit > 0) {
            $products = array_slice($products, 0, $limit);
        }

        $this->info('Will import ' . count($products) . ' products.');

        if ($this->dryRun) {
            $this->warn('DRY RUN — nothing will be saved.');
            foreach (array_slice($products, 0, 10) as $i => $p) {
                $this->line("[{$i}] {$p['name']} | SKU: {$p['sku']} | Price: {$p['unit_price']}");
            }
            return 0;
        }

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $data) {
            try {
                // Skip SBA variants (single-blister retail packaging)
                if (preg_match('/-SBA(?:-|$)/i', $data['sku'] ?? '')) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                $existing = $this->findExisting($data);
                if ($existing) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }
                $this->createProduct($data);
                $this->saved++;
            } catch (\Throwable $e) {
                $this->failed++;
                $this->newLine();
                $this->warn("FAILED [{$data['name']}]: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Saved', 'Skipped (duplicate)', 'Failed', 'No image'],
            [[$this->saved, $this->skipped, $this->failed, $this->noImage]]
        );

        return 0;
    }

    /* ════════════════════════════════════════════════════════════════
     * PDF PARSING — KNIPEX-specific
     * ════════════════════════════════════════════════════════════════ */

    private function parseKnipexPdf(string $filePath): array
    {
        $text = $this->extractPdfText($filePath);
        if (empty($text)) {
            $this->error('Could not extract text from PDF.');
            return [];
        }

        return $this->parseKnipexText($text);
    }

    private function extractPdfText(string $filePath): string
    {
        // Try smalot/pdfparser first (pure PHP)
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($filePath);
                $text   = $pdf->getText();
                if (!empty($text) && strlen(trim($text)) > 100) {
                    return trim($text);
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Try pdftotext (poppler)
        $bins = [
            'pdftotext',
            'C:/poppler/bin/pdftotext',
            'C:/Program Files/poppler/bin/pdftotext',
        ];
        foreach ($bins as $bin) {
            $cmd    = escapeshellarg($bin) . ' -layout ' . escapeshellarg($filePath) . ' - 2>nul';
            $output = shell_exec($cmd);
            if (!empty($output) && strlen(trim($output)) > 100) {
                return trim($output);
            }
        }

        return '';
    }

    /**
     * Parse KNIPEX Canada catalog PDF text.
     *
     * PDF layout (tab/space separated columns):
     *   {row#} {SKU}  {Product Name (may wrap)}
     *   {Description text (may span several lines, ends with "...")}
     *   KNIPEX, pliers, tools,
     *   professional, Canada
     *   ${ListPrice} ${NetPrice}Piece {Stock}
     *
     * SKU format examples: 00-20-06-US4 | 82-01-150 | 9K-00-80-168-US
     */
    private function parseKnipexText(string $text): array
    {
        // ── Strip page headers ──────────────────────────────────────
        $text = preg_replace(
            '/^#\s+SKU\s*\/\s*Part Number.*?(?:Unit\s*Stock|Unit\s+Stock)\s*$/ms',
            '',
            $text
        );

        // ── Tokenise into per-product blocks ────────────────────────
        // Each product block starts with: {digits} {SKU-with-dashes}
        // SKU pattern: [A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+(-[A-Z0-9]+)*
        $rowStart = '/(?:^|\n)(\d{1,4})\s+([A-Z0-9]{1,4}-[A-Z0-9]{2,4}-[A-Z0-9]{2,6}(?:-[A-Z0-9]+)*)\s/';
        preg_match_all($rowStart, $text, $startMatches, PREG_OFFSET_CAPTURE);

        if (empty($startMatches[0])) {
            return [];
        }

        $products = [];
        $total    = count($startMatches[0]);

        for ($i = 0; $i < $total; $i++) {
            $blockStart = $startMatches[0][$i][1];
            $blockEnd   = isset($startMatches[0][$i + 1])
                ? $startMatches[0][$i + 1][1]
                : strlen($text);

            $block = substr($text, $blockStart, $blockEnd - $blockStart);
            $block = trim($block);

            $sku = $startMatches[2][$i][0];

            $parsed = $this->parseKnipexProductBlock($block, $sku);
            if ($parsed) {
                $products[] = $parsed;
            }
        }

        // ── Deduplicate by SKU ──────────────────────────────────────
        $seen   = [];
        $unique = [];
        foreach ($products as $p) {
            $key = $p['sku'] ?: strtolower($p['name']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $p;
            }
        }

        return $unique;
    }

    /**
     * Parse a single product block.
     *
     * Block example (after the row# and SKU are identified):
     *   "1 00-20-06-US4 KNIPEX 3 Pc TwinGrip Pliers Set, Plastic\nDipped (00 20 06 US4)\n
     *    KNIPEX 3 Pc TwinGrip ... Ideal...\nKNIPEX, pliers, tools,\nprofessional, Canada\n
     *    $235.68 $98.99Piece 10"
     */
    private function parseKnipexProductBlock(string $block, string $sku): ?array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
        if (empty($lines)) return null;

        $netPrice = 0.0;

        // ── Separate structural lines from content ─────────────────────────
        $contentLines = [];
        foreach ($lines as $line) {
            // Price line: "$235.68 $98.99Piece 10"
            if (preg_match('/\$\s*\d+[\.,]\d{2}/', $line)) {
                preg_match_all('/\$\s*(\d[\d,]*\.\d{2})/', $line, $pm);
                if (!empty($pm[1])) {
                    $idx      = count($pm[1]) >= 2 ? 1 : 0;
                    $netPrice = (float) str_replace(',', '', $pm[1][$idx]);
                }
                continue;
            }
            // Tags lines
            if (preg_match('/^KNIPEX,\s*pliers/i', $line) ||
                preg_match('/^professional,?\s*Canada/i', $line)) {
                continue;
            }
            $contentLines[] = $line;
        }

        if (empty($contentLines)) return null;

        // ── Strip the "{row#} {SKU}" prefix from the first line ────────────
        $contentLines[0] = preg_replace('/^\d+\s+\S+\s*/', '', $contentLines[0]);
        if (empty(trim($contentLines[0]))) {
            array_shift($contentLines);
        }

        // ── Split name vs description line by line ─────────────────────────
        // Description starts on the first line (after line 0) that:
        //   (a) starts with "KNIPEX " AND
        //   (b) "(Part#" appears on that line or within the next 2 lines
        // Special case: if line 0 contains "(Part#" it means name+desc are on same line →
        //   split at the "KNIPEX " that immediately precedes "(Part#".
        $nameLines = [];
        $descLines = [];
        $inDesc    = false;
        $n         = count($contentLines);

        for ($j = 0; $j < $n; $j++) {
            $line = $contentLines[$j];

            if (!$inDesc) {
                if ($j === 0 && stripos($line, '(Part#') !== false) {
                    // Name and description merged on one line.
                    // Split at last "KNIPEX " before "(Part#"
                    $partPos      = stripos($line, '(Part#');
                    $beforePart   = substr($line, 0, $partPos);
                    $knipexOffset = strrpos($beforePart, 'KNIPEX ');
                    if ($knipexOffset !== false && $knipexOffset > 0) {
                        $nameLines[] = trim(substr($line, 0, $knipexOffset));
                        $descLines[] = trim(substr($line, $knipexOffset));
                    } else {
                        // Only one KNIPEX — description is just the Part# section
                        $nameLines[] = trim($beforePart);
                        $descLines[] = trim(substr($line, $partPos));
                    }
                    $inDesc = true;
                    continue;
                }

                if ($j > 0 && preg_match('/^KNIPEX\s/i', $line)) {
                    // Check if "(Part#" appears on this or next 2 lines
                    $window = implode(' ', array_slice($contentLines, $j, 3));
                    if (stripos($window, '(Part#') !== false) {
                        $inDesc = true;
                    }
                }
                // Line itself contains "(Part#" mid-line (continuation)
                if (!$inDesc && stripos($line, '(Part#') !== false) {
                    $inDesc = true;
                }

                if ($inDesc) {
                    $descLines[] = $line;
                } else {
                    $nameLines[] = $line;
                }
            } else {
                $descLines[] = $line;
            }
        }

        $namePart = implode(' ', $nameLines);
        $descPart = implode(' ', $descLines);

        // ── Clean name ─────────────────────────────────────────────────────
        $name = $namePart;
        // Remove trailing parenthetical SKU ref "(00 20 06 US4)" or "(82 150 E01)"
        $name = preg_replace('/\s*\([0-9A-Z\s]+\)\s*$/', '', $name);
        // Collapse repeated "KNIPEX KNIPEX" at start
        $name = preg_replace('/^(KNIPEX\s+)\1+/i', '$1', $name);
        $name = trim($name);

        if (strlen($name) < 3) return null;

        // ── Clean description ──────────────────────────────────────────────
        $desc = $descPart;
        $desc = preg_replace('/\s*\.\.\.\s*$/', '.', $desc);
        $desc = trim($desc);

        return $this->buildProductArray($sku, $name, $netPrice, $desc);
    }

    private function buildProductArray(string $sku, string $name, float $price, string $desc): array
    {
        return [
            'sku'         => $sku,
            'name'        => $name,
            'unit_price'  => $price,
            'description' => $desc,
            'tags'        => $this->defaultTags,
            'unit'        => $this->defaultUnit,
            'quantity'    => $this->defaultStock,
            'brand'       => $this->defaultBrand,
            'category_id' => $this->categoryId,
        ];
    }

    /* ════════════════════════════════════════════════════════════════
     * DUPLICATE DETECTION
     * ════════════════════════════════════════════════════════════════ */

    private function findExisting(array $data): ?Product
    {
        $sku = $data['sku'] ?? null;
        if ($sku) {
            $found = Product::where('barcode', $sku)->first();
            if ($found) return $found;
        }
        return Product::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first();
    }

    /* ════════════════════════════════════════════════════════════════
     * PRODUCT CREATION
     * ════════════════════════════════════════════════════════════════ */

    private function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            // ── Brand ───────────────────────────────────────────────
            $brandId = null;
            $brand   = Brand::where('name', $this->defaultBrand)->first();
            if (!$brand) {
                $brandId = DB::table('brands')->insertGetId([
                    'name'       => $this->defaultBrand,
                    'slug'       => 'knipex-' . rand(100, 999),
                    'logo'       => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $brandId = $brand->id;
            }

            // ── Category ─────────────────────────────────────────────
            $categoryId = $this->categoryId;
            if (!Category::find($categoryId)) {
                $categoryId = Category::where('level', 0)->value('id') ?? 1;
            }

            // ── Slug ─────────────────────────────────────────────────
            $slug      = Str::slug($data['name']);
            $slugBase  = $slug;
            $slugCount = Product::where('slug', 'LIKE', $slugBase . '%')->count();
            if ($slugCount) {
                $slug = $slugBase . '-' . ($slugCount + 1);
            }

            // ── Image — try multiple sources ─────────────────────────
            $imageUrl = $this->fetchBestImage($data['sku'] ?? '', $data['name']);

            // ── Tags ─────────────────────────────────────────────────
            $tags = $data['tags'] ?? $this->defaultTags;
            if (is_array($tags)) $tags = implode(',', $tags);

            // ── Price ────────────────────────────────────────────────
            $price = (float)($data['unit_price'] ?? 0);

            // ── Description ──────────────────────────────────────────
            $description = $data['description'] ?? '';

            // ── Product ──────────────────────────────────────────────
            $product = Product::create([
                'name'                   => $data['name'],
                'added_by'               => 'admin',
                'user_id'                => $this->userId,
                'category_id'            => $categoryId,
                'brand_id'               => $brandId,
                'photos'                 => $imageUrl,
                'thumbnail_img'          => $imageUrl,
                'tags'                   => $tags,
                'description'            => $description,
                'short_description'      => '',
                'unit_price'             => $price,
                'purchase_price'         => 0,
                'variant_product'        => 0,
                'attributes'             => json_encode([]),
                'choice_options'         => json_encode([]),
                'colors'                 => json_encode([]),
                'variations'             => json_encode([]),
                'todays_deal'            => 0,
                'published'              => 1,
                'approved'               => 1,
                'stock_visibility_state' => 'quantity',
                'cash_on_delivery'       => 1,
                'featured'               => 0,
                'seller_featured'        => 0,
                'current_stock'          => $this->defaultStock,
                'unit'                   => $this->defaultUnit,
                'weight'                 => 0,
                'min_qty'                => 1,
                'low_stock_quantity'     => 1,
                'discount'               => 0,
                'discount_type'          => 'percent',
                'shipping_type'          => 'free',
                'shipping_cost'          => 0,
                'is_quantity_multiplied' => 0,
                'num_of_sale'            => 0,
                'meta_title'             => $data['name'],
                'meta_description'       => $description ? Str::limit($description, 160) : '',
                'meta_img'               => $imageUrl,
                'slug'                   => $slug,
                'barcode'                => $data['sku'] ?? '',
                'digital'                => 0,
                'auction_product'        => 0,
                'wholesale_product'      => 0,
                'rating'                 => 0,
                'refundable'             => 1,
            ]);

            // ── Stock ────────────────────────────────────────────────
            ProductStock::create([
                'product_id' => $product->id,
                'variant'    => '',
                'price'      => $price,
                'sku'        => $data['sku'] ?? '',
                'qty'        => $this->defaultStock,
                'image'      => $imageUrl,
            ]);

            // ── Translation ──────────────────────────────────────────
            $lang = env('DEFAULT_LANGUAGE', 'en');
            ProductTranslation::firstOrCreate(
                ['product_id' => $product->id, 'lang' => $lang],
                [
                    'name'              => $data['name'],
                    'unit'              => $this->defaultUnit,
                    'description'       => $description,
                    'short_description' => '',
                ]
            );

            return $product;
        });
    }

    /* ════════════════════════════════════════════════════════════════
     * IMAGE FETCHING — multiple sources
     * ════════════════════════════════════════════════════════════════ */

    /**
     * Try multiple image sources and return the first one that works.
     * Sources (in priority order):
     *   1. Zoro.com search (most reliable, no Cloudflare blocking)
     *   2. KNIPEX CDN direct URL
     *   3. KNIPEX product page og:image
     *   4. Amazon Canada
     */
    private function fetchBestImage(string $sku, string $productName): string
    {
        $imageUrl = '';

        // ── 1. Zoro (primary — no bot protection) ────────────────────
        if ($sku) {
            $imageUrl = $this->tryZoro($sku);
        }

        // ── 2. KNIPEX CDN ────────────────────────────────────────────
        if (!$imageUrl && $sku) {
            $imageUrl = $this->tryKnipexCdn($sku);
        }

        // ── 3. KNIPEX product page og:image ─────────────────────────
        if (!$imageUrl && $sku) {
            $imageUrl = $this->tryKnipexProductPage($sku, $productName);
        }

        // ── 4. Amazon Canada ─────────────────────────────────────────
        if (!$imageUrl) {
            $imageUrl = $this->tryAmazonCanada($productName);
        }

        if ($imageUrl) {
            $saved = $this->downloadAndSave($imageUrl);
            if ($saved) return $saved;
        }

        $this->noImage++;
        return '';
    }

    /**
     * Search Zoro.com for the SKU and extract the first product image.
     * Zoro carries most KNIPEX tools and returns accessible HTML.
     */
    public function tryZoro(string $sku): string
    {
        // Compact: "82-01-150" → "8201150"
        $compact = str_replace('-', '', $sku);
        $html    = $this->fetchUrlHtml("https://www.zoro.com/search?q={$compact}");
        if (empty($html)) return '';

        // Find the first product listing image from Zoro CDN
        if (preg_match('#src=["\']+(https?://www\.zoro\.com/static/cms/product/[^"\'>\s]+\.(jpg|JPG|jpeg|png|webp))#i', $html, $m)) {
            return $m[1];
        }

        // Fallback: find product link with our SKU → fetch product page og:image
        $skuPattern = preg_quote($sku, '#');
        if (preg_match("#href=\"(/knipex-[^\"]*{$skuPattern}[^\"]*)/i/G[0-9]+/\"#i", $html, $lm)) {
            $productUrl = 'https://www.zoro.com' . $lm[1] . '/i/' . (explode('/i/', $lm[0])[1] ?? '') . '/';
            return $this->scrapeOgImage($productUrl);
        }

        return '';
    }

    /**
     * KNIPEX CDN URL format:
     * SKU "82 01 150" → "8201150" → https://www.knipex.com/medias/8201150-a1-1400-1400.jpg
     * Also tries variants: -front, -a0, -a2, -b1
     */
    private function tryKnipexCdn(string $sku): string
    {
        // Extract only digits from SKU (remove spaces, letters)
        $digitsOnly = preg_replace('/[^0-9]/', '', $sku);
        if (strlen($digitsOnly) < 5) return '';

        // Also try: keep alphanumeric (no spaces)
        $compact = preg_replace('/\s+/', '', $sku);

        $variants = [
            "https://www.knipex.com/medias/{$digitsOnly}-a1-1400-1400.jpg",
            "https://www.knipex.com/medias/{$compact}-a1-1400-1400.jpg",
            "https://www.knipex.com/medias/{$digitsOnly}-a0-1400-1400.jpg",
            "https://www.knipex.com/medias/{$digitsOnly}-front-1400-1400.jpg",
            "https://www.knipex.com/medias/{$digitsOnly}-a2-1400-1400.jpg",
            // Smaller sizes
            "https://www.knipex.com/medias/{$digitsOnly}-a1-515-515.jpg",
            "https://www.knipex.com/medias/{$digitsOnly}-a1-265-265.jpg",
        ];

        foreach ($variants as $url) {
            $binary = $this->fetchUrl($url);
            if ($binary && strlen($binary) > 500) {
                // Validate it's an image
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->buffer($binary);
                if (str_starts_with($mime, 'image/')) {
                    return $url; // Return URL; caller will download & save it
                }
            }
        }

        return '';
    }

    /**
     * Scrape knipex.com product page for og:image.
     * URL patterns tried:
     *   https://www.knipex.com/products/{slug}
     *   https://www.knipex.com/index.php?cl=details&anid={sku-compact}
     */
    private function tryKnipexProductPage(string $sku, string $productName): string
    {
        $compact  = preg_replace('/\s+/', '-', trim($sku));
        $nameSlug = Str::slug($productName);

        $urls = [
            "https://www.knipex.com/products/{$compact}",
            "https://www.knipex.com/products/{$nameSlug}/{$compact}",
            "https://www.knipex.com/index.php?cl=details&anid={$compact}",
        ];

        foreach ($urls as $url) {
            $ogImage = $this->scrapeOgImage($url);
            if ($ogImage) return $ogImage;
        }

        return '';
    }

    /**
     * Try Home Depot Canada product search page.
     */
    private function tryHomeDepotCanada(string $sku, string $productName): string
    {
        $query    = urlencode('KNIPEX ' . $sku);
        $searchUrl = "https://www.homedepot.ca/search?q={$query}";

        $html = $this->fetchUrlHtml($searchUrl);
        if (!$html) return '';

        // Find first product image in search results
        // Home Depot uses og:image or specific data attributes
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        // Try to find image in search result HTML
        if (preg_match('/"image"\s*:\s*"(https:[^"]+knipex[^"]+\.(?:jpg|png|webp))"/i', $html, $m)) {
            return str_replace('\\/', '/', $m[1]);
        }

        return '';
    }

    /**
     * Try Amazon Canada product search.
     */
    private function tryAmazonCanada(string $productName): string
    {
        $query     = urlencode('KNIPEX ' . $productName);
        $searchUrl = "https://www.amazon.ca/s?k={$query}&rh=p_89%3AKNIPEX";

        $html = $this->fetchUrlHtml($searchUrl);
        if (!$html) return '';

        // Find first product image
        if (preg_match('#"large"\s*:\s*"(https://m\.media-amazon\.com[^"]+)"#', $html, $m)) {
            return str_replace('\\/', '/', $m[1]);
        }
        if (preg_match('#data-src="(https://m\.media-amazon\.com/images/[^"]+\._AC_[^"]+)"#', $html, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * Scrape og:image from any URL.
     */
    private function scrapeOgImage(string $url): string
    {
        $html = $this->fetchUrlHtml($url);
        if (!$html) return '';

        $patterns = [
            '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/',
            '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image["\']/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                return $m[1];
            }
        }

        return '';
    }

    /**
     * Download binary content from URL.
     */
    private function fetchUrl(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout'       => 10,
                'user_agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
                'ignore_errors' => true,
                'header'        => "Accept: image/*,*/*\r\n",
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        return (string)(@file_get_contents($url, false, $context) ?? '');
    }

    /**
     * Fetch HTML page content.
     */
    private function fetchUrlHtml(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout'       => 12,
                'user_agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
                'ignore_errors' => true,
                'header'        =>
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                    "Accept-Language: en-CA,en;q=0.9\r\n" .
                    "Accept-Encoding: identity\r\n",
                'follow_location' => 1,
                'max_redirects'   => 5,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        return (string)(@file_get_contents($url, false, $context) ?? '');
    }

    /**
     * Download an image from a URL and save it locally.
     * Returns relative path like "uploads/all/import_xxx.jpg" or ''.
     */
    private function downloadAndSave(string $url): string
    {
        try {
            $binary = $this->fetchUrl($url);
            if (empty($binary) || strlen($binary) < 500) return '';

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($binary);
            if (!str_starts_with($mime, 'image/')) return '';

            $ext  = match(true) {
                str_contains($mime, 'png')  => 'png',
                str_contains($mime, 'gif')  => 'gif',
                str_contains($mime, 'webp') => 'webp',
                default                     => 'jpg',
            };

            $dir = public_path('uploads/all');
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $filename = 'knipex_' . uniqid() . '.' . $ext;
            file_put_contents($dir . '/' . $filename, $binary);

            return 'uploads/all/' . $filename;
        } catch (\Throwable $e) {
            return '';
        }
    }
}
