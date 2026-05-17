<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social Media Automation — Global Config
    |--------------------------------------------------------------------------
    */

    'default_ai_provider' => env('SOCIAL_AI_PROVIDER', 'openai'),

    'ssl_verify' => env('SOCIAL_SSL_VERIFY', true),

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    */
    'ai_providers' => [
        'openai' => [
            'label'    => 'ChatGPT (OpenAI)',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'model'    => env('SOCIAL_OPENAI_MODEL', 'gpt-4o'),
            'api_key'  => env('SOCIAL_OPENAI_API_KEY', ''),
        ],
        'claude' => [
            'label'    => 'Claude (Anthropic)',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'model'    => env('SOCIAL_CLAUDE_MODEL', 'claude-sonnet-4-6'),
            'api_key'  => env('SOCIAL_CLAUDE_API_KEY', ''),
        ],
        'grok' => [
            'label'    => 'Grok (xAI)',
            'endpoint' => 'https://api.x.ai/v1/chat/completions',
            'model'    => env('SOCIAL_GROK_MODEL', 'grok-beta'),
            'api_key'  => env('SOCIAL_GROK_API_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Platforms
    |--------------------------------------------------------------------------
    | key        => internal slug
    | label      => display name
    | icon       => font-awesome/line-awesome class
    | region     => 'global' | 'canada' | 'both'
    | char_limit => max characters (null = unlimited)
    |--------------------------------------------------------------------------
    */
    'platforms' => [
        'twitter'   => ['label' => 'X / Twitter',        'icon' => 'lab la-twitter',   'region' => 'global', 'char_limit' => 280],
        'facebook'  => ['label' => 'Facebook Page',      'icon' => 'lab la-facebook-f', 'region' => 'global', 'char_limit' => 63206],
        'instagram' => ['label' => 'Instagram Business', 'icon' => 'lab la-instagram',  'region' => 'global', 'char_limit' => 2200],
        'linkedin'  => ['label' => 'LinkedIn',           'icon' => 'lab la-linkedin-in','region' => 'global', 'char_limit' => 3000],
        'youtube'   => ['label' => 'YouTube Community',  'icon' => 'lab la-youtube',    'region' => 'global', 'char_limit' => 5000],
        'telegram'  => ['label' => 'Telegram Channel',   'icon' => 'lab la-telegram',   'region' => 'global', 'char_limit' => 4096],
        'whatsapp'  => ['label' => 'WhatsApp Business',  'icon' => 'lab la-whatsapp',   'region' => 'global', 'char_limit' => 4096],
        'tiktok'    => ['label' => 'TikTok Business',    'icon' => 'lab la-tiktok',     'region' => 'both',   'char_limit' => 2200],
        'pinterest' => ['label' => 'Pinterest',          'icon' => 'lab la-pinterest',  'region' => 'both',   'char_limit' => 500],
        'reddit'    => ['label' => 'Reddit',             'icon' => 'lab la-reddit',     'region' => 'both',   'char_limit' => 40000],
        'snapchat'  => ['label' => 'Snapchat Business',  'icon' => 'lab la-snapchat',   'region' => 'canada', 'char_limit' => 250],
        'discord'   => ['label' => 'Discord',            'icon' => 'lab la-discord',    'region' => 'both',   'char_limit' => 2000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Tones
    |--------------------------------------------------------------------------
    */
    'tones' => [
        'professional'  => 'Professional & Authoritative',
        'casual'        => 'Casual & Friendly',
        'promotional'   => 'Promotional & Sales',
        'educational'   => 'Educational & Informative',
        'urgent'        => 'Urgent & Action-Driven',
        'storytelling'  => 'Storytelling & Engaging',
        'humorous'      => 'Humorous & Light',
        'inspirational' => 'Inspirational & Motivational',
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Types
    |--------------------------------------------------------------------------
    */
    'campaign_types' => [
        'product_launch'  => 'Product Launch',
        'sale_promotion'  => 'Sale & Promotion',
        'brand_awareness' => 'Brand Awareness',
        'seasonal'        => 'Seasonal / Holiday',
        'educational'     => 'Educational Content',
        'testimonial'     => 'Customer Testimonial',
        'news_update'     => 'News & Update',
        'custom'          => 'Custom',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */
    'queue'  => env('SOCIAL_QUEUE_NAME', 'social'),
    'retry'  => 3,
    'backoff'=> 120,

];
