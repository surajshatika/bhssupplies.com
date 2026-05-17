<?php

namespace App\Services\Import;

use App\Services\Seo\Support\AbstractSeoService;
use Illuminate\Support\Arr;

class ImportOrchestrator extends AbstractSeoService
{
    public function __construct(string $providerName = 'openai')
    {
        parent::__construct($providerName);
    }

    /**
     * Enrich and validate parsed products using AI before database import.
     */
    public function enrichProducts(array $products, array $options = []): array
    {
        if (empty($products)) {
            return ['enriched' => [], 'message' => 'No products to enrich.'];
        }

        $enriched = [];
        foreach (array_slice($products, 0, 50) as $product) {
            $enriched[] = $this->enrichSingle($product, $options);
        }

        return [
            'enriched'       => $enriched,
            'total_enriched' => count($enriched),
            'skipped'        => max(0, count($products) - 50),
        ];
    }

    /**
     * Import from PDF file path.
     */
    public function importFromPdf(string $filePath, array $options = []): array
    {
        $parser  = new PdfProductParser();
        $parsed  = $parser->parseFile($filePath);

        if (isset($parsed['error'])) {
            return $parsed;
        }

        return $this->enrichProducts($parsed['products'] ?? [], $options);
    }

    /**
     * Import from URLs (scraping).
     */
    public function importFromUrls(array $urls, array $options = []): array
    {
        $scraper = new UrlProductScraper();
        $scraped = $scraper->scrapeUrls($urls, $options['selectors'] ?? []);

        if (empty($scraped['products'])) {
            return array_merge($scraped, ['enriched' => []]);
        }

        $enrichResult = $this->enrichProducts(array_values($scraped['products']), $options);
        return array_merge($scraped, $enrichResult);
    }

    /**
     * Map imported product array to the e-commerce platform's product schema.
     */
    public function mapToProductSchema(array $importedProducts): array
    {
        $mapped = [];
        foreach ($importedProducts as $product) {
            $sku        = Arr::get($product, 'sku') ?: Arr::get($product, 'gtin');
            $imageUrl   = Arr::get($product, 'image', '');
            $photos     = array_values(array_filter([$imageUrl]));
            $tags       = Arr::get($product, 'tags', []);
            $tagsStr    = is_array($tags) ? implode(',', $tags) : (string) $tags;

            $mapped[] = [
                'name'              => Arr::get($product, 'name', ''),
                'unit_price'        => Arr::get($product, 'price') ?? Arr::get($product, 'unit_price'),
                'description'       => Arr::get($product, 'enhanced_description')
                                        ?: Arr::get($product, 'description', ''),
                'short_description' => Arr::get($product, 'short_description', ''),
                'sku'               => $sku,
                'barcode'           => $sku,
                'image'             => $imageUrl,
                'photos'            => $photos,
                'category'          => Arr::get($product, 'suggested_category')
                                        ?: Arr::get($product, 'category', ''),
                'brand'             => Arr::get($product, 'brand', ''),
                'tags'              => $tagsStr,
                'seo_title'         => Arr::get($product, 'seo_title'),
                'seo_description'   => Arr::get($product, 'seo_description'),
                'import_source'     => Arr::get($product, 'import_source', 'import'),
                'meta'              => Arr::only($product, ['sku', 'url', 'gtin', 'brand']),
            ];
        }
        return $mapped;
    }

    private function enrichSingle(array $product, array $options): array
    {
        $name        = $product['name']        ?? '';
        $description = $product['description'] ?? '';
        $price       = $product['price']       ?? '';
        $sku         = $product['sku']         ?? '';
        $url         = $product['url']         ?? '';
        $category    = $product['category']    ?? '';

        if (empty($name)) {
            return $product;
        }

        $urlNote = $url ? "\nProduct URL: {$url}" : '';
        $skuNote = $sku ? "\nSKU / Model: {$sku}" : '';
        $catNote = $category ? "\nHint category: {$category}" : '';

        $prompt = <<<PROMPT
Enrich the following product data with SEO-optimized title, description, and categorization.
Use your knowledge of the product (name, SKU, URL) to fill in realistic details if the description is missing.

Product Name: {$name}{$skuNote}{$catNote}
Price: {$price}
Raw Description: {$description}{$urlNote}

Return JSON:
{
  "name": "",
  "seo_title": "",
  "seo_description": "",
  "enhanced_description": "",
  "suggested_category": "",
  "tags": [],
  "brand": "",
  "material": "",
  "target_audience": ""
}
PROMPT;

        $enriched = $this->askForJson($prompt, 'You are a product catalog and SEO specialist.', [
            'name'                 => $name,
            'seo_title'            => $name,
            'seo_description'      => $description,
            'enhanced_description' => $description,
            'suggested_category'   => '',
            'tags'                 => [],
        ]);

        return array_merge($product, $enriched);
    }
}
