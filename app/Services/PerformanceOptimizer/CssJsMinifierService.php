<?php

namespace App\Services\PerformanceOptimizer;

use App\Models\PerformanceOptimizer\OptimizationLog;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CssJsMinifierService
{
    protected array $cssRoots;
    protected array $jsRoots;

    public function __construct()
    {
        $this->cssRoots = [
            public_path('assets/css'),
            public_path('frontend/css'),
            public_path('backend/css'),
            public_path('css'),
        ];
        $this->jsRoots = [
            public_path('assets/js'),
            public_path('frontend/js'),
            public_path('backend/js'),
            public_path('js'),
        ];
    }

    // ── CSS ──────────────────────────────────────────────────────────

    public function minifyAllCss(): array
    {
        $excludes = $this->getExcludes('perf_css_minify_exclude');
        $results  = ['files' => 0, 'minified' => 0, 'skipped' => 0, 'failed' => 0, 'saved_bytes' => 0];

        foreach ($this->cssRoots as $root) {
            $this->walkExt($root, 'css', function ($file) use (&$results, $excludes) {
                if (str_contains(basename($file), '.min.')) {
                    $results['skipped']++;
                    return;
                }
                if ($this->isExcluded(basename($file), $excludes)) {
                    $results['skipped']++;
                    return;
                }
                $results['files']++;
                $r = $this->minifyCssFile($file);
                if ($r['success'] ?? false) {
                    $results['minified']++;
                    $results['saved_bytes'] += $r['saved_bytes'] ?? 0;
                } else {
                    $results['failed']++;
                }
            });
        }
        $results['saved_human'] = $this->humanSize($results['saved_bytes']);
        return $results;
    }

    public function minifyCssFile(string $sourcePath): array
    {
        $origSize = @filesize($sourcePath) ?: 0;
        $minPath  = $this->minifiedPath($sourcePath);
        try {
            $css      = File::get($sourcePath);
            $minified = $this->minifyCssContent($css);
            File::put($minPath, $minified);
            $savedBytes = max(0, $origSize - strlen($minified));

            OptimizationLog::create([
                'type'   => 'css_minify', 'action' => 'minify', 'status' => 'success', 'target' => $sourcePath,
                'meta'   => ['original_size' => $origSize, 'minified_size' => strlen($minified), 'space_saved_bytes' => $savedBytes],
            ]);
            return ['success' => true, 'saved_bytes' => $savedBytes, 'min_path' => $minPath];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function minifyCssContent(string $css): string
    {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([:;{},>~+])\s*/', '$1', $css);
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }

    // ── JS ───────────────────────────────────────────────────────────

    public function minifyAllJs(): array
    {
        $excludes = $this->getExcludes('perf_js_minify_exclude');
        $results  = ['files' => 0, 'minified' => 0, 'skipped' => 0, 'failed' => 0, 'saved_bytes' => 0];

        foreach ($this->jsRoots as $root) {
            $this->walkExt($root, 'js', function ($file) use (&$results, $excludes) {
                if (str_contains(basename($file), '.min.')) {
                    $results['skipped']++;
                    return;
                }
                if ($this->isExcluded(basename($file), $excludes)) {
                    $results['skipped']++;
                    return;
                }
                $results['files']++;
                $r = $this->minifyJsFile($file);
                if ($r['success'] ?? false) {
                    $results['minified']++;
                    $results['saved_bytes'] += $r['saved_bytes'] ?? 0;
                } else {
                    $results['failed']++;
                }
            });
        }
        $results['saved_human'] = $this->humanSize($results['saved_bytes']);
        return $results;
    }

    public function minifyJsFile(string $sourcePath): array
    {
        if (!$this->jsMinifierAvailable()) {
            return ['success' => false, 'error' => 'JShrink not installed — run: composer require tedivm/jshrink'];
        }
        $origSize = @filesize($sourcePath) ?: 0;
        $minPath  = $this->minifiedPath($sourcePath);
        try {
            $js       = File::get($sourcePath);
            $minified = $this->minifyJsContent($js);
            if ($minified === null) {
                return ['success' => false, 'error' => 'Minifier returned null'];
            }
            File::put($minPath, $minified);
            $savedBytes = max(0, $origSize - strlen($minified));

            OptimizationLog::create([
                'type'   => 'js_minify', 'action' => 'minify', 'status' => 'success', 'target' => $sourcePath,
                'meta'   => ['original_size' => $origSize, 'minified_size' => strlen($minified), 'space_saved_bytes' => $savedBytes],
            ]);
            return ['success' => true, 'saved_bytes' => $savedBytes, 'min_path' => $minPath];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Returns null if no safe minifier is available — caller MUST treat null as "skip".
     * The previous naive regex stripped `//` from URL strings like 'http://example.com',
     * corrupting any JS file that contained URLs. We no longer do that.
     */
    public function minifyJsContent(string $js): ?string
    {
        if (class_exists('\JShrink\Minifier')) {
            try {
                return \JShrink\Minifier::minify($js, ['flaggedComments' => false]);
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function jsMinifierAvailable(): bool
    {
        return class_exists('\JShrink\Minifier');
    }

    // ── Image dimension scanner / fixer (theme blade) ───────────────

    public function scanMissingDimensions(): array
    {
        $missing  = [];
        $themeDir = resource_path('views/frontend');
        if (!is_dir($themeDir)) return $missing;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $lines = @file($file->getRealPath()) ?: [];
            foreach ($lines as $lineNo => $line) {
                if (stripos($line, '<img') === false) continue;
                if (stripos($line, 'width') === false || stripos($line, 'height') === false) {
                    $missing[] = [
                        'file'    => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath()),
                        'line'    => $lineNo + 1,
                        'snippet' => trim($line),
                    ];
                }
            }
            if (count($missing) >= 200) break;
        }
        return $missing;
    }

    public function autoFixDimensions(): array
    {
        $fixed = 0; $skipped = 0;
        $themeDir = resource_path('views/frontend');
        if (!is_dir($themeDir)) return compact('fixed', 'skipped');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $abs   = $file->getRealPath();
            $lines = @file($abs);
            if (!$lines) continue;

            $changed = false;
            foreach ($lines as $i => $line) {
                if (stripos($line, '<img') === false) continue;
                $hasW = (bool) preg_match('/\bwidth\s*=\s*["\'\d]/i', $line);
                $hasH = (bool) preg_match('/\bheight\s*=\s*["\'\d]/i', $line);
                if ($hasW && $hasH) continue;

                if (str_contains($line, '{{') || str_contains($line, '<?')) { $skipped++; continue; }
                if (!preg_match('/\bsrc=["\']([^"\']+)["\']/i', $line, $m)) { $skipped++; continue; }

                $src = trim($m[1]);
                if (preg_match('#^(https?:)?//#i', $src) || str_starts_with($src, 'data:')) { $skipped++; continue; }
                if (str_ends_with(strtolower($src), '.svg')) { $skipped++; continue; }

                $local = public_path(ltrim(str_replace('/', DIRECTORY_SEPARATOR, $src), DIRECTORY_SEPARATOR));
                if (!file_exists($local)) { $skipped++; continue; }

                $dims = @getimagesize($local);
                if (!$dims || $dims[0] <= 0 || $dims[1] <= 0) { $skipped++; continue; }

                [$w, $h] = $dims;
                $new = $line;
                if (!$hasW) $new = preg_replace('/(<img\b)/i', "$1 width=\"{$w}\"", $new, 1);
                if (!$hasH) $new = preg_replace('/(<img\b)/i', "$1 height=\"{$h}\"", $new, 1);
                if ($new !== $line) {
                    $lines[$i] = $new;
                    $changed = true;
                    $fixed++;
                }
            }
            if ($changed) {
                @file_put_contents($abs, implode('', $lines));
            }
        }
        return compact('fixed', 'skipped');
    }

    // ── Delay-JS payload (user-interaction triggered) ───────────────

    public function getDelayScript(): string
    {
        return <<<JS
<script data-perfopt="delay-js">
(function(){
    var delayed = [];
    var fired   = false;
    document.querySelectorAll('script[type="text/perf-delay"]').forEach(function(node){
        var attrs = [];
        Array.prototype.forEach.call(node.attributes, function(attr){
            if (attr.name !== 'type' && attr.name !== 'data-perf-delay' && attr.name !== 'src') {
                attrs.push([attr.name, attr.value]);
            }
        });
        delayed.push({ src: node.getAttribute('src'), content: node.textContent || '', attrs: attrs });
        node.parentNode.removeChild(node);
    });
    function appendNext(queue){
        if (!queue.length) return;
        var d = queue.shift();
        var s = document.createElement('script');
        d.attrs.forEach(function(attr){ s.setAttribute(attr[0], attr[1]); });
        if (d.src) {
            s.src = d.src;
            s.async = false;
            s.onload = s.onerror = function(){ appendNext(queue); };
        } else {
            s.textContent = d.content;
        }
        (document.body || document.documentElement).appendChild(s);
        if (!d.src) appendNext(queue);
    }
    function loadDelayed(){
        if (fired) return; fired = true;
        appendNext(delayed);
    }
    ['click','scroll','keydown','touchstart','mousemove'].forEach(function(e){
        window.addEventListener(e, loadDelayed, { once: true, passive: true });
    });
    setTimeout(loadDelayed, 10000);
})();
</script>
JS;
    }

    // ── Helpers ──────────────────────────────────────────────────────

    protected function walkExt(string $dir, string $ext, callable $cb): void
    {
        if (!is_dir($dir)) return;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === $ext) {
                    $cb($file->getRealPath());
                }
            }
        } catch (Exception $e) {
            Log::warning('[PerfOptimizer] walkExt: ' . $e->getMessage());
        }
    }

    protected function minifiedPath(string $source): string
    {
        $info = pathinfo($source);
        return $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '.min.' . $info['extension'];
    }

    protected function getExcludes(string $key): array
    {
        $raw = (string) get_setting($key, '');
        return array_filter(array_map('trim', explode("\n", $raw)));
    }

    protected function isExcluded(string $filename, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if ($p === '') continue;
            if (fnmatch($p, $filename) || stripos($filename, $p) !== false) return true;
        }
        return false;
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
