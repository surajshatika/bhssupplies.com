<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FixUniliteImages extends Command
{
    protected $signature = 'fix:unilite-images {--limit=0 : Max products to process (0=all)} {--force : Re-download even if image exists}';
    protected $description = 'Fetch missing images for Unilite products from hansler.com + Gemini AI fallback';

    private ?int $adminUserId = null;

    /** Ordered list of image-finding strategies */
    private array $strategies = ['hansler', 'yesss', 'diy', 'gemini'];

    public function handle(): int
    {
        $this->adminUserId = User::where('user_type', 'admin')->value('id') ?? 1;
        $limit  = (int) $this->option('limit');
        $force  = $this->option('force');

        $query = Product::where('brand_id', $this->resolveBrandId())
            ->orderBy('id');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('thumbnail_img')->orWhere('thumbnail_img', '');
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get(['id', 'name', 'barcode', 'slug', 'thumbnail_img']);
        $total    = $products->count();

        $this->info("Unilite image fixer — {$total} products to process");
        $this->newLine();

        $done    = 0;
        $failed  = 0;
        $bar     = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->start();

        foreach ($products as $product) {
            // Extract product code from name ("PL-3 275 Lumen..." → "PL-3")
            $code = $this->extractCode($product->name, $product->barcode);
            $bar->setMessage($code);

            $imageUrl = $this->findImage($code, $product->name);

            if ($imageUrl) {
                $uploadId = $this->downloadImage($imageUrl, $code);
                if ($uploadId > 0) {
                    $val = (string) $uploadId;
                    $product->thumbnail_img = $val;
                    $product->photos        = $val;
                    $product->meta_img      = $val;
                    $product->save();

                    ProductStock::where('product_id', $product->id)
                        ->update(['image' => $uploadId]);

                    $done++;
                } else {
                    $failed++;
                }
            } else {
                // Final resort: generate with DALL-E
                $uploadId = $this->generateWithDalle($product->name, $code);
                if ($uploadId > 0) {
                    $val = (string) $uploadId;
                    $product->thumbnail_img = $val;
                    $product->photos        = $val;
                    $product->meta_img      = $val;
                    $product->save();
                    ProductStock::where('product_id', $product->id)
                        ->update(['image' => $uploadId]);
                    $done++;
                } else {
                    $failed++;
                }
            }

            $bar->advance();
            usleep(400000); // 0.4s polite delay
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done — Updated: {$done} | Failed: {$failed}");

        return self::SUCCESS;
    }

    // ── Strategy orchestrator ─────────────────────────────────────────────

    private function findImage(string $code, string $fullName): string
    {
        // 1. hansler.com — only accepts image if product code in URL
        $imageUrl = $this->searchHansler($code);
        if ($imageUrl) return $imageUrl;

        // 2. yesss.co.uk
        $imageUrl = $this->searchYesss($code);
        if ($imageUrl) return $imageUrl;

        // 3. diy.com
        $imageUrl = $this->searchDiy($code);
        if ($imageUrl) return $imageUrl;

        // 4. Gemini AI with Google Search grounding
        $imageUrl = $this->searchViaGemini($code, $fullName);
        if ($imageUrl) return $imageUrl;

        return '';
    }

    // ── Hansler.com ───────────────────────────────────────────────────────

    private function searchHansler(string $code): string
    {
        $url  = 'https://www.hansler.com/search?q=' . urlencode('Unilite ' . $code);
        $html = $this->httpGet($url);
        if (!$html) return '';

        // Build a loose version of the code for URL matching
        // e.g. "PL-3" → "pl-3", "CRI-1250R" → "cri-1250r"
        $codeSlug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $code));

        // Collect ALL hansler CDN image URLs from the page
        preg_match_all(
            '/(?:data-lazy-src|src)=["\']([^"\']*hansler\.com\/cdn\/shop\/files\/[^"\']+\.(jpg|jpeg|png|webp)[^"\']*)["\']/',
            $html, $m
        );
        $candidates = array_unique($m[1] ?? []);

        // First pass: only accept images whose filename contains the product code
        foreach ($candidates as $src) {
            $filename = strtolower(basename(parse_url($src, PHP_URL_PATH) ?? ''));
            if (str_contains($filename, $codeSlug)) {
                $src = preg_replace('/\{width\}/', '1024', $src) ?? $src;
                $src = preg_replace('/_\{width\}x/', '_1024x1024', $src) ?? $src;
                return $this->normaliseUrl($src);
            }
        }

        // Second pass: try product-card specific elements (grid-product__image class)
        if (preg_match(
            '/class=["\'][^"\']*(?:grid-product|product-card|product__image)[^"\']*["\'][^>]*>.*?src=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/is',
            $html, $m
        )) {
            $src = preg_replace('/\{width\}/', '1024', $m[1]) ?? $m[1];
            return $this->normaliseUrl($src);
        }

        // Nothing matched (banner/lifestyle images excluded)
        return '';
    }

    // ── YESSS ─────────────────────────────────────────────────────────────

    private function searchYesss(string $code): string
    {
        $url  = 'https://www.yesss.co.uk/catalogsearch/result/?q=' . urlencode('Unilite ' . $code);
        $html = $this->httpGet($url);
        if (!$html) return '';

        if (preg_match('/data-src=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/', $html, $m) ||
            preg_match('/<img[^>]+src=["\']([^"\']+catalog\/product[^"\']+\.(jpg|jpeg|png|webp))["\']/', $html, $m)) {
            return $this->normaliseUrl($m[1]);
        }

        return '';
    }

    // ── DIY.com ───────────────────────────────────────────────────────────

    private function searchDiy(string $code): string
    {
        $url  = 'https://www.diy.com/search?term=' . urlencode('Unilite ' . $code);
        $html = $this->httpGet($url);
        if (!$html) return '';

        // diy.com uses JSON data in HTML
        if (preg_match('/"imageUrl"\s*:\s*"([^"]+\.(jpg|jpeg|png|webp))"/', $html, $m) ||
            preg_match('/data-image-url=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/', $html, $m)) {
            return $this->normaliseUrl($m[1]);
        }

        return '';
    }

    // ── Gemini AI web search ──────────────────────────────────────────────

    private function searchViaGemini(string $code, string $fullName): string
    {
        $apiKey = get_setting('seo_gemini_api_key') ?? config('seo.providers.gemini.api_key');
        if (!$apiKey) return '';

        try {
            // Ask Gemini to search the web and return a direct image URL
            $prompt = "Search the web for a product image of the Unilite {$code} — \"{$fullName}\".\n"
                    . "Look on these sites in order: hansler.com, yesss.co.uk, tooled-up.com, diy.com, amazon.co.uk.\n"
                    . "I need a DIRECT image URL (ending in .jpg, .jpeg, .png or .webp) that I can download.\n"
                    . "Reply with ONLY the bare URL — no markdown, no explanation, no quotes, nothing else.";

            $response = Http::timeout(25)
                ->withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'tools'    => [['google_search' => (object)[]]],
                    'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 300],
                ]);

            if ($response->successful()) {
                // Gather text from all parts (grounding sometimes splits them)
                $parts = $response->json('candidates.0.content.parts') ?? [];
                $text  = implode(' ', array_column($parts, 'text'));
                $text  = trim($text);

                // Extract first valid image URL
                if (preg_match('/(https?:\/\/[^\s\'"<>\]\)]+\.(jpg|jpeg|png|webp))(\?[^\s]*)?/i', $text, $m)) {
                    $url = $m[1] . ($m[3] ?? '');
                    // Sanity-check: must not be a known bad domain
                    if (!preg_match('#(unilite\.co\.uk|cloudflare|placeholder)#i', $url)) {
                        return $url;
                    }
                }

                // Fallback: Gemini sometimes returns a grounding chunk with image source URL
                $chunks = $response->json('candidates.0.groundingMetadata.groundingChunks') ?? [];
                foreach ($chunks as $chunk) {
                    $uri = $chunk['web']['uri'] ?? '';
                    if (preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $uri)) {
                        return $uri;
                    }
                }
            }
        } catch (\Throwable) {}

        return '';
    }

    // ── Additional scraper: tooled-up.com ─────────────────────────────────

    private function searchTooledUp(string $code): string
    {
        // tooled-up returns 403 via Cloudflare, skip
        return '';
    }

    // ── DALL-E 3 image generation (final fallback) ────────────────────────

    private function generateWithDalle(string $fullName, string $code): int
    {
        $apiKey = get_setting('seo_openai_api_key') ?? env('OPENAI_API_KEY');
        if (!$apiKey) return 0;

        try {
            $prompt = "Professional product photograph of the Unilite {$code} — {$fullName}. "
                    . "Clean white background, studio lighting, high detail, e-commerce style. "
                    . "The product is a professional work/safety lighting or tool made by Unilite brand.";

            $response = Http::timeout(60)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model'           => 'dall-e-3',
                    'prompt'          => $prompt,
                    'n'               => 1,
                    'size'            => '1024x1024',
                    'quality'         => 'standard',
                    'response_format' => 'url',
                ]);

            if ($response->successful()) {
                $imageUrl = $response->json('data.0.url') ?? '';
                if ($imageUrl) {
                    return $this->downloadImage($imageUrl, $code);
                }
            }
        } catch (\Throwable) {}

        return 0;
    }

    // ── Image download ────────────────────────────────────────────────────

    private function downloadImage(string $url, string $label): int
    {
        if (empty($url)) return 0;

        $ext    = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
        $binary = $this->httpGet($url);

        if (empty($binary) || strlen($binary) < 500) return 0;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($binary);
        if (!str_starts_with($mime, 'image/')) return 0;

        $dir = public_path('uploads/all');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'unilite_' . Str::slug($label) . '_' . uniqid() . '.' . $ext;
        file_put_contents($dir . '/' . $filename, $binary);

        return Upload::insertGetId([
            'file_name'          => 'uploads/all/' . $filename,
            'file_original_name' => Str::limit($label, 240),
            'user_id'            => $this->adminUserId,
            'file_size'          => strlen($binary),
            'extension'          => $ext,
            'type'               => 'image',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    // ── Utilities ─────────────────────────────────────────────────────────

    private function extractCode(string $name, string $barcode): string
    {
        // Name format: "PL-3 275 Lumen Pocket..."
        if (preg_match('/^([A-Z0-9][A-Z0-9\-+\.\/]+)\s/', $name, $m)) {
            return $m[1];
        }
        return $barcode;
    }

    private function normaliseUrl(string $url): string
    {
        $url = trim($url);
        // Replace {width} placeholders with fixed size
        $url = str_replace('{width}', '1024', $url);
        $url = preg_replace('/_\{width\}x/', '_1024x1024', $url) ?? $url;
        // Add protocol if missing
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        return $url;
    }

    private function httpGet(string $url, int $timeout = 10): string
    {
        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout'       => $timeout,
                    'user_agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'ignore_errors' => true,
                    'header'        => "Accept: text/html,application/xhtml+xml,image/*\r\nAccept-Language: en-GB,en;q=0.9\r\nReferer: https://www.google.com/\r\n",
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            return (string) @file_get_contents($url, false, $ctx);
        } catch (\Throwable) {
            return '';
        }
    }

    private function resolveBrandId(): int
    {
        return (int) DB::table('brands')->where('name', 'Unilite')->value('id');
    }
}
