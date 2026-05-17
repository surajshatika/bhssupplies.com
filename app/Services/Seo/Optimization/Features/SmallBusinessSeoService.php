<?php

namespace App\Services\Seo\Optimization\Features;

use App\Services\Seo\Support\AbstractSeoService;

class SmallBusinessSeoService extends AbstractSeoService
{
    public function handle(array $payload): array
    {
        $businessName = $payload['business_name'] ?? get_setting('seo_local_business_name', get_setting('website_name'));
        $businessType = $payload['business_type'] ?? get_setting('seo_local_business_type', 'Store');
        $city         = $payload['city'] ?? config('seo.local_business.city', 'Noida');
        $phone        = $payload['phone'] ?? get_setting('business_phone', '');
        $address      = $payload['address'] ?? get_setting('business_address', '');
        $website      = $payload['website'] ?? url('/');
        $niche        = $payload['niche'] ?? 'e-commerce';

        $prompt = "You are a small business SEO expert. Create a comprehensive SEO action plan for:\n"
            . "Business: {$businessName}\n"
            . "Type: {$businessType}\n"
            . "Location: {$city}\n"
            . "Website: {$website}\n"
            . "Niche: {$niche}\n\n"
            . "Provide a prioritized SEO action plan with:\n"
            . "1. Google Business Profile optimization checklist\n"
            . "2. Local citation building strategy (top 10 directories)\n"
            . "3. On-page SEO quick wins (top 5)\n"
            . "4. Local keyword targeting strategy\n"
            . "5. Review generation strategy\n"
            . "6. Social proof building\n"
            . "7. Monthly content calendar suggestions\n"
            . "8. Backlink acquisition for small businesses\n"
            . "Format as actionable checklist with priorities.";

        $plan = $this->ai()->generate($prompt, 'You are a small business SEO consultant with 10+ years of experience.');

        $schema = $this->buildLocalBusinessSchema($businessName, $businessType, $city, $phone, $address, $website);

        $citations = $this->getTopCitationSites();

        return [
            'business_name' => $businessName,
            'city'          => $city,
            'seo_plan'      => $plan,
            'local_schema'  => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'citation_sites'=> $citations,
        ];
    }

    protected function buildLocalBusinessSchema(string $name, string $type, string $city, string $phone, string $address, string $website): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => $type ?: 'LocalBusiness',
            'name'       => $name,
            'url'        => $website,
            'telephone'  => $phone,
            'address'    => [
                '@type'           => 'PostalAddress',
                'addressLocality' => $city,
                'addressCountry'  => config('seo.local_business.country', 'IN'),
            ],
        ];
    }

    protected function getTopCitationSites(): array
    {
        return [
            ['name' => 'Google Business Profile', 'url' => 'https://business.google.com', 'priority' => 'critical'],
            ['name' => 'Bing Places',             'url' => 'https://www.bingplaces.com', 'priority' => 'high'],
            ['name' => 'IndiaMART',               'url' => 'https://www.indiamart.com',  'priority' => 'high'],
            ['name' => 'Justdial',                'url' => 'https://www.justdial.com',   'priority' => 'high'],
            ['name' => 'Sulekha',                 'url' => 'https://www.sulekha.com',    'priority' => 'medium'],
            ['name' => 'TradeIndia',              'url' => 'https://www.tradeindia.com', 'priority' => 'medium'],
            ['name' => 'Yellow Pages India',      'url' => 'https://www.yellowpages.in', 'priority' => 'medium'],
            ['name' => 'Yelp',                    'url' => 'https://www.yelp.com',       'priority' => 'low'],
        ];
    }
}
