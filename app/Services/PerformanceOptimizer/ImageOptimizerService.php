<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Exception;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

class ImageOptimizerService
{
    protected string $publicBase;
    protected string $backupBase;
    protected array  $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    protected array  $scanRoots;

    public function __construct()
    {
        $this->publicBase = public_path();
        $this->backupBase = storage_path('app/performance-backup');
        $this->scanRoots  = array_filter([
            public_path('uploads'),
            public_path('images'),
            public_path('storage'),
        ], 'is_dir');
    }

    public function getScanRoots(): array
    {
        return $this->scanRoots;
    }

    /**
     * Reject paths outside the configured scan roots (prevents traversal exploits).
     */
    public function isPathAllowed(string $absolute): bool
    {
        $real = @realpath($absolute);
        if (!$real) return false;
        foreach ($this->scanRoots as $root) {
            $rr = @realpath($root);
            if ($rr && str_starts_with($real, $rr . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }
        return false;
    }

    // ── Scan / Stats ─────────────────────────────────────────────────

    public function scanUnoptimized(int $limit = 500): array
    {
        $images = [];
        $count  = 0;
        foreach ($this->scanRoots as $root) {
            $this->walkImages($root, function ($path) use (&$images, &$count, $limit) {
                if ($count >= $limit) return;
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $webp = preg_replace('/\.' . $ext . '$/i', '.webp', $path);
                $size = @filesize($path) ?: 0;
                $images[] = [
                    'path'          => $path,
                    'relative'      => str_replace($this->publicBase . DIRECTORY_SEPARATOR, '', $path),
                    'size'          => $size,
                    'size_human'    => $this->humanSize($size),
                    'webp_exists'   => file_exists($webp),
                    'has_backup'    => $this->hasBackup($path),
                    'extension'     => $ext,
                ];
                $count++;
            });
        }
        return $images;
    }

    public function scanOversized(int $minBytes = 500000, int $minWidth = 2000, int $limit = 100): array
    {
        $items = [];
        foreach ($this->scanRoots as $root) {
            $this->walkImages($root, function ($path) use (&$items, $minBytes, $minWidth, $limit) {
                if (count($items) >= $limit) return;
                $size = @filesize($path) ?: 0;
                if ($size < $minBytes) return;
                $info = @getimagesize($path);
                if (!$info || ($info[0] ?? 0) < $minWidth) return;
                $items[] = [
                    'relative'  => str_replace($this->publicBase . DIRECTORY_SEPARATOR, '', $path),
                    'size'      => $size,
                    'size_human'=> $this->humanSize($size),
                    'width'     => $info[0],
                    'height'    => $info[1],
                ];
            });
        }
        usort($items, fn ($a, $b) => $b['size'] <=> $a['size']);
        return $items;
    }

    public function getStats(): array
    {
        $total = $converted = $avifConverted = $spaceSaved = $avifSpaceSaved = $backupSize = 0;
        foreach ($this->scanRoots as $root) {
            $this->walkImages($root, function ($path) use (&$total, &$converted, &$avifConverted, &$spaceSaved, &$avifSpaceSaved) {
                $total++;
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $webp = preg_replace('/\.' . $ext . '$/i', '.webp', $path);
                $avif = preg_replace('/\.' . $ext . '$/i', '.avif', $path);
                if (file_exists($webp)) {
                    $converted++;
                    $spaceSaved += max(0, (@filesize($path) ?: 0) - (@filesize($webp) ?: 0));
                }
                if (file_exists($avif)) {
                    $avifConverted++;
                    $avifSpaceSaved += max(0, (@filesize($path) ?: 0) - (@filesize($avif) ?: 0));
                }
            });
        }
        if (is_dir($this->backupBase)) {
            $backupSize = $this->directorySize($this->backupBase);
        }
        return [
            'total'         => $total,
            'converted'     => $converted,
            'not_converted' => max(0, $total - $converted),
            'space_saved'   => $this->humanSize($spaceSaved),
            'avif_converted'=> $avifConverted,
            'avif_space_saved' => $this->humanSize($avifSpaceSaved),
            'backup_size'   => $this->humanSize($backupSize),
        ];
    }

    // ── Backup ───────────────────────────────────────────────────────

    public function backupOriginal(string $absolutePath): string
    {
        $relative   = str_replace($this->publicBase . DIRECTORY_SEPARATOR, '', $absolutePath);
        $backupPath = $this->backupBase . DIRECTORY_SEPARATOR . $relative;
        if (!is_dir(dirname($backupPath))) {
            @mkdir(dirname($backupPath), 0777, true);
        }
        if (!file_exists($backupPath)) {
            @copy($absolutePath, $backupPath);
        }
        return $backupPath;
    }

    public function hasBackup(string $absolutePath): bool
    {
        $relative = str_replace($this->publicBase . DIRECTORY_SEPARATOR, '', $absolutePath);
        return file_exists($this->backupBase . DIRECTORY_SEPARATOR . $relative);
    }

    // ── Convert ──────────────────────────────────────────────────────

    public function convertSingleToWebp(string $absolutePath, ?int $quality = null): array
    {
        if (!$this->isPathAllowed($absolutePath)) {
            return ['success' => false, 'error' => 'Path not allowed'];
        }
        $quality = $quality ?? (int) get_setting('perf_image_webp_quality', 82);
        $ext     = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (!in_array($ext, $this->supportedExtensions)) {
            return ['success' => false, 'skipped' => true, 'reason' => 'unsupported_extension'];
        }
        if (!file_exists($absolutePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $webpPath = preg_replace('/\.' . $ext . '$/i', '.webp', $absolutePath);
        $origSize = @filesize($absolutePath) ?: 0;
        $this->backupOriginal($absolutePath);

        try {
            $manager = new ImageManager(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
            $image   = $manager->make($absolutePath);
            $image->save($webpPath, $quality, 'webp');

            $newSize    = @filesize($webpPath) ?: 0;
            $savedBytes = max(0, $origSize - $newSize);

            OptimizationLog::create([
                'type'   => 'image_convert',
                'action' => 'webp_convert',
                'status' => 'success',
                'target' => $absolutePath,
                'meta'   => [
                    'original_size'    => $origSize,
                    'webp_size'        => $newSize,
                    'space_saved_bytes'=> $savedBytes,
                    'quality'          => $quality,
                ],
            ]);

            return ['success' => true, 'saved_bytes' => $savedBytes, 'webp_path' => $webpPath];
        } catch (Exception $e) {
            Log::error('[PerfOptimizer] WebP convert failed: ' . $absolutePath . ' — ' . $e->getMessage());
            OptimizationLog::create([
                'type'          => 'image_convert',
                'action'        => 'webp_convert',
                'status'        => 'failed',
                'target'        => $absolutePath,
                'error_message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function convertAllToWebp(?int $quality = null, int $maxFiles = 200): array
    {
        $results = ['total' => 0, 'converted' => 0, 'skipped' => 0, 'failed' => 0, 'saved_bytes' => 0];

        foreach ($this->scanRoots as $root) {
            $this->walkImages($root, function ($path) use (&$results, $quality, $maxFiles) {
                if ($results['converted'] + $results['failed'] >= $maxFiles) return;
                $results['total']++;
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $webp = preg_replace('/\.' . $ext . '$/i', '.webp', $path);
                if (file_exists($webp)) {
                    $results['skipped']++;
                    return;
                }
                $result = $this->convertSingleToWebp($path, $quality);
                if ($result['success'] ?? false) {
                    $results['converted']++;
                    $results['saved_bytes'] += $result['saved_bytes'] ?? 0;
                } elseif ($result['skipped'] ?? false) {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                }
            });
        }
        $results['saved_human'] = $this->humanSize($results['saved_bytes']);
        return $results;
    }

    public function convertSingleToAvif(string $absolutePath, ?int $quality = null): array
    {
        if (!$this->isPathAllowed($absolutePath)) {
            return ['success' => false, 'error' => 'Path not allowed'];
        }
        if (!function_exists('imageavif')) {
            return ['success' => false, 'error' => 'AVIF not supported on this server (requires PHP 8.1+ GD with AVIF)'];
        }
        $quality  = $quality ?? (int) get_setting('perf_image_avif_quality', 70);
        $ext      = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $avifPath = preg_replace('/\.' . $ext . '$/i', '.avif', $absolutePath);
        $origSize = @filesize($absolutePath) ?: 0;
        $this->backupOriginal($absolutePath);

        try {
            $gd = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
                'png'         => @imagecreatefrompng($absolutePath),
                'gif'         => @imagecreatefromgif($absolutePath),
                default       => null,
            };
            if (!$gd) {
                return ['success' => false, 'error' => 'Could not load image'];
            }
            imageavif($gd, $avifPath, $quality);
            imagedestroy($gd);

            $newSize    = @filesize($avifPath) ?: 0;
            $savedBytes = max(0, $origSize - $newSize);

            OptimizationLog::create([
                'type'   => 'image_convert',
                'action' => 'avif_convert',
                'status' => 'success',
                'target' => $absolutePath,
                'meta'   => ['original_size' => $origSize, 'avif_size' => $newSize, 'space_saved_bytes' => $savedBytes],
            ]);
            return ['success' => true, 'saved_bytes' => $savedBytes];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function convertAllToAvif(?int $quality = null, int $maxFiles = 200): array
    {
        $results = ['total' => 0, 'converted' => 0, 'skipped' => 0, 'failed' => 0, 'saved_bytes' => 0];
        foreach ($this->scanRoots as $root) {
            $this->walkImages($root, function ($path) use (&$results, $quality, $maxFiles) {
                if ($results['converted'] + $results['failed'] >= $maxFiles) return;
                $results['total']++;
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $avif = preg_replace('/\.' . $ext . '$/i', '.avif', $path);
                if (file_exists($avif)) {
                    $results['skipped']++;
                    return;
                }
                $r = $this->convertSingleToAvif($path, $quality);
                if ($r['success'] ?? false) {
                    $results['converted']++;
                    $results['saved_bytes'] += $r['saved_bytes'] ?? 0;
                } else {
                    $results['failed']++;
                }
            });
        }
        $results['saved_human'] = $this->humanSize($results['saved_bytes']);
        return $results;
    }

    // ── Restore ──────────────────────────────────────────────────────

    public function restoreAll(): array
    {
        $results = ['restored' => 0, 'failed' => 0];
        if (!is_dir($this->backupBase)) {
            return $results + ['message' => 'No backup directory found.'];
        }

        $this->walkImages($this->backupBase, function ($backupPath) use (&$results) {
            $relative = str_replace($this->backupBase . DIRECTORY_SEPARATOR, '', $backupPath);
            $original = $this->publicBase . DIRECTORY_SEPARATOR . $relative;
            $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $webp     = preg_replace('/\.' . $ext . '$/i', '.webp', $original);
            $avif     = preg_replace('/\.' . $ext . '$/i', '.avif', $original);

            try {
                if (!is_dir(dirname($original))) {
                    @mkdir(dirname($original), 0777, true);
                }
                @copy($backupPath, $original);
                if (file_exists($webp)) @unlink($webp);
                if (file_exists($avif)) @unlink($avif);
                $results['restored']++;
            } catch (Exception $e) {
                $results['failed']++;
            }
        });

        OptimizationLog::create([
            'type' => 'image_convert', 'action' => 'restore_all', 'status' => 'success', 'meta' => $results,
        ]);
        return $results;
    }

    public function cleanOldBackups(int $days = 30): int
    {
        $days = max(1, $days); // never wipe everything by accident
        if (!is_dir($this->backupBase)) return 0;
        $cutoff = time() - ($days * 86400);
        $count  = 0;
        $this->walkImages($this->backupBase, function ($path) use ($cutoff, &$count) {
            if (@filemtime($path) < $cutoff) {
                @unlink($path);
                $count++;
            }
        });
        return $count;
    }

    // ── Compress on upload ───────────────────────────────────────────

    public function compressUploadedFile(string $absolutePath, int $quality = 85): bool
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->supportedExtensions)) {
            return false;
        }
        try {
            $manager = new ImageManager(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
            $img     = $manager->make($absolutePath);
            $img->save($absolutePath, $quality);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    protected function walkImages(string $dir, callable $callback): void
    {
        if (!is_dir($dir)) return;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $this->supportedExtensions)) {
                    $callback($file->getRealPath());
                }
            }
        } catch (Exception $e) {
            Log::warning('[PerfOptimizer] walkImages failed: ' . $e->getMessage());
        }
    }

    protected function directorySize(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        $size = 0;
        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) $size += $file->getSize();
            }
        } catch (Exception $e) {}
        return $size;
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
