<?php

return [
    /*
    |--------------------------------------------------------------------------
    | On-Page SEO Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => env('SEO_ONPAGE_QUEUE', 'default'),

    'features' => [
        'meta_tags' => [
            'label'       => 'Meta Title & Description Generator',
            'description' => 'AI-powered meta tag generation optimized for CTR and keyword placement.',
            'inputs'      => ['url', 'title', 'keyword', 'content'],
            'output'      => 'json',
        ],
        'keyword_density' => [
            'label'       => 'Focus Keyword Density Analyzer',
            'description' => 'Analyze keyword frequency, density %, and LSI keyword coverage.',
            'inputs'      => ['keyword', 'content'],
            'output'      => 'json',
        ],
        'content_writer' => [
            'label'       => 'SEO Content / Article Writer',
            'description' => 'Full SEO article with H1-H3 structure, FAQs, and CTA.',
            'inputs'      => ['topic', 'keyword', 'url'],
            'output'      => 'json',
        ],
        'heading_structure' => [
            'label'       => 'Heading Structure (H1-H6)',
            'description' => 'Audit and rebuild heading hierarchy with keyword alignment.',
            'inputs'      => ['url', 'content', 'keyword'],
            'output'      => 'json',
        ],
        'alt_text' => [
            'label'       => 'Image Alt Text Bulk Generator',
            'description' => 'Generate SEO-optimized alt text for all page images.',
            'inputs'      => ['images', 'keyword', 'url'],
            'output'      => 'json',
        ],
        'internal_links' => [
            'label'       => 'Internal Linking Suggestions',
            'description' => 'Contextual internal link suggestions with anchor text.',
            'inputs'      => ['url', 'content', 'keyword', 'links'],
            'output'      => 'json',
        ],
        'schema_markup' => [
            'label'       => 'Schema Markup (JSON-LD)',
            'description' => 'Generate structured data for rich results in Google.',
            'inputs'      => ['url', 'title', 'content', 'keyword'],
            'output'      => 'json',
        ],
        'readability' => [
            'label'       => 'Readability Score & Improvement',
            'description' => 'Flesch-Kincaid analysis with actionable improvement suggestions.',
            'inputs'      => ['content', 'keyword'],
            'output'      => 'json',
        ],
        'seo_audit' => [
            'label'       => 'Full On-Page SEO Audit (20+ factors)',
            'description' => 'Comprehensive audit covering all on-page ranking factors.',
            'inputs'      => ['url', 'title', 'keyword', 'content', 'description'],
            'output'      => 'json',
        ],
        'open_graph' => [
            'label'       => 'Open Graph & Twitter Card Generator',
            'description' => 'Social sharing meta tags for Facebook and Twitter.',
            'inputs'      => ['url', 'title', 'description', 'keyword'],
            'output'      => 'json',
        ],
    ],

    'score' => [
        'passing_grade' => 70,
        'grade_map' => [
            90 => 'A+', 85 => 'A', 80 => 'A-',
            75 => 'B+', 70 => 'B', 65 => 'B-',
            60 => 'C+', 55 => 'C', 50 => 'C-',
            45 => 'D', 0  => 'F',
        ],
    ],
];
