<?php

return [
    'default_provider' => env('SEO_AI_PROVIDER', 'openai'),

    // Disable SSL cert verification for local/WAMP development.
    // Set SEO_SSL_VERIFY=true in production.
    'ssl_verify' => env('SEO_SSL_VERIFY', false),

    'providers' => [
        'openai' => [
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'api_key' => env('OPENAI_API_KEY'),
            'endpoint' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        ],
        'claude' => [
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
            'api_key' => env('ANTHROPIC_API_KEY'),
            'endpoint' => env('ANTHROPIC_API_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
        ],
        'gemini' => [
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'api_key' => env('GEMINI_API_KEY'),
            'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
        ],
        'grok' => [
            'model' => env('GROK_MODEL', 'grok-3-mini'),
            'api_key' => env('GROK_API_KEY'),
            'endpoint' => env('GROK_API_ENDPOINT', 'https://api.x.ai/v1/chat/completions'),
        ],
    ],

    'queue' => [
        'on_page' => env('SEO_ONPAGE_QUEUE', 'default'),
        'off_page' => env('SEO_OFFPAGE_QUEUE', 'default'),
        'optimization' => env('SEO_OPTIMIZATION_QUEUE', 'default'),
    ],

    'search_console' => [
        'site_url' => env('SEO_GOOGLE_SEARCH_CONSOLE_SITE', env('APP_URL')),
    ],

    'local_business' => [
        'name' => env('SEO_LOCAL_BUSINESS_NAME'),
        'type' => env('SEO_LOCAL_BUSINESS_TYPE', 'Store'),
        'city' => env('SEO_LOCAL_CITY', 'Noida'),
        'region' => env('SEO_LOCAL_REGION', 'Uttar Pradesh'),
        'country' => env('SEO_LOCAL_COUNTRY', 'India'),
    ],

    'indexnow' => [
        'key' => env('SEO_INDEXNOW_KEY'),
        'host' => env('SEO_INDEXNOW_HOST', 'www.bing.com'),
    ],

    'webmaster' => [
        'google_verification' => env('SEO_GOOGLE_VERIFICATION'),
        'bing_verification' => env('SEO_BING_VERIFICATION'),
        'yandex_verification' => env('SEO_YANDEX_VERIFICATION'),
        'pinterest_verification' => env('SEO_PINTEREST_VERIFICATION'),
        'baidu_verification' => env('SEO_BAIDU_VERIFICATION'),
    ],

    'image_generation' => [
        'provider' => env('SEO_IMAGE_AI_PROVIDER', 'openai'),
        'model' => env('SEO_IMAGE_AI_MODEL', 'dall-e-3'),
        'size' => env('SEO_IMAGE_AI_SIZE', '1024x1024'),
    ],

    'features' => [
        'on_page' => [
            'local_onpage_blueprint' => 'Local On-Page Blueprint',
            'meta_tags'           => 'Meta Title & Description Generator',
            'keyword_density'     => 'Focus Keyword Density Analyzer',
            'content_writer'      => 'SEO Content / Article Writer',
            'heading_structure'   => 'Heading Structure (H1-H6)',
            'alt_text'            => 'Image Alt Text Bulk Generator',
            'internal_links'      => 'Internal Linking Suggestions',
            'schema_markup'       => 'Schema Markup (JSON-LD)',
            'readability'         => 'Readability Score & Improvement',
            'seo_audit'           => 'Full On-Page SEO Audit (20+ factors)',
            'open_graph'          => 'Open Graph & Twitter Card Generator',
            'truseo_analysis'     => 'TruSEO On-Page Analysis Score',
            'breadcrumbs'         => 'Breadcrumb Schema Generator',
            'image_seo'           => 'Image SEO Optimizer',
        ],
        'off_page' => [
            'ai_backlink_campaign'=> 'AI Backlink Campaign Generator',
            'backlink_outreach'   => 'Backlink Outreach Email Generator',
            'guest_post_topics'   => 'Guest Post Topic Generator',
            'guest_post_article'  => 'Guest Post Full Article Writer',
            'social_signal_posts' => 'Social Media Signal Posts',
            'press_release'       => 'Press Release Generator',
            'anchor_text_profile' => 'Anchor Text Profile Builder',
        ],
        'optimization' => [
            'page_speed'          => 'Page Speed Analyzer & Fixer',
            'technical_refresh'   => 'Automated Technical Refresh',
            'technical_audit'     => 'Technical SEO Audit',
            'competitor_gap'      => 'Competitor Keyword Gap Analyzer',
            'smart_sitemap'       => 'Smart XML Sitemap Generator',
            'video_sitemap'       => 'Video SEO Sitemap',
            'blog_sitemap'        => 'Blog / News Sitemap',
            'rss_content'         => 'RSS Content Feed',
            'robots'              => 'Robots.txt AI Optimizer',
            'canonical'           => 'Canonical URL Manager',
            'redirection_manager' => 'Redirection Manager',
            'broken_links'        => 'Broken Link Checker',
            'faq_schema'          => 'FAQ Schema Generator',
            'local_seo'           => 'Local SEO Optimizer',
            'ecommerce_seo'       => 'E-commerce Product SEO',
            'small_business_seo'  => 'Small Business SEO',
            'score_dashboard'     => 'SEO Score Dashboard',
            'indexnow'            => 'IndexNow Submission',
            'link_assistant'      => 'Link Assistant',
            'webmaster_tools'     => 'Webmaster Tools',
            'search_statistics'   => 'Search Statistics',
            'post_index_status'   => 'Post Index Status',
            'keyword_rank_tracker'=> 'Keyword Rank Tracker',
            'seo_revisions'       => 'SEO Revisions',
            'llms_txt'            => 'LLMs.txt Generator',
            'ai_writing_assistant'=> 'AI Writing Assistant',
            'ai_image_generator'  => 'AI Image Generator',
            'ai_assistant'        => 'AI SEO Assistant',
        ],
    ],
];
