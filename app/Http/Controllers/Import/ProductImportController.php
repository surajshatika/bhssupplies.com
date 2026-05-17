<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Services\Import\ImportOrchestrator;
use App\Services\Import\PdfProductParser;
use App\Services\Import\ProductImageFinder;
use App\Services\Import\UrlProductScraper;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTranslation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProductImportController extends Controller
{
    public function index()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        return view('backend.import.product_import.index', compact('categories'));
    }

    /* ────────────────────────────────────────────────────────────
     * Import from PDF
     * ──────────────────────────────────────────────────────────── */
    public function importFromPdf(Request $request)
    {
        $request->validate(['pdf_file' => 'required|file|mimes:pdf|max:10240']);

        $filePath = $request->file('pdf_file')->getRealPath();
        $provider = $request->input('provider', config('seo.default_provider', 'openai'));
        $enrich   = $request->boolean('ai_enrich', true);

        try {
            if ($enrich) {
                $orchestrator = new ImportOrchestrator($provider);
                $result       = $orchestrator->importFromPdf($filePath);
            } else {
                $parser = new PdfProductParser();
                $result = $parser->parseFile($filePath);
            }

            $orchestrator  = $orchestrator ?? new ImportOrchestrator($provider);
            $result['mapped'] = $orchestrator->mapToProductSchema(
                $result['enriched'] ?? $result['products'] ?? []
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
                'total'   => count($result['mapped'] ?? []),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Import from URLs
     * ──────────────────────────────────────────────────────────── */
    public function importFromUrls(Request $request)
    {
        $request->validate(['urls' => 'required|string']);

        $urls = array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $request->input('urls')))
        ));

        if (empty($urls)) {
            return response()->json(['success' => false, 'message' => 'No valid URLs provided.'], 422);
        }

        $provider = $request->input('provider', config('seo.default_provider', 'openai'));

        try {
            $orchestrator = new ImportOrchestrator($provider);
            $result = $orchestrator->importFromUrls($urls);
            $result['mapped'] = $orchestrator->mapToProductSchema(
                $result['enriched'] ?? array_values($result['products'] ?? [])
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
                'total'   => count($result['mapped'] ?? []),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Single-URL scrape + multi-source image search.
     * Used by the "AI Add/Edit Products" page for advanced single-product import.
     * ──────────────────────────────────────────────────────────── */
    public function scrapeSingle(Request $request)
    {
        $request->validate(['url' => 'required|url']);

        try {
            $scraper = new UrlProductScraper();
            $product = $scraper->scrapeUrl($request->input('url'));

            if (isset($product['error'])) {
                return response()->json(['success' => false, 'message' => $product['error']], 422);
            }

            // Build query for external image search: name + brand + sku
            $query  = trim(($product['name'] ?? '') . ' ' . ($product['brand'] ?? '') . ' ' . ($product['sku'] ?? ''));
            $extra  = [];
            if ($request->boolean('search_external', true) && strlen($query) >= 3) {
                $finder = new ProductImageFinder();
                $extra  = $finder->findImages($query, 8);
            }

            // Compose final image groups: site (from scraper), then external sources
            $imageGroups = [];
            if (!empty($product['images'])) {
                $imageGroups['site'] = array_values($product['images']);
            }
            foreach ($extra as $src => $imgs) {
                if (!empty($imgs)) $imageGroups[$src] = array_values($imgs);
            }

            return response()->json([
                'success'      => true,
                'product'      => $product,
                'image_groups' => $imageGroups,
                'query'        => $query,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Find images only — for "Search more images" button after the user edits the name.
     * ──────────────────────────────────────────────────────────── */
    public function findImages(Request $request)
    {
        $request->validate(['query' => 'required|string|min:3']);

        try {
            $finder = new ProductImageFinder();
            $groups = $finder->findImages(trim($request->input('query')), (int) $request->input('per_source', 12));

            $imageGroups = [];
            foreach ($groups as $src => $imgs) {
                if (!empty($imgs) && is_array($imgs)) $imageGroups[$src] = array_values($imgs);
            }

            return response()->json(['success' => true, 'image_groups' => $imageGroups]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Save a single product WITH multiple selected images.
     * Body: { product: {...}, images: [url, url, url] }
     * Downloads each image, attaches first as thumbnail, all in `photos` and `meta_img`.
     * ──────────────────────────────────────────────────────────── */
    public function saveProductWithImages(Request $request)
    {
        $data   = $request->input('product', []);
        $images = $request->input('images', []);

        if (empty($data) || empty($data['name'])) {
            return response()->json(['success' => false, 'message' => 'Product name is required.'], 422);
        }

        try {
            $existing = $this->findExisting($data);
            if ($existing) {
                return response()->json([
                    'success'    => true,
                    'skipped'    => true,
                    'message'    => 'Product already exists — skipped.',
                    'product_id' => $existing->id,
                    'edit_url'   => route('products.admin.edit', $existing->id),
                ]);
            }

            // Download every selected image first; collect upload IDs
            $uploadIds = [];
            foreach ((array) $images as $imgUrl) {
                $id = $this->downloadAndSaveImage($imgUrl);
                if ($id > 0) $uploadIds[] = $id;
            }

            // Inject into data so createProduct uses these instead of single-image flow
            $data['_upload_ids'] = $uploadIds;
            $product = $this->createProduct($data);

            return response()->json([
                'success'           => true,
                'skipped'           => false,
                'message'           => 'Product saved successfully.',
                'product_id'        => $product->id,
                'images_downloaded' => count($uploadIds),
                'edit_url'          => route('products.admin.edit', $product->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Image proxy — workaround for hotlink-protected images so the
     * UI can preview them without CORS/referer issues.
     * ──────────────────────────────────────────────────────────── */
    public function proxyImage(Request $request)
    {
        $url = $request->query('url');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid URL.');
        }

        try {
            $resp = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Referer'    => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST),
                ])
                ->withOptions(['verify' => false])
                ->get($url);

            if (!$resp->successful()) abort(404);

            $mime = $resp->header('Content-Type') ?: 'image/jpeg';
            return response($resp->body(), 200, [
                'Content-Type'   => $mime,
                'Cache-Control'  => 'public, max-age=3600',
            ]);
        } catch (\Throwable $e) {
            abort(500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Preview (no AI, no save)
     * ──────────────────────────────────────────────────────────── */
    public function preview(Request $request)
    {
        $type = $request->input('type', 'url');

        if ($type === 'pdf' && $request->hasFile('pdf_file')) {
            $parser = new PdfProductParser();
            $result = $parser->parseFile($request->file('pdf_file')->getRealPath());
        } elseif ($type === 'url' && $request->filled('urls')) {
            $urls    = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $request->input('urls')))));
            $scraper = new UrlProductScraper();
            $result  = $scraper->scrapeUrls($urls);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid preview request.'], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /* ────────────────────────────────────────────────────────────
     * Save a SINGLE product to the database
     * ──────────────────────────────────────────────────────────── */
    public function saveProduct(Request $request)
    {
        $data = $request->input('product');
        if (empty($data) || empty($data['name'])) {
            return response()->json(['success' => false, 'message' => 'Product name is required.'], 422);
        }

        try {
            $existing = $this->findExisting($data);
            if ($existing) {
                return response()->json([
                    'success'    => true,
                    'skipped'    => true,
                    'message'    => 'Product already exists — skipped.',
                    'product_id' => $existing->id,
                    'edit_url'   => route('products.admin.edit', $existing->id),
                ]);
            }

            $product = $this->createProduct($data);
            return response()->json([
                'success'    => true,
                'skipped'    => false,
                'message'    => 'Product saved successfully.',
                'product_id' => $product->id,
                'edit_url'   => route('products.admin.edit', $product->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Bulk save ALL products
     * ──────────────────────────────────────────────────────────── */
    public function bulkSave(Request $request)
    {
        $products = $request->input('products', []);
        if (empty($products)) {
            return response()->json(['success' => false, 'message' => 'No products provided.'], 422);
        }

        $saved    = [];
        $skipped  = [];
        $errors   = [];

        foreach ($products as $i => $data) {
            if (empty($data['name'])) {
                $errors[] = "Row {$i}: missing name";
                continue;
            }
            try {
                $existing = $this->findExisting($data);
                if ($existing) {
                    $skipped[] = [
                        'name'     => $existing->name,
                        'id'       => $existing->id,
                        'edit_url' => route('products.admin.edit', $existing->id),
                    ];
                    continue;
                }
                $product = $this->createProduct($data);
                $saved[] = [
                    'name'     => $product->name,
                    'id'       => $product->id,
                    'edit_url' => route('products.admin.edit', $product->id),
                ];
            } catch (\Throwable $e) {
                $errors[] = "Row {$i} ({$data['name']}): " . $e->getMessage();
            }
        }

        $msg = count($saved) . ' imported, ' . count($skipped) . ' skipped (already exist), ' . count($errors) . ' failed.';
        return response()->json([
            'success' => true,
            'saved'   => $saved,
            'skipped' => $skipped,
            'errors'  => $errors,
            'message' => $msg,
        ]);
    }

    /* ────────────────────────────────────────────────────────────
     * Duplicate detection: match by SKU (barcode) then by name
     * ──────────────────────────────────────────────────────────── */
    private function findExisting(array $data): ?Product
    {
        $sku = $data['sku'] ?? $data['barcode'] ?? null;
        if ($sku) {
            $found = Product::where('barcode', $sku)->first();
            if ($found) return $found;
        }
        return Product::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first();
    }

    /* ────────────────────────────────────────────────────────────
     * Image: download external image and save locally
     * ──────────────────────────────────────────────────────────── */

    /**
     * Entry point: decide whether URL is a direct image or a product page.
     * Downloads the image, saves it, creates an uploads record and returns the upload ID.
     * Returns 0 on failure.
     */
    private function downloadAndSaveImage(string $url): int
    {
        if (empty($url)) return 0;

        // Skip already-local URLs
        if (preg_match('#(127\.0\.0\.1|localhost)#i', $url)) return 0;

        // If URL path ends with a known image extension, download directly
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
            return $this->fetchAndSaveImage($url, $ext);
        }

        // Otherwise treat as a product page — scrape og:image
        $ogImage = $this->scrapeOgImage($url);
        if ($ogImage) {
            $ext2 = strtolower(pathinfo(parse_url($ogImage, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
            return $this->fetchAndSaveImage($ogImage, $ext2);
        }

        return 0;
    }

    /**
     * Download binary image from $url, save to public/uploads/all/, insert an uploads
     * record, and return the upload ID (0 on error).
     */
    private function fetchAndSaveImage(string $url, string $ext): int
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout'       => 15,
                    'user_agent'    => 'Mozilla/5.0 (compatible; BHSSupplies/1.0)',
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $binary = @file_get_contents($url, false, $context);
            if (empty($binary) || strlen($binary) < 500) return 0;

            // Validate it's actually an image
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($binary);
            if (!str_starts_with($mime, 'image/')) return 0;

            $dir = public_path('uploads/all');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $ext      = $ext ?: 'jpg';
            $filename = 'import_' . uniqid() . '.' . $ext;
            $filePath = $dir . '/' . $filename;
            file_put_contents($filePath, $binary);

            $relativePath  = 'uploads/all/' . $filename;
            $originalName  = pathinfo(parse_url($url, PHP_URL_PATH) ?? $filename, PATHINFO_FILENAME);
            $adminUserId   = User::where('user_type', 'admin')->value('id') ?? 1;

            $uploadId = Upload::insertGetId([
                'file_name'          => $relativePath,
                'file_original_name' => Str::limit($originalName, 240),
                'user_id'            => $adminUserId,
                'file_size'          => strlen($binary),
                'extension'          => $ext,
                'type'               => 'image',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            return $uploadId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Fetch product page HTML and extract og:image meta content.
     */
    private function scrapeOgImage(string $url): string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout'       => 10,
                    'user_agent'    => 'Mozilla/5.0 (compatible; BHSSupplies/1.0)',
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $html = @file_get_contents($url, false, $context);
            if (empty($html)) return '';

            // og:image
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
                return $m[1];
            }
            if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $html, $m)) {
                return $m[1];
            }

            // twitter:image fallback
            if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
                return $m[1];
            }

            return '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Core: create one Product in DB
     * ──────────────────────────────────────────────────────────── */
    private function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            // ── Admin user ──────────────────────────────────────
            $adminUser = User::where('user_type', 'admin')->first();

            // ── Resolve brand ───────────────────────────────────
            $brandId = null;
            if (!empty($data['brand'])) {
                $brand = Brand::where('name', $data['brand'])->first();
                if (!$brand) {
                    $brandId = DB::table('brands')->insertGetId([
                        'name'       => $data['brand'],
                        'slug'       => Str::slug($data['brand']) . '-' . rand(100,999),
                        'logo'       => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $brandId = $brand->id;
                }
            }

            // ── Resolve category ────────────────────────────────
            $categoryId = null;
            // If the user selected a category from the dropdown, use it directly
            if (!empty($data['category_id'])) {
                $categoryId = (int) $data['category_id'];
            }
            if (!$categoryId) {
                $catName = $data['category'] ?? $data['brand'] ?? null;
                if ($catName) {
                    $category = Category::whereRaw('LOWER(name) = ?', [strtolower($catName)])->first();
                    if (!$category) {
                        $categoryId = DB::table('categories')->insertGetId([
                            'name'        => $catName,
                            'slug'        => Str::slug($catName) . '-' . rand(100,999),
                            'parent_id'   => 0,
                            'level'       => 0,
                            'order_level' => 0,
                            'digital'     => 0,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    } else {
                        $categoryId = $category->id;
                    }
                }
            }
            if (!$categoryId) {
                $categoryId = Category::where('level', 0)->value('id') ?? 1;
            }

            // ── Slug (unique) ───────────────────────────────────
            $slug      = Str::slug($data['name']);
            $slugCount = Product::where('slug', 'LIKE', $slug . '%')->count();
            if ($slugCount) {
                $slug .= '-' . ($slugCount + 1);
            }

            // ── Unit price ──────────────────────────────────────
            $price = isset($data['unit_price']) ? (float) $data['unit_price'] : 0.00;

            // ── Image: if pre-downloaded upload IDs were passed, use them.
            //         Otherwise download single URL from scraper/AI enrichment.
            $uploadIds = [];
            if (!empty($data['_upload_ids']) && is_array($data['_upload_ids'])) {
                $uploadIds = array_filter(array_map('intval', $data['_upload_ids']));
            }
            if (empty($uploadIds)) {
                $rawImageUrl = $data['image'] ?? '';
                if (empty($rawImageUrl) && !empty($data['photos']) && is_array($data['photos'])) {
                    $rawImageUrl = $data['photos'][0] ?? '';
                }
                if ($rawImageUrl) {
                    $id = $this->downloadAndSaveImage($rawImageUrl);
                    if ($id > 0) $uploadIds[] = $id;
                }
            }
            $uploadId = $uploadIds[0] ?? 0;

            // ── Tags (ensure string) ────────────────────────────
            $tags = $data['tags'] ?? '';
            if (is_array($tags)) {
                $tags = implode(',', $tags);
            }
            if (empty($tags)) {
                $tags = $data['name'];
            }

            // ── Description (prefer AI-enhanced) ───────────────
            $description      = $data['description'] ?? '';
            $shortDescription = $data['short_description'] ?? '';

            // ── Image value: store upload ID (or empty string if none) ─
            $imageValue = $uploadId > 0 ? (string) $uploadId : '';
            // `photos` column stores comma-separated IDs of all gallery images
            $photosValue = !empty($uploadIds) ? implode(',', $uploadIds) : $imageValue;

            // ── Create product row ──────────────────────────────
            $product = Product::create([
                'name'                  => $data['name'],
                'added_by'              => 'admin',
                'user_id'               => $adminUser->id,
                'category_id'           => $categoryId,
                'brand_id'              => $brandId,
                'photos'                => $photosValue,
                'thumbnail_img'         => $imageValue,
                'tags'                  => $tags,
                'description'           => $description,
                'short_description'     => $shortDescription,
                'unit_price'            => $price,
                'purchase_price'        => 0,
                'variant_product'       => 0,
                'attributes'            => json_encode([]),
                'choice_options'        => json_encode([]),
                'colors'                => json_encode([]),
                'variations'            => json_encode([]),
                'todays_deal'           => 0,
                'published'             => 1,
                'approved'              => 1,
                'stock_visibility_state'=> 'quantity',
                'cash_on_delivery'      => 1,
                'featured'              => 0,
                'seller_featured'       => 0,
                'current_stock'         => (int)($data['quantity'] ?? 10),
                'unit'                  => $data['unit'] ?? 'pcs',
                'weight'                => 0,
                'min_qty'               => 1,
                'low_stock_quantity'    => 1,
                'discount'              => 0,
                'discount_type'         => 'percent',
                'shipping_type'         => 'free',
                'shipping_cost'         => 0,
                'is_quantity_multiplied'=> 0,
                'num_of_sale'           => 0,
                'meta_title'            => $data['seo_title'] ?? $data['meta_title'] ?? $data['name'],
                'meta_description'      => $data['seo_description'] ?? $data['meta_description'] ?? '',
                'meta_img'              => $imageValue,
                'slug'                  => $slug,
                'barcode'               => $data['sku'] ?? $data['barcode'] ?? '',
                'digital'               => 0,
                'auction_product'       => 0,
                'wholesale_product'     => 0,
                'rating'                => 0,
                'refundable'            => 1,
            ]);

            // ── Stock record ────────────────────────────────────
            ProductStock::create([
                'product_id' => $product->id,
                'variant'    => '',
                'price'      => $price,
                'sku'        => $data['sku'] ?? $data['barcode'] ?? '',
                'qty'        => (int)($data['quantity'] ?? 10),
                'image'      => $uploadId > 0 ? $uploadId : null,
            ]);

            // ── Translation ─────────────────────────────────────
            $lang = env('DEFAULT_LANGUAGE', 'en');
            if (!ProductTranslation::where('product_id', $product->id)->where('lang', $lang)->exists()) {
                ProductTranslation::create([
                    'lang'              => $lang,
                    'product_id'        => $product->id,
                    'name'              => $data['name'],
                    'unit'              => $data['unit'] ?? 'pcs',
                    'description'       => $description,
                    'short_description' => $shortDescription,
                ]);
            }

            return $product;
        });
    }
}
