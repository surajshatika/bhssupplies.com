<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class EcommerceSeoService extends AbstractSeoService
{
    public function optimize(array $payload): array
    {
        $url       = $payload['url']     ?? url('/');
        $keyword   = $payload['keyword'] ?? '';
        $title     = $payload['title']   ?? '';
        $content   = $payload['content'] ?? '';
        $productId = $payload['product_id'] ?? null;

        $context = $productId
            ? "Product ID: {$productId}\nProduct Name: {$title}"
            : "Category/Page: {$title}";

        $prompt = <<<PROMPT
Optimize the following e-commerce page/product for SEO.

Website URL: {$url}
{$context}
Focus Keyword: {$keyword}
Content/Description:
{$content}

Provide complete e-commerce SEO optimization:

Return JSON:
{
  "meta_title": "",
  "meta_description": "",
  "product_schema": {},
  "breadcrumb_schema": {},
  "optimized_description": "",
  "bullet_points": [],
  "category_keywords": [],
  "related_products_strategy": "",
  "review_schema_template": {},
  "url_slug_suggestion": "",
  "image_alt_suggestions": [],
  "internal_linking_plan": [],
  "recommendations": []
}
PROMPT;

        return $this->askForJson($prompt, 'You are an e-commerce SEO specialist.', [
            'meta_title'                => $title,
            'meta_description'          => '',
            'product_schema'            => [],
            'breadcrumb_schema'         => [],
            'optimized_description'     => '',
            'bullet_points'             => [],
            'category_keywords'         => [],
            'related_products_strategy' => '',
            'review_schema_template'    => [],
            'url_slug_suggestion'       => '',
            'image_alt_suggestions'     => [],
            'internal_linking_plan'     => [],
            'recommendations'           => [],
        ]);
    }
}
