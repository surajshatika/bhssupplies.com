<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class LocalSeoService extends AbstractSeoService
{
    public function optimize(array $payload): array
    {
        $url          = $payload['url']     ?? url('/');
        $businessName = config('seo.local_business.name', get_setting('website_name', config('app.name')));
        $businessType = config('seo.local_business.type', 'Store');
        $city         = config('seo.local_business.city', '');
        $region       = config('seo.local_business.region', '');
        $country      = config('seo.local_business.country', '');
        $keyword      = $payload['keyword'] ?? '';

        $prompt = <<<PROMPT
Create a comprehensive local SEO optimization plan for the following business.

Business Name: {$businessName}
Business Type: {$businessType}
Location: {$city}, {$region}, {$country}
Website: {$url}
Target Keyword: {$keyword}

Generate:
1. LocalBusiness schema JSON-LD
2. Google Business Profile optimization tips
3. Local citation building strategy
4. Local keyword variations
5. NAP (Name, Address, Phone) consistency checklist

Return JSON:
{
  "local_schema": {},
  "google_business_tips": [],
  "citation_sites": [
    {"name": "", "url": "", "priority": "high|medium|low"}
  ],
  "local_keywords": [],
  "nap_checklist": [],
  "on_page_local_tips": [],
  "review_strategy": ""
}
PROMPT;

        return $this->askForJson($prompt, 'You are a local SEO specialist.', [
            'local_schema'        => [],
            'google_business_tips' => [],
            'citation_sites'      => [],
            'local_keywords'      => [],
            'nap_checklist'       => [],
            'on_page_local_tips'  => [],
            'review_strategy'     => '',
        ]);
    }
}
