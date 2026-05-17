<?php

return [
    'enabled'           => env('AMAZON_ENABLED', false),
    'sandbox'           => env('AMAZON_SANDBOX', true),
    'marketplace_id'    => env('AMAZON_MARKETPLACE_ID', 'A2EUQ1WTGCTBG2'), // Amazon Canada
    'lwa_client_id'     => env('AMAZON_LWA_CLIENT_ID'),
    'lwa_client_secret' => env('AMAZON_LWA_CLIENT_SECRET'),
    'aws_access_key'    => env('AMAZON_AWS_ACCESS_KEY'),
    'aws_secret_key'    => env('AMAZON_AWS_SECRET_KEY'),
    'refresh_token'     => env('AMAZON_REFRESH_TOKEN'),
    'seller_id'         => env('AMAZON_SELLER_ID'),
    'region'            => env('AMAZON_REGION', 'us-east-1'),
    'default_brand'     => env('AMAZON_DEFAULT_BRAND', 'BHS Supplies'),
    'sku_prefix'        => env('AMAZON_SKU_PREFIX', 'BHS-'),
];
