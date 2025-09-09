<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'swop' => [
        'base_url' => env('SWOP_API_BASE_URL', 'https://swop.cx/rest'),
        'cache_ttl' => env('SWOP_API_CACHE_TTL', 3600),
        'api_key' => env('SWOP_API_KEY', ''),
    ],

    'influxdb' => [
        'url' => env('INFLUXDB_URL', 'http://influxdb:8086'),
        'token' => env('INFLUXDB_TOKEN'),
        'bucket' => env('INFLUXDB_BUCKET', 'currency_converter'),
        'org' => env('INFLUXDB_ORG', 'currency-converter'),
    ],

    'monitoring' => [
        'enabled' => env('METRICS_ENABLED', false),
        'sample_rate' => env('METRICS_SAMPLE_RATE', 1.0),
        'influxdb_url' => env('INFLUXDB_URL'),
        'influxdb_token' => env('INFLUXDB_TOKEN'),
        'influxdb_org' => env('INFLUXDB_ORG'),
        'influxdb_bucket' => env('INFLUXDB_BUCKET'),
    ],
];
