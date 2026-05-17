<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Technical SEO Optimization Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => env('SEO_OPTIMIZATION_QUEUE', 'default'),

    'sitemap' => [
        'output_path'     => null, // resolved at runtime: public_path('sitemap.xml')
        'public_url'      => null, // resolved at runtime: url('sitemap.xml')
        'default_priority' => '0.8',
        'default_changefreq' => 'weekly',
        'include_products' => true,
        'include_categories' => true,
        'include_pages'    => true,
    ],

    'robots' => [
        'output_path' => null, // resolved at runtime: public_path('robots.txt')
        'disallow'    => [
            '/admin/',
            '/checkout/',
            '/cart/',
            '/account/',
            '/api/',
            '/?*',
        ],
        'allow' => [
            '/admin/css/',
            '/admin/js/',
        ],
    ],

    'redirects' => [
        'default_code'  => 301,
        'cache_minutes' => 60,
    ],

    'broken_links' => [
        'timeout_seconds' => 8,
        'max_urls'        => 50,
        'user_agent'      => 'Mozilla/5.0 (compatible; SeoBot/1.0)',
    ],

    'page_speed' => [
        'api_key'  => env('GOOGLE_PAGESPEED_API_KEY'),
        'strategy' => env('SEO_PAGESPEED_STRATEGY', 'mobile'),
    ],

    'score_dashboard' => [
        'cron'        => env('SEO_SCORE_CRON', '0 0 * * 1'), // Every Monday
        'history_weeks' => 12,
    ],

    'features' => [
        'page_speed' => [
            'label'   => 'Page Speed Analyzer & Fixer',
            'inputs'  => ['url'],
        ],
        'technical_audit' => [
            'label'   => 'Technical SEO Audit',
            'inputs'  => ['url'],
        ],
        'competitor_gap' => [
            'label'   => 'Competitor Keyword Gap Analyzer',
            'inputs'  => ['url', 'our_keywords', 'comp1_keywords', 'comp2_keywords'],
        ],
        'sitemap' => [
            'label'   => 'Dynamic Sitemap Generator',
            'inputs'  => ['url', 'links'],
        ],
        'robots' => [
            'label'   => 'Robots.txt AI Optimizer',
            'inputs'  => ['url', 'content'],
        ],
        'canonical' => [
            'label'   => 'Canonical URL Manager',
            'inputs'  => ['url', 'links'],
        ],
        'redirects' => [
            'label'   => 'Redirect Chain Fixer',
            'inputs'  => ['url', 'links'],
        ],
        'broken_links' => [
            'label'   => 'Broken Link Detector',
            'inputs'  => ['links'],
        ],
        'faq_schema' => [
            'label'   => 'FAQ Schema Auto-Generator',
            'inputs'  => ['url', 'content', 'keyword', 'faq'],
        ],
        'local_seo' => [
            'label'   => 'Local SEO Optimizer',
            'inputs'  => ['url', 'keyword'],
        ],
        'ecommerce_seo' => [
            'label'   => 'E-commerce Product SEO',
            'inputs'  => ['url', 'title', 'keyword', 'content', 'product_id'],
        ],
        'score_dashboard' => [
            'label'   => 'SEO Score Dashboard',
            'inputs'  => ['url'],
        ],
    ],
];
