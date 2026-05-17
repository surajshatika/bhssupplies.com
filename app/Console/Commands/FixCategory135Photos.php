<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCategory135Photos extends Command
{
    protected $signature   = 'fix:category135-photos {--dry-run : Preview changes without writing to DB}';
    protected $description = 'Migrate category_id=135 products: convert file-path photos to uploads table IDs';

    /** @var int|null Cached placeholder upload ID */
    private ?int $placeholderId = null;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $adminId = DB::table('users')->where('user_type', 'admin')->value('id') ?? 1;

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be written.');
        }

        $products = Product::where('category_id', 135)->get();
        $this->info("Found {$products->count()} products in category 135.");

        $fixed   = 0;
        $already = 0;
        $empty   = 0;
        $errors  = 0;

        foreach ($products as $product) {
            $photos    = $product->photos ?? '';
            $thumbnail = $product->thumbnail_img ?? '';
            $metaImg   = $product->meta_img ?? '';

            // ── Case 1: already a numeric upload ID ─────────────────
            if (is_numeric($photos) && (int) $photos > 0) {
                $already++;
                continue;
            }

            // ── Case 2: path-based (uploads/all/xxx.jpg) ────────────
            if (str_starts_with($photos, 'uploads/')) {
                $filePath = public_path($photos);

                if (!file_exists($filePath)) {
                    $this->warn("  [MISSING FILE] Product #{$product->id}: {$photos}");
                    // fall through to placeholder
                } else {
                    try {
                        $uploadId = $this->getOrCreateUpload($photos, $adminId, $dryRun);
                        if (!$dryRun) {
                            $product->update([
                                'photos'        => (string) $uploadId,
                                'thumbnail_img' => (string) $uploadId,
                                'meta_img'      => (string) $uploadId,
                            ]);
                            // Fix product_stocks.image for this product if it has a path
                            DB::table('product_stocks')
                                ->where('product_id', $product->id)
                                ->where('image', $photos)
                                ->update(['image' => $uploadId]);
                        }
                        $this->line("  [FIXED] #{$product->id}: '{$photos}' → upload #{$uploadId}");
                        $fixed++;
                    } catch (\Throwable $e) {
                        $this->error("  [ERROR] #{$product->id}: " . $e->getMessage());
                        $errors++;
                    }
                    continue;
                }
            }

            // ── Case 3: empty / null / missing file → placeholder ───
            $empty++;
            try {
                $placeholderId = $this->getPlaceholderUploadId($adminId, $dryRun);
                if (!$dryRun) {
                    $product->update([
                        'photos'        => (string) $placeholderId,
                        'thumbnail_img' => (string) $placeholderId,
                        'meta_img'      => (string) $placeholderId,
                    ]);
                }
                $this->line("  [PLACEHOLDER] #{$product->id} \"{$product->name}\" → upload #{$placeholderId}");
            } catch (\Throwable $e) {
                $this->error("  [ERROR] placeholder for #{$product->id}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Done. Fixed: {$fixed} | Already correct: {$already} | Placeholder assigned: {$empty} | Errors: {$errors}");

        return self::SUCCESS;
    }

    /**
     * Look up an existing Upload for the given file_name, or create one.
     * Returns the upload ID.
     */
    private function getOrCreateUpload(string $relativePath, int $userId, bool $dryRun): int
    {
        // Re-use existing upload record if one already points to this file
        $existing = Upload::where('file_name', $relativePath)->first();
        if ($existing) {
            return $existing->id;
        }

        $fullPath      = public_path($relativePath);
        $filename      = basename($relativePath);
        $ext           = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $originalName  = pathinfo($filename, PATHINFO_FILENAME);
        $fileSize      = file_exists($fullPath) ? filesize($fullPath) : 0;

        if ($dryRun) {
            // Return a fake ID for display purposes
            return -1;
        }

        return Upload::insertGetId([
            'file_name'          => $relativePath,
            'file_original_name' => $originalName,
            'user_id'            => $userId,
            'file_size'          => $fileSize,
            'extension'          => $ext,
            'type'               => 'image',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    /**
     * Get or create a single placeholder upload record (cached for the run).
     */
    private function getPlaceholderUploadId(int $userId, bool $dryRun): int
    {
        if ($this->placeholderId !== null) {
            return $this->placeholderId;
        }

        // Look for an existing placeholder record
        $existing = Upload::where('file_name', 'assets/img/placeholder.jpg')->first();
        if ($existing) {
            $this->placeholderId = $existing->id;
            return $this->placeholderId;
        }

        if ($dryRun) {
            return -2;
        }

        $placeholderPath = public_path('assets/img/placeholder.jpg');
        $this->placeholderId = Upload::insertGetId([
            'file_name'          => 'assets/img/placeholder.jpg',
            'file_original_name' => 'placeholder',
            'user_id'            => $userId,
            'file_size'          => file_exists($placeholderPath) ? filesize($placeholderPath) : 0,
            'extension'          => 'jpg',
            'type'               => 'image',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $this->placeholderId;
    }
}
