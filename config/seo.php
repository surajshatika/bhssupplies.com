<?php

return [
    'default_provider' => env('SEO_AI_PROVIDER', 'openai'),

    // Local WAMP installs can opt out explicitly. Production verifies remote
    // AI, Google, Bing, and PageSpeed TLS certificates by default.
    'ssl_verify' => env('SEO_SSL_VERIFY', env('APP_ENV', 'production') === 'production'),

    'provider_failover' => [
        'enabled' => env('SEO_AI_FAILOVER_ENABLED', true),
        'order' => array_values(array_filter(array_map('trim', explode(',', env('SEO_AI_FAILOVER_ORDER', 'claude,openai,gemini,grok'))))),
        'max_attempts' => env('SEO_AI_FAILOVER_MAX_ATTEMPTS', 4),
        'cooldown_enabled' => env('SEO_AI_PROVIDER_COOLDOWN_ENABLED', true),
        'failure_threshold' => env('SEO_AI_PROVIDER_FAILURE_THRESHOLD', 3),
        'cooldown_minutes' => env('SEO_AI_PROVIDER_COOLDOWN_MINUTES', 15),
        'request_timeout' => env('SEO_AI_REQUEST_TIMEOUT', 45),
        'connect_timeout' => env('SEO_AI_CONNECT_TIMEOUT', 5),
        'http_retries' => env('SEO_AI_HTTP_RETRIES', 2),
        'attempt_cost_usd' => [
            'openai' => env('SEO_OPENAI_ESTIMATED_REQUEST_USD', 0.0009),
            'claude' => env('SEO_CLAUDE_ESTIMATED_REQUEST_USD', 0.0207),
            'gemini' => env('SEO_GEMINI_ESTIMATED_REQUEST_USD', 0.0004),
            'grok' => env('SEO_GROK_ESTIMATED_REQUEST_USD', 0.0023),
        ],
        'database_settings' => true,
    ],

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
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
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

    // Production site URL used for internal-link detection in TruSEO scoring.
    // Set SEO_SITE_URL=https://www.bhssupplies.com in .env so links like
    // https://www.bhssupplies.com/shop are correctly counted as internal even
    // when running on localhost where APP_URL is 127.0.0.1.
    'site_url' => env('SEO_SITE_URL', env('APP_URL')),

    'search_console' => [
        'site_url' => env('SEO_GOOGLE_SEARCH_CONSOLE_SITE', env('APP_URL')),
    ],

    'local_business' => [
        'name' => env('SEO_LOCAL_BUSINESS_NAME'),
        'type' => env('SEO_LOCAL_BUSINESS_TYPE', 'Store'),
        'city' => env('SEO_LOCAL_CITY', 'Mississauga'),
        'region' => env('SEO_LOCAL_REGION', 'Ontario'),
        'country' => env('SEO_LOCAL_COUNTRY', 'Canada'),
        'phone' => env('SEO_LOCAL_PHONE', '+1 647 456 2244'),
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
            'technical_audit'     => 'Technical SEO Audit (live measured)',
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
