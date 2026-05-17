<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class EnhanceProductDescriptions extends Command
{
    protected $signature   = 'products:enhance-descriptions
                                {--dry-run : Preview changes without saving}
                                {--id=     : Process a single product ID}';
    protected $description = 'One-time enhancement of all product description HTML (tables, lists, structure)';

    public function handle(): int
    {
        $query = Product::whereNotNull('description')->where('description', '!=', '');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $products = $query->get();
        $total    = $products->count();
        $changed  = 0;

        $this->info("Processing {$total} product(s)…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($products as $product) {
            $original = $product->description;
            $enhanced = static::enhanceHtml($original);

            if ($enhanced !== $original) {
                $changed++;
                if (!$this->option('dry-run')) {
                    $product->description = $enhanced;
                    $product->save();
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $label = $this->option('dry-run') ? 'Would update' : 'Updated';
        $this->info("{$label} {$changed} / {$total} product descriptions.");

        return 0;
    }

    /* ─────────────────────────────────────────────────────────────
     * Core HTML enhancer — pure PHP, no external dependencies
     * ───────────────────────────────────────────────────────────── */
    public static function enhanceHtml(string $html): string
    {
        if (!trim($html)) {
            return $html;
        }

        // ── 1. Strip ALL inline styles from list / structural elements ───
        // These are typically scraped Tailwind/framework CSS variable dumps
        // that override our custom CSS classes. Wipe them entirely.
        $html = preg_replace_callback(
            '/<(ul|ol|li|tr|td|th|tbody|thead|tfoot)(\s[^>]*)>/i',
            function ($m) {
                // Remove style="..." attribute completely
                $attrs = preg_replace('/\s+style\s*=\s*(["\']).*?\1/si', '', $m[2]);
                return '<' . $m[1] . $attrs . '>';
            },
            $html
        );
        // Also strip fixed pixel widths from any element
        $html = preg_replace('/\s*width\s*:\s*\d+px\s*;?/i',  '', $html);
        $html = preg_replace('/\s*font-family\s*:[^;\"]+;?/i', '', $html);

        // ── 2. Load into DOM ─────────────────────────────────────────────
        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8">'
            . '<div id="__bhs_wrap__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $root  = $xpath->query('//*[@id="__bhs_wrap__"]')->item(0);

        // ── 3. Enhance <table> elements ───────────────────────────────────
        foreach ($xpath->query('.//table', $root) as $table) {
            static::setAttr($table, 'class', 'bhs-spec-table');
            static::setAttr($table, 'style', '');   // remove inline styles
            static::setAttr($table, 'cellpadding', '0');
            static::setAttr($table, 'cellspacing', '0');

            // Remove inline styles from all cells too
            foreach ($xpath->query('.//td | .//th | .//tr | .//tbody', $table) as $cell) {
                static::setAttr($cell, 'style', '');
            }

            // Detect 2-column spec table (Article No / EAN style)
            $rows = $xpath->query('.//tr', $table);
            $is2col = true;
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td | .//th', $row);
                if ($cells->length !== 2) { $is2col = false; break; }
            }
            if ($is2col) {
                static::setAttr($table, 'class', 'bhs-spec-table bhs-spec-table--2col');
            }
        }

        // ── 4. Enhance <ul> / <ol> elements — strip inline styles too ───
        foreach ($xpath->query('.//ul', $root) as $ul) {
            $ul->removeAttribute('style');
            $ul->removeAttribute('class'); // remove scraped framework classes
            static::addClass($ul, 'bhs-features-list');
            foreach ($xpath->query('.//li', $ul) as $li) {
                $li->removeAttribute('style');
                $li->removeAttribute('class');
            }
        }
        foreach ($xpath->query('.//ol', $root) as $ol) {
            $ol->removeAttribute('style');
            static::addClass($ol, 'bhs-ordered-list');
            foreach ($xpath->query('.//li', $ol) as $li) {
                $li->removeAttribute('style');
            }
        }

        // ── 5. Clean up empty inline styles ──────────────────────────────
        foreach ($xpath->query('.//*[@style]', $root) as $el) {
            $s = trim((string) $el->getAttribute('style'));
            if ($s === '' || $s === ';') {
                $el->removeAttribute('style');
            }
        }

        // ── 6. Remove empty <p> tags at end ──────────────────────────────
        foreach ($xpath->query('.//p', $root) as $p) {
            if (trim($p->textContent) === '' && !$p->childNodes->length) {
                $p->parentNode->removeChild($p);
            }
        }

        // ── 7. Extract enhanced inner HTML ───────────────────────────────
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        // ── 8. Post-processing with regex (cleaner than DOM for simple replacements) ──
        // Tighten up multiple blank lines inside tags
        $out = preg_replace('/(\s*<br\s*\/?>\s*){3,}/i', '<br>', $out);
        // Remove the xml encoding PI if somehow carried over
        $out = preg_replace('/<\?xml[^>]+\?>\s*/i', '', $out);

        return trim($out);
    }

    /* ── Helpers ── */
    private static function setAttr(\DOMElement $el, string $attr, string $val): void
    {
        if ($val === '') {
            $el->removeAttribute($attr);
        } else {
            $el->setAttribute($attr, $val);
        }
    }

    private static function addClass(\DOMElement $el, string $cls): void
    {
        $existing = $el->getAttribute('class');
        $classes  = array_filter(explode(' ', $existing));
        if (!in_array($cls, $classes, true)) {
            $classes[] = $cls;
        }
        $el->setAttribute('class', implode(' ', $classes));
    }
}
